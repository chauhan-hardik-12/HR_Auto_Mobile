<?php
define('PAGE_TITLE', 'Vehicle Types - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Vehicle Type
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO vehicle_types (name, icon, status, created_at) VALUES (:name, :icon, :status, NOW())");
            $stmt->execute(['name' => $name, 'icon' => $icon, 'status' => $status]);
            setFlashMessage('success', "Vehicle Type '{$name}' created successfully.");
        } else {
            setFlashMessage('error', "Vehicle Type name is required.");
        }
        header("Location: vehicle_types.php");
        exit;
    }

    // Edit Vehicle Type
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE vehicle_types SET name = :name, icon = :icon, status = :status WHERE id = :id");
            $stmt->execute(['name' => $name, 'icon' => $icon, 'status' => $status, 'id' => $id]);
            setFlashMessage('success', "Vehicle Type updated successfully.");
        }
        header("Location: vehicle_types.php");
        exit;
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE vehicle_types SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Vehicle Type status updated.");
        }
        header("Location: vehicle_types.php");
        exit;
    }

    // Delete
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            // Check if brands exist under this type
            $brandCount = $pdo->prepare("SELECT COUNT(*) FROM brands WHERE vehicle_type_id = :id");
            $brandCount->execute(['id' => $id]);
            if ($brandCount->fetchColumn() > 0) {
                setFlashMessage('error', "Cannot delete Vehicle Type because brands are linked to it. Delete or reassign the brands first.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM vehicle_types WHERE id = :id");
                $stmt->execute(['id' => $id]);
                setFlashMessage('success', "Vehicle Type deleted successfully.");
            }
        }
        header("Location: vehicle_types.php");
        exit;
    }
}

// Fetch all vehicle types with counts of brands and models
$types = $pdo->query("
    SELECT 
        vt.*,
        COUNT(DISTINCT br.id) AS total_brands,
        COUNT(DISTINCT m.id) AS total_models
    FROM vehicle_types vt
    LEFT JOIN brands br ON br.vehicle_type_id = vt.id
    LEFT JOIN models m ON m.brand_id = br.id
    GROUP BY vt.id
    ORDER BY vt.id ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-truck-pickup mr-2 text-primary"></i> Vehicle Types</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTypeModal">
                        <i class="fas fa-plus mr-1"></i> Add Vehicle Type
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
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Configured Vehicle Categories</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-init">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Category Name</th>
                                <th>Icon Identifier</th>
                                <th>Brands Count</th>
                                <th>Models Count</th>
                                <th>Status</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($types as $t): ?>
                                <tr>
                                    <td><strong>#<?= $t['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($t['name']) ?></strong>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($t['icon'] ?? 'bx-car') ?></code>
                                    </td>
                                    <td>
                                        <a href="brands.php?type_id=<?= $t['id'] ?>" class="badge badge-info px-2 py-1">
                                            <?= $t['total_brands'] ?> Brands
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary px-2 py-1"><?= $t['total_models'] ?> Models</span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= ($t['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($t['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($t['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($t['status'] == 1) ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editType(<?= json_encode($t) ?>)' title="Edit Type">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete Vehicle Type \'<?= htmlspecialchars($t['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Type">
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

<!-- Modal: Add Type -->
<div class="modal fade" id="addTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add New Vehicle Type</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Car, Bike, Electric Vehicle" required>
                    </div>
                    <div class="form-group">
                        <label>Icon CSS Class</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g. bx-car, bx-cycling, fas fa-car">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_status" checked>
                        <label class="form-check-label" for="add_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Type -->
<div class="modal fade" id="editTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Vehicle Type</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Icon CSS Class</label>
                        <input type="text" name="icon" id="edit_icon" class="form-control">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_status">
                        <label class="form-check-label" for="edit_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editType(t) {
    $('#edit_id').val(t.id);
    $('#edit_name').val(t.name);
    $('#edit_icon').val(t.icon);
    $('#edit_status').prop('checked', parseInt(t.status) === 1);
    $('#editTypeModal').modal('show');
}
</script>
