<?php

/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ADMIN ANALYTICS
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

$pageTitle = 'Analytics';


/**
 * =========================================================
 * HELPER FUNCTIONS
 * =========================================================
 */

/**
 * Check whether a table exists.
 */
function tableExists(PDO $pdo, string $tableName): bool
{
    try {

        $stmt = $pdo->prepare(
            "
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = :table_name
            "
        );

        $stmt->execute([
            ':table_name' => $tableName
        ]);

        return (int) $stmt->fetchColumn() > 0;

    } catch (Throwable $e) {

        return false;
    }
}


/**
 * Get columns of a table.
 */
function getTableColumns(PDO $pdo, string $tableName): array
{
    if (!tableExists($pdo, $tableName)) {
        return [];
    }

    try {

        $stmt = $pdo->query(
            "SHOW COLUMNS FROM `" . str_replace('`', '``', $tableName) . "`"
        );

        $columns = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;

    } catch (Throwable $e) {

        return [];
    }
}


/**
 * Find first matching column from candidates.
 */
function findColumn(array $columns, array $candidates): ?string
{
    $lowerColumns = [];

    foreach ($columns as $column) {
        $lowerColumns[strtolower($column)] = $column;
    }

    foreach ($candidates as $candidate) {

        $candidateLower = strtolower($candidate);

        if (isset($lowerColumns[$candidateLower])) {
            return $lowerColumns[$candidateLower];
        }
    }

    return null;
}


/**
 * Safely quote a database identifier.
 */
function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}


/**
 * Escape HTML.
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
 * Format Indian currency.
 */
function formatCurrency(float $amount): string
{
    return '₹' . number_format(
        $amount,
        0,
        '.',
        ','
    );
}


/**
 * Format large numbers.
 */
function formatNumber(int $number): string
{
    return number_format(
        $number,
        0,
        '.',
        ','
    );
}


/**
 * =========================================================
 * DEFAULT ANALYTICS VALUES
 * =========================================================
 */

$totalOrders = 0;

$totalRevenue = 0.0;

$averageOrderValue = 0.0;

$totalProducts = 0;

$totalCustomers = 0;

$totalUnitsSold = 0;

$topProducts = [];

$statusBreakdown = [];

$dailySales = [];

$analyticsError = null;


/**
 * =========================================================
 * DATE FILTER
 * =========================================================
 */

$allowedPeriods = [
    '7d',
    '30d',
    '90d',
    '1y',
    'all'
];

$period = $_GET['period'] ?? '30d';

if (!in_array($period, $allowedPeriods, true)) {
    $period = '30d';
}


/**
 * =========================================================
 * DATABASE ANALYSIS
 * =========================================================
 */

try {

    /**
     * -----------------------------------------------------
     * CHECK TABLES
     * -----------------------------------------------------
     */

    $ordersExists = tableExists(
        $pdo,
        'orders'
    );

    $productsExists = tableExists(
        $pdo,
        'products'
    );

    $customersExists = tableExists(
        $pdo,
        'customers'
    );

    $orderItemsExists = tableExists(
        $pdo,
        'order_items'
    );


    /**
     * -----------------------------------------------------
     * PRODUCTS COUNT
     * -----------------------------------------------------
     */

    if ($productsExists) {

        $productsColumns = getTableColumns(
            $pdo,
            'products'
        );

        $productStatusColumn = findColumn(
            $productsColumns,
            [
                'status',
                'product_status'
            ]
        );

        if ($productStatusColumn !== null) {

            $stmt = $pdo->query(
                "
                SELECT COUNT(*)
                FROM products
                WHERE " .
                quoteIdentifier($productStatusColumn) .
                " = 'active'
                "
            );

        } else {

            $stmt = $pdo->query(
                "
                SELECT COUNT(*)
                FROM products
                "
            );
        }

        $totalProducts = (int) $stmt->fetchColumn();
    }


    /**
     * -----------------------------------------------------
     * CUSTOMERS COUNT
     * -----------------------------------------------------
     */

    if ($customersExists) {

        $stmt = $pdo->query(
            "
            SELECT COUNT(*)
            FROM customers
            "
        );

        $totalCustomers = (int) $stmt->fetchColumn();
    }


    /**
     * -----------------------------------------------------
     * ORDER ANALYTICS
     * -----------------------------------------------------
     */

    if ($ordersExists) {

        $ordersColumns = getTableColumns(
            $pdo,
            'orders'
        );


        /**
         * Find date column.
         */
        $orderDateColumn = findColumn(
            $ordersColumns,
            [
                'order_date',
                'created_at',
                'created_date',
                'ordered_at',
                'date',
                'order_created_at'
            ]
        );


        /**
         * Find total amount column.
         */
        $orderAmountColumn = findColumn(
            $ordersColumns,
            [
                'total_amount',
                'grand_total',
                'order_total',
                'total',
                'amount',
                'final_amount',
                'net_total'
            ]
        );


        /**
         * Find status column.
         */
        $orderStatusColumn = findColumn(
            $ordersColumns,
            [
                'status',
                'order_status'
            ]
        );


        /**
         * -------------------------------------------------
         * BASE WHERE CONDITION
         * -------------------------------------------------
         */

        $whereParts = [];

        $queryParams = [];


        /**
         * Exclude cancelled/refunded orders from revenue
         * and sales analytics.
         */
        if ($orderStatusColumn !== null) {

            $whereParts[] =
                "LOWER(" .
                quoteIdentifier($orderStatusColumn) .
                ") NOT IN (
                    'cancelled',
                    'canceled',
                    'refunded',
                    'failed'
                )";
        }


        /**
         * Date filter.
         */
        if (
            $orderDateColumn !== null &&
            $period !== 'all'
        ) {

            $days = 30;

            if ($period === '7d') {
                $days = 7;
            }

            if ($period === '30d') {
                $days = 30;
            }

            if ($period === '90d') {
                $days = 90;
            }

            if ($period === '1y') {
                $days = 365;
            }

            $whereParts[] =
                quoteIdentifier($orderDateColumn) .
                " >= DATE_SUB(
                    CURDATE(),
                    INTERVAL :period_days DAY
                )";

            $queryParams[':period_days'] = $days;
        }


        $whereSQL = '';

        if (!empty($whereParts)) {

            $whereSQL =
                'WHERE ' .
                implode(
                    ' AND ',
                    $whereParts
                );
        }


        /**
         * -------------------------------------------------
         * TOTAL ORDERS
         * -------------------------------------------------
         */

        $stmt = $pdo->prepare(
            "
            SELECT COUNT(*)
            FROM orders
            {$whereSQL}
            "
        );

        $stmt->execute(
            $queryParams
        );

        $totalOrders = (int) $stmt->fetchColumn();


        /**
         * -------------------------------------------------
         * TOTAL REVENUE
         * -------------------------------------------------
         */

        if ($orderAmountColumn !== null) {

            $stmt = $pdo->prepare(
                "
                SELECT COALESCE(
                    SUM(
                        " .
                        quoteIdentifier($orderAmountColumn) .
                        "
                    ),
                    0
                )
                FROM orders
                {$whereSQL}
                "
            );

            $stmt->execute(
                $queryParams
            );

            $totalRevenue = (float) $stmt->fetchColumn();


            /**
             * Average order value.
             */
            if ($totalOrders > 0) {

                $averageOrderValue =
                    $totalRevenue /
                    $totalOrders;
            }
        }


        /**
         * -------------------------------------------------
         * ORDER STATUS BREAKDOWN
         * -------------------------------------------------
         */

        if ($orderStatusColumn !== null) {

            $statusWhereParts = [];

            $statusParams = [];


            if (
                $orderDateColumn !== null &&
                $period !== 'all'
            ) {

                $days = 30;

                if ($period === '7d') {
                    $days = 7;
                }

                if ($period === '30d') {
                    $days = 30;
                }

                if ($period === '90d') {
                    $days = 90;
                }

                if ($period === '1y') {
                    $days = 365;
                }

                $statusWhereParts[] =
                    quoteIdentifier($orderDateColumn) .
                    " >= DATE_SUB(
                        CURDATE(),
                        INTERVAL :status_days DAY
                    )";

                $statusParams[':status_days'] =
                    $days;
            }


            $statusWhereSQL = '';

            if (!empty($statusWhereParts)) {

                $statusWhereSQL =
                    'WHERE ' .
                    implode(
                        ' AND ',
                        $statusWhereParts
                    );
            }


            $statusStmt = $pdo->prepare(
                "
                SELECT
                    " .
                    quoteIdentifier($orderStatusColumn) .
                    " AS status,
                    COUNT(*) AS total
                FROM orders
                {$statusWhereSQL}
                GROUP BY " .
                quoteIdentifier($orderStatusColumn) .
                "
                ORDER BY total DESC
                "
            );

            $statusStmt->execute(
                $statusParams
            );

            $statusRows =
                $statusStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach ($statusRows as $row) {

                $statusName =
                    trim(
                        (string) (
                            $row['status'] ??
                            'Unknown'
                        )
                    );

                if ($statusName === '') {
                    $statusName = 'Unknown';
                }

                $statusBreakdown[] = [
                    'status' => $statusName,
                    'total' =>
                        (int) (
                            $row['total'] ??
                            0
                        )
                ];
            }
        }


        /**
         * -------------------------------------------------
         * DAILY SALES DATA
         * -------------------------------------------------
         */

        if (
            $orderDateColumn !== null &&
            $orderAmountColumn !== null
        ) {

            $chartWhereParts = [];

            $chartParams = [];


            /**
             * Exclude cancelled/refunded.
             */
            if ($orderStatusColumn !== null) {

                $chartWhereParts[] =
                    "LOWER(" .
                    quoteIdentifier($orderStatusColumn) .
                    ") NOT IN (
                        'cancelled',
                        'canceled',
                        'refunded',
                        'failed'
                    )";
            }


            /**
             * Date range.
             */
            if ($period !== 'all') {

                $days = 30;

                if ($period === '7d') {
                    $days = 7;
                }

                if ($period === '30d') {
                    $days = 30;
                }

                if ($period === '90d') {
                    $days = 90;
                }

                if ($period === '1y') {
                    $days = 365;
                }

                $chartWhereParts[] =
                    quoteIdentifier($orderDateColumn) .
                    " >= DATE_SUB(
                        CURDATE(),
                        INTERVAL :chart_days DAY
                    )";

                $chartParams[':chart_days'] =
                    $days;
            }


            $chartWhereSQL = '';

            if (!empty($chartWhereParts)) {

                $chartWhereSQL =
                    'WHERE ' .
                    implode(
                        ' AND ',
                        $chartWhereParts
                    );
            }


            /**
             * For one year / all data we use monthly
             * aggregation to keep the chart readable.
             */
            if (
                $period === '1y' ||
                $period === 'all'
            ) {

                $chartStmt = $pdo->prepare(
                    "
                    SELECT
                        DATE_FORMAT(
                            " .
                            quoteIdentifier($orderDateColumn) .
                            ",
                            '%Y-%m'
                        ) AS sale_date,

                        COALESCE(
                            SUM(
                                " .
                                quoteIdentifier(
                                    $orderAmountColumn
                                ) .
                                "
                            ),
                            0
                        ) AS revenue

                    FROM orders

                    {$chartWhereSQL}

                    GROUP BY
                        DATE_FORMAT(
                            " .
                            quoteIdentifier(
                                $orderDateColumn
                            ) .
                            ",
                            '%Y-%m'
                        )

                    ORDER BY sale_date ASC
                    "
                );

            } else {

                $chartStmt = $pdo->prepare(
                    "
                    SELECT
                        DATE(
                            " .
                            quoteIdentifier(
                                $orderDateColumn
                            ) .
                            "
                        ) AS sale_date,

                        COALESCE(
                            SUM(
                                " .
                                quoteIdentifier(
                                    $orderAmountColumn
                                ) .
                                "
                            ),
                            0
                        ) AS revenue

                    FROM orders

                    {$chartWhereSQL}

                    GROUP BY
                        DATE(
                            " .
                            quoteIdentifier(
                                $orderDateColumn
                            ) .
                            "
                        )

                    ORDER BY sale_date ASC
                    "
                );
            }


            $chartStmt->execute(
                $chartParams
            );

            $chartRows =
                $chartStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach ($chartRows as $row) {

                $dailySales[] = [
                    'date' =>
                        (string) (
                            $row['sale_date'] ??
                            ''
                        ),

                    'revenue' =>
                        (float) (
                            $row['revenue'] ??
                            0
                        )
                ];
            }
        }
    }


    /**
     * =====================================================
     * ORDER ITEMS ANALYTICS
     * =====================================================
     */

    if (
        $ordersExists &&
        $orderItemsExists &&
        $productsExists
    ) {

        $orderItemsColumns =
            getTableColumns(
                $pdo,
                'order_items'
            );

        $productsColumns =
            getTableColumns(
                $pdo,
                'products'
            );


        /**
         * Detect order_items columns.
         */
        $itemProductIdColumn =
            findColumn(
                $orderItemsColumns,
                [
                    'product_id',
                    'product',
                    'productid'
                ]
            );

        $itemQuantityColumn =
            findColumn(
                $orderItemsColumns,
                [
                    'quantity',
                    'qty',
                    'product_quantity'
                ]
            );


        /**
         * Detect product ID.
         */
        $productIdColumn =
            findColumn(
                $productsColumns,
                [
                    'id',
                    'product_id'
                ]
            );


        /**
         * Detect product name.
         */
        $productNameColumn =
            findColumn(
                $productsColumns,
                [
                    'name',
                    'product_name',
                    'title'
                ]
            );


        /**
         * Detect product price.
         */
        $itemPriceColumn =
            findColumn(
                $orderItemsColumns,
                [
                    'price',
                    'unit_price',
                    'selling_price',
                    'product_price'
                ]
            );


        /**
         * Detect line total.
         */
        $itemTotalColumn =
            findColumn(
                $orderItemsColumns,
                [
                    'line_total',
                    'subtotal',
                    'total',
                    'amount'
                ]
            );


        /**
         * -------------------------------------------------
         * TOP PRODUCTS
         * -------------------------------------------------
         */

        if (
            $itemProductIdColumn !== null &&
            $productIdColumn !== null &&
            $productNameColumn !== null
        ) {

            /**
             * Determine sold quantity.
             */
            if ($itemQuantityColumn !== null) {

                $quantityExpression =
                    'SUM(oi.' .
                    quoteIdentifier(
                        $itemQuantityColumn
                    ) .
                    ')';

            } else {

                $quantityExpression =
                    'COUNT(*)';
            }


            /**
             * Determine revenue.
             */
            if ($itemTotalColumn !== null) {

                $revenueExpression =
                    'SUM(oi.' .
                    quoteIdentifier(
                        $itemTotalColumn
                    ) .
                    ')';

            } elseif ($itemPriceColumn !== null) {

                if ($itemQuantityColumn !== null) {

                    $revenueExpression =
                        'SUM(
                            oi.' .
                            quoteIdentifier(
                                $itemPriceColumn
                            ) .
                            ' * oi.' .
                            quoteIdentifier(
                                $itemQuantityColumn
                            ) .
                        ')';

                } else {

                    $revenueExpression =
                        'SUM(oi.' .
                        quoteIdentifier(
                            $itemPriceColumn
                        ) .
                        ')';
                }

            } else {

                $revenueExpression = '0';
            }


            /**
             * Top products do not use the date filter
             * when "all" is selected.
             */
            $topWhereParts = [];

            $topParams = [];


            /**
             * If orders have a date column, use
             * selected analytics period.
             */
            if ($ordersExists) {

                $ordersColumns =
                    getTableColumns(
                        $pdo,
                        'orders'
                    );

                $topOrderDateColumn =
                    findColumn(
                        $ordersColumns,
                        [
                            'order_date',
                            'created_at',
                            'created_date',
                            'ordered_at',
                            'date',
                            'order_created_at'
                        ]
                    );

                $topOrderStatusColumn =
                    findColumn(
                        $ordersColumns,
                        [
                            'status',
                            'order_status'
                        ]
                    );

                $topOrderIdColumn =
                    findColumn(
                        $ordersColumns,
                        [
                            'id',
                            'order_id'
                        ]
                    );


                if (
                    $topOrderStatusColumn !== null
                ) {

                    $topWhereParts[] =
                        "LOWER(o." .
                        quoteIdentifier(
                            $topOrderStatusColumn
                        ) .
                        ") NOT IN (
                            'cancelled',
                            'canceled',
                            'refunded',
                            'failed'
                        )";
                }


                if (
                    $topOrderDateColumn !== null &&
                    $period !== 'all'
                ) {

                    $days = 30;

                    if ($period === '7d') {
                        $days = 7;
                    }

                    if ($period === '30d') {
                        $days = 30;
                    }

                    if ($period === '90d') {
                        $days = 90;
                    }

                    if ($period === '1y') {
                        $days = 365;
                    }

                    $topWhereParts[] =
                        "o." .
                        quoteIdentifier(
                            $topOrderDateColumn
                        ) .
                        " >= DATE_SUB(
                            CURDATE(),
                            INTERVAL :top_days DAY
                        )";

                    $topParams[':top_days'] =
                        $days;
                }


                if ($topOrderIdColumn !== null) {

                    $joinCondition =
                        "oi." .
                        quoteIdentifier(
                            findColumn(
                                $orderItemsColumns,
                                [
                                    'order_id',
                                    'orderid'
                                ]
                            ) ?? 'order_id'
                        ) .
                        " = o." .
                        quoteIdentifier(
                            $topOrderIdColumn
                        );

                } else {

                    $joinCondition = null;
                }


                $itemOrderIdColumn =
                    findColumn(
                        $orderItemsColumns,
                        [
                            'order_id',
                            'orderid'
                        ]
                    );


                if (
                    $joinCondition !== null &&
                    $itemOrderIdColumn !== null
                ) {

                    $topWhereSQL = '';

                    if (!empty($topWhereParts)) {

                        $topWhereSQL =
                            'WHERE ' .
                            implode(
                                ' AND ',
                                $topWhereParts
                            );
                    }


                    $topProductStmt =
    $pdo->prepare(
        "
        SELECT

            p." .
            quoteIdentifier(
                $productNameColumn
            ) .
            " AS product_name,

            {$quantityExpression}
            AS units_sold,

            {$revenueExpression}
            AS revenue

        FROM order_items oi

        INNER JOIN orders o
            ON " .
            $joinCondition . "

        INNER JOIN products p
            ON p." .
            quoteIdentifier(
                $productIdColumn
            ) .
            " = oi." .
            quoteIdentifier(
                $itemProductIdColumn
             ) .
            "

        " .
        $topWhereSQL .
        "

        GROUP BY
            p." .
            quoteIdentifier(
                $productIdColumn
            ) .
            ",
            p." .
            quoteIdentifier(
                $productNameColumn
            ) . "

        ORDER BY
            units_sold DESC

        LIMIT 5
        "
    );


                    $topProductStmt->execute(
                        $topParams
                    );


                    $topProducts =
                        $topProductStmt->fetchAll(
                            PDO::FETCH_ASSOC
                        );


                    foreach (
                        $topProducts
                        as &$topProduct
                    ) {

                        $topProduct['units_sold'] =
                            (int) (
                                $topProduct[
                                    'units_sold'
                                ] ?? 0
                            );

                        $topProduct['revenue'] =
                            (float) (
                                $topProduct[
                                    'revenue'
                                ] ?? 0
                            );
                    }

                    unset($topProduct);


                    /**
                     * Total units sold.
                     */
                    foreach (
                        $topProducts
                        as $topProduct
                    ) {

                        $totalUnitsSold +=
                            (int) (
                                $topProduct[
                                    'units_sold'
                                ] ?? 0
                            );
                    }
                }
            }
        }
    }


} catch (Throwable $e) {

    /**
     * Do not expose database details to the admin.
     */
    $analyticsError =
        'Some analytics data could not be loaded. Please check your database configuration.';

}


/**
 * =========================================================
 * CHART DATA
 * =========================================================
 */

$chartLabels = [];

$chartValues = [];

foreach ($dailySales as $sale) {

    $label = $sale['date'];

    /**
     * Convert YYYY-MM to readable month.
     */
    if (
        preg_match(
            '/^\d{4}-\d{2}$/',
            $label
        )
    ) {

        $timestamp =
            strtotime(
                $label . '-01'
            );

        if ($timestamp !== false) {

            $label =
                date(
                    'M Y',
                    $timestamp
                );
        }

    } else {

        $timestamp =
            strtotime($label);

        if ($timestamp !== false) {

            $label =
                date(
                    'd M',
                    $timestamp
                );
        }
    }


    $chartLabels[] = $label;

    $chartValues[] =
        round(
            (float) $sale['revenue'],
            2
        );
}


/**
 * =========================================================
 * STATUS TOTAL
 * =========================================================
 */

$statusTotal = 0;

foreach ($statusBreakdown as $status) {

    $statusTotal +=
        (int) $status['total'];
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
        content="Ssrini Handicrafts Admin Analytics"
    >

    <title>

        <?= e($pageTitle) ?>

        | Ssrini Handicrafts

    </title>


    <!--
        Existing Admin CSS
    -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!--
        Analytics Page Styles
    -->

    <style>

        /* =====================================================
           ANALYTICS PAGE
           ===================================================== */

        .analytics-page {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        /* =====================================================
           FILTER BAR
           ===================================================== */

        .analytics-filter-card {

            background: var(--surface, #ffffff);

            border-radius: 16px;

            padding: 18px 20px;

            border: 1px solid
                var(--border-light, #eeeaf2);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

        }


        .analytics-filter-left {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .analytics-filter-label {

            font-size: 13px;

            font-weight: 600;

            color:
                var(--text-secondary, #77717f);

        }


        .analytics-period {

            min-width: 150px;

            height: 42px;

            border: 1px solid
                #e3dfea;

            border-radius: 10px;

            padding: 0 12px;

            background: #ffffff;

            color: #25212d;

            outline: none;

            cursor: pointer;

        }


        .analytics-period:focus {

            border-color: #9b48d1;

            box-shadow:
                0 0 0 3px
                rgba(155, 72, 209, 0.10);

        }


        .analytics-filter-button {

            height: 42px;

            border: none;

            border-radius: 10px;

            padding: 0 18px;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 7px 16px
                rgba(118, 39, 201, 0.20);

        }


        /* =====================================================
           STATISTICS
           ===================================================== */

        .analytics-stats-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;

        }


        .analytics-stat-card {

            background: #ffffff;

            border-radius: 16px;

            padding: 20px;

            border: 1px solid
                #eeeaf2;

            box-shadow:
                0 7px 22px
                rgba(30, 20, 50, 0.05);

            position: relative;

            overflow: hidden;

        }


        .analytics-stat-card::after {

            content: "";

            position: absolute;

            width: 95px;

            height: 95px;

            border-radius: 50%;

            right: -40px;

            top: -40px;

            background:
                rgba(155, 72, 209, 0.06);

        }


        .analytics-stat-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            background:
                #eee5ff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            margin-bottom: 14px;

        }


        .analytics-stat-label {

            color:
                #77717f;

            font-size: 12px;

            margin-bottom: 7px;

        }


        .analytics-stat-value {

            font-size: 25px;

            font-weight: 700;

            color:
                #25212d;

            line-height: 1.2;

        }


        .analytics-stat-description {

            margin-top: 8px;

            font-size: 11px;

            color:
                #a09aa8;

        }


        /* =====================================================
           MAIN ANALYTICS GRID
           ===================================================== */

        .analytics-main-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 2fr)
                minmax(300px, 1fr);

            gap: 20px;

        }


        .analytics-card {

            background: #ffffff;

            border-radius: 16px;

            border: 1px solid
                #eeeaf2;

            box-shadow:
                0 7px 22px
                rgba(30, 20, 50, 0.05);

            overflow: hidden;

        }


        .analytics-card-header {

            padding: 19px 20px;

            border-bottom:
                1px solid
                #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .analytics-card-title {

            font-size: 15px;

            font-weight: 700;

            color:
                #25212d;

        }


        .analytics-card-description {

            margin-top: 4px;

            font-size: 11px;

            color:
                #9a94a2;

        }


        .analytics-card-body {

            padding: 20px;

        }


        /* =====================================================
           SALES CHART
           ===================================================== */

        .sales-chart {

            width: 100%;

            height: 300px;

            display: flex;

            flex-direction: column;

            justify-content: flex-end;

        }


        .chart-bars {

            height: 245px;

            display: flex;

            align-items: flex-end;

            gap: 7px;

            padding:
                10px 5px 0;

            border-bottom:
                1px solid
                #eeeaf2;

            overflow-x: auto;

        }


        .chart-bar-wrapper {

            min-width: 30px;

            height: 100%;

            display: flex;

            align-items: flex-end;

            justify-content: center;

            position: relative;

        }


        .chart-bar {

            width: 22px;

            min-height: 4px;

            border-radius:
                7px 7px 2px 2px;

            background:
                linear-gradient(
                    180deg,
                    #9b48d1,
                    #7627c9
                );

            transition:
                height 0.3s ease;

            position: relative;

        }


        .chart-bar:hover {

            opacity: 0.82;

        }


        .chart-tooltip {

            position: absolute;

            bottom: calc(100% + 7px);

            left: 50%;

            transform:
                translateX(-50%);

            background: #25212d;

            color: #ffffff;

            font-size: 10px;

            padding: 6px 8px;

            border-radius: 6px;

            white-space: nowrap;

            opacity: 0;

            pointer-events: none;

            transition:
                opacity 0.2s ease;

            z-index: 10;

        }


        .chart-bar:hover
        .chart-tooltip {

            opacity: 1;

        }


        .chart-labels {

            display: flex;

            gap: 7px;

            overflow-x: auto;

            padding:
                8px 5px 0;

        }


        .chart-label {

            min-width: 30px;

            text-align: center;

            font-size: 9px;

            color:
                #9a94a2;

            white-space: nowrap;

        }


        .chart-empty {

            height: 245px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            color:
                #9a94a2;

            font-size: 12px;

            border-bottom:
                1px solid
                #eeeaf2;

        }


        /* =====================================================
           TOP PRODUCTS
           ===================================================== */

        .top-product-list {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        .top-product-item {

            display: grid;

            grid-template-columns:
                36px
                minmax(0, 1fr)
                auto;

            align-items: center;

            gap: 10px;

            padding: 11px;

            border:
                1px solid
                #eeeaf2;

            border-radius: 10px;

        }


        .top-product-number {

            width: 32px;

            height: 32px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                #f1e8ff;

            color:
                #7627c9;

            font-size: 11px;

            font-weight: 700;

        }


        .top-product-name {

            font-size: 12px;

            font-weight: 600;

            color:
                #25212d;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .top-product-units {

            margin-top: 3px;

            font-size: 10px;

            color:
                #9a94a2;

        }


        .top-product-revenue {

            font-size: 12px;

            font-weight: 700;

            color:
                #25212d;

            white-space: nowrap;

        }


        /* =====================================================
           STATUS
           ===================================================== */

        .status-list {

            display: flex;

            flex-direction: column;

            gap: 12px;

        }


        .status-row {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .status-row-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            font-size: 11px;

        }


        .status-name {

            color:
                #5f5865;

            font-weight: 600;

            text-transform:
                capitalize;

        }


        .status-count {

            color:
                #25212d;

            font-weight: 700;

        }


        .status-progress {

            height: 7px;

            background:
                #f0edf3;

            border-radius: 10px;

            overflow: hidden;

        }


        .status-progress-bar {

            height: 100%;

            border-radius: 10px;

            background:
                linear-gradient(
                    90deg,
                    #7627c9,
                    #c52b9f
                );

        }


        /* =====================================================
           INSIGHT CARDS
           ===================================================== */

        .analytics-insight-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap: 18px;

        }


        .analytics-insight {

            padding: 18px;

            background: #ffffff;

            border:
                1px solid
                #eeeaf2;

            border-radius: 14px;

        }


        .analytics-insight-icon {

            font-size: 21px;

            margin-bottom: 10px;

        }


        .analytics-insight-title {

            font-size: 12px;

            color:
                #8c8693;

            margin-bottom: 5px;

        }


        .analytics-insight-value {

            font-size: 18px;

            font-weight: 700;

            color:
                #25212d;

        }


        /* =====================================================
           ERROR
           ===================================================== */

        .analytics-error {

            padding: 14px 16px;

            border-radius: 10px;

            background:
                #fff3f3;

            border:
                1px solid
                #ffd5d5;

            color:
                #c53030;

            font-size: 12px;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1100px) {

            .analytics-stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

            }


            .analytics-main-grid {

                grid-template-columns: 1fr;

            }


            .analytics-insight-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

            }

        }


        @media (max-width: 700px) {

            .analytics-stats-grid {

                grid-template-columns: 1fr;

            }


            .analytics-insight-grid {

                grid-template-columns: 1fr;

            }


            .analytics-filter-left {

                width: 100%;

            }


            .analytics-period {

                flex: 1;

            }


            .analytics-filter-button {

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

                        Analytics

                    </h1>


                    <p class="page-description">

                        Track your store performance,
                        sales and product insights.

                    </p>

                </div>


                <div>

                    <a
                        href="analytics.php?period=<?= e($period) ?>"
                        class="btn btn-primary"
                    >

                        🔄

                        Refresh Analytics

                    </a>

                </div>


            </section>


            <!-- =================================================
                 ANALYTICS
                 ================================================= -->

            <div class="analytics-page">


                <?php if ($analyticsError !== null): ?>

                    <div class="analytics-error">

                        <?= e($analyticsError) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     FILTER
                     ================================================= -->

                <form
                    method="GET"
                    action="analytics.php"
                    class="analytics-filter-card"
                >


                    <div class="analytics-filter-left">

                        <span
                            class="analytics-filter-label"
                        >

                            Analytics Period

                        </span>


                        <select
                            name="period"
                            class="analytics-period"
                        >

                            <option
                                value="7d"
                                <?= $period === '7d'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Last 7 Days

                            </option>


                            <option
                                value="30d"
                                <?= $period === '30d'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Last 30 Days

                            </option>


                            <option
                                value="90d"
                                <?= $period === '90d'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Last 90 Days

                            </option>


                            <option
                                value="1y"
                                <?= $period === '1y'
                                    ? 'selected'
                                    : '' ?>
                            >

                                Last 1 Year

                            </option>


                            <option
                                value="all"
                                <?= $period === 'all'
                                    ? 'selected'
                                    : '' ?>
                            >

                                All Time

                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="analytics-filter-button"
                    >

                        Apply Filter

                    </button>


                </form>


                <!-- =================================================
                     STAT CARDS
                     ================================================= -->

                <section
                    class="analytics-stats-grid"
                >


                    <!-- ORDERS -->

                    <div class="analytics-stat-card">

                        <div
                            class="analytics-stat-icon"
                        >

                            📦

                        </div>


                        <div
                            class="analytics-stat-label"
                        >

                            Total Orders

                        </div>


                        <div
                            class="analytics-stat-value"
                        >

                            <?= formatNumber(
                                $totalOrders
                            ) ?>

                        </div>


                        <div
                            class="analytics-stat-description"
                        >

                            Orders in selected period

                        </div>

                    </div>


                    <!-- REVENUE -->

                    <div class="analytics-stat-card">

                        <div
                            class="analytics-stat-icon"
                        >

                            ₹

                        </div>


                        <div
                            class="analytics-stat-label"
                        >

                            Total Revenue

                        </div>


                        <div
                            class="analytics-stat-value"
                        >

                            <?= formatCurrency(
                                $totalRevenue
                            ) ?>

                        </div>


                        <div
                            class="analytics-stat-description"
                        >

                            Sales excluding cancelled orders

                        </div>

                    </div>


                    <!-- AVERAGE ORDER -->

                    <div class="analytics-stat-card">

                        <div
                            class="analytics-stat-icon"
                        >

                            📈

                        </div>


                        <div
                            class="analytics-stat-label"
                        >

                            Average Order Value

                        </div>


                        <div
                            class="analytics-stat-value"
                        >

                            <?= formatCurrency(
                                $averageOrderValue
                            ) ?>

                        </div>


                        <div
                            class="analytics-stat-description"
                        >

                            Average revenue per order

                        </div>

                    </div>


                    <!-- PRODUCTS -->

                    <div class="analytics-stat-card">

                        <div
                            class="analytics-stat-icon"
                        >

                            🛍️

                        </div>


                        <div
                            class="analytics-stat-label"
                        >

                            Active Products

                        </div>


                        <div
                            class="analytics-stat-value"
                        >

                            <?= formatNumber(
                                $totalProducts
                            ) ?>

                        </div>


                        <div
                            class="analytics-stat-description"
                        >

                            Products currently available

                        </div>

                    </div>


                </section>


                <!-- =================================================
                     SALES + TOP PRODUCTS
                     ================================================= -->

                <section
                    class="analytics-main-grid"
                >


                    <!-- SALES OVERVIEW -->

                    <div class="analytics-card">


                        <div
                            class="analytics-card-header"
                        >

                            <div>

                                <div
                                    class="analytics-card-title"
                                >

                                    Sales Overview

                                </div>


                                <div
                                    class="analytics-card-description"
                                >

                                    Revenue performance for
                                    the selected period

                                </div>

                            </div>


                            <strong
                                style="
                                    font-size: 13px;
                                    color: #7627c9;
                                "
                            >

                                <?= formatCurrency(
                                    $totalRevenue
                                ) ?>

                            </strong>

                        </div>


                        <div
                            class="analytics-card-body"
                        >


                            <?php if (
                                !empty($chartValues)
                            ): ?>

                                <?php

                                $maxChartValue =
                                    max(
                                        $chartValues
                                    );

                                if (
                                    $maxChartValue <= 0
                                ) {
                                    $maxChartValue = 1;
                                }

                                ?>


                                <div
                                    class="sales-chart"
                                >


                                    <div
                                        class="chart-bars"
                                    >

                                        <?php foreach (
                                            $chartValues
                                            as $index =>
                                            $value
                                        ): ?>

                                            <?php

                                            $height =
                                                (
                                                    $value /
                                                    $maxChartValue
                                                ) *
                                                100;

                                            if (
                                                $value > 0 &&
                                                $height < 5
                                            ) {
                                                $height = 5;
                                            }

                                            ?>

                                            <div
                                                class="chart-bar-wrapper"
                                            >

<div
    class="chart-bar"
    style="height: <?= number_format($height, 2, '.', '') ?>%;"
>

                                                    <div
                                                        class="chart-tooltip"
                                                    >

                                                        <?= e($chartLabels[$index] ?? '') ?>

                                                        :

                                                         <?= formatCurrency((float) $value) ?>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                    <div
                                        class="chart-labels"
                                    >

                                        <?php foreach (
                                            $chartLabels
                                            as $label
                                        ): ?>

                                            <div
                                                class="chart-label"
                                            >

                                                <?= e(
                                                    $label
                                                ) ?>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>


                                </div>


                            <?php else: ?>


                                <div
                                    class="chart-empty"
                                >

                                    <div>

                                        <div
                                            style="
                                                font-size: 30px;
                                                margin-bottom: 8px;
                                            "
                                        >

                                            📊

                                        </div>


                                        <strong>

                                            No sales data yet

                                        </strong>


                                        <div
                                            style="
                                                margin-top: 5px;
                                            "
                                        >

                                            Sales data will appear
                                            here once orders are placed.

                                        </div>

                                    </div>

                                </div>


                            <?php endif; ?>


                        </div>

                    </div>


                    <!-- TOP PRODUCTS -->

                    <div class="analytics-card">


                        <div
                            class="analytics-card-header"
                        >

                            <div>

                                <div
                                    class="analytics-card-title"
                                >

                                    Top Products

                                </div>


                                <div
                                    class="analytics-card-description"
                                >

                                    Best selling products

                                </div>

                            </div>

                        </div>


                        <div
                            class="analytics-card-body"
                        >


                            <?php if (
                                !empty($topProducts)
                            ): ?>


                                <div
                                    class="top-product-list"
                                >


                                    <?php

                                    $productRank = 1;

                                    foreach (
                                        $topProducts
                                        as $product
                                    ):

                                    ?>


                                        <div
                                            class="top-product-item"
                                        >


                                            <div
                                                class="top-product-number"
                                            >

                                                <?= $productRank ?>

                                            </div>


                                            <div
                                                style="
                                                    min-width: 0;
                                                "
                                            >

                                                <div
                                                    class="top-product-name"
                                                    title="<?= e(
                                                        $product[
                                                            'product_name'
                                                        ] ??
                                                        'Product'
                                                    ) ?>"
                                                >

                                                    <?= e(
                                                        $product[
                                                            'product_name'
                                                        ] ??
                                                        'Product'
                                                    ) ?>

                                                </div>


                                                <div
                                                    class="top-product-units"
                                                >

                                                    <?= formatNumber(
                                                        (int) (
                                                            $product[
                                                                'units_sold'
                                                            ] ??
                                                            0
                                                        )
                                                    ) ?>

                                                    units sold

                                                </div>

                                            </div>


                                            <div
                                                class="top-product-revenue"
                                            >

                                                <?= formatCurrency(
                                                    (float) (
                                                        $product[
                                                            'revenue'
                                                        ] ??
                                                        0
                                                    )
                                                ) ?>

                                            </div>


                                        </div>


                                    <?php

                                        $productRank++;

                                    endforeach;

                                    ?>


                                </div>


                            <?php else: ?>


                                <div
                                    style="
                                        min-height: 220px;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        text-align: center;
                                        color: #9a94a2;
                                        font-size: 12px;
                                    "
                                >

                                    <div>

                                        <div
                                            style="
                                                font-size: 30px;
                                                margin-bottom: 8px;
                                            "
                                        >

                                            🛍️

                                        </div>


                                        <strong
                                            style="
                                                color: #25212d;
                                            "
                                        >

                                            No product sales yet

                                        </strong>


                                        <div
                                            style="
                                                margin-top: 5px;
                                            "
                                        >

                                            Top selling products
                                            will appear here.

                                        </div>

                                    </div>

                                </div>


                            <?php endif; ?>


                        </div>

                    </div>


                </section>


                <!-- =================================================
                     ORDER STATUS
                     ================================================= -->

                <section
                    class="analytics-card"
                >


                    <div
                        class="analytics-card-header"
                    >

                        <div>

                            <div
                                class="analytics-card-title"
                            >

                                Order Status

                            </div>


                            <div
                                class="analytics-card-description"
                            >

                                Order distribution by status

                            </div>

                        </div>


                        <div
                            style="
                                font-size: 11px;
                                color: #9a94a2;
                            "
                        >

                            <?= formatNumber(
                                $statusTotal
                            ) ?>

                            total

                        </div>

                    </div>


                    <div
                        class="analytics-card-body"
                    >


                        <?php if (
                            !empty($statusBreakdown)
                        ): ?>


                            <div
                                class="status-list"
                            >


                                <?php foreach (
                                    $statusBreakdown
                                    as $status
                                ): ?>


                                    <?php

                                    $statusPercentage =
                                        $statusTotal > 0
                                            ? (
                                                (
                                                    $status[
                                                        'total'
                                                    ] /
                                                    $statusTotal
                                                ) *
                                                100
                                            )
                                            : 0;

                                    ?>


                                    <div
                                        class="status-row"
                                    >


                                        <div
                                            class="status-row-header"
                                        >

                                            <span
                                                class="status-name"
                                            >

                                                <?= e(
                                                    $status[
                                                        'status'
                                                    ]
                                                ) ?>

                                            </span>


                                            <span
                                                class="status-count"
                                            >

                                                <?= formatNumber(
                                                    (int) (
                                                        $status[
                                                            'total'
                                                        ]
                                                    )
                                                ) ?>

                                                (
                                                <?= number_format(
                                                    $statusPercentage,
                                                    1
                                                ) ?>%
                                                )

                                            </span>

                                        </div>


                                        <div
                                            class="status-progress"
                                        >

                                            <div
                                                class="status-progress-bar"
                                                style="width:
                                                        <?= number_format($statusPercentage,2, '.','') ?>%;">
                                        </div>

                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                        <?php else: ?>


                            <div
                                style="
                                    text-align: center;
                                    padding: 30px;
                                    color: #9a94a2;
                                    font-size: 12px;
                                "
                            >

                                <div
                                    style="
                                        font-size: 28px;
                                        margin-bottom: 8px;
                                    "
                                >

                                    📦

                                </div>


                                No order status data available yet.

                            </div>


                        <?php endif; ?>


                    </div>


                </section>


                <!-- =================================================
                     STORE INSIGHTS
                     ================================================= -->

                <section
                    class="analytics-insight-grid"
                >


                    <!-- CUSTOMERS -->

                    <div
                        class="analytics-insight"
                    >

                        <div
                            class="analytics-insight-icon"
                        >

                            👥

                        </div>


                        <div
                            class="analytics-insight-title"
                        >

                            Registered Customers

                        </div>


                        <div
                            class="analytics-insight-value"
                        >

                            <?= formatNumber(
                                $totalCustomers
                            ) ?>

                        </div>

                    </div>


                    <!-- UNITS -->

                    <div
                        class="analytics-insight"
                    >

                        <div
                            class="analytics-insight-icon"
                        >

                            📦

                        </div>


                        <div
                            class="analytics-insight-title"
                        >

                            Units Sold

                        </div>


                        <div
                            class="analytics-insight-value"
                        >

                            <?= formatNumber(
                                $totalUnitsSold
                            ) ?>

                        </div>

                    </div>


                    <!-- AOV -->

                    <div
                        class="analytics-insight"
                    >

                        <div
                            class="analytics-insight-icon"
                        >

                            💰

                        </div>


                        <div
                            class="analytics-insight-title"
                        >

                            Average Order Value

                        </div>


                        <div
                            class="analytics-insight-value"
                        >

                            <?= formatCurrency(
                                $averageOrderValue
                            ) ?>

                        </div>

                    </div>


                </section>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     ADMIN JAVASCRIPT
     ===================================================== -->

<script
    src="../assets/js/admin.js"
></script>


</body>

</html>