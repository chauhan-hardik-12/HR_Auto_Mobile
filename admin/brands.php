<?php
define('PAGE_TITLE', 'Brands Management - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Add Brand
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $typeId = filter_input(INPUT_POST, 'vehicle_type_id', FILTER_VALIDATE_INT);
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($name) && $typeId) {
            $stmt = $pdo->prepare("INSERT INTO brands (name, vehicle_type_id, status, created_at) VALUES (:name, :type_id, :status, NOW())");
            $stmt->execute(['name' => $name, 'type_id' => $typeId, 'status' => $status]);
            setFlashMessage('success', "Brand '{$name}' created successfully.");
        } else {
            setFlashMessage('error', "Please provide both brand name and vehicle type.");
        }
        header("Location: brands.php");
        exit;
    }

    // Edit Brand
    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $typeId = filter_input(INPUT_POST, 'vehicle_type_id', FILTER_VALIDATE_INT);
        $status = isset($_POST['status']) ? 1 : 0;

        if ($id && !empty($name) && $typeId) {
            $stmt = $pdo->prepare("UPDATE brands SET name = :name, vehicle_type_id = :type_id, status = :status WHERE id = :id");
            $stmt->execute(['name' => $name, 'type_id' => $typeId, 'status' => $status, 'id' => $id]);
            setFlashMessage('success', "Brand updated successfully.");
        }
        header("Location: brands.php");
        exit;
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE brands SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Brand status updated.");
        }
        header("Location: brands.php");
        exit;
    }

    // Delete Brand
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $modelCount = $pdo->prepare("SELECT COUNT(*) FROM models WHERE brand_id = :id");
            $modelCount->execute(['id' => $id]);
            if ($modelCount->fetchColumn() > 0) {
                setFlashMessage('error', "Cannot delete brand because models are linked to it. Delete or reassign the models first.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM brands WHERE id = :id");
                $stmt->execute(['id' => $id]);
                setFlashMessage('success', "Brand deleted successfully.");
            }
        }
        header("Location: brands.php");
        exit;
    }
}

// Filter parameter
$filterTypeId = filter_input(INPUT_GET, 'type_id', FILTER_VALIDATE_INT);

$sql = "
    SELECT 
        br.*,
        vt.name AS vehicle_type_name,
        COUNT(m.id) AS total_models
    FROM brands br
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    LEFT JOIN models m ON m.brand_id = br.id
    WHERE 1=1
";
$params = [];

if ($filterTypeId) {
    $sql .= " AND br.vehicle_type_id = :type_id";
    $params['type_id'] = $filterTypeId;
}

$sql .= " GROUP BY br.id ORDER BY br.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$brands = $stmt->fetchAll();

// Vehicle types for modal dropdowns and filters
$vehicleTypes = $pdo->query("SELECT id, name FROM vehicle_types ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-certificate mr-2 text-primary"></i> Vehicle Brands</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBrandModal">
                        <i class="fas fa-plus mr-1"></i> Add New Brand
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Vehicle Type Filters -->
            <div class="card card-outline card-primary shadow-sm mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <span class="mr-3 text-muted font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter by Type:</span>
                        <a href="brands.php" class="btn btn-sm <?= empty($filterTypeId) ? 'btn-primary' : 'btn-outline-secondary' ?> mr-2">All Brands</a>
                        <?php foreach ($vehicleTypes as $vt): ?>
                            <a href="brands.php?type_id=<?= $vt['id'] ?>" class="btn btn-sm <?= ($filterTypeId == $vt['id']) ? 'btn-primary' : 'btn-outline-secondary' ?> mr-2">
                                <?= htmlspecialchars($vt['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Brands DataTable -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> All Brands (<?= count($brands) ?> total)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>Brand Name</th>
                                <th>Vehicle Type</th>
                                <th>Models Count</th>
                                <th>Status</th>
                                <th style="width: 160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $b): ?>
                                <tr>
                                    <td><strong>#<?= $b['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info px-2 py-1">
                                            <?= htmlspecialchars($b['vehicle_type_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="models.php?brand_id=<?= $b['id'] ?>" class="badge badge-secondary px-2 py-1" title="View models under <?= htmlspecialchars($b['name']) ?>">
                                            <?= $b['total_models'] ?> Models <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= ($b['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($b['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($b['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($b['status'] == 1) ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="models.php?brand_id=<?= $b['id'] ?>" class="btn btn-xs btn-outline-info" title="Manage Models">
                                            <i class="fas fa-car"></i> Models
                                        </a>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editBrand(<?= json_encode($b) ?>)' title="Edit Brand">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete brand \'<?= htmlspecialchars($b['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Brand">
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

<!-- Modal: Add Brand -->
<div class="modal fade" id="addBrandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add New Brand</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select name="vehicle_type_id" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <?php foreach ($vehicleTypes as $vt): ?>
                                <option value="<?= $vt['id'] ?>"><?= htmlspecialchars($vt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. BMW, Honda, Yamaha" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_brand_status" checked>
                        <label class="form-check-label" for="add_brand_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Brand -->
<div class="modal fade" id="editBrandModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_brand_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Brand</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Vehicle Type *</label>
                        <select name="vehicle_type_id" id="edit_vehicle_type_id" class="form-control" required>
                            <?php foreach ($vehicleTypes as $vt): ?>
                                <option value="<?= $vt['id'] ?>"><?= htmlspecialchars($vt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand Name *</label>
                        <input type="text" name="name" id="edit_brand_name" class="form-control" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_brand_status">
                        <label class="form-check-label" for="edit_brand_status">Active (Available on Frontend)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editBrand(b) {
    $('#edit_brand_id').val(b.id);
    $('#edit_brand_name').val(b.name);
    $('#edit_vehicle_type_id').val(b.vehicle_type_id);
    $('#edit_brand_status').prop('checked', parseInt(b.status) === 1);
    $('#editBrandModal').modal('show');
}
</script>
