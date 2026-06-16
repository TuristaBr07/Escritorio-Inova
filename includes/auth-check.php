<?php
require_once __DIR__ . '/auth.php';
if (!estaLogado()) {
    header('Location: /admin/login.php?msg=sessao_expirada');
    exit;
}
