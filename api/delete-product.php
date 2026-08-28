<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST ALLOWED
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = $_POST['id'] ?? '';


if ($productId === '' || !is_numeric($productId)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required.'
    ]);

    exit;
}


$productId = (int) $productId;


/*
|--------------------------------------------------------------------------
| VALID PRODUCT ID
|--------------------------------------------------------------------------
*/

if ($productId <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | FIND PRODUCT
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "SELECT id, name, image
         FROM products
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([
        $productId
    ]);

    $product = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | PRODUCT NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$product) {

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Product not found.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $pdo->prepare(
        "DELETE FROM products
         WHERE id = ?"
    );

    $deleteStmt->execute([
        $productId
    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK DELETE
    |--------------------------------------------------------------------------
    */

    if ($deleteStmt->rowCount() === 0) {

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Unable to delete product.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE PRODUCT IMAGE
    |--------------------------------------------------------------------------
    */

    if (
        !empty($product['image'])
    ) {

        $imagePath =
            __DIR__ .
            '/../assets/uploads/' .
            basename($product['image']);


        if (
            is_file($imagePath)
        ) {

            unlink($imagePath);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Product deleted successfully.'
    ]);

    exit;


} catch (PDOException $e) {

    error_log(
        'Delete Product Error: ' .
        $e->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database error while deleting product.'
    ]);

    exit;
}