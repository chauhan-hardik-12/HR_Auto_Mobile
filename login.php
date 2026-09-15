<?php
require_once "includes/settings_loader.php";

// 1. Prevent caching on login page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// 2. If already logged in as Admin, redirect to Admin Dashboard
if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit;
}

// 3. If already logged in as Customer, redirect to Customer Dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['is_authenticated'])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
$messageType = "error";

if (isset($_GET['logged_out'])) {
    $message = "You have been logged out successfully.";
    $messageType = "success";
}

// 4. Handle POST Login Request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $message = "Please enter your mobile number/email/username and password.";
        $messageType = "error";
    } else {
        try {
            // Check 1: Check Admins Table
            $adminStmt = $pdo->prepare("
                SELECT id, username, email, password, name, role, status
                FROM admins
                WHERE (username = :identifier OR email = :identifier)
                LIMIT 1
            ");
            $adminStmt->execute(['identifier' => $identifier]);
            $admin = $adminStmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ((int)$admin['status'] !== 1) {
                    $message = "Your administrator account has been deactivated.";
                    $messageType = "error";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];

                    $target = $_SESSION['admin_redirect_after_login'] ?? 'admin/index.php';
                    unset($_SESSION['admin_redirect_after_login']);
                    header("Location: " . $target);
                    exit;
                }
            } else {
                // Check 2: Check Customer Users Table
                $userStmt = $pdo->prepare("
                    SELECT id, name, email, phone, password
                    FROM users
                    WHERE (phone = :identifier OR email = :identifier)
                    LIMIT 1
                ");
                $userStmt->execute(['identifier' => $identifier]);
                $user = $userStmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['is_authenticated'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['customer_name'] = $user['name'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_email'] = $user['email'];

                    $target = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $target);
                    exit;
                } else {
                    $message = "Invalid mobile number, email, username, or password.";
                    $messageType = "error";
                }
            }
        } catch (PDOException $e) {
            $message = "A database error occurred. Please try again.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= siteSetting('site_name', 'HR Auto Mobile') ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .card {
            background: #fff;
            padding: 2.2rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .brand-logo {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
            text-decoration: none;
            display: block;
        }

        .brand-logo span {
            color: #2563eb;
        }

        .sub-text {
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.35rem;
        }

        input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.15s ease-in-out;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        button {
            width: 100%;
            padding: 0.85rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.15s ease;
            margin-top: 0.5rem;
        }

        button:hover {
            background: #1d4ed8;
        }

        .links-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.25rem;
            font-size: 0.88rem;
            color: #64748b;
        }

        .links-row a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .links-row a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }

        .back-home a {
            color: #64748b;
            text-decoration: none;
        }

        .back-home a:hover {
            color: #0f172a;
        }
    </style>
</head>

<body>
    <div class="card">
        <a href="index.php" class="brand-logo"><?= siteSetting('logo_text', 'HR Auto Mobile') ?> <span>.</span></a>
        <p class="sub-text">Sign in to access your portal</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class='bx <?= $messageType === "success" ? "bx-check-circle" : "bx-error-circle" ?>'></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="identifier">Mobile Number, Email or Username</label>
                <input type="text" id="identifier" name="identifier" placeholder="Enter your credentials" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            </div>

            <button type="submit">Sign In</button>

            <div class="links-row">
                <span>New customer?</span>
                <a href="register.php">Create Account</a>
            </div>

            <div class="back-home">
                <a href="index.php"><i class='bx bx-arrow-back'></i> Return to Home</a>
            </div>
        </form>
    </div>
</body>

</html>