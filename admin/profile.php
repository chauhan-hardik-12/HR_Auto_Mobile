<?php
define('PAGE_TITLE', 'My Profile - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Fetch latest admin data
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $currentAdmin['id']]);
$admin = $stmt->fetch();

if (!$admin) {
    header("Location: logout.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($username) || empty($email)) {
        setFlashMessage('error', "Name, username, and email are required.");
    } else {
        // Check uniqueness of username and email
        $check = $pdo->prepare("SELECT id FROM admins WHERE (username = :u OR email = :e) AND id != :id LIMIT 1");
        $check->execute(['u' => $username, 'e' => $email, 'id' => $admin['id']]);
        if ($check->fetch()) {
            setFlashMessage('error', "Username or email is already taken.");
        } else {
            // Password change requested
            if (!empty($newPassword)) {
                if (empty($currentPassword) || !password_verify($currentPassword, $admin['password'])) {
                    setFlashMessage('error', "Current password entered is incorrect.");
                } elseif (strlen($newPassword) < 6) {
                    setFlashMessage('error', "New password must be at least 6 characters long.");
                } elseif ($newPassword !== $confirmPassword) {
                    setFlashMessage('error', "New password and confirmation do not match.");
                } else {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE admins SET name = :n, username = :u, email = :e, password = :p WHERE id = :id");
                    $update->execute(['n' => $name, 'u' => $username, 'e' => $email, 'p' => $hash, 'id' => $admin['id']]);
                    $_SESSION['admin_name'] = $name;
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_email'] = $email;
                    setFlashMessage('success', "Profile and password updated successfully.");
                    header("Location: profile.php");
                    exit;
                }
            } else {
                $update = $pdo->prepare("UPDATE admins SET name = :n, username = :u, email = :e WHERE id = :id");
                $update->execute(['n' => $name, 'u' => $username, 'e' => $email, 'id' => $admin['id']]);
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_email'] = $email;
                setFlashMessage('success', "Profile information updated successfully.");
                header("Location: profile.php");
                exit;
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-user-cog mr-2 text-primary"></i> Administrator Profile</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Left Profile Summary Card -->
                <div class="col-md-4">
                    <div class="card card-primary card-outline shadow-sm">
                        <div class="card-body box-profile text-center">
                            <img class="profile-user-img img-fluid img-circle elevation-2" src="dist/img/admin.jpeg" alt="User profile picture">
                            <h3 class="profile-username font-weight-bold mt-2"><?= htmlspecialchars($admin['name']) ?></h3>
                            <p class="text-muted"><span class="badge badge-primary px-3 py-1"><?= ucfirst($admin['role']) ?></span></p>

                            <ul class="list-group list-group-unbordered mb-3 text-left">
                                <li class="list-group-item">
                                    <b>Username</b> <a class="float-right text-dark"><code><?= htmlspecialchars($admin['username']) ?></code></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Email</b> <a class="float-right text-dark"><?= htmlspecialchars($admin['email']) ?></a>
                                </li>
                                <li class="list-group-item">
                                    <b>Member Since</b> <a class="float-right text-dark"><?= date('d M Y', strtotime($admin['created_at'])) ?></a>
                                </li>
                            </ul>

                            <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-block"><i class="fas fa-globe mr-1"></i> Visit Main Website</a>
                        </div>
                    </div>
                </div>

                <!-- Right Edit Settings Card -->
                <div class="col-md-8">
                    <div class="card card-primary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Account Information</h3>
                        </div>
                        <form method="POST" action="profile.php">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Full Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Username *</label>
                                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Email Address *</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                </div>

                                <hr class="my-4">
                                <h5 class="text-secondary font-weight-bold mb-3"><i class="fas fa-key mr-1"></i> Change Password (Optional)</h5>

                                <div class="form-group">
                                    <label>Current Password (required only if changing password)</label>
                                    <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-light text-right">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
