<?php
define('PAGE_TITLE', 'Customers - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add User
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($name) && !empty($phone) && !empty($password)) {
            // Check if phone or email already registered
            $check = $pdo->prepare("SELECT id FROM users WHERE phone = :phone OR email = :email LIMIT 1");
            $check->execute(['phone' => $phone, 'email' => $email]);
            if ($check->fetch()) {
                setFlashMessage('error', "Phone number or email address is already registered.");
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, created_at) VALUES (:name, :phone, :email, :pass, NOW())");
                $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'pass' => $hash]);
                setFlashMessage('success', "Customer account '{$name}' created successfully.");
            }
        } else {
            setFlashMessage('error', "Name, phone, and password are required.");
        }
        header("Location: users.php");
        exit;
    }

    // Edit User
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';

        if ($id && !empty($name) && !empty($phone)) {
            // Check uniqueness except self
            $check = $pdo->prepare("SELECT id FROM users WHERE (phone = :phone OR (email != '' AND email = :email)) AND id != :id LIMIT 1");
            $check->execute(['phone' => $phone, 'email' => $email, 'id' => $id]);
            if ($check->fetch()) {
                setFlashMessage('error', "Phone number or email is already in use by another customer.");
            } else {
                if (!empty($newPassword)) {
                    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, email = :email, password = :pass WHERE id = :id");
                    $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'pass' => $hash, 'id' => $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = :name, phone = :phone, email = :email WHERE id = :id");
                    $stmt->execute(['name' => $name, 'phone' => $phone, 'email' => $email, 'id' => $id]);
                }
                setFlashMessage('success', "Customer details updated successfully.");
            }
        }
        header("Location: users.php");
        exit;
    }

    // Delete User
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);
            setFlashMessage('success', "Customer user deleted successfully.");
        }
        header("Location: users.php");
        exit;
    }
}

// Fetch all users with booking statistics
$users = $pdo->query("
    SELECT 
        u.*,
        COUNT(b.id) AS total_bookings,
        COALESCE(SUM(CASE WHEN b.status = 'completed' OR b.payment_status = 'paid' THEN b.amount ELSE 0 END), 0) AS total_spent
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-users mr-2 text-primary"></i> Customer Accounts</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                        <i class="fas fa-user-plus mr-1"></i> Register New Customer
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
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Registered Customers Directory (<?= count($users) ?> users)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Customer Name</th>
                                <th>Phone Number</th>
                                <th>Email Address</th>
                                <th>Total Appointments</th>
                                <th>Lifetime Spent</th>
                                <th>Joined Date</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong>#<?= $u['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-phone text-muted mr-1"></i> <strong><?= htmlspecialchars($u['phone']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($u['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary px-2 py-1">
                                            <?= $u['total_bookings'] ?> Bookings
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= formatCurrency($u['total_spent']) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('d M Y, h:i A', strtotime($u['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Customer">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete customer account \'<?= htmlspecialchars($u['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-1"></i> Add New Customer</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Customer full name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="email@domain.com">
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Account Password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-edit mr-1"></i> Edit Customer Account</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" id="edit_user_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="edit_user_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>New Password (Leave blank to keep current password)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="New Password (optional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editUser(u) {
    $('#edit_user_id').val(u.id);
    $('#edit_user_name').val(u.name);
    $('#edit_user_phone').val(u.phone);
    $('#edit_user_email').val(u.email);
    $('#editUserModal').modal('show');
}
</script>
