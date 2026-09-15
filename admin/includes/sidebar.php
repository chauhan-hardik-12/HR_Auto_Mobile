<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$isCatalogActive = in_array($currentPage, ['vehicle_types.php', 'brands.php', 'models.php'], true);
$isServicesActive = in_array($currentPage, ['services.php', 'model_pricing.php'], true);
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
        <img src="dist/img/AdminLTELogo.png" alt="HR Auto Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-bold">HR Auto Mobile</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="dist/img/admin.jpeg" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="profile.php" class="d-block"><?= htmlspecialchars($currentAdmin['name']) ?></a>
                <small class="text-muted"><i class="fas fa-circle text-success" style="font-size: 8px;"></i> <?= ucfirst($currentAdmin['role']) ?></small>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="bookings.php" class="nav-link <?= ($currentPage === 'bookings.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>
                            Service Bookings
                            <?php if ($pendingBookingsBadge > 0): ?>
                                <span class="badge badge-warning right"><?= $pendingBookingsBadge ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>

                <!-- Vehicle Catalog Treeview -->
                <li class="nav-item has-treeview <?= $isCatalogActive ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $isCatalogActive ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-car"></i>
                        <p>
                            Vehicle Catalog
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="vehicle_types.php" class="nav-link <?= ($currentPage === 'vehicle_types.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Vehicle Types</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="brands.php" class="nav-link <?= ($currentPage === 'brands.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Brands</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="models.php" class="nav-link <?= ($currentPage === 'models.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Vehicle Models</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Services & Packages Treeview -->
                <li class="nav-item has-treeview <?= $isServicesActive ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $isServicesActive ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tools"></i>
                        <p>
                            Services & Pricing
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="services.php" class="nav-link <?= ($currentPage === 'services.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Service Packages</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="model_pricing.php" class="nav-link <?= ($currentPage === 'model_pricing.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Model Pricing</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="users.php" class="nav-link <?= ($currentPage === 'users.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Customer Users</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="messages.php" class="nav-link <?= ($currentPage === 'messages.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Contact Inquiries
                            <?php if ($unreadMessagesBadge > 0): ?>
                                <span class="badge badge-danger right"><?= $unreadMessagesBadge ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>

                <li class="nav-header">CONTENT &amp; CMS</li>

                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?= ($currentPage === 'settings.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-sliders-h"></i>
                        <p>Site Settings</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="partners.php" class="nav-link <?= ($currentPage === 'partners.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>Team &amp; Partners</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="testimonials.php" class="nav-link <?= ($currentPage === 'testimonials.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-star"></i>
                        <p>Testimonials</p>
                    </a>
                </li>

                <li class="nav-header">SYSTEM</li>

                <?php if (($currentAdmin['role'] ?? '') === 'superadmin'): ?>
                <li class="nav-item">
                    <a href="admins.php" class="nav-link <?= ($currentPage === 'admins.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>Admin Accounts</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="profile.php" class="nav-link <?= ($currentPage === 'profile.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-cog"></i>
                        <p>My Profile</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="../index.php" target="_blank" class="nav-link text-info">
                        <i class="nav-icon fas fa-globe"></i>
                        <p>View Main Website</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
