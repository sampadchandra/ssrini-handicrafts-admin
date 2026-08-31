<?php

/**
 * ============================================================================
 * SSRINI HANDICRAFTS - REVIEWS MANAGEMENT
 * ============================================================================
 *
 * File:
 *      admin/reviews.php
 *
 * Features:
 *      - Admin authentication
 *      - Review listing
 *      - Search
 *      - Status filtering
 *      - Rating filtering
 *      - Sorting
 *      - Pagination
 *      - View review details
 *      - Approve review
 *      - Reject review
 *      - Delete review
 *      - CSRF protection
 *      - Responsive design
 *
 * Existing project structure:
 *      config/database.php
 *      includes/auth.php
 *      admin/reviews.php
 *
 * ============================================================================
 */


/*
|--------------------------------------------------------------------------
| DATABASE + AUTH
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();


$pageTitle = 'Reviews';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['reviews_csrf_token'])) {

    $_SESSION['reviews_csrf_token'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['reviews_csrf_token'];


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION CHECK
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !($pdo instanceof PDO)) {

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE NAME
|--------------------------------------------------------------------------
*/

try {

    $databaseName =
        $pdo->query('SELECT DATABASE()')
            ->fetchColumn();

} catch (Throwable $e) {

    $databaseName = null;
}


/*
|--------------------------------------------------------------------------
| GET TABLE COLUMNS
|--------------------------------------------------------------------------
|
| We detect the actual reviews table structure.
|
*/

$reviewColumns = [];

try {

    $columnStmt = $pdo->query(
        "SHOW COLUMNS FROM `reviews`"
    );

    $columnRows =
        $columnStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columnRows as $column) {

        $reviewColumns[
            strtolower($column['Field'])
        ] = $column;
    }

} catch (Throwable $e) {

    $reviewColumns = [];
}


/*
|--------------------------------------------------------------------------
| TABLE EXISTS CHECK
|--------------------------------------------------------------------------
*/

$tableExists =
    !empty($reviewColumns);


/*
|--------------------------------------------------------------------------
| COLUMN DETECTION HELPER
|--------------------------------------------------------------------------
*/

function firstExistingColumn(
    array $columns,
    array $possible
) {

    foreach ($possible as $column) {

        if (
            isset(
                $columns[
                    strtolower($column)
                ]
            )
        ) {
            return $column;
        }
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| DETECT REVIEW COLUMNS
|--------------------------------------------------------------------------
*/

$reviewIdColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'id',
            'review_id'
        ]
    );


$ratingColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'rating',
            'stars',
            'review_rating'
        ]
    );


$commentColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'comment',
            'review',
            'review_text',
            'review_content',
            'message',
            'description',
            'content'
        ]
    );


$statusColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'status',
            'review_status',
            'approval_status'
        ]
    );


$productIdColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'product_id',
            'product',
            'productId'
        ]
    );


$customerIdColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'customer_id',
            'user_id',
            'customerId',
            'userId'
        ]
    );


$customerNameColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'customer_name',
            'user_name',
            'name',
            'reviewer_name'
        ]
    );


$customerEmailColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'customer_email',
            'user_email',
            'email'
        ]
    );


$createdAtColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'created_at',
            'reviewed_at',
            'date',
            'review_date',
            'created'
        ]
    );


$updatedAtColumn =
    firstExistingColumn(
        $reviewColumns,
        [
            'updated_at',
            'modified_at'
        ]
    );


/*
|--------------------------------------------------------------------------
| DETECT PRODUCTS TABLE
|--------------------------------------------------------------------------
*/

$productColumns = [];

try {

    $productColumnStmt =
        $pdo->query(
            "SHOW COLUMNS FROM `products`"
        );

    $productColumnRows =
        $productColumnStmt
            ->fetchAll(PDO::FETCH_ASSOC);

    foreach (
        $productColumnRows
        as $column
    ) {

        $productColumns[
            strtolower($column['Field'])
        ] = $column;
    }

} catch (Throwable $e) {

    $productColumns = [];
}


$productTableExists =
    !empty($productColumns);


$productIdLookup =
    firstExistingColumn(
        $productColumns,
        [
            'id',
            'product_id'
        ]
    );


$productNameLookup =
    firstExistingColumn(
        $productColumns,
        [
            'name',
            'product_name',
            'title'
        ]
    );


$productImageLookup =
    firstExistingColumn(
        $productColumns,
        [
            'image',
            'product_image',
            'thumbnail'
        ]
    );


/*
|--------------------------------------------------------------------------
| DETECT CUSTOMERS TABLE
|--------------------------------------------------------------------------
*/

$customerColumns = [];

try {

    $customerColumnStmt =
        $pdo->query(
            "SHOW COLUMNS FROM `customers`"
        );

    $customerColumnRows =
        $customerColumnStmt
            ->fetchAll(PDO::FETCH_ASSOC);

    foreach (
        $customerColumnRows
        as $column
    ) {

        $customerColumns[
            strtolower($column['Field'])
        ] = $column;
    }

} catch (Throwable $e) {

    $customerColumns = [];
}


$customersTableExists =
    !empty($customerColumns);


$customerIdLookup =
    firstExistingColumn(
        $customerColumns,
        [
            'id',
            'customer_id',
            'user_id'
        ]
    );


$customerNameLookup =
    firstExistingColumn(
        $customerColumns,
        [
            'name',
            'full_name',
            'customer_name',
            'username'
        ]
    );


$customerEmailLookup =
    firstExistingColumn(
        $customerColumns,
        [
            'email',
            'customer_email'
        ]
    );


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$message = '';
$messageType = '';


/*
|--------------------------------------------------------------------------
| POST ACTIONS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /*
    |--------------------------------------------------------------------------
    | CHECK CSRF
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['reviews_csrf_token'],
            $submittedToken
        )
    ) {

        $message =
            'Security validation failed. Please try again.';

        $messageType = 'error';

    } else {

        $action =
            $_POST['action'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | APPROVE / REJECT
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $action,
                [
                    'approve',
                    'reject'
                ],
                true
            )
        ) {

            $reviewId =
                filter_var(
                    $_POST['review_id'] ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                !$reviewId ||
                !$reviewIdColumn
            ) {

                $message =
                    'Invalid review selected.';

                $messageType = 'error';

            } elseif (!$statusColumn) {

                $message =
                    'This reviews table does not contain a status column.';

                $messageType = 'error';

            } else {

                /*
                |--------------------------------------------------------------------------
                | DETECT ENUM STATUS VALUES
                |--------------------------------------------------------------------------
                */

                $statusMeta =
                    $reviewColumns[
                        strtolower(
                            $statusColumn
                        )
                    ] ?? null;

                $statusType =
                    $statusMeta['Type']
                    ?? 'varchar(50)';


                $enumValues = [];

                if (
                    stripos(
                        $statusType,
                        'enum('
                    ) === 0
                ) {

                    preg_match_all(
                        "/'([^']*)'/",
                        $statusType,
                        $matches
                    );

                    $enumValues =
                        $matches[1] ?? [];
                }


                /*
                |--------------------------------------------------------------------------
                | CHOOSE STATUS
                |--------------------------------------------------------------------------
                */

                if ($action === 'approve') {

                    $possibleApproved =
                        [
                            'approved',
                            'active',
                            'published',
                            'visible'
                        ];

                } else {

                    $possibleApproved =
                        [
                            'rejected',
                            'inactive',
                            'hidden'
                        ];
                }


                $newStatus = null;

                if (!empty($enumValues)) {

                    foreach (
                        $possibleApproved
                        as $candidate
                    ) {

                        if (
                            in_array(
                                $candidate,
                                $enumValues,
                                true
                            )
                        ) {

                            $newStatus =
                                $candidate;

                            break;
                        }
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | VARCHAR DEFAULT
                    |--------------------------------------------------------------------------
                    */

                    $newStatus =
                        $action === 'approve'
                            ? 'approved'
                            : 'rejected';
                }


                if ($newStatus === null) {

                    $message =
                        'The review status values in the database are not supported.';

                    $messageType = 'error';

                } else {

                    try {

                        $sql =
                            "UPDATE `reviews`
                             SET `"
                            . $statusColumn .
                            "` = :status";

                        if ($updatedAtColumn) {

                            $sql .=
                                ",
                                `"
                                . $updatedAtColumn .
                                "` = NOW()";
                        }

                        $sql .=
                            "
                             WHERE `"
                            . $reviewIdColumn .
                            "` = :id
                             LIMIT 1";


                        $stmt =
                            $pdo->prepare($sql);

                        $stmt->execute(
                            [
                                ':status' =>
                                    $newStatus,

                                ':id' =>
                                    $reviewId
                            ]
                        );


                        if (
                            $stmt->rowCount() >= 0
                        ) {

                            $message =
                                $action === 'approve'
                                    ? 'Review approved successfully.'
                                    : 'Review rejected successfully.';

                            $messageType =
                                'success';
                        }

                    } catch (Throwable $e) {

                        $message =
                            'Unable to update review status.';

                        $messageType =
                            'error';
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE REVIEW
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'delete'
        ) {

            $reviewId =
                filter_var(
                    $_POST['review_id'] ?? null,
                    FILTER_VALIDATE_INT
                );


            if (
                !$reviewId ||
                !$reviewIdColumn
            ) {

                $message =
                    'Invalid review selected.';

                $messageType =
                    'error';

            } else {

                try {

                    $stmt =
                        $pdo->prepare(
                            "DELETE FROM `reviews`
                             WHERE `"
                            . $reviewIdColumn .
                            "` = :id
                             LIMIT 1"
                        );

                    $stmt->execute(
                        [
                            ':id' =>
                                $reviewId
                        ]
                    );


                    if (
                        $stmt->rowCount() > 0
                    ) {

                        $message =
                            'Review deleted successfully.';

                        $messageType =
                            'success';

                    } else {

                        $message =
                            'Review was not found.';

                        $messageType =
                            'error';
                    }

                } catch (Throwable $e) {

                    $message =
                        'Unable to delete review.';

                    $messageType =
                        'error';
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search'] ?? ''
    );


$statusFilter =
    trim(
        $_GET['status'] ?? ''
    );


$ratingFilter =
    trim(
        $_GET['rating'] ?? ''
    );


$sort =
    $_GET['sort'] ?? 'newest';


$page =
    max(
        1,
        (int) (
            $_GET['page'] ?? 1
        )
    );


$perPage = 10;


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$reviews = [];

$totalReviews = 0;

$approvedCount = 0;

$pendingCount = 0;

$rejectedCount = 0;

$averageRating = 0;


if ($tableExists && $reviewIdColumn) {

    /*
    |--------------------------------------------------------------------------
    | BASE SELECT
    |--------------------------------------------------------------------------
    */

    $selectParts = [];


    $selectParts[] =
        "r.`"
        . $reviewIdColumn .
        "` AS review_id";


    /*
    |--------------------------------------------------------------------------
    | RATING
    |--------------------------------------------------------------------------
    */

    if ($ratingColumn) {

        $selectParts[] =
            "r.`"
            . $ratingColumn .
            "` AS review_rating";

    } else {

        $selectParts[] =
            "NULL AS review_rating";
    }


    /*
    |--------------------------------------------------------------------------
    | COMMENT
    |--------------------------------------------------------------------------
    */

    if ($commentColumn) {

        $selectParts[] =
            "r.`"
            . $commentColumn .
            "` AS review_comment";

    } else {

        $selectParts[] =
            "NULL AS review_comment";
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    if ($statusColumn) {

        $selectParts[] =
            "r.`"
            . $statusColumn .
            "` AS review_status";

    } else {

        $selectParts[] =
            "'approved' AS review_status";
    }


    /*
    |--------------------------------------------------------------------------
    | CREATED AT
    |--------------------------------------------------------------------------
    */

    if ($createdAtColumn) {

        $selectParts[] =
            "r.`"
            . $createdAtColumn .
            "` AS review_created_at";

    } else {

        $selectParts[] =
            "NULL AS review_created_at";
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATED AT
    |--------------------------------------------------------------------------
    */

    if ($updatedAtColumn) {

        $selectParts[] =
            "r.`"
            . $updatedAtColumn .
            "` AS review_updated_at";

    } else {

        $selectParts[] =
            "NULL AS review_updated_at";
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER NAME DIRECTLY FROM REVIEW
    |--------------------------------------------------------------------------
    */

    if ($customerNameColumn) {

        $selectParts[] =
            "r.`"
            . $customerNameColumn .
            "` AS review_customer_name";

    } else {

        $selectParts[] =
            "NULL AS review_customer_name";
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER EMAIL DIRECTLY FROM REVIEW
    |--------------------------------------------------------------------------
    */

    if ($customerEmailColumn) {

        $selectParts[] =
            "r.`"
            . $customerEmailColumn .
            "` AS review_customer_email";

    } else {

        $selectParts[] =
            "NULL AS review_customer_email";
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT NAME
    |--------------------------------------------------------------------------
    */

    if (
        $productIdColumn &&
        $productTableExists &&
        $productIdLookup &&
        $productNameLookup
    ) {

        $selectParts[] =
            "p.`"
            . $productNameLookup .
            "` AS product_name";

    } else {

        $selectParts[] =
            "NULL AS product_name";
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT IMAGE
    |--------------------------------------------------------------------------
    */

    if (
        $productIdColumn &&
        $productTableExists &&
        $productIdLookup &&
        $productImageLookup
    ) {

        $selectParts[] =
            "p.`"
            . $productImageLookup .
            "` AS product_image";

    } else {

        $selectParts[] =
            "NULL AS product_image";
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER JOIN NAME
    |--------------------------------------------------------------------------
    */

    if (
        $customerIdColumn &&
        $customersTableExists &&
        $customerIdLookup &&
        $customerNameLookup
    ) {

        $selectParts[] =
            "c.`"
            . $customerNameLookup .
            "` AS joined_customer_name";

    } else {

        $selectParts[] =
            "NULL AS joined_customer_name";
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER JOIN EMAIL
    |--------------------------------------------------------------------------
    */

    if (
        $customerIdColumn &&
        $customersTableExists &&
        $customerIdLookup &&
        $customerEmailLookup
    ) {

        $selectParts[] =
            "c.`"
            . $customerEmailLookup .
            "` AS joined_customer_email";

    } else {

        $selectParts[] =
            "NULL AS joined_customer_email";
    }


    /*
    |--------------------------------------------------------------------------
    | FROM
    |--------------------------------------------------------------------------
    */

    $fromSql =
        " FROM `reviews` r";


    /*
    |--------------------------------------------------------------------------
    | PRODUCT JOIN
    |--------------------------------------------------------------------------
    */

    if (
        $productIdColumn &&
        $productTableExists &&
        $productIdLookup
    ) {

        $fromSql .=
            " LEFT JOIN `products` p
              ON p.`"
            . $productIdLookup .
            "` = r.`"
            . $productIdColumn .
            "`";
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOMER JOIN
    |--------------------------------------------------------------------------
    */

    if (
        $customerIdColumn &&
        $customersTableExists &&
        $customerIdLookup
    ) {

        $fromSql .=
            " LEFT JOIN `customers` c
              ON c.`"
            . $customerIdLookup .
            "` = r.`"
            . $customerIdColumn .
            "`";
    }


    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    $where = [];

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $searchParts = [];


        if ($commentColumn) {

            $searchParts[] =
                "r.`"
                . $commentColumn .
                "` LIKE :search";
        }


        if ($customerNameColumn) {

            $searchParts[] =
                "r.`"
                . $customerNameColumn .
                "` LIKE :search";
        }


        if ($customerEmailColumn) {

            $searchParts[] =
                "r.`"
                . $customerEmailColumn .
                "` LIKE :search";
        }


        if (
            $customerIdColumn &&
            $customersTableExists &&
            $customerNameLookup
        ) {

            $searchParts[] =
                "c.`"
                . $customerNameLookup .
                "` LIKE :search";
        }


        if (
            $customerIdColumn &&
            $customersTableExists &&
            $customerEmailLookup
        ) {

            $searchParts[] =
                "c.`"
                . $customerEmailLookup .
                "` LIKE :search";
        }


        if (
            $productIdColumn &&
            $productTableExists &&
            $productNameLookup
        ) {

            $searchParts[] =
                "p.`"
                . $productNameLookup .
                "` LIKE :search";
        }


        if (!empty($searchParts)) {

            $where[] =
                '(' .
                implode(
                    ' OR ',
                    $searchParts
                ) .
                ')';

            $params[':search'] =
                '%' . $search . '%';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $statusFilter !== '' &&
        $statusColumn
    ) {

        $where[] =
            "r.`"
            . $statusColumn .
            "` = :status_filter";

        $params[':status_filter'] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | RATING FILTER
    |--------------------------------------------------------------------------
    */

    if (
        $ratingFilter !== '' &&
        $ratingColumn &&
        in_array(
            $ratingFilter,
            [
                '1',
                '2',
                '3',
                '4',
                '5'
            ],
            true
        )
    ) {

        $where[] =
            "r.`"
            . $ratingColumn .
            "` = :rating_filter";

        $params[':rating_filter'] =
            (int) $ratingFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | WHERE SQL
    |--------------------------------------------------------------------------
    */

    $whereSql = '';

    if (!empty($where)) {

        $whereSql =
            ' WHERE ' .
            implode(
                ' AND ',
                $where
            );
    }


    /*
    |--------------------------------------------------------------------------
    | COUNT
    |--------------------------------------------------------------------------
    */

    try {

        $countSql =
            "SELECT COUNT(*)
             FROM `reviews` r"
            . (
                (
                    $productIdColumn &&
                    $productTableExists &&
                    $productIdLookup
                )
                    ? " LEFT JOIN `products` p
                        ON p.`"
                        . $productIdLookup .
                        "` = r.`"
                        . $productIdColumn .
                        "`"
                    : ''
            )
            . (
                (
                    $customerIdColumn &&
                    $customersTableExists &&
                    $customerIdLookup
                )
                    ? " LEFT JOIN `customers` c
                        ON c.`"
                        . $customerIdLookup .
                        "` = r.`"
                        . $customerIdColumn .
                        "`"
                    : ''
            )
            . $whereSql;


        $countStmt =
            $pdo->prepare(
                $countSql
            );

        $countStmt->execute(
            $params
        );

        $totalReviews =
            (int) $countStmt->fetchColumn();

    } catch (Throwable $e) {

        $totalReviews = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    try {

        /*
        |----------------------------------------------------------------------
        | TOTAL
        |----------------------------------------------------------------------
        */

        $totalReviews =
            (int) $pdo
                ->query(
                    "SELECT COUNT(*)
                     FROM `reviews`"
                )
                ->fetchColumn();


        /*
        |----------------------------------------------------------------------
        | AVERAGE RATING
        |----------------------------------------------------------------------
        */

        if ($ratingColumn) {

            $averageRating =
                (float) $pdo
                    ->query(
                        "SELECT AVG(`"
                        . $ratingColumn .
                        "`)
                         FROM `reviews`"
                    )
                    ->fetchColumn();
        }


        /*
        |----------------------------------------------------------------------
        | STATUS COUNTS
        |----------------------------------------------------------------------
        */

        if ($statusColumn) {

            /*
            |------------------------------------------------------------------
            | Approved-like
            |------------------------------------------------------------------
            */

            $approvedStatuses =
                [
                    'approved',
                    'active',
                    'published',
                    'visible'
                ];


            $pendingStatuses =
                [
                    'pending',
                    'waiting',
                    'review'
                ];


            $rejectedStatuses =
                [
                    'rejected',
                    'inactive',
                    'hidden'
                ];


            $approvedPlaceholders =
                [];

            $pendingPlaceholders =
                [];

            $rejectedPlaceholders =
                [];

            $statusParams = [];


            foreach (
                $approvedStatuses
                as $index => $status
            ) {

                $placeholder =
                    ':approved_' . $index;

                $approvedPlaceholders[] =
                    $placeholder;

                $statusParams[
                    $placeholder
                ] = $status;
            }


            foreach (
                $pendingStatuses
                as $index => $status
            ) {

                $placeholder =
                    ':pending_' . $index;

                $pendingPlaceholders[] =
                    $placeholder;

                $statusParams[
                    $placeholder
                ] = $status;
            }


            foreach (
                $rejectedStatuses
                as $index => $status
            ) {

                $placeholder =
                    ':rejected_' . $index;

                $rejectedPlaceholders[] =
                    $placeholder;

                $statusParams[
                    $placeholder
                ] = $status;
            }


            $statsSql =
                "SELECT
                    SUM(
                        CASE
                            WHEN `"
                        . $statusColumn .
                        "` IN ("
                        . implode(
                            ',',
                            $approvedPlaceholders
                        )
                        . ")
                            THEN 1
                            ELSE 0
                        END
                    ) AS approved_count,

                    SUM(
                        CASE
                            WHEN `"
                        . $statusColumn .
                        "` IN ("
                        . implode(
                            ',',
                            $pendingPlaceholders
                        )
                        . ")
                            THEN 1
                            ELSE 0
                        END
                    ) AS pending_count,

                    SUM(
                        CASE
                            WHEN `"
                        . $statusColumn .
                        "` IN ("
                        . implode(
                            ',',
                            $rejectedPlaceholders
                        )
                        . ")
                            THEN 1
                            ELSE 0
                        END
                    ) AS rejected_count

                 FROM `reviews`";


            $statsStmt =
                $pdo->prepare(
                    $statsSql
                );

            $statsStmt->execute(
                $statusParams
            );

            $stats =
                $statsStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            $approvedCount =
                (int) (
                    $stats['approved_count']
                    ?? 0
                );


            $pendingCount =
                (int) (
                    $stats['pending_count']
                    ?? 0
                );


            $rejectedCount =
                (int) (
                    $stats['rejected_count']
                    ?? 0
                );
        }

    } catch (Throwable $e) {

        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        $averageRating = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | SORTING
    |--------------------------------------------------------------------------
    */

    switch ($sort) {

        case 'oldest':

            if ($createdAtColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $createdAtColumn .
                    "` ASC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` ASC";
            }

            break;


        case 'rating_high':

            if ($ratingColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $ratingColumn .
                    "` DESC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` DESC";
            }

            break;


        case 'rating_low':

            if ($ratingColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $ratingColumn .
                    "` ASC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` ASC";
            }

            break;


        case 'name_asc':

            if ($customerNameColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $customerNameColumn .
                    "` ASC";

            } elseif (
                $customersTableExists &&
                $customerNameLookup &&
                $customerIdColumn
            ) {

                $orderSql =
                    " ORDER BY c.`"
                    . $customerNameLookup .
                    "` ASC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` ASC";
            }

            break;


        case 'name_desc':

            if ($customerNameColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $customerNameColumn .
                    "` DESC";

            } elseif (
                $customersTableExists &&
                $customerNameLookup &&
                $customerIdColumn
            ) {

                $orderSql =
                    " ORDER BY c.`"
                    . $customerNameLookup .
                    "` DESC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` DESC";
            }

            break;


        case 'newest':

        default:

            if ($createdAtColumn) {

                $orderSql =
                    " ORDER BY r.`"
                    . $createdAtColumn .
                    "` DESC";

            } else {

                $orderSql =
                    " ORDER BY r.`"
                    . $reviewIdColumn .
                    "` DESC";
            }

            break;
    }


    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */

    $totalPages =
        max(
            1,
            (int) ceil(
                $totalReviews / $perPage
            )
        );


    if ($page > $totalPages) {

        $page =
            $totalPages;
    }


    $offset =
        ($page - 1) * $perPage;


    /*
    |--------------------------------------------------------------------------
    | FINAL QUERY
    |--------------------------------------------------------------------------
    */

    try {

        $sql =
            "SELECT "
            . implode(
                ", ",
                $selectParts
            )
            . $fromSql
            . $whereSql
            . $orderSql
            . " LIMIT "
            . (int) $perPage
            . " OFFSET "
            . (int) $offset;


        $stmt =
            $pdo->prepare($sql);

        $stmt->execute(
            $params
        );


        $reviews =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (Throwable $e) {

        $reviews = [];

        if ($message === '') {

            $message =
                'Unable to load reviews.';

            $messageType =
                'error';
        }
    }
}


/*
|--------------------------------------------------------------------------
| HELPER: DISPLAY CUSTOMER
|--------------------------------------------------------------------------
*/

function getCustomerName($review)
{
    if (
        !empty(
            $review['review_customer_name']
        )
    ) {

        return $review[
            'review_customer_name'
        ];
    }


    if (
        !empty(
            $review['joined_customer_name']
        )
    ) {

        return $review[
            'joined_customer_name'
        ];
    }


    return 'Guest Customer';
}


/*
|--------------------------------------------------------------------------
| HELPER: DISPLAY EMAIL
|--------------------------------------------------------------------------
*/

function getCustomerEmail($review)
{
    if (
        !empty(
            $review['review_customer_email']
        )
    ) {

        return $review[
            'review_customer_email'
        ];
    }


    if (
        !empty(
            $review['joined_customer_email']
        )
    ) {

        return $review[
            'joined_customer_email'
        ];
    }


    return '';
}


/*
|--------------------------------------------------------------------------
| HELPER: STATUS CLASS
|--------------------------------------------------------------------------
*/

function statusClass($status)
{
    $status =
        strtolower(
            trim(
                (string) $status
            )
        );


    if (
        in_array(
            $status,
            [
                'approved',
                'active',
                'published',
                'visible'
            ],
            true
        )
    ) {

        return 'status-approved';
    }


    if (
        in_array(
            $status,
            [
                'pending',
                'waiting',
                'review'
            ],
            true
        )
    ) {

        return 'status-pending';
    }


    if (
        in_array(
            $status,
            [
                'rejected',
                'inactive',
                'hidden'
            ],
            true
        )
    ) {

        return 'status-rejected';
    }


    return 'status-default';
}


/*
|--------------------------------------------------------------------------
| HELPER: STATUS LABEL
|--------------------------------------------------------------------------
*/

function statusLabel($status)
{
    if (
        $status === null ||
        $status === ''
    ) {

        return 'Approved';
    }


    return ucwords(
        str_replace(
            '_',
            ' ',
            (string) $status
        )
    );
}


/*
|--------------------------------------------------------------------------
| HELPER: DATE FORMAT
|--------------------------------------------------------------------------
*/

function formatReviewDate($date)
{
    if (
        empty($date)
    ) {

        return '—';
    }


    $timestamp =
        strtotime(
            $date
        );


    if (!$timestamp) {

        return e($date);
    }


    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


/*
|--------------------------------------------------------------------------
| HELPER: BUILD QUERY URL
|--------------------------------------------------------------------------
*/

function pageUrl($pageNumber)
{
    $query =
        $_GET;

    $query['page'] =
        $pageNumber;


    return '?' .
        http_build_query(
            $query
        );
}


/*
|--------------------------------------------------------------------------
| DETERMINE AVAILABLE STATUS FILTERS
|--------------------------------------------------------------------------
*/

$availableStatuses = [];

if (
    $statusColumn &&
    $tableExists
) {

    try {

        $statusStmt =
            $pdo->query(
                "SELECT DISTINCT `"
                . $statusColumn .
                "`
                 FROM `reviews`
                 WHERE `"
                . $statusColumn .
                "` IS NOT NULL
                   AND `"
                . $statusColumn .
                "` <> ''
                 ORDER BY `"
                . $statusColumn .
                "` ASC"
            );


        $availableStatuses =
            $statusStmt->fetchAll(
                PDO::FETCH_COLUMN
            );

    } catch (Throwable $e) {

        $availableStatuses = [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Reviews | Ssrini Handicrafts
    </title>


    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!--
        Main Admin CSS
    -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | RESET
        |--------------------------------------------------------------------------
        */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {
            font-family:
                "Inter",
                Arial,
                sans-serif;

            background:
                #f7f5fb;

            color:
                #272230;

            min-height:
                100vh;
        }


        button,
        input,
        select {
            font:
                inherit;
        }


        button {
            border:
                0;
        }


        a {
            text-decoration:
                none;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGE
        |--------------------------------------------------------------------------
        */

        .reviews-page {

            min-height:
                100vh;

            padding:
                32px;

            width:
                100%;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .page-header {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            gap:
                20px;

            margin-bottom:
                28px;
        }


        .page-title h1 {

            font-size:
                30px;

            font-weight:
                800;

            letter-spacing:
                -0.5px;

            margin-bottom:
                7px;
        }


        .page-title p {

            color:
                #77717f;

            font-size:
                14px;
        }


        .refresh-btn {

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            gap:
                8px;

            min-height:
                44px;

            padding:
                0 16px;

            border-radius:
                11px;

            background:
                #ffffff;

            color:
                #5e5668;

            border:
                1px solid #e7e1ee;

            cursor:
                pointer;

            font-size:
                13px;

            font-weight:
                600;

            transition:
                all 0.2s ease;

            box-shadow:
                0 5px 18px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );
        }


        .refresh-btn:hover {

            transform:
                translateY(-2px);

            color:
                #7627c9;

            border-color:
                #d8c6e8;

            box-shadow:
                0 8px 22px
                rgba(
                    30,
                    20,
                    50,
                    0.08
                );
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .alert {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                14px 17px;

            border-radius:
                13px;

            margin-bottom:
                22px;

            font-size:
                13px;

            font-weight:
                600;
        }


        .alert-success {

            background:
                #eaf9f0;

            color:
                #177a45;

            border:
                1px solid #ccefdc;
        }


        .alert-error {

            background:
                #fff0f1;

            color:
                #c83242;

            border:
                1px solid #f7d4d8;
        }


        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                16px;

            margin-bottom:
                24px;
        }


        .stat-card {

            background:
                #ffffff;

            border:
                1px solid #eee8f3;

            border-radius:
                17px;

            padding:
                19px;

            display:
                flex;

            align-items:
                center;

            gap:
                15px;

            box-shadow:
                0 8px 25px
                rgba(
                    30,
                    20,
                    50,
                    0.055
                );

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }


        .stat-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 30px
                rgba(
                    30,
                    20,
                    50,
                    0.08
                );
        }


        .stat-icon {

            width:
                46px;

            height:
                46px;

            flex:
                0 0 46px;

            border-radius:
                13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                18px;
        }


        .stat-icon-purple {

            background:
                #f0e8fb;

            color:
                #7627c9;
        }


        .stat-icon-green {

            background:
                #e7f8ef;

            color:
                #21945a;
        }


        .stat-icon-orange {

            background:
                #fff4e5;

            color:
                #d98519;
        }


        .stat-icon-red {

            background:
                #fff0f1;

            color:
                #d43b4a;
        }


        .stat-info {

            min-width:
                0;
        }


        .stat-label {

            color:
                #837b8c;

            font-size:
                12px;

            font-weight:
                600;

            margin-bottom:
                5px;
        }


        .stat-value {

            color:
                #292331;

            font-size:
                21px;

            font-weight:
                800;
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER PANEL
        |--------------------------------------------------------------------------
        */

        .filter-panel {

            background:
                #ffffff;

            border:
                1px solid #eee8f3;

            border-radius:
                18px;

            padding:
                19px;

            margin-bottom:
                22px;

            box-shadow:
                0 8px 25px
                rgba(
                    30,
                    20,
                    50,
                    0.055
                );
        }


        .filter-row {

            display:
                grid;

            grid-template-columns:
                minmax(
                    220px,
                    2fr
                )
                1fr
                1fr
                1fr
                auto;

            gap:
                12px;

            align-items:
                center;
        }


        .search-wrapper {

            position:
                relative;
        }


        .search-wrapper i {

            position:
                absolute;

            left:
                15px;

            top:
                50%;

            transform:
                translateY(-50%);

            color:
                #9c94a4;

            font-size:
                14px;

            pointer-events:
                none;
        }


        .search-input,
        .filter-select {

            width:
                100%;

            height:
                46px;

            border:
                1px solid #e4deea;

            border-radius:
                11px;

            background:
                #ffffff;

            outline:
                none;

            color:
                #302a38;

            font-size:
                13px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .search-input {

            padding:
                0 14px 0 41px;
        }


        .filter-select {

            padding:
                0 13px;
        }


        .search-input:focus,
        .filter-select:focus {

            border-color:
                #9b48d1;

            box-shadow:
                0 0 0 3px
                rgba(
                    155,
                    72,
                    209,
                    0.10
                );
        }


        .clear-btn {

            height:
                46px;

            padding:
                0 15px;

            border-radius:
                11px;

            background:
                #f2eef5;

            color:
                #665e6e;

            cursor:
                pointer;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                all 0.2s ease;
        }


        .clear-btn:hover {

            background:
                #e9e1ed;

            transform:
                translateY(-1px);
        }


        /*
        |--------------------------------------------------------------------------
        | TABLE CARD
        |--------------------------------------------------------------------------
        */

        .table-card {

            background:
                #ffffff;

            border:
                1px solid #eee8f3;

            border-radius:
                18px;

            overflow:
                hidden;

            box-shadow:
                0 8px 25px
                rgba(
                    30,
                    20,
                    50,
                    0.055
                );
        }


        .table-header {

            padding:
                20px 22px;

            border-bottom:
                1px solid #eeeaf2;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;
        }


        .table-header h2 {

            font-size:
                17px;

            font-weight:
                750;
        }


        .result-count {

            color:
                #8a8391;

            font-size:
                12px;

            font-weight:
                600;
        }


        .table-wrapper {

            width:
                100%;

            overflow-x:
                auto;
        }


        table {

            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                1050px;
        }


        thead {

            background:
                #faf8fc;
        }


        th {

            padding:
                14px 18px;

            text-align:
                left;

            color:
                #77707f;

            font-size:
                11px;

            text-transform:
                uppercase;

            letter-spacing:
                0.45px;

            font-weight:
                750;

            white-space:
                nowrap;

            border-bottom:
                1px solid #eeeaf2;
        }


        td {

            padding:
                16px 18px;

            border-bottom:
                1px solid #f0edf3;

            vertical-align:
                middle;

            font-size:
                13px;

            color:
                #3e3746;
        }


        tbody tr {

            transition:
                background 0.2s ease;
        }


        tbody tr:hover {

            background:
                #fcfaff;
        }


        tbody tr:last-child td {

            border-bottom:
                0;
        }


        /*
        |--------------------------------------------------------------------------
        | CUSTOMER
        |--------------------------------------------------------------------------
        */

        .customer-cell {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

            min-width:
                170px;
        }


        .customer-avatar {

            width:
                38px;

            height:
                38px;

            flex:
                0 0 38px;

            border-radius:
                50%;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                linear-gradient(
                    135deg,
                    #eee2f9,
                    #f8e6f4
                );

            color:
                #7627c9;

            font-size:
                13px;

            font-weight:
                800;
        }


        .customer-details {

            min-width:
                0;
        }


        .customer-name {

            font-weight:
                700;

            color:
                #312b38;

            margin-bottom:
                3px;

            white-space:
                nowrap;

            overflow:
                hidden;

            text-overflow:
                ellipsis;

            max-width:
                180px;
        }


        .customer-email {

            color:
                #918998;

            font-size:
                11px;

            white-space:
                nowrap;

            overflow:
                hidden;

            text-overflow:
                ellipsis;

            max-width:
                180px;
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT
        |--------------------------------------------------------------------------
        */

        .product-cell {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            min-width:
                170px;
        }


        .product-thumb {

            width:
                42px;

            height:
                42px;

            border-radius:
                9px;

            flex:
                0 0 42px;

            object-fit:
                cover;

            border:
                1px solid #eee8f3;

            background:
                #f8f5fa;
        }


        .product-placeholder {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #a29aaa;

            font-size:
                14px;
        }


        .product-name {

            font-weight:
                650;

            color:
                #37313e;

            max-width:
                190px;

            white-space:
                nowrap;

            overflow:
                hidden;

            text-overflow:
                ellipsis;
        }


        /*
        |--------------------------------------------------------------------------
        | RATING
        |--------------------------------------------------------------------------
        */

        .rating-wrapper {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            white-space:
                nowrap;
        }


        .stars {

            color:
                #f2a51a;

            letter-spacing:
                1px;

            font-size:
                13px;
        }


        .rating-number {

            font-weight:
                700;

            color:
                #5b5361;

            font-size:
                12px;
        }


        /*
        |--------------------------------------------------------------------------
        | REVIEW TEXT
        |--------------------------------------------------------------------------
        */

        .review-text {

            max-width:
                260px;

            color:
                #625b69;

            line-height:
                1.55;

            display:
                -webkit-box;

            -webkit-line-clamp:
                2;

            -webkit-box-orient:
                vertical;

            overflow:
                hidden;
        }


        .no-review-text {

            color:
                #aaa2b0;

            font-style:
                italic;
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-badge {

            display:
                inline-flex;

            align-items:
                center;

            gap:
                6px;

            padding:
                6px 10px;

            border-radius:
                999px;

            font-size:
                11px;

            font-weight:
                750;

            white-space:
                nowrap;
        }


        .status-badge::before {

            content:
                "";

            width:
                6px;

            height:
                6px;

            border-radius:
                50%;

            background:
                currentColor;
        }


        .status-approved {

            color:
                #18824a;

            background:
                #e9f8f0;
        }


        .status-pending {

            color:
                #bf7411;

            background:
                #fff5e4;
        }


        .status-rejected {

            color:
                #cb3949;

            background:
                #fff0f1;
        }


        .status-default {

            color:
                #6d6575;

            background:
                #f1eef4;
        }


        /*
        |--------------------------------------------------------------------------
        | ACTION BUTTONS
        |--------------------------------------------------------------------------
        */

        .actions {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

            white-space:
                nowrap;
        }


        .action-btn {

            width:
                34px;

            height:
                34px;

            border-radius:
                9px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            cursor:
                pointer;

            transition:
                all 0.2s ease;

            font-size:
                12px;
        }


        .view-btn {

            background:
                #f0e8fb;

            color:
                #7627c9;
        }


        .view-btn:hover {

            background:
                #e5d8f6;

            transform:
                translateY(-2px);
        }


        .approve-btn {

            background:
                #e8f8ef;

            color:
                #1b8a50;
        }


        .approve-btn:hover {

            background:
                #d8f1e4;

            transform:
                translateY(-2px);
        }


        .reject-btn {

            background:
                #fff5e7;

            color:
                #c47a18;
        }


        .reject-btn:hover {

            background:
                #ffecd1;

            transform:
                translateY(-2px);
        }


        .delete-btn {

            background:
                #fff0f1;

            color:
                #d13b4a;
        }


        .delete-btn:hover {

            background:
                #ffe1e4;

            transform:
                translateY(-2px);
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-state {

            padding:
                75px 25px;

            text-align:
                center;
        }


        .empty-icon {

            width:
                64px;

            height:
                64px;

            border-radius:
                18px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            margin:
                0 auto 16px;

            background:
                #f1eafa;

            color:
                #7627c9;

            font-size:
                25px;
        }


        .empty-state h3 {

            font-size:
                18px;

            margin-bottom:
                7px;
        }


        .empty-state p {

            color:
                #8d8594;

            font-size:
                13px;
        }


        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        .pagination {

            padding:
                18px 22px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            border-top:
                1px solid #eeeaf2;
        }


        .pagination-info {

            color:
                #8a8291;

            font-size:
                12px;
        }


        .pagination-links {

            display:
                flex;

            align-items:
                center;

            gap:
                5px;
        }


        .page-link {

            min-width:
                34px;

            height:
                34px;

            padding:
                0 9px;

            border-radius:
                9px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #f4f0f7;

            color:
                #665e6d;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                all 0.2s ease;
        }


        .page-link:hover {

            background:
                #e9dff1;

            color:
                #7627c9;
        }


        .page-link.active {

            color:
                #ffffff;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            box-shadow:
                0 5px 12px
                rgba(
                    118,
                    39,
                    201,
                    0.22
                );
        }


        .page-link.disabled {

            opacity:
                0.45;

            pointer-events:
                none;
        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .modal-overlay {

            position:
                fixed;

            inset:
                0;

            z-index:
                9999;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                20px;

            background:
                rgba(
                    31,
                    22,
                    39,
                    0.48
                );

            backdrop-filter:
                blur(7px);

            opacity:
                0;

            visibility:
                hidden;

            transition:
                all 0.22s ease;
        }


        .modal-overlay.active {

            opacity:
                1;

            visibility:
                visible;
        }


        .review-modal {

            width:
                min(
                    680px,
                    100%
                );

            max-height:
                calc(
                    100vh - 40px
                );

            overflow-y:
                auto;

            background:
                #ffffff;

            border-radius:
                20px;

            box-shadow:
                0 30px 80px
                rgba(
                    20,
                    12,
                    30,
                    0.25
                );

            transform:
                translateY(12px)
                scale(0.98);

            transition:
                all 0.22s ease;
        }


        .modal-overlay.active
        .review-modal {

            transform:
                translateY(0)
                scale(1);
        }


        .modal-header {

            padding:
                20px 23px;

            border-bottom:
                1px solid #eeeaf2;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;
        }


        .modal-title {

            font-size:
                18px;

            font-weight:
                800;

            margin-bottom:
                4px;
        }


        .modal-subtitle {

            color:
                #918998;

            font-size:
                12px;
        }


        .close-modal {

            width:
                34px;

            height:
                34px;

            border-radius:
                9px;

            background:
                #f4f0f6;

            color:
                #706876;

            cursor:
                pointer;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            transition:
                all 0.2s ease;
        }


        .close-modal:hover {

            background:
                #ebe4ee;

            transform:
                rotate(4deg);
        }


        .modal-body {

            padding:
                23px;
        }


        .review-profile {

            display:
                flex;

            align-items:
                center;

            gap:
                13px;

            margin-bottom:
                21px;
        }


        .large-avatar {

            width:
                52px;

            height:
                52px;

            border-radius:
                50%;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                linear-gradient(
                    135deg,
                    #eee2f9,
                    #f8e6f4
                );

            color:
                #7627c9;

            font-weight:
                800;

            font-size:
                16px;
        }


        .profile-name {

            font-size:
                15px;

            font-weight:
                750;

            margin-bottom:
                3px;
        }


        .profile-email {

            color:
                #8f8797;

            font-size:
                12px;
        }


        .detail-grid {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                13px;

            margin-bottom:
                18px;
        }


        .detail-card {

            padding:
                14px;

            border:
                1px solid #eee9f2;

            border-radius:
                12px;

            background:
                #fcfbfd;
        }


        .detail-label {

            color:
                #928a99;

            font-size:
                10px;

            font-weight:
                750;

            text-transform:
                uppercase;

            letter-spacing:
                0.5px;

            margin-bottom:
                6px;
        }


        .detail-value {

            color:
                #393240;

            font-size:
                13px;

            font-weight:
                650;
        }


        .review-content-box {

            border:
                1px solid #eee9f2;

            border-radius:
                13px;

            padding:
                17px;

            background:
                #fcfbfd;
        }


        .review-content-title {

            color:
                #6f6877;

            font-size:
                11px;

            text-transform:
                uppercase;

            font-weight:
                750;

            letter-spacing:
                0.5px;

            margin-bottom:
                9px;
        }


        .review-content {

            color:
                #494250;

            line-height:
                1.7;

            font-size:
                13px;

            white-space:
                pre-wrap;

            word-break:
                break-word;
        }


        /*
        |--------------------------------------------------------------------------
        | MODAL FOOTER
        |--------------------------------------------------------------------------
        */

        .modal-footer {

            padding:
                16px 23px;

            border-top:
                1px solid #eeeaf2;

            display:
                flex;

            justify-content:
                flex-end;

            gap:
                9px;
        }


        .modal-btn {

            min-height:
                40px;

            padding:
                0 15px;

            border-radius:
                10px;

            cursor:
                pointer;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                all 0.2s ease;
        }


        .modal-cancel {

            background:
                #f2eef4;

            color:
                #625b69;
        }


        .modal-cancel:hover {

            background:
                #e8e2eb;
        }


        .modal-delete {

            background:
                #fff0f1;

            color:
                #d13b4a;
        }


        .modal-delete:hover {

            background:
                #ffe0e3;
        }


        /*
        |--------------------------------------------------------------------------
        | CONFIRM MODAL
        |--------------------------------------------------------------------------
        */

        .confirm-modal {

            width:
                min(
                    410px,
                    100%
                );

            background:
                #ffffff;

            border-radius:
                18px;

            padding:
                25px;

            text-align:
                center;

            box-shadow:
                0 30px 70px
                rgba(
                    20,
                    12,
                    30,
                    0.25
                );
        }


        .confirm-icon {

            width:
                56px;

            height:
                56px;

            margin:
                0 auto 14px;

            border-radius:
                16px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #fff0f1;

            color:
                #d13b4a;

            font-size:
                21px;
        }


        .confirm-modal h3 {

            font-size:
                17px;

            margin-bottom:
                7px;
        }


        .confirm-modal p {

            color:
                #8a8290;

            font-size:
                12px;

            line-height:
                1.55;

            margin-bottom:
                20px;
        }


        .confirm-actions {

            display:
                flex;

            gap:
                9px;
        }


        .confirm-actions button {

            flex:
                1;

            min-height:
                42px;

            border-radius:
                10px;

            cursor:
                pointer;

            font-size:
                12px;

            font-weight:
                700;
        }


        .confirm-cancel {

            background:
                #f2eef4;

            color:
                #625b69;
        }


        .confirm-delete {

            background:
                #d13b4a;

            color:
                #ffffff;

            box-shadow:
                0 6px 15px
                rgba(
                    209,
                    59,
                    74,
                    0.18
                );
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 1050px
        ) {

            .stats-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );
            }


            .filter-row {

                grid-template-columns:
                    2fr 1fr 1fr;
            }


            .filter-row
            .clear-btn {

                width:
                    100%;
            }
        }


        @media (
            max-width: 700px
        ) {

            .reviews-page {

                padding:
                    18px;
            }


            .page-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .refresh-btn {

                width:
                    100%;
            }


            .stats-grid {

                grid-template-columns:
                    1fr 1fr;

                gap:
                    11px;
            }


            .stat-card {

                padding:
                    14px;

                gap:
                    10px;
            }


            .stat-icon {

                width:
                    39px;

                height:
                    39px;

                flex-basis:
                    39px;
            }


            .stat-value {

                font-size:
                    17px;
            }


            .filter-row {

                grid-template-columns:
                    1fr;
            }


            .detail-grid {

                grid-template-columns:
                    1fr;
            }


            .pagination {

                flex-direction:
                    column;

                align-items:
                    flex-start;
            }


            .pagination-links {

                width:
                    100%;

                overflow-x:
                    auto;

                padding-bottom:
                    3px;
            }
        }


        @media (
            max-width: 450px
        ) {

            .stats-grid {

                grid-template-columns:
                    1fr;
            }


            .modal-overlay {

                padding:
                    10px;
            }


            .review-modal {

                max-height:
                    calc(
                        100vh - 20px
                    );

                border-radius:
                    16px;
            }


            .modal-header,
            .modal-body {

                padding:
                    18px;
            }


            .modal-footer {

                padding:
                    14px 18px;
            }
        }

    </style>

</head>


<body><div class="admin-wrapper">


    <?php
    require_once __DIR__ . '/../includes/sidebar.php';
    ?>


    <main class="main-area">


        <?php
        require_once __DIR__ . '/../includes/header.php';
        ?>


        <div class="page-content">


            <div class="reviews-page">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <header class="page-header">

        <div class="page-title">

            <h1>
                Reviews
            </h1>

            <p>
                Manage customer reviews and feedback
            </p>

        </div>


        <button
            type="button"
            class="refresh-btn"
            onclick="window.location.reload()"
        >

            <i class="fa-solid fa-rotate-right"></i>

            Refresh

        </button>

    </header>


    <!-- =========================================================
         ALERT
    ========================================================== -->

    <?php if ($message !== ''): ?>

        <div
            class="alert
            <?php
                echo $messageType === 'success'
                    ? 'alert-success'
                    : 'alert-error';
            ?>"
        >

            <i
                class="fa-solid
                <?php
                    echo $messageType === 'success'
                        ? 'fa-circle-check'
                        : 'fa-circle-exclamation';
                ?>"
            ></i>

            <?php echo e($message); ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         STATISTICS
    ========================================================== -->

    <section class="stats-grid">


        <!-- TOTAL -->

        <article class="stat-card">

            <div
                class="stat-icon stat-icon-purple"
            >

                <i class="fa-solid fa-comments"></i>

            </div>


            <div class="stat-info">

                <div class="stat-label">
                    Total Reviews
                </div>

                <div class="stat-value">
                    <?php
                        echo number_format(
                            $totalReviews
                        );
                    ?>
                </div>

            </div>

        </article>


        <!-- APPROVED -->

        <article class="stat-card">

            <div
                class="stat-icon stat-icon-green"
            >

                <i class="fa-solid fa-circle-check"></i>

            </div>


            <div class="stat-info">

                <div class="stat-label">
                    Approved
                </div>

                <div class="stat-value">
                    <?php
                        echo number_format(
                            $approvedCount
                        );
                    ?>
                </div>

            </div>

        </article>


        <!-- PENDING -->

        <article class="stat-card">

            <div
                class="stat-icon stat-icon-orange"
            >

                <i class="fa-solid fa-clock"></i>

            </div>


            <div class="stat-info">

                <div class="stat-label">
                    Pending
                </div>

                <div class="stat-value">
                    <?php
                        echo number_format(
                            $pendingCount
                        );
                    ?>
                </div>

            </div>

        </article>


        <!-- RATING -->

        <article class="stat-card">

            <div
                class="stat-icon stat-icon-red"
            >

                <i class="fa-solid fa-star"></i>

            </div>


            <div class="stat-info">

                <div class="stat-label">
                    Average Rating
                </div>

                <div class="stat-value">

                    <?php
                        echo number_format(
                            $averageRating,
                            1
                        );
                    ?>

                    <span
                        style="
                            font-size:12px;
                            color:#a49baa;
                            font-weight:600;
                        "
                    >
                        / 5
                    </span>

                </div>

            </div>

        </article>


    </section>


    <!-- =========================================================
         FILTER PANEL
    ========================================================== -->

    <section class="filter-panel">

        <form
            method="GET"
            action=""
            class="filter-row"
            id="filterForm"
        >


            <!-- SEARCH -->

            <div class="search-wrapper">

                <i
                    class="fa-solid fa-magnifying-glass"
                ></i>

                <input
                    type="search"
                    name="search"
                    class="search-input"
                    placeholder="Search customer, product or review..."
                    value="<?php echo e($search); ?>"
                >

            </div>


            <!-- STATUS -->

            <select
                name="status"
                class="filter-select"
            >

                <option value="">
                    All Status
                </option>


                <?php if (!empty($availableStatuses)): ?>

                    <?php foreach (
                        $availableStatuses
                        as $availableStatus
                    ): ?>

                        <option
                            value="<?php echo e($availableStatus); ?>"
                            <?php
                                echo
                                    $statusFilter ===
                                    $availableStatus
                                    ? 'selected'
                                    : '';
                            ?>
                        >

                            <?php
                                echo e(
                                    statusLabel(
                                        $availableStatus
                                    )
                                );
                            ?>

                        </option>

                    <?php endforeach; ?>

                <?php else: ?>

                    <option
                        value="approved"
                        <?php
                            echo
                                $statusFilter ===
                                'approved'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Approved
                    </option>

                    <option
                        value="pending"
                        <?php
                            echo
                                $statusFilter ===
                                'pending'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Pending
                    </option>

                    <option
                        value="rejected"
                        <?php
                            echo
                                $statusFilter ===
                                'rejected'
                                ? 'selected'
                                : '';
                        ?>
                    >
                        Rejected
                    </option>

                <?php endif; ?>

            </select>


            <!-- RATING -->

            <select
                name="rating"
                class="filter-select"
            >

                <option value="">
                    All Ratings
                </option>

                <option
                    value="5"
                    <?php
                        echo
                            $ratingFilter === '5'
                            ? 'selected'
                            : '';
                    ?>
                >
                    ★★★★★ 5 Stars
                </option>

                <option
                    value="4"
                    <?php
                        echo
                            $ratingFilter === '4'
                            ? 'selected'
                            : '';
                    ?>
                >
                    ★★★★☆ 4 Stars
                </option>

                <option
                    value="3"
                    <?php
                        echo
                            $ratingFilter === '3'
                            ? 'selected'
                            : '';
                    ?>
                >
                    ★★★☆☆ 3 Stars
                </option>

                <option
                    value="2"
                    <?php
                        echo
                            $ratingFilter === '2'
                            ? 'selected'
                            : '';
                    ?>
                >
                    ★★☆☆☆ 2 Stars
                </option>

                <option
                    value="1"
                    <?php
                        echo
                            $ratingFilter === '1'
                            ? 'selected'
                            : '';
                    ?>
                >
                    ★☆☆☆☆ 1 Star
                </option>

            </select>


            <!-- SORT -->

            <select
                name="sort"
                class="filter-select"
            >

                <option
                    value="newest"
                    <?php
                        echo
                            $sort === 'newest'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Newest
                </option>

                <option
                    value="oldest"
                    <?php
                        echo
                            $sort === 'oldest'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Oldest
                </option>

                <option
                    value="rating_high"
                    <?php
                        echo
                            $sort === 'rating_high'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Rating: High to Low
                </option>

                <option
                    value="rating_low"
                    <?php
                        echo
                            $sort === 'rating_low'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Rating: Low to High
                </option>

                <option
                    value="name_asc"
                    <?php
                        echo
                            $sort === 'name_asc'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Customer: A to Z
                </option>

                <option
                    value="name_desc"
                    <?php
                        echo
                            $sort === 'name_desc'
                            ? 'selected'
                            : '';
                    ?>
                >
                    Customer: Z to A
                </option>

            </select>


            <!-- CLEAR -->

            <a
                href="reviews.php"
                class="clear-btn"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                "
            >

                Clear

            </a>


        </form>

    </section>


    <!-- =========================================================
         REVIEWS TABLE
    ========================================================== -->

    <section class="table-card">


        <div class="table-header">

            <h2>
                Customer Reviews
            </h2>


            <span class="result-count">

                <?php
                    echo number_format(
                        count($reviews)
                    );
                ?>

                shown

            </span>

        </div>


        <?php if (!$tableExists): ?>

            <!-- TABLE ERROR -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i
                        class="fa-solid fa-database"
                    ></i>

                </div>

                <h3>
                    Reviews table not found
                </h3>

                <p>
                    Please make sure the
                    <strong>reviews</strong>
                    table exists in your
                    ssrini_handcrafts database.
                </p>

            </div>


        <?php elseif (empty($reviews)): ?>

            <!-- EMPTY -->

            <div class="empty-state">

                <div class="empty-icon">

                    <i
                        class="fa-regular fa-comment-dots"
                    ></i>

                </div>

                <h3>
                    No reviews found
                </h3>

                <p>
                    There are no reviews matching
                    your current filters.
                </p>

            </div>


        <?php else: ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Customer
                            </th>

                            <th>
                                Product
                            </th>

                            <th>
                                Rating
                            </th>

                            <th>
                                Review
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
                        $reviews
                        as $review
                    ): ?>


                        <?php

                        $customerName =
                            getCustomerName(
                                $review
                            );

                        $customerEmail =
                            getCustomerEmail(
                                $review
                            );

                        $reviewRating =
                            $review[
                                'review_rating'
                            ];

                        $reviewComment =
                            $review[
                                'review_comment'
                            ];

                        $reviewStatus =
                            $review[
                                'review_status'
                            ];

                        $productName =
                            $review[
                                'product_name'
                            ]
                            ?? null;

                        $productImage =
                            $review[
                                'product_image'
                            ]
                            ?? null;

                        $reviewDate =
                            $review[
                                'review_created_at'
                            ]
                            ?? null;


                        $initial =
                            strtoupper(
                                substr(
                                    trim(
                                        $customerName
                                    ),
                                    0,
                                    1
                                )
                            );


                        if (
                            $initial === ''
                        ) {

                            $initial = 'G';
                        }


                        $safeRating =
                            is_numeric(
                                $reviewRating
                            )
                                ? max(
                                    0,
                                    min(
                                        5,
                                        (int)
                                            $reviewRating
                                    )
                                )
                                : 0;


                        $stars = '';

                        for (
                            $i = 1;
                            $i <= 5;
                            $i++
                        ) {

                            $stars .=
                                $i <= $safeRating
                                    ? '★'
                                    : '☆';
                        }


                        ?>



                        <tr>


                            <!-- CUSTOMER -->

                            <td>

                                <div
                                    class="customer-cell"
                                >

                                    <div
                                        class="customer-avatar"
                                    >

                                        <?php
                                            echo e(
                                                $initial
                                            );
                                        ?>

                                    </div>


                                    <div
                                        class="customer-details"
                                    >

                                        <div
                                            class="customer-name"
                                            title="<?php echo e($customerName); ?>"
                                        >

                                            <?php
                                                echo e(
                                                    $customerName
                                                );
                                            ?>

                                        </div>


                                        <?php if (
                                            $customerEmail !== ''
                                        ): ?>

                                            <div
                                                class="customer-email"
                                                title="<?php echo e($customerEmail); ?>"
                                            >

                                                <?php
                                                    echo e(
                                                        $customerEmail
                                                    );
                                                ?>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </td>



                            <!-- PRODUCT -->

                            <td>

                                <div
                                    class="product-cell"
                                >


                                    <?php if (
                                        !empty(
                                            $productImage
                                        )
                                    ): ?>

                                        <img
                                            src="../assets/uploads/<?php echo e($productImage); ?>"
                                            alt="<?php echo e($productName ?: 'Product'); ?>"
                                            class="product-thumb"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                        >

                                        <div
                                            class="product-thumb product-placeholder"
                                            style="display:none;"
                                        >

                                            <i
                                                class="fa-solid fa-box"
                                            ></i>

                                        </div>


                                    <?php else: ?>

                                        <div
                                            class="product-thumb product-placeholder"
                                        >

                                            <i
                                                class="fa-solid fa-box"
                                            ></i>

                                        </div>

                                    <?php endif; ?>


                                    <div
                                        class="product-name"
                                        title="<?php echo e($productName ?: 'Product unavailable'); ?>"
                                    >

                                        <?php
                                            echo e(
                                                $productName
                                                ?: 'Product unavailable'
                                            );
                                        ?>

                                    </div>

                                </div>

                            </td>



                            <!-- RATING -->

                            <td>

                                <div
                                    class="rating-wrapper"
                                >

                                    <span
                                        class="stars"
                                    >
                                        <?php
                                            echo e(
                                                $stars
                                            );
                                        ?>
                                    </span>


                                    <?php if (
                                        $safeRating > 0
                                    ): ?>

                                        <span
                                            class="rating-number"
                                        >
                                            <?php
                                                echo
                                                    $safeRating
                                                    . '/5';
                                            ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>



                            <!-- REVIEW -->

                            <td>

                                <?php if (
                                    trim(
                                        (string)
                                            $reviewComment
                                    ) !== ''
                                ): ?>

                                    <div
                                        class="review-text"
                                        title="<?php echo e($reviewComment); ?>"
                                    >

                                        <?php
                                            echo e(
                                                $reviewComment
                                            );
                                        ?>

                                    </div>

                                <?php else: ?>

                                    <span
                                        class="no-review-text"
                                    >
                                        No written review
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status-badge
                                    <?php
                                        echo e(
                                            statusClass(
                                                $reviewStatus
                                            )
                                        );
                                    ?>"
                                >

                                    <?php
                                        echo e(
                                            statusLabel(
                                                $reviewStatus
                                            )
                                        );
                                    ?>

                                </span>

                            </td>



                            <!-- DATE -->

                            <td>

                                <span
                                    style="
                                        white-space:nowrap;
                                        color:#77707f;
                                        font-size:11px;
                                    "
                                >

                                    <?php
                                        echo
                                            formatReviewDate(
                                                $reviewDate
                                            );
                                    ?>

                                </span>

                            </td>



                            <!-- ACTIONS -->

                            <td>

                                <div
                                    class="actions"
                                >


                                    <!-- VIEW -->

                                    <button
                                        type="button"
                                        class="action-btn view-btn"
                                        title="View Review"
                                        onclick='openReviewModal(<?php echo json_encode([
                                            "id" => $review["review_id"],
                                            "customer" => $customerName,
                                            "email" => $customerEmail,
                                            "product" => $productName ?: "Product unavailable",
                                            "rating" => $safeRating,
                                            "comment" => $reviewComment ?: "",
                                            "status" => statusLabel($reviewStatus),
                                            "date" => formatReviewDate($reviewDate)
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'
                                    >

                                        <i
                                            class="fa-solid fa-eye"
                                        ></i>

                                    </button>



                                    <?php
                                    $currentStatus =
                                        strtolower(
                                            trim(
                                                (string)
                                                    $reviewStatus
                                            )
                                        );
                                    ?>


                                    <!-- APPROVE -->

                                    <?php if (
                                        $statusColumn &&
                                        !in_array(
                                            $currentStatus,
                                            [
                                                'approved',
                                                'active',
                                                'published',
                                                'visible'
                                            ],
                                            true
                                        )
                                    ): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo e($csrfToken); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="approve"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?php echo e($review['review_id']); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn approve-btn"
                                                title="Approve Review"
                                                onclick="return confirm('Approve this review?');"
                                            >

                                                <i
                                                    class="fa-solid fa-check"
                                                ></i>

                                            </button>

                                        </form>

                                    <?php endif; ?>



                                    <!-- REJECT -->

                                    <?php if (
                                        $statusColumn &&
                                        !in_array(
                                            $currentStatus,
                                            [
                                                'rejected',
                                                'inactive',
                                                'hidden'
                                            ],
                                            true
                                        )
                                    ): ?>

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo e($csrfToken); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?php echo e($review['review_id']); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn reject-btn"
                                                title="Reject Review"
                                                onclick="return confirm('Reject this review?');"
                                            >

                                                <i
                                                    class="fa-solid fa-xmark"
                                                ></i>

                                            </button>

                                        </form>

                                    <?php endif; ?>



                                    <!-- DELETE -->

                                    <button
                                        type="button"
                                        class="action-btn delete-btn"
                                        title="Delete Review"
                                        onclick="openDeleteModal('<?php echo e($review['review_id']); ?>')"
                                    >

                                        <i
                                            class="fa-solid fa-trash"
                                        ></i>

                                    </button>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


            <!-- =====================================================
                 PAGINATION
            ====================================================== -->

            <?php if (
                $totalPages > 1
            ): ?>

                <div
                    class="pagination"
                >


                    <div
                        class="pagination-info"
                    >

                        Page
                        <strong>
                            <?php echo $page; ?>
                        </strong>
                        of
                        <strong>
                            <?php echo $totalPages; ?>
                        </strong>

                    </div>


                    <div
                        class="pagination-links"
                    >


                        <!-- PREVIOUS -->

                        <a
                            href="<?php echo e(
                                $page > 1
                                    ? pageUrl(
                                        $page - 1
                                    )
                                    : '#'
                            ); ?>"
                            class="page-link
                            <?php
                                echo
                                    $page <= 1
                                        ? 'disabled'
                                        : '';
                            ?>"
                        >

                            <i
                                class="fa-solid fa-chevron-left"
                            ></i>

                        </a>



                        <?php

                        $startPage =
                            max(
                                1,
                                $page - 2
                            );


                        $endPage =
                            min(
                                $totalPages,
                                $page + 2
                            );

                        ?>


                        <?php if (
                            $startPage > 1
                        ): ?>

                            <a
                                href="<?php echo e(
                                    pageUrl(1)
                                ); ?>"
                                class="page-link"
                            >
                                1
                            </a>


                            <?php if (
                                $startPage > 2
                            ): ?>

                                <span
                                    style="
                                        color:#9b93a3;
                                        padding:0 3px;
                                        font-size:12px;
                                    "
                                >
                                    ...
                                </span>

                            <?php endif; ?>

                        <?php endif; ?>



                        <?php for (
                            $i = $startPage;
                            $i <= $endPage;
                            $i++
                        ): ?>

                            <a
                                href="<?php echo e(
                                    pageUrl($i)
                                ); ?>"
                                class="page-link
                                <?php
                                    echo
                                        $i === $page
                                            ? 'active'
                                            : '';
                                ?>"
                            >

                                <?php
                                    echo $i;
                                ?>

                            </a>

                        <?php endfor; ?>



                        <?php if (
                            $endPage < $totalPages
                        ): ?>

                            <?php if (
                                $endPage <
                                $totalPages - 1
                            ): ?>

                                <span
                                    style="
                                        color:#9b93a3;
                                        padding:0 3px;
                                        font-size:12px;
                                    "
                                >
                                    ...
                                </span>

                            <?php endif; ?>


                            <a
                                href="<?php echo e(
                                    pageUrl(
                                        $totalPages
                                    )
                                ); ?>"
                                class="page-link"
                            >

                                <?php
                                    echo
                                        $totalPages;
                                ?>

                            </a>

                        <?php endif; ?>



                        <!-- NEXT -->

                        <a
                            href="<?php echo e(
                                $page < $totalPages
                                    ? pageUrl(
                                        $page + 1
                                    )
                                    : '#'
                            ); ?>"
                            class="page-link
                            <?php
                                echo
                                    $page >= $totalPages
                                        ? 'disabled'
                                        : '';
                            ?>"
                        >

                            <i
                                class="fa-solid fa-chevron-right"
                            ></i>

                        </a>


                    </div>

                </div>

            <?php endif; ?>


        <?php endif; ?>


    </section>


            </div>


        </div>


    </main>


</div>



<!-- =================================================
     MOBILE SIDEBAR CONTROLS
     ================================================= -->

<div
    class="mobile-sidebar-overlay"
    id="mobileSidebarOverlay"
>
</div>


<script>
(function () {
    "use strict";

    const sidebar =
        document.querySelector(".sidebar") ||
        document.querySelector(".admin-sidebar") ||
        document.querySelector(".side-bar") ||
        document.querySelector(".admin-wrapper > aside");

    const menuButton =
        document.getElementById("mobileMenuButton");

    const overlay =
        document.getElementById("mobileSidebarOverlay");

    if (!sidebar || !menuButton || !overlay) {
        return;
    }

    function openSidebar() {
        sidebar.classList.add("mobile-sidebar-open");
        overlay.classList.add("active");
        menuButton.setAttribute("aria-expanded", "true");
        menuButton.innerHTML = "✕";
        document.body.style.overflow = "hidden";
    }

    function closeSidebar() {
        sidebar.classList.remove("mobile-sidebar-open");
        overlay.classList.remove("active");
        menuButton.setAttribute("aria-expanded", "false");
        menuButton.innerHTML = "☰";
        document.body.style.overflow = "";
    }

    menuButton.addEventListener("click", function () {
        if (sidebar.classList.contains("mobile-sidebar-open")) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay.addEventListener("click", function () {
        closeSidebar();
    });

    sidebar.addEventListener("click", function (event) {
        const link = event.target.closest("a");
        if (link && window.innerWidth <= 768) {
            closeSidebar();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && window.innerWidth <= 768) {
            closeSidebar();
        }
    });

    window.addEventListener("resize", function () {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
})();
</script>


<!-- =============================================================
     REVIEW VIEW MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="reviewModal"
    aria-hidden="true"
>


    <div
        class="review-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="reviewModalTitle"
    >


        <div
            class="modal-header"
        >

            <div>

                <div
                    class="modal-title"
                    id="reviewModalTitle"
                >
                    Review Details
                </div>

                <div
                    class="modal-subtitle"
                >
                    Customer feedback
                </div>

            </div>


            <button
                type="button"
                class="close-modal"
                onclick="closeReviewModal()"
                aria-label="Close"
            >

                <i
                    class="fa-solid fa-xmark"
                ></i>

            </button>

        </div>



        <div
            class="modal-body"
        >


            <!-- CUSTOMER -->

            <div
                class="review-profile"
            >

                <div
                    class="large-avatar"
                    id="modalAvatar"
                >
                    G
                </div>


                <div>

                    <div
                        class="profile-name"
                        id="modalCustomer"
                    >
                        Guest Customer
                    </div>


                    <div
                        class="profile-email"
                        id="modalEmail"
                    >
                        —
                    </div>

                </div>

            </div>



            <!-- DETAILS -->

            <div
                class="detail-grid"
            >


                <div
                    class="detail-card"
                >

                    <div
                        class="detail-label"
                    >
                        Product
                    </div>

                    <div
                        class="detail-value"
                        id="modalProduct"
                    >
                        —
                    </div>

                </div>



                <div
                    class="detail-card"
                >

                    <div
                        class="detail-label"
                    >
                        Rating
                    </div>

                    <div
                        class="detail-value"
                        id="modalRating"
                    >
                        —
                    </div>

                </div>



                <div
                    class="detail-card"
                >

                    <div
                        class="detail-label"
                    >
                        Status
                    </div>

                    <div
                        class="detail-value"
                        id="modalStatus"
                    >
                        —
                    </div>

                </div>



                <div
                    class="detail-card"
                >

                    <div
                        class="detail-label"
                    >
                        Submitted
                    </div>

                    <div
                        class="detail-value"
                        id="modalDate"
                    >
                        —
                    </div>

                </div>


            </div>



            <!-- REVIEW -->

            <div
                class="review-content-box"
            >

                <div
                    class="review-content-title"
                >
                    Customer Review
                </div>


                <div
                    class="review-content"
                    id="modalComment"
                >
                    No written review.
                </div>

            </div>


        </div>



        <div
            class="modal-footer"
        >

            <button
                type="button"
                class="modal-btn modal-cancel"
                onclick="closeReviewModal()"
            >

                Close

            </button>

        </div>


    </div>

</div>



<!-- =============================================================
     DELETE CONFIRM MODAL
============================================================== -->

<div
    class="modal-overlay"
    id="deleteModal"
    aria-hidden="true"
>


    <div
        class="confirm-modal"
    >


        <div
            class="confirm-icon"
        >

            <i
                class="fa-solid fa-trash"
            ></i>

        </div>


        <h3>
            Delete Review?
        </h3>


        <p>
            This review will be permanently
            deleted from the database.
            This action cannot be undone.
        </p>


        <div
            class="confirm-actions"
        >

            <button
                type="button"
                class="confirm-cancel"
                onclick="closeDeleteModal()"
            >

                Cancel

            </button>


            <form
                method="POST"
                id="deleteReviewForm"
                style="flex:1;"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo e($csrfToken); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >

                <input
                    type="hidden"
                    name="review_id"
                    id="deleteReviewId"
                    value=""
                >


                <button
                    type="submit"
                    class="confirm-delete"
                    style="width:100%;"
                >

                    Delete Review

                </button>

            </form>

        </div>


    </div>

</div>



<script>

    /*
    |--------------------------------------------------------------------------
    | REVIEW MODAL
    |--------------------------------------------------------------------------
    */

    const reviewModal =
        document.getElementById(
            'reviewModal'
        );


    const deleteModal =
        document.getElementById(
            'deleteModal'
        );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        return String(value ?? '')
            .replace(
                /&/g,
                '&amp;'
            )
            .replace(
                /</g,
                '&lt;'
            )
            .replace(
                />/g,
                '&gt;'
            )
            .replace(
                /"/g,
                '&quot;'
            )
            .replace(
                /'/g,
                '&#039;'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE STARS
    |--------------------------------------------------------------------------
    */

    function createStars(rating) {

        rating =
            Number(rating) || 0;

        rating =
            Math.max(
                0,
                Math.min(
                    5,
                    rating
                )
            );


        let output = '';


        for (
            let i = 1;
            i <= 5;
            i++
        ) {

            output +=
                i <= rating
                    ? '★'
                    : '☆';
        }


        return output;
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN REVIEW MODAL
    |--------------------------------------------------------------------------
    */

    function openReviewModal(review) {

        if (!review) {
            return;
        }


        const customer =
            review.customer ||
            'Guest Customer';


        const initial =
            customer
                .trim()
                .charAt(0)
                .toUpperCase() ||
            'G';


        document.getElementById(
            'modalAvatar'
        ).textContent =
            initial;


        document.getElementById(
            'modalCustomer'
        ).textContent =
            customer;


        document.getElementById(
            'modalEmail'
        ).textContent =
            review.email ||
            'No email available';


        document.getElementById(
            'modalProduct'
        ).textContent =
            review.product ||
            'Product unavailable';


        document.getElementById(
            'modalRating'
        ).innerHTML =
            `
                <span
                    style="
                        color:#f2a51a;
                        letter-spacing:1px;
                        margin-right:7px;
                    "
                >
                    ${escapeHtml(
                        createStars(
                            review.rating
                        )
                    )}
                </span>
                ${escapeHtml(
                    review.rating || 0
                )}/5
            `;


        document.getElementById(
            'modalStatus'
        ).textContent =
            review.status ||
            'Approved';


        document.getElementById(
            'modalDate'
        ).textContent =
            review.date ||
            '—';


        const comment =
            review.comment ||
            'No written review.';


        document.getElementById(
            'modalComment'
        ).textContent =
            comment;


        reviewModal.classList.add(
            'active'
        );


        reviewModal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.style.overflow =
            'hidden';
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE REVIEW MODAL
    |--------------------------------------------------------------------------
    */

    function closeReviewModal() {

        reviewModal.classList.remove(
            'active'
        );


        reviewModal.setAttribute(
            'aria-hidden',
            'true'
        );


        if (
            !deleteModal.classList.contains(
                'active'
            )
        ) {

            document.body.style.overflow =
                '';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE MODAL
    |--------------------------------------------------------------------------
    */

    function openDeleteModal(id) {

        document.getElementById(
            'deleteReviewId'
        ).value =
            id;


        deleteModal.classList.add(
            'active'
        );


        deleteModal.setAttribute(
            'aria-hidden',
            'false'
        );


        document.body.style.overflow =
            'hidden';
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE DELETE MODAL
    |--------------------------------------------------------------------------
    */

    function closeDeleteModal() {

        deleteModal.classList.remove(
            'active'
        );


        deleteModal.setAttribute(
            'aria-hidden',
            'true'
        );


        if (
            !reviewModal.classList.contains(
                'active'
            )
        ) {

            document.body.style.overflow =
                '';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    reviewModal.addEventListener(
        'click',
        function(event) {

            if (
                event.target ===
                reviewModal
            ) {

                closeReviewModal();
            }

        }
    );


    deleteModal.addEventListener(
        'click',
        function(event) {

            if (
                event.target ===
                deleteModal
            ) {

                closeDeleteModal();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESC KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function(event) {

            if (
                event.key ===
                'Escape'
            ) {

                if (
                    reviewModal.classList.contains(
                        'active'
                    )
                ) {

                    closeReviewModal();

                } else if (
                    deleteModal.classList.contains(
                        'active'
                    )
                ) {

                    closeDeleteModal();
                }
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | AUTO SUBMIT FILTER
    |--------------------------------------------------------------------------
    */

    const filterForm =
        document.getElementById(
            'filterForm'
        );


    const filterSelects =
        filterForm.querySelectorAll(
            'select'
        );


    filterSelects.forEach(
        function(select) {

            select.addEventListener(
                'change',
                function() {

                    filterForm.submit();

                }
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SEARCH ENTER
    |--------------------------------------------------------------------------
    */

    const searchInput =
        filterForm.querySelector(
            'input[name="search"]'
        );


    searchInput.addEventListener(
        'keydown',
        function(event) {

            if (
                event.key ===
                'Enter'
            ) {

                filterForm.submit();
            }

        }
    );

</script>


</body>

</html>