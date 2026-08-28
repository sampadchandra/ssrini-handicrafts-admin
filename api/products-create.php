<?php

/**
 * Ssrini Handicrafts
 * Create Product API
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Read Form Data
|--------------------------------------------------------------------------
*/

$productCode = trim($_POST['product_code'] ?? '');
$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

$price = $_POST['price'] ?? '';
$discountPrice = $_POST['discount_price'] ?? '';

$stockQuantity = $_POST['stock_quantity'] ?? '';

$status = $_POST['status'] ?? 'active';


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$errors = [];


if ($productCode === '') {

    $errors['product_code'] =
        'Product code is required.';
}


if ($name === '') {

    $errors['name'] =
        'Product name is required.';
}


if ($categoryId <= 0) {

    $errors['category_id'] =
        'Please select a category.';
}


if ($price === '' || !is_numeric($price)) {

    $errors['price'] =
        'Please enter a valid price.';
}


if (
    $discountPrice !== '' &&
    !is_numeric($discountPrice)
) {

    $errors['discount_price'] =
        'Please enter a valid discount price.';
}


if (
    $discountPrice !== '' &&
    is_numeric($price) &&
    (float) $discountPrice >= (float) $price
) {

    $errors['discount_price'] =
        'Discount price must be lower than the regular price.';
}


if (
    $stockQuantity === '' ||
    !filter_var(
        $stockQuantity,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 0
            ]
        ]
    )
) {

    $errors['stock_quantity'] =
        'Stock quantity must be 0 or greater.';
}


if (
    !in_array(
        $status,
        ['active', 'inactive'],
        true
    )
) {

    $errors['status'] =
        'Invalid product status.';
}


/*
|--------------------------------------------------------------------------
| Return Validation Errors
|--------------------------------------------------------------------------
*/

if (!empty($errors)) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' => 'Please correct the form errors.',
        'errors' => $errors
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Normalize Values
|--------------------------------------------------------------------------
*/

$price = (float) $price;

$discountPrice =
    $discountPrice === ''
        ? null
        : (float) $discountPrice;

$stockQuantity = (int) $stockQuantity;


/*
|--------------------------------------------------------------------------
| Generate Slug
|--------------------------------------------------------------------------
*/

$slug = strtolower(
    trim(
        preg_replace(
            '/[^A-Za-z0-9-]+/',
            '-',
            $name
        ),
        '-'
    )
);


/*
|--------------------------------------------------------------------------
| Make Slug Unique
|--------------------------------------------------------------------------
*/

$baseSlug = $slug;

$counter = 1;

while (true) {

    $slugCheck = $pdo->prepare(
        "
        SELECT id
        FROM products
        WHERE slug = :slug
        LIMIT 1
        "
    );

    $slugCheck->execute([
        ':slug' => $slug
    ]);

    if (!$slugCheck->fetch()) {
        break;
    }

    $slug =
        $baseSlug . '-' . $counter;

    $counter++;
}


/*
|--------------------------------------------------------------------------
| Check Product Code
|--------------------------------------------------------------------------
*/

$codeCheck = $pdo->prepare(
    "
    SELECT id
    FROM products
    WHERE product_code = :product_code
    LIMIT 1
    "
);

$codeCheck->execute([
    ':product_code' => $productCode
]);

if ($codeCheck->fetch()) {

    http_response_code(409);

    echo json_encode([
        'success' => false,
        'message' =>
            'A product with this product code already exists.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Category
|--------------------------------------------------------------------------
*/

$categoryCheck = $pdo->prepare(
    "
    SELECT id
    FROM categories
    WHERE id = :category_id
      AND status = 'active'
    LIMIT 1
    "
);

$categoryCheck->execute([
    ':category_id' => $categoryId
]);

if (!$categoryCheck->fetch()) {

    http_response_code(422);

    echo json_encode([
        'success' => false,
        'message' =>
            'Selected category does not exist or is inactive.'
    ]);

    exit;
}


/**-----------------------------------------------------------------------
 * IMAGE UPLOAD
 *----------------------------------------------------------------------- */

$imageName = null;

$uploadedImagePath = null;

if(
    isset($_FILES['image']) &&
    $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
){
    $image = $_FILES['image'];

    /**
     * CHECK UPLOAD ERROR
     */


        if($image['error'] !== UPLOAD_ERR_OK){

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Image upload failed.'
            ]);
            exit;
        }


        /**
         * MAXIMUM FILE SIZE
         * 
         * 5MB
         * 
         */


    }

    









/*
|--------------------------------------------------------------------------
| Insert Product
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare(
        "
        INSERT INTO products (
            category_id,
            product_code,
            name,
            slug,
            description,
            price,
            discount_price,
            stock_quantity,
            status
        )

        VALUES (
            :category_id,
            :product_code,
            :name,
            :slug,
            :description,
            :price,
            :discount_price,
            :stock_quantity,
            :status
        )
        "
    );


    $stmt->execute([
        ':category_id' =>
            $categoryId,

        ':product_code' =>
            $productCode,

        ':name' =>
            $name,

        ':slug' =>
            $slug,

        ':description' =>
            $description !== ''
                ? $description
                : null,

        ':price' =>
            $price,

        ':discount_price' =>
            $discountPrice,

        ':stock_quantity' =>
            $stockQuantity,

        ':status' =>
            $status
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get New Product ID
    |--------------------------------------------------------------------------
    */

    $productId =
        (int) $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,

        'message' =>
            'Product created successfully.',

        'product_id' =>
            $productId
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,

        'message' =>
            'Unable to create product.'
    ]);

}