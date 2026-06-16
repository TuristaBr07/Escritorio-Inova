// Toggle sidebar
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const toggle      = document.getElementById('sidebarToggle');

toggle?.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
  mainContent.classList.toggle('expanded');
});

// Máscara de CNPJ
function aplicarMascaraCNPJ(el) {
  el.addEventListener('input', function () {
    let v = this.value.replace(/\D/g, '').substring(0, 14);
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    this.value = v;
  });
}
document.querySelectorAll('[data-mask="cnpj"]').forEach(aplicarMascaraCNPJ);

// ===== Consulta CND via AJAX =====
const formCnd     = document.getElementById('form-cnd');
const cndResult   = document.getElementById('cnd-result');
const cndSpinner  = document.getElementById('cnd-spinner');
const cndHistBody = document.getElementById('cnd-hist-body');

formCnd?.addEventListener('submit', async function (e) {
  e.preventDefault();

  const cnpj = document.getElementById('cnd-cnpj').value.trim();
  const tipo = document.getElementById('cnd-tipo').value;

  if (!cnpj) return;

  // Mostrar spinner
  if (cndResult)  cndResult.innerHTML = '';
  if (cndSpinner) cndSpinner.style.display = 'flex';

  try {
    const resp = await fetch('../api/cnd-query.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    `cnpj=${encodeURIComponent(cnpj)}&tipo=${encodeURIComponent(tipo)}`,
    });

    const data = await resp.json();

    if (cndSpinner) cndSpinner.style.display = 'none';

    if (!data.success) {
      cndResult.innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i>${escapeHtml(data.erro || 'Erro ao consultar CND.')}
        </div>`;
      return;
    }

    const isReg    = data.status?.toLowerCase() === 'regular';
    const cssClass = isReg ? 'regular' : 'irregular';
    const icon     = isReg ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
    const modoTag  = data.modo === 'demo'
      ? '<span class="badge bg-warning text-dark ms-2">MODO DEMO</span>'
      : '';

    cndResult.innerHTML = `
      <div class="cnd-result ${cssClass}">
        <div class="d-flex align-items-center gap-3 mb-3">
          <i class="fas ${icon} cnd-status-icon"></i>
          <div>
            <div class="cnd-status-text">${escapeHtml(data.status)} ${modoTag}</div>
            <div class="cnd-detail">CNPJ: <strong>${escapeHtml(data.cnpj_formatado || data.cnpj)}</strong></div>
          </div>
        </div>
        <hr class="my-2">
        <div class="row g-2">
          <div class="col-sm-6">
            <small class="text-muted d-block">Tipo</small>
            <strong>${escapeHtml(data.tipo?.toUpperCase() || '—')}</strong>
          </div>
          <div class="col-sm-6">
            <small class="text-muted d-block">Validade</small>
            <strong>${escapeHtml(data.validade || '—')}</strong>
          </div>
          <div class="col-sm-6">
            <small class="text-muted d-block">Emissão</small>
            <strong>${escapeHtml(data.emissao || '—')}</strong>
          </div>
          <div class="col-sm-6">
            <small class="text-muted d-block">Número</small>
            <strong>${escapeHtml(data.numero || '—')}</strong>
          </div>
        </div>
        ${data.mensagem ? `<p class="mt-3 mb-0 cnd-detail">${escapeHtml(data.mensagem)}</p>` : ''}
      </div>`;

    // Adicionar ao histórico sem reload
    if (cndHistBody && data.id) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="font-size:.85rem;">${escapeHtml(data.cnpj_formatado || data.cnpj)}</td>
        <td><span class="badge bg-secondary">${escapeHtml(data.tipo?.toUpperCase())}</span></td>
        <td><span class="badge ${isReg ? 'badge-regular' : 'badge-irregular'}">${escapeHtml(data.status)}</span></td>
        <td style="font-size:.8rem;">${escapeHtml(data.emissao || '—')}</td>`;
      cndHistBody.prepend(tr);
    }

  } catch (err) {
    if (cndSpinner) cndSpinner.style.display = 'none';
    if (cndResult) cndResult.innerHTML = `
      <div class="alert alert-danger">
        <i class="fas fa-wifi me-2"></i>Erro de conexão. Tente novamente.
      </div>`;
  }
});

function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}
