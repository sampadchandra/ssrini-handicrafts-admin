<?php

$pageTitle = $pageTitle ?? 'Dashboard';

?>

<header class="top-header">

    <div class="header-left">

        <!-- =================================================
             MOBILE MENU BUTTON
             ONE SINGLE BUTTON
             ================================================= -->

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


        <!-- =================================================
             PAGE TITLE
             ================================================= -->

        <h1>
            <?= htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h1>

    </div>


    <!-- =================================================
         HEADER RIGHT
         ================================================= -->

    <div class="header-right">


        <!-- REFRESH -->

        <button
            type="button"
            class="header-button"
            id="refreshButton"
            title="Refresh page"
        >

            🔄

            <span>
                Refresh
            </span>

        </button>


        <!-- PROFILE -->

        <div class="profile">

            <div class="profile-avatar">

                <img
                    src="../assets/images/logo.jpg"
                    alt="Ssrini Handicrafts"
                    style="border-radius: 30%;"
                >

            </div>


            <div class="profile-info">

                <span class="profile-name">

                    <?= htmlspecialchars(
                        $_SESSION['admin_name'] ?? 'Admin',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>


                <span class="profile-role">

                    <?= htmlspecialchars(
                        ucfirst(
                            $_SESSION['admin_role'] ?? 'admin'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>

        </div>

    </div>

</header>