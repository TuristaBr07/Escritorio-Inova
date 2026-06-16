<?php
require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo     = getDB();
$usuario = usuarioLogado();
$acao    = $_GET['acao'] ?? 'listar';
$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── Processar formulário (novo ou editar) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = sanitizar($_POST['nome'] ?? '');
    $cnpjRaw  = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telefone = sanitizar($_POST['telefone'] ?? '');
    $obs      = sanitizar($_POST['observacoes'] ?? '');
    $idEd     = isset($_POST['id_cliente']) && $_POST['id_cliente'] ? (int)$_POST['id_cliente'] : null;

    if (empty($nome)) {
        flashMensagem('danger', 'O nome do cliente é obrigatório.');
    } elseif (!empty($cnpjRaw) && !validarCNPJ($cnpjRaw)) {
        flashMensagem('danger', 'CNPJ inválido. Verifique os dígitos e tente novamente.');
    } else {
        if ($idEd) {
            $stmt = $pdo->prepare("UPDATE clientes SET nome=?,cnpj=?,email=?,telefone=?,observacoes=?,atualizado_em=datetime('now','localtime') WHERE id=?");
            $stmt->execute([$nome, $cnpjRaw, $email, $telefone, $obs, $idEd]);
            registrarAuditLog($usuario['id'], 'CLIENTE_EDITADO', "id={$idEd}");
            flashMensagem('success', 'Cliente atualizado com sucesso!');
        } else {
            $stmt = $pdo->prepare('INSERT INTO clientes (nome,cnpj,email,telefone,observacoes) VALUES (?,?,?,?,?)');
            $stmt->execute([$nome, $cnpjRaw, $email, $telefone, $obs]);
            $novoId = $pdo->lastInsertId();
            registrarAuditLog($usuario['id'], 'CLIENTE_CRIADO', "id={$novoId}");
            flashMensagem('success', 'Cliente cadastrado com sucesso!');
        }
        header('Location: clientes.php');
        exit;
    }
}

// ── Soft delete ────────────────────────────────────────────────────
if ($acao === 'excluir' && $id) {
    $pdo->prepare('UPDATE clientes SET ativo=0 WHERE id=?')->execute([$id]);
    registrarAuditLog($usuario['id'], 'CLIENTE_EXCLUIDO', "id={$id}");
    flashMensagem('info', 'Cliente removido.');
    header('Location: clientes.php');
    exit;
}

// ── Buscar cliente para edição ─────────────────────────────────────
$clienteEd = null;
if ($acao === 'editar' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM clientes WHERE id=? AND ativo=1');
    $stmt->execute([$id]);
    $clienteEd = $stmt->fetch();
    if (!$clienteEd) { header('Location: clientes.php'); exit; }
}

// ── Listagem / busca ───────────────────────────────────────────────
$busca = sanitizar($_GET['busca'] ?? '');
if ($busca) {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE ativo=1 AND (nome LIKE ? OR cnpj LIKE ?) ORDER BY nome");
    $stmt->execute(["%{$busca}%", "%{$busca}%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM clientes WHERE ativo=1 ORDER BY nome");
}
$clientes = $stmt->fetchAll();

$pageTitle = 'Clientes';
require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="page-title">
  <i class="fas fa-users" style="color:#1e6091;"></i> Clientes
</div>

<div class="row g-4">

  <!-- Formulário -->
  <div class="col-lg-4">
    <div class="table-card p-4">
      <h5 class="mb-3 fw-semibold" style="font-size:.95rem;">
        <?= $clienteEd ? '✏️ Editar Cliente' : '➕ Novo Cliente' ?>
      </h5>
      <form method="POST" action="clientes.php">
        <?php if ($clienteEd): ?>
          <input type="hidden" name="id_cliente" value="<?= $clienteEd['id'] ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Nome / Razão Social *</label>
          <input type="text" class="form-control form-control-sm" name="nome" required
                 value="<?= htmlspecialchars($clienteEd['nome'] ?? ($_POST['nome'] ?? '')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">CNPJ</label>
          <input type="text" class="form-control form-control-sm" name="cnpj"
                 data-mask="cnpj" maxlength="18" placeholder="00.000.000/0000-00"
                 value="<?= $clienteEd && $clienteEd['cnpj'] ? formatarCNPJ($clienteEd['cnpj']) : htmlspecialchars($_POST['cnpj'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">E-mail</label>
          <input type="email" class="form-control form-control-sm" name="email"
                 value="<?= htmlspecialchars($clienteEd['email'] ?? ($_POST['email'] ?? '')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Telefone</label>
          <input type="text" class="form-control form-control-sm" name="telefone"
                 value="<?= htmlspecialchars($clienteEd['telefone'] ?? ($_POST['telefone'] ?? '')) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.85rem;">Observações</label>
          <textarea class="form-control form-control-sm" name="observacoes" rows="2"
                    ><?= htmlspecialchars($clienteEd['observacoes'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
            <i class="fas fa-save me-1"></i><?= $clienteEd ? 'Salvar' : 'Cadastrar' ?>
          </button>
          <?php if ($clienteEd): ?>
            <a href="clientes.php" class="btn btn-outline-secondary btn-sm">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="col-lg-8">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-list me-2" style="color:#1e6091;"></i>
          <?= count($clientes) ?> cliente<?= count($clientes) !== 1 ? 's' : '' ?>
          <?= $busca ? " encontrado(s) para \"" . htmlspecialchars($busca) . "\"" : '' ?>
        </h5>
        <form method="GET" action="clientes.php" class="d-flex gap-2 align-items-center">
          <input type="search" class="form-control form-control-sm" name="busca"
                 placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>" style="max-width:200px;">
          <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-search"></i>
          </button>
          <?php if ($busca): ?>
            <a href="clientes.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
          <?php endif; ?>
        </form>
      </div>

      <?php if (empty($clientes)): ?>
        <p class="text-muted p-3 mb-0" style="font-size:.875rem;">
          <?= $busca ? 'Nenhum resultado encontrado.' : 'Nenhum cliente cadastrado ainda.' ?>
        </p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr><th>Nome</th><th>CNPJ</th><th>Telefone</th><th class="text-end">Ações</th></tr>
            </thead>
            <tbody>
              <?php foreach ($clientes as $c): ?>
                <tr>
                  <td>
                    <div style="font-size:.875rem; font-weight:500;"><?= htmlspecialchars($c['nome']) ?></div>
                    <?php if ($c['email']): ?>
                      <div style="font-size:.75rem; color:#888;"><?= htmlspecialchars($c['email']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.82rem;">
                    <?= $c['cnpj'] ? formatarCNPJ($c['cnpj']) : '<span class="text-muted">—</span>' ?>
                  </td>
                  <td style="font-size:.82rem;">
                    <?= $c['telefone'] ? htmlspecialchars($c['telefone']) : '<span class="text-muted">—</span>' ?>
                  </td>
                  <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                      <a href="clientes.php?acao=editar&id=<?= $c['id'] ?>"
                         class="btn btn-sm btn-outline-primary" title="Editar">
                        <i class="fas fa-edit"></i>
                      </a>
                      <?php if ($c['cnpj']): ?>
                        <a href="cnd.php?cnpj=<?= urlencode(formatarCNPJ($c['cnpj'])) ?>"
                           class="btn btn-sm btn-outline-success" title="Consultar CND">
                          <i class="fas fa-certificate"></i>
                        </a>
                      <?php endif; ?>
                      <a href="clientes.php?acao=excluir&id=<?= $c['id'] ?>"
                         class="btn btn-sm btn-outline-danger"
                         onclick="return confirm('Remover <?= htmlspecialchars(addslashes($c['nome'])) ?>?')"
                         title="Remover">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
