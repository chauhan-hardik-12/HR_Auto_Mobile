<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Admin Dashboard - HR Auto Mobile');
}

$pendingBookingsBadge = getPendingBookingsCount($pdo);
$unreadMessagesBadge = getUnreadMessagesCount($pdo);
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= htmlspecialchars(PAGE_TITLE) ?></title>

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">

    <style>
        .brand-link {
            border-bottom: 1px solid #4b545c;
            background: #1f2d3d;
        }
        .main-sidebar {
            box-shadow: 0 14px 28px rgba(0,0,0,.25), 0 10px 10px rgba(0,0,0,.22);
        }
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: #007bff;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.24);
        }
        .badge-status-pending { background-color: #ffc107; color: #1f2d3d; }
        .badge-status-confirmed { background-color: #17a2b8; color: #fff; }
        .badge-status-completed { background-color: #28a745; color: #fff; }
        .badge-status-cancelled { background-color: #dc3545; color: #fff; }
        .badge-payment-paid { background-color: #28a745; color: #fff; }
        .badge-payment-pending { background-color: #ffc107; color: #1f2d3d; }
        .badge-payment-failed { background-color: #dc3545; color: #fff; }
        .badge-payment-refunded { background-color: #6c757d; color: #fff; }
        .small-box { border-radius: 8px; overflow: hidden; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link"><i class="fas fa-home mr-1"></i> Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../index.php" target="_blank" class="nav-link text-primary font-weight-bold">
                    <i class="fas fa-external-link-alt mr-1"></i> View Website
                </a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Pending Bookings Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-calendar-check"></i>
                    <?php if ($pendingBookingsBadge > 0): ?>
                        <span class="badge badge-warning navbar-badge font-weight-bold"><?= $pendingBookingsBadge ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header font-weight-bold"><?= $pendingBookingsBadge ?> Pending Bookings</span>
                    <div class="dropdown-divider"></div>
                    <a href="bookings.php?status=pending" class="dropdown-item">
                        <i class="fas fa-clock mr-2 text-warning"></i> View pending bookings
                        <span class="float-right text-muted text-sm"><?= $pendingBookingsBadge ?> items</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="bookings.php" class="dropdown-item dropdown-footer">See All Bookings</a>
                </div>
            </li>

            <!-- Unread Messages Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-envelope"></i>
                    <?php if ($unreadMessagesBadge > 0): ?>
                        <span class="badge badge-danger navbar-badge font-weight-bold"><?= $unreadMessagesBadge ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header font-weight-bold"><?= $unreadMessagesBadge ?> Unread Inquiries</span>
                    <div class="dropdown-divider"></div>
                    <a href="messages.php?status=unread" class="dropdown-item">
                        <i class="fas fa-inbox mr-2 text-danger"></i> View unread messages
                        <span class="float-right text-muted text-sm"><?= $unreadMessagesBadge ?> new</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="messages.php" class="dropdown-item dropdown-footer">See All Inquiries</a>
                </div>
            </li>

            <!-- Admin User Menu -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="dist/img/admin.jpeg" class="user-image img-circle elevation-2" alt="User Image">
                    <span class="d-none d-md-inline"><?= htmlspecialchars($currentAdmin['name']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <!-- User image -->
                    <li class="user-header bg-primary">
                        <img src="dist/img/admin.jpeg" class="img-circle elevation-2" alt="User Image">
                        <p>
                            <?= htmlspecialchars($currentAdmin['name']) ?> - <?= ucfirst($currentAdmin['role']) ?>
                            <small><?= htmlspecialchars($currentAdmin['email']) ?></small>
                        </p>
                    </li>
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <a href="profile.php" class="btn btn-default btn-flat"><i class="fas fa-user-cog mr-1"></i> Profile</a>
                        <a href="logout.php" class="btn btn-default btn-flat float-right text-danger"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->
