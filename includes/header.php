<?php

/**
 * Ssrini Handicrafts
 * Admin Header
 */

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$pageTitles = [
    'dashboard.php' => 'Dashboard',
    'activity-logs.php' => 'Activity Logs',
    'customers.php' => 'Customers',
    'customer-details.php' => 'Customer Details',
    'products.php' => 'Products',
    'add-product.php' => 'Add Product',
    'edit-product.php' => 'Edit Product',
    'orders.php' => 'Orders',
    'order-details.php' => 'Order Details',
    'invoices.php' => 'Invoices',
    'reviews.php' => 'Reviews',
    'notifications.php' => 'Notifications',
    'filter-configuration.php' => 'Filter Configuration',
    'front-page-content.php' => 'Front Page Content',
    'about-details.php' => 'About Details'
];

if(empty($pageTitle) || $pageTitle === 'Dashboard') {
    $pageTitle = $pageTitles[$currentPage] ?? 'Dashboard';
}

?>

<header class="top-header">

    <div class="header-left">

        <button
            type="button"
            class="header-button mobile-menu-button"
            id="mobileMenuButton"
            aria-label="Open navigation"
            aria-expanded="false"
            aria-controls="adminSidebar"
        >
            <span class="menu-icon">☰</span>
        </button>

        <h1><?=htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8')?></h1>

    </div>

    <div class="header-right">

        <button
            type="button"
            class="header-button"
            id="refreshButton"
            title="Refresh page"
        >
            🔄
            <span>Refresh</span>
        </button>

        <div class="profile">

            <div class="profile-avatar">
                <img
                    src="../assets/images/logo.jpg"
                    alt="Ssrini Handicrafts"
                    style="border-radius:30%;"
                >
            </div>

            <div class="profile-info">

                <span class="profile-name">
                    <?=htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8')?>
                </span>

                <span class="profile-role">
                    <?=htmlspecialchars(ucfirst($_SESSION['admin_role'] ?? 'admin'), ENT_QUOTES, 'UTF-8')?>
                </span>

            </div>

        </div>

    </div>

</header>