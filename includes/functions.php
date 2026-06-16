<?php
require_once __DIR__ . '/auth.php';

function validarCNPJ(string $cnpj): bool {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) !== 14) return false;
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

    $w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($i = 0; $i < 12; $i++) $sum += (int)$cnpj[$i] * $w1[$i];
    $r = $sum % 11;
    if ((int)$cnpj[12] !== ($r < 2 ? 0 : 11 - $r)) return false;

    $w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    for ($i = 0; $i < 13; $i++) $sum += (int)$cnpj[$i] * $w2[$i];
    $r = $sum % 11;
    return (int)$cnpj[13] === ($r < 2 ? 0 : 11 - $r);
}

function formatarCNPJ(string $cnpj): string {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    if (strlen($cnpj) !== 14) return $cnpj;
    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
}

function sanitizar(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

function registrarAuditLog(int $usuarioId, string $acao, string $dado = ''): void {
    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare('INSERT INTO audit_log (usuario_id, acao, dado, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([$usuarioId, $acao, $dado, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        // Falha silenciosa — não deve interromper o fluxo principal
    }
}

function flashMensagem(string $tipo, string $mensagem): void {
    iniciarSessao();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function obterFlash(): ?array {
    iniciarSessao();
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
