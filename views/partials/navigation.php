<nav class="side-nav" aria-label="Navegação principal">
    <div class="side-label">Principal</div>
    <a class="side-link<?= activeNav('/dashboard/') ?>" href="<?= e(appUrl('/dashboard/index.php')) ?>"><i data-lucide="layout-dashboard"></i><span>Visão geral</span></a>
    <a class="side-link<?= activeNav('/views/equipment/') ?>" href="<?= e(appUrl('/views/equipment/index.php')) ?>"><i data-lucide="monitor-cog"></i><span>Equipamentos</span></a>
    <?php if (can('manage_school_data')): ?>
    <a class="side-link<?= activeNav('/views/students/') ?>" href="<?= e(appUrl('/views/students/index.php')) ?>"><i data-lucide="graduation-cap"></i><span>Alunos</span></a>
    <?php endif; ?>
    <a class="side-link<?= activeNav('/views/loans/') ?>" href="<?= e(appUrl('/views/loans/index.php')) ?>"><i data-lucide="calendar-days"></i><span><?= can('manage_loans') ? 'Agenda e empréstimos' : 'Meus agendamentos' ?></span></a>
    <?php if (can('manage_maintenance')): ?><a class="side-link<?= activeNav('/views/maintenance/') ?>" href="<?= e(appUrl('/views/maintenance/index.php')) ?>"><i data-lucide="wrench"></i><span>Manutenções</span></a><?php endif; ?>
    <?php if (can('manage_school_data') || can('manage_users')): ?>
    <div class="side-label mt-3">Administração</div>
    <?php if (can('manage_users')): ?><a class="side-link<?= activeNav('/views/users/') ?>" href="<?= e(appUrl('/views/users/index.php')) ?>"><i data-lucide="users"></i><span>Professores e acessos</span></a><?php endif; ?>
    <?php if (can('manage_school_data')): ?>
    <a class="side-link<?= activeNav('/views/categories/') ?>" href="<?= e(appUrl('/views/categories/index.php')) ?>"><i data-lucide="tags"></i><span>Categorias</span></a>
    <a class="side-link<?= activeNav('/views/locations/') ?>" href="<?= e(appUrl('/views/locations/index.php')) ?>"><i data-lucide="map-pin"></i><span>Ambientes</span></a>
    <?php endif; endif; ?>
</nav>
