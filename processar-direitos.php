<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: solicitar-direitos.html");
    exit;
}

// Validar campos obrigatórios
if (empty($_POST["nome"]) || empty($_POST["email"]) || empty($_POST["tipo_direito"]) || empty($_POST["confirmacao"])) {
    header("Location: solicitar-direitos.html?erro=campos");
    exit;
}

$nome     = htmlspecialchars(strip_tags(trim($_POST["nome"])));
$email    = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
$tipo     = htmlspecialchars(strip_tags(trim($_POST["tipo_direito"])));
$detalhes = htmlspecialchars(strip_tags(trim($_POST["detalhes"] ?? "")));

// Validar e-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: solicitar-direitos.html?erro=email");
    exit;
}

// Validar tipo de direito
$tipos_validos = ["acesso", "correcao", "exclusao", "portabilidade", "oposicao", "revogacao", "outro"];
if (!in_array($tipo, $tipos_validos)) {
    header("Location: solicitar-direitos.html?erro=tipo");
    exit;
}

// Gerar ID único e prazo de resposta
$id_solicitacao = "LGPD-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
$data_prazo     = date("d/m/Y", strtotime("+15 days"));
$data_hora      = date("Y-m-d H:i:s");
$ip             = $_SERVER["REMOTE_ADDR"];

// Registrar evidência em log local (LGPD)
$log_dir = __DIR__ . "/logs";
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0750, true);
}
$registro = json_encode([
    "id"        => $id_solicitacao,
    "tipo"      => $tipo,
    "nome"      => $nome,
    "email"     => $email,
    "detalhes"  => $detalhes,
    "timestamp" => $data_hora,
    "ip"        => $ip,
    "status"    => "pendente",
    "prazo"     => $data_prazo,
], JSON_UNESCAPED_UNICODE);
file_put_contents($log_dir . "/solicitacoes_lgpd.json", $registro . "\n", FILE_APPEND | LOCK_EX);

// Rótulos legíveis por tipo
$tipos_label = [
    "acesso"        => "Acesso aos dados",
    "correcao"      => "Correção de dados",
    "exclusao"      => "Exclusão de dados",
    "portabilidade" => "Portabilidade de dados",
    "oposicao"      => "Oposição ao tratamento",
    "revogacao"     => "Revogação de consentimento",
    "outro"         => "Outra solicitação",
];
$tipo_label = $tipos_label[$tipo];

// E-mail para o responsável do escritório
$msg_responsavel  = "Nova solicitação de direito LGPD recebida.\n\n";
$msg_responsavel .= "ID: {$id_solicitacao}\n";
$msg_responsavel .= "Tipo: {$tipo_label}\n";
$msg_responsavel .= "Nome: {$nome}\n";
$msg_responsavel .= "E-mail: {$email}\n";
$msg_responsavel .= "Detalhes: {$detalhes}\n";
$msg_responsavel .= "Data/Hora: {$data_hora}\n";
$msg_responsavel .= "Prazo legal para resposta: {$data_prazo}\n";

$headers_resp  = "From: contato@inovacontabil.com\r\n";
$headers_resp .= "Content-Type: text/plain; charset=UTF-8\r\n";
mail("contato@inovacontabil.com", "Solicitação LGPD #{$id_solicitacao} — {$tipo_label}", $msg_responsavel, $headers_resp);

// E-mail de confirmação para o titular
$msg_titular  = "Olá {$nome},\n\n";
$msg_titular .= "Recebemos sua solicitação de direito LGPD.\n\n";
$msg_titular .= "ID da solicitação: {$id_solicitacao}\n";
$msg_titular .= "Tipo: {$tipo_label}\n";
$msg_titular .= "Prazo para resposta: até {$data_prazo}\n\n";
$msg_titular .= "Dúvidas? Entre em contato: contato@inovacontabil.com\n\n";
$msg_titular .= "Inova Contábil\n(15) 3281-4991";

$headers_tit  = "From: contato@inovacontabil.com\r\n";
$headers_tit .= "Content-Type: text/plain; charset=UTF-8\r\n";
mail($email, "Confirmação de Solicitação LGPD — {$id_solicitacao}", $msg_titular, $headers_tit);

// Redirecionar para página de confirmação
header("Location: obrigado.html?lgpd=1&id=" . urlencode($id_solicitacao));
exit;
?>
