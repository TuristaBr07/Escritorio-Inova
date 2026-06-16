<?php
require_once dirname(__DIR__) . '/includes/auth-check.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pdo = getDB();

// Histórico de consultas
$historico = $pdo->query("
    SELECT c.id, c.cnpj, c.tipo, c.status_cnd, c.validade, c.numero_cert, c.consultado_em, u.nome AS usuario
    FROM consultas_cnd c
    JOIN usuarios u ON c.usuario_id = u.id
    ORDER BY c.id DESC LIMIT 50
")->fetchAll();

// Pré-preencher CNPJ vindo da página de clientes
$cnpjPreenchido = htmlspecialchars($_GET['cnpj'] ?? '');

$pageTitle = 'Consulta CND';
require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="page-title">
  <i class="fas fa-certificate" style="color:#1e6091;"></i> Consulta CND
</div>

<div class="row g-4">

  <!-- Formulário de consulta -->
  <div class="col-lg-5">
    <div class="table-card p-4">
      <h5 class="mb-1 fw-semibold" style="font-size:.95rem;">Nova Consulta</h5>
      <p class="text-muted mb-3" style="font-size:.8rem;">
        Certidão Negativa de Débitos — Receita Federal / PGFN
      </p>

      <form id="form-cnd">
        <div class="mb-3">
          <label for="cnd-cnpj" class="form-label fw-semibold" style="font-size:.85rem;">CNPJ *</label>
          <input type="text" class="form-control" id="cnd-cnpj" data-mask="cnpj"
                 maxlength="18" placeholder="00.000.000/0000-00" required
                 value="<?= $cnpjPreenchido ?>">
        </div>
        <div class="mb-3">
          <label for="cnd-tipo" class="form-label fw-semibold" style="font-size:.85rem;">Tipo de CND</label>
          <select class="form-select" id="cnd-tipo">
            <option value="federal">Federal (RFB + PGFN)</option>
            <option value="inss">INSS (Dataprev)</option>
            <option value="estadual_sp">Estadual SP (Fazenda SP)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-search me-2"></i>Consultar CND
        </button>
      </form>

      <!-- Spinner -->
      <div id="cnd-spinner" class="text-center py-4" style="display:none;">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Consultando...</span>
        </div>
        <p class="text-muted mt-2 mb-0" style="font-size:.85rem;">Consultando...</p>
      </div>

      <!-- Resultado -->
      <div id="cnd-result" class="mt-2"></div>

      <?php if (CND_API_PROVIDER === 'demo'): ?>
        <div class="alert alert-warning mt-3 mb-0 py-2" style="font-size:.78rem;">
          <i class="fas fa-flask me-1"></i>
          <strong>Modo demonstração ativo.</strong>
          Configure <code>CND_API_KEY</code> em <code>includes/config.php</code> para consultas reais.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Histórico -->
  <div class="col-lg-7">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="fas fa-history me-2" style="color:#1e6091;"></i>Histórico de Consultas</h5>
        <span class="text-muted" style="font-size:.8rem;">Últimas 50</span>
      </div>

      <?php if (empty($historico)): ?>
        <p class="text-muted p-3 mb-0" style="font-size:.875rem;">Nenhuma consulta realizada ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>CNPJ</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Validade</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody id="cnd-hist-body">
              <?php foreach ($historico as $h):
                $cls = match(strtolower($h['status_cnd'] ?? '')) {
                    'regular'   => 'badge-regular',
                    'irregular' => 'badge-irregular',
                    default     => 'badge-pendente',
                };
              ?>
                <tr>
                  <td style="font-size:.82rem;"><?= formatarCNPJ($h['cnpj']) ?></td>
                  <td><span class="badge bg-secondary" style="font-size:.7rem;"><?= htmlspecialchars(strtoupper($h['tipo'])) ?></span></td>
                  <td><span class="badge <?= $cls ?>" style="font-size:.7rem;"><?= htmlspecialchars($h['status_cnd'] ?? 'N/A') ?></span></td>
                  <td style="font-size:.78rem;"><?= $h['validade'] ? date('d/m/Y', strtotime($h['validade'])) : '—' ?></td>
                  <td style="font-size:.75rem; white-space:nowrap;"><?= $h['consultado_em'] ?></td>
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
