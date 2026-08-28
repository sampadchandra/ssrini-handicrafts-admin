<?php 
 
/** 
 * ========================================================= 
 * SSRINI HANDICRAFTS 
 * ADMIN DASHBOARD 
 * ========================================================= 
 */ 
 
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/auth.php'; 
 
requireAdminLogin(); 
 
 
/** 
 * Page title 
 */ 
$pageTitle = 'Dashboard'; 
 
?> 
 
 
<!DOCTYPE html> 
 
<html lang="en"> 
 
<head> 
 
    <meta charset="UTF-8"> 
 
    <meta 
        name="viewport" 
        content="width=device-width, initial-scale=1.0" 
    > 
 
    <meta 
        name="description" 
        content="Ssrini Handicrafts Admin Panel" 
    > 
 
    <title> 
        <?= htmlspecialchars( 
            $pageTitle, 
            ENT_QUOTES, 
            'UTF-8' 
        ) ?> 
        | Ssrini Handicrafts 
    </title> 
 
 
    <!-- 
        Main Admin CSS 
    --> 
 
    <link 
        rel="stylesheet" 
        href="../assets/css/admin.css" 
    > 
 
</head> 
 
 
<body> 
 
 
<div class="admin-wrapper"> 
 
 
    <!-- ================================================= 
         SIDEBAR 
         ================================================= --> 
 
    <?php 
 
    require_once __DIR__ . 
        '/../includes/sidebar.php'; 
 
    ?> 
 
 
    <!-- ================================================= 
         MAIN AREA 
         ================================================= --> 
 
    <main class="main-area"> 
 
 
        <!-- ============================================= 
             TOP HEADER 
             ============================================= --> 
 
        <?php 
 
        require_once __DIR__ . 
            '/../includes/header.php'; 
 
        ?> 
 
 
        <!-- ============================================= 
             PAGE CONTENT 
             ============================================= --> 
 
        <div class="page-content"> 
 
 
            <!-- ========================================= 
                 PAGE HEADER 
                 ========================================= --> 
 
            <section class="page-header"> 
 
 
                <div> 
 
                    <h1 class="page-title"> 
 
                        Dashboard 
 
                    </h1> 
 
 
                    <p class="page-description"> 
 
                        Welcome back, 
                        <?= htmlspecialchars( 
                            $_SESSION['admin_name'] ?? 'Admin', 
                            ENT_QUOTES, 
                            'UTF-8' 
                        ) ?>. 
 
                        Here's what's happening 
                        with your store. 
 
                    </p> 
 
                </div> 
 
 
                <div> 
 
                    <button 
                        type="button" 
                        class="btn btn-primary" 
                        id="dashboardRefreshButton" 
                    > 
 
                        🔄 
 
                        Refresh Dashboard 
 
                    </button> 
 
                </div> 
 
 
            </section> 
 
 
 
            <!-- ========================================= 
                 STATISTICS 
                 ========================================= --> 
 
            <section class="stats-grid"> 
 
 
                <!-- TOTAL ORDERS --> 
 
                <div class="card stat-card card-hover"> 
 
                    <div class="stat-icon"> 
 
                        📦 
 
                    </div> 
 
 
                    <div class="stat-label"> 
 
                        Total Orders 
 
                    </div> 
 
 
                    <div 
                        class="stat-value" 
                        id="totalOrders" 
                    > 
 
                        0 
 
                    </div> 
 
 
                    <div class="stat-change"> 
 
                        All orders 
 
                    </div> 
 
                </div> 
 
 
 
                <!-- PRODUCTS --> 
 
                <div class="card stat-card card-hover"> 
 
                    <div class="stat-icon"> 
 
                        🛍️ 
 
                    </div> 
 
 
                    <div class="stat-label"> 
 
                        Products 
 
                    </div> 
 
 
                    <div 
                        class="stat-value" 
                        id="totalProducts" 
                    > 
 
                        0 
 
                    </div> 
 
 
                    <div class="stat-change"> 
 
                        Active inventory 
 
                    </div> 
 
                </div> 
 
 
 
                <!-- CUSTOMERS --> 
 
                <div class="card stat-card card-hover"> 
 
                    <div class="stat-icon"> 
 
                        👥 
 
                    </div> 
 
 
                    <div class="stat-label"> 
 
                        Customers 
 
                    </div> 
 
 
                    <div 
                        class="stat-value" 
                        id="totalCustomers" 
                    > 
 
                        0 
 
                    </div> 
 
 
                    <div class="stat-change"> 
 
                        Registered customers 
 
                    </div> 
 
                </div> 
 
 
 
                <!-- REVENUE --> 
 
                <div class="card stat-card card-hover"> 
 
                    <div class="stat-icon"> 
 
                        ₹ 
 
                    </div> 
 
 
                    <div class="stat-label"> 
 
                        Revenue 
 
                    </div> 
 
 
                    <div 
                        class="stat-value" 
                        id="totalRevenue" 
                    > 
 
                        ₹0 
 
                    </div> 
 
 
                    <div class="stat-change"> 
 
                        Total sales 
 
                    </div> 
 
                </div> 
 
 
            </section> 
 
 
 
            <!-- ========================================= 
                 DASHBOARD GRID 
                 ========================================= --> 
 
            <section 
                style=" 
                    display: grid; 
                    grid-template-columns: 
                        minmax(0, 2fr) 
                        minmax(280px, 1fr); 
                    gap: 20px; 
                " 
            > 
 
 
                <!-- ===================================== 
                     RECENT ORDERS 
                     ===================================== --> 
 
                <div class="table-card"> 
 
 
                    <div class="table-header"> 
 
 
                        <div> 
 
                            <div class="table-title"> 
 
                                Recent Orders 
 
                            </div> 
 
 
                            <p 
                                style=" 
                                    margin-top: 4px; 
                                    color: var(--text-muted); 
                                    font-size: 11px; 
                                " 
                            > 
 
                                Latest orders from your store 
 
                            </p> 
 
                        </div> 
 
 
                        <a 
                            href="#" 
                            class="btn btn-secondary" 
                        > 
 
                            View All 
 
                        </a> 
 
                    </div> 
 
 
                    <div class="table-wrapper"> 
 
 
                        <table class="admin-table"> 
 
 
                            <thead> 
 
                                <tr> 
 
                                    <th> 
                                        Order 
                                    </th> 
 
                                    <th> 
                                        Customer 
                                    </th> 
 
                                    <th> 
                                        Amount 
                                    </th> 
 
                                    <th> 
                                        Status 
                                    </th> 
 
                                    <th> 
                                        Date 
                                    </th> 
 
                                </tr> 
 
                            </thead> 
 
 
                            <tbody> 
 
 
                                <!-- 
                                    Temporary empty state. 
 
                                    Real orders will come 
                                    from MySQL later. 
                                --> 
 
                                <tr> 
 
                                    <td 
                                        colspan="5" 
                                        style=" 
                                            padding: 0; 
                                        " 
                                    > 
 
                                        <div class="empty-state"> 
 
                                            <div 
                                                class="empty-state-icon" 
                                            > 
 
                                                📦 
 
                                            </div> 
 
 
                                            <h3> 
 
                                                No orders yet 
 
                                            </h3> 
 
 
                                            <p> 
 
                                                New customer 
                                                orders will 
                                                appear here. 
 
                                            </p> 
 
                                        </div> 
 
                                    </td> 
 
                                </tr> 
 
 
                            </tbody> 
 
 
                        </table> 
 
 
                    </div> 
 
 
                </div> 
 
 
 
                <!-- ===================================== 
                     QUICK ACTIONS 
                     ===================================== --> 
 
                <div class="card"> 
 
 
                    <div 
                        style=" 
                            padding: 20px; 
                            border-bottom: 
                                1px solid 
                                var(--border-light); 
                        " 
                    > 
 
                        <div class="table-title"> 
 
                            Quick Actions 
 
                        </div> 
 
 
                        <p 
                            style=" 
                                margin-top: 4px; 
                                color: var(--text-muted); 
                                font-size: 11px; 
                            " 
                        > 
 
                            Frequently used actions 
 
                        </p> 
 
                    </div> 
 
 
                    <div 
                        style=" 
                            padding: 16px; 
                            display: flex; 
                            flex-direction: column; 
                            gap: 10px; 
                        " 
                    > 
 
 
                        <!-- ADD PRODUCT --> 
 
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            data-action="add-product" 
                            onclick="window.location.href='products.php';" 
                        > 
 
                            🛍️ 
 
                            Add Product 
 
                        </button> 
 
 
 
                        <!-- NEW ORDER --> 
 
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            data-action="new-order" 
                        > 
 
                            📦 
 
                            New Order 
 
                        </button> 
 
 
 
                        <!-- CREATE INVOICE --> 
 
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            data-action="create-invoice" 
                        > 
 
                            🧾 
 
                            Create Invoice 
 
                        </button> 
 
 
 
                        <!-- ADD CATEGORY --> 
 
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            data-action="add-category" 
                        > 
 
                            ➕ 
 
                            Add Category 
 
                        </button> 
 
 
                    </div> 
 
 
                </div> 
 
 
            </section> 
 
 
 
            <!-- ========================================= 
                 STORE STATUS 
                 ========================================= --> 
 
            <section 
                style=" 
                    margin-top: 20px; 
                " 
            > 
 
                <div class="card"> 
 
 
                    <div 
                        style=" 
                            padding: 20px; 
                        " 
                    > 
 
 
                        <div 
                            style=" 
                                display: flex; 
                                align-items: center; 
                                justify-content: 
                                    space-between; 
                                gap: 15px; 
                                flex-wrap: wrap; 
                            " 
                        > 
 
 
                            <div> 
 
 
                                <div class="table-title"> 
 
                                    Store Status 
 
                                </div> 
 
 
                                <p 
                                    style=" 
                                        margin-top: 5px; 
                                        color: 
                                            var(--text-secondary); 
                                        font-size: 12px; 
                                    " 
                                > 
 
                                    Your current store 
                                    configuration. 
 
                                </p> 
 
                            </div> 
 
 
                            <span 
                                class="badge badge-success" 
                            > 
 
                                ● Store Active 
 
                            </span> 
 
 
                        </div> 
 
 
                        <div 
                            style=" 
                                display: grid; 
                                grid-template-columns: 
                                    repeat( 
                                        3, 
                                        minmax(0, 1fr) 
                                    ); 
                                gap: 14px; 
                                margin-top: 20px; 
                            " 
                        > 
 
 
                            <!-- COD --> 
 
                            <div 
                                style=" 
                                    padding: 15px; 
                                    border: 
                                        1px solid 
                                        var(--border-light); 
                                    border-radius: 
                                        var(--radius-md); 
                                    background: 
                                        var(--surface-soft); 
                                " 
                            > 
 
                                <div 
                                    style=" 
                                        font-size: 20px; 
                                        margin-bottom: 8px; 
                                    " 
                                > 
 
                                    💵 
 
                                </div> 
 
 
                                <strong 
                                    style=" 
                                        font-size: 13px; 
                                    " 
                                > 
 
                                    Cash on Delivery 
 
                                </strong> 
 
 
                                <div 
                                    style=" 
                                        margin-top: 5px; 
                                        font-size: 11px; 
                                        color: 
                                            var(--success); 
                                    " 
                                > 
 
                                    Available 
 
                                </div> 
 
                            </div> 
 
 
 
                            <!-- ONLINE PAYMENT --> 
 
                            <div 
                                style=" 
                                    padding: 15px; 
                                    border: 
                                        1px solid 
                                        var(--border-light); 
                                    border-radius: 
                                        var(--radius-md); 
                                    background: 
                                        var(--surface-soft); 
                                " 
                            > 
 
                                <div 
                                    style=" 
                                        font-size: 20px; 
                                        margin-bottom: 8px; 
                                    " 
                                > 
 
                                    💳 
 
                                </div> 
 
 
                                <strong 
                                    style=" 
                                        font-size: 13px; 
                                    " 
                                > 
 
                                    Online Payment 
 
                                </strong> 
 
 
                                <div 
                                    style=" 
                                        margin-top: 5px; 
                                        font-size: 11px; 
                                        color: 
                                            var(--warning); 
                                    " 
                                > 
 
                                    Coming Soon 
 
                                </div> 
 
                            </div> 
 
 
 
                            <!-- WEBSITE --> 
 
                            <div 
                                style=" 
                                    padding: 15px; 
                                    border: 
                                        1px solid 
                                        var(--border-light); 
                                    border-radius: 
                                        var(--radius-md); 
                                    background: 
                                        var(--surface-soft); 
                                " 
                            > 
 
                                <div 
                                    style=" 
                                        font-size: 20px; 
                                        margin-bottom: 8px; 
                                    " 
                                > 
 
                                    🌐 
 
                                </div> 
 
 
                                <strong 
                                    style=" 
                                        font-size: 13px; 
                                    " 
                                > 
 
                                    Website 
 
                                </strong> 
 
 
                                <div 
                                    style=" 
                                        margin-top: 5px; 
                                        font-size: 11px; 
                                        color: 
                                            var(--success); 
                                    " 
                                > 
 
                                    Connected Soon 
 
                                </div> 
 
                            </div> 
 
 
                        </div> 
 
 
                    </div> 
 
 
                </div> 
 
            </section> 
 
 
        </div> 
 
 
    </main> 
 
 
</div> 