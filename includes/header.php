<?php

$pageTitle = $pageTitle ?? 'Dashboard';

?>

<header class="top-header">

    <div class="header-left">

        <button
            type="button"
            class="header-button mobile-menu-button"
            id="mobileMenuButton"
            aria-label="Open navigation"
        >

            ☰

        </button>


        <h1>

            <?= htmlspecialchars(
                $pageTitle,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </h1>

    </div>


    <div class="header-right">


        <!-- Refresh -->

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


        <!-- Profile -->

        <div class="profile">

            <div class="profile-avatar">

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $_SESSION['admin_name'] ?? 'A',
                            0,
                            1
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

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