<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function iniciarSessao(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function estaLogado(): bool {
    iniciarSessao();
    if (empty($_SESSION['usuario_id'])) return false;
    if (time() - (int)($_SESSION['ultimo_acesso'] ?? 0) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    $_SESSION['ultimo_acesso'] = time();
    return true;
}

function logarUsuario(string $email, string $senha): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha FROM usuarios WHERE email = ?');
    $stmt->execute([trim($email)]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) return false;

    iniciarSessao();
    session_regenerate_id(true);
    $_SESSION['usuario_id']    = $usuario['id'];
    $_SESSION['usuario_nome']  = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['ultimo_acesso'] = time();
    return true;
}

function deslogar(): void {
    iniciarSessao();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function usuarioLogado(): array {
    return [
        'id'    => $_SESSION['usuario_id']    ?? null,
        'nome'  => $_SESSION['usuario_nome']  ?? '',
        'email' => $_SESSION['usuario_email'] ?? '',
    ];
}

function gerarCsrfToken(): string {
    iniciarSessao();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validarCsrfToken(string $token): bool {
    iniciarSessao();
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}
