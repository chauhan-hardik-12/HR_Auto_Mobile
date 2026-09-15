<?php
define('PAGE_TITLE', 'Services Management - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Service
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO services (name, duration, description, status, created_at) VALUES (:name, :duration, :description, :status, NOW())");
            $stmt->execute([
                'name' => $name,
                'duration' => $duration,
                'description' => $description,
                'status' => $status
            ]);
            setFlashMessage('success', "Service package '{$name}' created successfully.");
        } else {
            setFlashMessage('error', "Service name is required.");
        }
        header("Location: services.php");
        exit;
    }

    // Edit Service
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $duration = trim($_POST['duration'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id && !empty($name)) {
            $stmt = $pdo->prepare("UPDATE services SET name = :name, duration = :duration, description = :description, status = :status WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'duration' => $duration,
                'description' => $description,
                'status' => $status,
                'id' => $id
            ]);
            setFlashMessage('success', "Service package updated successfully.");
        }
        header("Location: services.php");
        exit;
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE services SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Service status updated.");
        }
        header("Location: services.php");
        exit;
    }

    // Delete Service
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $bookingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE service_id = :id");
            $bookingCount->execute(['id' => $id]);
            if ($bookingCount->fetchColumn() > 0) {
                setFlashMessage('error', "Cannot delete service because active bookings refer to this service package.");
            } else {
                // Delete model_services entries
                $pdo->prepare("DELETE FROM model_services WHERE service_id = :id")->execute(['id' => $id]);
                // Delete service
                $pdo->prepare("DELETE FROM services WHERE id = :id")->execute(['id' => $id]);
                setFlashMessage('success', "Service package deleted successfully.");
            }
        }
        header("Location: services.php");
        exit;
    }
}

// Fetch all services with pricing counts
$services = $pdo->query("
    SELECT 
        s.*,
        COUNT(ms.id) AS mapped_models_count,
        COUNT(DISTINCT b.id) AS total_bookings_count
    FROM services s
    LEFT JOIN model_services ms ON ms.service_id = s.id
    LEFT JOIN bookings b ON b.service_id = s.id
    GROUP BY s.id
    ORDER BY s.id ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-tools mr-2 text-primary"></i> Service Packages</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
                        <i class="fas fa-plus mr-1"></i> Add Service Package
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
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> All Service Packages (<?= count($services) ?> packages)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Package Name</th>
                                <th>Default Duration</th>
                                <th>Description</th>
                                <th>Configured Models</th>
                                <th>Total Bookings</th>
                                <th>Status</th>
                                <th style="width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $s): ?>
                                <tr>
                                    <td><strong>#<?= $s['id'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-light border"><i class="far fa-clock mr-1"></i><?= htmlspecialchars($s['duration'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($s['description'] ?? '', 0, 80, "...")) ?></small>
                                    </td>
                                    <td>
                                        <a href="model_pricing.php?service_id=<?= $s['id'] ?>" class="badge badge-info px-2 py-1">
                                            <?= $s['mapped_models_count'] ?> Models Priced
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary px-2 py-1"><?= $s['total_bookings_count'] ?> Bookings</span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= ($s['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($s['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($s['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($s['status'] == 1) ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="model_pricing.php?service_id=<?= $s['id'] ?>" class="btn btn-xs btn-outline-info" title="Manage Model Pricing">
                                            <i class="fas fa-tags"></i> Pricing
                                        </a>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editService(<?= json_encode($s) ?>)' title="Edit Service">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete service \'<?= htmlspecialchars($s['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Service">
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

<!-- Modal: Add Service -->
<div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add Service Package</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Package Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Full Periodic Service, Engine Oil & Filter Change" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Default Duration</label>
                            <input type="text" name="duration" class="form-control" placeholder="e.g. 2 Hours, 45 Mins">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description &amp; Checklist</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Summary of maintenance operations performed in this package..."></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_srv_status" checked>
                        <label class="form-check-label" for="add_srv_status">Active (Available for booking)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Service -->
<div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_srv_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Service Package</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Package Name *</label>
                            <input type="text" name="name" id="edit_srv_name" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Default Duration</label>
                            <input type="text" name="duration" id="edit_srv_duration" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description &amp; Checklist</label>
                        <textarea name="description" id="edit_srv_desc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_srv_status">
                        <label class="form-check-label" for="edit_srv_status">Active (Available for booking)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editService(s) {
    $('#edit_srv_id').val(s.id);
    $('#edit_srv_name').val(s.name);
    $('#edit_srv_duration').val(s.duration);
    $('#edit_srv_desc').val(s.description);
    $('#edit_srv_status').prop('checked', parseInt(s.status) === 1);
    $('#editServiceModal').modal('show');
}
</script>
