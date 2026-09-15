<?php
require_once "../config/db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    if ($action === 'login') {
        $identifier = trim($input['phone'] ?? $input['identifier'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Please enter your details."]);
            exit;
        }

        // Check Admins first
        $adminStmt = $pdo->prepare("SELECT id, username, email, password, name, role, status FROM admins WHERE (username = :identifier OR email = :identifier) LIMIT 1");
        $adminStmt->execute(['identifier' => $identifier]);
        $admin = $adminStmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            if ((int)$admin['status'] !== 1) {
                echo json_encode(["success" => false, "message" => "Your admin account is inactive."]);
                exit;
            }
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            echo json_encode(["success" => true, "is_admin" => true, "redirect" => "admin/index.php", "message" => "Admin Login successful"]);
            exit;
        }

        // Check Customer Users
        $stmt = $pdo->prepare("SELECT id, name, email, phone, password FROM users WHERE phone = :phone OR email = :email LIMIT 1");
        $stmt->execute(['phone' => $identifier, 'email' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['is_authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['customer_name'] = $user['name'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_email'] = $user['email'];

            echo json_encode(["success" => true, "is_admin" => false, "redirect" => "dashboard.php", "message" => "Login successful"]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid credentials."]);
        }
        exit;
    }

    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($name) || empty($phone) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Name, phone, and password are required."]);
            exit;
        }

        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE phone = :phone OR (email != '' AND email = :email) LIMIT 1");
        $checkStmt->execute(['phone' => $phone, 'email' => $email]);
        if ($checkStmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Phone number or email is already registered."]);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, created_at) VALUES (:name, :phone, :email, :password, NOW())");
        $insertStmt->execute([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        $newUserId = (int) $pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['is_authenticated'] = true;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['customer_name'] = $name;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_email'] = $email;

        echo json_encode(["success" => true, "message" => "Registration successful"]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Invalid action."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error occurred."]);
}