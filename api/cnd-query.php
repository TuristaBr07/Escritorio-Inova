<?php
header('Content-Type: application/json; charset=UTF-8');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Aceitar apenas POST autenticado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'erro' => 'Método não permitido.']);
    exit;
}

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'erro' => 'Não autenticado.']);
    exit;
}

$usuario = usuarioLogado();

// Validar CNPJ
$cnpjRaw = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
$tipo    = in_array($_POST['tipo'] ?? '', ['federal', 'inss', 'estadual_sp'])
         ? $_POST['tipo']
         : 'federal';

if (strlen($cnpjRaw) !== 14) {
    echo json_encode(['success' => false, 'erro' => 'CNPJ deve ter 14 dígitos.']);
    exit;
}

if (!validarCNPJ($cnpjRaw)) {
    echo json_encode(['success' => false, 'erro' => 'CNPJ inválido. Verifique os dígitos verificadores.']);
    exit;
}

// ── Executar consulta conforme provedor configurado ────────────────
$resultado = match(CND_API_PROVIDER) {
    'infosimples' => consultarInfosimples($cnpjRaw, $tipo),
    'netrin'      => consultarNetrin($cnpjRaw, $tipo),
    default       => consultarDemo($cnpjRaw, $tipo),
};

if (!$resultado['success']) {
    echo json_encode($resultado);
    exit;
}

// ── Salvar no banco e log de auditoria ────────────────────────────
$pdo  = getDB();
$stmt = $pdo->prepare("
    INSERT INTO consultas_cnd (usuario_id, cnpj, tipo, status_cnd, validade, numero_cert, resultado)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $usuario['id'],
    $cnpjRaw,
    $tipo,
    $resultado['status'],
    $resultado['validade_db'] ?? null,
    $resultado['numero']      ?? null,
    json_encode($resultado),
]);
$resultado['id'] = $pdo->lastInsertId();

registrarAuditLog($usuario['id'], 'CND_CONSULTA', "cnpj={$cnpjRaw}&tipo={$tipo}");

echo json_encode($resultado);
exit;


// ── Funções de Integração ─────────────────────────────────────────

function consultarDemo(string $cnpj, string $tipo): array {
    // Modo demonstração — usa último dígito para simular regular/irregular
    $ultimo = (int)substr($cnpj, -1);
    $isReg  = ($ultimo % 2 === 0);

    $emissaoTs  = time();
    $validadeTs = strtotime('+180 days', $emissaoTs);

    if ($isReg) {
        return [
            'success'       => true,
            'cnpj'          => $cnpj,
            'cnpj_formatado'=> formatarCNPJ($cnpj),
            'tipo'          => $tipo,
            'status'        => 'Regular',
            'validade'      => date('d/m/Y', $validadeTs),
            'validade_db'   => date('Y-m-d', $validadeTs),
            'emissao'       => date('d/m/Y H:i', $emissaoTs),
            'numero'        => strtoupper(substr(md5($cnpj . $tipo), 0, 8)) . '-' . date('Y'),
            'mensagem'       => 'Certidão emitida. Nenhum débito encontrado junto à ' . tipoLabel($tipo) . '.',
            'modo'          => 'demo',
        ];
    }

    return [
        'success'        => true,
        'cnpj'           => $cnpj,
        'cnpj_formatado' => formatarCNPJ($cnpj),
        'tipo'           => $tipo,
        'status'         => 'Irregular',
        'validade'       => null,
        'validade_db'    => null,
        'emissao'        => date('d/m/Y H:i', $emissaoTs),
        'numero'         => null,
        'mensagem'        => 'Foram encontrados débitos junto à ' . tipoLabel($tipo) . '. Certidão Positiva emitida.',
        'modo'           => 'demo',
    ];
}

function consultarInfosimples(string $cnpj, string $tipo): array {
    // Documentação: https://infosimples.com/consultas/receita-federal-pgfn/
    $endpoints = [
        'federal'     => 'https://api.infosimples.com/api/v2/consultas/receita-federal/pgfn/cnd',
        'inss'        => 'https://api.infosimples.com/api/v2/consultas/inss/cnd',
        'estadual_sp' => 'https://api.infosimples.com/api/v2/consultas/sefaz/sp/cnd',
    ];
    $url = $endpoints[$tipo] ?? $endpoints['federal'];

    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Token " . CND_API_KEY,
        'content' => http_build_query(['cnpj' => $cnpj, 'token' => CND_API_KEY]),
        'timeout' => 30,
    ]]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return ['success' => false, 'erro' => 'Falha na conexão com a API Infosimples.'];

    $data = json_decode($raw, true);
    if (!$data || ($data['code'] ?? 0) !== 200) {
        return ['success' => false, 'erro' => $data['message'] ?? 'Erro retornado pela API.'];
    }

    $item = $data['data'][0] ?? [];
    return [
        'success'        => true,
        'cnpj'           => $cnpj,
        'cnpj_formatado' => formatarCNPJ($cnpj),
        'tipo'           => $tipo,
        'status'         => $item['situacao']    ?? 'Desconhecido',
        'validade'       => $item['data_validade'] ? date('d/m/Y', strtotime($item['data_validade'])) : null,
        'validade_db'    => $item['data_validade'] ?? null,
        'emissao'        => $item['data_emissao']  ?? date('d/m/Y H:i'),
        'numero'         => $item['numero_certidao'] ?? null,
        'mensagem'        => $item['descricao'] ?? '',
        'modo'           => 'infosimples',
    ];
}

function consultarNetrin(string $cnpj, string $tipo): array {
    // Documentação: https://netrin.com.br/api/receita-federal-pgfn-cnd-federal/
    $url = 'https://api.netrin.com.br/v2/cnd?' . http_build_query([
        'cnpj'  => $cnpj,
        'token' => CND_API_KEY,
    ]);

    $raw = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30]]));
    if ($raw === false) return ['success' => false, 'erro' => 'Falha na conexão com a API Netrin.'];

    $data = json_decode($raw, true);
    if (!$data || isset($data['error'])) {
        return ['success' => false, 'erro' => $data['error'] ?? 'Erro retornado pela API Netrin.'];
    }

    return [
        'success'        => true,
        'cnpj'           => $cnpj,
        'cnpj_formatado' => formatarCNPJ($cnpj),
        'tipo'           => $tipo,
        'status'         => $data['situacao']       ?? 'Desconhecido',
        'validade'       => $data['validade']        ? date('d/m/Y', strtotime($data['validade'])) : null,
        'validade_db'    => $data['validade']        ?? null,
        'emissao'        => $data['data_emissao']    ?? date('d/m/Y H:i'),
        'numero'         => $data['numero_certidao'] ?? null,
        'mensagem'        => $data['descricao']      ?? '',
        'modo'           => 'netrin',
    ];
}

function tipoLabel(string $tipo): string {
    return match($tipo) {
        'inss'        => 'INSS (Dataprev)',
        'estadual_sp' => 'Fazenda do Estado de SP',
        default       => 'Receita Federal / PGFN',
    };
}
