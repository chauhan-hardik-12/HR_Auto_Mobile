<?php
/**
 * Deep Functional Verification of HR Auto Mobile
 * Verifies that all protected views (admin and customer) and public pages execute cleanly without errors.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$pagesToTest = [
    // Admin Views (Testing with active superadmin session)
    'admin/index.php'          => 'admin',
    'admin/bookings.php'       => 'admin',
    'admin/brands.php'         => 'admin',
    'admin/models.php'         => 'admin',
    'admin/services.php'       => 'admin',
    'admin/model_pricing.php'  => 'admin',
    'admin/users.php'          => 'admin',
    'admin/messages.php'       => 'admin',
    'admin/settings.php'       => 'admin',
    'admin/testimonials.php'   => 'admin',
    'admin/partners.php'       => 'admin',
    'admin/admins.php'         => 'admin',
    'admin/profile.php'        => 'admin',
    'admin/vehicle_types.php'  => 'admin',

    // Customer Views (Testing with active user session)
    'dashboard.php'            => 'user',
    'checkout.php'             => 'user_checkout',

    // Public Views (Testing unauthenticated)
    'index.php'                => 'public',
    'about.php'                => 'public',
    'services.php'             => 'public',
    'brands.php'               => 'public',
    'contact.php'              => 'public',
    'login.php'                => 'public',
    'register.php'             => 'public'
];

echo "====================================================\n";
echo "HR AUTO MOBILE - COMPLETE FUNCTIONAL AUDIT\n";
echo "====================================================\n\n";

$allPassed = true;

foreach ($pagesToTest as $page => $type) {
    // Generate a temporary execution harness file for each page
    $harnessFile = __DIR__ . '/temp_harness.php';
    
    $code = "<?php\n";
    $code .= "error_reporting(E_ALL);\n";
    $code .= "ini_set('display_errors', '1');\n";
    $code .= "session_start();\n";

    if ($type === 'admin') {
        $code .= "\$_SESSION['admin_logged_in'] = true;\n";
        $code .= "\$_SESSION['admin_id'] = 1;\n";
        $code .= "\$_SESSION['admin_username'] = 'admin';\n";
        $code .= "\$_SESSION['admin_role'] = 'superadmin';\n";
        $code .= "\$_SESSION['admin_name'] = 'Administrator';\n";
        $code .= "\$_SESSION['admin_email'] = 'admin@hrautomobile.com';\n";
    } elseif ($type === 'user' || $type === 'user_checkout') {
        $code .= "\$_SESSION['is_authenticated'] = true;\n";
        $code .= "\$_SESSION['user_id'] = 1;\n";
        $code .= "\$_SESSION['customer_name'] = 'Chauhan Hardik';\n";
        $code .= "\$_SESSION['user_phone'] = '9016747304';\n";
        $code .= "\$_SESSION['user_email'] = 'chauhan.hardik.ca.012@gmail.com';\n";
        if ($type === 'user_checkout') {
            $code .= "\$_GET['model_id'] = 197;\n";
            $code .= "\$_GET['service_id'] = 2;\n";
            $code .= "\$_GET['amount'] = 4999.00;\n";
        }
    }

    $code .= "\$_SERVER['REQUEST_METHOD'] = 'GET';\n";
    $code .= "\$_SERVER['HTTP_HOST'] = 'localhost';\n";
    $code .= "\$_SERVER['REQUEST_URI'] = '/" . $page . "';\n";
    $code .= "\$_SERVER['PHP_SELF'] = '/" . $page . "';\n";
    $code .= "\$_SERVER['SCRIPT_NAME'] = '/" . $page . "';\n";
    $code .= "chdir(" . var_export(dirname(__DIR__), true) . ");\n";
    $code .= "ob_start();\n";
    $code .= "try {\n";
    $code .= "    include " . var_export(dirname(__DIR__) . '/' . $page, true) . ";\n";
    $code .= "} catch (\\Throwable \$e) {\n";
    $code .= "    echo 'FATAL_EXCEPTION: ' . \$e->getMessage() . ' in ' . \$e->getFile() . ' on line ' . \$e->getLine();\n";
    $code .= "}\n";
    $code .= "\$output = ob_get_clean();\n";
    $code .= "if (preg_match('/(Fatal error|Parse error|Notice:|Warning:|Deprecated:|FATAL_EXCEPTION:)/i', \$output, \$m)) {\n";
    $code .= "    echo 'FAIL: ' . \$m[0] . ' -> ' . trim(substr(\$output, 0, 300));\n";
    $code .= "} else {\n";
    $code .= "    echo 'OK (size: ' . strlen(\$output) . ' bytes)';\n";
    $code .= "}\n";

    file_put_contents($harnessFile, $code);

    $process = popen('C:\\xampp\\php\\php.exe "' . $harnessFile . '" 2>&1', 'r');
    $read = '';
    while (!feof($process)) {
        $read .= fread($process, 1024);
    }
    pclose($process);
    @unlink($harnessFile);

    $read = trim($read);

    if (str_starts_with($read, 'OK')) {
        echo "  [PASS] " . str_pad($page, 28) . " -> " . $read . "\n";
    } else {
        $allPassed = false;
        echo "  [FAIL] " . str_pad($page, 28) . " -> " . $read . "\n";
    }
}

echo "\n====================================================\n";
if ($allPassed) {
    echo "VERIFICATION PASSED: ALL 23 PAGES WORK FLAWLESSLY!\n";
    echo "ZERO Fatal Errors, ZERO Warnings, ZERO Notices.\n";
} else {
    echo "VERIFICATION FAILED: Review the error output above.\n";
}
echo "====================================================\n";

exit($allPassed ? 0 : 1);
