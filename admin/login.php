<?php
require_once dirname(__DIR__) . '/includes/auth.php';

if (estaLogado()) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (logarUsuario($email, $senha)) {
        header('Location: index.php');
        exit;
    }
    $erro = 'E-mail ou senha incorretos.';
}

$alertas = [
    'logout'           => ['warning', 'Você saiu do sistema.'],
    'sessao_expirada'  => ['info', 'Sua sessão expirou. Faça login novamente.'],
];
$msg = $alertas[$_GET['msg'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex">
  <title>Login | Inova Contábil Admin</title>
  <link rel="icon" type="image/webp" href="../img/logo1.webp">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body { background:#f4f8fc; display:flex; align-items:center; justify-content:center; min-height:100vh; }
    .login-card { max-width:400px; width:100%; background:#fff; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.12); padding:2.5rem; }
    .login-logo { width:68px; height:68px; border-radius:50%; border:3px solid #e0ebf5; }
    .btn-login  { background:#1e6091; color:#fff; border:none; }
    .btn-login:hover { background:#154f78; color:#fff; }
    .input-group-text { background:#f4f8fc; }
  </style>
</head>
<body>
  <div class="login-card text-center">
    <img src="../img/logo1.webp" alt="Inova Contábil" class="login-logo mb-3">
    <h5 class="mb-0" style="color:#1e6091; font-weight:700;">Inova Contábil</h5>
    <p class="text-muted mb-4" style="font-size:.85rem;">Painel Administrativo</p>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msg[0] ?> py-2 text-start" style="font-size:.875rem;"><?= htmlspecialchars($msg[1]) ?></div>
    <?php endif; ?>

    <?php if ($erro): ?>
      <div class="alert alert-danger py-2 text-start" style="font-size:.875rem;">
        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="on">
      <div class="mb-3 text-start">
        <label for="email" class="form-label fw-semibold" style="font-size:.875rem;">E-mail</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope text-muted" style="font-size:.8rem;"></i></span>
          <input type="email" class="form-control" id="email" name="email" required autofocus
                 autocomplete="username" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>
      <div class="mb-4 text-start">
        <label for="senha" class="form-label fw-semibold" style="font-size:.875rem;">Senha</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock text-muted" style="font-size:.8rem;"></i></span>
          <input type="password" class="form-control" id="senha" name="senha" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn btn-login w-100 py-2">
        <i class="fas fa-sign-in-alt me-2"></i>Entrar
      </button>
    </form>

    <div class="mt-4" style="font-size:.8rem;">
      <a href="../index.html" class="text-muted text-decoration-none">
        <i class="fas fa-arrow-left me-1"></i>Voltar ao site
      </a>
    </div>
  </div>
</body>
</html>
