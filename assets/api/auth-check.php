<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['is_authenticated']) && !empty($_SESSION['user_id']);

echo json_encode([
    "success" => true,
    "logged_in" => $isLoggedIn,
    "user_id" => $isLoggedIn ? (int) $_SESSION['user_id'] : null
]);