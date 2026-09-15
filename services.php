<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['is_authenticated']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - HR Auto Mobile</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include("includes/header.php"); ?>
    <section class="services">
        <h2>Services</h2>
        <h1>Professional Car &amp; Bike Services</h1>
        <p>Browse our verified vehicle service packages and book directly online.</p>
    </section>
<!-- container-booking -->
    <section class="choose-service">
        <h2>Choose Your Vehicle</h2>
        <div class="vehicle-types" id="vehicleTypes">
            <div class="vehicle-card loading">Loading vehicle types...</div>
        </div>
        <select id="vehicle_type" name="vehicle_type" hidden></select>

        <div class="booking-form">
            <div class="form-group">
                <label for="brand_id">Brand</label>
                <select id="brand_id" name="brand_id" disabled>
                    <option value="">Select vehicle type first</option>
                </select>
            </div>
            <div class="form-group">
                <label for="model_id">Model</label>
                <select id="model_id" name="model_id" disabled>
                    <option value="">Select brand first</option>
                </select>
            </div>
            <button type="button" id="bookBtn" class="book-btn" disabled>Book Selected Service</button>
        </div>
    </section>

    <section class="services-selection-section container-booking" id="servicesSection" style="display: none;">
        <div class="section-title">
            <h2>Available Packages</h2>
            <p>Select your preferred package below</p>
        </div>
        <div class="services-container" id="servicesContainer"></div>
    </section>

    <!-- Auth Modal Popup -->
    <div class="auth-modal-overlay" id="authModal">
        <div class="auth-modal-box">
            <button type="button" class="modal-close-btn" id="closeAuthModal">&times;</button>
            <div class="modal-tabs">
                <button type="button" class="modal-tab-btn active" id="tabLoginBtn">Sign In</button>
                <button type="button" class="modal-tab-btn" id="tabRegisterBtn">Register</button>
            </div>

            <form id="quickLoginForm" class="modal-form">
                <div class="form-group">
                    <label for="login_phone">Phone Number or Email</label>
                    <input type="text" id="login_phone" required placeholder="Mobile / Email">
                </div>
                <div class="form-group">
                    <label for="login_pass">Password</label>
                    <input type="password" id="login_pass" required placeholder="Password">
                </div>
                <button type="submit" class="btn-modal-submit">Sign In &amp; Continue</button>
            </form>

            <form id="quickRegisterForm" class="modal-form" style="display: none;">
                <div class="form-group">
                    <label for="reg_name">Full Name</label>
                    <input type="text" id="reg_name" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label for="reg_phone">Phone Number</label>
                    <input type="tel" id="reg_phone" required placeholder="10-digit number">
                </div>
                <div class="form-group">
                    <label for="reg_email">Email Address</label>
                    <input type="email" id="reg_email" required placeholder="name@example.com">
                </div>
                <div class="form-group">
                    <label for="reg_pass">Password</label>
                    <input type="password" id="reg_pass" required placeholder="Password">
                </div>
                <button type="submit" class="btn-modal-submit">Create Account &amp; Continue</button>
            </form>
        </div>
    </div>

    <!-- Pass Authentication state from PHP to JavaScript -->
    <script>
        window.IS_LOGGED_IN = <?= json_encode($isLoggedIn) ?>;
    </script>
    <script src="assets/js/book.js"></script>
</body>

</html>