<?php
// $pageTitle deve ser definido antes de incluir este arquivo
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> | Inova Contábil</title>
  <link rel="icon" type="image/webp" href="../img/logo1.webp">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img src="../img/logo1.webp" alt="Inova Contábil" class="sidebar-logo">
      <span>Inova Contábil</span>
    </div>
    <ul class="sidebar-menu">
      <li class="<?= $currentPage === 'index' ? 'active' : '' ?>">
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
      </li>
      <li class="<?= $currentPage === 'clientes' ? 'active' : '' ?>">
        <a href="clientes.php"><i class="fas fa-users"></i> <span>Clientes</span></a>
      </li>
      <li class="<?= $currentPage === 'cnd' ? 'active' : '' ?>">
        <a href="cnd.php"><i class="fas fa-certificate"></i> <span>Consulta CND</span></a>
      </li>
      <li class="sidebar-divider"></li>
      <li>
        <a href="../index.html" target="_blank" rel="noopener">
          <i class="fas fa-external-link-alt"></i> <span>Ver Site</span>
        </a>
      </li>
      <li>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
      </li>
    </ul>
  </nav>

  <!-- Conteúdo Principal -->
  <div class="main-content" id="mainContent">

    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-sm btn-outline-secondary" id="sidebarToggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-user">
        <i class="fas fa-user-circle me-2" style="color:#1e6091;"></i>
        <span><?= htmlspecialchars(usuarioLogado()['nome']) ?></span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger ms-3">
          <i class="fas fa-sign-out-alt me-1"></i>Sair
        </a>
      </div>
    </div>

    <!-- Conteúdo da Página -->
    <div class="page-content">
      <?php $flash = obterFlash(); if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($flash['mensagem']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      <?php endif; ?>
