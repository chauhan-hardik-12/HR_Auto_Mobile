<?php
define('PAGE_TITLE', 'Model Pricing - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Pricing
    if ($action === 'add') {
        $modelId = filter_input(INPUT_POST, 'model_id', FILTER_VALIDATE_INT);
        $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
        $price = (float) ($_POST['price'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $isRecommended = isset($_POST['is_recommended']) ? 1 : 0;
        $status = isset($_POST['status']) ? 1 : 0;

        if ($modelId && $serviceId) {
            // Check if mapping already exists
            $check = $pdo->prepare("SELECT id FROM model_services WHERE model_id = :mid AND service_id = :sid LIMIT 1");
            $check->execute(['mid' => $modelId, 'sid' => $serviceId]);
            if ($check->fetch()) {
                setFlashMessage('error', "Pricing for this Model and Service combination already exists. Please edit the existing entry.");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO model_services (model_id, service_id, price, duration, is_recommended, status, created_at)
                    VALUES (:mid, :sid, :price, :duration, :rec, :status, NOW())
                ");
                $stmt->execute([
                    'mid' => $modelId,
                    'sid' => $serviceId,
                    'price' => $price,
                    'duration' => $duration,
                    'rec' => $isRecommended,
                    'status' => $status
                ]);
                setFlashMessage('success', "Service pricing successfully added for model.");
            }
        } else {
            setFlashMessage('error', "Please select both a model and a service package.");
        }
        header("Location: model_pricing.php" . ($modelId ? "?model_id={$modelId}" : ""));
        exit;
    }

    // Edit Pricing
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $price = (float) ($_POST['price'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $isRecommended = isset($_POST['is_recommended']) ? 1 : 0;
        $status = isset($_POST['status']) ? 1 : 0;
        $modelId = filter_input(INPUT_POST, 'model_id', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE model_services 
                SET price = :price, duration = :duration, is_recommended = :rec, status = :status 
                WHERE id = :id
            ");
            $stmt->execute([
                'price' => $price,
                'duration' => $duration,
                'rec' => $isRecommended,
                'status' => $status,
                'id' => $id
            ]);
            setFlashMessage('success', "Pricing details updated successfully.");
        }
        header("Location: model_pricing.php" . ($modelId ? "?model_id={$modelId}" : ""));
        exit;
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        $modelId = filter_input(INPUT_POST, 'model_id', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE model_services SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Pricing status updated.");
        }
        header("Location: model_pricing.php" . ($modelId ? "?model_id={$modelId}" : ""));
        exit;
    }

    // Delete Mapping
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $modelId = filter_input(INPUT_POST, 'model_id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare("DELETE FROM model_services WHERE id = :id")->execute(['id' => $id]);
            setFlashMessage('success', "Model service pricing entry deleted.");
        }
        header("Location: model_pricing.php" . ($modelId ? "?model_id={$modelId}" : ""));
        exit;
    }
}

// Filter parameters
$filterModelId = filter_input(INPUT_GET, 'model_id', FILTER_VALIDATE_INT);
$filterServiceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);

$sql = "
    SELECT 
        ms.*,
        m.name AS model_name,
        br.name AS brand_name,
        vt.name AS vehicle_type_name,
        s.name AS service_name,
        s.duration AS default_duration
    FROM model_services ms
    JOIN models m ON ms.model_id = m.id
    JOIN brands br ON m.brand_id = br.id
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    JOIN services s ON ms.service_id = s.id
    WHERE 1=1
";
$params = [];

if ($filterModelId) {
    $sql .= " AND ms.model_id = :mid";
    $params['mid'] = $filterModelId;
}

if ($filterServiceId) {
    $sql .= " AND ms.service_id = :sid";
    $params['sid'] = $filterServiceId;
}

$sql .= " ORDER BY br.name ASC, m.name ASC, ms.price ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pricings = $stmt->fetchAll();

// Dropdowns for filters and modals
$allModels = $pdo->query("
    SELECT m.id, m.name, br.name as brand_name, vt.name as type_name
    FROM models m
    JOIN brands br ON m.brand_id = br.id
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    ORDER BY br.name ASC, m.name ASC
")->fetchAll();

$allServices = $pdo->query("SELECT id, name, duration FROM services ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-tags mr-2 text-primary"></i> Model Service Pricing</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPricingModal">
                        <i class="fas fa-plus mr-1"></i> Add Custom Pricing
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Filter Toolbar -->
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="form-inline">
                        <label class="mr-2 text-muted font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter by Model:</label>
                        <select name="model_id" class="form-control form-control-sm mr-3">
                            <option value="">-- All Models (<?= count($allModels) ?>) --</option>
                            <?php foreach ($allModels as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($filterModelId == $m['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['brand_name'] . ' ' . $m['name'] . ' (' . $m['type_name'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="mr-2 text-muted font-weight-bold">Service Package:</label>
                        <select name="service_id" class="form-control form-control-sm mr-3">
                            <option value="">-- All Services --</option>
                            <?php foreach ($allServices as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($filterServiceId == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-search mr-1"></i> Filter</button>
                        <?php if ($filterModelId || $filterServiceId): ?>
                            <a href="model_pricing.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Pricing DataTable -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> Service Pricing Matrix (<?= count($pricings) ?> entries)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Vehicle Model</th>
                                <th>Vehicle Type</th>
                                <th>Service Package</th>
                                <th>Price (₹)</th>
                                <th>Duration</th>
                                <th>Featured</th>
                                <th>Status</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pricings as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['brand_name'] . ' ' . $p['model_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= htmlspecialchars($p['vehicle_type_name']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['service_name']) ?></strong>
                                    </td>
                                    <td>
                                        <strong class="text-success font-weight-bold"><?= formatCurrency($p['price']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">
                                            <i class="far fa-clock mr-1"></i><?= htmlspecialchars($p['duration'] ?: $p['default_duration'] ?: 'Standard') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['is_recommended']): ?>
                                            <span class="badge badge-warning"><i class="fas fa-star mr-1"></i> Recommended</span>
                                        <?php else: ?>
                                            <span class="text-muted small">Standard</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="model_id" value="<?= $filterModelId ?>">
                                            <input type="hidden" name="new_status" value="<?= ($p['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($p['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($p['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($p['status'] == 1) ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editPricing(<?= json_encode($p) ?>)' title="Edit Pricing">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete this price entry?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="model_id" value="<?= $filterModelId ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Entry">
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

<!-- Modal: Add Pricing -->
<div class="modal fade" id="addPricingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add Custom Model Pricing</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Vehicle Model *</label>
                            <select name="model_id" class="form-control" required>
                                <option value="">-- Select Model --</option>
                                <?php foreach ($allModels as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= ($filterModelId == $m['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['brand_name'] . ' ' . $m['name'] . ' (' . $m['type_name'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Service Package *</label>
                            <select name="service_id" class="form-control" required>
                                <option value="">-- Select Service --</option>
                                <?php foreach ($allServices as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($filterServiceId == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Custom Price (₹) *</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Custom Duration (Optional)</label>
                            <input type="text" name="duration" class="form-control" placeholder="Leave empty to use service default">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group form-check pl-4">
                            <input type="checkbox" name="is_recommended" class="form-check-input" id="add_rec">
                            <label class="form-check-label" for="add_rec">Mark as Recommended Package</label>
                        </div>
                        <div class="col-md-6 form-group form-check pl-4">
                            <input type="checkbox" name="status" class="form-check-input" id="add_prc_status" checked>
                            <label class="form-check-label" for="add_prc_status">Active (Available for booking)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Pricing -->
<div class="modal fade" id="editPricingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_prc_id">
                <input type="hidden" name="model_id" value="<?= $filterModelId ?>">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Service Pricing</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <strong id="edit_model_label"></strong> - <span id="edit_service_label"></span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Custom Price (₹) *</label>
                            <input type="number" step="0.01" name="price" id="edit_prc_price" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Custom Duration</label>
                            <input type="text" name="duration" id="edit_prc_duration" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group form-check pl-4">
                            <input type="checkbox" name="is_recommended" class="form-check-input" id="edit_prc_rec">
                            <label class="form-check-label" for="edit_prc_rec">Mark as Recommended Package</label>
                        </div>
                        <div class="col-md-6 form-group form-check pl-4">
                            <input type="checkbox" name="status" class="form-check-input" id="edit_prc_status">
                            <label class="form-check-label" for="edit_prc_status">Active (Available for booking)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editPricing(p) {
    $('#edit_prc_id').val(p.id);
    $('#edit_model_label').text(p.brand_name + ' ' + p.model_name);
    $('#edit_service_label').text(p.service_name);
    $('#edit_prc_price').val(p.price);
    $('#edit_prc_duration').val(p.duration);
    $('#edit_prc_rec').prop('checked', parseInt(p.is_recommended) === 1);
    $('#edit_prc_status').prop('checked', parseInt(p.status) === 1);
    $('#editPricingModal').modal('show');
}
</script>
