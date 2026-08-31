<?php 
 
$currentPage = basename($_SERVER['PHP_SELF']); 
 
?> 
 
<!-- =================================================
     MOBILE SIDEBAR TOGGLE
     Only visible on mobile devices
     ================================================= -->

<button
    type="button"
    class="mobile-sidebar-toggle"
    id="mobileSidebarToggle"
    aria-label="Open Sidebar"
>
    ☰
</button>


<!-- =================================================
     MOBILE SIDEBAR OVERLAY
     ================================================= -->

<div
    class="mobile-sidebar-overlay"
    id="mobileSidebarOverlay"
></div>


<aside class="sidebar"
    id="adminSidebar"
    aria-hidden="true"> 
 
    <!-- BRAND --> 
 
    <div class="sidebar-brand"> 
 
        <h2>Ssrini Handcrafts</h2> 
 
    </div> 
 
 
    <!-- NAVIGATION --> 
 
    <nav class="sidebar-nav"> 
 
        <!-- Dashboard --> 
 
        <div class="nav-section"> 
 
            <a 
                href="index.php" 
                class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>" 
            > 
 
                <span class="nav-icon"> 
                    📊 
                </span> 
 
                <span> 
                    Dashboard 
                </span> 
 
            </a> 
 
        </div> 
 
 
        <!-- SALES --> 
 
        <div class="nav-section"> 
 
            <div class="nav-section-title"> 
                Sales 
            </div> 
 
 
           <div  
    class="nav-item nav-dropdown"  
    data-menu="orders" 
> 
 
    <span class="nav-icon"> 
        📦 
    </span> 
 
    <span class="nav-label"> 
        Orders 
    </span> 
 
    <span class="nav-arrow"> 
        › 
    </span> 
 
</div> 
 
 
<div  
    class="nav-submenu"  
    data-submenu="orders" 
> 
 
    <a href="orders.php?status=new"> 
        New Orders 
    </a> 
 
    <a href="orders.php"> 
        All Orders 
    </a> 
 
</div> 
 
        </div> 
 
 
        <!-- CATALOGUE --> 
 
        <div class="nav-section"> 
 
            <div class="nav-section-title"> 
                Catalogue 
            </div> 
 
 
            <a href="products.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    🛍️ 
                </span> 
 
                <span> 
                    Products 
                </span> 
 
            </a> 
 
 
            <a href="invoices.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    🧾 
                </span> 
 
                <span> 
                    Invoices 
                </span> 
 
            </a> 
 
        </div> 
 
 
        <!-- WEBSITE --> 
 
        <div class="nav-section"> 
 
            <div class="nav-section-title"> 
                Website 
            </div> 
 
 
            <a href="filter-configuration.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    🔍 
                </span> 
 
                <span> 
                    Filter Configuration 
                </span> 
 
            </a> 
 
 
            <a href="front-page-content.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    🏠 
                </span> 
 
                <span> 
                    Front Page Content 
                </span> 
 
            </a> 
 
 
            <a href="about-details.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    ℹ️ 
                </span> 
 
                <span> 
                    About Details 
                </span> 
 
            </a> 
 
        </div> 
 
 
        <!-- MANAGEMENT --> 
 
        <div class="nav-section"> 
 
            <div class="nav-section-title"> 
                Management 
            </div> 
 
 
            <a href="customers.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    👥 
                </span> 
 
                <span> 
                    Customers 
                </span> 
 
            </a> 
 
 
            <a href="reviews.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    ⭐ 
                </span> 
 
                <span> 
                    Reviews 
                </span> 
 
            </a> 
 
 
            <a href="notifications.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    🔔 
                </span> 
 
                <span> 
                    Notifications 
                </span> 
 
            </a> 
 
 
            <a href="analytics.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    📈 
                </span> 
 
                <span> 
                    Analytics 
                </span> 
 
            </a> 
 
 
            <a href="activity-logs.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    📝 
                </span> 
 
                <span> 
                    Activity Logs 
                </span> 
 
            </a> 
 
 
            <a href="settings.php" class="nav-item"> 
 
                <span class="nav-icon"> 
                    ⚙️ 
                </span> 
 
                <span> 
                    Settings 
                </span> 
 
            </a> 
 
        </div> 
 
    </nav> 
 
 
    <!-- LOGOUT --> 
 
    <div class="sidebar-footer"> 
 
        <a 
            href="logout.php" 
            class="nav-item logout-link" 
        > 
 
            <span class="nav-icon"> 
                🚪 
            </span> 
 
            <span> 
                Logout 
            </span> 
 
        </a> 
 
    </div> 
 
</aside> 
 
 
<!-- =================================================
     SIDEBAR RESPONSIVE CSS
     Desktop remains unchanged.
     Mobile behavior is added only below 768px.
     ================================================= -->

<style>

    /* -----------------------------------------------
       MOBILE SIDEBAR TOGGLE
       ----------------------------------------------- */

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

        box-shadow:
            0 8px 25px rgba(0, 0, 0, 0.15);

        font-size: 22px;
        line-height: 1;

        cursor: pointer;

        align-items: center;
        justify-content: center;

        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }


    .mobile-sidebar-toggle:active {
        transform: scale(0.94);
    }


    /* -----------------------------------------------
       MOBILE OVERLAY
       ----------------------------------------------- */

    .mobile-sidebar-overlay {
        display: none;

        position: fixed;
        inset: 0;

        z-index: 9998;

        background: rgba(15, 23, 42, 0.45);

        opacity: 0;

        transition:
            opacity 0.25s ease;
    }


    /* -----------------------------------------------
       MOBILE SIDEBAR
       ----------------------------------------------- */

    @media (max-width: 768px) {

        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
        }


        .mobile-sidebar-toggle {
            display: flex;
        }


        .sidebar {
            position: fixed !important;

            top: 0;
            left: 0;
            bottom: 0;

            width: min(290px, 82vw) !important;
            max-width: 82vw;

            height: 100vh;
            height: 100dvh;

            z-index: 9999;

            transform: translateX(-105%);

            transition:
                transform 0.28s ease;

            overflow-x: hidden;
            overflow-y: auto;

            -webkit-overflow-scrolling: touch;

            box-shadow:
                10px 0 35px rgba(0, 0, 0, 0.18);
        }


        .sidebar.mobile-open {
            transform: translateX(0);
        }


        .mobile-sidebar-overlay {
            display: block;

            pointer-events: none;
        }


        body.sidebar-mobile-open .mobile-sidebar-overlay {
            opacity: 1;
            pointer-events: auto;
        }


        body.sidebar-mobile-open {
            overflow: hidden;
        }


        /* -------------------------------------------
           MAIN AREA
           ------------------------------------------- */

        .main-area {
            width: 100% !important;
            max-width: 100% !important;

            margin-left: 0 !important;

            box-sizing: border-box;
        }


        /* -------------------------------------------
           HEADER
           ------------------------------------------- */

        .main-area > header,
        .main-area .top-header,
        .main-area .admin-header {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }


        /* -------------------------------------------
           SIDEBAR BRAND
           ------------------------------------------- */

        .sidebar-brand {
            min-width: 0;
        }


        .sidebar-brand h2 {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        /* -------------------------------------------
           NAVIGATION
           ------------------------------------------- */

        .sidebar-nav {
            width: 100%;
            box-sizing: border-box;
        }


        .nav-section {
            width: 100%;
            box-sizing: border-box;
        }


        .nav-item {
            width: 100%;
            min-height: 46px;

            box-sizing: border-box;

            touch-action: manipulation;
        }


        .nav-label {
            min-width: 0;
        }


        .nav-submenu {
            width: 100%;
            box-sizing: border-box;
        }


        .nav-submenu a {
            min-height: 42px;

            box-sizing: border-box;

            display: flex;
            align-items: center;

            touch-action: manipulation;
        }


        /* -------------------------------------------
           SIDEBAR FOOTER
           ------------------------------------------- */

        .sidebar-footer {
            width: 100%;
            box-sizing: border-box;
        }


        .logout-link {
            min-height: 46px;
        }

    }


    /* -----------------------------------------------
       SMALL MOBILE DEVICES
       ----------------------------------------------- */

    @media (max-width: 480px) {

        .mobile-sidebar-toggle {
            top: 10px;
            left: 10px;

            width: 42px;
            height: 42px;

            border-radius: 11px;

            font-size: 21px;
        }


        .sidebar {
            width: min(280px, 86vw) !important;
            max-width: 86vw;
        }

    }

</style>
 
 
<script> 
document.addEventListener('DOMContentLoaded', function () { 
 
    const dropdowns = document.querySelectorAll('.nav-dropdown'); 
    const sidebar = document.getElementById('adminSidebar');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');


    /* -----------------------------------------------
       ORDERS DROPDOWN
       ----------------------------------------------- */

    dropdowns.forEach(function (dropdown) { 
 
        dropdown.addEventListener('click', function () { 
 
            const menuName = this.getAttribute('data-menu'); 
 
            const submenu = document.querySelector( 
                '.nav-submenu[data-submenu="' + menuName + '"]' 
            ); 
 
            if (!submenu) { 
                return; 
            } 
 
            submenu.classList.toggle('open'); 
 
            this.classList.toggle('active'); 
 
        }); 
 
    });


    /* -----------------------------------------------
       MOBILE SIDEBAR OPEN / CLOSE
       ----------------------------------------------- */

    function openMobileSidebar() {

        if (!sidebar) {
            return;
        }

        sidebar.classList.add('mobile-open');

        document.body.classList.add(
            'sidebar-mobile-open'
        );

        if (mobileSidebarToggle) {

            mobileSidebarToggle.setAttribute(
                'aria-label',
                'Close Sidebar'
            );

            mobileSidebarToggle.innerHTML = '✕';

        }

    }


    function closeMobileSidebar() {

        if (!sidebar) {
            return;
        }

        sidebar.classList.remove('mobile-open');

        document.body.classList.remove(
            'sidebar-mobile-open'
        );

        if (mobileSidebarToggle) {

            mobileSidebarToggle.setAttribute(
                'aria-label',
                'Open Sidebar'
            );

            mobileSidebarToggle.innerHTML = '☰';

        }

    }


    if (mobileSidebarToggle) {

        mobileSidebarToggle.addEventListener(
            'click',
            function () {

                if (
                    sidebar &&
                    sidebar.classList.contains(
                        'mobile-open'
                    )
                ) {

                    closeMobileSidebar();

                } else {

                    openMobileSidebar();

                }

            }
        );

    }


    /* -----------------------------------------------
       CLOSE SIDEBAR WHEN OVERLAY IS CLICKED
       ----------------------------------------------- */

    if (mobileSidebarOverlay) {

        mobileSidebarOverlay.addEventListener(
            'click',
            function () {

                closeMobileSidebar();

            }
        );

    }


    /* -----------------------------------------------
       CLOSE SIDEBAR AFTER NAVIGATION
       ----------------------------------------------- */

    const navigationLinks =
        document.querySelectorAll(
            '.sidebar a'
        );

    navigationLinks.forEach(function (link) {

        link.addEventListener(
            'click',
            function () {

                if (
                    window.innerWidth <= 768
                ) {

                    closeMobileSidebar();

                }

            }
        );

    });


    /* -----------------------------------------------
       ESC KEY
       ----------------------------------------------- */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeMobileSidebar();

            }

        }
    );


    /* -----------------------------------------------
       RESET WHEN SCREEN BECOMES DESKTOP
       ----------------------------------------------- */

    window.addEventListener(
        'resize',
        function () {

            if (window.innerWidth > 768) {

                closeMobileSidebar();

            }

        }
    );
 
}); 
</script>