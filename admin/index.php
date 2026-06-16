<?php
require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo = getDB();

$totalClientes  = (int)$pdo->query("SELECT COUNT(*) FROM clientes WHERE ativo = 1")->fetchColumn();
$totalConsultas = (int)$pdo->query("SELECT COUNT(*) FROM consultas_cnd")->fetchColumn();
$consultasHoje  = (int)$pdo->query("SELECT COUNT(*) FROM consultas_cnd WHERE date(consultado_em) = date('now', 'localtime')")->fetchColumn();
$regularidades  = (int)$pdo->query("SELECT COUNT(*) FROM consultas_cnd WHERE status_cnd = 'Regular'")->fetchColumn();

$ultimasConsultas = $pdo->query("
    SELECT c.cnpj, c.tipo, c.status_cnd, c.consultado_em, u.nome AS usuario
    FROM consultas_cnd c
    JOIN usuarios u ON c.usuario_id = u.id
    ORDER BY c.id DESC LIMIT 8
")->fetchAll();

$clientesRecentes = $pdo->query("
    SELECT nome, cnpj, criado_em FROM clientes
    WHERE ativo = 1 ORDER BY id DESC LIMIT 6
")->fetchAll();

$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="page-title">
  <i class="fas fa-tachometer-alt" style="color:#1e6091;"></i> Dashboard
</div>

<!-- Métricas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="metric-card d-flex justify-content-between align-items-center">
      <div>
        <div class="metric-value"><?= $totalClientes ?></div>
        <div class="metric-label">Clientes ativos</div>
      </div>
      <i class="fas fa-users metric-icon"></i>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="metric-card d-flex justify-content-between align-items-center" style="border-left-color:#198754;">
      <div>
        <div class="metric-value" style="color:#198754;"><?= $totalConsultas ?></div>
        <div class="metric-label">CNDs consultadas</div>
      </div>
      <i class="fas fa-certificate metric-icon" style="color:#198754;"></i>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="metric-card d-flex justify-content-between align-items-center" style="border-left-color:#fd7e14;">
      <div>
        <div class="metric-value" style="color:#fd7e14;"><?= $consultasHoje ?></div>
        <div class="metric-label">Consultas hoje</div>
      </div>
      <i class="fas fa-search metric-icon" style="color:#fd7e14;"></i>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="metric-card d-flex justify-content-between align-items-center" style="border-left-color:#198754;">
      <div>
        <div class="metric-value" style="color:#198754;"><?= $regularidades ?></div>
        <div class="metric-label">CNDs regulares</div>
      </div>
      <i class="fas fa-check-circle metric-icon" style="color:#198754;"></i>
    </div>
  </div>
</div>

<!-- Tabelas -->
<div class="row g-4">
  <div class="col-lg-7">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-certificate me-2" style="color:#1e6091;"></i>Últimas Consultas CND</h5>
        <a href="cnd.php" class="btn btn-sm btn-outline-primary">Nova consulta</a>
      </div>
      <?php if (empty($ultimasConsultas)): ?>
        <p class="text-muted p-3 mb-0" style="font-size:.875rem;">Nenhuma consulta realizada ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>CNPJ</th><th>Tipo</th><th>Status</th><th>Data/Hora</th></tr>
            </thead>
            <tbody>
              <?php foreach ($ultimasConsultas as $c):
                $cls = match(strtolower($c['status_cnd'] ?? '')) {
                    'regular'   => 'badge-regular',
                    'irregular' => 'badge-irregular',
                    default     => 'badge-pendente',
                };
              ?>
                <tr>
                  <td style="font-size:.85rem;"><?= formatarCNPJ($c['cnpj']) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($c['tipo'])) ?></span></td>
                  <td><span class="badge <?= $cls ?>"><?= htmlspecialchars($c['status_cnd'] ?? 'N/A') ?></span></td>
                  <td style="font-size:.78rem;"><?= $c['consultado_em'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-users me-2" style="color:#1e6091;"></i>Clientes Recentes</h5>
        <a href="clientes.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
      </div>
      <?php if (empty($clientesRecentes)): ?>
        <p class="text-muted p-3 mb-0" style="font-size:.875rem;">Nenhum cliente cadastrado ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr><th>Nome</th><th>CNPJ</th><th>Desde</th></tr>
            </thead>
            <tbody>
              <?php foreach ($clientesRecentes as $c): ?>
                <tr>
                  <td style="font-size:.875rem;"><?= htmlspecialchars($c['nome']) ?></td>
                  <td style="font-size:.82rem;">
                    <?= $c['cnpj'] ? formatarCNPJ($c['cnpj']) : '<span class="text-muted">—</span>' ?>
                  </td>
                  <td style="font-size:.78rem;"><?= date('d/m/y', strtotime($c['criado_em'])) ?></td>
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
