<?php

/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * CUSTOMER DETAILS
 * =========================================================
 *
 * File:
 * admin/customer-details.php
 *
 * Purpose:
 * - Display complete customer information
 * - Display customer's orders
 * - Show order statistics
 * - Phone / Email / WhatsApp actions
 * - Responsive desktop + mobile layout
 *
 * Existing structure:
 *
 * ssrini-handicrafts-admin/
 *
 * ├── admin/
 * │   ├── customers.php
 * │   ├── customer-details.php
 * │   └── ...
 * │
 * ├── config/
 * │   └── database.php
 * │
 * ├── includes/
 * │   ├── auth.php
 * │   ├── sidebar.php
 * │   └── header.php
 * │
 * └── assets/
 *     └── css/
 *         └── admin.css
 *
 * Database tables used:
 * - customers
 * - orders
 *
 * No database structure is modified.
 * =========================================================
 */


/**
 * =========================================================
 * DATABASE + AUTHENTICATION
 * =========================================================
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

requireAdminLogin();


/**
 * =========================================================
 * PAGE CONFIGURATION
 * =========================================================
 */

$pageTitle = 'Customer Details';


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
 * Format money.
 */
function formatMoney($value): string
{
    if (!is_numeric($value)) {
        return '₹0.00';
    }

    return '₹' . number_format(
        (float) $value,
        2
    );
}


/**
 * Format date.
 */
function formatDateTime($value): string
{
    if (empty($value)) {
        return '—';
    }

    $timestamp = strtotime(
        (string) $value
    );

    if ($timestamp === false) {
        return e($value);
    }

    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


/**
 * Get initials.
 */
function getInitials(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        return 'C';
    }

    $parts = preg_split(
        '/\s+/',
        $name
    );

    if (count($parts) >= 2) {

        return strtoupper(
            substr($parts[0], 0, 1) .
            substr($parts[1], 0, 1)
        );
    }

    return strtoupper(
        substr($name, 0, 2)
    );
}


/**
 * Order status class.
 */
function orderStatusClass($status): string
{
    $status = strtolower(
        trim((string) $status)
    );

    switch ($status) {

        case 'pending':
            return 'status-pending';

        case 'processing':
            return 'status-processing';

        case 'confirmed':
            return 'status-confirmed';

        case 'shipped':
            return 'status-shipped';

        case 'delivered':
            return 'status-delivered';

        case 'cancelled':
        case 'canceled':
            return 'status-cancelled';

        case 'returned':
            return 'status-returned';

        default:
            return 'status-default';
    }
}


/**
 * Human readable order status.
 */
function orderStatusLabel($status): string
{
    $status = trim(
        (string) $status
    );

    if ($status === '') {
        return 'Unknown';
    }

    return ucwords(
        str_replace(
            ['_', '-'],
            ' ',
            strtolower($status)
        )
    );
}


/**
 * =========================================================
 * CUSTOMER ID
 * =========================================================
 */

$customerId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/**
 * =========================================================
 * DEFAULT VALUES
 * =========================================================
 */

$customer = null;
$orders = [];

$totalOrders = 0;
$totalSpent = 0;
$pendingOrders = 0;
$completedOrders = 0;

$errorMessage = null;


/**
 * =========================================================
 * VALIDATE CUSTOMER ID
 * =========================================================
 */

if ($customerId <= 0) {

    $errorMessage =
        'Invalid customer ID.';
}


/**
 * =========================================================
 * LOAD CUSTOMER
 * =========================================================
 */

if (
    $customerId > 0 &&
    $errorMessage === null
) {

    try {

        /**
         * -----------------------------------------------------
         * CUSTOMER QUERY
         * -----------------------------------------------------
         */

        $customerSQL = "
            SELECT
                id,
                name,
                email,
                phone,
                address,
                city,
                state,
                pincode,
                created_at,
                updated_at
            FROM customers
            WHERE id = ?
            LIMIT 1
        ";

        $customerStmt =
            $pdo->prepare(
                $customerSQL
            );

        $customerStmt->execute([
            $customerId
        ]);

        $customer =
            $customerStmt->fetch(
                PDO::FETCH_ASSOC
            );


        /**
         * -----------------------------------------------------
         * CUSTOMER NOT FOUND
         * -----------------------------------------------------
         */

        if (!$customer) {

            $errorMessage =
                'Customer not found.';
        }


        /**
         * -----------------------------------------------------
         * CUSTOMER ORDERS
         * -----------------------------------------------------
         */

        if (
            $customer &&
            $errorMessage === null
        ) {

            $ordersSQL = "
                SELECT
                    id,
                    order_number,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    shipping_address,
                    created_at,
                    updated_at
                FROM orders
                WHERE customer_id = ?
                ORDER BY id DESC
            ";

            $ordersStmt =
                $pdo->prepare(
                    $ordersSQL
                );

            $ordersStmt->execute([
                $customerId
            ]);

            $orders =
                $ordersStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );


            /**
             * -------------------------------------------------
             * ORDER STATISTICS
             * -------------------------------------------------
             */

            $totalOrders =
                count($orders);

            foreach (
                $orders as $order
            ) {

                $totalSpent +=
                    (float) (
                        $order['total_amount']
                        ?? 0
                    );

                $status =
                    strtolower(
                        trim(
                            (string) (
                                $order['order_status']
                                ?? ''
                            )
                        )
                    );


                if (
                    in_array(
                        $status,
                        [
                            'pending',
                            'processing',
                            'confirmed'
                        ],
                        true
                    )
                ) {

                    $pendingOrders++;
                }


                if (
                    in_array(
                        $status,
                        [
                            'delivered',
                            'completed'
                        ],
                        true
                    )
                ) {

                    $completedOrders++;
                }
            }
        }

    } catch (Throwable $e) {

        /**
         * Do not expose database internals.
         */

        $errorMessage =
            'Customer details could not be loaded. Please check your database configuration.';
    }
}


/**
 * =========================================================
 * CUSTOMER DISPLAY VALUES
 * =========================================================
 */

if ($customer) {

    $customerName =
        trim(
            (string) (
                $customer['name']
                ?? 'Customer'
            )
        );

    $customerEmail =
        trim(
            (string) (
                $customer['email']
                ?? ''
            )
        );

    $customerPhone =
        trim(
            (string) (
                $customer['phone']
                ?? ''
            )
        );

    $customerAddress =
        trim(
            (string) (
                $customer['address']
                ?? ''
            )
        );

    $customerCity =
        trim(
            (string) (
                $customer['city']
                ?? ''
            )
        );

    $customerState =
        trim(
            (string) (
                $customer['state']
                ?? ''
            )
        );

    $customerPincode =
        trim(
            (string) (
                $customer['pincode']
                ?? ''
            )
        );

    $customerInitials =
        getInitials(
            $customerName
        );

    $pageTitle =
        $customerName .
        ' - Customer Details';
}


/**
 * =========================================================
 * PHONE / WHATSAPP NUMBER
 * =========================================================
 */

$cleanPhone = '';

if ($customerPhone !== '') {

    $cleanPhone =
        preg_replace(
            '/[^0-9]/',
            '',
            $customerPhone
        );
}


/**
 * =========================================================
 * FULL ADDRESS
 * =========================================================
 */

$addressParts = [];

if ($customerAddress !== '') {
    $addressParts[] =
        $customerAddress;
}

if ($customerCity !== '') {
    $addressParts[] =
        $customerCity;
}

if ($customerState !== '') {
    $addressParts[] =
        $customerState;
}

if ($customerPincode !== '') {
    $addressParts[] =
        $customerPincode;
}

$fullAddress =
    implode(
        ', ',
        $addressParts
    );


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
        content="Customer details - Ssrini Handicrafts Admin Panel"
    >

    <title>
        <?= e($pageTitle) ?>
        | Ssrini Handicrafts
    </title>


    <!-- =====================================================
         EXISTING ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         CUSTOMER DETAILS CSS
         ===================================================== -->

    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .customer-details-page {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }


        /* =====================================================
           TOP HEADER
           ===================================================== */

        .customer-details-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 20px;
        }


        .customer-header-left {

            display: flex;

            align-items: center;

            gap: 14px;

            min-width: 0;
        }


        .customer-back-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 40px;

            height: 40px;

            border-radius: 10px;

            border: 1px solid
                var(--border-light, #e5e7eb);

            background:
                var(--surface, #ffffff);

            color:
                var(--text-primary, #111827);

            text-decoration: none;

            font-size: 18px;

            flex-shrink: 0;
        }


        .customer-back-button:hover {

            transform: translateY(-1px);

            box-shadow:
                0 5px 15px
                rgba(0, 0, 0, .07);
        }


        .customer-page-title {

            min-width: 0;
        }


        .customer-page-title h1 {

            margin: 0;

            font-size: 24px;

            line-height: 1.3;

            word-break: break-word;
        }


        .customer-page-title p {

            margin: 4px 0 0;

            color:
                var(--text-muted, #6b7280);

            font-size: 12px;
        }


        /* =====================================================
           ERROR
           ===================================================== */

        .customer-error {

            background:
                rgba(239, 68, 68, .08);

            border:
                1px solid
                rgba(239, 68, 68, .18);

            color: #b91c1c;

            border-radius: 12px;

            padding: 16px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        /* =====================================================
           PROFILE CARD
           ===================================================== */

        .customer-profile-card {

            background:
                var(--surface, #ffffff);

            border:
                1px solid
                var(--border-light, #e5e7eb);

            border-radius:
                var(--radius-lg, 14px);

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .04);

            overflow: hidden;

            margin-bottom: 20px;
        }


        .customer-profile-top {

            padding: 24px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            flex-wrap: wrap;
        }


        .customer-profile-main {

            display: flex;

            align-items: center;

            gap: 16px;

            min-width: 0;
        }


        .customer-avatar {

            width: 72px;

            height: 72px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #8b5cf6,
                    #ec4899
                );

            color: #ffffff;

            font-size: 22px;

            font-weight: 700;

            flex-shrink: 0;

            box-shadow:
                0 8px 20px
                rgba(139, 92, 246, .20);
        }


        .customer-profile-name {

            min-width: 0;
        }


        .customer-profile-name h2 {

            margin: 0;

            font-size: 22px;

            line-height: 1.3;

            word-break: break-word;
        }


        .customer-profile-name p {

            margin: 5px 0 0;

            color:
                var(--text-muted, #6b7280);

            font-size: 12px;

            word-break: break-word;
        }


        .customer-actions {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }


        .customer-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            min-height: 38px;

            padding: 8px 13px;

            border-radius: 9px;

            border:
                1px solid
                var(--border-light, #e5e7eb);

            background:
                var(--surface-soft, #f9fafb);

            color:
                var(--text-primary, #111827);

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            transition: .2s ease;
        }


        .customer-action:hover {

            transform: translateY(-1px);

            box-shadow:
                0 5px 15px
                rgba(0, 0, 0, .06);
        }


        .customer-action-whatsapp {

            background:
                rgba(34, 197, 94, .09);

            border-color:
                rgba(34, 197, 94, .18);

            color: #15803d;
        }


        .customer-action-phone {

            background:
                rgba(59, 130, 246, .09);

            border-color:
                rgba(59, 130, 246, .18);

            color: #1d4ed8;
        }


        .customer-action-email {

            background:
                rgba(139, 92, 246, .09);

            border-color:
                rgba(139, 92, 246, .18);

            color: #6d28d9;
        }


        /* =====================================================
           CUSTOMER INFORMATION
           ===================================================== */

        .customer-info-grid {

            border-top:
                1px solid
                var(--border-light, #e5e7eb);

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));
        }


        .customer-info-item {

            padding: 17px 20px;

            border-right:
                1px solid
                var(--border-light, #e5e7eb);

            min-width: 0;
        }


        .customer-info-item:last-child {

            border-right: none;
        }


        .customer-info-label {

            color:
                var(--text-muted, #6b7280);

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .05em;

            font-weight: 700;

            margin-bottom: 5px;
        }


        .customer-info-value {

            color:
                var(--text-primary, #111827);

            font-size: 13px;

            font-weight: 600;

            word-break: break-word;
        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .customer-stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 15px;

            margin-bottom: 20px;
        }


        .customer-stat {

            background:
                var(--surface, #ffffff);

            border:
                1px solid
                var(--border-light, #e5e7eb);

            border-radius:
                var(--radius-lg, 14px);

            padding: 18px;

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .04);

            min-width: 0;
        }


        .customer-stat-icon {

            width: 38px;

            height: 38px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                rgba(139, 92, 246, .10);

            margin-bottom: 12px;

            font-size: 18px;
        }


        .customer-stat-label {

            font-size: 11px;

            color:
                var(--text-muted, #6b7280);

            margin-bottom: 4px;
        }


        .customer-stat-value {

            font-size: 22px;

            font-weight: 750;

            color:
                var(--text-primary, #111827);

            word-break: break-word;
        }


        /* =====================================================
           MAIN GRID
           ===================================================== */

        .customer-main-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 2fr)
                minmax(280px, 1fr);

            gap: 20px;

            align-items: start;
        }


        .customer-card {

            background:
                var(--surface, #ffffff);

            border:
                1px solid
                var(--border-light, #e5e7eb);

            border-radius:
                var(--radius-lg, 14px);

            box-shadow:
                0 5px 20px
                rgba(0, 0, 0, .04);

            overflow: hidden;

            margin-bottom: 20px;
        }


        .customer-card-header {

            padding: 18px 20px;

            border-bottom:
                1px solid
                var(--border-light, #e5e7eb);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            flex-wrap: wrap;
        }


        .customer-card-title {

            font-size: 15px;

            font-weight: 700;

            color:
                var(--text-primary, #111827);
        }


        .customer-card-subtitle {

            margin-top: 4px;

            color:
                var(--text-muted, #6b7280);

            font-size: 11px;
        }


        /* =====================================================
           ORDERS TABLE
           ===================================================== */

        .customer-table-wrapper {

            width: 100%;

            overflow-x: auto;

            -webkit-overflow-scrolling: touch;
        }


        .customer-orders-table {

            width: 100%;

            min-width: 720px;

            border-collapse: collapse;
        }


        .customer-orders-table th {

            padding: 12px 16px;

            background:
                var(--surface-soft, #f9fafb);

            color:
                var(--text-muted, #6b7280);

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .04em;

            text-align: left;

            white-space: nowrap;
        }


        .customer-orders-table td {

            padding: 14px 16px;

            border-top:
                1px solid
                var(--border-light, #e5e7eb);

            color:
                var(--text-secondary, #374151);

            font-size: 12px;

            vertical-align: middle;
        }


        .customer-orders-table tbody tr:hover {

            background:
                rgba(139, 92, 246, .025);
        }


        .order-number-link {

            color:
                #7c3aed;

            font-weight: 700;

            text-decoration: none;
        }


        .order-number-link:hover {

            text-decoration: underline;
        }


        .order-status {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .status-pending {

            background:
                rgba(245, 158, 11, .10);

            color: #b45309;
        }


        .status-processing {

            background:
                rgba(59, 130, 246, .10);

            color: #1d4ed8;
        }


        .status-confirmed {

            background:
                rgba(139, 92, 246, .10);

            color: #6d28d9;
        }


        .status-shipped {

            background:
                rgba(6, 182, 212, .10);

            color: #0e7490;
        }


        .status-delivered {

            background:
                rgba(34, 197, 94, .10);

            color: #15803d;
        }


        .status-cancelled {

            background:
                rgba(239, 68, 68, .10);

            color: #b91c1c;
        }


        .status-returned {

            background:
                rgba(236, 72, 153, .10);

            color: #be185d;
        }


        .status-default {

            background:
                rgba(107, 114, 128, .10);

            color: #4b5563;
        }


        /* =====================================================
           EMPTY ORDERS
           ===================================================== */

        .customer-empty-orders {

            padding: 45px 20px;

            text-align: center;

            color:
                var(--text-muted, #6b7280);
        }


        .customer-empty-orders-icon {

            font-size: 32px;

            margin-bottom: 10px;
        }


        .customer-empty-orders h3 {

            margin: 0;

            font-size: 15px;

            color:
                var(--text-primary, #111827);
        }


        .customer-empty-orders p {

            margin: 5px 0 0;

            font-size: 11px;
        }


        /* =====================================================
           DETAILS LIST
           ===================================================== */

        .customer-details-list {

            padding: 5px 20px 18px;
        }


        .customer-detail-row {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 15px;

            padding: 13px 0;

            border-bottom:
                1px solid
                var(--border-light, #e5e7eb);
        }


        .customer-detail-row:last-child {

            border-bottom: none;
        }


        .customer-detail-label {

            color:
                var(--text-muted, #6b7280);

            font-size: 11px;

            flex-shrink: 0;
        }


        .customer-detail-value {

            color:
                var(--text-primary, #111827);

            font-size: 12px;

            font-weight: 600;

            text-align: right;

            word-break: break-word;

            max-width: 70%;
        }


        /* =====================================================
           ADDRESS
           ===================================================== */

        .customer-address-box {

            padding: 20px;

            color:
                var(--text-secondary, #374151);

            font-size: 12px;

            line-height: 1.7;

            word-break: break-word;
        }


        /* =====================================================
           MOBILE
           ===================================================== */

        @media (max-width: 1100px) {

            .customer-main-grid {

                grid-template-columns:
                    minmax(0, 1fr);
            }

            .customer-info-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .customer-info-item:nth-child(2) {

                border-right: none;
            }

            .customer-info-item:nth-child(n + 3) {

                border-top:
                    1px solid
                    var(--border-light, #e5e7eb);
            }

            .customer-stats-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 700px) {

            .customer-details-header {

                align-items: flex-start;
            }


            .customer-header-left {

                width: 100%;
            }


            .customer-page-title h1 {

                font-size: 20px;
            }


            .customer-profile-top {

                padding: 18px;

                align-items: flex-start;
            }


            .customer-profile-main {

                width: 100%;
            }


            .customer-avatar {

                width: 58px;

                height: 58px;

                font-size: 18px;
            }


            .customer-profile-name h2 {

                font-size: 18px;
            }


            .customer-actions {

                width: 100%;
            }


            .customer-action {

                flex: 1 1 auto;
            }


            .customer-info-grid {

                grid-template-columns:
                    1fr;
            }


            .customer-info-item {

                border-right: none;

                border-top:
                    1px solid
                    var(--border-light, #e5e7eb);
            }


            .customer-info-item:first-child {

                border-top: none;
            }


            .customer-stats-grid {

                grid-template-columns:
                    1fr 1fr;

                gap: 10px;
            }


            .customer-stat {

                padding: 14px;
            }


            .customer-stat-value {

                font-size: 18px;
            }


            .customer-card-header {

                padding: 15px;
            }


            .customer-details-list {

                padding-left: 15px;

                padding-right: 15px;
            }


            .customer-detail-row {

                flex-direction: column;

                gap: 5px;
            }


            .customer-detail-value {

                max-width: 100%;

                text-align: left;
            }
        }


        @media (max-width: 450px) {

            .customer-stats-grid {

                grid-template-columns:
                    1fr;
            }


            .customer-profile-main {

                align-items: flex-start;
            }


            .customer-actions {

                flex-direction: column;
            }


            .customer-action {

                width: 100%;
            }


            .customer-back-button {

                width: 36px;

                height: 36px;
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

    require_once dirname(__DIR__) .
        '/includes/sidebar.php';

    ?>


    <!-- =================================================
         MAIN AREA
         ================================================= -->

    <main class="main-area">


        <!-- =================================================
             HEADER
             ================================================= -->

        <?php

        require_once dirname(__DIR__) .
            '/includes/header.php';

        ?>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <div class="page-content">


            <div class="customer-details-page">


                <!-- =================================================
                     PAGE HEADER
                     ================================================= -->

                <div class="customer-details-header">


                    <div class="customer-header-left">


                        <a
                            href="customers.php"
                            class="customer-back-button"
                            title="Back to Customers"
                        >
                            ←
                        </a>


                        <div class="customer-page-title">

                            <h1>
                                Customer Details
                            </h1>

                            <p>
                                View customer information and order history.
                            </p>

                        </div>


                    </div>


                </div>


                <?php if ($errorMessage !== null): ?>


                    <!-- =================================================
                         ERROR
                         ================================================= -->

                    <div class="customer-error">

                        <?= e($errorMessage) ?>

                    </div>


                <?php else: ?>


                    <!-- =================================================
                         CUSTOMER PROFILE
                         ================================================= -->

                    <section class="customer-profile-card">


                        <div class="customer-profile-top">


                            <div class="customer-profile-main">


                                <div class="customer-avatar">

                                    <?= e(
                                        $customerInitials
                                    ) ?>

                                </div>


                                <div class="customer-profile-name">

                                    <h2>

                                        <?= e(
                                            $customerName
                                        ) ?>

                                    </h2>


                                    <?php if ($customerEmail !== ''): ?>

                                        <p>

                                            <?= e(
                                                $customerEmail
                                            ) ?>

                                        </p>

                                    <?php else: ?>

                                        <p>
                                            No email address available
                                        </p>

                                    <?php endif; ?>

                                </div>


                            </div>


                            <!-- =================================================
                                 ACTIONS
                                 ================================================= -->

                            <div class="customer-actions">


                                <?php if ($cleanPhone !== ''): ?>

                                    <a
                                        href="tel:<?= e($cleanPhone) ?>"
                                        class="customer-action customer-action-phone"
                                    >
                                        📞
                                        Call
                                    </a>


                                    <a
                                        href="https://wa.me/<?= e($cleanPhone) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="customer-action customer-action-whatsapp"
                                    >
                                        💬
                                        WhatsApp
                                    </a>

                                <?php endif; ?>


                                <?php if ($customerEmail !== ''): ?>

                                    <a
                                        href="mailto:<?= e($customerEmail) ?>"
                                        class="customer-action customer-action-email"
                                    >
                                        ✉️
                                        Email
                                    </a>

                                <?php endif; ?>


                            </div>


                        </div>


                        <!-- =================================================
                             BASIC CUSTOMER INFORMATION
                             ================================================= -->

                        <div class="customer-info-grid">


                            <div class="customer-info-item">

                                <div class="customer-info-label">
                                    Customer ID
                                </div>

                                <div class="customer-info-value">
                                    #<?= e(
                                        $customer['id']
                                    ) ?>
                                </div>

                            </div>


                            <div class="customer-info-item">

                                <div class="customer-info-label">
                                    Phone
                                </div>

                                <div class="customer-info-value">

                                    <?= $customerPhone !== ''
                                        ? e($customerPhone)
                                        : '—'
                                    ?>

                                </div>

                            </div>


                            <div class="customer-info-item">

                                <div class="customer-info-label">
                                    City
                                </div>

                                <div class="customer-info-value">

                                    <?= $customerCity !== ''
                                        ? e($customerCity)
                                        : '—'
                                    ?>

                                </div>

                            </div>


                            <div class="customer-info-item">

                                <div class="customer-info-label">
                                    Pincode
                                </div>

                                <div class="customer-info-value">

                                    <?= $customerPincode !== ''
                                        ? e($customerPincode)
                                        : '—'
                                    ?>

                                </div>

                            </div>


                        </div>


                    </section>


                    <!-- =================================================
                         CUSTOMER STATISTICS
                         ================================================= -->

                    <section class="customer-stats-grid">


                        <!-- TOTAL ORDERS -->

                        <div class="customer-stat">

                            <div class="customer-stat-icon">
                                📦
                            </div>

                            <div class="customer-stat-label">
                                Total Orders
                            </div>

                            <div class="customer-stat-value">
                                <?= e($totalOrders) ?>
                            </div>

                        </div>


                        <!-- TOTAL SPENT -->

                        <div class="customer-stat">

                            <div class="customer-stat-icon">
                                ₹
                            </div>

                            <div class="customer-stat-label">
                                Total Spent
                            </div>

                            <div class="customer-stat-value">
                                <?= e(
                                    formatMoney(
                                        $totalSpent
                                    )
                                ) ?>
                            </div>

                        </div>


                        <!-- PENDING -->

                        <div class="customer-stat">

                            <div class="customer-stat-icon">
                                ⏳
                            </div>

                            <div class="customer-stat-label">
                                Active Orders
                            </div>

                            <div class="customer-stat-value">
                                <?= e($pendingOrders) ?>
                            </div>

                        </div>


                        <!-- COMPLETED -->

                        <div class="customer-stat">

                            <div class="customer-stat-icon">
                                ✓
                            </div>

                            <div class="customer-stat-label">
                                Completed Orders
                            </div>

                            <div class="customer-stat-value">
                                <?= e($completedOrders) ?>
                            </div>

                        </div>


                    </section>


                    <!-- =================================================
                         MAIN CONTENT
                         ================================================= -->

                    <section class="customer-main-grid">


                        <!-- =================================================
                             LEFT COLUMN
                             ================================================= -->

                        <div>


                            <!-- =================================================
                                 ORDERS
                                 ================================================= -->

                            <div class="customer-card">


                                <div class="customer-card-header">


                                    <div>

                                        <div class="customer-card-title">
                                            Order History
                                        </div>

                                        <div class="customer-card-subtitle">
                                            All orders placed by this customer.
                                        </div>

                                    </div>


                                    <div class="customer-card-title">
                                        <?= e(
                                            $totalOrders
                                        ) ?>
                                        Orders
                                    </div>


                                </div>


                                <?php if (!empty($orders)): ?>


                                    <div class="customer-table-wrapper">


                                        <table class="customer-orders-table">


                                            <thead>

                                                <tr>

                                                    <th>
                                                        Order
                                                    </th>

                                                    <th>
                                                        Amount
                                                    </th>

                                                    <th>
                                                        Payment
                                                    </th>

                                                    <th>
                                                        Payment Status
                                                    </th>

                                                    <th>
                                                        Order Status
                                                    </th>

                                                    <th>
                                                        Date
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody>


                                                <?php foreach (
                                                    $orders
                                                    as $order
                                                ): ?>


                                                    <tr>


                                                        <td>

                                                            <a
                                                                href="order-details.php?id=<?= e(
                                                                    $order['id']
                                                                ) ?>"
                                                                class="order-number-link"
                                                            >

                                                                #<?= e(
                                                                    $order['order_number']
                                                                    ?: $order['id']
                                                                ) ?>

                                                            </a>

                                                        </td>


                                                        <td>

                                                            <strong>

                                                                <?= e(
                                                                    formatMoney(
                                                                        $order['total_amount']
                                                                    )
                                                                ) ?>

                                                            </strong>

                                                        </td>


                                                        <td>

                                                            <?= e(
                                                                $order['payment_method']
                                                                ?: '—'
                                                            ) ?>

                                                        </td>


                                                        <td>

                                                            <?= e(
                                                                $order['payment_status']
                                                                ?: '—'
                                                            ) ?>

                                                        </td>


                                                        <td>

                                                            <span
                                                                class="order-status <?= e(
                                                                    orderStatusClass(
                                                                        $order['order_status']
                                                                    )
                                                                ) ?>"
                                                            >

                                                                <?= e(
                                                                    orderStatusLabel(
                                                                        $order['order_status']
                                                                    )
                                                                ) ?>

                                                            </span>

                                                        </td>


                                                        <td>

                                                            <?= e(
                                                                formatDateTime(
                                                                    $order['created_at']
                                                                )
                                                            ) ?>

                                                        </td>


                                                    </tr>


                                                <?php endforeach; ?>


                                            </tbody>


                                        </table>


                                    </div>


                                <?php else: ?>


                                    <div class="customer-empty-orders">


                                        <div class="customer-empty-orders-icon">
                                            📦
                                        </div>


                                        <h3>
                                            No orders yet
                                        </h3>


                                        <p>
                                            This customer has not placed any orders.
                                        </p>


                                    </div>


                                <?php endif; ?>


                            </div>


                        </div>


                        <!-- =================================================
                             RIGHT COLUMN
                             ================================================= -->

                        <div>


                            <!-- =================================================
                                 CUSTOMER INFORMATION
                                 ================================================= -->

                            <div class="customer-card">


                                <div class="customer-card-header">

                                    <div>

                                        <div class="customer-card-title">
                                            Customer Information
                                        </div>

                                        <div class="customer-card-subtitle">
                                            Registered customer details.
                                        </div>

                                    </div>

                                </div>


                                <div class="customer-details-list">


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            Full Name
                                        </div>

                                        <div class="customer-detail-value">
                                            <?= e(
                                                $customerName
                                            ) ?>
                                        </div>

                                    </div>


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            Email
                                        </div>

                                        <div class="customer-detail-value">

                                            <?= $customerEmail !== ''
                                                ? e($customerEmail)
                                                : '—'
                                            ?>

                                        </div>

                                    </div>


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            Phone
                                        </div>

                                        <div class="customer-detail-value">

                                            <?= $customerPhone !== ''
                                                ? e($customerPhone)
                                                : '—'
                                            ?>

                                        </div>

                                    </div>


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            State
                                        </div>

                                        <div class="customer-detail-value">

                                            <?= $customerState !== ''? e($customerState): '—'?>

                                        </div>

                                    </div>


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            Registered
                                        </div>

                                        <div class="customer-detail-value">

                                            <?= e(
                                                formatDateTime(
                                                    $customer['created_at']
                                                )
                                            ) ?>

                                        </div>

                                    </div>


                                    <div class="customer-detail-row">

                                        <div class="customer-detail-label">
                                            Updated
                                        </div>

                                        <div class="customer-detail-value">

                                            <?= e(
                                                formatDateTime(
                                                    $customer['updated_at']
                                                )
                                            ) ?>

                                        </div>

                                    </div>


                                </div>


                            </div>


                            <!-- =================================================
                                 ADDRESS
                                 ================================================= -->

                            <div class="customer-card">


                                <div class="customer-card-header">

                                    <div>

                                        <div class="customer-card-title">
                                            Delivery Address
                                        </div>

                                        <div class="customer-card-subtitle">
                                            Customer's saved address.
                                        </div>

                                    </div>

                                </div>


                                <div class="customer-address-box">


                                    <?php if ($fullAddress !== ''): ?>

                                        <?= e(
                                            $fullAddress
                                        ) ?>

                                    <?php else: ?>

                                        Address not available.

                                    <?php endif; ?>


                                </div>


                            </div>


                        </div>


                    </section>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>