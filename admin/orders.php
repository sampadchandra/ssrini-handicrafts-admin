<?php

/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ORDERS MANAGEMENT
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Orders';

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function ordersTableExists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ");
        $stmt->execute([':table' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function ordersGetColumns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table
        ");
        $stmt->execute([':table' => $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function ordersFirstColumn(array $columns, array $possible): ?string
{
    foreach ($possible as $column) {
        if (in_array($column, $columns, true)) {
            return $column;
        }
    }
    return null;
}

function orderStatusClass(string $status): string
{
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'pending', 'new', 'placed' => 'status-pending',
        'processing'              => 'status-processing',
        'shipped', 'shipping'     => 'status-shipped',
        'delivered', 'completed'  => 'status-delivered',
        'cancelled', 'canceled'   => 'status-cancelled',
        default                   => 'status-default'
    };
}

function orderStatusLabel(string $status): string
{
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'pending'              => 'Pending',
        'new'                  => 'New',
        'placed'               => 'Placed',
        'processing'           => 'Processing',
        'shipped'              => 'Shipped',
        'shipping'             => 'Shipping',
        'delivered'            => 'Delivered',
        'completed'            => 'Completed',
        'cancelled', 'canceled'=> 'Cancelled',
        default                => ucfirst($status)
    };
}

function moneyFormat($amount): string
{
    return '₹' . number_format((float) $amount, 2);
}

function safeDate($date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime((string) $date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d M Y', $timestamp);
}

function safeDateTime($date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime((string) $date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d M Y, h:i A', $timestamp);
}

function initials(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name);

    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 1));
    }

    return strtoupper(
        substr($parts[0], 0, 1) .
        substr($parts[count($parts) - 1], 0, 1)
    );
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['orders_csrf_token'])) {
    $_SESSION['orders_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['orders_csrf_token'];

/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

$ordersTableExists = ordersTableExists($pdo, 'orders');
$orderItemsTableExists = ordersTableExists($pdo, 'order_items');

$ordersColumns = $ordersTableExists ? ordersGetColumns($pdo, 'orders') : [];
$orderItemsColumns = $orderItemsTableExists ? ordersGetColumns($pdo, 'order_items') : [];

/*
|--------------------------------------------------------------------------
| DETECT ORDER COLUMNS
|--------------------------------------------------------------------------
|
| This keeps the page flexible with the existing database structure.
|
*/

$orderIdColumn       = ordersFirstColumn($ordersColumns, ['id', 'order_id']);
$orderNumberColumn   = ordersFirstColumn($ordersColumns, ['order_number', 'order_no', 'order_code', 'order_reference']);
$customerIdColumn    = ordersFirstColumn($ordersColumns, ['customer_id', 'user_id']);
$customerNameColumn  = ordersFirstColumn($ordersColumns, ['customer_name', 'name', 'full_name']);
$customerPhoneColumn = ordersFirstColumn($ordersColumns, ['customer_phone', 'phone', 'phone_number', 'mobile', 'contact']);
$customerEmailColumn = ordersFirstColumn($ordersColumns, ['customer_email', 'email']);
$totalColumn         = ordersFirstColumn($ordersColumns, ['total_amount', 'grand_total', 'total', 'amount', 'order_total']);
$subtotalColumn      = ordersFirstColumn($ordersColumns, ['subtotal', 'sub_total']);
$taxColumn           = ordersFirstColumn($ordersColumns, ['tax', 'tax_amount']);
$shippingColumn      = ordersFirstColumn($ordersColumns, ['shipping', 'shipping_amount', 'delivery_charge']);
$discountColumn      = ordersFirstColumn($ordersColumns, ['discount', 'discount_amount']);
$statusColumn        = ordersFirstColumn($ordersColumns, ['status', 'order_status']);
$paymentMethodColumn = ordersFirstColumn($ordersColumns, ['payment_method', 'payment_type']);
$paymentStatusColumn = ordersFirstColumn($ordersColumns, ['payment_status']);
$addressColumn       = ordersFirstColumn($ordersColumns, ['shipping_address', 'address', 'customer_address', 'delivery_address']);
$cityColumn          = ordersFirstColumn($ordersColumns, ['city']);
$stateColumn         = ordersFirstColumn($ordersColumns, ['state']);
$pincodeColumn       = ordersFirstColumn($ordersColumns, ['pincode', 'postal_code', 'zip_code']);
$notesColumn         = ordersFirstColumn($ordersColumns, ['notes', 'order_notes', 'customer_notes']);
$createdAtColumn     = ordersFirstColumn($ordersColumns, ['created_at', 'order_date', 'created_on', 'date']);
$updatedAtColumn     = ordersFirstColumn($ordersColumns, ['updated_at', 'modified_at', 'updated_on']);

/*
|--------------------------------------------------------------------------
| ORDER ITEMS COLUMNS
|--------------------------------------------------------------------------
*/

$orderItemsIdColumn          = ordersFirstColumn($orderItemsColumns, ['id', 'order_item_id']);
$orderItemsOrderIdColumn     = ordersFirstColumn($orderItemsColumns, ['order_id']);
$orderItemsProductIdColumn   = ordersFirstColumn($orderItemsColumns, ['product_id']);
$orderItemsProductNameColumn = ordersFirstColumn($orderItemsColumns, ['product_name', 'name', 'title']);
$orderItemsQuantityColumn    = ordersFirstColumn($orderItemsColumns, ['quantity', 'qty']);
$orderItemsPriceColumn       = ordersFirstColumn($orderItemsColumns, ['price', 'unit_price', 'product_price']);
$orderItemsTotalColumn       = ordersFirstColumn($orderItemsColumns, ['total', 'total_price', 'line_total', 'amount']);

/*
|--------------------------------------------------------------------------
| ACTION MESSAGES
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| HANDLE POST ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $postedToken)) {
        $errorMessage = 'Security validation failed. Please refresh the page and try again.';
    } else {
        $action = trim($_POST['action'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        if ($action === 'update_status') {
            $orderId   = (int) ($_POST['order_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');

            $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

            if (!$ordersTableExists || !$orderIdColumn || !$statusColumn) {
                $errorMessage = 'Order status cannot be updated because the required database columns are missing.';
            } elseif ($orderId <= 0) {
                $errorMessage = 'Invalid order selected.';
            } elseif (!in_array($newStatus, $allowedStatuses, true)) {
                $errorMessage = 'Invalid order status.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE orders
                        SET `$statusColumn` = :status
                        " . ($updatedAtColumn ? ", `$updatedAtColumn` = NOW()" : "") . "
                        WHERE `$orderIdColumn` = :order_id
                        LIMIT 1
                    ");

                    $stmt->execute([
                        ':status'   => $newStatus,
                        ':order_id' => $orderId
                    ]);

                    if ($stmt->rowCount() > 0) {
                        $successMessage = 'Order status updated successfully.';
                    } else {
                        $successMessage = 'Order status is already up to date.';
                    }
                } catch (Throwable $e) {
                    $errorMessage = 'Unable to update order status.';
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE ORDER
        |--------------------------------------------------------------------------
        */

        elseif ($action === 'delete_order') {
            $orderId = (int) ($_POST['order_id'] ?? 0);

            if (!$ordersTableExists || !$orderIdColumn) {
                $errorMessage = 'Order cannot be deleted because the required database columns are missing.';
            } elseif ($orderId <= 0) {
                $errorMessage = 'Invalid order selected.';
            } else {
                try {
                    $pdo->beginTransaction();

                    if ($orderItemsTableExists && $orderItemsOrderIdColumn) {
                        $deleteItems = $pdo->prepare("
                            DELETE FROM order_items
                            WHERE `$orderItemsOrderIdColumn` = :order_id
                        ");
                        $deleteItems->execute([':order_id' => $orderId]);
                    }

                    $deleteOrder = $pdo->prepare("
                        DELETE FROM orders
                        WHERE `$orderIdColumn` = :order_id
                        LIMIT 1
                    ");
                    $deleteOrder->execute([':order_id' => $orderId]);

                    if ($deleteOrder->rowCount() === 0) {
                        $pdo->rollBack();
                        $errorMessage = 'Order was not found.';
                    } else {
                        $pdo->commit();
                        $successMessage = 'Order deleted successfully.';
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errorMessage = 'Unable to delete the order.';
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search        = trim($_GET['search'] ?? '');
$statusFilter  = strtolower(trim($_GET['status'] ?? ''));
$paymentFilter = strtolower(trim($_GET['payment'] ?? ''));
$dateFilter    = trim($_GET['date'] ?? '');

$allowedStatusFilters = ['', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];

if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = '';
}

/*
|--------------------------------------------------------------------------
| LOAD ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];

$totalOrders      = 0;
$pendingOrders    = 0;
$processingOrders = 0;
$shippedOrders    = 0;
$deliveredOrders  = 0;
$cancelledOrders  = 0;
$totalRevenue     = 0;

if ($ordersTableExists && $orderIdColumn) {

    /*
    |--------------------------------------------------------------------------
    | BUILD SELECT
    |--------------------------------------------------------------------------
    */

    $selectParts = [];

    $selectParts[] = "o.`$orderIdColumn` AS order_id";
    $selectParts[] = $orderNumberColumn   ? "o.`$orderNumberColumn` AS order_number"   : "NULL AS order_number";
    $selectParts[] = $customerIdColumn    ? "o.`$customerIdColumn` AS customer_id"     : "NULL AS customer_id";
    $selectParts[] = $customerNameColumn  ? "o.`$customerNameColumn` AS customer_name" : "NULL AS customer_name";
    $selectParts[] = $customerPhoneColumn ? "o.`$customerPhoneColumn` AS customer_phone" : "NULL AS customer_phone";
    $selectParts[] = $customerEmailColumn ? "o.`$customerEmailColumn` AS customer_email" : "NULL AS customer_email";
    $selectParts[] = $totalColumn         ? "o.`$totalColumn` AS total_amount"         : "0 AS total_amount";
    $selectParts[] = $subtotalColumn      ? "o.`$subtotalColumn` AS subtotal"          : "NULL AS subtotal";
    $selectParts[] = $taxColumn           ? "o.`$taxColumn` AS tax_amount"             : "NULL AS tax_amount";
    $selectParts[] = $shippingColumn      ? "o.`$shippingColumn` AS shipping_amount"   : "NULL AS shipping_amount";
    $selectParts[] = $discountColumn      ? "o.`$discountColumn` AS discount_amount"   : "NULL AS discount_amount";
    $selectParts[] = $statusColumn        ? "o.`$statusColumn` AS order_status"        : "'pending' AS order_status";
    $selectParts[] = $paymentMethodColumn ? "o.`$paymentMethodColumn` AS payment_method": "NULL AS payment_method";
    $selectParts[] = $paymentStatusColumn ? "o.`$paymentStatusColumn` AS payment_status": "NULL AS payment_status";
    $selectParts[] = $addressColumn       ? "o.`$addressColumn` AS shipping_address"   : "NULL AS shipping_address";
    $selectParts[] = $cityColumn          ? "o.`$cityColumn` AS city"                  : "NULL AS city";
    $selectParts[] = $stateColumn         ? "o.`$stateColumn` AS state"                : "NULL AS state";
    $selectParts[] = $pincodeColumn       ? "o.`$pincodeColumn` AS pincode"            : "NULL AS pincode";
    $selectParts[] = $notesColumn         ? "o.`$notesColumn` AS order_notes"          : "NULL AS order_notes";
    $selectParts[] = $createdAtColumn     ? "o.`$createdAtColumn` AS created_at"       : "NULL AS created_at";
    $selectParts[] = $updatedAtColumn     ? "o.`$updatedAtColumn` AS updated_at"       : "NULL AS updated_at";

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER TABLE JOIN
    |--------------------------------------------------------------------------
    */

    $customersTableExists = ordersTableExists($pdo, 'customers');
    $customerColumns      = $customersTableExists ? ordersGetColumns($pdo, 'customers') : [];

    $customersIdColumn    = ordersFirstColumn($customerColumns, ['id', 'customer_id', 'user_id']);
    $customersNameColumn  = ordersFirstColumn($customerColumns, ['name', 'full_name', 'customer_name']);
    $customersPhoneColumn = ordersFirstColumn($customerColumns, ['phone', 'phone_number', 'mobile', 'contact']);
    $customersEmailColumn = ordersFirstColumn($customerColumns, ['email', 'email_address']);

    $joinCustomer = false;

    if ($customersTableExists && $customerIdColumn && $customersIdColumn) {
        $joinCustomer = true;
        $selectParts[] = $customersNameColumn  ? "c.`$customersNameColumn` AS joined_customer_name"  : "NULL AS joined_customer_name";
        $selectParts[] = $customersPhoneColumn ? "c.`$customersPhoneColumn` AS joined_customer_phone": "NULL AS joined_customer_phone";
        $selectParts[] = $customersEmailColumn ? "c.`$customersEmailColumn` AS joined_customer_email": "NULL AS joined_customer_email";
    }

    /*
    |--------------------------------------------------------------------------
    | ITEM COUNT
    |--------------------------------------------------------------------------
    */

    if ($orderItemsTableExists && $orderItemsOrderIdColumn) {
        if ($orderItemsQuantityColumn) {
            $selectParts[] = "
                (
                    SELECT COALESCE(SUM(oi_count.`$orderItemsQuantityColumn`), 0)
                    FROM order_items oi_count
                    WHERE oi_count.`$orderItemsOrderIdColumn` = o.`$orderIdColumn`
                ) AS item_count
            ";
        } else {
            $selectParts[] = "
                (
                    SELECT COUNT(*)
                    FROM order_items oi_count
                    WHERE oi_count.`$orderItemsOrderIdColumn` = o.`$orderIdColumn`
                ) AS item_count
            ";
        }
    } else {
        $selectParts[] = "0 AS item_count";
    }

    /*
    |--------------------------------------------------------------------------
    | FROM
    |--------------------------------------------------------------------------
    */

    $sql = "SELECT " . implode(", ", $selectParts) . " FROM orders o";

    if ($joinCustomer) {
        $sql .= " LEFT JOIN customers c ON c.`$customersIdColumn` = o.`$customerIdColumn` ";
    }

    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    $where = [];
    $params = [];

    if ($search !== '') {
        $searchParts = [];

        if ($orderNumberColumn)   $searchParts[] = "o.`$orderNumberColumn` LIKE :search";
        if ($customerNameColumn)  $searchParts[] = "o.`$customerNameColumn` LIKE :search";
        if ($customerPhoneColumn) $searchParts[] = "o.`$customerPhoneColumn` LIKE :search";
        if ($customerEmailColumn) $searchParts[] = "o.`$customerEmailColumn` LIKE :search";
        if ($customerIdColumn)    $searchParts[] = "CAST(o.`$customerIdColumn` AS CHAR) LIKE :search";

        if ($joinCustomer && $customersNameColumn)  $searchParts[] = "c.`$customersNameColumn` LIKE :search";
        if ($joinCustomer && $customersPhoneColumn) $searchParts[] = "c.`$customersPhoneColumn` LIKE :search";

        if (!empty($searchParts)) {
            $where[] = "(" . implode(" OR ", $searchParts) . ")";
            $params[':search'] = '%' . $search . '%';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if ($statusFilter !== '' && $statusColumn) {
        $where[] = "LOWER(o.`$statusColumn`) = :status_filter";
        $params[':status_filter'] = $statusFilter;
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT FILTER
    |--------------------------------------------------------------------------
    */

    if ($paymentFilter !== '' && $paymentMethodColumn) {
        $where[] = "LOWER(o.`$paymentMethodColumn`) = :payment_filter";
        $params[':payment_filter'] = $paymentFilter;
    }

    /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

    if ($dateFilter !== '' && $createdAtColumn) {
        $dateObject = DateTime::createFromFormat('Y-m-d', $dateFilter);

        if ($dateObject && $dateObject->format('Y-m-d') === $dateFilter) {
            $where[] = "DATE(o.`$createdAtColumn`) = :date_filter";
            $params[':date_filter'] = $dateFilter;
        }
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    if ($createdAtColumn) {
        $sql .= " ORDER BY o.`$createdAtColumn` DESC";
    } else {
        $sql .= " ORDER BY o.`$orderIdColumn` DESC";
    }

    /*
    |--------------------------------------------------------------------------
    | EXECUTE
    |--------------------------------------------------------------------------
    */

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $orders = [];
        $errorMessage = 'Unable to load orders from the database.';
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    foreach ($orders as $order) {
        $status = strtolower(trim((string) ($order['order_status'] ?? 'pending')));
        $amount = (float) ($order['total_amount'] ?? 0);

        $totalOrders++;
        $totalRevenue += $amount;

        switch ($status) {
            case 'pending':
            case 'new':
            case 'placed':
                $pendingOrders++;
                break;

            case 'processing':
                $processingOrders++;
                break;

            case 'shipped':
            case 'shipping':
                $shippedOrders++;
                break;

            case 'delivered':
            case 'completed':
                $deliveredOrders++;
                break;

            case 'cancelled':
            case 'canceled':
                $cancelledOrders++;
                break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| PAGE URL
|--------------------------------------------------------------------------
*/

$currentQuery = $_GET;
unset($currentQuery['page']);
$queryString = http_build_query($currentQuery);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ssrini Handicrafts Orders Management">
    <title><?= e($pageTitle) ?> | Ssrini Handicrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        /*
        |--------------------------------------------------------------------------
        | ORDERS PAGE
        |--------------------------------------------------------------------------
        */
        .orders-page {
            width: 100%;
        }

        .orders-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .orders-title h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
            font-weight: 750;
            color: var(--text-primary, #25212d);
        }

        .orders-title p {
            margin: 7px 0 0;
            color: var(--text-muted, #77717f);
            font-size: 13px;
        }

        .orders-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .orders-header-actions a,
        .orders-header-actions button {
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */
        .order-stat-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .order-stat-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(125, 73, 190, 0.08);
            border-radius: 16px;
            padding: 17px;
            box-shadow: 0 7px 22px rgba(40, 25, 65, 0.055);
        }

        .order-stat-card::after {
            content: "";
            position: absolute;
            width: 80px;
            height: 80px;
            right: -30px;
            bottom: -35px;
            border-radius: 50%;
            background: rgba(126, 54, 202, 0.055);
        }

        .order-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .order-stat-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(118, 39, 201, 0.12), rgba(197, 43, 159, 0.12));
            color: #7627c9;
            font-size: 18px;
        }

        .order-stat-number {
            margin-top: 13px;
            font-size: 25px;
            font-weight: 750;
            color: #25212d;
        }

        .order-stat-label {
            margin-top: 3px;
            font-size: 11px;
            color: #77717f;
        }

        .order-stat-revenue .order-stat-icon {
            color: #16855c;
            background: rgba(30, 160, 108, 0.11);
        }

        .order-stat-processing .order-stat-icon {
            color: #c88300;
            background: rgba(255, 176, 0, 0.12);
        }

        .order-stat-shipped .order-stat-icon {
            color: #5365c7;
            background: rgba(83, 101, 199, 0.11);
        }

        .order-stat-delivered .order-stat-icon {
            color: #16855c;
            background: rgba(30, 160, 108, 0.11);
        }

        /*
        |--------------------------------------------------------------------------
        | ALERTS
        |--------------------------------------------------------------------------
        */
        .orders-alert {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .orders-alert-success {
            color: #146c43;
            background: #eaf8f0;
            border: 1px solid #bfe9d0;
        }

        .orders-alert-error {
            color: #9d2634;
            background: #fff0f2;
            border: 1px solid #f3c1c9;
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PANEL
        |--------------------------------------------------------------------------
        */
        .orders-filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 17px;
            margin-bottom: 20px;
            box-shadow: 0 7px 22px rgba(40, 25, 65, 0.055);
            border: 1px solid rgba(125, 73, 190, 0.07);
        }

        .orders-filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) repeat(3, minmax(150px, 1fr)) auto;
            gap: 10px;
            align-items: end;
        }

        .orders-field {
            min-width: 0;
        }

        .orders-field label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 650;
            color: #625a6d;
        }

        .orders-input,
        .orders-select {
            width: 100%;
            height: 43px;
            border: 1px solid #e3dfea;
            border-radius: 10px;
            padding: 0 12px;
            background: #fff;
            color: #2d2835;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .orders-input:focus,
        .orders-select:focus {
            border-color: #9b48d1;
            box-shadow: 0 0 0 3px rgba(155, 72, 209, 0.09);
        }

        .orders-filter-actions {
            display: flex;
            gap: 8px;
        }

        .orders-filter-actions .btn {
            height: 43px;
        }

        /*
        |--------------------------------------------------------------------------
        | TABLE CARD
        |--------------------------------------------------------------------------
        */
        .orders-table-card {
            background: #fff;
            border-radius: 17px;
            overflow: hidden;
            box-shadow: 0 7px 22px rgba(40, 25, 65, 0.055);
            border: 1px solid rgba(125, 73, 190, 0.07);
        }

        .orders-table-header {
            padding: 17px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid #eeeaf2;
        }

        .orders-table-title {
            font-size: 16px;
            font-weight: 720;
            color: #2c2734;
        }

        .orders-table-subtitle {
            margin-top: 4px;
            font-size: 11px;
            color: #85808d;
        }

        .orders-result-count {
            padding: 7px 11px;
            border-radius: 9px;
            background: #f7f3fb;
            color: #7627c9;
            font-size: 11px;
            font-weight: 650;
        }

        .orders-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .orders-table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        .orders-table th {
            padding: 12px 16px;
            text-align: left;
            background: #faf9fc;
            color: #77717f;
            font-size: 10px;
            letter-spacing: .04em;
            font-weight: 750;
            white-space: nowrap;
            border-bottom: 1px solid #eeeaf2;
        }

        .orders-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0edf4;
            vertical-align: middle;
            font-size: 12px;
            color: #3b3543;
        }

        .orders-table tbody tr {
            transition: background .2s ease;
        }

        .orders-table tbody tr:hover {
            background: #fcfaff;
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER NUMBER
        |--------------------------------------------------------------------------
        */
        .order-number {
            color: #7627c9;
            font-weight: 750;
            text-decoration: none;
        }

        .order-number:hover {
            color: #c52b9f;
        }

        .order-id-small {
            display: block;
            margin-top: 3px;
            color: #9b95a2;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */
        .order-customer {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 170px;
        }

        .customer-avatar {
            flex: 0 0 auto;
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            color: #7627c9;
            background: linear-gradient(135deg, #f0e7ff, #fde7f5);
            font-size: 11px;
            font-weight: 750;
        }

        .customer-name {
            font-weight: 650;
            color: #302a38;
            line-height: 1.25;
        }

        .customer-contact {
            margin-top: 3px;
            color: #918b98;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */
        .order-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .order-status::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-pending { color: #a56a00; background: #fff5d9; }
        .status-processing { color: #a56a00; background: #fff5d9; }
        .status-shipped { color: #5965bd; background: #eef0ff; }
        .status-delivered { color: #15845a; background: #e8f8f0; }
        .status-cancelled { color: #bd3343; background: #fff0f2; }
        .status-default { color: #696270; background: #f2f0f4; }

        /*
        |--------------------------------------------------------------------------
        | PAYMENT
        |--------------------------------------------------------------------------
        */
        .payment-method {
            font-weight: 650;
            color: #453e4d;
        }

        .payment-status {
            margin-top: 3px;
            color: #88818f;
            font-size: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */
        .order-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .order-action-btn {
            width: 33px;
            height: 33px;
            display: grid;
            place-items: center;
            border: 1px solid #e6e1eb;
            border-radius: 9px;
            background: #fff;
            color: #625a6d;
            cursor: pointer;
            text-decoration: none;
            transition: transform .2s ease, border-color .2s ease, color .2s ease, background .2s ease;
        }

        .order-action-btn:hover {
            transform: translateY(-1px);
            color: #7627c9;
            border-color: #cdb3e5;
            background: #faf6ff;
        }

        .order-action-danger:hover {
            color: #c33242;
            border-color: #efb8c0;
            background: #fff5f6;
        }

        .order-action-whatsapp:hover {
            color: #138b57;
            border-color: #b6e4cd;
            background: #f0fbf5;
        }

        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */
        .orders-empty {
            padding: 65px 20px;
            text-align: center;
        }

        .orders-empty-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin: 0 auto 13px;
            border-radius: 16px;
            background: #f5effb;
            color: #8a42c6;
            font-size: 25px;
        }

        .orders-empty h3 {
            margin: 0;
            color: #302a38;
            font-size: 16px;
        }

        .orders-empty p {
            max-width: 400px;
            margin: 7px auto 0;
            color: #89838f;
            font-size: 11px;
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */
        .order-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(31, 20, 45, 0.48);
            backdrop-filter: blur(4px);
        }

        .order-modal.show {
            display: flex;
        }

        .order-modal-card {
            width: min(760px, 100%);
            max-height: 90vh;
            overflow: hidden;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 25px 70px rgba(25, 15, 40, 0.22);
        }

        .order-modal-header {
            padding: 17px 19px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #eeeaf2;
        }

        .order-modal-title {
            font-size: 17px;
            font-weight: 750;
            color: #2d2735;
        }

        .order-modal-close {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 9px;
            background: #f6f3f8;
            color: #625a6d;
            cursor: pointer;
            font-size: 18px;
        }

        .order-modal-body {
            max-height: calc(90vh - 70px);
            overflow-y: auto;
            padding: 19px;
        }

        .order-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .order-detail-box {
            padding: 13px;
            border: 1px solid #eeeaf2;
            border-radius: 12px;
            background: #fcfbfd;
        }

        .order-detail-label {
            color: #8a8390;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 750;
        }

        .order-detail-value {
            margin-top: 5px;
            color: #342d3c;
            font-size: 12px;
            font-weight: 650;
            line-height: 1.5;
            word-break: break-word;
        }

        .order-items-title {
            margin: 0 0 10px;
            color: #342d3c;
            font-size: 14px;
            font-weight: 750;
        }

        .order-items-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .order-item {
            padding: 11px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border: 1px solid #eeeaf2;
            border-radius: 11px;
        }

        .order-item-name {
            font-weight: 650;
            color: #403847;
            font-size: 11px;
        }

        .order-item-meta {
            margin-top: 3px;
            color: #8a8390;
            font-size: 9px;
        }

        .order-item-price {
            white-space: nowrap;
            color: #7627c9;
            font-weight: 750;
            font-size: 11px;
        }

        .order-update-form {
            margin-top: 18px;
            padding: 14px;
            border-radius: 13px;
            background: #faf8fc;
            border: 1px solid #eeeaf2;
        }

        .order-update-form-title {
            margin-bottom: 9px;
            font-size: 11px;
            font-weight: 750;
            color: #453d4d;
        }

        .order-update-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 9px;
        }

        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */
        @media (max-width: 1200px) {
            .order-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .orders-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .orders-filter-actions {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 800px) {
            .orders-header {
                flex-direction: column;
            }
            .orders-header-actions {
                width: 100%;
            }
            .orders-header-actions .btn {
                flex: 1;
            }
            .order-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .orders-filter-form {
                grid-template-columns: 1fr;
            }
            .orders-filter-actions {
                grid-column: auto;
            }
            .orders-filter-actions .btn {
                flex: 1;
            }
        }

        @media (max-width: 560px) {
            .orders-title h1 {
                font-size: 25px;
            }
            .order-stat-grid {
                grid-template-columns: 1fr;
            }
            .orders-table-header {
                align-items: flex-start;
                flex-direction: column;
            }
            .order-detail-grid {
                grid-template-columns: 1fr;
            }
            .order-update-row {
                grid-template-columns: 1fr;
            }
            .order-modal {
                padding: 10px;
            }
            .order-modal-card {
                max-height: 94vh;
            }
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
        <div class="page-content">
            <div class="orders-page">

                <!-- PAGE HEADER -->
                <section class="orders-header">
                    <div class="orders-title">
                        <h1>Orders</h1>
                        <p>Manage customer orders, payments and delivery status.</p>
                    </div>

                    <div class="orders-header-actions">
                        <a href="orders.php" class="btn btn-secondary">🔄 Refresh</a>
                    </div>
                </section>

                <!-- ALERTS -->
                <?php if ($successMessage !== ''): ?>
                    <div class="orders-alert orders-alert-success">
                        <?= e($successMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="orders-alert orders-alert-error">
                        <?= e($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <!-- STATISTICS -->
                <section class="order-stat-grid">
                    <!-- TOTAL -->
                    <div class="order-stat-card">
                        <div class="order-stat-top">
                            <div class="order-stat-icon">📦</div>
                        </div>
                        <div class="order-stat-number"><?= number_format($totalOrders) ?></div>
                        <div class="order-stat-label">Total Orders</div>
                    </div>

                    <!-- PENDING -->
                    <div class="order-stat-card">
                        <div class="order-stat-top">
                            <div class="order-stat-icon">🕐</div>
                        </div>
                        <div class="order-stat-number"><?= number_format($pendingOrders) ?></div>
                        <div class="order-stat-label">New / Pending</div>
                    </div>

                    <!-- PROCESSING -->
                    <div class="order-stat-card order-stat-processing">
                        <div class="order-stat-top">
                            <div class="order-stat-icon">⚙️</div>
                        </div>
                        <div class="order-stat-number"><?= number_format($processingOrders) ?></div>
                        <div class="order-stat-label">Processing</div>
                    </div>

                    <!-- SHIPPED -->
                    <div class="order-stat-card order-stat-shipped">
                        <div class="order-stat-top">
                            <div class="order-stat-icon">🚚</div>
                        </div>
                        <div class="order-stat-number"><?= number_format($shippedOrders) ?></div>
                        <div class="order-stat-label">Shipped</div>
                    </div>

                    <!-- DELIVERED -->
                    <div class="order-stat-card order-stat-delivered">
                        <div class="order-stat-top">
                            <div class="order-stat-icon">✓</div>
                        </div>
                        <div class="order-stat-number"><?= number_format($deliveredOrders) ?></div>
                        <div class="order-stat-label">Delivered</div>
                    </div>
                </section>

                <!-- REVENUE MINI CARD -->
                <section style="margin-bottom:20px;">
                    <div class="order-stat-card order-stat-revenue" style="display:flex; align-items:center; justify-content: space-betweeneen; gap:20px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div class="order-stat-icon">₹</div>
                            <div>
                                <div style="font-size:11px; color:#77717f;">Total Sales</div>
                                <div style="margin-top:3px; font-size:23px; font-weight:750; color:#25212d;">
                                    <?= e(moneyFormat($totalRevenue)) ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right; color:#15845a; font-size:10px; font-weight:700;">
                            All loaded orders
                        </div>
                    </div>
                </section>

                <!-- FILTERS -->
                <section class="orders-filter-card">
                    <form method="GET" action="orders.php" class="orders-filter-form">
                        <!-- SEARCH -->
                        <div class="orders-field">
                            <label for="ordersSearch">Search Orders</label>
                            <input type="search" id="ordersSearch" name="search" class="orders-input" value="<?= e($search) ?>" placeholder="Order number, customer, phone...">
                        </div>

                        <!-- STATUS -->
                        <div class="orders-field">
                            <label for="ordersStatus">Status</label>
                            <select id="ordersStatus" name="status" class="orders-select">
                                <option value="">All Status</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <!-- PAYMENT -->
                        <div class="orders-field">
                            <label for="ordersPayment">Payment</label>
                            <select id="ordersPayment" name="payment" class="orders-select">
                                <option value="">All Payments</option>
                                <option value="cod" <?= $paymentFilter === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
                                <option value="online" <?= $paymentFilter === 'online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>

                        <!-- DATE -->
                        <div class="orders-field">
                            <label for="ordersDate">Order Date</label>
                            <input type="date" id="ordersDate" name="date" class="orders-input" value="<?= e($dateFilter) ?>">
                        </div>

                        <!-- BUTTONS -->
                        <div class="orders-filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 Filter</button>
                            <a href="orders.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </section>

                <!-- ORDERS TABLE -->
                <section class="orders-table-card">
                    <!-- TABLE HEADER -->
                    <div class="orders-table-header">
                        <div>
                            <div class="orders-table-title">All Orders</div>
                            <div class="orders-table-subtitle">View and manage customer orders.</div>
                        </div>
                        <div class="orders-result-count">
                            <?= number_format(count($orders)) ?> order(s)
                        </div>
                    </div>

                    <?php if (!$ordersTableExists): ?>
                        <div class="orders-empty">
                            <div class="orders-empty-icon">⚠️</div>
                            <h3>Orders table not found</h3>
                            <p>The <strong>orders</strong> table does not exist in the current database.</p>
                        </div>
                    <?php elseif (empty($orders)): ?>
                        <div class="orders-empty">
                            <div class="orders-empty-icon">📦</div>
                            <h3>No orders found</h3>
                            <p>Orders will appear here when customers place orders. Try changing your filters if you expected to see an order.</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-table-wrapper">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>ORDER</th>
                                        <th>CUSTOMER</th>
                                        <th>ITEMS</th>
                                        <th>AMOUNT</th>
                                        <th>PAYMENT</th>
                                        <th>STATUS</th>
                                        <th>DATE</th>
                                        <th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $orderId     = (int) ($order['order_id'] ?? 0);
                                    $orderNumber = trim((string) ($order['order_number'] ?? ''));

                                    if ($orderNumber === '') {
                                        $orderNumber = 'ORD-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
                                    }

                                    $customerName = trim((string) ($order['customer_name'] ?? $order['joined_customer_name'] ?? 'Guest Customer'));
                                    if ($customerName === '') {
                                        $customerName = 'Guest Customer';
                                    }

                                    $customerPhone = trim((string) ($order['customer_phone'] ?? $order['joined_customer_phone'] ?? ''));
                                    $customerEmail = trim((string) ($order['customer_email'] ?? $order['joined_customer_email'] ?? ''));
                                    $orderStatus   = strtolower(trim((string) ($order['order_status'] ?? 'pending')));
                                    $paymentMethod = trim((string) ($order['payment_method'] ?? ''));

                                    if ($paymentMethod === '') {
                                        $paymentMethod = 'COD';
                                    }

                                    $paymentStatus    = trim((string) ($order['payment_status'] ?? ''));
                                    $itemsCount       = (int) ($order['item_count'] ?? 0);
                                    $totalAmount      = (float) ($order['total_amount'] ?? 0);
                                    $createdAt        = $order['created_at'] ?? null;
                                    $customerInitials = initials($customerName);
                                    $whatsappPhone    = preg_replace('/[^0-9]/', '', $customerPhone);

                                    $modalOrder = [
                                        'id'             => $orderId,
                                        'number'         => $orderNumber,
                                        'customer'       => $customerName,
                                        'phone'          => $customerPhone,
                                        'email'          => $customerEmail,
                                        'status'         => $orderStatus,
                                        'payment'        => $paymentMethod,
                                        'payment_status' => $paymentStatus,
                                        'amount'         => moneyFormat($totalAmount),
                                        'date'           => safeDateTime($createdAt),
                                        'address'        => trim((string) ($order['shipping_address'] ?? '')),
                                        'city'           => trim((string) ($order['city'] ?? '')),
                                        'state'          => trim((string) ($order['state'] ?? '')),
                                        'pincode'        => trim((string) ($order['pincode'] ?? '')),
                                        'notes'          => trim((string) ($order['order_notes'] ?? ''))
                                    ];
                                    ?>
                                    <tr>
                                        <!-- ORDER -->
                                        <td>
                                            <a href="javascript:void(0)" class="order-number" onclick="openOrderModal(<?= e(htmlspecialchars(json_encode($modalOrder, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8')) ?>)">
                                                #<?= e($orderNumber) ?>
                                            </a>
                                            <span class="order-id-small">ID: <?= e($orderId) ?></span>
                                        </td>

                                        <!-- CUSTOMER -->
                                        <td>
                                            <div class="order-customer">
                                                <div class="customer-avatar"><?= e($customerInitials) ?></div>
                                                <div>
                                                    <div class="customer-name"><?= e($customerName) ?></div>
                                                    <?php if ($customerPhone !== ''): ?>
                                                        <div class="customer-contact"><?= e($customerPhone) ?></div>
                                                    <?php elseif ($customerEmail !== ''): ?>
                                                        <div class="customer-contact"><?= e($customerEmail) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- ITEMS -->
                                        <td>
                                            <?= number_format($itemsCount) ?> <?= $itemsCount === 1 ? 'item' : 'items' ?>
                                        </td>

                                        <!-- AMOUNT -->
                                        <td>
                                            <strong><?= e(moneyFormat($totalAmount)) ?></strong>
                                        </td>

                                        <!-- PAYMENT -->
                                        <td>
                                            <div class="payment-method"><?= e(strtoupper($paymentMethod)) ?></div>
                                            <?php if ($paymentStatus !== ''): ?>
                                                <div class="payment-status"><?= e(ucfirst($paymentStatus)) ?></div>
                                            <?php else: ?>
                                                <div class="payment-status">Payment</div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- STATUS -->
                                        <td>
                                            <span class="order-status <?= e(orderStatusClass($orderStatus)) ?>">
                                                <?= e(orderStatusLabel($orderStatus)) ?>
                                            </span>
                                        </td>

                                        <!-- DATE -->
                                        <td>
                                            <?= e(safeDate($createdAt)) ?>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td>
                                            <div class="order-actions">
                                                <!-- VIEW -->
                                                <button type="button" class="order-action-btn" title="View Order" onclick="openOrderModal(<?= e(htmlspecialchars(json_encode($modalOrder, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8')) ?>)">
                                                    👁
                                                </button>

                                                <!-- WHATSAPP -->
                                                <?php if ($whatsappPhone !== ''): ?>
                                                    <a href="https://wa.me/<?= e($whatsappPhone) ?>" target="_blank" rel="noopener noreferrer" class="order-action-btn order-action-whatsapp" title="WhatsApp Customer">
                                                        💬
                                                    </a>
                                                <?php endif; ?>

                                                <!-- CALL -->
                                                <?php if ($customerPhone !== ''): ?>
                                                    <a href="tel:<?= e($customerPhone) ?>" class="order-action-btn" title="Call Customer">
                                                        📞
                                                    </a>
                                                <?php endif; ?>

                                                <!-- DELETE -->
                                                <button type="button" class="order-action-btn order-action-danger" title="Delete Order" onclick="confirmDeleteOrder(<?= e($orderId) ?>, '<?= e($orderNumber) ?>')">
                                                    🗑
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </div>
    </main>
</div>

<!-- =========================================================
     ORDER DETAILS MODAL
     ========================================================= -->
<div class="order-modal" id="orderModal" aria-hidden="true">
    <div class="order-modal-card" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle">
        <!-- HEADER -->
        <div class="order-modal-header">
            <div class="order-modal-title" id="orderModalTitle">Order Details</div>
            <button type="button" class="order-modal-close" onclick="closeOrderModal()" aria-label="Close">×</button>
        </div>

        <!-- BODY -->
        <div class="order-modal-body" id="orderModalBody">
            <div class="order-detail-grid">
                <div class="order-detail-box">
                    <div class="order-detail-label">Order</div>
                    <div class="order-detail-value" id="modalOrderNumber">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Customer</div>
                    <div class="order-detail-value" id="modalCustomer">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Phone</div>
                    <div class="order-detail-value" id="modalPhone">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Email</div>
                    <div class="order-detail-value" id="modalEmail">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Amount</div>
                    <div class="order-detail-value" id="modalAmount">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Payment</div>
                    <div class="order-detail-value" id="modalPayment">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Order Date</div>
                    <div class="order-detail-value" id="modalDate">—</div>
                </div>

                <div class="order-detail-box">
                    <div class="order-detail-label">Current Status</div>
                    <div class="order-detail-value" id="modalStatus">—</div>
                </div>
            </div>

            <!-- ADDRESS -->
            <div class="order-detail-box" style="margin-bottom:18px;">
                <div class="order-detail-label">Delivery Address</div>
                <div class="order-detail-value" id="modalAddress">—</div>
            </div>

            <!-- NOTES -->
            <div class="order-detail-box" id="modalNotesBox" style="margin-bottom:18px; display:none;">
                <div class="order-detail-label">Order Notes</div>
                <div class="order-detail-value" id="modalNotes">—</div>
            </div>

            <!-- ITEMS -->
            <h3 class="order-items-title">Order Items</h3>
            <div class="order-items-list" id="modalItems">
                <div class="order-item">
                    <div>
                        <div class="order-item-name">Order items</div>
                        <div class="order-item-meta">Item details will appear here.</div>
                    </div>
                </div>
            </div>

            <!-- STATUS UPDATE -->
            <form method="POST" action="orders.php" class="order-update-form" id="modalStatusForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="modalOrderId" value="">

                <div class="order-update-form-title">Update Order Status</div>

                <div class="order-update-row">
                    <select name="status" id="modalStatusSelect" class="orders-select">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================================================
     DELETE FORM
     ========================================================= -->
<form method="POST" action="orders.php" id="deleteOrderForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="delete_order">
    <input type="hidden" name="order_id" id="deleteOrderId" value="">
</form>

<script>
/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/
function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/*
|--------------------------------------------------------------------------
| ORDER MODAL
|--------------------------------------------------------------------------
*/
function openOrderModal(order) {
    const modal = document.getElementById('orderModal');
    if (!modal) return;

    document.getElementById('modalOrderNumber').textContent = order.number || '—';
    document.getElementById('modalCustomer').textContent    = order.customer || '—';
    document.getElementById('modalPhone').textContent       = order.phone || '—';
    document.getElementById('modalEmail').textContent       = order.email || '—';
    document.getElementById('modalAmount').textContent      = order.amount || '—';
    document.getElementById('modalPayment').textContent     = order.payment || '—';
    document.getElementById('modalDate').textContent        = order.date || '—';
    document.getElementById('modalStatus').textContent      = order.status || '—';
    document.getElementById('modalOrderId').value           = order.id || '';
    document.getElementById('modalStatusSelect').value     = normalizeStatus(order.status);

    let address = '';
    if (order.address) address += order.address;

    const locationParts = [];
    if (order.city) locationParts.push(order.city);
    if (order.state) locationParts.push(order.state);
    if (order.pincode) locationParts.push(order.pincode);

    if (locationParts.length > 0) {
        if (address !== '') address += ', ';
        address += locationParts.join(', ');
    }

    document.getElementById('modalAddress').textContent = address || '—';

    /* NOTES */
    const notesBox = document.getElementById('modalNotesBox');
    const notes    = document.getElementById('modalNotes');

    if (order.notes && String(order.notes).trim() !== '') {
        notes.textContent = order.notes;
        notesBox.style.display = 'block';
    } else {
        notes.textContent = '—';
        notesBox.style.display = 'none';
    }

    /* ITEMS */
    const itemsContainer = document.getElementById('modalItems');
    itemsContainer.innerHTML = `
        <div class="order-item">
            <div>
                <div class="order-item-name">${escapeHtml(order.number || 'Order')}</div>
                <div class="order-item-meta">View the complete order item details from the order record.</div>
            </div>
            <div class="order-item-price">${escapeHtml(order.amount || '₹0.00')}</div>
        </div>
    `;

    /* SHOW */
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

/*
|--------------------------------------------------------------------------
| NORMALIZE STATUS
|--------------------------------------------------------------------------
*/
function normalizeStatus(status) {
    const value = String(status || '').toLowerCase().trim();

    if (value === 'new' || value === 'placed') return 'pending';
    if (value === 'shipping') return 'shipped';
    if (value === 'completed') return 'delivered';
    if (value === 'canceled') return 'cancelled';

    return value || 'pending';
}

/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/
function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE MODAL & ESC KEY
|--------------------------------------------------------------------------
*/
document.getElementById('orderModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        closeOrderModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeOrderModal();
    }
});

/*
|--------------------------------------------------------------------------
| DELETE CONFIRMATION
|--------------------------------------------------------------------------
*/
function confirmDeleteOrder(orderId, orderNumber) {
    const confirmed = window.confirm(
        'Are you sure you want to delete order #' + orderNumber + '?\n\nThis action cannot be undone.'
    );

    if (!confirmed) return;

    document.getElementById('deleteOrderId').value = orderId;
    document.getElementById('deleteOrderForm').submit();
}

/*
|--------------------------------------------------------------------------
| AUTO HIDE SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/
window.setTimeout(function() {
    const alerts = document.querySelectorAll('.orders-alert-success');
    alerts.forEach(function(alert) {
        alert.style.transition = 'opacity .3s ease';
        alert.style.opacity = '0';
        window.setTimeout(function() {
            alert.remove();
        }, 300);
    });
}, 4500);
</script>

</body>
</html>