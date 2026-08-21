<?php
require_once __DIR__ . '/../../includes/session.php';
$title = 'Entrar - '.APP_NAME;

use Controllers\AuthController;
use Models\User;

if(AuthController::isAuthenticated()) redirect('/dashboard/index.php');
$error = null;
$dbError = null;
$hasUsers = false;

try {
    $hasUsers = User::count() > 0;
} catch (Throwable $e) {
    error_log('[LabManager login] ' . $e->getMessage());
    $dbError = 'O sistema ainda não conseguiu acessar o banco de dados desta hospedagem.';
}

if(isPost() && !$dbError){
    requireCsrfForPost();
    try {
        $result=AuthController::login(trim((string)($_POST['email']??'')),(string)($_POST['password']??''));
        if($result['success']) redirect('/dashboard/index.php');
        $error=$result['message'];
    } catch (Throwable $e) {
        error_log('[LabManager login] ' . $e->getMessage());
        $dbError = 'Não foi possível acessar o banco de dados. Confira a configuração da hospedagem.';
    }
}
$flashes=pullFlash();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=e(appUrl('/assets/css/style.css'))?>" rel="stylesheet"><style>body{min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at top left,#e9f5ff,transparent 38%),#f5f8fb}.login-wrap{width:min(440px,100%)}.login-card{border-radius:22px}.login-brand{display:flex;align-items:center;gap:12px;margin-bottom:24px}.login-brand .brand-mark{width:52px;height:52px}.login-brand h1{font-size:1.35rem;margin:0;color:var(--navy);font-weight:850}.login-brand p{margin:3px 0 0;color:var(--muted);font-size:.86rem}</style></head><body>
<div class="login-wrap">
 <div class="card login-card"><div class="card-body p-4 p-sm-5">
  <div class="login-brand"><span class="brand-mark"><i data-lucide="school"></i></span><div><h1>LabManager Escolar</h1><p>Professores podem consultar e agendar equipamentos para suas aulas</p></div></div>
  <?php foreach($flashes as $type=>$msgs):foreach($msgs as $msg):?><div class="alert alert-<?=e($type)?>"><?=e($msg)?></div><?php endforeach;endforeach;?>
  <?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>
  <?php if($dbError): ?>
      <div class="alert alert-warning"><strong>Banco de dados não configurado.</strong><br><?=e($dbError)?></div>
      <div class="border rounded-3 p-3 bg-light">
        <strong>Como corrigir:</strong>
        <ol class="mb-0 mt-2 ps-3 small">
          <li>Importe <code>database/IMPORTAR_NO_PHPMYADMIN.sql</code> no phpMyAdmin.</li>
          <li>Abra <code>config/banco.php</code> no Gerenciador de Arquivos.</li>
          <li>Cole host, nome do banco, usuário e senha do MySQL.</li>
          <li>Salve e atualize esta página.</li>
        </ol>
      </div>
      <a class="btn btn-outline-primary w-100 mt-3" href="<?=e(appUrl('/teste-banco.php'))?>"><i data-lucide="database"></i> Testar conexão com o banco</a>
  <?php elseif(!$hasUsers): ?>
      <div class="alert alert-info"><strong>Primeiro acesso:</strong> ainda não existe administrador cadastrado.</div>
      <a class="btn btn-primary w-100" href="<?=e(appUrl('/views/auth/register.php'))?>"><i data-lucide="settings"></i> Configurar administrador</a>
  <?php else: ?>
      <form method="post" data-loading><?=csrfField()?><div class="mb-3"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" autocomplete="username" required autofocus></div><div class="mb-4"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" autocomplete="current-password" required></div><button class="btn btn-primary w-100" type="submit"><i data-lucide="log-in" class="me-1"></i> Entrar</button></form>
  <?php endif;?>
  <p class="text-center help-text mt-4 mb-0">Professores entram com sua conta individual. Os agendamentos ficam vinculados ao responsável.</p>
 </div></div>
</div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script><script>lucide.createIcons()</script></body></html>
