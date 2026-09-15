<?php
require_once "includes/settings_loader.php";
$is_logged_in = !empty($_SESSION['is_authenticated']) && !empty($_SESSION['user_id']) ? 'true' : 'false';

// Fetch Dynamic Partners (from DB)
$partners = [];
try {
    $pStmt = $pdo->query("SELECT name, role, image FROM partners WHERE status = 1 ORDER BY display_order ASC, id ASC");
    $partners = $pStmt->fetchAll();
} catch (Exception $e) {
}

// Fetch Dynamic Testimonials (from DB)
$testimonials = [];
try {
    $tStmt = $pdo->query("SELECT customer_name, customer_city, vehicle_name, rating, comment FROM testimonials WHERE status = 1 ORDER BY id DESC LIMIT 3");
    $testimonials = $tStmt->fetchAll();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= siteSetting('site_name', 'HR Auto Mobile') ?> -
        <?= siteSetting('site_tagline', 'Professional Bikes & Cars Services') ?>
    </title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include("includes/header.php"); ?>

    <!-- Dynamic Hero section -->
    <section class="home">
        <div class="home-content">
            <img src="assets/images/background.jpg" alt="Background Image" class="bg-image">
        </div>
        <div class="bar">
            <div>
                <div class="bar-title"><?= siteSetting('hero_title', 'Bikes & Cars Services') ?></div>
            </div>
            <a href="<?= siteSetting('hero_btn_link', 'services.php') ?>"
                class="bar-btn"><?= siteSetting('hero_btn_text', 'Book Now') ?></a>
        </div>
    </section>

    <!-- How it works section -->
    <section class="how-it-work container" id="how-it-work">
        <div class="heading">
            <h2>How It Works</h2>
        </div>
        <div class="how-content">
            <div class="how-box">
                <h3>Book a Service</h3>
                <p>Schedule a car or bike service appointment quickly and easily.</p>
            </div>
            <div class="how-box">
                <h3>Vehicle Inspection</h3>
                <p>Our experts inspect your vehicle and identify any issues.</p>
            </div>
            <div class="how-box">
                <h3>Service & Repair</h3>
                <p>We perform maintenance and repairs using quality parts and equipment.</p>
            </div>
            <div class="how-box">
                <h3>Ready to Ride</h3>
                <p>Collect your serviced vehicle and enjoy a safe, smooth driving experience.</p>
            </div>
        </div>
    </section>
    <!-- Popular Services section -->
    <section class="popular-service container" id="popular-service">
        <div class="heading">
            <h2>Popular Services</h2>
        </div>
        <div class="service-content">
            <div class="service-box">
                <img src="assets/images/services/bike.png" alt="Bike Services">
                <h3>Bikes Services</h3>
                <p>Comprehensive mainte nance and repair services for your bike.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/car.png" alt="Car Services">
                <h3>Cars Services</h3>
                <p>Comprehensive maintenance and repair services for your car.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/tires.png" alt="Tire Service">
                <h3>Tire Service</h3>
                <p>Get tire rotation, balancing, and replacement for optimal performance.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/battery.png" alt="Battery Replacement">
                <h3>Battery</h3>
                <p>Replace your car or bike battery to avoid unexpected breakdowns.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/ac.png" alt="Battery Replacement">
                <h3>Car AC</h3>
                <p>Service and repair your car's air conditioning system for optimal cooling.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/brake-disc.png" alt="Brake Disc Replacement">
                <h3>Brake Disc</h3>
                <p>Replace your car or bike brake discs to ensure safe stopping performance.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/car-engine.png" alt="Engine">
                <h3>Car Engine</h3>
                <p>Comprehensive engine maintenance and repair services for your car.</p>
            </div>
            <div class="service-box">
                <img src="assets/images/services/oil.png" alt="Oil Change">
                <h3>Oil Change</h3>
                <p>Regular oil changes to keep your engine running smoothly.</p>
            </div>
        </div>
        <div class="hero-btn">
            <a href="services.php"><button>View All Services</button></a>
        </div>
    </section>
    <!-- Dynamic Brands section -->
    <section class="brands container" id="brands">
        <div class="heading">
            <h2>Popular Brands</h2>
        </div>
        <div class="brand-content">
            <div class="brand-box">
                <img src="assets/images/brands/audi.png" alt="">
                <p>Audi</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/bentley.png" alt="">
                <p>Bentley</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/bmw.png" alt="">
                <p>BMW</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/bugatti.png" alt="">
                <p>Bugatti</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/byd.png" alt="">
                <p>BYD</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/dodge.png" alt="">
                <p>Dodge</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/ferrari.png" alt="">
                <p>Ferrari</p>
            </div>
            <div class="brand-box">
                <img src="assets/images/brands/fiat.png" alt="">
                <p>Fiat</p>
            </div>
        </div>
        </div>
        <div class="hero-btn">
            <a href="brands.php"><button>View All Brands</button></a>
        </div>
    </section>
    <!-- Why Choose Us section -->
    <section class="why-choose-us container" id="why-choose-us">
        <div class="heading">
            <h2>Why Choose Us</h2>
        </div>
        <div class="choose-content">
            <div class="choose-box">
                <h3>Trusted Professionals</h3>
                <p>All our service providers are verified and highly rated by customers.</p>
            </div>
            <div class="choose-box">
                <h3>Convenient Booking</h3>
                <p>Book your service at your convenience with our easy-to-use platform.</p>
            </div>
            <div class="choose-box">
                <h3>Competitive Pricing</h3>
                <p>Compare prices and choose the best option for your budget.</p>
            </div>
            <div class="choose-box">
                <h3>Customer Satisfaction</h3>
                <p>We prioritize customer satisfaction and strive to provide the best service experience.</p>
            </div>
        </div>
    </section>

    <!-- Dynamic Impact Statistics section -->
    <section class="statistics container" id="statistics">
        <div class="heading">
            <h2>Our Impact in Numbers</h2>
        </div>
        <div class="statistics-content">
            <div class="stat-box">
                <h3><?= siteSetting('stat1_num', '15 Lakhs+') ?></h3>
                <p><?= siteSetting('stat1_label', 'Vehicles Serviced') ?></p>
            </div>
            <div class="stat-box">
                <h3><?= siteSetting('stat2_num', '20,000+') ?></h3>
                <p><?= siteSetting('stat2_label', 'Happy Customers') ?></p>
            </div>
            <div class="stat-box">
                <h3><?= siteSetting('stat3_num', '32+ Cities') ?></h3>
                <p><?= siteSetting('stat3_label', 'Across India') ?></p>
            </div>
            <div class="stat-box">
                <h3><?= siteSetting('stat4_num', '4.8 / 5') ?></h3>
                <p><?= siteSetting('stat4_label', 'Average Rating') ?></p>
            </div>
        </div>
    </section>

    <!-- Dynamic Partners section -->
    <section class="partners container" id="partners">
        <div class="heading">
            <h2>Our Partners &amp; Team</h2>
        </div>
        <div class="partners-content">
            <?php if (!empty($partners)): ?>
                <?php foreach ($partners as $p): ?>
                    <div class="partner-box">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                            onerror="this.src='assets/images/partner-1.jpg'">
                        <h2><?= htmlspecialchars($p['name']) ?></h2>
                        <span><?= htmlspecialchars($p['role']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #64748b;">Partners information coming soon.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Dynamic Customer Testimonials Section -->
    <?php if (!empty($testimonials)): ?>
        <section class="why-choose-us container" id="testimonials"
            style="background: #f8fafc; border-radius: 16px; padding: 40px 20px; margin-top: 40px;">
            <div class="heading">
                <h2>What Our Customers Say</h2>
            </div>
            <div class="choose-content">
                <?php foreach ($testimonials as $t): ?>
                    <div class="choose-box"
                        style="background: #fff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 25px; border-radius: 12px;">
                        <div style="color: #f59e0b; margin-bottom: 8px;">
                            <?php for ($i = 0; $i < (int) $t['rating']; $i++): ?>★<?php endfor; ?>
                        </div>
                        <p style="font-style: italic; color: #334155; margin-bottom: 15px;">
                            "<?= htmlspecialchars($t['comment']) ?>"</p>
                        <h3 style="font-size: 1rem; margin-bottom: 2px; color: #1e293b;">
                            <?= htmlspecialchars($t['customer_name']) ?>
                        </h3>
                        <small
                            style="color: #64748b;"><?= htmlspecialchars($t['vehicle_name']) ?><?= $t['customer_city'] ? ' • ' . htmlspecialchars($t['customer_city']) : '' ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Auth Modal (Hidden by default) -->
    <div id="authModal" class="auth-modal-backdrop" style="display: none;">
        <div class="auth-modal-box">
            <button type="button" class="auth-close-btn" id="closeAuthModal">&times;</button>

            <div class="auth-toggle-tabs">
                <button class="auth-tab-btn active" id="tabLoginBtn" type="button">Login</button>
                <button class="auth-tab-btn" id="tabRegisterBtn" type="button">Register</button>
            </div>

            <div id="authErrorBox" class="auth-error" style="display: none;"></div>

            <!-- Login Form -->
            <form id="quickLoginForm" class="auth-form">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" id="login_phone" required placeholder="10-digit phone number">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="login_pass" required placeholder="Enter password">
                </div>
                <button type="submit" class="auth-submit-btn">Login &amp; Proceed</button>
            </form>

            <!-- Register Form -->
            <form id="quickRegisterForm" class="auth-form" style="display: none;">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="reg_name" required placeholder="e.g. John Doe">
                </div>
                <div class="form-group">
                    <label>Mobile Number *</label>
                    <input type="tel" id="reg_phone" required placeholder="10-digit phone number">
                </div>
                <div class="form-group">
                    <label>Email (Optional)</label>
                    <input type="email" id="reg_email" placeholder="name@example.com">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" id="reg_pass" required placeholder="Create a password">
                </div>
                <button type="submit" class="auth-submit-btn">Register &amp; Proceed</button>
            </form>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>

    <!-- Booking Engine & Auth Logic -->
    <script src="assets/js/book.js"></script>
</body>

</html>