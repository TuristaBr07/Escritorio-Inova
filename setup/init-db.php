<?php
// Inicialização do banco de dados — execute uma única vez
// Acesso: http://seusite.com/setup/init-db.php
// ⚠️ Remova ou proteja este arquivo após a inicialização em produção.

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = getDB();

// Usuários do sistema
$pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nome       TEXT NOT NULL,
    email      TEXT UNIQUE NOT NULL,
    senha      TEXT NOT NULL,
    criado_em  TEXT DEFAULT (datetime('now', 'localtime'))
)");

// Clientes do escritório
$pdo->exec("CREATE TABLE IF NOT EXISTS clientes (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    nome          TEXT NOT NULL,
    cnpj          TEXT,
    email         TEXT,
    telefone      TEXT,
    observacoes   TEXT,
    ativo         INTEGER DEFAULT 1,
    criado_em     TEXT DEFAULT (datetime('now', 'localtime')),
    atualizado_em TEXT DEFAULT (datetime('now', 'localtime'))
)");

// Histórico de consultas CND
$pdo->exec("CREATE TABLE IF NOT EXISTS consultas_cnd (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id   INTEGER NOT NULL,
    cliente_id   INTEGER,
    cnpj         TEXT NOT NULL,
    tipo         TEXT NOT NULL DEFAULT 'federal',
    status_cnd   TEXT,
    validade     TEXT,
    numero_cert  TEXT,
    resultado    TEXT,
    consultado_em TEXT DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
)");

// Log de auditoria (LGPD)
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER,
    acao       TEXT NOT NULL,
    dado       TEXT,
    ip         TEXT,
    criado_em  TEXT DEFAULT (datetime('now', 'localtime'))
)");

// Criar usuário admin padrão se não existir
$total = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

if ($total === 0) {
    $hash = password_hash('Inova@2026', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
    $stmt->execute(['Administrador', 'admin@inovacontabil.com', $hash]);
    $criado = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicialização do Banco | Inova Contábil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
  <div class="card mx-auto shadow" style="max-width:500px;">
    <div class="card-body p-4">
      <?php if (!empty($criado)): ?>
        <h4 class="text-success"><i class="fas fa-check-circle"></i> Banco inicializado!</h4>
        <p>Tabelas criadas e usuário admin configurado:</p>
        <table class="table table-bordered table-sm">
          <tr><th>E-mail</th><td>admin@inovacontabil.com</td></tr>
          <tr><th>Senha</th><td><code>Inova@2026</code></td></tr>
        </table>
        <div class="alert alert-warning mt-3 mb-0" style="font-size:.9rem;">
          ⚠️ <strong>Troque a senha após o primeiro acesso.</strong><br>
          Remova ou proteja este arquivo em produção.
        </div>
      <?php else: ?>
        <h4 class="text-info">Banco já inicializado</h4>
        <p class="mb-0">O banco de dados já estava configurado. Nenhuma alteração foi feita.</p>
      <?php endif; ?>
      <hr>
      <a href="../admin/login.php" class="btn btn-primary">Ir para o painel admin</a>
    </div>
  </div>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</body>
</html>
