<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/autoload.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user'])) {
    $role = ROLE_MAP[$_SESSION['user']['RoleID']] ?? 'admin';
    redirect(BASE_URL . "/public/dashboards/$role.php");
} else {
    redirect(BASE_URL . '/public/login.php');
}
