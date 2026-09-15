<?php
session_start();

require_once "includes/settings_loader.php";

// 1. Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Handle POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["number"] ?? "");
    $userMessage = trim($_POST["message"] ?? "");

    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "Invalid security token. Please refresh and try again.";
        $_SESSION['flash_type'] = "error";
        $_SESSION['form_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'userMessage' => $userMessage];
    } elseif ($name === "" || $email === "" || $phone === "" || $userMessage === "") {
        $_SESSION['flash_message'] = "Please fill in all fields.";
        $_SESSION['flash_type'] = "error";
        $_SESSION['form_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'userMessage' => $userMessage];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = "Please enter a valid email address.";
        $_SESSION['flash_type'] = "error";
        $_SESSION['form_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'userMessage' => $userMessage];
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $_SESSION['flash_message'] = "Please enter a valid 10-digit mobile number.";
        $_SESSION['flash_type'] = "error";
        $_SESSION['form_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'userMessage' => $userMessage];
    } else {
        try {
            $currentTime = date("Y-m-d H:i:s");

            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, message, created_at)
                VALUES (:name, :email, :phone, :message, :created_at)
            ");

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $userMessage,
                'created_at' => $currentTime
            ]);

            $formattedTime = date("F d, Y \a\\t h:i A", strtotime($currentTime));
            $_SESSION['flash_message'] = "Your message was successfully submitted on " . $formattedTime . "! We will get back to you soon.";
            $_SESSION['flash_type'] = "success";

            // Clear saved form data so fields become empty
            unset($_SESSION['form_data']);

            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        } catch (PDOException $e) {
            error_log("Database Error in contact form: " . $e->getMessage());
            $_SESSION['flash_message'] = "Something went wrong while saving your message. Please try again later.";
            $_SESSION['flash_type'] = "error";
            $_SESSION['form_data'] = ['name' => $name, 'email' => $email, 'phone' => $phone, 'userMessage' => $userMessage];
        }
    }

    // 3. Redirect back to GET
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 4. Retrieve Flash Data for GET display
$message = $_SESSION['flash_message'] ?? "";
$messageType = $_SESSION['flash_type'] ?? "";
$formData = $_SESSION['form_data'] ?? [];

// Clear flash messages so they don't persist on subsequent refreshes
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['form_data']);

// 5. Populate fields ONLY if there was an error; otherwise keep completely blank
$name = $formData['name'] ?? "";
$email = $formData['email'] ?? "";
$phone = $formData['phone'] ?? "";
$userMessage = $formData['userMessage'] ?? "";

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - HR Auto Mobile</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .form-message {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
        }

        .form-message.success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .form-message.error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
    </style>
</head>

<body>
    <?php include("includes/header.php"); ?>

    <!-- Contact Header -->
    <section class="contact">
        <h2>Contact</h2>
        <h1>Get in Touch</h1>
        <p>
            Have questions about our services, booking process, or partner
            registration? Our team is ready to help.
            Contact us anytime and we'll get back to you as soon as possible.
        </p>
    </section>

    <!-- Contact Information -->
    <section class="contact-info">
        <h2>Reach Us</h2>
        <h1>Contact Information</h1>
        <div class="contact-content container">
            <!-- Phone -->
            <div class="contact-box">
                <a href="tel:<?= preg_replace('/[^0-9+]/', '', siteSetting('contact_phone', '+91 9016747304')) ?>">
                    <img src="assets/images/contact-page/phone-call.png" alt="Phone">
                    <h3>Phone</h3>
                    <p><?= siteSetting('contact_phone', '+91 9016747304') ?></p>
                </a>
            </div>
            <!-- Email -->
            <div class="contact-box">
                <a href="mailto:<?= siteSetting('contact_email', 'hrautomobile@gmail.com') ?>">
                    <img src="assets/images/contact-page/mail.png" alt="Email">
                    <h3>Email</h3>
                    <p><?= siteSetting('contact_email', 'hrautomobile@gmail.com') ?></p>
                </a>
            </div>
            <!-- Address -->
            <div class="contact-box">
                <img src="assets/images/contact-page/location.png" alt="Address">
                <h3>Address</h3>
                <p><?= nl2br(siteSetting('contact_address', 'Housing board, Ambedkarnagar, Surendranagar, Gujarat 363001')) ?>
                </p>
            </div>
            <!-- Working Hours -->
            <div class="contact-box">
                <img src="assets/images/contact-page/clock.png" alt="Time">
                <h3>Working Hours</h3>
                <p><?= siteSetting('business_hours', 'Monday - Saturday: 9:00 AM - 8:00 PM') ?></p>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="message">
        <h2>Send Us a Message</h2>
        <h1>Have a Question? Write to Us</h1>
        <div class="message container">

            <!-- Success / Error Alert -->
            <?php if (!empty($message)): ?>
                <div class="form-message <?php echo htmlspecialchars($messageType); ?>">
                    <i class='bx <?php echo $messageType === "success" ? "bx-check-circle" : "bx-error-circle"; ?>'></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-row">
                    <!-- Name -->
                    <div class="form-box">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter Your Name"
                            value="<?php echo htmlspecialchars($name); ?>" required autocomplete="off">
                    </div>
                    <!-- Email -->
                    <div class="form-box">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter Your Email"
                            value="<?php echo htmlspecialchars($email); ?>" required autocomplete="off">
                    </div>
                    <!-- Phone -->
                    <div class="form-box">
                        <label for="number">Phone</label>
                        <input type="tel" id="number" name="number" placeholder="Enter 10-digit Number" maxlength="10"
                            pattern="[0-9]{10}" value="<?php echo htmlspecialchars($phone); ?>" required
                            autocomplete="off">
                    </div>
                </div>

                <!-- Message -->
                <div class="form-row">
                    <div class="form-box full-width">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="6" placeholder="Enter Your Message"
                            required><?php echo htmlspecialchars($userMessage); ?></textarea>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit">Send Message</button>
            </form>
        </div>
    </section>

    <?php include("includes/footer.php"); ?>
</body>

</html>