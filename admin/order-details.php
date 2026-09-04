<?php

/**
 * SSRINI HANDICRAFTS - ADMIN PANEL
 * ORDER DETAILS
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');

/* HELPER FUNCTIONS */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount) {
    return '₹' . number_format((float)$amount, 2);
}

function formatDateTime($date) {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return $timestamp === false ? e($date) : date('d M Y, h:i A', $timestamp);
}

function orderStatusClass($status) {
    $status = strtolower(trim((string)$status));
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'confirmed': return 'status-confirmed';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        case 'delivered': return 'status-delivered';
        case 'cancelled': case 'canceled': return 'status-cancelled';
        case 'returned': return 'status-returned';
        default: return 'status-default';
    }
}

function paymentStatusClass($status) {
    $status = strtolower(trim((string)$status));
    switch ($status) {
        case 'paid': case 'completed': return 'status-paid';
        case 'pending': return 'status-pending';
        case 'failed': case 'cancelled': case 'canceled': return 'status-cancelled';
        case 'refunded': return 'status-refunded';
        default: return 'status-default';
    }
}

/* ORDER ID VALIDATION */
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

/* UPDATE ORDER POST HANDLER */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $newOrderStatus = isset($_POST['order_status']) ? trim($_POST['order_status']) : '';
    $newPaymentStatus = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : '';

    $allowedOrderStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    $allowedPaymentStatuses = ['pending', 'paid', 'failed', 'cancelled', 'refunded'];

    if (!in_array($newOrderStatus, $allowedOrderStatuses, true)) {
        header('Location: order-details.php?id=' . $orderId . '&error=' . rawurlencode('Invalid order status selected.'));
        exit;
    }

    if (!in_array($newPaymentStatus, $allowedPaymentStatuses, true)) {
        header('Location: order-details.php?id=' . $orderId . '&error=' . rawurlencode('Invalid payment status selected.'));
        exit;
    }

    $updateQuery = "UPDATE orders SET order_status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);

    if (!$updateStmt) {
        header('Location: order-details.php?id=' . $orderId . '&error=' . rawurlencode('Unable to prepare update query.'));
        exit;
    }

    $updateStmt->bind_param('ssi', $newOrderStatus, $newPaymentStatus, $orderId);

    if ($updateStmt->execute()) {
        $updateStmt->close();
        header('Location: order-details.php?id=' . $orderId . '&success=' . rawurlencode('Order details updated successfully.'));
        exit;
    } else {
        $updateStmt->close();
        header('Location: order-details.php?id=' . $orderId . '&error=' . rawurlencode('Unable to update the order.'));
        exit;
    }
}

$updateSuccess = isset($_GET['success']) ? trim($_GET['success']) : '';
$updateError = isset($_GET['error']) ? trim($_GET['error']) : '';

/* FETCH ORDER + CUSTOMER */
$order = null;
$orderQuery = "
    SELECT
        o.id, o.order_number, o.customer_id, o.total_amount, o.payment_method,
        o.payment_status, o.order_status, o.shipping_address, o.created_at, o.updated_at,
        c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone,
        c.address AS customer_address, c.city AS customer_city, c.state AS customer_state,
        c.pincode AS customer_pincode
    FROM orders o
    LEFT JOIN customers c ON c.id = o.customer_id
    WHERE o.id = ?
    LIMIT 1
";

$orderStmt = $conn->prepare($orderQuery);
if ($orderStmt) {
    $orderStmt->bind_param('i', $orderId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    if ($orderResult && $orderResult->num_rows > 0) {
        $order = $orderResult->fetch_assoc();
    }
    $orderStmt->close();
}

/* ORDER NOT FOUND */
if (!$order) {
    $pageTitle = 'Order Not Found';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Order details - Ssrini Handicrafts Admin Panel">
        <title>Order Not Found | Ssrini Handicrafts</title>
        <link rel="stylesheet" href="../assets/css/admin.css">
        <style>
            .not-found-wrapper { min-height: 70vh; display: flex; align-items: center; justify-content: center; padding: 30px; }
            .not-found-card { width: 100%; max-width: 520px; text-align: center; padding: 40px 30px; background: var(--surface, #ffffff); border: 1px solid var(--border-light, #e5e7eb); border-radius: 18px; box-shadow: 0 12px 35px rgba(0, 0, 0, .08); }
            .not-found-icon { font-size: 55px; margin-bottom: 18px; }
            .not-found-card h2 { margin: 0 0 10px; }
            .not-found-card p { color: var(--text-muted, #777); margin-bottom: 24px; }
        </style>
    </head>
    <body>
        <div class="admin-wrapper">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
            <main class="main-area">
                <?php require_once __DIR__ . '/../includes/header.php'; ?>
                <div class="page-content">
                    <div class="not-found-wrapper">
                        <div class="not-found-card">
                            <div class="not-found-icon">📦</div>
                            <h2>Order Not Found</h2>
                            <p>The requested order could not be found in the database.</p>
                            <a href="orders.php" class="btn btn-primary">← Back to Orders</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* FETCH ORDER ITEMS */
$orderItems = [];
$itemsQuery = "
    SELECT
        oi.id, oi.order_id, oi.product_id, oi.product_name, oi.quantity, oi.price, oi.created_at,
        p.product_code, p.image, p.slug
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
";

$itemsStmt = $conn->prepare($itemsQuery);
if ($itemsStmt) {
    $itemsStmt->bind_param('i', $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    if ($itemsResult) {
        while ($item = $itemsResult->fetch_assoc()) {
            $orderItems[] = $item;
        }
    }
    $itemsStmt->close();
}

/* CALCULATIONS & DATA MAPPING */
$itemsTotal = 0;
foreach ($orderItems as $item) {
    $itemsTotal += ((float)$item['price']) * ((int)$item['quantity']);
}

$customerPhone = preg_replace('/[^0-9]/', '', (string)$order['customer_phone']);
$whatsappPhone = $customerPhone;
$customerName = trim((string)$order['customer_name']);
$avatarLetter = !empty($customerName) ? strtoupper(substr($customerName, 0, 1)) : '?';
$shippingAddress = trim((string)$order['shipping_address']);
$paymentMethod = strtolower(trim((string)$order['payment_method']));
$pageTitle = 'Order #' . ($order['order_number'] ?: $order['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Order details - Ssrini Handicrafts Admin Panel">
    <title><?= e($pageTitle) ?> | Ssrini Handicrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .order-details-page { width: 100%; max-width: 1500px; margin: 0 auto; }
        .order-page-header { display: flex; align-items: center; justify-content: space-between; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
        .order-page-title { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; min-width: 0; }
        .order-page-title h1 { margin: 0; font-size: 24px; line-height: 1.3; }
        .order-number { color: var(--text-muted, #777); font-size: 13px; margin-top: 3px; }
        .order-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        
        .order-alert { padding: 13px 16px; border-radius: var(--radius-md, 10px); margin-bottom: 18px; font-size: 13px; font-weight: 500; }
        .order-alert-success { background: rgba(34, 197, 94, .10); color: #15803d; border: 1px solid rgba(34, 197, 94, .20); }
        .order-alert-error { background: rgba(239, 68, 68, .10); color: #b91c1c; border: 1px solid rgba(239, 68, 68, .20); }

        .order-details-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 20px; align-items: start; }
        .order-left-column, .order-right-column { min-width: 0; }

        .order-card { background: var(--surface, #ffffff); border: 1px solid var(--border-light, #e5e7eb); border-radius: var(--radius-lg, 14px); box-shadow: 0 5px 20px rgba(0, 0, 0, .04); overflow: hidden; margin-bottom: 20px; }
        .order-card-header { padding: 18px 20px; border-bottom: 1px solid var(--border-light, #e5e7eb); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .order-card-title { font-size: 16px; font-weight: 700; }
        .order-card-subtitle { color: var(--text-muted, #777); font-size: 11px; margin-top: 4px; }
        .order-card-body { padding: 20px; }

        .order-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap; }
        .status-pending { background: #fff7ed; color: #c2410c; }
        .status-confirmed { background: #eff6ff; color: #1d4ed8; }
        .status-processing { background: #f5f3ff; color: #6d28d9; }
        .status-shipped { background: #ecfeff; color: #0e7490; }
        .status-delivered { background: #ecfdf5; color: #047857; }
        .status-cancelled { background: #fef2f2; color: #b91c1c; }
        .status-returned { background: #fff7ed; color: #9a3412; }
        .status-paid { background: #ecfdf5; color: #047857; }
        .status-refunded { background: #f5f3ff; color: #6d28d9; }
        .status-default { background: #f3f4f6; color: #4b5563; }

        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .summary-box { padding: 15px; border: 1px solid var(--border-light, #e5e7eb); border-radius: var(--radius-md, 10px); background: var(--surface-soft, #f8fafc); min-width: 0; }
        .summary-label { font-size: 11px; color: var(--text-muted, #777); margin-bottom: 7px; }
        .summary-value { font-size: 14px; font-weight: 700; word-break: break-word; }

        .order-products-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .order-products-table { width: 100%; min-width: 650px; border-collapse: collapse; }
        .order-products-table th { text-align: left; padding: 12px 14px; background: var(--surface-soft, #f8fafc); border-bottom: 1px solid var(--border-light, #e5e7eb); font-size: 11px; color: var(--text-muted, #777); font-weight: 700; white-space: nowrap; }
        .order-products-table td { padding: 14px; border-bottom: 1px solid var(--border-light, #e5e7eb); font-size: 12px; vertical-align: middle; }
        .order-products-table tr:last-child td { border-bottom: 0; }

        .product-cell { display: flex; align-items: center; gap: 12px; min-width: 220px; }
        .product-image { width: 55px; height: 55px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-light, #e5e7eb); background: var(--surface-soft, #f8fafc); flex-shrink: 0; }
        .product-image-placeholder { width: 55px; height: 55px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: var(--surface-soft, #f8fafc); border: 1px solid var(--border-light, #e5e7eb); font-size: 22px; flex-shrink: 0; }
        .product-name { font-weight: 700; color: var(--text-primary, #111827); word-break: break-word; }
        .product-code { color: var(--text-muted, #777); font-size: 10px; margin-top: 3px; }
        .text-right { text-align: right; }
        .order-total-row { display: flex; justify-content: flex-end; align-items: center; gap: 50px; padding-top: 18px; }
        .order-total-label { color: var(--text-muted, #777); font-size: 13px; }
        .order-total-value { font-size: 18px; font-weight: 800; }

        .customer-profile { display: flex; align-items: center; gap: 13px; margin-bottom: 20px; min-width: 0; }
        .customer-avatar { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--primary, #7c3aed); color: #fff; font-size: 18px; font-weight: 800; flex-shrink: 0; }
        .customer-name { font-size: 15px; font-weight: 700; word-break: break-word; }
        .customer-id { font-size: 10px; color: var(--text-muted, #777); margin-top: 3px; }
        .customer-info-list { display: flex; flex-direction: column; gap: 13px; }
        .customer-info-item { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
        .customer-info-icon { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; background: var(--surface-soft, #f8fafc); border: 1px solid var(--border-light, #e5e7eb); border-radius: 8px; flex-shrink: 0; }
        .customer-info-content { min-width: 0; }
        .customer-info-label { font-size: 10px; color: var(--text-muted, #777); margin-bottom: 3px; }
        .customer-info-value { font-size: 12px; font-weight: 600; overflow-wrap: anywhere; }
        .customer-contact-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 18px; }
        .contact-btn { display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; min-height: 40px; }

        .address-box { padding: 15px; background: var(--surface-soft, #f8fafc); border: 1px solid var(--border-light, #e5e7eb); border-radius: var(--radius-md, 10px); font-size: 12px; line-height: 1.7; white-space: pre-line; overflow-wrap: anywhere; }
        .payment-method { display: flex; align-items: center; gap: 12px; padding: 13px; border: 1px solid var(--border-light, #e5e7eb); border-radius: var(--radius-md, 10px); background: var(--surface-soft, #f8fafc); margin-bottom: 15px; }
        .payment-icon { width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: var(--surface, #ffffff); border: 1px solid var(--border-light, #e5e7eb); font-size: 19px; flex-shrink: 0; }
        .payment-method-name { font-size: 12px; font-weight: 700; word-break: break-word; }
        .payment-method-description { color: var(--text-muted, #777); font-size: 10px; margin-top: 3px; }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 11px; font-weight: 700; margin-bottom: 7px; }
        .order-select { width: 100%; min-height: 42px; padding: 9px 12px; border: 1px solid var(--border-light, #e5e7eb); border-radius: var(--radius-md, 10px); background: var(--surface, #ffffff); color: var(--text-primary, #111827); outline: none; font-size: 12px; }
        .order-select:focus { border-color: var(--primary, #7c3aed); box-shadow: 0 0 0 3px rgba(124, 58, 237, .10); }
        .update-button { width: 100%; min-height: 43px; }

        .timeline { position: relative; display: flex; flex-direction: column; gap: 18px; }
        .timeline::before { content: ""; position: absolute; left: 7px; top: 8px; bottom: 8px; width: 1px; background: var(--border-light, #e5e7eb); }
        .timeline-item { position: relative; display: flex; gap: 13px; }
        .timeline-dot { width: 15px; height: 15px; border-radius: 50%; background: var(--primary, #7c3aed); border: 3px solid var(--surface, #ffffff); box-shadow: 0 0 0 1px var(--primary, #7c3aed); flex-shrink: 0; z-index: 1; }
        .timeline-content { min-width: 0; }
        .timeline-title { font-size: 12px; font-weight: 700; }
        .timeline-date { color: var(--text-muted, #777); font-size: 10px; margin-top: 3px; }

        .empty-items { padding: 35px 20px; text-align: center; color: var(--text-muted, #777); }
        .empty-items-icon { font-size: 35px; margin-bottom: 8px; }

        @media (max-width: 1100px) {
            .order-details-grid { grid-template-columns: 1fr; }
            .order-right-column { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; align-items: start; }
            .order-right-column .order-card { margin-bottom: 0; }
        }
        @media (max-width: 760px) {
            .order-page-header { align-items: flex-start; flex-direction: column; }
            .order-actions { width: 100%; }
            .order-actions .btn { flex: 1; min-width: 130px; }
            .order-page-title h1 { font-size: 20px; }
            .summary-grid, .order-right-column { grid-template-columns: 1fr; }
            .order-card-header, .order-card-body { padding: 15px; }
            .customer-contact-actions { grid-template-columns: 1fr; }
            .order-total-row { justify-content: space-between; gap: 15px; }
        }
        @media (max-width: 480px) {
            .order-page-header { gap: 12px; }
            .order-page-title { width: 100%; align-items: flex-start; }
            .order-page-title h1 { font-size: 18px; }
            .order-number { font-size: 11px; }
            .order-actions { flex-direction: column; width: 100%; }
            .order-actions .btn { width: 100%; min-width: 0; }
            .order-card { margin-bottom: 15px; border-radius: 12px; }
            .order-card-header, .order-card-body, .summary-box { padding: 13px; }
            .product-cell { min-width: 190px; }
            .product-image, .product-image-placeholder { width: 48px; height: 48px; }
            .order-total-row { align-items: flex-start; flex-direction: column; gap: 5px; }
            .order-total-value { font-size: 16px; }
            .customer-profile, .payment-method { align-items: flex-start; }
            .customer-avatar { width: 46px; height: 46px; font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content">
            <div class="order-details-page">

                <!-- PAGE HEADER -->
                <div class="order-page-header">
                    <div class="order-page-title">
                        <a href="orders.php" class="btn btn-secondary">← Back</a>
                        <div>
                            <h1>Order Details</h1>
                            <div class="order-number">#<?= e($order['order_number'] ?: $order['id']) ?></div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <a href="orders.php" class="btn btn-secondary">📦 All Orders</a>
                        <?php if (!empty($customerPhone)): ?>
                            <a href="tel:<?= e($customerPhone) ?>" class="btn btn-secondary">📞 Call</a>
                            <?php if (!empty($whatsappPhone)): ?>
                                <a href="https://wa.me/<?= e($whatsappPhone) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary">💬 WhatsApp</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ALERTS -->
                <?php if (!empty($updateSuccess)): ?>
                    <div class="order-alert order-alert-success">✓ <?= e($updateSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($updateError)): ?>
                    <div class="order-alert order-alert-error">⚠ <?= e($updateError) ?></div>
                <?php endif; ?>

                <!-- MAIN GRID -->
                <div class="order-details-grid">

                    <!-- LEFT COLUMN -->
                    <div class="order-left-column">

                        <!-- ORDER SUMMARY -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Order Summary</div>
                                    <div class="order-card-subtitle">Basic order information</div>
                                </div>
                                <span class="order-status-badge <?= e(orderStatusClass($order['order_status'])) ?>">
                                    ● <?= e(ucfirst($order['order_status'] ?: 'pending')) ?>
                                </span>
                            </div>
                            <div class="order-card-body">
                                <div class="summary-grid">
                                    <div class="summary-box">
                                        <div class="summary-label">Order Number</div>
                                        <div class="summary-value">#<?= e($order['order_number'] ?: $order['id']) ?></div>
                                    </div>
                                    <div class="summary-box">
                                        <div class="summary-label">Order Date</div>
                                        <div class="summary-value"><?= e(formatDateTime($order['created_at'])) ?></div>
                                    </div>
                                    <div class="summary-box">
                                        <div class="summary-label">Total Amount</div>
                                        <div class="summary-value"><?= e(formatMoney($order['total_amount'])) ?></div>
                                    </div>
                                    <div class="summary-box">
                                        <div class="summary-label">Payment Method</div>
                                        <div class="summary-value"><?= e($order['payment_method'] ?: '-') ?></div>
                                    </div>
                                    <div class="summary-box">
                                        <div class="summary-label">Payment Status</div>
                                        <div>
                                            <span class="order-status-badge <?= e(paymentStatusClass($order['payment_status'])) ?>">
                                                ● <?= e(ucfirst($order['payment_status'] ?: 'pending')) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="summary-box">
                                        <div class="summary-label">Last Updated</div>
                                        <div class="summary-value"><?= e(formatDateTime($order['updated_at'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ORDER ITEMS -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Order Items</div>
                                    <div class="order-card-subtitle">Products included in this order</div>
                                </div>
                                <span class="badge badge-success"><?= count($orderItems) ?> Item(s)</span>
                            </div>

                            <?php if (!empty($orderItems)): ?>
                                <div class="order-products-wrapper">
                                    <table class="order-products-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th class="text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($orderItems as $item): 
                                            $itemPrice = (float)$item['price'];
                                            $itemQuantity = (int)$item['quantity'];
                                            $itemTotal = $itemPrice * $itemQuantity;
                                            $productImage = trim((string)$item['image']);
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="product-cell">
                                                        <?php if (!empty($productImage)): ?>
                                                            <img src="../assets/images/<?= e($productImage) ?>" alt="<?= e($item['product_name']) ?>" class="product-image" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                                            <div class="product-image-placeholder" style="display:none;">🛍️</div>
                                                        <?php else: ?>
                                                            <div class="product-image-placeholder">🛍️</div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="product-name"><?= e($item['product_name'] ?: 'Product') ?></div>
                                                            <?php if (!empty($item['product_code'])): ?>
                                                                <div class="product-code">Code: <?= e($item['product_code']) ?></div>
                                                            <?php endif; ?>
                                                            <div class="product-code">Product ID: <?= e($item['product_id']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= e(formatMoney($itemPrice)) ?></td>
                                                <td><?= e($itemQuantity) ?></td>
                                                <td class="text-right"><strong><?= e(formatMoney($itemTotal)) ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="order-card-body">
                                    <div class="order-total-row">
                                        <div class="order-total-label">Items Total</div>
                                        <div class="order-total-value"><?= e(formatMoney($itemsTotal)) ?></div>
                                    </div>
                                    <div class="order-total-row" style="padding-top:8px;">
                                        <div class="order-total-label">Order Total</div>
                                        <div class="order-total-value"><?= e(formatMoney($order['total_amount'])) ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="empty-items">
                                    <div class="empty-items-icon">🛍️</div>
                                    <div>No items found for this order.</div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- SHIPPING ADDRESS -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Shipping Address</div>
                                    <div class="order-card-subtitle">Delivery information</div>
                                </div>
                            </div>
                            <div class="order-card-body">
                                <?php if (!empty($shippingAddress)): ?>
                                    <div class="address-box"><?= nl2br(e($shippingAddress)) ?></div>
                                <?php else: ?>
                                    <div class="address-box">
                                        <?= e($order['customer_address'] ?: 'Address not available.') ?>
                                        <?php if (!empty($order['customer_city'])): ?><br><?= e($order['customer_city']) ?><?php endif; ?>
                                        <?php if (!empty($order['customer_state'])): ?><br><?= e($order['customer_state']) ?><?php endif; ?>
                                        <?php if (!empty($order['customer_pincode'])): ?> - <?= e($order['customer_pincode']) ?><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="order-right-column">

                        <!-- UPDATE ORDER -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Update Order</div>
                                    <div class="order-card-subtitle">Change order and payment status</div>
                                </div>
                            </div>
                            <div class="order-card-body">
                                <form method="POST" action="order-details.php?id=<?= e($orderId) ?>">
                                    <div class="form-group">
                                        <label for="order_status" class="form-label">Order Status</label>
                                        <select id="order_status" name="order_status" class="order-select" required>
                                            <?php
                                            $orderStatuses = [
                                                'pending' => 'Pending', 'confirmed' => 'Confirmed',
                                                'processing' => 'Processing', 'shipped' => 'Shipped',
                                                'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
                                                'returned' => 'Returned'
                                            ];
                                            foreach ($orderStatuses as $statusValue => $statusLabel): ?>
                                                <option value="<?= e($statusValue) ?>" <?= (strtolower((string)$order['order_status']) === $statusValue) ? 'selected' : '' ?>>
                                                    <?= e($statusLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="payment_status" class="form-label">Payment Status</label>
                                        <select id="payment_status" name="payment_status" class="order-select" required>
                                            <?php
                                            $paymentStatuses = [
                                                'pending' => 'Pending', 'paid' => 'Paid',
                                                'failed' => 'Failed', 'cancelled' => 'Cancelled',
                                                'refunded' => 'Refunded'
                                            ];
                                            foreach ($paymentStatuses as $paymentValue => $paymentLabel): ?>
                                                <option value="<?= e($paymentValue) ?>" <?= (strtolower((string)$order['payment_status']) === $paymentValue) ? 'selected' : '' ?>>
                                                    <?= e($paymentLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <button type="submit" name="update_order" value="1" class="btn btn-primary update-button">✓ Update Order</button>
                                </form>
                            </div>
                        </div>

                        <!-- CUSTOMER -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Customer</div>
                                    <div class="order-card-subtitle">Customer information</div>
                                </div>
                            </div>
                            <div class="order-card-body">
                                <div class="customer-profile">
                                    <div class="customer-avatar"><?= e($avatarLetter) ?></div>
                                    <div>
                                        <div class="customer-name"><?= e($customerName ?: 'Guest Customer') ?></div>
                                        <div class="customer-id">Customer ID: <?= e($order['customer_id'] ?: '-') ?></div>
                                    </div>
                                </div>

                                <div class="customer-info-list">
                                    <div class="customer-info-item">
                                        <div class="customer-info-icon">✉️</div>
                                        <div class="customer-info-content">
                                            <div class="customer-info-label">Email</div>
                                            <div class="customer-info-value"><?= e($order['customer_email'] ?: '-') ?></div>
                                        </div>
                                    </div>
                                    <div class="customer-info-item">
                                        <div class="customer-info-icon">📞</div>
                                        <div class="customer-info-content">
                                            <div class="customer-info-label">Phone</div>
                                            <div class="customer-info-value"><?= e($order['customer_phone'] ?: '-') ?></div>
                                        </div>
                                    </div>
                                    <div class="customer-info-item">
                                        <div class="customer-info-icon">📍</div>
                                        <div class="customer-info-content">
                                            <div class="customer-info-label">Location</div>
                                            <div class="customer-info-value">
                                                <?= e($order['customer_city'] ?: '-') ?>
                                                <?php if (!empty($order['customer_state'])): ?>, <?= e($order['customer_state']) ?><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($customerPhone)): ?>
                                    <div class="customer-contact-actions">
                                        <a href="tel:<?= e($customerPhone) ?>" class="btn btn-secondary contact-btn">📞 Call</a>
                                        <a href="https://wa.me/<?= e($whatsappPhone) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary contact-btn">💬 WhatsApp</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- PAYMENT -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Payment</div>
                                    <div class="order-card-subtitle">Payment information</div>
                                </div>
                            </div>
                            <div class="order-card-body">
                                <div class="payment-method">
                                    <div class="payment-icon">
                                        <?php if (strpos($paymentMethod, 'cash') !== false || strpos($paymentMethod, 'cod') !== false): ?>
                                            💵
                                        <?php elseif (strpos($paymentMethod, 'upi') !== false): ?>
                                            📱
                                        <?php else: ?>
                                            💳
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="payment-method-name"><?= e($order['payment_method'] ?: 'Not specified') ?></div>
                                        <div class="payment-method-description">Payment Status: <?= e(ucfirst($order['payment_status'] ?: 'pending')) ?></div>
                                    </div>
                                </div>
                                <div class="summary-box">
                                    <div class="summary-label">Order Amount</div>
                                    <div class="summary-value"><?= e(formatMoney($order['total_amount'])) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- ORDER TIMELINE -->
                        <div class="order-card">
                            <div class="order-card-header">
                                <div>
                                    <div class="order-card-title">Order Timeline</div>
                                    <div class="order-card-subtitle">Order activity</div>
                                </div>
                            </div>
                            <div class="order-card-body">
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-title">Order Created</div>
                                            <div class="timeline-date"><?= e(formatDateTime($order['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-title">Current Status</div>
                                            <div class="timeline-date"><?= e(ucfirst($order['order_status'] ?: 'Pending')) ?></div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-title">Last Updated</div>
                                            <div class="timeline-date"><?= e(formatDateTime($order['updated_at'])) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>