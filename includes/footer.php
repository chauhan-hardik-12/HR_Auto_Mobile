<?php
require_once __DIR__ . '/settings_loader.php';

// Fetch dynamic active services for footer links
$footerServices = [];
try {
    $footerServices = $pdo->query("SELECT id, name FROM services WHERE status = 1 ORDER BY id ASC LIMIT 6")->fetchAll();
} catch (Exception $e) {
}
?>
<!-- Dynamic Call to Action Section -->
<section class="call-to-action container" id="call-to-action">
    <div class="heading">
        <h2><?= siteSetting('cta_title', 'Ready to Experience Hassle-Free Maintenance?') ?></h2>
    </div>
    <p><?= siteSetting('cta_desc', 'Book your next car or bike service with HR Auto Mobile and enjoy a hassle-free experience!') ?>
    </p>
    <a href="/Hr_Auto_Mobile/<?= siteSetting('cta_btn_link', 'services.php') ?>">
        <button class="cta-btn"><?= siteSetting('cta_btn_text', 'Book Now') ?></button>
    </a>
</section>

<!-- Dynamic Footer Section -->
<footer class="footer">
    <div class="footer-content container">
        <div class="footer-box">
            <a href="/Hr_Auto_Mobile/index.php" class="logo"><?= siteSetting('logo_text', 'HR Auto Mobile') ?>
                <span>.</span></a>
            <p><?= siteSetting('contact_address', 'Housing board, Ambedkarnagar, Surendranagar, Gujarat 363001') ?></p>
            <div class="social">
                <?php if (siteSetting('social_instagram')): ?>
                    <a href="<?= siteSetting('social_instagram') ?>" target="_blank" title="Instagram"><i
                            class='bx bxl-instagram'></i></a>
                <?php endif; ?>
                <?php if (siteSetting('social_linkedin')): ?>
                    <a href="<?= siteSetting('social_linkedin') ?>" target="_blank" title="LinkedIn"><i
                            class='bx bxl-linkedin'></i></a>
                <?php endif; ?>
                <?php if (siteSetting('social_facebook')): ?>
                    <a href="<?= siteSetting('social_facebook') ?>" target="_blank" title="Facebook"><i
                            class='bx bxl-facebook'></i></a>
                <?php endif; ?>
                <?php if (siteSetting('social_pinterest')): ?>
                    <a href="<?= siteSetting('social_pinterest') ?>" target="_blank" title="Pinterest"><i
                            class='bx bxl-pinterest-alt'></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-box">
            <h3>About HR Auto Mobile</h3>
            <p><?= siteSetting('about_us', 'HR Auto Mobile is your one-stop solution for all car and bike maintenance needs. We provide top-quality services with a focus on customer satisfaction.') ?></p>
        </div>

        <div class="footer-box">
            <h3>Quick Navigation</h3>
            <a href="/Hr_Auto_Mobile/index.php"><span>Home</span></a>
            <a href="/Hr_Auto_Mobile/about.php"><span>About Us</span></a>
            <a href="/Hr_Auto_Mobile/services.php"><span>Services</span></a>
            <a href="/Hr_Auto_Mobile/contact.php"><span>Contacts</span></a>
        </div>

        <div class="footer-box">
            <h3>Contact Us</h3>
            <a href="tel:<?= preg_replace('/[^0-9+]/', '', siteSetting('contact_phone', '+91 9016747304')) ?>">
                <span><i class='bx bx-phone'></i> <?= siteSetting('contact_phone', '+91 9016747304') ?></span>
            </a>
            <a href="mailto:<?= siteSetting('contact_email', 'hrautomobile@gmail.com') ?>">
                <span><i class='bx bx-envelope'></i>
                    <?= siteSetting('contact_email', 'hrautomobile@gmail.com') ?></span>
            </a>
            <p><i class='bx bx-time-five'></i> <?= siteSetting('business_hours', 'Mon - Sat: 9:00 AM - 8:00 PM') ?></p>
        </div>
    </div>
    <div class="copyright">&#169; <?= date('Y') ?> <?= siteSetting('site_name', 'HR Auto Mobile') ?>. All rights
        reserved.</div>
</footer>