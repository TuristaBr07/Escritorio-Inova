<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Método não permitido.";
    exit;
}

// Validar consentimento LGPD (obrigatório — Art. 8, Lei nº 13.709/2018)
if (empty($_POST["consentimento"])) {
    echo "Você deve concordar com a Política de Privacidade para enviar a mensagem.";
    exit;
}

// Captura e sanitiza os dados do formulário
$nome     = strip_tags(trim($_POST["nome"]));
$email    = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
$mensagem = strip_tags(trim($_POST["mensagem"]));

// Verifica se todos os campos foram preenchidos
if (empty($nome) || empty($email) || empty($mensagem)) {
    echo "Por favor, preencha todos os campos.";
    exit;
}

// Valida o email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email inválido.";
    exit;
}

// Registrar evidência de consentimento (LGPD)
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0750, true);
}
$evidencia = json_encode([
    'timestamp'  => date('Y-m-d H:i:s'),
    'ip'         => $_SERVER['REMOTE_ADDR'],
    'email'      => $email,
    'consentimento' => 'sim',
    'finalidade' => 'responder contato',
], JSON_UNESCAPED_UNICODE);
file_put_contents($log_dir . '/lgpd_consentimentos.json', $evidencia . "\n", FILE_APPEND | LOCK_EX);

// Configurações do email
$destinatario   = "contato@inovacontabil.com";
$assunto        = "Nova mensagem de contato de $nome";
$mensagem_email = "Nome: $nome\nEmail: $email\nMensagem:\n$mensagem";

// Cabeçalhos
$headers  = "From: $email\r\nReply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Tenta enviar o email
if (mail($destinatario, $assunto, $mensagem_email, $headers)) {
    header("Location: obrigado.html");
    exit;
} else {
    echo "Erro ao enviar a mensagem. Tente novamente.";
}
?>
