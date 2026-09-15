<?php
define('PAGE_TITLE', 'Vehicle Models - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Model
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($name) && $brandId) {
            $stmt = $pdo->prepare("INSERT INTO models (name, brand_id, status, created_at) VALUES (:name, :brand_id, :status, NOW())");
            $stmt->execute(['name' => $name, 'brand_id' => $brandId, 'status' => $status]);
            setFlashMessage('success', "Model '{$name}' added successfully.");
        } else {
            setFlashMessage('error', "Please provide model name and select a brand.");
        }
        header("Location: models.php" . ($brandId ? "?brand_id={$brandId}" : ""));
        exit;
    }

    // Edit Model
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id && !empty($name) && $brandId) {
            $stmt = $pdo->prepare("UPDATE models SET name = :name, brand_id = :brand_id, status = :status WHERE id = :id");
            $stmt->execute(['name' => $name, 'brand_id' => $brandId, 'status' => $status, 'id' => $id]);
            setFlashMessage('success', "Model updated successfully.");
        }
        header("Location: models.php" . ($brandId ? "?brand_id={$brandId}" : ""));
        exit;
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        $brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE models SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Model status updated.");
        }
        header("Location: models.php" . ($brandId ? "?brand_id={$brandId}" : ""));
        exit;
    }

    // Delete Model
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
        if ($id) {
            $bookingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE model_id = :id");
            $bookingCount->execute(['id' => $id]);
            if ($bookingCount->fetchColumn() > 0) {
                setFlashMessage('error', "Cannot delete model because active customer bookings exist for this model.");
            } else {
                // Delete model_services mapping first
                $pdo->prepare("DELETE FROM model_services WHERE model_id = :id")->execute(['id' => $id]);
                // Delete model
                $pdo->prepare("DELETE FROM models WHERE id = :id")->execute(['id' => $id]);
                setFlashMessage('success', "Model deleted successfully.");
            }
        }
        header("Location: models.php" . ($brandId ? "?brand_id={$brandId}" : ""));
        exit;
    }
}

// Filter parameter
$filterBrandId = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT);

$sql = "
    SELECT 
        m.*,
        br.name AS brand_name,
        vt.name AS vehicle_type_name,
        COUNT(ms.id) AS pricing_count
    FROM models m
    JOIN brands br ON m.brand_id = br.id
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    LEFT JOIN model_services ms ON ms.model_id = m.id AND ms.status = 1
    WHERE 1=1
";
$params = [];

if ($filterBrandId) {
    $sql .= " AND m.brand_id = :brand_id";
    $params['brand_id'] = $filterBrandId;
}

$sql .= " GROUP BY m.id ORDER BY br.name ASC, m.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$models = $stmt->fetchAll();

// Brands for modal and dropdown
$allBrands = $pdo->query("
    SELECT br.id, br.name, vt.name AS type_name
    FROM brands br
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    ORDER BY br.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-car mr-2 text-primary"></i> Vehicle Models</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModelModal">
                        <i class="fas fa-plus mr-1"></i> Add New Model
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Filter Card -->
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 text-muted font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter by Brand:</label>
                        <select name="brand_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">-- All Brands (<?= count($allBrands) ?>) --</option>
                            <?php foreach ($allBrands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($filterBrandId == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name'] . ' (' . $b['type_name'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($filterBrandId): ?>
                            <a href="models.php" class="btn btn-sm btn-outline-secondary">Clear Filter</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Models DataTable -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Vehicle Models List (<?= count($models) ?> models)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Model Name</th>
                                <th>Brand</th>
                                <th>Vehicle Type</th>
                                <th>Priced Services</th>
                                <th>Status</th>
                                <th style="width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models as $m): ?>
                                <tr>
                                    <td><strong>#<?= $m['id'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-light border">
                                            <?= htmlspecialchars($m['brand_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($m['vehicle_type_name']) ?></span>
                                    </td>
                                    <td>
                                        <a href="model_pricing.php?model_id=<?= $m['id'] ?>" class="badge badge-success px-2 py-1" title="View Service Pricing Matrix">
                                            <i class="fas fa-tags mr-1"></i> <?= $m['pricing_count'] ?> Priced Services
                                        </a>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <input type="hidden" name="brand_id" value="<?= $filterBrandId ?>">
                                            <input type="hidden" name="new_status" value="<?= ($m['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($m['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($m['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($m['status'] == 1) ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="model_pricing.php?model_id=<?= $m['id'] ?>" class="btn btn-xs btn-outline-success" title="Configure Pricing">
                                            <i class="fas fa-tags"></i> Pricing
                                        </a>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editModel(<?= json_encode($m) ?>)' title="Edit Model">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete model \'<?= htmlspecialchars($m['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <input type="hidden" name="brand_id" value="<?= $filterBrandId ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Model">
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

<!-- Modal: Add Model -->
<div class="modal fade" id="addModelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add New Vehicle Model</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Brand *</label>
                        <select name="brand_id" class="form-control" required>
                            <option value="">-- Select Brand --</option>
                            <?php foreach ($allBrands as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($filterBrandId == $b['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['name'] . ' (' . $b['type_name'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. City, Swift, R15, Classic 350" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_model_status" checked>
                        <label class="form-check-label" for="add_model_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Model -->
<div class="modal fade" id="editModelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_model_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Vehicle Model</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Brand *</label>
                        <select name="brand_id" id="edit_brand_id" class="form-control" required>
                            <?php foreach ($allBrands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name'] . ' (' . $b['type_name'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Model Name *</label>
                        <input type="text" name="name" id="edit_model_name" class="form-control" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_model_status">
                        <label class="form-check-label" for="edit_model_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editModel(m) {
    $('#edit_model_id').val(m.id);
    $('#edit_model_name').val(m.name);
    $('#edit_brand_id').val(m.brand_id);
    $('#edit_model_status').prop('checked', parseInt(m.status) === 1);
    $('#editModelModal').modal('show');
}
</script>
