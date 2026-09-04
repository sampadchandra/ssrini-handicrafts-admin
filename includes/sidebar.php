<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentStatus = $_GET['status'] ?? '';

/**
 * Helper function to check if current page matches target page(s)
 */
function isActivePage($pages, $currentPage) {
    if (is_array($pages)) {
        return in_array($currentPage, $pages, true);
    }
    return $currentPage === $pages;
}

$isOrdersPage = isActivePage(['orders.php', 'order-view.php', 'order-edit.php'], $currentPage);
?>

<!-- =================================================
     MOBILE SIDEBAR TOGGLE & OVERLAY
     ================================================= -->
<button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Open Sidebar">☰</button>
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

<aside class="sidebar" id="adminSidebar" aria-hidden="true"> 
    <!-- BRAND --> 
    <div class="sidebar-brand"> 
        <h2>Ssrini Handcrafts</h2> 
    </div> 

    <!-- NAVIGATION --> 
    <nav class="sidebar-nav"> 
        <!-- Dashboard --> 
        <div class="nav-section"> 
            <a href="index.php" class="nav-item <?= isActivePage(['index.php', 'dashboard.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📊</span> 
                <span>Dashboard</span> 
            </a> 
        </div> 

        <!-- SALES --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Sales</div> 
            
            <!-- DIRECT LINK FOR ORDERS (Guarantees navigation from any page) -->
            <a href="orders.php" class="nav-item <?= $isOrdersPage ? 'active' : '' ?>"> 
                <span class="nav-icon">📦</span> 
                <span class="nav-label">Orders</span> 
            </a> 
 
            <!-- SUBMENU: Auto-opens when on orders page -->
            <div class="nav-submenu <?= $isOrdersPage ? 'open' : '' ?>"> 
                <a href="orders.php?status=new" class="<?= ($currentPage === 'orders.php' && $currentStatus === 'new') ? 'sub-active' : '' ?>">New Orders</a> 
                <a href="orders.php" class="<?= ($currentPage === 'orders.php' && $currentStatus !== 'new') ? 'sub-active' : '' ?>">All Orders</a> 
            </div> 
        </div> 

        <!-- CATALOGUE --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Catalogue</div> 
            
            <a href="products.php" class="nav-item <?= isActivePage(['products.php', 'product-add.php', 'product-edit.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🛍️</span> 
                <span>Products</span> 
            </a> 

            <a href="invoices.php" class="nav-item <?= isActivePage(['invoices.php', 'invoice-view.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🧾</span> 
                <span>Invoices</span> 
            </a> 
        </div> 

        <!-- WEBSITE --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Website</div> 

            <a href="filter-configuration.php" class="nav-item <?= isActivePage(['filter-configuration.php', 'filter-config.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🔍</span> 
                <span>Filter Configuration</span> 
            </a> 

            <a href="front-page-content.php" class="nav-item <?= isActivePage(['front-page-content.php', 'front-content.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🏠</span> 
                <span>Front Page Content</span> 
            </a> 

            <a href="about-details.php" class="nav-item <?= isActivePage(['about-details.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">ℹ️</span> 
                <span>About Details</span> 
            </a> 
        </div> 

        <!-- MANAGEMENT --> 
        <div class="nav-section"> 
            <div class="nav-section-title">Management</div> 

            <a href="customers.php" class="nav-item <?= isActivePage(['customers.php', 'customer-view.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">👥</span> 
                <span>Customers</span> 
            </a> 

            <a href="reviews.php" class="nav-item <?= isActivePage(['reviews.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">⭐</span> 
                <span>Reviews</span> 
            </a> 

            <a href="notifications.php" class="nav-item <?= isActivePage(['notifications.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">🔔</span> 
                <span>Notifications</span> 
            </a> 

            <a href="analytics.php" class="nav-item <?= isActivePage(['analytics.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📈</span> 
                <span>Analytics</span> 
            </a> 

            <a href="activity-logs.php" class="nav-item <?= isActivePage(['activity-logs.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">📝</span> 
                <span>Activity Logs</span> 
            </a> 

            <a href="settings.php" class="nav-item <?= isActivePage(['settings.php'], $currentPage) ? 'active' : '' ?>"> 
                <span class="nav-icon">⚙️</span> 
                <span>Settings</span> 
            </a> 
        </div> 
    </nav> 

    <!-- LOGOUT --> 
    <div class="sidebar-footer"> 
        <a href="logout.php" class="nav-item logout-link"> 
            <span>Logout</span> 
        </a> 
    </div> 
</aside> 

<!-- =================================================
     SIDEBAR STYLES
     ================================================= -->
<style>
    .nav-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        text-decoration: none;
        color: #475569;
        font-weight: 500;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer !important;
        pointer-events: auto !important;
    }

    .nav-item:hover, .nav-item.active {
        background: rgba(124, 58, 237, 0.1);
        color: #7c3aed;
        font-weight: 600;
    }

    .nav-submenu {
        display: none;
        padding-left: 38px;
        flex-direction: column;
        gap: 4px;
        margin-top: 4px;
    }

    .nav-submenu.open {
        display: flex;
    }

    .nav-submenu a {
        padding: 6px 12px;
        font-size: 13px;
        color: #64748b;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .nav-submenu a:hover,
    .nav-submenu a.sub-active {
        color: #7c3aed;
        background: rgba(124, 58, 237, 0.08);
        font-weight: 600;
    }

    /* Mobile Sidebar Toggle */
    .mobile-sidebar-toggle {
        display: none;
        position: fixed;
        top: 14px;
        left: 14px;
        z-index: 10001;
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 12px;
        background: #ffffff;
        color: #7c3aed;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        font-size: 22px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
    }

    .mobile-sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(15, 23, 42, 0.45);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    @media (max-width: 768px) {
        .mobile-sidebar-toggle { display: flex; }
        .sidebar {
            position: fixed !important;
            top: 0; left: 0; bottom: 0;
            width: min(290px, 82vw) !important;
            z-index: 9999;
            transform: translateX(-105%);
            transition: transform 0.28s ease;
            overflow-y: auto;
            box-shadow: 10px 0 35px rgba(0, 0, 0, 0.18);
        }
        .sidebar.mobile-open { transform: translateX(0); }
        .mobile-sidebar-overlay { display: block; pointer-events: none; }
        body.sidebar-mobile-open .mobile-sidebar-overlay { opacity: 1; pointer-events: auto; }
        body.sidebar-mobile-open { overflow: hidden; }
        .main-area { width: 100% !important; margin-left: 0 !important; }
    }
</style>

<!-- =================================================
     SIDEBAR JAVASCRIPT
     ================================================= -->
<script> 
document.addEventListener('DOMContentLoaded', function () { 
    const sidebar = document.getElementById('adminSidebar');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('mobile-open');
        document.body.classList.add('sidebar-mobile-open');
        if (mobileSidebarToggle) mobileSidebarToggle.innerHTML = '✕';
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('mobile-open');
        document.body.classList.remove('sidebar-mobile-open');
        if (mobileSidebarToggle) mobileSidebarToggle.innerHTML = '☰';
    }

    if (mobileSidebarToggle) {
        mobileSidebarToggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('mobile-open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });
    }

    if (mobileSidebarOverlay) {
        mobileSidebarOverlay.addEventListener('click', closeMobileSidebar);
    }

    const navigationLinks = document.querySelectorAll('.sidebar a');
    navigationLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) closeMobileSidebar();
        });
    });
}); 
</script>