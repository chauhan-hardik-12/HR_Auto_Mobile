<?php
define('PAGE_TITLE', 'Testimonials - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim($_POST['customer_name'] ?? '');
            $city = trim($_POST['customer_city'] ?? '');
            $vehicle = trim($_POST['vehicle_name'] ?? '');
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            $comment = trim($_POST['comment'] ?? '');
            $status = isset($_POST['status']) ? 1 : 0;

            if ($rating === false || $rating === null) {
                $rating = 5;
            }
            $rating = min(5, max(1, $rating));

            if ($name === '' || $comment === '') {
                setFlashMessage('error', 'Customer name and review comment are required.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (customer_name, customer_city, vehicle_name, rating, comment, status, created_at)
                    VALUES (:name, :city, :vehicle, :rating, :comment, :status, NOW())
                ");
                $stmt->execute([
                    'name' => $name,
                    'city' => $city,
                    'vehicle' => $vehicle,
                    'rating' => $rating,
                    'comment' => $comment,
                    'status' => $status
                ]);
                setFlashMessage('success', "Customer testimonial from '{$name}' added successfully.");
            }
        }

        if ($action === 'edit') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $name = trim($_POST['customer_name'] ?? '');
            $city = trim($_POST['customer_city'] ?? '');
            $vehicle = trim($_POST['vehicle_name'] ?? '');
            $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
            $comment = trim($_POST['comment'] ?? '');
            $status = isset($_POST['status']) ? 1 : 0;

            if ($rating === false || $rating === null) {
                $rating = 5;
            }
            $rating = min(5, max(1, $rating));

            if (!$id || $name === '' || $comment === '') {
                setFlashMessage('error', 'A valid testimonial, customer name, and review comment are required.');
            } else {
                $stmt = $pdo->prepare("
                    UPDATE testimonials
                    SET customer_name = :name, customer_city = :city, vehicle_name = :vehicle,
                        rating = :rating, comment = :comment, status = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    'name' => $name,
                    'city' => $city,
                    'vehicle' => $vehicle,
                    'rating' => $rating,
                    'comment' => $comment,
                    'status' => $status,
                    'id' => $id
                ]);
                setFlashMessage('success', 'Testimonial updated successfully.');
            }
        }

        if ($action === 'toggle_status') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_VALIDATE_INT);
            if (!$id || !in_array($newStatus, [0, 1], true)) {
                setFlashMessage('error', 'Invalid testimonial status request.');
            } else {
                $stmt = $pdo->prepare("UPDATE testimonials SET status = :status WHERE id = :id");
                $stmt->execute(['status' => $newStatus, 'id' => $id]);
                setFlashMessage('success', 'Testimonial status updated.');
            }
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                setFlashMessage('error', 'Invalid testimonial selected.');
            } else {
                $pdo->prepare("DELETE FROM testimonials WHERE id = :id")->execute(['id' => $id]);
                setFlashMessage('success', 'Testimonial deleted.');
            }
        }
    } catch (PDOException $e) {
        error_log('testimonials.php database error: ' . $e->getMessage());
        setFlashMessage('error', 'The testimonial could not be saved. Please try again.');
    }

    header("Location: testimonials.php");
    exit;
}

try {
    $testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    error_log('testimonials.php load error: ' . $e->getMessage());
    $testimonials = [];
    setFlashMessage('error', 'Testimonials could not be loaded from the database.');
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-star mr-2 text-warning"></i> Customer Testimonials</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal"
                        data-target="#addTestimonialModal">
                        <i class="fas fa-plus mr-1"></i> Add New Review
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
                    <h3 class="card-title font-weight-bold"><i class="fas fa-comments mr-1"></i> Customer Reviews
                        (<?= count($testimonials) ?> reviews)</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover datatable-init">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>Customer Name</th>
                                <th>Vehicle &amp; City</th>
                                <th>Rating</th>
                                <th>Review / Feedback</th>
                                <th>Status</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testimonials as $t): ?>
                                <tr>
                                    <td><strong>#<?= $t['id'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($t['customer_name']) ?></strong></td>
                                    <td>
                                        <span
                                            class="badge badge-light border"><?= htmlspecialchars($t['vehicle_name'] ?: 'Vehicle') ?></span><br>
                                        <small class="text-muted"><i
                                                class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($t['customer_city']) ?></small>
                                    </td>
                                    <td>
                                        <span class="text-warning">
                                            <?php for ($i = 0; $i < (int) $t['rating']; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-dark">"<?= htmlspecialchars($t['comment']) ?>"</small>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="new_status"
                                                value="<?= ($t['status'] == 1) ? 0 : 1 ?>">
                                            <button type="submit"
                                                class="btn btn-xs btn-<?= ($t['status'] == 1) ? 'success' : 'secondary' ?>">
                                                <i
                                                    class="fas fa-<?= ($t['status'] == 1) ? 'check-circle' : 'times-circle' ?> mr-1"></i>
                                                <?= ($t['status'] == 1) ? 'Visible' : 'Hidden' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning text-white"
                                            onclick='editTestimonial(<?= json_encode($t) ?>)' title="Edit Review">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display:inline-block;"
                                            onsubmit="return confirmAction(event, 'Are you sure you want to delete review from \'<?= htmlspecialchars($t['customer_name']) ?>\'?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Review">
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

<!-- Modal: Add Testimonial -->
<div class="modal fade" id="addTestimonialModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus mr-1"></i> Add Customer Review</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="e.g. Amit Patel"
                                required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>City / Location</label>
                            <input type="text" name="customer_city" class="form-control"
                                placeholder="e.g. Surendranagar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Vehicle Serviced</label>
                            <input type="text" name="vehicle_name" class="form-control"
                                placeholder="e.g. Hyundai i20, Royal Enfield">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Rating (1 to 5 Stars)</label>
                            <select name="rating" class="form-control">
                                <option value="5" selected>⭐⭐⭐⭐⭐ (5 Stars)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                                <option value="3">⭐⭐⭐ (3 Stars)</option>
                                <option value="2">⭐⭐ (2 Stars)</option>
                                <option value="1">⭐ (1 Star)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Review Comment *</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Write feedback here..."
                            required></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="add_t_status" checked>
                        <label class="form-check-label" for="add_t_status">Show on Live Website</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Testimonial -->
<div class="modal fade" id="editTestimonialModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_t_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Review</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" id="edit_t_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>City / Location</label>
                            <input type="text" name="customer_city" id="edit_t_city" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Vehicle Serviced</label>
                            <input type="text" name="vehicle_name" id="edit_t_vehicle" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Rating</label>
                            <select name="rating" id="edit_t_rating" class="form-control">
                                <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                                <option value="3">⭐⭐⭐ (3 Stars)</option>
                                <option value="2">⭐⭐ (2 Stars)</option>
                                <option value="1">⭐ (1 Star)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Review Comment *</label>
                        <textarea name="comment" id="edit_t_comment" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="edit_t_status">
                        <label class="form-check-label" for="edit_t_status">Show on Live Website</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update
                        Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    function editTestimonial(t) {
        $('#edit_t_id').val(t.id);
        $('#edit_t_name').val(t.customer_name);
        $('#edit_t_city').val(t.customer_city);
        $('#edit_t_vehicle').val(t.vehicle_name);
        $('#edit_t_rating').val(t.rating);
        $('#edit_t_comment').val(t.comment);
        $('#edit_t_status').prop('checked', parseInt(t.status) === 1);
        $('#editTestimonialModal').modal('show');
    }
</script>