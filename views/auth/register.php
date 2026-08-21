<?php
require_once __DIR__ . '/../../includes/session.php';
$title = 'Configuração inicial - '.APP_NAME;
use Controllers\UserController;use Models\User;
try {
    if(User::count()>0){redirect('/views/auth/login.php');}
} catch (Throwable $e) {
    error_log('[LabManager register] ' . $e->getMessage());
    flash('warning', 'Configure o banco no arquivo config/banco.php e importe database/IMPORTAR_NO_PHPMYADMIN.sql.');
    redirect('/views/auth/login.php');
}
$error=null;
if(isPost()){
 requireCsrfForPost();
 $data=$_POST;$data['role']='admin';$data['status']='active';
 if(($data['password']??'')!==($data['password_confirmation']??'')){$error='As senhas não coincidem.';}else{$r=UserController::store($data);if($r['success']){flash('success','Administrador criado. Faça login para continuar.');redirect('/views/auth/login.php');}$error=$r['message'];}
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="<?=e(appUrl('/assets/css/style.css'))?>" rel="stylesheet"><style>body{min-height:100vh;display:grid;place-items:center;padding:24px}.setup{width:min(560px,100%)}</style></head><body><div class="setup"><div class="card"><div class="card-body p-4 p-sm-5"><div class="login-brand mb-4"><span class="brand-mark"><i data-lucide="shield-check"></i></span><div><h1 class="page-title">Configuração inicial</h1><p class="page-subtitle">Crie o primeiro administrador da escola.</p></div></div><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?><form method="post" data-loading><?=csrfField()?><div class="mb-3"><label class="form-label">Nome completo</label><input class="form-control" name="name" required minlength="3"></div><div class="mb-3"><label class="form-label">E-mail institucional</label><input class="form-control" type="email" name="email" required></div><div class="row g-3"><div class="col-sm-6"><label class="form-label">Senha</label><input class="form-control" type="password" name="password" minlength="8" required></div><div class="col-sm-6"><label class="form-label">Confirmar senha</label><input class="form-control" type="password" name="password_confirmation" minlength="8" required></div></div><p class="help-text mt-2">Use no mínimo 8 caracteres. Evite senhas usadas em outros serviços.</p><button class="btn btn-primary w-100 mt-3">Criar administrador</button></form></div></div></div><script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script><script>lucide.createIcons()</script></body></html>
