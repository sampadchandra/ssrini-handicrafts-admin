<?php
/**
 * =========================================================
 * SSRINI HANDICRAFTS - ADMIN PANEL
 * INVOICE VIEW
 * =========================================================
 * File: admin/invoice-view.php
 * Purpose: View a complete invoice/order invoice.
 * Expected URL: invoice-view.php?id=1
 */

/*
|--------------------------------------------------------------------------
| DATABASE & AUTHENTICATION
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'Invoice';

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

/**
 * Safely escape HTML.
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Return first available value from an array.
 */
function firstValue(array $row, array $keys, $default = '') {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
}

/**
 * Format money according to Indian format.
 */
function money($amount) {
    return '₹' . number_format((float)($amount ?? 0), 2, '.', ',');
}

/**
 * Format date safely.
 */
function formatDate($date) {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return $timestamp === false ? e($date) : date('d M Y', $timestamp);
}

/**
 * Format date and time safely.
 */
function formatDateTime($date) {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return $timestamp === false ? e($date) : date('d M Y, h:i A', $timestamp);
}

/**
 * Convert status into readable text.
 */
function readableStatus($status) {
    if ($status === '') return 'Pending';
    return ucwords(str_replace(['_', '-'], ' ', strtolower($status)));
}

/**
 * Return CSS class for status.
 */
function statusClass($status) {
    $status = strtolower(trim((string)$status));
    switch ($status) {
        case 'paid':
        case 'completed':
        case 'delivered':
        case 'success':
            return 'status-success';

        case 'pending':
        case 'processing':
        case 'confirmed':
            return 'status-warning';

        case 'cancelled':
        case 'canceled':
        case 'failed':
        case 'refunded':
            return 'status-danger';

        default:
            return 'status-neutral';
    }
}

/*
|--------------------------------------------------------------------------
| READ & VALIDATE INVOICE ID
|--------------------------------------------------------------------------
*/
$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoiceId <= 0) {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Invoice | Ssrini Handicrafts</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f7f5fb; font-family: Inter, Arial, sans-serif; color: #25212d; }
            .error-box { width: min(500px, calc(100% - 40px)); background: #ffffff; border-radius: 18px; padding: 35px; text-align: center; box-shadow: 0 15px 40px rgba(30, 20, 50, 0.08); }
            .error-icon { width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: #f1e7ff; font-size: 28px; }
            .error-box h1 { margin: 0 0 10px; font-size: 24px; }
            .error-box p { margin: 0 0 22px; color: #77717f; font-size: 14px; }
            .back-btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 20px; border-radius: 10px; text-decoration: none; color: #ffffff; font-weight: 600; background: linear-gradient(135deg, #7627c9, #c52b9f); }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🧾</div>
            <h1>Invalid Invoice</h1>
            <p>No valid invoice ID was provided.</p>
            <a href="invoices.php" class="back-btn">← Back to Invoices</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH INVOICE
|--------------------------------------------------------------------------
*/
try {
    $invoiceStatement = $pdo->prepare("SELECT * FROM invoices WHERE id = :id LIMIT 1");
    $invoiceStatement->execute([':id' => $invoiceId]);
    $invoice = $invoiceStatement->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    http_response_code(500);
    die('Unable to load invoice.');
}

if (!$invoice) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice Not Found | Ssrini Handicrafts</title>
        <style>
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f7f5fb; font-family: Inter, Arial, sans-serif; color: #25212d; }
            .error-box { width: min(500px, calc(100% - 40px)); background: #ffffff; border-radius: 18px; padding: 35px; text-align: center; box-shadow: 0 15px 40px rgba(30, 20, 50, 0.08); }
            .error-icon { width: 64px; height: 64px; margin: 0 auto 18px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: #f1e7ff; font-size: 28px; }
            .error-box h1 { margin: 0 0 10px; font-size: 24px; }
            .error-box p { margin: 0 0 22px; color: #77717f; font-size: 14px; }
            .back-btn { display: inline-flex; align-items: center; justify-content: center; padding: 11px 20px; border-radius: 10px; text-decoration: none; color: #ffffff; font-weight: 600; background: linear-gradient(135deg, #7627c9, #c52b9f); }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🧾</div>
            <h1>Invoice Not Found</h1>
            <p>The requested invoice does not exist or has already been removed.</p>
            <a href="invoices.php" class="back-btn">← Back to Invoices</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/*
|--------------------------------------------------------------------------
| INVOICE BASIC INFORMATION & AMOUNTS
|--------------------------------------------------------------------------
*/
$invoiceNumber = firstValue($invoice, ['invoice_number', 'invoice_no', 'number', 'invoice_id'], 'INV-' . str_pad($invoiceId, 5, '0', STR_PAD_LEFT));
$invoiceDate   = firstValue($invoice, ['invoice_date', 'issued_at', 'created_at', 'date'], '');
$invoiceStatus = firstValue($invoice, ['status', 'payment_status', 'invoice_status'], 'pending');
$paymentMethod = firstValue($invoice, ['payment_method', 'payment_type', 'method'], 'Cash on Delivery');
$paymentStatus = firstValue($invoice, ['payment_status', 'paid_status'], '');

$orderId       = (int) firstValue($invoice, ['order_id', 'order'], 0);
$customerId    = (int) firstValue($invoice, ['customer_id', 'user_id'], 0);

$subtotal      = (float) firstValue($invoice, ['subtotal', 'sub_total'], 0);
$discount      = (float) firstValue($invoice, ['discount', 'discount_amount'], 0);
$tax           = (float) firstValue($invoice, ['tax', 'tax_amount', 'gst', 'gst_amount'], 0);
$shipping      = (float) firstValue($invoice, ['shipping', 'shipping_charge', 'delivery_charge'], 0);
$totalAmount   = (float) firstValue($invoice, ['total_amount', 'grand_total', 'total', 'amount'], 0);

/*
|--------------------------------------------------------------------------
| FETCH ORDER & CUSTOMER DETAILS
|--------------------------------------------------------------------------
*/
$order = [];
if ($orderId > 0) {
    try {
        $orderStatement = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
        $orderStatement->execute([':id' => $orderId]);
        $order = $orderStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        $order = [];
    }
}

if ($orderId <= 0) {
    $orderId = (int) firstValue($order, ['id', 'order_id'], 0);
}

$orderNumber = firstValue($order, ['order_number', 'order_no', 'number'], $orderId > 0 ? '#' . $orderId : '-');
$orderStatus = firstValue($order, ['status', 'order_status'], $invoiceStatus);

$customer = [];
if ($customerId > 0) {
    try {
        $customerStatement = $pdo->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
        $customerStatement->execute([':id' => $customerId]);
        $customer = $customerStatement->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $exception) {
        $customer = [];
    }
}

$customerName    = firstValue($customer, ['name', 'full_name', 'customer_name'], firstValue($order, ['customer_name', 'name', 'full_name'], 'Customer'));
$customerEmail   = firstValue($customer, ['email', 'email_address'], firstValue($order, ['customer_email', 'email'], ''));
$customerPhone   = firstValue($customer, ['phone', 'mobile', 'phone_number'], firstValue($order, ['customer_phone', 'phone', 'mobile'], ''));
$customerAddress = firstValue($customer, ['address', 'full_address'], firstValue($order, ['shipping_address', 'billing_address', 'address'], ''));
$customerCity    = firstValue($customer, ['city'], firstValue($order, ['shipping_city', 'billing_city', 'city'], ''));
$customerState   = firstValue($customer, ['state', 'state_name'], firstValue($order, ['shipping_state', 'billing_state', 'state'], ''));
$customerPincode = firstValue($customer, ['pincode', 'postal_code', 'zip_code'], firstValue($order, ['shipping_pincode', 'billing_pincode', 'pincode', 'postal_code'], ''));

/*
|--------------------------------------------------------------------------
| FETCH ORDER ITEMS & PRODUCTS
|--------------------------------------------------------------------------
*/
$orderItems = [];
if ($orderId > 0) {
    try {
        $itemsStatement = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC");
        $itemsStatement->execute([':order_id' => $orderId]);
        $orderItems = $itemsStatement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        $orderItems = [];
    }
}

$productIds = [];
foreach ($orderItems as $item) {
    $productId = (int) firstValue($item, ['product_id', 'product'], 0);
    if ($productId > 0) {
        $productIds[] = $productId;
    }
}
$productIds = array_values(array_unique($productIds));

$products = [];
if (!empty($productIds)) {
    try {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $productStatement = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $productStatement->execute($productIds);
        $productRows = $productStatement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productRows as $productRow) {
            $productId = (int) firstValue($productRow, ['id'], 0);
            if ($productId > 0) {
                $products[$productId] = $productRow;
            }
        }
    } catch (PDOException $exception) {
        $products = [];
    }
}

/*
|--------------------------------------------------------------------------
| CALCULATE ITEM TOTALS & FALLBACKS
|--------------------------------------------------------------------------
*/
$calculatedSubtotal = 0;

foreach ($orderItems as &$item) {
    $productId = (int) firstValue($item, ['product_id', 'product'], 0);
    $product   = $products[$productId] ?? [];

    $itemName  = firstValue($item, ['product_name', 'name', 'title'], firstValue($product, ['name', 'product_name', 'title'], 'Product'));
    $itemCode  = firstValue($item, ['product_code', 'code', 'sku'], firstValue($product, ['product_code', 'sku', 'code'], ''));
    $quantity  = (float) firstValue($item, ['quantity', 'qty'], 1);
    $unitPrice = (float) firstValue($item, ['unit_price', 'price', 'selling_price'], firstValue($product, ['discount_price', 'price', 'selling_price'], 0));
    $itemTotal = (float) firstValue($item, ['total', 'total_price', 'line_total', 'amount'], $quantity * $unitPrice);

    $calculatedSubtotal += $itemTotal;

    $item['_display_name']     = $itemName;
    $item['_display_code']     = $itemCode;
    $item['_display_quantity'] = $quantity;
    $item['_display_price']    = $unitPrice;
    $item['_display_total']    = $itemTotal;
}
unset($item);

if ($subtotal <= 0 && $calculatedSubtotal > 0) {
    $subtotal = $calculatedSubtotal;
}

if ($totalAmount <= 0) {
    $totalAmount = $subtotal - $discount + $tax + $shipping;
}

$addressParts = array_filter([$customerAddress, $customerCity, $customerState, $customerPincode]);
$fullCustomerAddress = implode(', ', $addressParts);

$displayPaymentStatus = $paymentStatus !== '' ? readableStatus($paymentStatus) : readableStatus($invoiceStatus);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Invoice View | Ssrini Handicrafts">
    <title>Invoice <?= e($invoiceNumber) ?> | Ssrini Handicrafts</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .invoice-page { padding-bottom: 40px; }
        
        /* PAGE HEADER */
        .invoice-page-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 22px; flex-wrap: wrap; }
        .invoice-page-title h1 { margin: 0; font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .invoice-page-title p { margin: 5px 0 0; font-size: 12px; color: var(--text-muted); }
        .invoice-page-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* BUTTONS */
        .invoice-action-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 42px; padding: 0 16px; border-radius: 10px; border: 1px solid var(--border-light); background: #ffffff; color: var(--text-primary); text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(30, 20, 50, 0.04); transition: all 0.2s ease; }
        .invoice-action-btn:hover { transform: translateY(-1px); box-shadow: 0 7px 16px rgba(30, 20, 50, 0.08); }
        .invoice-action-btn.primary { border: none; color: #ffffff; background: linear-gradient(135deg, #7627c9, #c52b9f); box-shadow: 0 7px 16px rgba(118, 39, 201, 0.20); }

        /* INVOICE CARD */
        .invoice-wrapper { max-width: 1050px; margin: 0 auto; }
        .invoice-card { background: #ffffff; border-radius: 18px; box-shadow: 0 10px 35px rgba(30, 20, 50, 0.07); overflow: hidden; }

        /* HEADER & BRAND */
        .invoice-top { padding: 32px; border-bottom: 1px solid #eeeaf2; display: flex; justify-content: space-between; gap: 30px; flex-wrap: wrap; }
        .invoice-brand { min-width: 220px; }
        .invoice-brand-name { font-size: 25px; font-weight: 800; background: linear-gradient(135deg, #7627c9, #c52b9f); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .invoice-brand-subtitle { margin-top: 5px; font-size: 11px; color: var(--text-muted); }
        .invoice-heading { text-align: right; }
        .invoice-heading h2 { margin: 0; font-size: 27px; font-weight: 700; color: var(--text-primary); }
        .invoice-number { margin-top: 6px; font-size: 13px; font-weight: 600; color: var(--text-secondary); }

        /* META GRID */
        .invoice-meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 22px 32px; background: #fcfbfd; border-bottom: 1px solid #eeeaf2; }
        .invoice-meta-box { padding: 15px; border: 1px solid #eeeaf2; border-radius: 12px; background: #ffffff; }
        .invoice-meta-label { margin-bottom: 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
        .invoice-meta-value { font-size: 13px; font-weight: 650; color: var(--text-primary); }

        /* STATUS STYLES */
        .invoice-status { display: inline-flex; align-items: center; width: fit-content; padding: 5px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .status-success { color: #11834b; background: #e9f9f0; }
        .status-warning { color: #9a6500; background: #fff5dc; }
        .status-danger { color: #c62e3c; background: #ffedf0; }
        .status-neutral { color: #665f70; background: #f1eef5; }

        /* CUSTOMER SECTION */
        .invoice-information { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 30px; padding: 30px 32px; }
        .information-title { margin-bottom: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
        .customer-name { margin-bottom: 7px; font-size: 15px; font-weight: 700; color: var(--text-primary); }
        .customer-detail { margin-top: 4px; font-size: 12px; line-height: 1.6; color: var(--text-secondary); }

        /* ITEMS TABLE */
        .invoice-items { padding: 0 32px 30px; }
        .invoice-items-table-wrapper { width: 100%; overflow-x: auto; border: 1px solid #eeeaf2; border-radius: 12px; }
        .invoice-items-table { width: 100%; border-collapse: collapse; min-width: 650px; }
        .invoice-items-table th { padding: 13px 14px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; color: #77717f; background: #faf8fc; border-bottom: 1px solid #eeeaf2; }
        .invoice-items-table td { padding: 15px 14px; font-size: 12px; color: var(--text-secondary); border-bottom: 1px solid #f0edf3; }
        .invoice-items-table tr:last-child td { border-bottom: none; }
        .invoice-product-name { font-weight: 650; color: var(--text-primary); }
        .invoice-product-code { margin-top: 3px; font-size: 10px; color: var(--text-muted); }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .invoice-empty-items { padding: 35px; text-align: center; color: var(--text-muted); font-size: 12px; }

        /* BOTTOM SECTION */
        .invoice-bottom { display: grid; grid-template-columns: minmax(0, 1fr) minmax(300px, 400px); gap: 30px; padding: 0 32px 32px; }
        .invoice-notes { padding-top: 8px; }
        .invoice-notes-title { margin-bottom: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
        .invoice-notes-text { max-width: 500px; font-size: 12px; line-height: 1.7; color: var(--text-secondary); }
        .invoice-summary { padding: 20px; border: 1px solid #eeeaf2; border-radius: 14px; background: #fcfbfd; }
        .summary-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 10px; font-size: 12px; color: var(--text-secondary); }
        .summary-row strong { color: var(--text-primary); }
        .summary-total { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e7e2eb; }
        .summary-total span { font-size: 14px; font-weight: 700; color: var(--text-primary); }
        .summary-total strong { font-size: 21px; font-weight: 800; color: #7627c9; }

        /* FOOTER */
        .invoice-footer { padding: 18px 32px; text-align: center; border-top: 1px solid #eeeaf2; background: #fcfbfd; font-size: 10px; color: var(--text-muted); }

        /* PRINT STYLES */
        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #ffffff !important; }
            .sidebar, .admin-sidebar, .top-header, .header, .invoice-page-header, .invoice-page-actions { display: none !important; }
            .main-area { margin: 0 !important; width: 100% !important; }
            .page-content, .invoice-page { padding: 0 !important; }
            .invoice-wrapper { max-width: none; width: 100%; }
            .invoice-card { box-shadow: none !important; border: 1px solid #dddddd; border-radius: 0; }
            .invoice-top, .invoice-meta, .invoice-information, .invoice-items, .invoice-bottom { break-inside: avoid; }
            .invoice-items-table { min-width: 0; }
        }

        /* RESPONSIVE */
        @media (max-width: 850px) {
            .invoice-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .invoice-bottom { grid-template-columns: 1fr; }
        }
        @media (max-width: 650px) {
            .invoice-page-title h1 { font-size: 24px; }
            .invoice-top { padding: 24px 20px; }
            .invoice-heading { text-align: left; }
            .invoice-heading h2 { font-size: 23px; }
            .invoice-meta, .invoice-information { grid-template-columns: 1fr; padding: 18px 20px; gap: 22px; }
            .invoice-items, .invoice-bottom, .invoice-footer { padding: 0 20px 24px; }
            .invoice-page-actions { width: 100%; }
            .invoice-action-btn { flex: 1; }
        }
    </style>
</head>

<body>
<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN AREA -->
    <main class="main-area">

        <!-- HEADER -->
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <!-- PAGE CONTENT -->
        <div class="page-content invoice-page">

            <!-- PAGE HEADER -->
            <section class="invoice-page-header">
                <div class="invoice-page-title">
                    <h1>Invoice</h1>
                    <p>View invoice details and print the invoice.</p>
                </div>
                <div class="invoice-page-actions">
                    <a href="invoices.php" class="invoice-action-btn">← Back</a>
                    <button type="button" class="invoice-action-btn primary" onclick="window.print()">🖨️ Print Invoice</button>
                </div>
            </section>

            <!-- INVOICE -->
            <div class="invoice-wrapper">
                <article class="invoice-card">

                    <!-- TOP SECTION -->
                    <section class="invoice-top">
                        <div class="invoice-brand">
                            <div class="invoice-brand-name">Ssrini Handicrafts</div>
                            <div class="invoice-brand-subtitle">Handcrafted with care</div>
                        </div>
                        <div class="invoice-heading">
                            <h2>INVOICE</h2>
                            <div class="invoice-number"><?= e($invoiceNumber) ?></div>
                        </div>
                    </section>

                    <!-- META DATA -->
                    <section class="invoice-meta">
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Invoice Date</div>
                            <div class="invoice-meta-value"><?= e(formatDate($invoiceDate)) ?></div>
                        </div>
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Order</div>
                            <div class="invoice-meta-value"><?= e($orderNumber) ?></div>
                        </div>
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Payment</div>
                            <div class="invoice-meta-value"><?= e(readableStatus($paymentMethod)) ?></div>
                        </div>
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Invoice Status</div>
                            <div class="invoice-status <?= e(statusClass($invoiceStatus)) ?>">
                                <?= e(readableStatus($invoiceStatus)) ?>
                            </div>
                        </div>
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Payment Status</div>
                            <div class="invoice-status <?= e(statusClass($paymentStatus !== '' ? $paymentStatus : $invoiceStatus)) ?>">
                                <?= e($displayPaymentStatus) ?>
                            </div>
                        </div>
                        <div class="invoice-meta-box">
                            <div class="invoice-meta-label">Order Status</div>
                            <div class="invoice-meta-value"><?= e(readableStatus($orderStatus)) ?></div>
                        </div>
                    </section>

                    <!-- CUSTOMER INFORMATION -->
                    <section class="invoice-information">
                        <div>
                            <div class="information-title">Bill To</div>
                            <div class="customer-name"><?= e($customerName) ?></div>
                            <?php if ($customerEmail !== ''): ?>
                                <div class="customer-detail"><?= e($customerEmail) ?></div>
                            <?php endif; ?>
                            <?php if ($customerPhone !== ''): ?>
                                <div class="customer-detail"><?= e($customerPhone) ?></div>
                            <?php endif; ?>
                            <?php if ($fullCustomerAddress !== ''): ?>
                                <div class="customer-detail"><?= e($fullCustomerAddress) ?></div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="information-title">Order Information</div>
                            <div class="customer-detail"><strong>Order:</strong> <?= e($orderNumber) ?></div>
                            <?php if ($orderId > 0): ?>
                                <div class="customer-detail"><strong>Order ID:</strong> <?= e($orderId) ?></div>
                            <?php endif; ?>
                            <div class="customer-detail"><strong>Invoice:</strong> <?= e($invoiceNumber) ?></div>
                            <div class="customer-detail"><strong>Date:</strong> <?= e(formatDateTime($invoiceDate)) ?></div>
                        </div>
                    </section>

                    <!-- ITEMS TABLE -->
                    <section class="invoice-items">
                        <div class="invoice-items-table-wrapper">
                            <?php if (!empty($orderItems)): ?>
                                <table class="invoice-items-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-right">Price</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="invoice-product-name"><?= e($item['_display_name']) ?></div>
                                                    <?php if ($item['_display_code'] !== ''): ?>
                                                        <div class="invoice-product-code">Code: <?= e($item['_display_code']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= e($item['_display_quantity']) ?></td>
                                                <td class="text-right"><?= e(money($item['_display_price'])) ?></td>
                                                <td class="text-right"><strong><?= e(money($item['_display_total'])) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="invoice-empty-items">No order items are available for this invoice.</div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- TOTAL & NOTES -->
                    <section class="invoice-bottom">
                        <div class="invoice-notes">
                            <div class="invoice-notes-title">Notes</div>
                            <div class="invoice-notes-text">
                                Thank you for shopping with Ssrini Handicrafts.<br>
                                Payment method: <?= e(readableStatus($paymentMethod)) ?>.
                            </div>
                        </div>

                        <div class="invoice-summary">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <strong><?= e(money($subtotal)) ?></strong>
                            </div>
                            <?php if ($discount > 0): ?>
                                <div class="summary-row">
                                    <span>Discount</span>
                                    <strong>- <?= e(money($discount)) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($tax > 0): ?>
                                <div class="summary-row">
                                    <span>Tax / GST</span>
                                    <strong><?= e(money($tax)) ?></strong>
                                </div>
                            <?php endif; ?>
                            <?php if ($shipping > 0): ?>
                                <div class="summary-row">
                                    <span>Shipping</span>
                                    <strong><?= e(money($shipping)) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="summary-total">
                                <span>Total</span>
                                <strong><?= e(money($totalAmount)) ?></strong>
                            </div>
                        </div>
                    </section>

                    <!-- FOOTER -->
                    <footer class="invoice-footer">
                        This is a computer-generated invoice and does not require a signature.
                    </footer>

                </article>
            </div>
        </div>
    </main>
</div>
</body>
</html>