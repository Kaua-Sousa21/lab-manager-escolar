<?php
require_once __DIR__ . '/../includes/session.php';
use Controllers\AuthController;
AuthController::logout();
redirect('/views/auth/login.php');
