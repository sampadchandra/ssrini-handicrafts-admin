<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar" id="adminSidebar">

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

                <span>
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

                <a href="#">
                    New Orders
                </a>

                <a href="#">
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