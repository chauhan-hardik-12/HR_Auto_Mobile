<?php
define('PAGE_TITLE', 'Partners - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

    // Helper function for uploading partner image
    function handlePartnerImageUpload($fileInputKey) {
        if (!isset($_FILES[$fileInputKey]) || $_FILES[$fileInputKey]['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $file = $_FILES[$fileInputKey];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            setFlashMessage('error', 'Invalid file format. Allowed: JPG, PNG, WEBP, GIF, SVG.');
            return false;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            setFlashMessage('error', 'Image size exceeds maximum limit of 5MB.');
            return false;
        }

        $uploadDir = realpath(__DIR__ . '/../assets/images') ?: (__DIR__ . '/../assets/images');
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $newFileName = 'partner-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
        $targetPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'assets/images/' . $newFileName;
        } else {
            setFlashMessage('error', 'Failed to save the uploaded image file.');
            return false;
        }
    }

    // Handle POST actions
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $action = $_POST['action'] ?? '';

        // Add Partner
        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $displayOrder = (int) ($_POST['display_order'] ?? 0);
            $status = isset($_POST['status']) ? 1 : 0;

            // Check if file was uploaded via browse
            $uploadedPath = handlePartnerImageUpload('image_file');
            if ($uploadedPath !== false) {
                $image = $uploadedPath;
            } else {
                $image = trim($_POST['image'] ?? 'assets/images/partner-1.jpg');
                if (empty($image)) {
                    $image = 'assets/images/partner-1.jpg';
                }
            }

            if (!empty($name) && !empty($role)) {
                $stmt = $pdo->prepare("INSERT INTO partners (name, role, image, display_order, status, created_at) VALUES (:n, :r, :img, :o, :s, NOW())");
                $stmt->execute(['n' => $name, 'r' => $role, 'img' => $image, 'o' => $displayOrder, 's' => $status]);
                setFlashMessage('success', "Partner profile for '{$name}' created successfully.");
            } else {
                setFlashMessage('error', "Name and role are required.");
            }
            header("Location: partners.php");
            exit;
        }

        // Edit Partner
        if ($action === 'edit') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $displayOrder = (int) ($_POST['display_order'] ?? 0);
            $status = isset($_POST['status']) ? 1 : 0;

            // Check if a new file was uploaded via browse
            $uploadedPath = handlePartnerImageUpload('image_file');
            if ($uploadedPath !== false) {
                $image = $uploadedPath;
            } else {
                $image = trim($_POST['image'] ?? '');
                // If path is empty and no new upload, preserve existing database image
                if (empty($image) && $id) {
                    $currStmt = $pdo->prepare("SELECT image FROM partners WHERE id = :id");
                    $currStmt->execute(['id' => $id]);
                    $image = $currStmt->fetchColumn() ?: 'assets/images/partner-1.jpg';
                }
            }

            if ($id && !empty($name) && !empty($role)) {
                $stmt = $pdo->prepare("UPDATE partners SET name = :n, role = :r, image = :img, display_order = :o, status = :s WHERE id = :id");
                $stmt->execute(['n' => $name, 'r' => $role, 'img' => $image, 'o' => $displayOrder, 's' => $status, 'id' => $id]);
                setFlashMessage('success', "Partner profile updated successfully.");
            }
            header("Location: partners.php");
            exit;
        }

    // Toggle Status
    if ($action === 'toggle_status') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
        if ($id !== null && $newStatus !== null) {
            $stmt = $pdo->prepare("UPDATE partners SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $id]);
            setFlashMessage('success', "Partner status updated.");
        }
        header("Location: partners.php");
        exit;
    }

    // Delete Partner
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare("DELETE FROM partners WHERE id = :id")->execute(['id' => $id]);
            setFlashMessage('success', "Partner removed successfully.");
        }
        header("Location: partners.php");
        exit;
    }
}

$partners = $pdo->query("SELECT * FROM partners ORDER BY display_order ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-handshake mr-2 text-primary"></i> Team &amp; Partners</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPartnerModal">
                        <i class="fas fa-plus mr-1"></i> Add Partner Profile
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
                    <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1"></i> All Partners (<?= count($partners) ?> profiles)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-init">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Order</th>
                                <th>Photo</th>
                                <th>Partner Name</th>
                                <th>Designation / Role</th>
                                <th>Status</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partners as $p): ?>
                                <tr>
                                    <td><span class="badge badge-light border">#<?= $p['display_order'] ?></span></td>
                                    <td>
                                        <img src="../<?= htmlspecialchars($p['image']) ?>" alt="Partner" style="width: 45px; height: 45px; object-fit: cover; border-radius: 50%;" onerror="this.src='dist/img/admin.jpeg'">
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['role']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= ($p['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-xs btn-<?= ($p['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i class="fas fa-<?= ($p['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($p['status'] == 1) ? 'Active' : 'Hidden' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editPartner(<?= json_encode($p) ?>)' title="Edit Partner">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to delete partner \'<?= htmlspecialchars($p['name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Partner">
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

<!-- Modal: Add Partner -->
<div class="modal fade" id="addPartnerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add Partner Profile</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Raj Chauhan" required>
                    </div>
                    <div class="form-group">
                        <label>Role / Title *</label>
                        <input type="text" name="role" class="form-control" placeholder="e.g. Partner of HR Auto Mobile" required>
                    </div>
                    
                    <!-- Image Upload / Browse System -->
                    <div class="form-group">
                        <label><i class="fas fa-image mr-1"></i> Partner Photo</label>
                        <div class="custom-file mb-2">
                            <input type="file" name="image_file" class="custom-file-input" id="add_p_file" accept="image/*" onchange="previewPartnerImage(this, '#add_img_preview', '#add_p_file_label')">
                            <label class="custom-file-label text-truncate" id="add_p_file_label" for="add_p_file">Browse image from device...</label>
                        </div>
                        <div class="d-flex align-items-center p-2 rounded bg-light border">
                            <img id="add_img_preview" src="../assets/images/partner-1.jpg" alt="Preview" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #007bff; margin-right: 12px; flex-shrink: 0;"
                                 onerror="this.src='dist/img/admin.jpeg'">
                            <div class="flex-grow-1">
                                <small class="text-muted font-weight-bold d-block">Or Asset Path:</small>
                                <input type="text" name="image" id="add_p_image" class="form-control form-control-sm" value="assets/images/partner-1.jpg" placeholder="assets/images/partner-1.jpg" oninput="$('#add_img_preview').attr('src', '../' + this.value)">
                            </div>
                        </div>
                        <small class="form-text text-muted">Browse an image (JPG, PNG, WEBP, max 5MB) or keep the default asset path.</small>
                    </div>

                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="1">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_p_status" checked>
                        <label class="form-check-label" for="add_p_status">Show on Live Website</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Partner -->
<div class="modal fade" id="editPartnerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_p_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Partner</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="edit_p_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role / Title *</label>
                        <input type="text" name="role" id="edit_p_role" class="form-control" required>
                    </div>

                    <!-- Image Upload / Browse System -->
                    <div class="form-group">
                        <label><i class="fas fa-image mr-1"></i> Partner Photo</label>
                        <div class="custom-file mb-2">
                            <input type="file" name="image_file" class="custom-file-input" id="edit_p_file" accept="image/*" onchange="previewPartnerImage(this, '#edit_img_preview', '#edit_p_file_label')">
                            <label class="custom-file-label text-truncate" id="edit_p_file_label" for="edit_p_file">Browse new image from device...</label>
                        </div>
                        <div class="d-flex align-items-center p-2 rounded bg-light border">
                            <img id="edit_img_preview" src="../assets/images/partner-1.jpg" alt="Preview" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 2px solid #ffc107; margin-right: 12px; flex-shrink: 0;"
                                 onerror="this.src='dist/img/admin.jpeg'">
                            <div class="flex-grow-1">
                                <small class="text-muted font-weight-bold d-block">Current / Asset Path:</small>
                                <input type="text" name="image" id="edit_p_image" class="form-control form-control-sm" placeholder="assets/images/partner-1.jpg" oninput="$('#edit_img_preview').attr('src', '../' + this.value)">
                            </div>
                        </div>
                        <small class="form-text text-muted">Browse a new image from device to replace, or keep the existing path.</small>
                    </div>

                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="edit_p_order" class="form-control">
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_p_status">
                        <label class="form-check-label" for="edit_p_status">Show on Live Website</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function editPartner(p) {
    $('#edit_p_id').val(p.id);
    $('#edit_p_name').val(p.name);
    $('#edit_p_role').val(p.role);
    $('#edit_p_image').val(p.image);
    $('#edit_p_order').val(p.display_order);
    $('#edit_p_status').prop('checked', parseInt(p.status) === 1);

    // Reset file input & label
    $('#edit_p_file').val('');
    $('#edit_p_file_label').text('Browse new image from device...');

    // Set preview image
    var imgSrc = p.image ? ('../' + p.image) : 'dist/img/admin.jpeg';
    $('#edit_img_preview').attr('src', imgSrc);

    $('#editPartnerModal').modal('show');
}

function previewPartnerImage(input, previewSelector, labelSelector) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        $(labelSelector).text(file.name);

        var reader = new FileReader();
        reader.onload = function(e) {
            $(previewSelector).attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    }
}
</script>
