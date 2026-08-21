<?php
$flashes = pullFlash();
$currentUserName = $_SESSION['user_name'] ?? '';
$currentRole = $_SESSION['user_role'] ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f4c81">
    <title><?= e($title ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(appUrl('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body data-base-url="<?= e(basePath()) ?>">
<nav class="topbar fixed-top">
    <div class="container-fluid h-100 d-flex align-items-center gap-3">
        <button class="btn icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Abrir menu"><i data-lucide="menu"></i></button>
        <a class="brand" href="<?= e(appUrl('/dashboard/index.php')) ?>">
            <span class="brand-mark"><i data-lucide="school"></i></span>
            <span><strong>LabManager</strong><small>Gestão Escolar</small></span>
        </a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <div class="user-chip d-none d-sm-flex">
                <span class="avatar"><?= e(mb_strtoupper(mb_substr($currentUserName, 0, 1))) ?></span>
                <span><strong><?= e($currentUserName) ?></strong><small><?= e(roleLabel($currentRole)) ?></small></span>
            </div>
            <a class="btn icon-btn" href="<?= e(appUrl('/auth/logout.php')) ?>" title="Sair" aria-label="Sair"><i data-lucide="log-out"></i></a>
        </div>
    </div>
</nav>

<aside class="sidebar d-none d-lg-block">
    <?php require __DIR__ . '/navigation.php'; ?>
</aside>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom"><div class="brand"><span class="brand-mark"><i data-lucide="school"></i></span><span><strong>LabManager</strong><small>Gestão Escolar</small></span></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body p-2"><?php require __DIR__ . '/navigation.php'; ?></div>
</div>

<main class="main-content">
    <div class="container-fluid page-shell">
        <?php foreach ($flashes as $type => $messages): foreach ($messages as $message): ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show shadow-sm" role="alert"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; endforeach; ?>
