<?php

/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ADMIN - INVOICES
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

$pageTitle = 'Invoices';


/**
 * =========================================================
 * HELPER FUNCTIONS
 * =========================================================
 */

/**
 * Safely escape HTML output.
 */
function invoiceEscape($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Find the first existing column from a list
 * of possible column names.
 */
function findInvoiceColumn($columns, $candidates)
{
    foreach ($candidates as $candidate) {

        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}


/**
 * Format amount as Indian currency.
 */
function formatInvoiceAmount($amount)
{
    if ($amount === null || $amount === '') {
        return '₹0';
    }

    return '₹' . number_format(
        (float)$amount,
        2,
        '.',
        ','
    );
}


/**
 * Format invoice date.
 */
function formatInvoiceDate($date)
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return invoiceEscape($date);
    }

    return date(
        'd M Y',
        $timestamp
    );
}


/**
 * Normalize invoice status for display.
 */
function invoiceStatusClass($status)
{
    $status = strtolower(
        trim((string)$status)
    );

    if (
        in_array(
            $status,
            [
                'paid',
                'completed',
                'complete',
                'success',
                'successful'
            ],
            true
        )
    ) {
        return 'invoice-status-success';
    }

    if (
        in_array(
            $status,
            [
                'pending',
                'unpaid',
                'due',
                'processing'
            ],
            true
        )
    ) {
        return 'invoice-status-warning';
    }

    if (
        in_array(
            $status,
            [
                'cancelled',
                'canceled',
                'failed',
                'refunded'
            ],
            true
        )
    ) {
        return 'invoice-status-danger';
    }

    return 'invoice-status-neutral';
}


/**
 * =========================================================
 * DATABASE / INVOICE DATA
 * =========================================================
 */

$invoices = [];

$invoiceColumns = [];

$invoiceTableExists = false;

$databaseError = null;


/**
 * Search / filter values.
 */
$search = trim(
    $_GET['search'] ?? ''
);

$statusFilter = trim(
    $_GET['status'] ?? ''
);


/**
 * =========================================================
 * CHECK INVOICE TABLE
 * =========================================================
 */

try {

    /**
     * Check whether invoices table exists.
     */
    $tableCheck = $pdo->query(
        "
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'invoices'
        "
    );

    $invoiceTableExists =
        ((int)$tableCheck->fetchColumn() > 0);


    /**
     * =====================================================
     * LOAD INVOICE TABLE COLUMNS
     * =====================================================
     */

    if ($invoiceTableExists) {

        $columnStatement = $pdo->query(
            "SHOW COLUMNS FROM invoices"
        );

        $columnRows =
            $columnStatement->fetchAll(
                PDO::FETCH_ASSOC
            );

        foreach ($columnRows as $columnRow) {

            if (
                isset(
                    $columnRow['Field']
                )
            ) {
                $invoiceColumns[] =
                    $columnRow['Field'];
            }
        }
    }


    /**
     * =====================================================
     * DETECT COMMON COLUMN NAMES
     * =====================================================
     */

    $invoiceNumberColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'invoice_number',
                'invoice_no',
                'invoice_num',
                'number'
            ]
        );


    $orderIdColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'order_id',
                'order',
                'order_number'
            ]
        );


    $customerNameColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'customer_name',
                'customer',
                'name',
                'billing_name'
            ]
        );


    $customerEmailColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'customer_email',
                'email',
                'billing_email'
            ]
        );


    $customerPhoneColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'customer_phone',
                'phone',
                'mobile',
                'contact',
                'customer_contact'
            ]
        );


    $totalColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'total_amount',
                'grand_total',
                'total',
                'amount',
                'invoice_total'
            ]
        );


    $subtotalColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'subtotal',
                'sub_total'
            ]
        );


    $taxColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'tax',
                'tax_amount',
                'gst',
                'gst_amount'
            ]
        );


    $discountColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'discount',
                'discount_amount'
            ]
        );


    $paymentMethodColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'payment_method',
                'payment_type',
                'method'
            ]
        );


    $paymentStatusColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'payment_status',
                'paid_status'
            ]
        );


    $statusColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'status',
                'invoice_status'
            ]
        );


    $dateColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'invoice_date',
                'created_at',
                'date',
                'created_on',
                'issued_at'
            ]
        );


    $dueDateColumn =
        findInvoiceColumn(
            $invoiceColumns,
            [
                'due_date',
                'payment_due_date'
            ]
        );


    /**
     * =====================================================
     * LOAD INVOICES
     * =====================================================
     */

    if ($invoiceTableExists) {

        $whereParts = [];

        $parameters = [];


        /**
         * Search.
         */
        if ($search !== '') {

            $searchColumns = [];

            if ($invoiceNumberColumn) {
                $searchColumns[] =
                    $invoiceNumberColumn;
            }

            if ($orderIdColumn) {
                $searchColumns[] =
                    $orderIdColumn;
            }

            if ($customerNameColumn) {
                $searchColumns[] =
                    $customerNameColumn;
            }

            if ($customerEmailColumn) {
                $searchColumns[] =
                    $customerEmailColumn;
            }

            if ($customerPhoneColumn) {
                $searchColumns[] =
                    $customerPhoneColumn;
            }


            if (!empty($searchColumns)) {

                $searchParts = [];

                foreach (
                    $searchColumns
                    as $index => $column
                ) {

                    $parameterName =
                        ':search' . $index;

                    $searchParts[] =
                        "`{$column}` LIKE {$parameterName}";

                    $parameters[
                        $parameterName
                    ] =
                        '%' . $search . '%';
                }


                $whereParts[] =
                    '(' .
                    implode(
                        ' OR ',
                        $searchParts
                    ) .
                    ')';
            }
        }


        /**
         * Status filter.
         */
        if (
            $statusFilter !== '' &&
            $statusColumn
        ) {

            $whereParts[] =
                "`{$statusColumn}` = :status";

            $parameters[':status'] =
                $statusFilter;
        }


        /**
         * Build WHERE clause.
         */
        $whereSql = '';

        if (!empty($whereParts)) {

            $whereSql =
                'WHERE ' .
                implode(
                    ' AND ',
                    $whereParts
                );
        }


        /**
         * Sorting.
         */
        $orderSql = '';


        if ($dateColumn) {

            $orderSql =
                "ORDER BY `{$dateColumn}` DESC";

        } else {

            $orderSql =
                "ORDER BY 1 DESC";
        }


        /**
         * Fetch invoices.
         */
        $invoiceQuery = "
            SELECT *
            FROM invoices
            {$whereSql}
            {$orderSql}
        ";


        $invoiceStatement =
            $pdo->prepare(
                $invoiceQuery
            );


        $invoiceStatement->execute(
            $parameters
        );


        $invoices =
            $invoiceStatement->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


} catch (Throwable $exception) {

    $databaseError =
        $exception->getMessage();

    $invoices = [];
}


/**
 * =========================================================
 * CALCULATE SUMMARY
 * =========================================================
 */

$totalInvoices =
    count($invoices);

$totalInvoiceValue = 0;

$paidInvoices = 0;

$pendingInvoices = 0;


foreach ($invoices as $invoice) {

    /**
     * Total.
     */
    if ($totalColumn) {

        $totalInvoiceValue +=
            (float)(
                $invoice[$totalColumn] ?? 0
            );
    }


    /**
     * Status.
     */
    $invoiceStatus = '';


    if ($paymentStatusColumn) {

        $invoiceStatus =
            $invoice[
                $paymentStatusColumn
            ] ?? '';

    } elseif ($statusColumn) {

        $invoiceStatus =
            $invoice[
                $statusColumn
            ] ?? '';
    }


    $normalizedStatus =
        strtolower(
            trim(
                (string)$invoiceStatus
            )
        );


    if (
        in_array(
            $normalizedStatus,
            [
                'paid',
                'completed',
                'complete',
                'success',
                'successful'
            ],
            true
        )
    ) {

        $paidInvoices++;

    } elseif (
        in_array(
            $normalizedStatus,
            [
                'pending',
                'unpaid',
                'due',
                'processing'
            ],
            true
        )
    ) {

        $pendingInvoices++;
    }
}


/**
 * =========================================================
 * STATUS FILTER OPTIONS
 * =========================================================
 */

$statusOptions = [];

if ($statusColumn && !empty($invoices)) {

    foreach ($invoices as $invoice) {

        $value =
            trim(
                (string)(
                    $invoice[
                        $statusColumn
                    ] ?? ''
                )
            );

        if (
            $value !== '' &&
            !in_array(
                $value,
                $statusOptions,
                true
            )
        ) {

            $statusOptions[] =
                $value;
        }
    }
}


sort($statusOptions);


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
        content="Ssrini Handicrafts Admin Invoices"
    >

    <title>
        <?= invoiceEscape($pageTitle) ?>
        | Ssrini Handicrafts
    </title>


    <!-- =====================================================
         MAIN ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         INVOICE PAGE ONLY CSS
         ===================================================== -->

    <style>

        /*
        ========================================================
        INVOICE FILTER PANEL
        ========================================================
        */

        .invoice-filter-panel {

            background: var(--surface, #ffffff);

            border: 1px solid
                var(--border-light, #eeeaf2);

            border-radius:
                var(--radius-lg, 16px);

            padding: 16px;

            margin-bottom: 20px;

            box-shadow:
                0 8px 25px
                rgba(40, 20, 70, 0.04);

        }


        .invoice-filter-form {

            display: grid;

            grid-template-columns:
                minmax(240px, 1fr)
                220px
                auto
                auto;

            gap: 12px;

            align-items: center;

        }


        .invoice-search-input,
        .invoice-filter-select {

            width: 100%;

            height: 44px;

            border: 1px solid
                var(--border-light, #e7e1ed);

            border-radius: 10px;

            padding:
                0 14px;

            background: #ffffff;

            color:
                var(--text-primary, #1f2937);

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

        }


        .invoice-search-input:focus,
        .invoice-filter-select:focus {

            border-color: #a43bce;

            box-shadow:
                0 0 0 3px
                rgba(164, 59, 206, 0.10);

        }


        /*
        ========================================================
        INVOICE SUMMARY
        ========================================================
        */

        .invoice-summary-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap: 16px;

            margin-bottom: 20px;

        }


        .invoice-summary-card {

            background: #ffffff;

            border:
                1px solid
                var(--border-light, #eeeaf2);

            border-radius:
                var(--radius-lg, 16px);

            padding: 18px;

            box-shadow:
                0 8px 25px
                rgba(40, 20, 70, 0.04);

        }


        .invoice-summary-label {

            font-size: 12px;

            color:
                var(--text-muted, #8b8495);

            margin-bottom: 8px;

        }


        .invoice-summary-value {

            font-size: 24px;

            font-weight: 700;

            color:
                var(--text-primary, #1f2937);

        }


        /*
        ========================================================
        INVOICE TABLE
        ========================================================
        */

        .invoice-table-card {

            background: #ffffff;

            border:
                1px solid
                var(--border-light, #eeeaf2);

            border-radius:
                var(--radius-lg, 16px);

            overflow: hidden;

            box-shadow:
                0 8px 25px
                rgba(40, 20, 70, 0.04);

        }


        .invoice-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .invoice-table {

            width: 100%;

            border-collapse:
                collapse;

            min-width: 900px;

        }


        .invoice-table th {

            padding:
                15px 18px;

            background:
                #faf8fc;

            border-bottom:
                1px solid
                var(--border-light, #eeeaf2);

            color:
                #81788e;

            font-size: 11px;

            font-weight: 700;

            text-transform:
                uppercase;

            letter-spacing:
                0.04em;

            text-align: left;

        }


        .invoice-table td {

            padding:
                16px 18px;

            border-bottom:
                1px solid
                #f0edf3;

            font-size: 13px;

            color:
                var(--text-primary, #273142);

            vertical-align: middle;

        }


        .invoice-table tbody tr {

            transition:
                background 0.2s ease;

        }


        .invoice-table tbody tr:hover {

            background:
                #fcfaff;

        }


        .invoice-table tbody tr:last-child td {

            border-bottom: none;

        }


        .invoice-number {

            font-weight: 700;

            color:
                #7428c8;

        }


        .invoice-customer-name {

            font-weight: 600;

        }


        .invoice-customer-contact {

            color:
                #8b8495;

            font-size: 11px;

            margin-top: 3px;

        }


        .invoice-amount {

            font-weight: 700;

            white-space: nowrap;

        }


        /*
        ========================================================
        STATUS
        ========================================================
        */

        .invoice-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding:
                6px 10px;

            border-radius:
                999px;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;

        }


        .invoice-status-success {

            background:
                #e9f9ef;

            color:
                #159447;

        }


        .invoice-status-warning {

            background:
                #fff6df;

            color:
                #b87800;

        }


        .invoice-status-danger {

            background:
                #fff0f1;

            color:
                #d9343e;

        }


        .invoice-status-neutral {

            background:
                #f1eef6;

            color:
                #766c82;

        }


        /*
        ========================================================
        ACTION BUTTONS
        ========================================================
        */

        .invoice-actions {

            display: flex;

            align-items: center;

            gap: 8px;

        }


        .invoice-action-btn {

            height: 34px;

            padding:
                0 11px;

            border:
                1px solid
                #e7e1ed;

            border-radius: 8px;

            background: #ffffff;

            color:
                #5f566b;

            cursor: pointer;

            font-size: 11px;

            font-weight: 600;

            transition:
                all 0.2s ease;

        }


        .invoice-action-btn:hover {

            transform:
                translateY(-1px);

            border-color:
                #b98bd6;

            color:
                #7627c9;

        }


        .invoice-action-btn.primary {

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            border: none;

            color: #ffffff;

        }


        .invoice-action-btn.primary:hover {

            color: #ffffff;

            box-shadow:
                0 6px 14px
                rgba(118, 39, 201, 0.18);

        }


        /*
        ========================================================
        EMPTY STATE
        ========================================================
        */

        .invoice-empty-state {

            padding:
                65px 25px;

            text-align: center;

        }


        .invoice-empty-icon {

            width: 62px;

            height: 62px;

            margin:
                0 auto 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius:
                18px;

            background:
                #eee8ff;

            font-size: 28px;

        }


        .invoice-empty-state h3 {

            margin: 0 0 7px;

            font-size: 16px;

        }


        .invoice-empty-state p {

            margin: 0;

            color:
                var(--text-muted, #8b8495);

            font-size: 12px;

        }


        /*
        ========================================================
        DATABASE WARNING
        ========================================================
        */

        .invoice-warning {

            background:
                #fff7e8;

            border:
                1px solid
                #f4d79d;

            color:
                #8a5a00;

            border-radius:
                12px;

            padding:
                14px 16px;

            margin-bottom:
                20px;

            font-size: 12px;

        }


        /*
        ========================================================
        INVOICE VIEW MODAL
        ========================================================
        */

        .invoice-modal-overlay {

            position: fixed;

            inset: 0;

            z-index: 9999;

            background:
                rgba(20, 12, 30, 0.58);

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

        }


        .invoice-modal-overlay.active {

            display: flex;

        }


        .invoice-modal {

            width: min(
                850px,
                100%
            );

            max-height:
                calc(100vh - 40px);

            overflow-y: auto;

            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 25px 80px
                rgba(0, 0, 0, 0.22);

        }


        .invoice-modal-header {

            display: flex;

            align-items: center;

            justify-content:
                space-between;

            gap: 15px;

            padding:
                20px 24px;

            border-bottom:
                1px solid
                #eeeaf2;

        }


        .invoice-modal-title {

            margin: 0;

            font-size: 18px;

        }


        .invoice-modal-close {

            width: 34px;

            height: 34px;

            border: none;

            border-radius: 8px;

            background:
                #f3f0f5;

            color:
                #5f5865;

            font-size: 20px;

            cursor: pointer;

        }


        .invoice-modal-body {

            padding:
                24px;

        }


        .invoice-view-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 14px;

        }


        .invoice-view-item {

            padding:
                13px;

            border:
                1px solid
                #eeeaf2;

            border-radius:
                10px;

            background:
                #fcfbfd;

        }


        .invoice-view-label {

            font-size: 10px;

            color:
                #93899e;

            text-transform:
                uppercase;

            font-weight: 700;

            margin-bottom:
                5px;

        }


        .invoice-view-value {

            font-size: 13px;

            font-weight: 600;

            word-break: break-word;

        }


        .invoice-modal-footer {

            display: flex;

            justify-content:
                flex-end;

            gap: 10px;

            padding:
                18px 24px;

            border-top:
                1px solid
                #eeeaf2;

        }


        /*
        ========================================================
        PRINT
        ========================================================
        */

        @media print {

            body * {

                visibility: hidden !important;

            }


            #printInvoiceArea,
            #printInvoiceArea * {

                visibility: visible !important;

            }


            #printInvoiceArea {

                position: absolute;

                left: 0;

                top: 0;

                width: 100%;

                background: #ffffff;

                padding: 30px;

            }

        }


        /*
        ========================================================
        RESPONSIVE
        ========================================================
        */

        @media (max-width: 900px) {

            .invoice-filter-form {

                grid-template-columns:
                    1fr 1fr;

            }


            .invoice-summary-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 600px) {

            .invoice-filter-form {

                grid-template-columns:
                    1fr;

            }


            .invoice-view-grid {

                grid-template-columns:
                    1fr;

            }


            .invoice-modal-overlay {

                padding: 10px;

            }


            .invoice-modal-body {

                padding: 18px;

            }


            .invoice-modal-footer {

                padding: 15px 18px;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <?php

    require_once __DIR__ .
        '/../includes/sidebar.php';

    ?>


    <!-- =====================================================
         MAIN AREA
         ===================================================== -->

    <main class="main-area">


        <!-- =================================================
             TOP HEADER
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

                        Invoices

                    </h1>


                    <p class="page-description">

                        Manage and view your store invoices

                    </p>

                </div>


                <div>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="window.location.reload();"
                    >

                        🔄

                        Refresh

                    </button>

                </div>


            </section>


            <?php if ($databaseError): ?>

                <!-- =================================================
                     DATABASE ERROR
                     ================================================= -->

                <div class="invoice-warning">

                    <strong>
                        Unable to load invoices.
                    </strong>

                    <br>

                    Please check your database connection
                    and the structure of the
                    <strong>invoices</strong> table.

                </div>

            <?php endif; ?>


            <?php if (!$invoiceTableExists && !$databaseError): ?>

                <!-- =================================================
                     TABLE NOT FOUND
                     ================================================= -->

                <div class="invoice-warning">

                    <strong>
                        Invoices table not found.
                    </strong>

                    <br>

                    The invoice page is ready, but the
                    <strong>invoices</strong> table is not
                    available in the current database.

                    No existing database data has been modified.

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SUMMARY CARDS
                 ================================================= -->

            <section class="invoice-summary-grid">


                <!-- TOTAL INVOICES -->

                <div class="invoice-summary-card">

                    <div class="invoice-summary-label">

                        Total Invoices

                    </div>


                    <div class="invoice-summary-value">

                        <?= number_format(
                            $totalInvoices
                        ) ?>

                    </div>

                </div>


                <!-- TOTAL VALUE -->

                <div class="invoice-summary-card">

                    <div class="invoice-summary-label">

                        Invoice Value

                    </div>


                    <div class="invoice-summary-value">

                        <?= formatInvoiceAmount(
                            $totalInvoiceValue
                        ) ?>

                    </div>

                </div>


                <!-- PAID -->

                <div class="invoice-summary-card">

                    <div class="invoice-summary-label">

                        Paid Invoices

                    </div>


                    <div class="invoice-summary-value">

                        <?= number_format(
                            $paidInvoices
                        ) ?>

                    </div>

                </div>


            </section>


            <!-- =================================================
                 FILTER PANEL
                 ================================================= -->

            <section class="invoice-filter-panel">


                <form
                    method="GET"
                    action=""
                    class="invoice-filter-form"
                >


                    <!-- SEARCH -->

                    <input
                        type="search"
                        name="search"
                        class="invoice-search-input"
                        placeholder="Search invoice, order or customer..."
                        value="<?= invoiceEscape(
                            $search
                        ) ?>"
                        autocomplete="off"
                    >


                    <!-- STATUS -->

                    <select
                        name="status"
                        class="invoice-filter-select"
                    >

                        <option value="">

                            All Status

                        </option>


                        <?php foreach (
                            $statusOptions
                            as $option
                        ): ?>

                            <option
                                value="<?= invoiceEscape(
                                    $option
                                ) ?>"
                                <?= (
                                    $statusFilter === $option
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= invoiceEscape(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $option
                                        )
                                    )
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>


                    <!-- FILTER -->

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        🔍

                        Filter

                    </button>


                    <!-- RESET -->

                    <a
                        href="invoices.php"
                        class="btn btn-secondary"
                    >

                        Reset

                    </a>


                </form>


            </section>


            <!-- =================================================
                 INVOICE TABLE
                 ================================================= -->

            <section class="invoice-table-card">


                <!-- TABLE HEADER -->

                <div class="table-header">


                    <div>

                        <div class="table-title">

                            Invoice List

                        </div>


                        <p
                            style="
                                margin-top: 4px;
                                color: var(--text-muted);
                                font-size: 11px;
                            "
                        >

                            All invoices generated for your store

                        </p>

                    </div>


                    <div>

                        <span
                            class="badge badge-success"
                        >

                            <?= number_format(
                                $totalInvoices
                            ) ?>

                            Invoice(s)

                        </span>

                    </div>


                </div>


                <?php if (!empty($invoices)): ?>


                    <div class="invoice-table-wrapper">


                        <table class="invoice-table">


                            <thead>

                                <tr>

                                    <th>
                                        Invoice
                                    </th>

                                    <th>
                                        Customer
                                    </th>

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
                                        Status
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $invoices
                                    as $invoiceIndex =>
                                    $invoice
                                ): ?>


                                    <?php

                                    /**
                                     * Invoice number.
                                     */

                                    $invoiceNumber =
                                        $invoiceNumberColumn
                                            ? (
                                                $invoice[
                                                    $invoiceNumberColumn
                                                ] ?? ''
                                            )
                                            : 'INV-' .
                                                (
                                                    $invoiceIndex + 1
                                                );


                                    if (
                                        trim(
                                            (string)$invoiceNumber
                                        ) === ''
                                    ) {

                                        $invoiceNumber =
                                            'INV-' .
                                            (
                                                $invoiceIndex + 1
                                            );
                                    }


                                    /**
                                     * Order.
                                     */

                                    $orderValue =
                                        $orderIdColumn
                                            ? (
                                                $invoice[
                                                    $orderIdColumn
                                                ] ?? '-'
                                            )
                                            : '-';


                                    /**
                                     * Customer.
                                     */

                                    $customerName =
                                        $customerNameColumn
                                            ? (
                                                $invoice[
                                                    $customerNameColumn
                                                ] ?? 'Customer'
                                            )
                                            : 'Customer';


                                    if (
                                        trim(
                                            (string)$customerName
                                        ) === ''
                                    ) {

                                        $customerName =
                                            'Customer';
                                    }


                                    /**
                                     * Customer contact.
                                     */

                                    $customerContact = '';


                                    if ($customerPhoneColumn) {

                                        $customerContact =
                                            $invoice[
                                                $customerPhoneColumn
                                            ] ?? '';
                                    }


                                    if (
                                        $customerContact === '' &&
                                        $customerEmailColumn
                                    ) {

                                        $customerContact =
                                            $invoice[
                                                $customerEmailColumn
                                            ] ?? '';
                                    }


                                    /**
                                     * Amount.
                                     */

                                    $invoiceAmount =
                                        $totalColumn
                                            ? (
                                                $invoice[
                                                    $totalColumn
                                                ] ?? 0
                                            )
                                            : 0;


                                    /**
                                     * Payment method.
                                     */

                                    $paymentMethod =
                                        $paymentMethodColumn
                                            ? (
                                                $invoice[
                                                    $paymentMethodColumn
                                                ] ?? 'COD'
                                            )
                                            : 'COD';


                                    if (
                                        trim(
                                            (string)$paymentMethod
                                        ) === ''
                                    ) {

                                        $paymentMethod =
                                            'COD';
                                    }


                                    /**
                                     * Status.
                                     */

                                    $displayStatus = 'Pending';


                                    if ($paymentStatusColumn) {

                                        $displayStatus =
                                            $invoice[
                                                $paymentStatusColumn
                                            ] ?? 'Pending';

                                    } elseif ($statusColumn) {

                                        $displayStatus =
                                            $invoice[
                                                $statusColumn
                                            ] ?? 'Pending';
                                    }


                                    if (
                                        trim(
                                            (string)$displayStatus
                                        ) === ''
                                    ) {

                                        $displayStatus =
                                            'Pending';
                                    }


                                    /**
                                     * Date.
                                     */

                                    $invoiceDate =
                                        $dateColumn
                                            ? (
                                                $invoice[
                                                    $dateColumn
                                                ] ?? null
                                            )
                                            : null;


                                    /**
                                     * JSON for modal.
                                     */

                                    $invoiceJson =
                                        json_encode(
                                            $invoice,
                                            JSON_HEX_TAG |
                                            JSON_HEX_APOS |
                                            JSON_HEX_QUOT |
                                            JSON_HEX_AMP
                                        );

                                    ?>


                                    <tr>


                                        <!-- INVOICE -->

                                        <td>

                                            <div
                                                class="invoice-number"
                                            >

                                                <?= invoiceEscape(
                                                    $invoiceNumber
                                                ) ?>

                                            </div>

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>

                                            <div
                                                class="invoice-customer-name"
                                            >

                                                <?= invoiceEscape(
                                                    $customerName
                                                ) ?>

                                            </div>


                                            <?php if (
                                                $customerContact !== ''
                                            ): ?>

                                                <div
                                                    class="invoice-customer-contact"
                                                >

                                                    <?= invoiceEscape(
                                                        $customerContact
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ORDER -->

                                        <td>

                                            <?= invoiceEscape(
                                                $orderValue
                                            ) ?>

                                        </td>


                                        <!-- AMOUNT -->

                                        <td>

                                            <span
                                                class="invoice-amount"
                                            >

                                                <?= formatInvoiceAmount(
                                                    $invoiceAmount
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- PAYMENT -->

                                        <td>

                                            <?= invoiceEscape(
                                                $paymentMethod
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="
                                                    invoice-status
                                                    <?= invoiceStatusClass(
                                                        $displayStatus
                                                    ) ?>
                                                "
                                            >

                                                ●

                                                <?= invoiceEscape(
                                                    ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $displayStatus
                                                        )
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <?= formatInvoiceDate(
                                                $invoiceDate
                                            ) ?>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <div
                                                class="invoice-actions"
                                            >


                                                <button
                                                    type="button"
                                                    class="invoice-action-btn primary"
                                                    onclick='viewInvoice(
                                                        <?= $invoiceJson ?>
                                                    )'
                                                >

                                                    View

                                                </button>


                                                <button
                                                    type="button"
                                                    class="invoice-action-btn"
                                                    onclick='printInvoice(
                                                        <?= $invoiceJson ?>
                                                    )'
                                                >

                                                    Print

                                                </button>


                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <!-- =================================================
                         EMPTY STATE
                         ================================================= -->

                    <div class="invoice-empty-state">


                        <div
                            class="invoice-empty-icon"
                        >

                            🧾

                        </div>


                        <h3>

                            No invoices found

                        </h3>


                        <p>

                            <?php if (
                                $search !== '' ||
                                $statusFilter !== ''
                            ): ?>

                                No invoices match your
                                current search or filter.

                            <?php else: ?>

                                Generated invoices will
                                appear here.

                            <?php endif; ?>

                        </p>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </main>


</div>


<!-- =========================================================
     INVOICE VIEW MODAL
     ========================================================= -->

<div
    class="invoice-modal-overlay"
    id="invoiceModal"
    aria-hidden="true"
>


    <div
        class="invoice-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="invoiceModalTitle"
    >


        <!-- MODAL HEADER -->

        <div
            class="invoice-modal-header"
        >

            <h2
                class="invoice-modal-title"
                id="invoiceModalTitle"
            >

                Invoice Details

            </h2>


            <button
                type="button"
                class="invoice-modal-close"
                id="invoiceModalClose"
                aria-label="Close"
            >

                &times;

            </button>

        </div>


        <!-- MODAL BODY -->

        <div
            class="invoice-modal-body"
            id="invoiceModalBody"
        >

            <!-- JavaScript will insert invoice data -->

        </div>


        <!-- MODAL FOOTER -->

        <div
            class="invoice-modal-footer"
        >

            <button
                type="button"
                class="btn btn-secondary"
                id="invoiceModalCancel"
            >

                Close

            </button>


            <button
                type="button"
                class="btn btn-primary"
                id="invoiceModalPrint"
            >

                🖨️

                Print Invoice

            </button>

        </div>


    </div>

</div>


<!-- =========================================================
     PRINT AREA
     ========================================================= -->

<div
    id="printInvoiceArea"
    style="display:none;"
></div>


<!-- =========================================================
     ADMIN JAVASCRIPT
     ========================================================= -->

<script
    src="../assets/js/admin.js"
></script>


<script>

/*
=========================================================
INVOICE PAGE JAVASCRIPT
=========================================================
*/

const invoiceModal =
    document.getElementById(
        'invoiceModal'
    );


const invoiceModalBody =
    document.getElementById(
        'invoiceModalBody'
    );


const invoiceModalClose =
    document.getElementById(
        'invoiceModalClose'
    );


const invoiceModalCancel =
    document.getElementById(
        'invoiceModalCancel'
    );


const invoiceModalPrint =
    document.getElementById(
        'invoiceModalPrint'
    );


let currentInvoice = null;


/*
=========================================================
ESCAPE HTML
=========================================================
*/

function escapeInvoiceHTML(value)
{
    const element =
        document.createElement(
            'div'
        );

    element.textContent =
        value ?? '';

    return element.innerHTML;
}


/*
=========================================================
FORMAT KEY
=========================================================
*/

function formatInvoiceKey(key)
{
    return key

        .replace(
            /_/g,
            ' '
        )

        .replace(
            /\b\w/g,
            function (letter) {
                return letter.toUpperCase();
            }
        );
}


/*
=========================================================
FORMAT VALUE
=========================================================
*/

function formatInvoiceValue(
    key,
    value
)
{
    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {

        return '-';
    }


    const lowerKey =
        key.toLowerCase();


    /*
    Currency fields.
    */

    if (
        lowerKey.includes('amount') ||
        lowerKey.includes('total') ||
        lowerKey.includes('subtotal') ||
        lowerKey.includes('sub_total') ||
        lowerKey.includes('discount') ||
        lowerKey.includes('tax') ||
        lowerKey.includes('gst')
    ) {

        const number =
            Number(value);


        if (
            !Number.isNaN(number)
        ) {

            return '₹' +
                number.toLocaleString(
                    'en-IN',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );
        }
    }


    /*
    Date fields.
    */

    if (
        lowerKey.includes('date') ||
        lowerKey.includes('created_at') ||
        lowerKey.includes('updated_at') ||
        lowerKey.includes('issued_at')
    ) {

        const date =
            new Date(value);


        if (
            !Number.isNaN(
                date.getTime()
            )
        ) {

            return date.toLocaleString(
                'en-IN',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );
        }
    }


    return String(value);
}


/*
=========================================================
VIEW INVOICE
=========================================================
*/

function viewInvoice(invoice)
{
    currentInvoice =
        invoice;


    invoiceModalBody.innerHTML =
        '';


    const grid =
        document.createElement(
            'div'
        );


    grid.className =
        'invoice-view-grid';


    Object.keys(invoice)
        .forEach(
            function (key) {

                const item =
                    document.createElement(
                        'div'
                    );


                item.className =
                    'invoice-view-item';


                const label =
                    document.createElement(
                        'div'
                    );


                label.className =
                    'invoice-view-label';


                label.textContent =
                    formatInvoiceKey(
                        key
                    );


                const value =
                    document.createElement(
                        'div'
                    );


                value.className =
                    'invoice-view-value';


                value.textContent =
                    formatInvoiceValue(
                        key,
                        invoice[key]
                    );


                item.appendChild(
                    label
                );


                item.appendChild(
                    value
                );


                grid.appendChild(
                    item
                );

            }
        );


    invoiceModalBody.appendChild(
        grid
    );


    invoiceModal.classList.add(
        'active'
    );


    invoiceModal.setAttribute(
        'aria-hidden',
        'false'
    );


    document.body.style.overflow =
        'hidden';
}


/*
=========================================================
CLOSE MODAL
=========================================================
*/

function closeInvoiceModal()
{
    invoiceModal.classList.remove(
        'active'
    );


    invoiceModal.setAttribute(
        'aria-hidden',
        'true'
    );


    document.body.style.overflow =
        '';


    currentInvoice =
        null;
}


/*
=========================================================
MODAL CLOSE EVENTS
=========================================================
*/

invoiceModalClose.addEventListener(
    'click',
    closeInvoiceModal
);


invoiceModalCancel.addEventListener(
    'click',
    closeInvoiceModal
);


/*
=========================================================
CLICK OUTSIDE
=========================================================
*/

invoiceModal.addEventListener(
    'click',
    function (event) {

        if (
            event.target ===
            invoiceModal
        ) {

            closeInvoiceModal();
        }

    }
);


/*
=========================================================
ESC KEY
=========================================================
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Escape' &&
            invoiceModal.classList.contains(
                'active'
            )
        ) {

            closeInvoiceModal();
        }

    }
);


/*
=========================================================
PRINT CURRENT MODAL INVOICE
=========================================================
*/

invoiceModalPrint.addEventListener(
    'click',
    function () {

        if (!currentInvoice) {
            return;
        }


        printInvoice(
            currentInvoice
        );

    }
);


/*
=========================================================
PRINT INVOICE
=========================================================
*/

function printInvoice(invoice)
{
    const printArea =
        document.getElementById(
            'printInvoiceArea'
        );


    let html = '';


    html += `
        <div
            style="
                font-family: Arial, sans-serif;
                color: #222;
                max-width: 900px;
                margin: 0 auto;
            "
        >

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:flex-start;
                    margin-bottom:30px;
                    border-bottom:2px solid #7627c9;
                    padding-bottom:20px;
                "
            >

                <div>

                    <h1
                        style="
                            margin:0 0 6px;
                            font-size:28px;
                        "
                    >
                        Ssrini Handicrafts
                    </h1>

                    <p
                        style="
                            margin:0;
                            color:#777;
                            font-size:13px;
                        "
                    >
                        Invoice
                    </p>

                </div>

                <div
                    style="
                        text-align:right;
                        font-size:13px;
                    "
                >

                    <strong>
                        Invoice
                    </strong>

                    <br>

                    ${escapeInvoiceHTML(
                        getInvoiceNumber(
                            invoice
                        )
                    )}

                </div>

            </div>
    `;


    html += `
        <div
            style="
                display:grid;
                grid-template-columns:1fr 1fr;
                gap:15px;
                margin-bottom:25px;
            "
        >
    `;


    Object.keys(invoice)
        .forEach(
            function (key) {

                html += `
                    <div
                        style="
                            border:1px solid #e5e0e9;
                            border-radius:8px;
                            padding:12px;
                            background:#faf9fb;
                        "
                    >

                        <div
                            style="
                                font-size:10px;
                                color:#8a8192;
                                text-transform:uppercase;
                                font-weight:bold;
                                margin-bottom:5px;
                            "
                        >

                            ${escapeInvoiceHTML(
                                formatInvoiceKey(
                                    key
                                )
                            )}

                        </div>

                        <div
                            style="
                                font-size:13px;
                                font-weight:600;
                            "
                        >

                            ${escapeInvoiceHTML(
                                formatInvoiceValue(
                                    key,
                                    invoice[key]
                                )
                            )}

                        </div>

                    </div>
                `;

            }
        );


    html += `
        </div>


        <div
            style="
                margin-top:35px;
                padding-top:15px;
                border-top:1px solid #ddd;
                font-size:11px;
                color:#777;
                text-align:center;
            "
        >

            Thank you for shopping with
            Ssrini Handicrafts.

        </div>

        </div>
    `;


    printArea.innerHTML =
        html;


    printArea.style.display =
        'block';


    window.print();


    printArea.style.display =
        'none';

}


/*
=========================================================
GET INVOICE NUMBER
=========================================================
*/

function getInvoiceNumber(
    invoice
)
{
    const possibleKeys = [
        'invoice_number',
        'invoice_no',
        'invoice_num',
        'number',
        'id'
    ];


    for (
        const key of possibleKeys
    ) {

        if (
            invoice[key] !== undefined &&
            invoice[key] !== null &&
            invoice[key] !== ''
        ) {

            return invoice[key];
        }

    }


    return 'Invoice';
}

</script>


</body>

</html>