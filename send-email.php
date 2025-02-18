<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura e sanitiza os dados do formulário
    $nome = strip_tags(trim($_POST["nome"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $mensagem = trim($_POST["mensagem"]);

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

    // Configurações do email
    $destinatario = "contato@escritorioinova.com"; // Altere para seu email na Hostinger
    $assunto = "Nova mensagem de contato de $nome";
    $mensagem_email = "Nome: $nome\nEmail: $email\nMensagem:\n$mensagem";

    // Cabeçalhos
    $headers = "From: $email\r\nReply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Tenta enviar o email
    if (mail($destinatario, $assunto, $mensagem_email, $headers)) {
        // Se deu certo, redireciona para a página de agradecimento
        header("Location: obrigado.html");
        exit;
    } else {
        echo "Erro ao enviar a mensagem. Tente novamente.";
    }
} else {
    echo "Método não permitido.";
}
?>

