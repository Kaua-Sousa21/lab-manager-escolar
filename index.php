<?php
require_once __DIR__ . '/includes/session.php';
use Controllers\AuthController;
if (AuthController::isAuthenticated()) redirect('/dashboard/index.php');
redirect('/views/auth/login.php');
