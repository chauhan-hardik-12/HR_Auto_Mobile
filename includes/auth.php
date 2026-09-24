<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 1. Send strict anti-cache headers FIRST so browser never caches protected views
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// 2. Verify login status
if (empty($_SESSION['is_authenticated']) || empty($_SESSION['user_id'])) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
    $path = parse_url($currentUri, PHP_URL_PATH);
    $targetFile = basename($path);

    if (!in_array($targetFile, ['login.php', 'register.php', 'logout.php'], true)) {
        $_SESSION['redirect_after_login'] = $currentUri;
    }

    header("Location: login.php");
    exit;
}