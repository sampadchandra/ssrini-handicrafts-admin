<?php

/**
 * Ssrini Handicrafts
 * Dashboard Data API
 *
 * Provides dashboard statistics from MySQL.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=UTF-8');

try {

    /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
    ");

    $totalOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | NEW / PENDING ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'pending'
    ");

    $pendingOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PROCESSING ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'processing'
    ");

    $processingOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | SHIPPED ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'shipped'
    ");

    $shippedOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | DELIVERED ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE order_status = 'delivered'
    ");

    $deliveredOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL PRODUCTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM products
    ");

    $totalProducts = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL CUSTOMERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM customers
    ");

    $totalCustomers = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TOTAL REVENUE
    |--------------------------------------------------------------------------
    |
    | Revenue is calculated only from delivered orders.
    |
    */

    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM orders
        WHERE order_status = 'delivered'
    ");

    $totalRevenue = (float) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | COD ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE payment_method = 'cod'
    ");

    $codOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ONLINE PAYMENT ORDERS
    |--------------------------------------------------------------------------
    |
    | Online payment is currently Coming Soon on the website,
    | but the database is prepared for future integration.
    |
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE payment_method = 'online'
    ");

    $onlineOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PENDING PAYMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE payment_status = 'pending'
    ");

    $pendingPayments = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TODAY'S ORDERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM orders
        WHERE DATE(created_at) = CURDATE()
    ");

    $todayOrders = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | TODAY'S REVENUE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0)
        FROM orders
        WHERE order_status = 'delivered'
        AND DATE(created_at) = CURDATE()
    ");

    $todayRevenue = (float) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | FINAL RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            'success' => true,

            'data' => [

                'orders' => [
                    'total' => $totalOrders,
                    'pending' => $pendingOrders,
                    'processing' => $processingOrders,
                    'shipped' => $shippedOrders,
                    'delivered' => $deliveredOrders,
                    'today' => $todayOrders
                ],

                'products' => [
                    'total' => $totalProducts
                ],

                'customers' => [
                    'total' => $totalCustomers
                ],

                'revenue' => [
                    'total' => $totalRevenue,
                    'today' => $todayRevenue
                ],

                'payments' => [
                    'cod' => $codOrders,
                    'online' => $onlineOrders,
                    'pending' => $pendingPayments
                ]

            ]
        ],
        JSON_UNESCAPED_UNICODE
    );


} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' => 'Unable to load dashboard data.'
        ],
        JSON_UNESCAPED_UNICODE
    );

}