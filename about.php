<?php
require_once "includes/settings_loader.php";

// Fetch customer testimonials from DB
$aboutTestimonials = [];
try {
    $stmt = $pdo->query("SELECT customer_name, customer_city, vehicle_name, comment, rating FROM testimonials WHERE status = 1 ORDER BY id DESC LIMIT 3");
    $aboutTestimonials = $stmt->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?= siteSetting('site_name', 'HR Auto Mobile') ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include("includes/header.php"); ?>

    <!-- Dynamic About Banner -->
    <section class="about">
        <h2><?= siteSetting('about_badge', 'About Us') ?></h2>
        <h1><?= siteSetting('about_title', 'Making Vehicle Service Hassle-Free') ?></h1>
        <p><?= siteSetting('about_subtitle', 'Bike and car repair across 32+ Indian cities — trusted by 20,000+ customers') ?></p>
        <div class="about-content container">
            <div class="about-box">
                <h3>30-Day</h3>
                <p>Service Warranty</p>
            </div>
            <div class="about-box">
                <h3><?= siteSetting('stat2_num', '20,000+') ?></h3>
                <p><?= siteSetting('stat2_label', 'Happy Customers') ?></p>
            </div>
            <div class="about-box">
                <h3><?= siteSetting('stat1_num', '15 Lakhs+') ?></h3>
                <p><?= siteSetting('stat1_label', 'Vehicles Serviced') ?></p>
            </div>
            <div class="about-box">
                <h3><?= siteSetting('stat4_num', '4.8 / 5') ?></h3>
                <p><?= siteSetting('stat4_label', 'Average Rating') ?></p>
            </div>
        </div>
    </section>

    <!-- Dynamic Our Story Section -->
    <section class="our-story">
        <h2>Our Story</h2>
        <h1><?= siteSetting('about_story_title', 'Why We Built HR Auto Mobile') ?></h1>
        <div class="our-content container">
            <p><?= nl2br(siteSetting('about_story_p1', 'At HR Auto Mobile, we recognized the challenges vehicle owners face when searching for reliable and affordable car and bike services. Many customers struggle to find trustworthy workshops, transparent pricing, and convenient booking options.')) ?></p>
            <p><?= nl2br(siteSetting('about_story_p2', 'Our vision is to simplify the automotive service experience through technology. Instead of spending hours searching for service centers or comparing options, customers can quickly find the right services, book appointments, and manage their vehicle maintenance needs from a single platform.')) ?></p>
        </div>
    </section>

    <!-- Dynamic Our Journey Section -->
    <section class="our-journey">
        <h2>Our Journey</h2>
        <h1>From Startup to <?= siteSetting('stat2_num', '20,000+') ?> Customers &amp; Partners</h1>
        <div class="journey-content container">
            <div class="journey-box">
                <div class="icon">🚀</div>
                <h3>Founded</h3>
                <p><?= siteSetting('site_name', 'HR Auto Mobile') ?> was founded with a vision to revolutionize the automotive service industry by providing a digital platform that connects customers with trusted service providers.</p>
            </div>
            <div class="journey-box">
                <div class="icon">🌍</div>
                <h3>Expansion to <?= siteSetting('stat3_num', '32+ Cities') ?></h3>
                <p>Expanded our certified doorstep and workshop network across major hubs in Gujarat and nationwide.</p>
            </div>
            <div class="journey-box">
                <div class="icon">👥</div>
                <h3><?= siteSetting('stat2_num', '20,000+') ?> Customers</h3>
                <p>We reached a significant milestone of servicing over <?= siteSetting('stat2_num', '20,000+') ?> happy vehicle owners with genuine parts and upfront pricing.</p>
            </div>
            <div class="journey-box">
                <div class="icon">🤝</div>
                <h3>Trusted by <?= siteSetting('stat5_num', '500+') ?> Service Providers</h3>
                <p>Built partnerships with certified service stations and technicians ensuring top quality automotive care.</p>
            </div>
        </div>
    </section>

    <!-- Our Values section -->
    <section class="values container">
        <h2>Our Values</h2>
        <h1>What Makes Us Different</h1>
        <div class="values-content">
            <div class="values-box">
                <h3>Customer-Centric</h3>
                <p>We prioritize our customers' needs and satisfaction above all else, ensuring a seamless and positive experience.</p>
            </div>
            <div class="values-box">
                <h3>Full Transparency</h3>
                <p>We believe in providing clear and honest information about our services, pricing, and certified parts to build lifelong trust.</p>
            </div>
            <div class="values-box">
                <h3>Innovation</h3>
                <p>We continuously innovate our digital platform to make booking, scheduling, and billing effortless for every driver and rider.</p>
            </div>
            <div class="values-box">
                <h3>Quality Service</h3>
                <p>We are committed to connecting customers with verified workshops who deliver high-grade automotive care.</p>
            </div>
        </div>
    </section>

    <!-- Dynamic Customer Testimonials section -->
    <?php if (!empty($aboutTestimonials)): ?>
    <section class="trust">
        <h2>Trust &amp; Reputation</h2>
        <h1>What Customers Say</h1>
        <div class="trust-content container">
            <?php foreach ($aboutTestimonials as $at): ?>
                <div class="trust-box">
                    <p>"<?= htmlspecialchars($at['comment']) ?>"</p>
                    <h3>- <?= htmlspecialchars($at['customer_name']) ?> <small style="font-weight: normal; font-size: 0.85rem; color: #cbd5e1;">(<?= htmlspecialchars($at['vehicle_name']) ?>)</small></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php include("includes/footer.php"); ?>
</body>

</html>