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


    <!-- =================================================
         DASHBOARD RESPONSIVE CSS
         Only affects small/mobile screens.
         Desktop design remains unchanged.
         ================================================= -->

    <style>

        /* -----------------------------------------------
           GLOBAL MOBILE SAFETY
           ----------------------------------------------- */

        html,
        body {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        /* -----------------------------------------------
           MOBILE SIDEBAR CONTROL
           ----------------------------------------------- */

        .mobile-menu-button {
            display: none;
        }

        .mobile-sidebar-overlay {
            display: none;
        }


        /* -----------------------------------------------
           HERO BANNER
           ----------------------------------------------- */

        .craft-hero {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .craft-hero-content {
            min-width: 0;
        }

        .craft-hero-art {
            max-width: 100%;
            overflow: hidden;
        }

        .craft-hero-art img {
            display: block;
            max-width: 100%;
            height: auto;
        }


        /* -----------------------------------------------
           DASHBOARD GRID
           Recent Orders + Quick Actions
           ----------------------------------------------- */

        .page-content > section[style*="grid-template-columns"] {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }


        /* -----------------------------------------------
           TABLE SAFETY
           ----------------------------------------------- */

        .table-wrapper {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .admin-table {
            min-width: 650px;
        }


        /* -----------------------------------------------
           MOBILE
           ----------------------------------------------- */

        @media (max-width: 768px) {


            /* -------------------------------------------
               MOBILE MENU BUTTON
               ------------------------------------------- */

            .mobile-menu-button {
                display: flex !important;
                position: fixed;
                top: 14px;
                left: 14px;
                width: 42px;
                height: 42px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border-light);
                border-radius: 10px;
                background: var(--surface);
                color: var(--text-primary);
                box-shadow: 0 5px 18px rgba(0, 0, 0, 0.12);
                cursor: pointer;
                z-index: 10002;
                font-size: 20px;
                line-height: 1;
                padding: 0;
            }

            .mobile-menu-button:active {
                transform: scale(0.96);
            }


            /* -------------------------------------------
               MOBILE SIDEBAR
               ------------------------------------------- */

            .admin-wrapper {
                width: 100%;
                max-width: 100%;
                min-height: 100vh;
            }

            .admin-wrapper > aside,
            .admin-wrapper .sidebar,
            .admin-wrapper .admin-sidebar,
            .admin-wrapper .side-bar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                bottom: 0 !important;
                width: min(290px, 84vw) !important;
                max-width: 84vw !important;
                height: 100vh !important;
                max-height: 100vh !important;
                z-index: 10001 !important;
                transform: translateX(-105%) !important;
                transition: transform 0.28s ease !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                box-shadow: 12px 0 35px rgba(0, 0, 0, 0.15);
            }

            .admin-wrapper > aside.mobile-sidebar-open,
            .admin-wrapper .sidebar.mobile-sidebar-open,
            .admin-wrapper .admin-sidebar.mobile-sidebar-open,
            .admin-wrapper .side-bar.mobile-sidebar-open {
                transform: translateX(0) !important;
            }


            /* -------------------------------------------
               SIDEBAR OVERLAY
               ------------------------------------------- */

            .mobile-sidebar-overlay {
                position: fixed;
                inset: 0;
                display: block;
                background: rgba(0, 0, 0, 0.35);
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition:
                    opacity 0.28s ease,
                    visibility 0.28s ease;
                z-index: 10000;
            }

            .mobile-sidebar-overlay.active {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }


            /* -------------------------------------------
               MAIN AREA
               ------------------------------------------- */

            .main-area {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin-left: 0 !important;
            }


            /* -------------------------------------------
               HEADER
               ------------------------------------------- */

            .top-header {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                padding-left: 68px !important;
                padding-right: 12px !important;
            }

            .header-left,
            .header-right {
                min-width: 0;
            }

            .header-right {
                max-width: 100%;
            }

            .profile {
                min-width: 0;
            }

            .profile-info {
                min-width: 0;
            }


            /* -------------------------------------------
               PAGE CONTENT
               ------------------------------------------- */

            .page-content {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                padding: 18px 14px !important;
                box-sizing: border-box;
            }


            /* -------------------------------------------
               PAGE HEADER
               ------------------------------------------- */

            .page-header {
                width: 100%;
                max-width: 100%;
            }


            /* -------------------------------------------
               HERO
               ------------------------------------------- */

            .craft-hero {
                width: 100%;
                min-width: 0;
                min-height: auto !important;
                margin: 0 0 18px 0 !important;
                border-radius: 18px !important;
                overflow: hidden;
                box-sizing: border-box;
            }

            .craft-hero-content {
                width: 100%;
                min-width: 0;
                padding: 24px 20px !important;
                box-sizing: border-box;
            }

            .craft-hero-content h1 {
                font-size: clamp(26px, 8vw, 42px) !important;
                line-height: 1.05 !important;
                word-break: normal;
                overflow-wrap: anywhere;
            }

            .craft-hero-content p {
                font-size: 13px !important;
                line-height: 1.5 !important;
            }

            .craft-hero-eyebrow {
                font-size: 13px !important;
            }

            .craft-hero-divider {
                max-width: 100%;
            }

            .craft-hero-art {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .craft-hero-art img {
                display: block;
                width: 100%;
                max-width: 100%;
                height: auto;
                object-fit: cover;
            }


            /* -------------------------------------------
               STATISTICS
               ------------------------------------------- */

            .stats-grid {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                grid-template-columns: 1fr !important;
                gap: 14px !important;
            }

            .stat-card {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                box-sizing: border-box;
            }


            /* -------------------------------------------
               RECENT ORDERS + QUICK ACTIONS
               ------------------------------------------- */

            .page-content > section[style*="grid-template-columns"] {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                gap: 16px !important;
                margin-top: 16px;
            }


            /* -------------------------------------------
               TABLE CARD
               ------------------------------------------- */

            .table-card {
                width: 100%;
                min-width: 0;
                max-width: 100%;
                box-sizing: border-box;
                overflow: hidden;
            }

            .table-header {
                padding: 16px !important;
                gap: 12px;
                flex-wrap: wrap;
            }

            .table-header > div {
                min-width: 0;
                max-width: 100%;
            }

            .table-header .btn {
                width: 100%;
                justify-content: center;
            }

            .table-title {
                font-size: 16px !important;
            }


            /* -------------------------------------------
               QUICK ACTIONS CARD
               ------------------------------------------- */

            .card {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }

            .card button {
                width: 100%;
                max-width: 100%;
            }


            /* -------------------------------------------
               STORE STATUS
               ------------------------------------------- */

            .page-content > section[style*="margin-top: 20px"] {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                margin-top: 16px !important;
            }

            .page-content > section[style*="margin-top: 20px"] .card {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .page-content > section[style*="margin-top: 20px"]
            [style*="repeat"] {
                grid-template-columns: 1fr !important;
                width: 100%;
                max-width: 100%;
            }


            /* -------------------------------------------
               STORE STATUS HEADER
               ------------------------------------------- */

            .page-content > section[style*="margin-top: 20px"]
            [style*="space-between"] {
                align-items: flex-start !important;
            }

            .page-content > section[style*="margin-top: 20px"]
            .badge {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
            }


            /* -------------------------------------------
               EMPTY STATE
               ------------------------------------------- */

            .empty-state {
                padding: 30px 16px !important;
            }


            /* -------------------------------------------
               BUTTONS
               ------------------------------------------- */

            .btn {
                min-height: 42px;
                box-sizing: border-box;
            }


        }


        /* -----------------------------------------------
           SMALL MOBILE DEVICES
           ----------------------------------------------- */

        @media (max-width: 480px) {


            .mobile-menu-button {
                top: 10px;
                left: 10px;
                width: 40px;
                height: 40px;
            }


            .top-header {
                padding-left: 60px !important;
                padding-right: 10px !important;
            }


            .page-content {
                padding: 14px 10px !important;
            }


            .craft-hero {
                border-radius: 16px !important;
            }


            .craft-hero-content {
                padding: 20px 16px !important;
            }


            .craft-hero-content h1 {
                font-size: 28px !important;
            }


            .craft-hero-content p {
                font-size: 12px !important;
            }


            .stats-grid {
                gap: 12px !important;
            }


            .stat-card {
                padding: 16px !important;
            }


            .table-header {
                padding: 14px !important;
            }


            .page-content > section[style*="margin-top: 20px"]
            .card > div {
                padding: 16px !important;
            }


        }


        /* =========================================================
   FINAL MOBILE SIDEBAR FIX
   ========================================================= */

@media (max-width: 768px) {

    html,
    body {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }

    /* MOBILE MENU BUTTON */

    #mobileMenuButton {
        display: flex !important;
        position: fixed !important;
        top: 14px !important;
        left: 14px !important;
        width: 42px !important;
        height: 42px !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 1px solid var(--border-light) !important;
        border-radius: 10px !important;
        background: var(--surface) !important;
        color: var(--text-primary) !important;
        cursor: pointer !important;
        z-index: 99999 !important;
        font-size: 20px !important;
        line-height: 1 !important;
        box-shadow: 0 5px 18px rgba(0,0,0,.15) !important;
    }


    /* SIDEBAR */

    .admin-wrapper > aside,
    .admin-wrapper .sidebar,
    .admin-wrapper .admin-sidebar,
    .admin-wrapper .side-bar {

        position: fixed !important;

        top: 0 !important;
        left: 0 !important;
        bottom: 0 !important;

        width: min(290px, 84vw) !important;
        max-width: 84vw !important;

        height: 100vh !important;
        max-height: 100vh !important;

        margin: 0 !important;

        z-index: 99998 !important;

        transform: translateX(-110%) !important;

        visibility: hidden !important;

        opacity: 1 !important;

        transition:
            transform .3s ease,
            visibility .3s ease !important;

        overflow-y: auto !important;
        overflow-x: hidden !important;

        box-shadow: 12px 0 35px rgba(0,0,0,.25) !important;
    }


    /* SIDEBAR OPEN */

    body.sidebar-mobile-open .admin-wrapper > aside,
    body.sidebar-mobile-open .admin-wrapper .sidebar,
    body.sidebar-mobile-open .admin-wrapper .admin-sidebar,
    body.sidebar-mobile-open .admin-wrapper .side-bar,

    .admin-wrapper > aside.mobile-sidebar-open,
    .admin-wrapper .sidebar.mobile-sidebar-open,
    .admin-wrapper .admin-sidebar.mobile-sidebar-open,
    .admin-wrapper .side-bar.mobile-sidebar-open {

        transform: translateX(0) !important;

        visibility: visible !important;

        opacity: 1 !important;

        z-index: 99998 !important;
    }


    /* OVERLAY */

    #mobileSidebarOverlay {

        position: fixed !important;

        inset: 0 !important;

        width: 100vw !important;
        height: 100vh !important;

        display: block !important;

        background: rgba(0,0,0,.45) !important;

        opacity: 0 !important;

        visibility: hidden !important;

        pointer-events: none !important;

        z-index: 99997 !important;

        transition:
            opacity .3s ease,
            visibility .3s ease !important;
    }


    /* OVERLAY OPEN */

    body.sidebar-mobile-open #mobileSidebarOverlay,
    #mobileSidebarOverlay.active {

        opacity: 1 !important;

        visibility: visible !important;

        pointer-events: auto !important;
    }


    /* MAIN AREA */

    .main-area {

        width: 100% !important;

        max-width: 100% !important;

        min-width: 0 !important;

        margin-left: 0 !important;
    }


    /* PREVENT PAGE MOVEMENT */

    body.sidebar-mobile-open {

        overflow: hidden !important;
    }

}


@media (min-width: 769px) {

    #mobileMenuButton,
    #mobileSidebarOverlay {

        display: none !important;
    }

}

    </style>
 
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
 
 
            </section> 
 
 
        <!-- =========================================
     SSRINI HANDICRAFT HERO BANNER
     ========================================= -->

<section class="craft-hero">

    <div class="craft-hero-content">

        <div class="craft-hero-eyebrow">
            ✦ Welcome back,
        </div>

        <h1>
            SSRINI HANDICRAFTS
        </h1>

        <p>
            Empowering rural artisans of Bengal.
        </p>

        <div class="craft-hero-divider">
            <span></span>
            ✦
            <span></span>
        </div>

    </div>

    <div class="craft-hero-art">
        <img
            src="../assets/images/folk-art.png"
            alt="Ssrini Handicrafts Folk Art"
        >
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

<div
    class="mobile-sidebar-overlay"
    id="mobileSidebarOverlay"
    aria-hidden="true"
></div>


<script>

(function () {

    "use strict";


    /* -----------------------------------------------
       FIND SIDEBAR
       ----------------------------------------------- */

    const sidebar =
        document.querySelector(".sidebar") ||
        document.querySelector(".admin-sidebar") ||
        document.querySelector(".side-bar") ||
        document.querySelector(".admin-wrapper > aside");


    const menuButton =
        document.getElementById("mobileMenuButton");


    const overlay =
        document.getElementById("mobileSidebarOverlay");


    if (!sidebar || !menuButton || !overlay) {
        return;
    }


    /* -----------------------------------------------
       OPEN SIDEBAR
       ----------------------------------------------- */

    function openSidebar() {

        sidebar.classList.add("mobile-sidebar-open");

        overlay.classList.add("active");

        menuButton.setAttribute(
            "aria-expanded",
            "true"
        );

        menuButton.innerHTML = "✕";

        document.body.style.overflow = "hidden";
    }


    /* -----------------------------------------------
       CLOSE SIDEBAR
       ----------------------------------------------- */

    function closeSidebar() {

        sidebar.classList.remove(
            "mobile-sidebar-open"
        );

        overlay.classList.remove("active");

        menuButton.setAttribute(
            "aria-expanded",
            "false"
        );

        menuButton.innerHTML = "☰";

        document.body.style.overflow = "";
    }


    /* -----------------------------------------------
       TOGGLE SIDEBAR
       ----------------------------------------------- */

    menuButton.addEventListener(
        "click",
        function () {

            if (
                sidebar.classList.contains(
                    "mobile-sidebar-open"
                )
            ) {

                closeSidebar();

            } else {

                openSidebar();

            }

        }
    );


    /* -----------------------------------------------
       OVERLAY CLOSE
       ----------------------------------------------- */

    overlay.addEventListener(
        "click",
        function () {

            closeSidebar();

        }
    );


    /* -----------------------------------------------
       SIDEBAR LINK CLOSE
       ----------------------------------------------- */

    sidebar.addEventListener(
        "click",
        function (event) {

            const link =
                event.target.closest("a");

            if (!link) {
                return;
            }

            if (
                window.innerWidth <= 768
            ) {

                closeSidebar();

            }

        }
    );


    /* -----------------------------------------------
       ESC KEY
       ----------------------------------------------- */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                window.innerWidth <= 768
            ) {

                closeSidebar();

            }

        }
    );


    /* -----------------------------------------------
       WINDOW RESIZE
       ----------------------------------------------- */

    window.addEventListener(
        "resize",
        function () {

            if (window.innerWidth > 768) {

                closeSidebar();

            }

        }
    );


})();

</script>

</body>

</html>