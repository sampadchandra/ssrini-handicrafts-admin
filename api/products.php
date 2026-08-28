<?php

/**
 * Ssrini Handcrafts
 * Product Data API
 *
 * Handles product listing, search,
 * category filtering and sorting.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=UTF-8');


try {

    /*
    |--------------------------------------------------------------------------
    | GET PARAMETERS
    |--------------------------------------------------------------------------
    */

    $search = trim($_GET['search'] ?? '');

    $categoryId = isset($_GET['category_id'])
        ? (int) $_GET['category_id']
        : 0;

    $stockFilter = $_GET['stock'] ?? '';

    $sort = $_GET['sort'] ?? 'newest';


    /*
    |--------------------------------------------------------------------------
    | BASE QUERY
    |--------------------------------------------------------------------------
    |
    | LEFT JOIN is used so that a product can still appear even if
    | its category record is unavailable.
    |
    */

    $sql = "
        SELECT
            p.id,
            p.category_id,
            p.product_code,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.discount_price,
            p.stock_quantity,
            p.image,
            p.status,
            p.created_at,
            p.updated_at,
            c.name AS category_name

        FROM products p

        LEFT JOIN categories c
            ON c.id = p.category_id

        WHERE 1 = 1
    ";


    /*
    |--------------------------------------------------------------------------
    | QUERY PARAMETERS
    |--------------------------------------------------------------------------
    */

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    |
    | Search by:
    | - Product name
    | - Product code
    | - Category name
    |
    */

    if ($search !== '') {

        $sql .= "
            AND (
                p.name LIKE :search
                OR p.product_code LIKE :search
                OR c.name LIKE :search
            )
        ";

        $params[':search'] = '%' . $search . '%';
    }


    /*
    |--------------------------------------------------------------------------
    | CATEGORY FILTER
    |--------------------------------------------------------------------------
    */

    if ($categoryId > 0) {

        $sql .= "
            AND p.category_id = :category_id
        ";

        $params[':category_id'] = $categoryId;
    }


    /*
    |--------------------------------------------------------------------------
    | STOCK FILTER
    |--------------------------------------------------------------------------
    |
    | low      = 1 to 5
    | out      = 0
    | in_stock = greater than 5
    |
    */

    if ($stockFilter === 'out') {

        $sql .= "
            AND p.stock_quantity = 0
        ";

    } elseif ($stockFilter === 'low') {

        $sql .= "
            AND p.stock_quantity BETWEEN 1 AND 5
        ";

    } elseif ($stockFilter === 'in_stock') {

        $sql .= "
            AND p.stock_quantity > 5
        ";
    }


    /*
    |--------------------------------------------------------------------------
    | SORTING
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | Sort values are controlled by PHP.
    | We never directly put user input into ORDER BY.
    |
    */

    switch ($sort) {

        case 'oldest':

            $sql .= "
                ORDER BY p.created_at ASC
            ";

            break;


        case 'price_low':

            $sql .= "
                ORDER BY p.price ASC
            ";

            break;


        case 'price_high':

            $sql .= "
                ORDER BY p.price DESC
            ";

            break;


        case 'name_asc':

            $sql .= "
                ORDER BY p.name ASC
            ";

            break;


        case 'name_desc':

            $sql .= "
                ORDER BY p.name DESC
            ";

            break;


        case 'stock_low':

            $sql .= "
                ORDER BY p.stock_quantity ASC
            ";

            break;


        case 'stock_high':

            $sql .= "
                ORDER BY p.stock_quantity DESC
            ";

            break;


        case 'newest':
        default:

            $sql .= "
                ORDER BY p.created_at DESC
            ";

            break;
    }


    /*
    |--------------------------------------------------------------------------
    | EXECUTE QUERY
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare($sql);

    $stmt->execute($params);

    $products = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | FORMAT PRODUCT DATA
    |--------------------------------------------------------------------------
    */

    foreach ($products as &$product) {

        $product['id'] =
            (int) $product['id'];

        $product['category_id'] =
            (int) $product['category_id'];

        $product['price'] =
            (float) $product['price'];

        $product['discount_price'] =
            $product['discount_price'] !== null
                ? (float) $product['discount_price']
                : null;

        $product['stock_quantity'] =
            (int) $product['stock_quantity'];

    }

    unset($product);


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            'success' => true,

            'count' => count($products),

            'data' => $products
        ],

        JSON_UNESCAPED_UNICODE
    );


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | ERROR RESPONSE
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,

            'message' =>
                'Unable to load products.'
        ],

        JSON_UNESCAPED_UNICODE
    );
}