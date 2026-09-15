<?php
require_once __DIR__ . '/db.php';

// 1. Send strict anti-cache headers FIRST so browser never caches protected admin views
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// 2. Check if admin is authenticated
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? '/Hr_Auto_Mobile/admin/index.php';
    $_SESSION['admin_redirect_after_login'] = $currentUri;
    header("Location: /Hr_Auto_Mobile/login.php");
    exit;
}

// Current logged in admin info
$currentAdmin = [
    'id' => $_SESSION['admin_id'] ?? 0,
    'username' => $_SESSION['admin_username'] ?? 'Admin',
    'name' => $_SESSION['admin_name'] ?? 'Administrator',
    'email' => $_SESSION['admin_email'] ?? 'admin@hrautomobile.com',
    'role' => $_SESSION['admin_role'] ?? 'admin'
];
