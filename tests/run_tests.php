<?php
/**
 * Automated Test Suite for HR Auto Mobile
 * Run via CLI: php tests/run_tests.php
 * Or via web browser: http://localhost/HR_Auto_Mobile/tests/run_tests.php
 */

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><title>HR Auto Mobile - Test Suite</title>";
    echo "<style>
        body { font-family: monospace; background: #0f172a; color: #f8fafc; padding: 24px; line-height: 1.6; }
        .pass { color: #4ade80; font-weight: bold; }
        .fail { color: #f87171; font-weight: bold; }
        .info { color: #38bdf8; }
        .summary { margin-top: 20px; padding: 16px; border: 1px solid #334155; border-radius: 8px; background: #1e293b; }
        pre { white-space: pre-wrap; }
    </style></head><body><pre>";
}

function colorLog($text, $status = 'info') {
    global $isCli;
    if ($isCli) {
        $colors = [
            'green' => "\033[32m",
            'red'   => "\033[31m",
            'yellow'=> "\033[33m",
            'cyan'  => "\033[36m",
            'reset' => "\033[0m"
        ];
        switch ($status) {
            case 'pass':   echo "  " . $colors['green']  . "[PASS] " . $colors['reset'] . $text . PHP_EOL; break;
            case 'fail':   echo "  " . $colors['red']    . "[FAIL] " . $colors['reset'] . $text . PHP_EOL; break;
            case 'header': echo PHP_EOL . $colors['cyan'] . "=== " . $text . " ===" . $colors['reset'] . PHP_EOL; break;
            default:       echo $text . PHP_EOL; break;
        }
    } else {
        switch ($status) {
            case 'pass':   echo "  <span class='pass'>[PASS]</span> " . htmlspecialchars($text) . "<br>"; break;
            case 'fail':   echo "  <span class='fail'>[FAIL]</span> " . htmlspecialchars($text) . "<br>"; break;
            case 'header': echo "<br><span class='info'>=== " . htmlspecialchars($text) . " ===</span><br>"; break;
            default:       echo htmlspecialchars($text) . "<br>"; break;
        }
    }
}

$passed = 0;
$failed = 0;

function assertTest($condition, $description, $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        colorLog($description, 'pass');
    } else {
        $failed++;
        colorLog($description . ($details ? " ($details)" : ""), 'fail');
    }
}

// Base configuration
$dbFile = __DIR__ . '/../assets/config/db.php';
$baseUrl = 'http://localhost/HR_Auto_Mobile';

colorLog("HR AUTO MOBILE - AUTOMATED TEST SUITE", 'header');
colorLog("Execution Time: " . date('Y-m-d H:i:s'));
colorLog("PHP Version: " . phpversion());

// -------------------------------------------------------------
// 1. Database Connection & Config
// -------------------------------------------------------------
colorLog("1. Database Connection & Configuration", 'header');

assertTest(file_exists($dbFile), "Database configuration file exists");
require_once $dbFile;
assertTest(isset($pdo) && ($pdo instanceof PDO), "PDO connection established successfully");

// Test database name
try {
    $dbNameStmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $dbNameStmt->fetchColumn();
    assertTest($currentDb === 'auto-mobile', "Connected to correct database: 'auto-mobile'");
} catch (Exception $e) {
    assertTest(false, "Database query failed", $e->getMessage());
}

// -------------------------------------------------------------
// 2. Database Schema & Tables Verification
// -------------------------------------------------------------
colorLog("2. Database Schema & Tables Verification", 'header');

$expectedTables = [
    'admins',
    'bookings',
    'brands',
    'contact_messages',
    'model_services',
    'models',
    'partners',
    'services',
    'site_settings',
    'testimonials',
    'users',
    'vehicle_types'
];

try {
    $tablesStmt = $pdo->query("SHOW TABLES");
    $existingTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($expectedTables as $table) {
        assertTest(in_array($table, $existingTables), "Table '{$table}' exists in database");
    }
} catch (Exception $e) {
    assertTest(false, "Failed checking tables", $e->getMessage());
}

// -------------------------------------------------------------
// 3. Database Integrity & Seeded Data Checks
// -------------------------------------------------------------
colorLog("3. Catalog & Relational Data Integrity", 'header');

try {
    // Vehicle types count
    $vTypeCount = (int) $pdo->query("SELECT COUNT(*) FROM vehicle_types WHERE status = 1")->fetchColumn();
    assertTest($vTypeCount >= 2, "Active vehicle types present (Found: {$vTypeCount}, expected >= 2)");

    // Brands count
    $brandCount = (int) $pdo->query("SELECT COUNT(*) FROM brands WHERE status = 1")->fetchColumn();
    assertTest($brandCount >= 10, "Active brands present (Found: {$brandCount}, expected >= 10)");

    // Check orphan brands
    $orphanBrands = (int) $pdo->query("
        SELECT COUNT(*) FROM brands b 
        LEFT JOIN vehicle_types vt ON b.vehicle_type_id = vt.id 
        WHERE vt.id IS NULL
    ")->fetchColumn();
    assertTest($orphanBrands === 0, "No orphaned brands (all map to valid vehicle_types)");

    // Models count
    $modelCount = (int) $pdo->query("SELECT COUNT(*) FROM models WHERE status = 1")->fetchColumn();
    assertTest($modelCount >= 10, "Active vehicle models present (Found: {$modelCount}, expected >= 10)");

    // Check orphan models
    $orphanModels = (int) $pdo->query("
        SELECT COUNT(*) FROM models m 
        LEFT JOIN brands b ON m.brand_id = b.id 
        WHERE b.id IS NULL
    ")->fetchColumn();
    assertTest($orphanModels === 0, "No orphaned models (all map to valid brands)");

    // Services count
    $serviceCount = (int) $pdo->query("SELECT COUNT(*) FROM services WHERE status = 1")->fetchColumn();
    assertTest($serviceCount >= 3, "Active services present (Found: {$serviceCount}, expected >= 3)");

    // Model services mapping
    $pricingCount = (int) $pdo->query("SELECT COUNT(*) FROM model_services WHERE status = 1")->fetchColumn();
    assertTest($pricingCount > 0, "Model-service pricing mappings present (Found: {$pricingCount})");

    // Check orphan model_services
    $orphanPricing = (int) $pdo->query("
        SELECT COUNT(*) FROM model_services ms 
        LEFT JOIN models m ON ms.model_id = m.id 
        LEFT JOIN services s ON ms.service_id = s.id 
        WHERE m.id IS NULL OR s.id IS NULL
    ")->fetchColumn();
    assertTest($orphanPricing === 0, "No orphaned model_services records");

    // Check admin account presence & password hash
    $admin = $pdo->query("SELECT id, username, email, password, role, status FROM admins WHERE role = 'superadmin' LIMIT 1")->fetch();
    assertTest(!empty($admin), "Superadmin account exists (Username: " . ($admin['username'] ?? 'none') . ")");
    assertTest(str_starts_with($admin['password'] ?? '', '$2y$'), "Admin password hash uses valid Bcrypt format");

    // Check site settings
    $settingsCount = (int) $pdo->query("SELECT COUNT(*) FROM site_settings")->fetchColumn();
    assertTest($settingsCount >= 5, "Site settings configured (Found: {$settingsCount} settings)");

} catch (Exception $e) {
    assertTest(false, "Catalog integrity check error", $e->getMessage());
}

// -------------------------------------------------------------
// 4. Catalog API Endpoint Tests (HTTP)
// -------------------------------------------------------------
colorLog("4. Catalog API Endpoint Verification", 'header');

function httpGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

function httpPost($url, $data = [], $json = true) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    if ($json) {
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

// Test Catalog: types
$typesRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=types");
assertTest($typesRes['code'] === 200, "Catalog API types endpoint returns HTTP 200");
$typesData = json_decode($typesRes['body'], true);
assertTest(isset($typesData['success']) && $typesData['success'] === true && !empty($typesData['data']), "Catalog types returns valid JSON with data list");

// Test Catalog: brands
$testTypeId = $typesData['data'][0]['id'] ?? 1;
$brandsRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=brands&vehicle_type_id={$testTypeId}");
assertTest($brandsRes['code'] === 200, "Catalog API brands endpoint returns HTTP 200");
$brandsData = json_decode($brandsRes['body'], true);
assertTest(isset($brandsData['success']) && $brandsData['success'] === true && !empty($brandsData['data']), "Catalog brands returns valid JSON with brands list");

// Test Catalog: models
$testBrandId = $brandsData['data'][0]['id'] ?? 1;
$modelsRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=models&brand_id={$testBrandId}");
assertTest($modelsRes['code'] === 200, "Catalog API models endpoint returns HTTP 200");
$modelsData = json_decode($modelsRes['body'], true);
assertTest(isset($modelsData['success']) && $modelsData['success'] === true, "Catalog models returns valid JSON structure");

// Test Catalog: services for Swift (model_id=197) or first model
$testModelId = 197;
$servicesRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=services&model_id={$testModelId}");
assertTest($servicesRes['code'] === 200, "Catalog API services endpoint returns HTTP 200");
$servicesData = json_decode($servicesRes['body'], true);
assertTest(isset($servicesData['success']) && $servicesData['success'] === true, "Catalog services returns valid pricing package list");

// Test Catalog Error Handling
$invalidActionRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=unknown_action");
$invalidActionData = json_decode($invalidActionRes['body'], true);
assertTest(isset($invalidActionData['success']) && $invalidActionData['success'] === false, "Catalog API rejects invalid action gracefully");

$missingParamRes = httpGet("{$baseUrl}/assets/api/catalog-api.php?action=brands");
$missingParamData = json_decode($missingParamRes['body'], true);
assertTest(isset($missingParamData['success']) && $missingParamData['success'] === false, "Catalog API requires vehicle_type_id parameter for brands");

// -------------------------------------------------------------
// 5. Authentication API & Validation Tests
// -------------------------------------------------------------
colorLog("5. Authentication API & Validation Tests", 'header');

// Auth check
$authCheckRes = httpGet("{$baseUrl}/assets/api/auth-check.php");
assertTest($authCheckRes['code'] === 200, "Auth check endpoint returns HTTP 200");
$authCheckData = json_decode($authCheckRes['body'], true);
assertTest(isset($authCheckData['logged_in']) && $authCheckData['logged_in'] === false, "Auth check reports logged_in = false for unauthenticated visitor");

// Invalid credentials login
$badLoginRes = httpPost("{$baseUrl}/assets/api/auth-handler.php?action=login", [
    'identifier' => 'nonexistent_test_user@example.com',
    'password'   => 'WrongPassword123!'
]);
$badLoginData = json_decode($badLoginRes['body'], true);
assertTest(isset($badLoginData['success']) && $badLoginData['success'] === false, "Invalid login credentials properly rejected");

// Empty login payload
$emptyLoginRes = httpPost("{$baseUrl}/assets/api/auth-handler.php?action=login", []);
$emptyLoginData = json_decode($emptyLoginRes['body'], true);
assertTest(isset($emptyLoginData['success']) && $emptyLoginData['success'] === false, "Empty login payload properly rejected");

// Registration validation: Invalid email
$badEmailReg = httpPost("{$baseUrl}/assets/api/auth-handler.php?action=register", [
    'name'     => 'Test Unit',
    'phone'    => '9999999999',
    'email'    => 'invalid-email-address',
    'password' => 'SecurePass123!'
]);
$badEmailData = json_decode($badEmailReg['body'], true);
assertTest(isset($badEmailData['success']) && $badEmailData['success'] === false && str_contains($badEmailData['message'], 'valid email'), "Registration rejects malformed email");

// Registration validation: Invalid phone length
$badPhoneReg = httpPost("{$baseUrl}/assets/api/auth-handler.php?action=register", [
    'name'     => 'Test Unit',
    'phone'    => '12345',
    'email'    => 'validtest@example.com',
    'password' => 'SecurePass123!'
]);
$badPhoneData = json_decode($badPhoneReg['body'], true);
assertTest(isset($badPhoneData['success']) && $badPhoneData['success'] === false && str_contains($badPhoneData['message'], '10 digits'), "Registration rejects phone number that is not 10 digits");

// Registration validation: Short password
$badPassReg = httpPost("{$baseUrl}/assets/api/auth-handler.php?action=register", [
    'name'     => 'Test Unit',
    'phone'    => '9898989898',
    'email'    => 'validtest@example.com',
    'password' => '123'
]);
$badPassData = json_decode($badPassReg['body'], true);
assertTest(isset($badPassData['success']) && $badPassData['success'] === false && str_contains($badPassData['message'], '8 characters'), "Registration rejects passwords shorter than 8 characters");

// -------------------------------------------------------------
// 6. Security, Access Guards & Route Protection
// -------------------------------------------------------------
colorLog("6. Security, Access Guards & Route Protection", 'header');

$publicRoutes = [
    'index.php',
    'about.php',
    'services.php',
    'brands.php',
    'contact.php',
    'login.php',
    'register.php'
];

foreach ($publicRoutes as $route) {
    $res = httpGet("{$baseUrl}/{$route}");
    assertTest($res['code'] === 200, "Public route '{$route}' returns HTTP 200 OK");
}

// admin/login.php is designed to forward to unified login.php
$adminLoginRes = httpGet("{$baseUrl}/admin/login.php");
assertTest($adminLoginRes['code'] === 302, "Route 'admin/login.php' correctly forwards to unified login (HTTP 302)");

$protectedCustomerRoutes = [
    'dashboard.php',
    'checkout.php'
];

foreach ($protectedCustomerRoutes as $route) {
    $res = httpGet("{$baseUrl}/{$route}");
    assertTest($res['code'] === 302, "Protected customer route '{$route}' redirects unauthenticated users (HTTP 302)");
}

$protectedAdminRoutes = [
    'admin/index.php',
    'admin/bookings.php',
    'admin/brands.php',
    'admin/models.php',
    'admin/services.php',
    'admin/model_pricing.php',
    'admin/users.php',
    'admin/messages.php',
    'admin/settings.php'
];

foreach ($protectedAdminRoutes as $route) {
    $res = httpGet("{$baseUrl}/{$route}");
    assertTest($res['code'] === 302, "Protected admin route '{$route}' redirects unauthenticated users (HTTP 302)");
}

// Check .htaccess sensitive protection
$htaccessRes = httpGet("{$baseUrl}/.htaccess");
assertTest(in_array($htaccessRes['code'], [403, 404, 302]), "Direct access to .htaccess is forbidden/blocked (HTTP {$htaccessRes['code']})");

// -------------------------------------------------------------
// 7. End-to-End Booking Workflow Simulation (Transactional)
// -------------------------------------------------------------
colorLog("7. End-to-End Booking Workflow Simulation", 'header');

try {
    // We run in a transaction and roll back so live database remains clean
    $pdo->beginTransaction();

    // 1. Create simulated user
    $testEmail = 'qa_autotest_' . time() . '@hrautomobile.test';
    $testPhone = '9000' . rand(100000, 999999);
    $userInsert = $pdo->prepare("
        INSERT INTO users (name, phone, email, password, created_at)
        VALUES ('QA Auto Test User', :phone, :email, :pass, NOW())
    ");
    $userInsert->execute([
        'phone' => $testPhone,
        'email' => $testEmail,
        'pass'  => password_hash('QaTestSecret123!', PASSWORD_DEFAULT)
    ]);
    $simUserId = (int) $pdo->lastInsertId();
    assertTest($simUserId > 0, "Created simulated test customer (ID: {$simUserId})");

    // 2. Resolve model & service
    $pkg = $pdo->query("
        SELECT ms.model_id, ms.service_id, ms.price 
        FROM model_services ms 
        WHERE ms.status = 1 
        LIMIT 1
    ")->fetch();
    assertTest(!empty($pkg), "Resolved active service package for test booking");

    // 3. Insert Booking
    $bStmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, model_id, service_id,
            customer_name, customer_phone, customer_email,
            booking_date, booking_time, address,
            amount, status, payment_status, notes,
            created_at, updated_at
        ) VALUES (
            :user_id, :model_id, :service_id,
            'QA Auto Test User', :phone, :email,
            :b_date, '10:00:00', '123 QA Test Street, Testing City',
            :amount, 'pending', 'pending', 'Automated QA Test Booking',
            NOW(), NOW()
        )
    ");
    $bStmt->execute([
        'user_id'    => $simUserId,
        'model_id'   => $pkg['model_id'],
        'service_id' => $pkg['service_id'],
        'phone'      => $testPhone,
        'email'      => $testEmail,
        'b_date'     => date('Y-m-d', strtotime('+2 days')),
        'amount'     => $pkg['price']
    ]);
    $simBookingId = (int) $pdo->lastInsertId();
    assertTest($simBookingId > 0, "Inserted test booking (Booking #{$simBookingId}, Amount: {$pkg['price']})");

    // 4. Verify Customer Dashboard query joins
    $dashStmt = $pdo->prepare("
        SELECT 
            b.*,
            m.name AS model_name,
            br.name AS brand_name,
            vt.name AS vehicle_type,
            s.name AS service_name
        FROM bookings b
        JOIN models m ON b.model_id = m.id
        JOIN brands br ON m.brand_id = br.id
        JOIN vehicle_types vt ON br.vehicle_type_id = vt.id
        JOIN services s ON b.service_id = s.id
        WHERE b.id = :id AND b.user_id = :user_id
    ");
    $dashStmt->execute(['id' => $simBookingId, 'user_id' => $simUserId]);
    $bookingRecord = $dashStmt->fetch();

    assertTest(!empty($bookingRecord), "Dashboard multi-table join successfully retrieved booking details");
    assertTest($bookingRecord['status'] === 'pending', "Booking status initialized as 'pending'");
    assertTest($bookingRecord['payment_status'] === 'pending', "Payment status initialized as 'pending'");
    assertTest(!empty($bookingRecord['model_name']) && !empty($bookingRecord['service_name']), "Vehicle model and service names resolved correctly");

    // 5. Simulate Admin Status Transition
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', updated_at = NOW() WHERE id = :id");
    $updateStmt->execute(['id' => $simBookingId]);
    $updatedStatus = $pdo->query("SELECT status FROM bookings WHERE id = {$simBookingId}")->fetchColumn();
    assertTest($updatedStatus === 'confirmed', "Admin status update to 'confirmed' executed successfully");

    $completeStmt = $pdo->prepare("UPDATE bookings SET status = 'completed', payment_status = 'paid', updated_at = NOW() WHERE id = :id");
    $completeStmt->execute(['id' => $simBookingId]);
    $completedBooking = $pdo->query("SELECT status, payment_status FROM bookings WHERE id = {$simBookingId}")->fetch();
    assertTest($completedBooking['status'] === 'completed' && $completedBooking['payment_status'] === 'paid', "Status updated to 'completed' and payment to 'paid'");

    // Rollback test data to leave DB clean
    $pdo->rollBack();
    colorLog("Automated test transaction rolled back successfully (database left in clean state)");

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    assertTest(false, "Booking workflow simulation failed", $e->getMessage());
}

// -------------------------------------------------------------
// 8. Client-Side JavaScript Integrity & Console Safety (T-17)
// -------------------------------------------------------------
colorLog("8. Client-Side JavaScript & Console Safety (T-17)", 'header');

$scriptJsPath = __DIR__ . '/../assets/js/script.js';
$bookJsPath = __DIR__ . '/../assets/js/book.js';

assertTest(file_exists($scriptJsPath), "Frontend script.js exists");
assertTest(file_exists($bookJsPath), "Frontend book.js exists");

$scriptJsContent = file_get_contents($scriptJsPath);
assertTest(
    str_contains($scriptJsContent, 'typeof ScrollReveal !== "undefined"') || str_contains($scriptJsContent, "typeof ScrollReveal !== 'undefined'"),
    "script.js protects ScrollReveal call with typeof check (prevents ReferenceError)"
);
assertTest(
    str_contains($scriptJsContent, 'if (startDate)') && str_contains($scriptJsContent, 'if (returnDate)'),
    "script.js protects start-date & return-date access with null guards (prevents TypeError)"
);
assertTest(
    str_contains($scriptJsContent, 'DOMContentLoaded'),
    "script.js defers execution until DOMContentLoaded is ready"
);

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
$totalTests = $passed + $failed;
$passRate = $totalTests > 0 ? round(($passed / $totalTests) * 100, 2) : 0;

colorLog("TEST EXECUTION SUMMARY", 'header');
colorLog("Total Tests Executed : " . $totalTests);
colorLog("Passed               : " . $passed);
colorLog("Failed               : " . $failed);
colorLog("Success Rate         : " . $passRate . "%");

if ($failed === 0) {
    colorLog("ALL TESTS PASSED SUCCESSFULLY!", 'pass');
} else {
    colorLog("SOME TESTS FAILED! Check log details above.", 'fail');
}

if (!$isCli) {
    echo "</pre></body></html>";
}

exit($failed === 0 ? 0 : 1);
