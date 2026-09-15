<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../assets/config/db.php';

// Global cache for site settings
$siteSettings = [];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $siteSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // If settings table not accessible, fallback gracefully
}

function siteSetting($key, $default = '') {
    global $siteSettings;
    return htmlspecialchars($siteSettings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function siteSettingRaw($key, $default = '') {
    global $siteSettings;
    return $siteSettings[$key] ?? $default;
}
