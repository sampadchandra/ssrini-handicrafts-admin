<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN FILTER CONFIGURATION
 * =========================================================
 *
 * Purpose:
 * - Configure minimum product price
 * - Configure maximum product price
 * - Display currently saved filter settings
 * - Insert settings when no configuration exists
 * - Update existing settings
 * - Validate price values
 * - Use secure PDO queries
 *
 * Existing project structure:
 * - config/database.php
 * - includes/auth.php
 * - includes/sidebar.php
 * - includes/header.php
 * - assets/css/admin.css
 *
 * Database table:
 * - filter_settings
 *
 * Table columns:
 * - id
 * - min_price
 * - max_price
 * - updated_at
 *
 * No database structure is modified by this page.
 */


/**
 * =========================================================
 * DATABASE + AUTHENTICATION
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();


/**
 * =========================================================
 * PAGE CONFIGURATION
 * =========================================================
 */

$pageTitle = 'Filter Configuration';


/**
 * =========================================================
 * HELPER FUNCTIONS
 * =========================================================
 */


/**
 * Escape HTML safely.
 */
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Format price for display.
 */
function formatPrice($value): string
{
    if (
        $value === null ||
        $value === ''
    ) {
        return 'Not configured';
    }

    if (
        !is_numeric($value)
    ) {
        return e($value);
    }

    return '₹' . number_format(
        (float) $value,
        2
    );
}


/**
 * =========================================================
 * DEFAULT VALUES
 * =========================================================
 */

$minPrice = '';

$maxPrice = '';

$updatedAt = null;

$message = '';

$messageType = '';

$pageError = '';

$settingsId = null;


/**
 * =========================================================
 * LOAD CURRENT FILTER SETTINGS
 * =========================================================
 */

try {

    $settingsStmt =
        $pdo->query(
            "
            SELECT
                id,
                min_price,
                max_price,
                updated_at
            FROM filter_settings
            ORDER BY id ASC
            LIMIT 1
            "
        );


    $settings =
        $settingsStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        $settings
    ) {

        $settingsId =
            $settings['id'] ?? null;


        $minPrice =
            $settings['min_price'] ?? '';


        $maxPrice =
            $settings['max_price'] ?? '';


        $updatedAt =
            $settings['updated_at'] ?? null;
    }


} catch (Throwable $e) {

    $pageError =
        'Filter configuration could not be loaded. Please check your database configuration.';
}


/**
 * =========================================================
 * HANDLE FORM SUBMISSION
 * =========================================================
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /**
     * -----------------------------------------------------
     * GET FORM VALUES
     * -----------------------------------------------------
     */

    $submittedMinPrice =
        isset($_POST['min_price'])
            ? trim(
                (string) $_POST['min_price']
            )
            : '';


    $submittedMaxPrice =
        isset($_POST['max_price'])
            ? trim(
                (string) $_POST['max_price']
            )
            : '';


    /**
     * -----------------------------------------------------
     * KEEP VALUES IN FORM
     * -----------------------------------------------------
     */

    $minPrice =
        $submittedMinPrice;


    $maxPrice =
        $submittedMaxPrice;


    /**
     * -----------------------------------------------------
     * VALIDATION
     * -----------------------------------------------------
     */

    $validationErrors = [];


    /**
     * Minimum price validation.
     */
    if (
        $submittedMinPrice === ''
    ) {

        $validationErrors[] =
            'Minimum price is required.';

    } elseif (
        !is_numeric(
            $submittedMinPrice
        )
    ) {

        $validationErrors[] =
            'Minimum price must be a valid number.';

    } elseif (
        (float) $submittedMinPrice < 0
    ) {

        $validationErrors[] =
            'Minimum price cannot be negative.';

    }


    /**
     * Maximum price validation.
     */
    if (
        $submittedMaxPrice === ''
    ) {

        $validationErrors[] =
            'Maximum price is required.';

    } elseif (
        !is_numeric(
            $submittedMaxPrice
        )
    ) {

        $validationErrors[] =
            'Maximum price must be a valid number.';

    } elseif (
        (float) $submittedMaxPrice < 0
    ) {

        $validationErrors[] =
            'Maximum price cannot be negative.';

    }


    /**
     * -----------------------------------------------------
     * MINIMUM / MAXIMUM RELATIONSHIP
     * -----------------------------------------------------
     */

    if (
        empty($validationErrors) &&
        (float) $submittedMinPrice >
        (float) $submittedMaxPrice
    ) {

        $validationErrors[] =
            'Minimum price cannot be greater than maximum price.';
    }


    /**
     * -----------------------------------------------------
     * DECIMAL LIMIT
     * -----------------------------------------------------
     */

    if (
        empty($validationErrors)
    ) {

        if (
            strlen(
                explode(
                    '.',
                    $submittedMinPrice
                )[1] ?? ''
            ) > 2
        ) {

            $validationErrors[] =
                'Minimum price can have maximum 2 decimal places.';
        }


        if (
            strlen(
                explode(
                    '.',
                    $submittedMaxPrice
                )[1] ?? ''
            ) > 2
        ) {

            $validationErrors[] =
                'Maximum price can have maximum 2 decimal places.';
        }
    }


    /**
     * -----------------------------------------------------
     * SAVE SETTINGS
     * -----------------------------------------------------
     */

    if (
        empty($validationErrors)
    ) {

        try {

            /**
             * Convert values to decimal.
             */
            $minPriceValue =
                number_format(
                    (float) $submittedMinPrice,
                    2,
                    '.',
                    ''
                );


            $maxPriceValue =
                number_format(
                    (float) $submittedMaxPrice,
                    2,
                    '.',
                    ''
                );


            /**
             * -------------------------------------------------
             * CHECK EXISTING SETTINGS
             * -------------------------------------------------
             */

            $existingStmt =
                $pdo->query(
                    "
                    SELECT
                        id
                    FROM filter_settings
                    ORDER BY id ASC
                    LIMIT 1
                    "
                );


            $existingSettings =
                $existingStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /**
             * -------------------------------------------------
             * UPDATE EXISTING ROW
             * -------------------------------------------------
             */

            if (
                $existingSettings
            ) {

                $settingsId =
                    $existingSettings['id'];


                $updateStmt =
                    $pdo->prepare(
                        "
                        UPDATE filter_settings
                        SET
                            min_price = :min_price,
                            max_price = :max_price,
                            updated_at = NOW()
                        WHERE id = :id
                        "
                    );


                $updateStmt->execute([
                    ':min_price' =>
                        $minPriceValue,

                    ':max_price' =>
                        $maxPriceValue,

                    ':id' =>
                        $settingsId
                ]);


                $message =
                    'Filter configuration updated successfully.';


                $messageType =
                    'success';


                $updatedAt =
                    date(
                        'Y-m-d H:i:s'
                    );


            /**
             * -------------------------------------------------
             * INSERT FIRST ROW
             * -------------------------------------------------
             */

            } else {

                $insertStmt =
                    $pdo->prepare(
                        "
                        INSERT INTO filter_settings
                        (
                            min_price,
                            max_price,
                            updated_at
                        )
                        VALUES
                        (
                            :min_price,
                            :max_price,
                            NOW()
                        )
                        "
                    );


                $insertStmt->execute([
                    ':min_price' =>
                        $minPriceValue,

                    ':max_price' =>
                        $maxPriceValue
                ]);


                $settingsId =
                    $pdo->lastInsertId();


                $message =
                    'Filter configuration saved successfully.';


                $messageType =
                    'success';


                $updatedAt =
                    date(
                        'Y-m-d H:i:s'
                    );
            }


            /**
             * Keep normalized values.
             */
            $minPrice =
                $minPriceValue;


            $maxPrice =
                $maxPriceValue;


        } catch (Throwable $e) {

            $message =
                'Filter configuration could not be saved. Please try again.';


            $messageType =
                'error';
        }


    } else {

        /**
         * -----------------------------------------------------
         * SHOW VALIDATION ERRORS
         * -----------------------------------------------------
         */

        $message =
            implode(
                ' ',
                $validationErrors
            );


        $messageType =
            'error';
    }
}


/**
 * =========================================================
 * PAGE
 * =========================================================
 */

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
        content="Ssrini Handcrafts Admin Filter Configuration"
    >

    <title>
        <?= e($pageTitle) ?>
        | Ssrini Handcrafts
    </title>


    <!-- =====================================================
         EXISTING ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         FILTER CONFIGURATION PAGE STYLES
         ===================================================== -->

    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .filter-config-page {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        /* =====================================================
           MAIN CONFIGURATION CARD
           ===================================================== */

        .filter-config-card {

            background: #ffffff;

            border:
                1px solid #eeeaf2;

            border-radius: 18px;

            box-shadow:
                0 8px 25px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );

            overflow: hidden;

        }


        /* =====================================================
           CARD HEADER
           ===================================================== */

        .filter-config-card-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .filter-config-card-title {

            color: #25212d;

            font-size: 16px;

            font-weight: 700;

        }


        .filter-config-card-description {

            margin-top: 5px;

            color: #9a94a2;

            font-size: 11px;

            line-height: 1.5;

        }


        .filter-config-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                6px 10px;

            border-radius: 8px;

            background: #e8f8ef;

            color: #23804c;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

        }


        .filter-config-status-dot {

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: #23804c;

        }


        /* =====================================================
           CARD BODY
           ===================================================== */

        .filter-config-card-body {

            padding:
                22px;

        }


        /* =====================================================
           INFORMATION BOX
           ===================================================== */

        .filter-config-info {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding:
                14px 15px;

            margin-bottom: 22px;

            border:
                1px solid #eadcf6;

            border-radius: 12px;

            background: #faf6fe;

        }


        .filter-config-info-icon {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 14px;

        }


        .filter-config-info-content {

            min-width: 0;

        }


        .filter-config-info-title {

            color: #403a47;

            font-size: 11px;

            font-weight: 700;

        }


        .filter-config-info-text {

            margin-top: 4px;

            color: #81798a;

            font-size: 10px;

            line-height: 1.55;

        }


        /* =====================================================
           FORM GRID
           ===================================================== */

        .filter-config-form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 18px;

        }


        .filter-config-field {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .filter-config-label {

            color: #4c4554;

            font-size: 11px;

            font-weight: 700;

        }


        .filter-config-label-required {

            color: #c53030;

        }


        .filter-config-input-wrapper {

            position: relative;

        }


        .filter-config-currency {

            position: absolute;

            left: 13px;

            top: 50%;

            transform:
                translateY(-50%);

            color: #8d8595;

            font-size: 12px;

            font-weight: 700;

            pointer-events: none;

        }


        .filter-config-input {

            width: 100%;

            height: 46px;

            border:
                1px solid #e3dfea;

            border-radius: 11px;

            background: #ffffff;

            color: #25212d;

            padding:
                0 13px 0 31px;

            outline: none;

            font-size: 13px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

            box-sizing: border-box;

        }


        .filter-config-input:focus {

            border-color: #9b48d1;

            box-shadow:
                0 0 0 3px
                rgba(
                    155,
                    72,
                    209,
                    0.10
                );

        }


        .filter-config-help {

            color: #9a94a2;

            font-size: 9px;

            line-height: 1.45;

        }


        /* =====================================================
           FORM ACTIONS
           ===================================================== */

        .filter-config-actions {

            margin-top: 22px;

            padding-top: 20px;

            border-top:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

        }


        .filter-config-last-updated {

            color: #9a94a2;

            font-size: 10px;

        }


        .filter-config-last-updated strong {

            color: #625b6b;

        }


        .filter-config-submit {

            min-width: 150px;

            height: 43px;

            border: none;

            border-radius: 10px;

            padding:
                0 18px;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 7px 17px
                rgba(
                    118,
                    39,
                    201,
                    0.20
                );

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;

        }


        .filter-config-submit:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 10px 22px
                rgba(
                    118,
                    39,
                    201,
                    0.25
                );

        }


        .filter-config-submit:active {

            transform:
                translateY(0);

        }


        /* =====================================================
           MESSAGE
           ===================================================== */

        .filter-config-message {

            padding:
                13px 15px;

            border-radius: 10px;

            font-size: 11px;

            line-height: 1.5;

        }


        .filter-config-message.success {

            background: #eaf8f0;

            border:
                1px solid #c8ebd7;

            color: #237346;

        }


        .filter-config-message.error {

            background: #fff2f2;

            border:
                1px solid #ffd3d3;

            color: #c53030;

        }


        /* =====================================================
           CURRENT VALUES
           ===================================================== */

        .filter-config-current {

            background: #ffffff;

            border:
                1px solid #eeeaf2;

            border-radius: 18px;

            padding: 20px 22px;

            box-shadow:
                0 8px 25px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );

        }


        .filter-config-current-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 17px;

        }


        .filter-config-current-title {

            color: #25212d;

            font-size: 14px;

            font-weight: 700;

        }


        .filter-config-current-subtitle {

            margin-top: 4px;

            color: #9a94a2;

            font-size: 10px;

        }


        .filter-config-current-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 14px;

        }


        .filter-config-value-card {

            border:
                1px solid #eeeaf2;

            border-radius: 12px;

            padding: 15px;

            background: #fcfbfd;

        }


        .filter-config-value-label {

            color: #9a94a2;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;

        }


        .filter-config-value {

            margin-top: 7px;

            color: #403a47;

            font-size: 18px;

            font-weight: 700;

        }


        .filter-config-value-id {

            color: #7627c9;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 800px) {

            .filter-config-form-grid {

                grid-template-columns: 1fr;

            }


            .filter-config-current-grid {

                grid-template-columns: 1fr;

            }


            .filter-config-card-header {

                align-items: flex-start;

                flex-direction: column;

            }

        }


        @media (max-width: 600px) {

            .filter-config-card-body {

                padding: 17px;

            }


            .filter-config-card-header {

                padding:
                    17px;

            }


            .filter-config-current {

                padding:
                    17px;

            }


            .filter-config-actions {

                align-items: stretch;

                flex-direction: column;

            }


            .filter-config-submit {

                width: 100%;

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


        <!-- =================================================
             HEADER
             ================================================= -->

        <?php

        require_once __DIR__ .
            '/../includes/header.php';

        ?>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <div class="page-content">


            <!-- =================================================
                 PAGE HEADER
                 ================================================= -->

            <section class="page-header">


                <div>

                    <h1 class="page-title">

                        Filter Configuration

                    </h1>


                    <p class="page-description">

                        Configure the price range available
                        for product filtering on the store.

                    </p>

                </div>


            </section>


            <!-- =================================================
                 FILTER CONFIGURATION PAGE
                 ================================================= -->

            <div class="filter-config-page">


                <!-- =================================================
                     DATABASE ERROR
                     ================================================= -->

                <?php if (
                    $pageError !== ''
                ): ?>

                    <div
                        class="filter-config-message error"
                    >

                        <?= e(
                            $pageError
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     SUCCESS / VALIDATION MESSAGE
                     ================================================= -->

                <?php if (
                    $message !== ''
                ): ?>

                    <div
                        class="
                            filter-config-message
                            <?= e(
                                $messageType
                            ) ?>
                        "
                    >

                        <?= e(
                            $message
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     CONFIGURATION CARD
                     ================================================= -->

                <section
                    class="filter-config-card"
                >


                    <!-- CARD HEADER -->

                    <div
                        class="filter-config-card-header"
                    >


                        <div>

                            <div
                                class="filter-config-card-title"
                            >

                                Product Price Filter

                            </div>


                            <div
                                class="filter-config-card-description"
                            >

                                Set the minimum and maximum
                                price values that should be
                                available in the storefront
                                filter.

                            </div>

                        </div>


                        <div
                            class="filter-config-status"
                        >

                            <span
                                class="filter-config-status-dot"
                            ></span>

                            Configuration

                        </div>


                    </div>


                    <!-- CARD BODY -->

                    <div
                        class="filter-config-card-body"
                    >


                        <!-- =================================================
                             INFORMATION
                             ================================================= -->

                        <div
                            class="filter-config-info"
                        >


                            <div
                                class="filter-config-info-icon"
                            >

                                ⚙

                            </div>


                            <div
                                class="filter-config-info-content"
                            >

                                <div
                                    class="filter-config-info-title"
                                >

                                    Configure your product
                                    price range

                                </div>


                                <div
                                    class="filter-config-info-text"
                                >

                                    Customers can use this
                                    price range to narrow down
                                    products on the storefront.
                                    Enter valid values between
                                    ₹0 and your desired maximum
                                    price.

                                </div>

                            </div>


                        </div>


                        <!-- =================================================
                             FORM
                             ================================================= -->

                        <form
                            method="POST"
                            action=""
                            id="filterConfigurationForm"
                        >


                            <div
                                class="filter-config-form-grid"
                            >


                                <!-- =================================================
                                     MINIMUM PRICE
                                     ================================================= -->

                                <div
                                    class="filter-config-field"
                                >


                                    <label
                                        class="filter-config-label"
                                        for="minPrice"
                                    >

                                        Minimum Price

                                        <span
                                            class="filter-config-label-required"
                                        >

                                            *

                                        </span>

                                    </label>


                                    <div
                                        class="filter-config-input-wrapper"
                                    >

                                        <span
                                            class="filter-config-currency"
                                        >

                                            ₹

                                        </span>


                                        <input
                                            type="number"
                                            id="minPrice"
                                            name="min_price"
                                            class="filter-config-input"
                                            value="<?= e(
                                                $minPrice
                                            ) ?>"
                                            min="0"
                                            step="0.01"
                                            inputmode="decimal"
                                            placeholder="0.00"
                                            required
                                        >

                                    </div>


                                    <div
                                        class="filter-config-help"
                                    >

                                        Products below this
                                        price will not be included
                                        in the price filter range.

                                    </div>


                                </div>


                                <!-- =================================================
                                     MAXIMUM PRICE
                                     ================================================= -->

                                <div
                                    class="filter-config-field"
                                >


                                    <label
                                        class="filter-config-label"
                                        for="maxPrice"
                                    >

                                        Maximum Price

                                        <span
                                            class="filter-config-label-required"
                                        >

                                            *

                                        </span>

                                    </label>


                                    <div
                                        class="filter-config-input-wrapper"
                                    >

                                        <span
                                            class="filter-config-currency"
                                        >

                                            ₹

                                        </span>


                                        <input
                                            type="number"
                                            id="maxPrice"
                                            name="max_price"
                                            class="filter-config-input"
                                            value="<?= e(
                                                $maxPrice
                                            ) ?>"
                                            min="0"
                                            step="0.01"
                                            inputmode="decimal"
                                            placeholder="10000.00"
                                            required
                                        >

                                    </div>


                                    <div
                                        class="filter-config-help"
                                    >

                                        Products above this
                                        price will not be included
                                        in the price filter range.

                                    </div>


                                </div>


                            </div>


                            <!-- =================================================
                                 FORM ACTIONS
                                 ================================================= -->

                            <div
                                class="filter-config-actions"
                            >


                                <div
                                    class="filter-config-last-updated"
                                >

                                    <?php if (
                                        !empty(
                                            $updatedAt
                                        )
                                    ): ?>

                                        Last updated:

                                        <strong>

                                            <?= e(
                                                date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        (string)
                                                        $updatedAt
                                                    )
                                                )
                                            ) ?>

                                        </strong>

                                    <?php else: ?>

                                        No configuration
                                        saved yet.

                                    <?php endif; ?>

                                </div>


                                <button
                                    type="submit"
                                    class="filter-config-submit"
                                    id="saveFilterConfiguration"
                                >

                                    💾

                                    Save Configuration

                                </button>


                            </div>


                        </form>


                    </div>


                </section>


                <!-- =================================================
                     CURRENT CONFIGURATION
                     ================================================= -->

                <section
                    class="filter-config-current"
                >


                    <div
                        class="filter-config-current-header"
                    >


                        <div>

                            <div
                                class="filter-config-current-title"
                            >

                                Current Configuration

                            </div>


                            <div
                                class="filter-config-current-subtitle"
                            >

                                Values currently stored in
                                the filter_settings table.

                            </div>

                        </div>


                    </div>


                    <div
                        class="filter-config-current-grid"
                    >


                        <!-- =================================================
                             MINIMUM VALUE
                             ================================================= -->

                        <div
                            class="filter-config-value-card"
                        >

                            <div
                                class="filter-config-value-label"
                            >

                                Minimum Price

                            </div>


                            <div
                                class="filter-config-value"
                            >

                                <?php if (
                                    $minPrice !== ''
                                ): ?>

                                    <?= e(
                                        formatPrice(
                                            $minPrice
                                        )
                                    ) ?>

                                <?php else: ?>

                                    Not configured

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- =================================================
                             MAXIMUM VALUE
                             ================================================= -->

                        <div
                            class="filter-config-value-card"
                        >

                            <div
                                class="filter-config-value-label"
                            >

                                Maximum Price

                            </div>


                            <div
                                class="filter-config-value"
                            >

                                <?php if (
                                    $maxPrice !== ''
                                ): ?>

                                    <?= e(
                                        formatPrice(
                                            $maxPrice
                                        )
                                    ) ?>

                                <?php else: ?>

                                    Not configured

                                <?php endif; ?>

                            </div>

                        </div>


                    </div>


                </section>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     EXISTING ADMIN JAVASCRIPT
     ===================================================== -->

<script
    src="../assets/js/admin.js"
></script>


<!-- =====================================================
     FILTER CONFIGURATION JAVASCRIPT
     ===================================================== -->

<script>

    /*
    |--------------------------------------------------------------------------
    | FORM ELEMENTS
    |--------------------------------------------------------------------------
    */

    const filterConfigurationForm =
        document.getElementById(
            'filterConfigurationForm'
        );


    const minPriceInput =
        document.getElementById(
            'minPrice'
        );


    const maxPriceInput =
        document.getElementById(
            'maxPrice'
        );


    /*
    |--------------------------------------------------------------------------
    | PRICE VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        filterConfigurationForm &&
        minPriceInput &&
        maxPriceInput
    ) {

        filterConfigurationForm.addEventListener(
            'submit',
            function (event) {

                const minValue =
                    parseFloat(
                        minPriceInput.value
                    );


                const maxValue =
                    parseFloat(
                        maxPriceInput.value
                    );


                /*
                |--------------------------------------------------------------
                | CHECK NUMBERS
                |--------------------------------------------------------------
                */

                if (
                    isNaN(minValue) ||
                    isNaN(maxValue)
                ) {

                    event.preventDefault();

                    alert(
                        'Please enter valid minimum and maximum prices.'
                    );

                    return;

                }


                /*
                |--------------------------------------------------------------
                | CHECK NEGATIVE VALUES
                |--------------------------------------------------------------
                */

                if (
                    minValue < 0 ||
                    maxValue < 0
                ) {

                    event.preventDefault();

                    alert(
                        'Price values cannot be negative.'
                    );

                    return;

                }


                /*
                |--------------------------------------------------------------
                | CHECK MINIMUM / MAXIMUM
                |--------------------------------------------------------------
                */

                if (
                    minValue >
                    maxValue
                ) {

                    event.preventDefault();

                    alert(
                        'Minimum price cannot be greater than maximum price.'
                    );

                    maxPriceInput.focus();

                    return;

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT NEGATIVE NUMBER INPUT
    |--------------------------------------------------------------------------
    */

    if (
        minPriceInput
    ) {

        minPriceInput.addEventListener(
            'input',
            function () {

                if (
                    parseFloat(
                        this.value
                    ) < 0
                ) {

                    this.value = 0;

                }

            }
        );

    }


    if (
        maxPriceInput
    ) {

        maxPriceInput.addEventListener(
            'input',
            function () {

                if (
                    parseFloat(
                        this.value
                    ) < 0
                ) {

                    this.value = 0;

                }

            }
        );

    }

</script>


</body>

</html>