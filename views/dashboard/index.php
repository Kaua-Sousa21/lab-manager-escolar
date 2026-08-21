<?php
require_once __DIR__ . '/../../includes/session.php';
$title = 'Visão geral - '.APP_NAME;

use Controllers\AuthController;
use Models\AuditLog;
use Models\Equipment;
use Models\Loan;
use Models\Maintenance;
use Models\Student;
use Models\User;

AuthController::requireAuth();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isManagerView = can('view_all_loans');
$totalEquipments = Equipment::count();
$available = Equipment::countByStatus('available');
$borrowed = Equipment::countByStatus('borrowed');
$maintenance = Maintenance::countInProgress();

if ($isManagerView) {
    $overdue = Loan::countOverdue();
    $dueToday = Loan::countDueToday();
    $students = can('manage_school_data') ? Student::countActive() : 0;
    $staff = can('manage_users') ? User::countActive() : 0;
    $overdueItems = Loan::overdue(8);
    $dueSoon = Loan::dueSoon(3,8);
    $activity = can('manage_users') ? AuditLog::latest(8) : [];
} else {
    $myUpcoming = Loan::upcomingByUser($currentUserId, 10);
    $myReservations = count(array_filter($myUpcoming, fn($l) => $l['status'] === 'reserved'));
    $myBorrowed = count(array_filter($myUpcoming, fn($l) => in_array($l['status'], ['borrowed','overdue'], true)));
}

require_once __DIR__ . '/../partials/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $isManagerView ? 'Visão geral' : 'Olá, '.e($_SESSION['user_name'] ?? 'Professor') ?></h1>
        <p class="page-subtitle"><?= $isManagerView ? 'Acompanhe equipamentos, devoluções e rotinas do laboratório escolar.' : 'Consulte os equipamentos e organize suas reservas para as próximas aulas.' ?></p>
    </div>
    <?php if(can('schedule_equipment')): ?><a class="btn btn-primary" href="<?=e(appUrl('/views/loans/index.php?new=1'))?>"><i data-lucide="calendar-plus"></i> Agendar equipamento</a><?php endif; ?>
</div>

<?php if(!$isManagerView): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon"><i data-lucide="monitor"></i></div><div><div class="stat-value"><?=$totalEquipments?></div><div class="stat-label">Equipamentos</div></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon success"><i data-lucide="circle-check"></i></div><div><div class="stat-value"><?=$available?></div><div class="stat-label">Disponíveis agora</div></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon"><i data-lucide="calendar-days"></i></div><div><div class="stat-value"><?=$myReservations?></div><div class="stat-label">Minhas reservas</div></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon warning"><i data-lucide="package-open"></i></div><div><div class="stat-value"><?=$myBorrowed?></div><div class="stat-label">Comigo agora</div></div></div></div></div>
</div>
<div class="row g-3">
    <div class="col-xl-8"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="section-title mb-1">Meus próximos agendamentos</h2><div class="help-text">Se precisar mudar o horário, edite a reserva antes da retirada.</div></div><a class="small fw-semibold" href="<?=e(appUrl('/views/loans/index.php'))?>">Abrir agenda</a></div><div class="card-body">
        <?php if(!$myUpcoming): ?><div class="empty-state"><i data-lucide="calendar-check"></i><div>Você ainda não tem equipamentos reservados.</div><a class="btn btn-primary btn-sm mt-2" href="<?=e(appUrl('/views/loans/index.php?new=1'))?>">Fazer primeiro agendamento</a></div><?php else: ?>
        <div class="quick-list"><?php foreach($myUpcoming as $l): ?><div class="quick-item"><div class="stat-icon <?= $l['status']==='overdue' ? 'danger' : ($l['status']==='borrowed' ? 'warning' : '') ?>"><i data-lucide="<?= $l['status']==='reserved' ? 'calendar-check' : 'package-open' ?>"></i></div><div class="quick-item-main"><strong><?=e($l['equipment_name'])?></strong><small><?=e(formatDateTime($l['withdrawal_date']))?> até <?=e(formatDateTime($l['expected_return_date']))?></small></div><?=getStatusBadge($l['status'])?></div><?php endforeach; ?></div>
        <?php endif; ?>
    </div></div></div>
    <div class="col-xl-4"><div class="card h-100"><div class="card-header"><h2 class="section-title">Como reservar</h2></div><div class="card-body"><div class="quick-list"><div class="quick-item"><div class="stat-icon"><i data-lucide="search"></i></div><div class="quick-item-main"><strong>1. Escolha o equipamento</strong><small>Abra Equipamentos e clique em Agendar.</small></div></div><div class="quick-item"><div class="stat-icon"><i data-lucide="clock-3"></i></div><div class="quick-item-main"><strong>2. Informe o período</strong><small>Defina início e fim de acordo com sua aula.</small></div></div><div class="quick-item"><div class="stat-icon"><i data-lucide="shield-check"></i></div><div class="quick-item-main"><strong>3. O sistema confere conflitos</strong><small>Horários já ocupados não podem ser reservados novamente.</small></div></div></div></div></div></div>
</div>
<?php else: ?>
<div class="row g-3 mb-4">
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon"><i data-lucide="monitor"></i></div><div><div class="stat-value"><?=$totalEquipments?></div><div class="stat-label">Equipamentos</div></div></div></div></div>
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon success"><i data-lucide="circle-check"></i></div><div><div class="stat-value"><?=$available?></div><div class="stat-label">Disponíveis</div></div></div></div></div>
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon warning"><i data-lucide="arrow-up-right"></i></div><div><div class="stat-value"><?=$borrowed?></div><div class="stat-label">Emprestados</div></div></div></div></div>
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon danger"><i data-lucide="clock-alert"></i></div><div><div class="stat-value"><?=$overdue?></div><div class="stat-label">Atrasados</div></div></div></div></div>
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon warning"><i data-lucide="calendar-clock"></i></div><div><div class="stat-value"><?=$dueToday?></div><div class="stat-label">Vencem hoje</div></div></div></div></div>
 <div class="col-6 col-xl-2"><div class="card stat-card h-100"><div class="card-body"><div class="stat-icon"><i data-lucide="wrench"></i></div><div><div class="stat-value"><?=$maintenance?></div><div class="stat-label">Em manutenção</div></div></div></div></div>
</div>
<?php if(can('manage_school_data')||can('manage_users')): ?><div class="row g-3 mb-4"><?php if(can('manage_school_data')): ?><div class="col-md-6"><div class="card"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="muted small">Alunos ativos cadastrados</div><div class="fs-3 fw-bold text-dark"><?=$students?></div></div><a href="<?=e(appUrl('/views/students/index.php'))?>" class="btn btn-outline-primary btn-sm">Ver alunos</a></div></div></div><?php endif; ?><?php if(can('manage_users')): ?><div class="col-md-6"><div class="card"><div class="card-body d-flex align-items-center justify-content-between"><div><div class="muted small">Professores/equipe com acesso</div><div class="fs-3 fw-bold text-dark"><?=$staff?></div></div><a href="<?=e(appUrl('/views/users/index.php'))?>" class="btn btn-outline-primary btn-sm">Gerenciar acessos</a></div></div></div><?php endif; ?></div><?php endif; ?>
<div class="row g-3">
 <div class="col-xl-7"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="section-title">Atenção nas devoluções</h2><a class="small fw-semibold" href="<?=e(appUrl('/views/loans/index.php'))?>">Ver agenda completa</a></div><div class="card-body">
 <?php if(!$overdueItems&&!$dueSoon): ?><div class="empty-state"><i data-lucide="badge-check"></i><div>Nenhuma devolução urgente no momento.</div></div><?php else: ?><div class="quick-list">
 <?php foreach($overdueItems as $l): ?><div class="quick-item alert-overdue"><div class="stat-icon danger"><i data-lucide="triangle-alert"></i></div><div class="quick-item-main"><strong><?=e($l['borrower_name'])?> • <?=e($l['equipment_name'])?></strong><small>Atrasado desde <?=e(formatDateTime($l['expected_return_date']))?><?= $l['student_id'] ? ' • '.$l['student_grade'].' '.$l['student_class'] : '' ?></small></div><?=getStatusBadge('overdue')?></div><?php endforeach; ?>
 <?php foreach($dueSoon as $l): ?><div class="quick-item"><div class="stat-icon warning"><i data-lucide="calendar-clock"></i></div><div class="quick-item-main"><strong><?=e($l['borrower_name'])?> • <?=e($l['equipment_name'])?></strong><small>Devolver até <?=e(formatDateTime($l['expected_return_date']))?></small></div><?=getStatusBadge('borrowed')?></div><?php endforeach; ?></div><?php endif; ?>
 </div></div></div>
 <div class="col-xl-5"><div class="card h-100"><div class="card-header"><h2 class="section-title">Fluxo recomendado</h2></div><div class="card-body"><div class="quick-list"><div class="quick-item"><div class="stat-icon"><i data-lucide="calendar-check"></i></div><div class="quick-item-main"><strong>Professores fazem as próprias reservas</strong><small>Cada agendamento fica vinculado à conta do professor.</small></div></div><div class="quick-item"><div class="stat-icon"><i data-lucide="package-check"></i></div><div class="quick-item-main"><strong>Equipe confirma a retirada</strong><small>O equipamento só muda para emprestado quando realmente sai.</small></div></div><div class="quick-item"><div class="stat-icon"><i data-lucide="undo-2"></i></div><div class="quick-item-main"><strong>Devolução encerra o empréstimo</strong><small>O patrimônio volta a ficar disponível para uso imediato.</small></div></div></div></div></div></div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
