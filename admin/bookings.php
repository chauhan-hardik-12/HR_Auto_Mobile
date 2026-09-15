<?php
define('PAGE_TITLE', 'Bookings Management - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Quick Update Booking Status
    if ($action === 'update_status') {
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $newStatus = $_POST['status'] ?? '';
        if ($bookingId && in_array($newStatus, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);
            setFlashMessage('success', "Booking #{$bookingId} status updated to " . ucfirst($newStatus) . ".");
        }
        header("Location: bookings.php");
        exit;
    }

    // Quick Update Payment Status
    if ($action === 'update_payment') {
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $newPayment = $_POST['payment_status'] ?? '';
        if ($bookingId && in_array($newPayment, ['pending', 'paid', 'failed', 'refunded'], true)) {
            $stmt = $pdo->prepare("UPDATE bookings SET payment_status = :status, updated_at = NOW() WHERE id = :id");
            $stmt->execute(['status' => $newPayment, 'id' => $bookingId]);
            setFlashMessage('success', "Booking #{$bookingId} payment status updated to " . ucfirst($newPayment) . ".");
        }
        header("Location: bookings.php");
        exit;
    }

    // Delete Booking
    if ($action === 'delete_booking') {
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        if ($bookingId) {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute(['id' => $bookingId]);
            setFlashMessage('success', "Booking #{$bookingId} deleted successfully.");
        }
        header("Location: bookings.php");
        exit;
    }

    // Create Manual Booking
    if ($action === 'add_booking') {
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = trim($_POST['customer_phone'] ?? '');
        $custEmail = trim($_POST['customer_email'] ?? '');
        $modelId = filter_input(INPUT_POST, 'model_id', FILTER_VALIDATE_INT);
        $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
        $bookingDate = $_POST['booking_date'] ?? date('Y-m-d');
        $bookingTime = $_POST['booking_time'] ?? '10:00:00';
        $address = trim($_POST['address'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'confirmed';
        $paymentStatus = $_POST['payment_status'] ?? 'pending';
        $notes = trim($_POST['notes'] ?? '');

        if (!empty($custName) && !empty($custPhone) && $modelId && $serviceId) {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (
                    model_id, service_id, customer_name, customer_phone, customer_email,
                    booking_date, booking_time, address, amount, status, payment_status, notes,
                    created_at, updated_at
                ) VALUES (
                    :model_id, :service_id, :name, :phone, :email,
                    :bdate, :btime, :address, :amount, :status, :payment_status, :notes,
                    NOW(), NOW()
                )
            ");
            $stmt->execute([
                'model_id' => $modelId,
                'service_id' => $serviceId,
                'name' => $custName,
                'phone' => $custPhone,
                'email' => $custEmail,
                'bdate' => $bookingDate,
                'btime' => $bookingTime,
                'address' => $address,
                'amount' => $amount,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'notes' => $notes
            ]);
            setFlashMessage('success', 'New manual booking added successfully.');
        } else {
            setFlashMessage('error', 'Please fill in all required booking fields.');
        }
        header("Location: bookings.php");
        exit;
    }

    // Edit Existing Booking Details
    if ($action === 'edit_booking') {
        $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
        $custName = trim($_POST['customer_name'] ?? '');
        $custPhone = trim($_POST['customer_phone'] ?? '');
        $custEmail = trim($_POST['customer_email'] ?? '');
        $bookingDate = $_POST['booking_date'] ?? '';
        $bookingTime = $_POST['booking_time'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $paymentStatus = $_POST['payment_status'] ?? 'pending';
        $notes = trim($_POST['notes'] ?? '');

        if ($bookingId && !empty($custName) && !empty($custPhone)) {
            $stmt = $pdo->prepare("
                UPDATE bookings SET
                    customer_name = :name,
                    customer_phone = :phone,
                    customer_email = :email,
                    booking_date = :bdate,
                    booking_time = :btime,
                    address = :address,
                    amount = :amount,
                    status = :status,
                    payment_status = :payment_status,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'name' => $custName,
                'phone' => $custPhone,
                'email' => $custEmail,
                'bdate' => $bookingDate,
                'btime' => $bookingTime,
                'address' => $address,
                'amount' => $amount,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'notes' => $notes,
                'id' => $bookingId
            ]);
            setFlashMessage('success', "Booking #{$bookingId} updated successfully.");
        }
        header("Location: bookings.php");
        exit;
    }
}

// Filter parameter
$filterStatus = $_GET['status'] ?? 'all';
$filterPayment = $_GET['payment'] ?? 'all';

$sql = "
    SELECT 
        b.*,
        m.name AS model_name,
        br.name AS brand_name,
        vt.name AS vehicle_type_name,
        s.name AS service_name,
        s.duration AS service_duration
    FROM bookings b
    LEFT JOIN models m ON b.model_id = m.id
    LEFT JOIN brands br ON m.brand_id = br.id
    LEFT JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE 1=1
";
$params = [];

if ($filterStatus !== 'all' && in_array($filterStatus, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $sql .= " AND b.status = :status";
    $params['status'] = $filterStatus;
}

if ($filterPayment !== 'all' && in_array($filterPayment, ['pending', 'paid', 'failed', 'refunded'], true)) {
    $sql .= " AND b.payment_status = :payment";
    $params['payment'] = $filterPayment;
}

$sql .= " ORDER BY b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Fetch models and services for add modal
$allModels = $pdo->query("
    SELECT m.id, m.name, br.name as brand_name, vt.name as type_name
    FROM models m
    JOIN brands br ON m.brand_id = br.id
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    WHERE m.status = 1
    ORDER BY br.name ASC, m.name ASC
")->fetchAll();

$allServices = $pdo->query("SELECT id, name FROM services WHERE status = 1 ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-calendar-check mr-2 text-primary"></i> Service Bookings</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBookingModal">
                        <i class="fas fa-plus mr-1"></i> Add New Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Filter Badges / Navigation Tabs -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="btn-group btn-group-toggle my-1" data-toggle="buttons">
                            <a href="bookings.php" class="btn btn-outline-secondary btn-sm <?= ($filterStatus === 'all') ? 'active font-weight-bold' : '' ?>">All Bookings</a>
                            <a href="bookings.php?status=pending" class="btn btn-outline-warning btn-sm <?= ($filterStatus === 'pending') ? 'active font-weight-bold' : '' ?>">Pending</a>
                            <a href="bookings.php?status=confirmed" class="btn btn-outline-info btn-sm <?= ($filterStatus === 'confirmed') ? 'active font-weight-bold' : '' ?>">Confirmed</a>
                            <a href="bookings.php?status=completed" class="btn btn-outline-success btn-sm <?= ($filterStatus === 'completed') ? 'active font-weight-bold' : '' ?>">Completed</a>
                            <a href="bookings.php?status=cancelled" class="btn btn-outline-danger btn-sm <?= ($filterStatus === 'cancelled') ? 'active font-weight-bold' : '' ?>">Cancelled</a>
                        </div>

                        <div class="my-1">
                            <span class="text-muted mr-2 small font-weight-bold">Filter Payment:</span>
                            <a href="bookings.php?payment=paid" class="badge badge-success px-2 py-1 mr-1">Paid</a>
                            <a href="bookings.php?payment=pending" class="badge badge-warning px-2 py-1 mr-1">Pending</a>
                            <a href="bookings.php?payment=refunded" class="badge badge-secondary px-2 py-1 mr-1">Refunded</a>
                            <a href="bookings.php?payment=failed" class="badge badge-danger px-2 py-1">Failed</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings DataTable Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-table mr-1 text-secondary"></i> All Appointments (<?= count($bookings) ?> records)
                    </h3>
                </div>
                <div class="card-body">
                    <table id="bookingsTable" class="table table-bordered table-striped table-hover datatable-buttons">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer Details</th>
                                <th>Vehicle Info</th>
                                <th>Service Package</th>
                                <th>Schedule Date &amp; Time</th>
                                <th>Amount</th>
                                <th>Booking Status</th>
                                <th>Payment</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['customer_name']) ?></strong><br>
                                        <small class="text-muted"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($b['customer_phone']) ?></small>
                                        <?php if (!empty($b['customer_email'])): ?>
                                            <br><small class="text-muted"><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($b['customer_email']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">
                                            <?= htmlspecialchars(($b['brand_name'] ?? '') . ' ' . ($b['model_name'] ?? 'Vehicle')) ?>
                                        </span>
                                        <br><small class="text-secondary"><?= htmlspecialchars($b['vehicle_type_name'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['service_name'] ?? 'Custom Package') ?></strong>
                                    </td>
                                    <td>
                                        <i class="far fa-calendar-alt text-primary mr-1"></i> <?= date('d M Y', strtotime($b['booking_date'])) ?><br>
                                        <i class="far fa-clock text-secondary mr-1"></i> <small><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-success font-weight-bold"><?= formatCurrency($b['amount']) ?></strong>
                                    </td>
                                    <td>
                                        <!-- Quick Status Form Dropdown -->
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <select name="status" class="form-control form-control-sm border-0 font-weight-bold badge-status-<?= $b['status'] ?>" onchange="this.form.submit()">
                                                <option value="pending" <?= ($b['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                                <option value="confirmed" <?= ($b['status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                                                <option value="completed" <?= ($b['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= ($b['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <!-- Quick Payment Form Dropdown -->
                                        <form method="POST" style="display:inline-block;">
                                            <input type="hidden" name="action" value="update_payment">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <select name="payment_status" class="form-control form-control-sm border-0 font-weight-bold badge-payment-<?= $b['payment_status'] ?>" onchange="this.form.submit()">
                                                <option value="pending" <?= ($b['payment_status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                                                <option value="paid" <?= ($b['payment_status'] === 'paid') ? 'selected' : '' ?>>Paid</option>
                                                <option value="failed" <?= ($b['payment_status'] === 'failed') ? 'selected' : '' ?>>Failed</option>
                                                <option value="refunded" <?= ($b['payment_status'] === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-info" onclick='viewBooking(<?= json_encode($b) ?>)' title="View Full Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-warning text-white" onclick='editBooking(<?= json_encode($b) ?>)' title="Edit Booking">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display:inline-block;" onsubmit="return confirmAction(event, 'Are you sure you want to permanently delete Booking #<?= $b['id'] ?>?')">
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Booking">
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

<!-- Modal: View Booking Details -->
<div class="modal fade" id="viewBookingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold" id="viewModalTitle">Booking Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add New Manual Booking -->
<div class="modal fade" id="addBookingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_booking">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Add New Manual Booking</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Customer Phone *</label>
                            <input type="tel" name="customer_phone" class="form-control" placeholder="10-digit Phone" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Email</label>
                            <input type="email" name="customer_email" class="form-control" placeholder="Email Address">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Vehicle Model *</label>
                            <select name="model_id" class="form-control select2" required>
                                <option value="">-- Select Model --</option>
                                <?php foreach ($allModels as $m): ?>
                                    <option value="<?= $m['id'] ?>">
                                        <?= htmlspecialchars($m['brand_name'] . ' ' . $m['name'] . ' (' . $m['type_name'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Service Package *</label>
                            <select name="service_id" class="form-control" required>
                                <option value="">-- Select Service --</option>
                                <?php foreach ($allServices as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Amount (₹) *</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Booking Date *</label>
                            <input type="date" name="booking_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Booking Time *</label>
                            <input type="time" name="booking_time" class="form-control" value="10:00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pickup / Service Address *</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Full service location address" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Booking Status</label>
                            <select name="status" class="form-control">
                                <option value="confirmed" selected>Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" class="form-control">
                                <option value="pending" selected>Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes / Vehicle Issue Description</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Special requirements, noise, engine symptoms..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Booking Details -->
<div class="modal fade" id="editBookingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit_booking">
                <input type="hidden" name="booking_id" id="edit_booking_id">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Edit Booking <span id="edit_booking_title"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Name *</label>
                            <input type="text" name="customer_name" id="edit_customer_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Customer Phone *</label>
                            <input type="tel" name="customer_phone" id="edit_customer_phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Customer Email</label>
                            <input type="email" name="customer_email" id="edit_customer_email" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Amount (₹) *</label>
                            <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Booking Date *</label>
                            <input type="date" name="booking_date" id="edit_booking_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Booking Time *</label>
                            <input type="time" name="booking_time" id="edit_booking_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pickup / Service Address *</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Booking Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" id="edit_payment_status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes / Vehicle Issue Description</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
function viewBooking(b) {
    $('#viewModalTitle').html('<i class="fas fa-receipt mr-1"></i> Booking Summary #' + String(b.id).padStart(5, '0'));
    var html = `
        <div class="invoice p-3 mb-3 border-0">
            <div class="row">
                <div class="col-12">
                    <h4>
                        <i class="fas fa-car-side text-primary mr-2"></i> HR Auto Mobile Appointment
                        <small class="float-right text-muted">Created: ${b.created_at || 'N/A'}</small>
                    </h4>
                </div>
            </div>
            <div class="row invoice-info mt-3">
                <div class="col-sm-4 invoice-col">
                    <strong>Customer Information</strong>
                    <address>
                        <b>${b.customer_name}</b><br>
                        Phone: ${b.customer_phone}<br>
                        Email: ${b.customer_email || 'N/A'}<br>
                        Address: ${b.address}
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    <strong>Vehicle & Service</strong>
                    <address>
                        Type: <span class="badge badge-info">${b.vehicle_type_name || 'Vehicle'}</span><br>
                        Brand & Model: <b>${b.brand_name || ''} ${b.model_name || ''}</b><br>
                        Package: <b>${b.service_name || 'Custom Package'}</b><br>
                        Schedule: <b>${b.booking_date} at ${b.booking_time}</b>
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    <strong>Status & Payment</strong><br>
                    Booking Status: <span class="badge badge-status-${b.status}">${b.status.toUpperCase()}</span><br>
                    Payment Status: <span class="badge badge-payment-${b.payment_status}">${b.payment_status.toUpperCase()}</span><br>
                    Total Amount: <h4 class="text-success font-weight-bold mt-1">₹${parseFloat(b.amount).toFixed(2)}</h4>
                </div>
            </div>
            ${b.notes ? `
            <div class="row mt-2">
                <div class="col-12 alert alert-light border">
                    <strong>Customer Notes / Issues:</strong><br>
                    ${b.notes}
                </div>
            </div>` : ''}
        </div>
    `;
    $('#viewModalBody').html(html);
    $('#viewBookingModal').modal('show');
}

function editBooking(b) {
    $('#edit_booking_id').val(b.id);
    $('#edit_booking_title').text('#' + String(b.id).padStart(5, '0'));
    $('#edit_customer_name').val(b.customer_name);
    $('#edit_customer_phone').val(b.customer_phone);
    $('#edit_customer_email').val(b.customer_email);
    $('#edit_amount').val(b.amount);
    $('#edit_booking_date').val(b.booking_date);
    $('#edit_booking_time').val(b.booking_time);
    $('#edit_address').val(b.address);
    $('#edit_status').val(b.status);
    $('#edit_payment_status').val(b.payment_status);
    $('#edit_notes').val(b.notes);
    $('#editBookingModal').modal('show');
}
</script>
