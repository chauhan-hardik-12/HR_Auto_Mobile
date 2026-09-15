<?php
// Ensure session is started safely
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

// Require root database configuration
require_once __DIR__ . '/../../assets/config/db.php';

// Flash message helper functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type; // success, error, warning, info
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (!empty($_SESSION['flash_message'])) {
        $msg = [
            'type' => $_SESSION['flash_type'] ?? 'info',
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

// Global counts helper for badges & stats
function getPendingBookingsCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function getUnreadMessagesCount($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Currency formatting helper
function formatCurrency($amount) {
    return '₹' . number_format((float) $amount, 2);
}

// Sanitize string
function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}
