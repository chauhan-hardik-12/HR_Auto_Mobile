<?php
require_once "includes/auth.php";
require_once "assets/config/db.php";

$userId = (int) $_SESSION['user_id'];
$modelId = filter_input(INPUT_GET, 'model_id', FILTER_VALIDATE_INT);
$serviceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_GET, 'amount', FILTER_VALIDATE_FLOAT);

if (!$modelId || !$serviceId) {
    header("Location: services.php");
    exit;
}

// Fetch user defaults
$userStmt = $pdo->prepare("SELECT id, name, phone, email FROM users WHERE id = :id LIMIT 1");
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

// Fetch summary names
$infoStmt = $pdo->prepare("
    SELECT m.name AS model_name, br.name AS brand_name, vt.name AS type_name, s.name AS service_name
    FROM models m
    JOIN brands br ON m.brand_id = br.id
    JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    JOIN services s ON s.id = :service_id
    WHERE m.id = :model_id LIMIT 1
");
$infoStmt->execute(['model_id' => $modelId, 'service_id' => $serviceId]);
$details = $infoStmt->fetch();

if (!$details) {
    header("Location: services.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $custName = trim($_POST['customer_name'] ?? $user['name']);
    $custPhone = trim($_POST['customer_phone'] ?? $user['phone']);
    $custEmail = trim($_POST['customer_email'] ?? $user['email']);
    $bookingDate = $_POST['booking_date'] ?? '';
    $bookingTime = $_POST['booking_time'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($custName) || empty($custPhone) || empty($bookingDate) || empty($bookingTime) || empty($address)) {
        $message = "Please complete all required fields.";
    } else {
        try {
            $insertSql = "
                INSERT INTO bookings (
                    user_id, model_id, service_id,
                    customer_name, customer_phone, customer_email,
                    booking_date, booking_time, address,
                    amount, status, payment_status, notes,
                    created_at, updated_at
                ) VALUES (
                    :user_id, :model_id, :service_id,
                    :customer_name, :customer_phone, :customer_email,
                    :booking_date, :booking_time, :address,
                    :amount, 'pending', 'pending', :notes,
                    NOW(), NOW()
                )
            ";

            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'user_id' => $userId,
                'model_id' => $modelId,
                'service_id' => $serviceId,
                'customer_name' => $custName,
                'customer_phone' => $custPhone,
                'customer_email' => $custEmail,
                'booking_date' => $bookingDate,
                'booking_time' => $bookingTime,
                'address' => $address,
                'amount' => $amount ?? 0.00,
                'notes' => $notes
            ]);

            header("Location: dashboard.php?booked=1");
            exit;
        } catch (PDOException $e) {
            $message = "Error creating booking: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - HR Auto Mobile</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 540px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .summary {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.25rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #334155;
        }

        input,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }

        .alert {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <div class="card">
        <h2 style="color: #2563eb; margin-bottom: 0.25rem;">Finalize Booking</h2>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem;">Review vehicle configuration and schedule
            your service.</p>

        <?php if (!empty($message)): ?>
            <div class="alert"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="summary">
            <p><strong>Vehicle:</strong> <?= htmlspecialchars($details['brand_name'] . ' ' . $details['model_name']) ?>
                (<?= htmlspecialchars($details['type_name']) ?>)</p>
            <p><strong>Package:</strong> <?= htmlspecialchars($details['service_name']) ?></p>
            <p style="margin-top: 0.4rem; font-size: 1.1rem; color: #1e40af; font-weight: 700;">Payable Amount:
                ₹<?= number_format((float) $amount, 2) ?></p>
        </div>

        <form method="POST">
            <div class="grid-2">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="customer_name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="customer_phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="customer_email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="booking_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label>Preferred Time</label>
                    <input type="time" name="booking_time" value="10:00" required>
                </div>
            </div>

            <div class="form-group">
                <label>Service Address / Workshop Drop-off</label>
                <textarea name="address" rows="2" placeholder="Enter complete pickup/service address"
                    required></textarea>
            </div>

            <div class="form-group">
                <label>Problem Notes / Special Instructions (Optional)</label>
                <textarea name="notes" rows="2" placeholder="Any specific issues with the vehicle?"></textarea>
            </div>

            <button type="submit" class="btn-submit">Confirm &amp; Place Appointment</button>
        </form>
    </div>

    <script>
    window.addEventListener('pageshow', function (event) {
        if (event.persisted || (window.performance && (window.performance.navigation && window.performance.navigation.type === 2))) {
            window.location.reload();
        }
    });
    </script>
</body>

</html>