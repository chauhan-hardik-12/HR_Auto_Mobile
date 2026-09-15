<?php
define('PAGE_TITLE', 'Admin Accounts - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Only superadmin can access this page
if (($currentAdmin['role'] ?? '') !== 'superadmin') {
    setFlashMessage('error', "Access restricted to Super Administrators.");
    header("Location: index.php");
    exit;
}

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Admin
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($username) && !empty($email) && !empty($password) && !empty($name)) {
            $check = $pdo->prepare("SELECT id FROM admins WHERE username = :u OR email = :e LIMIT 1");
            $check->execute(['u' => $username, 'e' => $email]);
            if ($check->fetch()) {
                setFlashMessage('error', "Username or email is already in use by another admin.");
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO admins (username, email, password, name, role, status, created_at)
                    VALUES (:u, :e, :p, :n, :r, :s, NOW())
                ");
                $stmt->execute([
                    'u' => $username,
                    'e' => $email,
                    'p' => $hash,
                    'n' => $name,
                    'r' => $role,
                    's' => $status
                ]);
                setFlashMessage('success', "New administrator account '{$username}' created successfully.");
            }
        } else {
            setFlashMessage('error', "Please fill in all required fields.");
        }
        header("Location: admins.php");
        exit;
    }

    // Edit Admin
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id && !empty($username) && !empty($email) && !empty($name)) {
            $check = $pdo->prepare("SELECT id FROM admins WHERE (username = :u OR email = :e) AND id != :id LIMIT 1");
            $check->execute(['u' => $username, 'e' => $email, 'id' => $id]);
            if ($check->fetch()) {
                setFlashMessage('error', "Username or email is already in use.");
            } else {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE admins SET username = :u, email = :e, name = :n, password = :p, role = :r, status = :s WHERE id = :id
                    ");
                    $stmt->execute([
                        'u' => $username,
                        'e' => $email,
                        'n' => $name,
                        'p' => $hash,
                        'r' => $role,
                        's' => $status,
                        'id' => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE admins SET username = :u, email = :e, name = :n, role = :r, status = :s WHERE id = :id
                    ");
                    $stmt->execute([
                        'u' => $username,
                        'e' => $email,
                        'n' => $name,
                        'r' => $role,
                        's' => $status,
                        'id' => $id
                    ]);
                }
                setFlashMessage('success', "Administrator account updated successfully.");
            }
        }
        header("Location: admins.php");
        exit;
    }

    // Delete Admin
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            if ($id == $currentAdmin['id']) {
                setFlashMessage('error', "You cannot delete your own active administrator account.");
            } else {
                $pdo->prepare("DELETE FROM admins WHERE id = :id")->execute(['id' => $id]);
                setFlashMessage('success', "Administrator account deleted.");
            }
        }
        header("Location: admins.php");
        exit;
    }
}

$admins = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-user-shield mr-2 text-primary"></i> Administrator Accounts</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addAdminModal">
                        <i class="fas fa-plus mr-1"></i> Add Administrator
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> System Staff &amp; Admins</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-init">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $a): ?>
                                <tr>
                                    <td><strong>#<?= $a['id'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($a['username']) ?></code></td>
                                    <td><?= htmlspecialchars($a['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= ($a['role'] === 'superadmin') ? 'danger' : 'info' ?> px-2 py-1">
                                            <?= ucfirst($a['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= ($a['status'] == 1) ? 'success' : 'secondary' ?> px-2 py-1">
                                            <?= ($a['status'] == 1) ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                    <td><small><?= date('d M Y', strtotime($a['created_at'])) ?></small></td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editAdmin(<?= json_encode($a) ?>)' title="Edit Account">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($a['id'] != $currentAdmin['id']): ?>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete admin account \'<?= htmlspecialchars($a['username']) ?>\'?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                <button type="submit" class="btn btn-xs btn-danger" title="Delete Admin">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal: Add Admin -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add Administrator</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_adm_status" checked>
                        <label class="form-check-label" for="add_adm_status">Active Account</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Admin -->
<div class="modal fade" id="editAdminModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_adm_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Administrator</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_adm_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" id="edit_adm_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_adm_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_adm_role" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_adm_status">
                        <label class="form-check-label" for="edit_adm_status">Active Account</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editAdmin(a) {
    $('#edit_adm_id').val(a.id);
    $('#edit_adm_name').val(a.name);
    $('#edit_adm_username').val(a.username);
    $('#edit_adm_email').val(a.email);
    $('#edit_adm_role').val(a.role);
    $('#edit_adm_status').prop('checked', parseInt(a.status) === 1);
    $('#editAdminModal').modal('show');
}
</script>
