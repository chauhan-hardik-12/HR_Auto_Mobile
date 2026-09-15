<?php
define('PAGE_TITLE', 'Dashboard - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 1. Fetch Dashboard KPI Metrics
$totalBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmedBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$completedBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$cancelledBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

$totalRevenue = (float) $pdo->query("
    SELECT COALESCE(SUM(amount), 0) 
    FROM bookings 
    WHERE status = 'completed' OR payment_status = 'paid'
")->fetchColumn();

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBrands = (int) $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$totalModels = (int) $pdo->query("SELECT COUNT(*) FROM models")->fetchColumn();
$totalServices = (int) $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();

// 2. Fetch Recent Bookings
$recentBookingsStmt = $pdo->query("
    SELECT 
        b.id, b.customer_name, b.customer_phone, b.customer_email,
        b.booking_date, b.booking_time, b.amount, b.status, b.payment_status,
        m.name AS model_name, br.name AS brand_name, s.name AS service_name, vt.name AS vehicle_type_name
    FROM bookings b
    LEFT JOIN models m ON b.model_id = m.id
    LEFT JOIN brands br ON m.brand_id = br.id
    LEFT JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    LEFT JOIN services s ON b.service_id = s.id
    ORDER BY b.id DESC
    LIMIT 7
");
$recentBookings = $recentBookingsStmt->fetchAll();

// 3. Fetch Recent Messages
$recentMessages = $pdo->query("
    SELECT id, name, email, phone, message, status, created_at
    FROM contact_messages
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

// 4. Vehicle Type Booking Stats
$vehicleStats = $pdo->query("
    SELECT COALESCE(vt.name, 'Other') AS type_name, COUNT(b.id) AS total_count
    FROM bookings b
    LEFT JOIN models m ON b.model_id = m.id
    LEFT JOIN brands br ON m.brand_id = br.id
    LEFT JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
    GROUP BY vt.name
")->fetchAll();

$vtLabels = [];
$vtCounts = [];
foreach ($vehicleStats as $vs) {
    $vtLabels[] = $vs['type_name'];
    $vtCounts[] = (int) $vs['total_count'];
}

// 5. Monthly Booking Trend (Last 6 distinct months or recent days)
$monthlyStats = $pdo->query("
    SELECT 
        DATE_FORMAT(booking_date, '%b %Y') AS month_label,
        COUNT(id) AS booking_count,
        COALESCE(SUM(amount), 0) AS monthly_revenue
    FROM bookings
    GROUP BY DATE_FORMAT(booking_date, '%Y-%m'), month_label
    ORDER BY MIN(booking_date) ASC
    LIMIT 6
")->fetchAll();

$monthLabels = [];
$monthBookings = [];
$monthRevenues = [];

if (empty($monthlyStats)) {
    $monthLabels = [date('M Y')];
    $monthBookings = [$totalBookings];
    $monthRevenues = [$totalRevenue];
} else {
    foreach ($monthlyStats as $ms) {
        $monthLabels[] = $ms['month_label'];
        $monthBookings[] = (int) $ms['booking_count'];
        $monthRevenues[] = (float) $ms['monthly_revenue'];
    }
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-tachometer-alt mr-2 text-primary"></i> Admin Dashboard
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Small boxes (Stat box) -->
            <div class="row">
                <!-- Total Bookings -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info elevation-2">
                        <div class="inner">
                            <h3><?= $totalBookings ?></h3>
                            <p>Total Bookings</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <a href="bookings.php" class="small-box-footer">View Bookings <i
                                class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <!-- Pending Bookings -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning elevation-2">
                        <div class="inner">
                            <h3><?= $pendingBookings ?></h3>
                            <p>Pending Actions</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="bookings.php?status=pending" class="small-box-footer">Review Pending <i
                                class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success elevation-2">
                        <div class="inner">
                            <h3><?= formatCurrency($totalRevenue) ?></h3>
                            <p>Completed Revenue</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <a href="bookings.php?status=completed" class="small-box-footer">Financial Summary <i
                                class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>

                <!-- Registered Users -->
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger elevation-2">
                        <div class="inner">
                            <h3><?= $totalUsers ?></h3>
                            <p>Registered Customers</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="users.php" class="small-box-footer">Customer Directory <i
                                class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
            <!-- /.row -->

            <!-- Quick Catalog Stats Row -->
            <div class="row mb-3">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-primary"><i class="fas fa-car"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted">Vehicle Brands</span>
                            <span class="info-box-number font-weight-bold"><?= $totalBrands ?> Brands</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-info"><i class="fas fa-motorcycle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted">Vehicle Models</span>
                            <span class="info-box-number font-weight-bold"><?= $totalModels ?> Models</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-tools"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted">Active Services</span>
                            <span class="info-box-number font-weight-bold"><?= $totalServices ?> Packages</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-warning"><i class="far fa-envelope"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text text-muted">Contact Inquiries</span>
                            <span class="info-box-number font-weight-bold"><?= count($recentMessages) ?>
                                Inquiries</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table & Contact Inquiries Row -->
        <div class="row">
            <!-- Recent Bookings -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header border-transparent">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-list-alt mr-1 text-primary"></i> Latest
                            Bookings</h3>
                        <div class="card-tools">
                            <a href="bookings.php" class="btn btn-sm btn-primary">View All Bookings</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover m-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Service</th>
                                        <th>Date &amp; Time</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentBookings)): ?>
                                        <?php foreach ($recentBookings as $b): ?>
                                            <tr>
                                                <td><strong>#<?= str_pad($b['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($b['customer_name']) ?></strong><br>
                                                    <small class="text-muted"><i
                                                            class="fas fa-phone mr-1"></i><?= htmlspecialchars($b['customer_phone']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light border">
                                                        <?= htmlspecialchars(($b['brand_name'] ?? '') . ' ' . ($b['model_name'] ?? 'Vehicle')) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($b['service_name'] ?? 'Service Package') ?></td>
                                                <td>
                                                    <?= date('d M Y', strtotime($b['booking_date'])) ?><br>
                                                    <small
                                                        class="text-muted"><?= date('h:i A', strtotime($b['booking_time'])) ?></small>
                                                </td>
                                                <td><strong class="text-success"><?= formatCurrency($b['amount']) ?></strong>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-status-<?= htmlspecialchars($b['status']) ?> px-2 py-1">
                                                        <?= ucfirst($b['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="bookings.php?highlight=<?= $b['id'] ?>"
                                                        class="btn btn-xs btn-outline-primary" title="Manage Booking">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No bookings found yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Contact Inquiries -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header border-transparent">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-envelope mr-1 text-danger"></i> Recent
                            Messages</h3>
                        <div class="card-tools">
                            <a href="messages.php" class="btn btn-sm btn-outline-secondary">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="products-list product-list-in-card pl-2 pr-2">
                            <?php if (!empty($recentMessages)): ?>
                                <?php foreach ($recentMessages as $msg): ?>
                                    <li class="item py-2 px-2 border-bottom">
                                        <div>
                                            <a href="messages.php" class="product-title font-weight-bold text-dark">
                                                <?= htmlspecialchars($msg['name']) ?>
                                                <?php if (($msg['status'] ?? 'unread') === 'unread'): ?>
                                                    <span class="badge badge-danger float-right">New</span>
                                                <?php endif; ?>
                                            </a>
                                            <span class="product-description text-muted small">
                                                <?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 75, "...")) ?>
                                            </span>
                                            <div class="text-xs text-secondary mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                <?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?>
                                                <span class="mx-1">|</span>
                                                <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="text-primary"><i
                                                        class="fas fa-reply mr-1"></i> Reply</a>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="item text-center text-muted py-4">No messages received yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    $(function () {
        // 1. Monthly Trend Bar/Line Chart
        var trendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [
                    {
                        label: 'Bookings Count',
                        backgroundColor: 'rgba(60,141,188,0.85)',
                        borderColor: 'rgba(60,141,188,1)',
                        data: <?= json_encode($monthBookings) ?>,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenue (₹)',
                        backgroundColor: 'rgba(40,167,69,0.7)',
                        borderColor: 'rgba(40,167,69,1)',
                        type: 'line',
                        fill: false,
                        data: <?= json_encode($monthRevenues) ?>,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: { beginAtZero: true, stepSize: 1 }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function (value) { return '₹' + value; }
                        }
                    }
                }
            }
        });

        // 2. Status Donut Chart
        var donutCtx = document.getElementById('statusDonutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
                datasets: [{
                    data: [
                        <?= $pendingBookings ?>,
                        <?= $confirmedBookings ?>,
                        <?= $completedBookings ?>,
                        <?= $cancelledBookings ?>
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: {
                    position: 'bottom'
                }
            }
        });
    });
</script>