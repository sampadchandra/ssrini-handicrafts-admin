<?php
declare(strict_types=1);
/*
|--------------------------------------------------------------------------
| SSRINI HANDCRAFTS - SETTINGS
|--------------------------------------------------------------------------
| File: admin/settings.php
| Database: ssrini_handcrafts
| Table: store_settings
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function postValue(string $key, $default = ''): string
{
    if (!isset($_POST[$key])) {
        return (string)$default;
    }
    return trim((string)$_POST[$key]);
}

function checkedValue($value): string
{
    return ((int)$value === 1) ? 'checked' : '';
}

/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is not available.');
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['settings_csrf_token'])) {
    $_SESSION['settings_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['settings_csrf_token'];

/*
|--------------------------------------------------------------------------
| ENSURE STORE SETTINGS COLUMNS
|--------------------------------------------------------------------------
*/
$requiredColumns = [ 
    'store_name' => "VARCHAR(150) NULL", 
    'tagline' => "VARCHAR(255) NULL", 
    'email' => "VARCHAR(150) NULL", 
    'phone' => "VARCHAR(30) NULL", 
    'whatsapp' => "VARCHAR(30) NULL", 
    'address' => "TEXT NULL", 
    'city' => "VARCHAR(100) NULL", 
    'state' => "VARCHAR(100) NULL", 
    'pincode' => "VARCHAR(20) NULL", 
    'instagram_url' => "VARCHAR(500) NULL", 
    'facebook_url' => "VARCHAR(500) NULL", 
    'twitter_url' => "VARCHAR(500) NULL", 
    'website_url' => "VARCHAR(500) NULL", 
    'currency' => "VARCHAR(10) NULL DEFAULT 'INR'", 
    'shipping_charge' => "DECIMAL(10,2) NULL DEFAULT 0", 
    'free_shipping_min' => "DECIMAL(10,2) NULL DEFAULT 0", 
    'cod_enabled' => "TINYINT(1) NULL DEFAULT 1", 
    'online_payment_enabled' => "TINYINT(1) NULL DEFAULT 0", 
    'low_stock_threshold' => "INT NULL DEFAULT 5", 
    'maintenance_mode' => "TINYINT(1) NULL DEFAULT 0", 
    'updated_at' => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP" 
];

try {
    $columnStatement = $pdo->query("SHOW COLUMNS FROM store_settings");
    $existingColumns = [];
    while ($column = $columnStatement->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $column['Field'];
    }

    foreach ($requiredColumns as $columnName => $definition) {
        if (!in_array($columnName, $existingColumns, true)) {
            $safeColumnName = '`' . str_replace('`', '', $columnName) . '`';
            $pdo->exec("ALTER TABLE store_settings ADD COLUMN {$safeColumnName} {$definition}");
        }
    }
} catch (Throwable $exception) {
    $databaseError = 'Unable to prepare store settings table: ' . $exception->getMessage();
}

try {
    $countStatement = $pdo->query("SELECT COUNT(*) FROM store_settings");
    $settingsCount = (int)$countStatement->fetchColumn();
    if ($settingsCount === 0) {
        $insertStatement = $pdo->prepare(
            "INSERT INTO store_settings ( store_name, tagline, currency, shipping_charge, free_shipping_min, cod_enabled, online_payment_enabled, low_stock_threshold, maintenance_mode ) 
             VALUES ( :store_name, :tagline, :currency, :shipping_charge, :free_shipping_min, :cod_enabled, :online_payment_enabled, :low_stock_threshold, :maintenance_mode )"
        );
        $insertStatement->execute([
            ':store_name' => 'Ssrini Handicrafts',
            ':tagline' => 'Authentic handcrafted products from Bengal',
            ':currency' => 'INR',
            ':shipping_charge' => 0,
            ':free_shipping_min' => 0,
            ':cod_enabled' => 1,
            ':online_payment_enabled' => 0,
            ':low_stock_threshold' => 5,
            ':maintenance_mode' => 0
        ]);
    }
} catch (Throwable $exception) {
    $databaseError = 'Unable to initialize store settings: ' . $exception->getMessage();
}

$successMessage = '';
$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, (string)$submittedToken)) {
        $errorMessage = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $storeName = postValue('store_name');
        $tagline = postValue('tagline');
        $email = postValue('email');
        $phone = postValue('phone');
        $whatsapp = postValue('whatsapp');
        $address = postValue('address');
        $city = postValue('city');
        $state = postValue('state');
        $pincode = postValue('pincode');
        $instagramUrl = postValue('instagram_url');
        $facebookUrl = postValue('facebook_url');
        $twitterUrl = postValue('twitter_url');
        $websiteUrl = postValue('website_url');
        $currency = postValue('currency', 'INR');
        $shippingCharge = postValue('shipping_charge', '0');
        $freeShippingMin = postValue('free_shipping_min', '0');
        $lowStockThreshold = postValue('low_stock_threshold', '5');
        $codEnabled = isset($_POST['cod_enabled']) ? 1 : 0;
        $onlinePaymentEnabled = isset($_POST['online_payment_enabled']) ? 1 : 0;
        $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;

        if ($storeName === '') {
            $errorMessage = 'Store name is required.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'Please enter a valid email address.';
        } elseif (!is_numeric($shippingCharge) || (float)$shippingCharge < 0) {
            $errorMessage = 'Shipping charge must be a valid positive number.';
        } elseif (!is_numeric($freeShippingMin) || (float)$freeShippingMin < 0) {
            $errorMessage = 'Free shipping minimum must be a valid positive number.';
        } elseif (!is_numeric($lowStockThreshold) || (int)$lowStockThreshold < 0) {
            $errorMessage = 'Low stock threshold must be a valid number.';
        } else {
            $shippingCharge = number_format((float)$shippingCharge, 2, '.', '');
            $freeShippingMin = number_format((float)$freeShippingMin, 2, '.', '');
            $lowStockThreshold = (int)$lowStockThreshold;

            try {
                $updateStatement = $pdo->prepare(
                    "UPDATE store_settings SET 
                        store_name = :store_name, tagline = :tagline, email = :email, phone = :phone, 
                        whatsapp = :whatsapp, address = :address, city = :city, state = :state, 
                        pincode = :pincode, instagram_url = :instagram_url, facebook_url = :facebook_url, 
                        twitter_url = :twitter_url, website_url = :website_url, currency = :currency, 
                        shipping_charge = :shipping_charge, free_shipping_min = :free_shipping_min, 
                        cod_enabled = :cod_enabled, online_payment_enabled = :online_payment_enabled, 
                        low_stock_threshold = :low_stock_threshold, maintenance_mode = :maintenance_mode 
                     WHERE id = ( SELECT id FROM ( SELECT id FROM store_settings ORDER BY id ASC LIMIT 1 ) AS first_settings )"
                );
                $updateStatement->execute([
                    ':store_name' => $storeName,
                    ':tagline' => $tagline,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':whatsapp' => $whatsapp,
                    ':address' => $address,
                    ':city' => $city,
                    ':state' => $state,
                    ':pincode' => $pincode,
                    ':instagram_url' => $instagramUrl,
                    ':facebook_url' => $facebookUrl,
                    ':twitter_url' => $twitterUrl,
                    ':website_url' => $websiteUrl,
                    ':currency' => $currency,
                    ':shipping_charge' => $shippingCharge,
                    ':free_shipping_min' => $freeShippingMin,
                    ':cod_enabled' => $codEnabled,
                    ':online_payment_enabled' => $onlinePaymentEnabled,
                    ':low_stock_threshold' => $lowStockThreshold,
                    ':maintenance_mode' => $maintenanceMode
                ]);
                $successMessage = 'Store settings updated successfully.';
            } catch (Throwable $exception) {
                $errorMessage = 'Unable to save settings: ' . $exception->getMessage();
            }
        }
    }
}

$settings = [ 
    'store_name' => 'Ssrini Handicrafts', 'tagline' => 'Authentic handcrafted products from Bengal', 
    'email' => '', 'phone' => '', 'whatsapp' => '', 'address' => '', 'city' => '', 'state' => '', 
    'pincode' => '', 'instagram_url' => '', 'facebook_url' => '', 'twitter_url' => '', 
    'website_url' => '', 'currency' => 'INR', 'shipping_charge' => '0.00', 'free_shipping_min' => '0.00', 
    'cod_enabled' => 1, 'online_payment_enabled' => 0, 'low_stock_threshold' => 5, 'maintenance_mode' => 0 
];

try {
    $settingsStatement = $pdo->query("SELECT * FROM store_settings ORDER BY id ASC LIMIT 1");
    $databaseSettings = $settingsStatement->fetch(PDO::FETCH_ASSOC);
    if ($databaseSettings) {
        foreach ($settings as $key => $defaultValue) {
            if (array_key_exists($key, $databaseSettings)) {
                $settings[$key] = $databaseSettings[$key];
            }
        }
    }
} catch (Throwable $exception) {
    $errorMessage = 'Unable to load current settings: ' . $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMessage !== '') {
    foreach ($settings as $key => $value) {
        if (isset($_POST[$key])) {
            $settings[$key] = is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
        }
    }
    $settings['cod_enabled'] = isset($_POST['cod_enabled']) ? 1 : 0;
    $settings['online_payment_enabled'] = isset($_POST['online_payment_enabled']) ? 1 : 0;
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? 1 : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Ssrini Handicrafts</title>
    
    <!-- FONTS & ICONS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- SAFE CSS RESOLUTION TO FIX 404 / STYLESHEET ERRORS -->
    <link rel="stylesheet" href="<?php echo file_exists(__DIR__ . '/assets/css/admin.css') ? 'assets/css/admin.css' : '../assets/css/admin.css'; ?>">
    <link rel="stylesheet" href="<?php echo file_exists(__DIR__ . '/assets/css/style.css') ? 'assets/css/style.css' : '../assets/css/style.css'; ?>">
    
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .settings-content-body {
            flex: 1;
            min-width: 0;
            background: #f7f5fb;
            min-height: 100vh;
            padding: 32px;
            box-sizing: border-box;
            width: 100%;
        }

        .settings-content-body button, 
        .settings-content-body input, 
        .settings-content-body textarea, 
        .settings-content-body select {
            font-family: inherit;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }
        .page-title h1 {
            font-size: 28px;
            line-height: 1.2;
            font-weight: 800;
            color: #1f1b2d;
            margin: 0 0 6px 0;
        }
        .page-title p {
            font-size: 14px;
            color: #77717f;
            margin: 0;
        }
        .header-refresh-btn {
            border: 1px solid #e4dfea;
            background: #ffffff;
            color: #5c5365;
            height: 42px;
            padding: 0 16px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
            box-shadow: 0 4px 12px rgba(30,20,50,.03);
            white-space: nowrap;
        }
        .header-refresh-btn:hover {
            border-color: #b98be1;
            color: #7627c9;
        }

        .alert {
            border-radius: 12px;
            padding: 14px 17px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 11px;
            font-size: 13px;
            font-weight: 600;
        }
        .alert-success { background: #edf9f1; color: #207443; border: 1px solid #cdebd7; }
        .alert-error { background: #fff0f0; color: #b42318; border: 1px solid #f2cccc; }

        .settings-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 310px;
            gap: 24px;
            align-items: start;
        }

        .settings-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #eee9f4;
            box-shadow: 0 4px 20px rgba(30,20,50,.04);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #eeeaf2;
            display: flex;
            align-items: center;
            gap: 13px;
        }
        .card-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7627c9;
            background: #f4ecfb;
            flex-shrink: 0;
        }
        .card-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: #292430;
            margin: 0 0 2px 0;
        }
        .card-header p {
            color: #817987;
            font-size: 12px;
            margin: 0;
        }
        .card-body { padding: 24px; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #443c4c;
            margin-bottom: 8px;
        }
        .required { color: #c52b9f; }
        .form-control {
            width: 100%;
            height: 44px;
            border: 1px solid #e3dfea;
            border-radius: 9px;
            padding: 0 13px;
            outline: none;
            color: #292430;
            background: #ffffff;
            font-size: 13px;
            box-sizing: border-box;
            transition: border .2s ease, box-shadow .2s ease;
        }
        textarea.form-control {
            height: 100px;
            padding: 12px 13px;
            resize: vertical;
        }
        .form-control:focus {
            border-color: #9b48d1;
            box-shadow: 0 0 0 3px rgba(155,72,209,.10);
        }
        .form-help {
            color: #8b8392;
            font-size: 11px;
            margin-top: 6px;
        }

        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #9b91a1;
            font-size: 13px;
            pointer-events: none;
        }
        .input-wrapper .form-control { padding-left: 37px; }

        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 14px 0;
            border-bottom: 1px solid #f0edf3;
        }
        .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
        .toggle-row:first-child { padding-top: 0; }
        .toggle-info {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .toggle-info-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f6f1fa;
            color: #7627c9;
            flex-shrink: 0;
        }
        .toggle-text strong {
            display: block;
            font-size: 13px;
            color: #332d39;
            margin-bottom: 3px;
        }
        .toggle-text span {
            display: block;
            font-size: 11px;
            color: #8a8290;
        }
        .switch {
            position: relative;
            width: 46px;
            height: 24px;
            flex-shrink: 0;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute;
            inset: 0;
            background: #d8d2dc;
            border-radius: 999px;
            transition: .25s;
            cursor: pointer;
        }
        .slider:before {
            content: "";
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            top: 3px;
            background: #ffffff;
            border-radius: 50%;
            transition: .25s;
        }
        .switch input:checked + .slider {
            background: linear-gradient(135deg, #7627c9, #c52b9f);
        }
        .switch input:checked + .slider:before {
            transform: translateX(22px);
        }

        .info-card {
            background: #ffffff;
            border: 1px solid #eee9f4;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(30,20,50,.04);
            margin-bottom: 20px;
        }
        .info-card h3 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 14px 0;
            color: #332d39;
        }
        .info-list { display: flex; flex-direction: column; gap: 11px; }
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #716979;
            font-size: 12px;
            line-height: 1.5;
        }
        .info-item i { color: #9b48d1; margin-top: 2px; }

        .payment-notice {
            border: 1px solid #eadcf4;
            background: #fbf7fe;
            border-radius: 11px;
            padding: 13px 15px;
            display: flex;
            gap: 11px;
            align-items: flex-start;
            margin-top: 16px;
        }
        .payment-notice i { color: #7627c9; margin-top: 2px; }
        .payment-notice strong { display: block; font-size: 12px; color: #43394b; margin-bottom: 2px; }
        .payment-notice span { display: block; font-size: 11px; color: #817987; }

        .save-bar {
            position: sticky;
            bottom: 18px;
            z-index: 20;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(10px);
            border: 1px solid #e9e2ef;
            border-radius: 14px;
            padding: 12px 16px;
            box-shadow: 0 10px 25px rgba(30,20,50,.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        .save-status { color: #766d7e; font-size: 12px; }
        .save-status i { color: #55a66f; margin-right: 5px; }
        .save-btn {
            border: none;
            height: 42px;
            padding: 0 22px;
            border-radius: 10px;
            color: #ffffff;
            background: linear-gradient(135deg, #7627c9, #c52b9f);
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(118,39,201,.25);
            transition: all .2s ease;
        }
        .save-btn:hover { transform: translateY(-1px); }

        .quick-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0edf3;
            color: #5f5867;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }
        .quick-link:last-child { border-bottom: none; padding-bottom: 0; }
        .quick-link:hover { color: #7627c9; }
        #overlaper{
            width:100vw;
            margin-left: 20%;
        }

        /* FIXED RESPONSIVE MEDIA QUERIES FOR MOBILE & TABLETS */
        @media (max-width: 992px) {
            .admin-wrapper {
                flex-direction: column !important;
            }
            .settings-content-body {
                padding: 16px !important;
                width: 100% !important;
            }
            .settings-layout {
                grid-template-columns: 1fr !important;
            }
            #overlaper{
                width: 100vw;
                margin-left: 0;
                margin-top: 50px;
                
            }
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr !important;
            }
            .form-group.full {
                grid-column: auto !important;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .save-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .save-btn {
                width: 100%;
            }

        }
    </style>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- LINKED SIDEBAR -->
    <?php
    if (file_exists(__DIR__ . '/sidebar.php')) {
        include __DIR__ . '/sidebar.php';
    } elseif (file_exists(__DIR__ . '/includes/sidebar.php')) {
        include __DIR__ . '/includes/sidebar.php';
    } elseif (file_exists(__DIR__ . '/../includes/sidebar.php')) {
        include __DIR__ . '/../includes/sidebar.php';
    }
    ?>

    <!-- MAIN SETTINGS PANEL -->
     <div id="overlaper" >
            <main class="settings-content-body">
        <!-- HEADER -->
        <header class="page-header">
            <div class="page-title">
                <h1>Store Settings</h1>
                <p>Manage your store information, contact details and preferences</p>
            </div>
            <button type="button" class="header-refresh-btn" onclick="window.location.reload();">
                <i class="fa-solid fa-rotate-right"></i>
                Refresh
            </button>
        </header>

        <!-- ALERTS -->
        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($successMessage); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo e($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <!-- LAYOUT -->
        <div class="settings-layout">
            <div class="settings-main">
                <!-- STORE INFORMATION -->
                <section class="settings-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fa-solid fa-store"></i></div>
                        <div>
                            <h2>Store Information</h2>
                            <p>Basic information displayed across your storefront</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="store_name">Store Name <span class="required">*</span></label>
                                <input type="text" id="store_name" name="store_name" class="form-control" form="settingsForm" maxlength="150" required value="<?php echo e($settings['store_name']); ?>" placeholder="Ssrini Handicrafts" autocomplete="organization">
                            </div>
                            <div class="form-group">
                                <label for="tagline">Store Tagline</label>
                                <input type="text" id="tagline" name="tagline" class="form-control" form="settingsForm" maxlength="255" value="<?php echo e($settings['tagline']); ?>" placeholder="Authentic handcrafted products from Bengal" autocomplete="off">
                            </div>
                            <div class="form-group full">
                                <label for="address">Store Address</label>
                                <textarea id="address" name="address" class="form-control" form="settingsForm" placeholder="Enter complete store address..." autocomplete="street-address"><?php echo e($settings['address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" class="form-control" form="settingsForm" maxlength="100" value="<?php echo e($settings['city']); ?>" placeholder="Kolkata" autocomplete="address-level2">
                            </div>
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" class="form-control" form="settingsForm" maxlength="100" value="<?php echo e($settings['state']); ?>" placeholder="West Bengal" autocomplete="address-level1">
                            </div>
                            <div class="form-group">
                                <label for="pincode">PIN Code</label>
                                <input type="text" id="pincode" name="pincode" class="form-control" form="settingsForm" maxlength="20" value="<?php echo e($settings['pincode']); ?>" placeholder="700001" autocomplete="postal-code">
                            </div>
                        </div>
                    </div>
                </section>

                <!-- CONTACT INFORMATION -->
                <section class="settings-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fa-solid fa-address-book"></i></div>
                        <div>
                            <h2>Contact Information</h2>
                            <p>Customer support and communication details</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" class="form-control" form="settingsForm" maxlength="150" value="<?php echo e($settings['email']); ?>" placeholder="contact@example.com" autocomplete="email">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-phone input-icon"></i>
                                    <input type="text" id="phone" name="phone" class="form-control" form="settingsForm" maxlength="30" value="<?php echo e($settings['phone']); ?>" placeholder="+91 XXXXX XXXXX" autocomplete="tel">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="whatsapp">WhatsApp Number</label>
                                <div class="input-wrapper">
                                    <i class="fa-brands fa-whatsapp input-icon"></i>
                                    <input type="text" id="whatsapp" name="whatsapp" class="form-control" form="settingsForm" maxlength="30" value="<?php echo e($settings['whatsapp']); ?>" placeholder="+91 XXXXX XXXXX" autocomplete="tel">
                                </div>
                                <span class="form-help">Used for quick customer communication.</span>
                            </div>
                            <div class="form-group">
                                <label for="website_url">Website URL</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-globe input-icon"></i>
                                    <input type="url" id="website_url" name="website_url" class="form-control" form="settingsForm" maxlength="500" value="<?php echo e($settings['website_url']); ?>" placeholder="https://example.com" autocomplete="url">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SOCIAL MEDIA -->
                <section class="settings-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fa-solid fa-share-nodes"></i></div>
                        <div>
                            <h2>Social Media</h2>
                            <p>Add your official social media profile links</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="instagram_url">Instagram</label>
                                <div class="input-wrapper">
                                    <i class="fa-brands fa-instagram input-icon"></i>
                                    <input type="url" id="instagram_url" name="instagram_url" class="form-control" form="settingsForm" maxlength="500" value="<?php echo e($settings['instagram_url']); ?>" placeholder="https://instagram.com/..." autocomplete="url">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="facebook_url">Facebook</label>
                                <div class="input-wrapper">
                                    <i class="fa-brands fa-facebook-f input-icon"></i>
                                    <input type="url" id="facebook_url" name="facebook_url" class="form-control" form="settingsForm" maxlength="500" value="<?php echo e($settings['facebook_url']); ?>" placeholder="https://facebook.com/..." autocomplete="url">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="twitter_url">X / Twitter</label>
                                <div class="input-wrapper">
                                    <i class="fa-brands fa-x-twitter input-icon"></i>
                                    <input type="url" id="twitter_url" name="twitter_url" class="form-control" form="settingsForm" maxlength="500" value="<?php echo e($settings['twitter_url']); ?>" placeholder="https://x.com/..." autocomplete="url">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SHIPPING & PRICING -->
                <section class="settings-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div>
                            <h2>Shipping & Pricing</h2>
                            <p>Configure basic shipping behaviour for your store</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <select id="currency" name="currency" class="form-control" form="settingsForm" autocomplete="off">
                                    <option value="INR" <?php echo $settings['currency'] === 'INR' ? 'selected' : ''; ?>>INR - Indian Rupee</option>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                    <option value="GBP" <?php echo $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP - Pound</option>
                                    <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="shipping_charge">Standard Shipping Charge</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-indian-rupee-sign input-icon"></i>
                                    <input type="number" id="shipping_charge" name="shipping_charge" class="form-control" form="settingsForm" min="0" step="0.01" value="<?php echo e($settings['shipping_charge']); ?>" placeholder="0.00" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="free_shipping_min">Free Shipping Above</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-bag-shopping input-icon"></i>
                                    <input type="number" id="free_shipping_min" name="free_shipping_min" class="form-control" form="settingsForm" min="0" step="0.01" value="<?php echo e($settings['free_shipping_min']); ?>" placeholder="0.00" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="low_stock_threshold">Low Stock Threshold</label>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-boxes-stacked input-icon"></i>
                                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" form="settingsForm" min="0" step="1" value="<?php echo e($settings['low_stock_threshold']); ?>" placeholder="5" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STORE OPTIONS -->
                <section class="settings-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fa-solid fa-sliders"></i></div>
                        <div>
                            <h2>Store Options</h2>
                            <p>Control payment and store availability options</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <div class="toggle-info-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                                <div class="toggle-text">
                                    <strong>Cash On Delivery</strong>
                                    <span>Allow customers to place COD orders.</span>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="cod_enabled" form="settingsForm" value="1" <?php echo checkedValue($settings['cod_enabled']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-info">
                                <div class="toggle-info-icon"><i class="fa-solid fa-credit-card"></i></div>
                                <div class="toggle-text">
                                    <strong>Online Payment</strong>
                                    <span>Keep disabled until the online payment gateway is implemented.</span>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="online_payment_enabled" form="settingsForm" value="1" <?php echo checkedValue($settings['online_payment_enabled']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <div class="payment-notice">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <strong>Online Payment Status</strong>
                                <span>Online payment is not available for now.</span>
                            </div>
                        </div>
                        <div class="toggle-row" style="margin-top:16px;">
                            <div class="toggle-info">
                                <div class="toggle-info-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                                <div class="toggle-text">
                                    <strong>Maintenance Mode</strong>
                                    <span>Enable this when the storefront needs temporary maintenance.</span>
                                </div>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="maintenance_mode" form="settingsForm" value="1" <?php echo checkedValue($settings['maintenance_mode']); ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                </section>

                <!-- FORM & SAVE BAR -->
                <form id="settingsForm" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                </form>

                <div class="save-bar">
                    <div class="save-status">
                        <i class="fa-solid fa-circle-check"></i>
                        Changes are saved to database
                    </div>
                    <button type="submit" form="settingsForm" class="save-btn" id="saveButton">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span id="saveButtonText">Save Settings</span>
                    </button>
                </div>
            </div>

            <!-- RIGHT SIDEBAR -->
            <aside class="settings-sidebar">
                <div class="info-card">
                    <h3>Settings Overview</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <i class="fa-solid fa-database"></i>
                            <span>Settings are stored in <strong>store_settings</strong> table.</span>
                        </div>
                        <div class="info-item">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Only authenticated administrators can update settings.</span>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3>Quick Navigation</h3>
                    <a href="index.php" class="quick-link"><span>Dashboard</span><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="products.php" class="quick-link"><span>Products</span><i class="fa-solid fa-chevron-right"></i></a>
                    <a href="orders.php" class="quick-link"><span>Orders</span><i class="fa-solid fa-chevron-right"></i></a>
                </div>
            </aside>
        </div>
    </main>

     </div>
</div>

</body>
</html>