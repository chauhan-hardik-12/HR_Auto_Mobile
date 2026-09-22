<?php
require_once __DIR__ . '/settings_loader.php';
?>
<!-- Dynamic Site Header -->
<header class="navbar">
    <a href="/Hr_Auto_Mobile/index.php" class="logo"><?= siteSetting('logo_text', 'HR Auto Mobile') ?>
        <span>.</span></a>
    <div class="menu">
        <a href="/Hr_Auto_Mobile/index.php" class="nav-link">Home</a>
        <a href="/Hr_Auto_Mobile/about.php" class="nav-link">About</a>
        <a href="/Hr_Auto_Mobile/services.php" class="nav-link">Services</a>
        <a href="/Hr_Auto_Mobile/contact.php" class="nav-link">Contact</a>
        <div class="user">
            <a href="/Hr_Auto_Mobile/dashboard.php" title="Customer Dashboard"><i class='bx bx-user'></i></a>
        </div>
    </div>
    <div class="menu-icon">
        <div class="line1"></div>
        <div class="line2"></div>
        <div class="line3"></div>
    </div>
</header>
<script src="assets/js/script.js"></script>