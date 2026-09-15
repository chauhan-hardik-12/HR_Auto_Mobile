<?php
define('PAGE_TITLE', 'Site Settings - HR Auto Mobile Admin');
require_once __DIR__ . '/includes/auth.php';

// Handle POST save settings
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_settings'])) {
    $settings = $_POST['settings'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value, setting_group)
        VALUES (:key, :val, :grp)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");

    foreach ($settings as $key => $val) {
        $group = $_POST['groups'][$key] ?? 'general';
        $stmt->execute([
            'key' => trim($key),
            'val' => trim($val),
            'grp' => $group
        ]);
    }

    setFlashMessage('success', "Website settings updated successfully! All changes are now live on the site.");
    header("Location: settings.php");
    exit;
}

// Fetch all settings from DB
$rawSettings = $pdo->query("SELECT * FROM site_settings")->fetchAll();
$s = [];
foreach ($rawSettings as $row) {
    $s[$row['setting_key']] = $row['setting_value'];
}

function val($key, $default = '') {
    global $s;
    return htmlspecialchars($s[$key] ?? $default);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-sliders-h mr-2 text-primary"></i> Master Website Settings</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt mr-1"></i> Preview Live Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <form method="POST" action="settings.php">
                <input type="hidden" name="save_settings" value="1">

                <div class="card card-primary card-outline card-tabs shadow-sm">
                    <div class="card-header p-0 pt-1 border-bottom-0">
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active font-weight-bold" id="general-tab" data-toggle="pill" href="#general" role="tab">
                                    <i class="fas fa-building mr-1"></i> Branding &amp; General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="contact-tab" data-toggle="pill" href="#contact" role="tab">
                                    <i class="fas fa-phone mr-1"></i> Contact &amp; Location
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="hero-tab" data-toggle="pill" href="#hero" role="tab">
                                    <i class="fas fa-image mr-1"></i> Hero Banner
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="stats-tab" data-toggle="pill" href="#stats" role="tab">
                                    <i class="fas fa-chart-pie mr-1"></i> Impact Numbers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="about-tab" data-toggle="pill" href="#about" role="tab">
                                    <i class="fas fa-info-circle mr-1"></i> About &amp; Story
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link font-weight-bold" id="social-tab" data-toggle="pill" href="#social" role="tab">
                                    <i class="fas fa-share-alt mr-1"></i> Social Media &amp; CTA
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content" id="settingsTabContent">

                            <!-- TAB 1: General & Branding -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-store mr-1"></i> Company Profile &amp; Identity</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Company / Platform Name</label>
                                        <input type="text" name="settings[site_name]" class="form-control" value="<?= val('site_name', 'HR Auto Mobile') ?>" required>
                                        <input type="hidden" name="groups[site_name]" value="general">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Logo Brand Text</label>
                                        <input type="text" name="settings[logo_text]" class="form-control" value="<?= val('logo_text', 'HR Auto Mobile') ?>" required>
                                        <input type="hidden" name="groups[logo_text]" value="general">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tagline / Motto</label>
                                    <input type="text" name="settings[site_tagline]" class="form-control" value="<?= val('site_tagline', 'Professional Bikes & Cars Maintenance Solutions') ?>">
                                    <input type="hidden" name="groups[site_tagline]" value="general">
                                    <small class="form-text text-muted">Displayed in header meta tags and footer overview.</small>
                                </div>
                            </div>

                            <!-- TAB 2: Contact & Location -->
                            <div class="tab-pane fade" id="contact" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-map-marker-alt mr-1"></i> Contact &amp; Workshop Details</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Contact Phone Number</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                                            <input type="text" name="settings[contact_phone]" class="form-control" value="<?= val('contact_phone', '+91 9016747304') ?>" required>
                                        </div>
                                        <input type="hidden" name="groups[contact_phone]" value="contact">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Official Support Email</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                                            <input type="email" name="settings[contact_email]" class="form-control" value="<?= val('contact_email', 'hrautomobile@gmail.com') ?>" required>
                                        </div>
                                        <input type="hidden" name="groups[contact_email]" value="contact">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Physical Address / Location</label>
                                    <textarea name="settings[contact_address]" class="form-control" rows="2"><?= val('contact_address', 'Housing board, Ambedkarnagar, Surendranagar, Gujarat 363001') ?></textarea>
                                    <input type="hidden" name="groups[contact_address]" value="contact">
                                </div>
                                <div class="form-group">
                                    <label>Business / Support Hours</label>
                                    <input type="text" name="settings[business_hours]" class="form-control" value="<?= val('business_hours', 'Mon - Sat: 9:00 AM - 8:00 PM (Sunday Open)') ?>">
                                    <input type="hidden" name="groups[business_hours]" value="contact">
                                </div>
                            </div>

                            <!-- TAB 3: Hero Banner -->
                            <div class="tab-pane fade" id="hero" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-bullhorn mr-1"></i> Homepage Hero Banner</h5>
                                <div class="form-group">
                                    <label>Hero Main Headline</label>
                                    <input type="text" name="settings[hero_title]" class="form-control" value="<?= val('hero_title', 'Bikes & Cars Services') ?>" required>
                                    <input type="hidden" name="groups[hero_title]" value="hero">
                                </div>
                                <div class="form-group">
                                    <label>Hero Subtitle / Description</label>
                                    <textarea name="settings[hero_subtitle]" class="form-control" rows="2"><?= val('hero_subtitle', 'Fast, reliable, and verified doorstep automotive maintenance and workshop repairs across Gujarat.') ?></textarea>
                                    <input type="hidden" name="groups[hero_subtitle]" value="hero">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Button Label Text</label>
                                        <input type="text" name="settings[hero_btn_text]" class="form-control" value="<?= val('hero_btn_text', 'Book Now') ?>">
                                        <input type="hidden" name="groups[hero_btn_text]" value="hero">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Button Target URL</label>
                                        <input type="text" name="settings[hero_btn_link]" class="form-control" value="<?= val('hero_btn_link', 'services.php') ?>">
                                        <input type="hidden" name="groups[hero_btn_link]" value="hero">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: Impact Numbers -->
                            <div class="tab-pane fade" id="stats" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-trophy mr-1"></i> Our Impact in Numbers (Live Statistics)</h5>
                                <p class="text-muted small">These numbers and labels are featured prominently on the Homepage and About page.</p>

                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label>Statistic 1 (Value)</label>
                                        <input type="text" name="settings[stat1_num]" class="form-control" value="<?= val('stat1_num', '15 Lakhs+') ?>">
                                        <input type="hidden" name="groups[stat1_num]" value="stats">
                                        <label class="mt-2 text-muted small">Label</label>
                                        <input type="text" name="settings[stat1_label]" class="form-control form-control-sm" value="<?= val('stat1_label', 'Vehicles Serviced') ?>">
                                        <input type="hidden" name="groups[stat1_label]" value="stats">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label>Statistic 2 (Value)</label>
                                        <input type="text" name="settings[stat2_num]" class="form-control" value="<?= val('stat2_num', '20,000+') ?>">
                                        <input type="hidden" name="groups[stat2_num]" value="stats">
                                        <label class="mt-2 text-muted small">Label</label>
                                        <input type="text" name="settings[stat2_label]" class="form-control form-control-sm" value="<?= val('stat2_label', 'Happy Customers') ?>">
                                        <input type="hidden" name="groups[stat2_label]" value="stats">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label>Statistic 3 (Value)</label>
                                        <input type="text" name="settings[stat3_num]" class="form-control" value="<?= val('stat3_num', '32+ Cities') ?>">
                                        <input type="hidden" name="groups[stat3_num]" value="stats">
                                        <label class="mt-2 text-muted small">Label</label>
                                        <input type="text" name="settings[stat3_label]" class="form-control form-control-sm" value="<?= val('stat3_label', 'Across India') ?>">
                                        <input type="hidden" name="groups[stat3_label]" value="stats">
                                    </div>

                                    <div class="col-md-3 form-group">
                                        <label>Statistic 4 (Value)</label>
                                        <input type="text" name="settings[stat4_num]" class="form-control" value="<?= val('stat4_num', '4.8 / 5') ?>">
                                        <input type="hidden" name="groups[stat4_num]" value="stats">
                                        <label class="mt-2 text-muted small">Label</label>
                                        <input type="text" name="settings[stat4_label]" class="form-control form-control-sm" value="<?= val('stat4_label', 'Average Rating') ?>">
                                        <input type="hidden" name="groups[stat4_label]" value="stats">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 5: About & Story -->
                            <div class="tab-pane fade" id="about" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-book-open mr-1"></i> About Us Page Content</h5>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label>Section Badge</label>
                                        <input type="text" name="settings[about_badge]" class="form-control" value="<?= val('about_badge', 'About Us') ?>">
                                        <input type="hidden" name="groups[about_badge]" value="about">
                                    </div>
                                    <div class="col-md-8 form-group">
                                        <label>Main Headline</label>
                                        <input type="text" name="settings[about_title]" class="form-control" value="<?= val('about_title', 'Making Vehicle Service Hassle-Free') ?>">
                                        <input type="hidden" name="groups[about_title]" value="about">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Subtitle Description</label>
                                    <input type="text" name="settings[about_subtitle]" class="form-control" value="<?= val('about_subtitle', 'Bike and car repair across 32+ Indian cities — trusted by 20,000+ customers') ?>">
                                    <input type="hidden" name="groups[about_subtitle]" value="about">
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label>Our Story Heading</label>
                                    <input type="text" name="settings[about_story_title]" class="form-control" value="<?= val('about_story_title', 'Why We Built HR Auto Mobile') ?>">
                                    <input type="hidden" name="groups[about_story_title]" value="about">
                                </div>
                                <div class="form-group">
                                    <label>Our Story (Paragraph 1)</label>
                                    <textarea name="settings[about_story_p1]" class="form-control" rows="3"><?= val('about_story_p1') ?></textarea>
                                    <input type="hidden" name="groups[about_story_p1]" value="about">
                                </div>
                                <div class="form-group">
                                    <label>Our Story (Paragraph 2)</label>
                                    <textarea name="settings[about_story_p2]" class="form-control" rows="3"><?= val('about_story_p2') ?></textarea>
                                    <input type="hidden" name="groups[about_story_p2]" value="about">
                                </div>
                            </div>

                            <!-- TAB 6: Social Media & CTA -->
                            <div class="tab-pane fade" id="social" role="tabpanel">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-share-alt mr-1"></i> Social Media Profiles</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label><i class="fab fa-instagram text-danger mr-1"></i> Instagram Profile URL</label>
                                        <input type="url" name="settings[social_instagram]" class="form-control" value="<?= val('social_instagram') ?>">
                                        <input type="hidden" name="groups[social_instagram]" value="social">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label><i class="fab fa-facebook text-primary mr-1"></i> Facebook Page URL</label>
                                        <input type="url" name="settings[social_facebook]" class="form-control" value="<?= val('social_facebook') ?>">
                                        <input type="hidden" name="groups[social_facebook]" value="social">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label><i class="fab fa-linkedin text-info mr-1"></i> LinkedIn Profile URL</label>
                                        <input type="url" name="settings[social_linkedin]" class="form-control" value="<?= val('social_linkedin') ?>">
                                        <input type="hidden" name="groups[social_linkedin]" value="social">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label><i class="fab fa-pinterest text-danger mr-1"></i> Pinterest Profile URL</label>
                                        <input type="url" name="settings[social_pinterest]" class="form-control" value="<?= val('social_pinterest') ?>">
                                        <input type="hidden" name="groups[social_pinterest]" value="social">
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-bullhorn mr-1"></i> Bottom Call To Action (CTA) Strip</h5>
                                <div class="form-group">
                                    <label>CTA Title</label>
                                    <input type="text" name="settings[cta_title]" class="form-control" value="<?= val('cta_title', 'Ready to Experience Hassle-Free Maintenance?') ?>">
                                    <input type="hidden" name="groups[cta_title]" value="cta">
                                </div>
                                <div class="form-group">
                                    <label>CTA Description</label>
                                    <input type="text" name="settings[cta_desc]" class="form-control" value="<?= val('cta_desc', 'Book your next car or bike service with HR Auto Mobile and enjoy a smooth, reliable experience!') ?>">
                                    <input type="hidden" name="groups[cta_desc]" value="cta">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>CTA Button Label</label>
                                        <input type="text" name="settings[cta_btn_text]" class="form-control" value="<?= val('cta_btn_text', 'Book Now') ?>">
                                        <input type="hidden" name="groups[cta_btn_text]" value="cta">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>CTA Button Link</label>
                                        <input type="text" name="settings[cta_btn_link]" class="form-control" value="<?= val('cta_btn_link', 'services.php') ?>">
                                        <input type="hidden" name="groups[cta_btn_link]" value="cta">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card-footer bg-light text-right">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm font-weight-bold">
                            <i class="fas fa-save mr-2"></i> Save &amp; Publish Website Changes
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
