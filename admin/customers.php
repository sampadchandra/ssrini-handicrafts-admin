<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN CUSTOMERS
 * =========================================================
 *
 * Purpose:
 * - Display registered customers
 * - Search customers
 * - Filter customers
 * - Show customer order statistics
 * - View complete customer details
 * - Quick phone / email / WhatsApp actions
 * - Pagination
 *
 * Existing project structure:
 *
 * ssrini-handicrafts-admin/
 *
 * ├── admin/
 * │   ├── customers.php
 * │   └── ...
 * │
 * ├── config/
 * │   └── database.php
 * │
 * ├── includes/
 * │   ├── auth.php
 * │   ├── sidebar.php
 * │   └── header.php
 * │
 * ├── assets/
 * │   ├── css/
 * │   │   └── admin.css
 * │   └── js/
 * │       └── admin.js
 *
 * Important:
 * - No database structure is modified.
 * - This page only reads customer/order information.
 * - Uses the existing PDO connection.
 */


/**
 * =========================================================
 * DATABASE + AUTHENTICATION
 * =========================================================
 */

require_once dirname(__DIR__) . '/config/database.php';

require_once dirname(__DIR__) . '/includes/auth.php';

requireAdminLogin();


/**
 * =========================================================
 * PAGE CONFIGURATION
 * =========================================================
 */

$pageTitle = 'Customers';


/**
 * =========================================================
 * HELPER FUNCTIONS
 * =========================================================
 */


/**
 * Escape HTML safely.
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
 * Get all columns of a table.
 */
function getTableColumns(
    PDO $pdo,
    string $tableName
): array {

    if (
        !tableExists(
            $pdo,
            $tableName
        )
    ) {

        return [];
    }

    try {

        $safeTable =
            str_replace(
                '`',
                '``',
                $tableName
            );

        $stmt =
            $pdo->query(
                "SHOW COLUMNS FROM `{$safeTable}`"
            );

        $columns = [];

        while (
            $row =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )
        ) {

            if (
                isset(
                    $row['Field']
                )
            ) {

                $columns[] =
                    $row['Field'];
            }
        }

        return $columns;

    } catch (Throwable $e) {

        return [];
    }
}


/**
 * Find first matching column.
 */
function findColumn(
    array $columns,
    array $candidates
): ?string {

    $lowerColumns = [];

    foreach (
        $columns
        as $column
    ) {

        $lowerColumns[
            strtolower($column)
        ] =
            $column;
    }

    foreach (
        $candidates
        as $candidate
    ) {

        $key =
            strtolower(
                $candidate
            );

        if (
            isset(
                $lowerColumns[$key]
            )
        ) {

            return $lowerColumns[$key];
        }
    }

    return null;
}


/**
 * Safely quote database identifier.
 */
function quoteIdentifier(
    string $identifier
): string {

    return '`' .
        str_replace(
            '`',
            '``',
            $identifier
        ) .
        '`';
}


/**
 * Format money.
 */
function formatMoney(
    $value
): string {

    if (
        !is_numeric($value)
    ) {

        return '₹0.00';
    }

    return '₹' .
        number_format(
            (float) $value,
            2
        );
}


/**
 * Format date/time.
 */
function formatDateTime(
    $value
): string {

    if (
        empty($value)
    ) {

        return '—';
    }

    $timestamp =
        strtotime(
            (string) $value
        );

    if (
        $timestamp === false
    ) {

        return (string) $value;
    }

    return date(
        'd M Y, h:i A',
        $timestamp
    );
}


/**
 * Format short date.
 */
function formatShortDate(
    $value
): string {

    if (
        empty($value)
    ) {

        return '—';
    }

    $timestamp =
        strtotime(
            (string) $value
        );

    if (
        $timestamp === false
    ) {

        return (string) $value;
    }

    return date(
        'd M Y',
        $timestamp
    );
}


/**
 * Get customer initials.
 */
function getInitials(
    string $name
): string {

    $name =
        trim($name);

    if (
        $name === ''
    ) {

        return 'C';
    }

    $parts =
        preg_split(
            '/\s+/',
            $name
        );

    if (
        count($parts) >= 2
    ) {

        return strtoupper(
            substr(
                $parts[0],
                0,
                1
            ) .
            substr(
                $parts[1],
                0,
                1
            )
        );
    }

    return strtoupper(
        substr(
            $name,
            0,
            2
        )
    );
}


/**
 * Normalize customer status.
 */
function normalizeCustomerStatus(
    $status
): string {

    $value =
        strtolower(
            trim(
                (string) $status
            )
        );

    if (
        in_array(
            $value,
            [
                'inactive',
                'disabled',
                'blocked',
                'suspended'
            ],
            true
        )
    ) {

        return 'Inactive';
    }

    if (
        in_array(
            $value,
            [
                'pending',
                'unverified'
            ],
            true
        )
    ) {

        return 'Pending';
    }

    return 'Active';
}


/**
 * Status CSS class.
 */
function customerStatusClass(
    $status
): string {

    $status =
        normalizeCustomerStatus(
            $status
        );

    if (
        $status === 'Inactive'
    ) {

        return 'customer-status-inactive';
    }

    if (
        $status === 'Pending'
    ) {

        return 'customer-status-pending';
    }

    return 'customer-status-active';
}


/**
 * Build page URL.
 */
function buildCustomerPageUrl(
    int $page
): string {

    $params = [];

    if (
        isset($_GET['search']) &&
        trim(
            (string) $_GET['search']
        ) !== ''
    ) {

        $params['search'] =
            trim(
                (string) $_GET['search']
            );
    }

    if (
        isset($_GET['status']) &&
        trim(
            (string) $_GET['status']
        ) !== ''
    ) {

        $params['status'] =
            trim(
                (string) $_GET['status']
            );
    }

    $params['page'] =
        max(
            1,
            $page
        );

    return
        'customers.php?' .
        http_build_query(
            $params
        );
}


/**
 * =========================================================
 * DEFAULT VALUES
 * =========================================================
 */

$customers = [];

$totalCustomers = 0;

$currentPage = 1;

$perPage = 15;

$totalPages = 1;

$displayStart = 0;

$displayEnd = 0;

$customerError = null;

$customersTableExists = false;

$ordersTableExists = false;


/**
 * =========================================================
 * GET FILTERS
 * =========================================================
 */

$search =
    isset($_GET['search'])
        ? trim(
            (string) $_GET['search']
        )
        : '';


$statusFilter =
    isset($_GET['status'])
        ? trim(
            (string) $_GET['status']
        )
        : '';


$currentPage =
    isset($_GET['page'])
        ? (int) $_GET['page']
        : 1;


if (
    $currentPage < 1
) {

    $currentPage = 1;
}


/**
 * =========================================================
 * DATABASE ANALYSIS
 * =========================================================
 */

try {

    /**
     * -----------------------------------------------------
     * CHECK CUSTOMERS TABLE
     * -----------------------------------------------------
     */

    $customersTableExists =
        tableExists(
            $pdo,
            'customers'
        );


    if (
        !$customersTableExists
    ) {

        $customerError =
            'The customers table was not found in the current database.';

    } else {

        /**
         * -------------------------------------------------
         * DETECT CUSTOMER COLUMNS
         * -------------------------------------------------
         */

        $customerColumns =
            getTableColumns(
                $pdo,
                'customers'
            );


        /**
         * Customer ID.
         */
        $idColumn =
            findColumn(
                $customerColumns,
                [
                    'id',
                    'customer_id',
                    'user_id'
                ]
            );


        /**
         * Customer name.
         */
        $nameColumn =
            findColumn(
                $customerColumns,
                [
                    'name',
                    'full_name',
                    'customer_name',
                    'username'
                ]
            );


        /**
         * First name.
         */
        $firstNameColumn =
            findColumn(
                $customerColumns,
                [
                    'first_name',
                    'firstname'
                ]
            );


        /**
         * Last name.
         */
        $lastNameColumn =
            findColumn(
                $customerColumns,
                [
                    'last_name',
                    'lastname'
                ]
            );


        /**
         * Email.
         */
        $emailColumn =
            findColumn(
                $customerColumns,
                [
                    'email',
                    'email_address'
                ]
            );


        /**
         * Phone.
         */
        $phoneColumn =
            findColumn(
                $customerColumns,
                [
                    'phone',
                    'phone_number',
                    'mobile',
                    'mobile_number',
                    'contact',
                    'contact_number'
                ]
            );


        /**
         * Address.
         */
        $addressColumn =
            findColumn(
                $customerColumns,
                [
                    'address',
                    'full_address',
                    'customer_address'
                ]
            );


        /**
         * City.
         */
        $cityColumn =
            findColumn(
                $customerColumns,
                [
                    'city',
                    'town'
                ]
            );


        /**
         * State.
         */
        $stateColumn =
            findColumn(
                $customerColumns,
                [
                    'state',
                    'province'
                ]
            );


        /**
         * Pincode.
         */
        $pincodeColumn =
            findColumn(
                $customerColumns,
                [
                    'pincode',
                    'pin_code',
                    'postal_code',
                    'zip',
                    'zip_code'
                ]
            );


        /**
         * Status.
         */
        $statusColumn =
            findColumn(
                $customerColumns,
                [
                    'status',
                    'account_status',
                    'customer_status'
                ]
            );


        /**
         * Created date.
         */
        $createdColumn =
            findColumn(
                $customerColumns,
                [
                    'created_at',
                    'created_date',
                    'registered_at',
                    'registration_date',
                    'date_created'
                ]
            );


        /**
         * Updated date.
         */
        $updatedColumn =
            findColumn(
                $customerColumns,
                [
                    'updated_at',
                    'updated_date',
                    'modified_at'
                ]
            );


        /**
         * -------------------------------------------------
         * CHECK BASIC STRUCTURE
         * -------------------------------------------------
         */

        if (
            $idColumn === null &&
            $nameColumn === null &&
            $emailColumn === null &&
            $phoneColumn === null
        ) {

            $customerError =
                'The customers table structure could not be recognised.';

        } else {

            /**
             * -------------------------------------------------
             * ORDERS TABLE
             * -------------------------------------------------
             */

            $ordersTableExists =
                tableExists(
                    $pdo,
                    'orders'
                );


            $orderColumns = [];

            $orderCustomerIdColumn = null;

            $orderTotalColumn = null;

            $orderCreatedColumn = null;


            if (
                $ordersTableExists
            ) {

                $orderColumns =
                    getTableColumns(
                        $pdo,
                        'orders'
                    );


                $orderCustomerIdColumn =
                    findColumn(
                        $orderColumns,
                        [
                            'customer_id',
                            'user_id',
                            'customer',
                            'user'
                        ]
                    );


                $orderTotalColumn =
                    findColumn(
                        $orderColumns,
                        [
                            'total_amount',
                            'total',
                            'grand_total',
                            'amount',
                            'order_total'
                        ]
                    );


                $orderCreatedColumn =
                    findColumn(
                        $orderColumns,
                        [
                            'created_at',
                            'order_date',
                            'ordered_at',
                            'date'
                        ]
                    );
            }


            /**
             * -------------------------------------------------
             * SELECT CUSTOMER NAME EXPRESSION
             * -------------------------------------------------
             */

            $selectParts = [];


            /**
             * ID.
             */
            if (
                $idColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $idColumn
                    ) .
                    ' AS customer_id';

            } else {

                $selectParts[] =
                    'NULL AS customer_id';
            }


            /**
             * Name.
             */
            if (
                $nameColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $nameColumn
                    ) .
                    ' AS customer_name';

            } elseif (
                $firstNameColumn !== null &&
                $lastNameColumn !== null
            ) {

                $selectParts[] =
                    "TRIM(CONCAT(
                        COALESCE(c." .
                    quoteIdentifier(
                        $firstNameColumn
                    ) .
                    ", ''),
                        ' ',
                        COALESCE(c." .
                    quoteIdentifier(
                        $lastNameColumn
                    ) .
                    ", '')
                    )) AS customer_name";

            } elseif (
                $firstNameColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $firstNameColumn
                    ) .
                    ' AS customer_name';

            } elseif (
                $lastNameColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $lastNameColumn
                    ) .
                    ' AS customer_name';

            } else {

                $selectParts[] =
                    "'' AS customer_name";
            }


            /**
             * Email.
             */
            if (
                $emailColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $emailColumn
                    ) .
                    ' AS email';

            } else {

                $selectParts[] =
                    "'' AS email";
            }


            /**
             * Phone.
             */
            if (
                $phoneColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $phoneColumn
                    ) .
                    ' AS phone';

            } else {

                $selectParts[] =
                    "'' AS phone";
            }


            /**
             * Address.
             */
            if (
                $addressColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $addressColumn
                    ) .
                    ' AS address';

            } else {

                $selectParts[] =
                    "'' AS address";
            }


            /**
             * City.
             */
            if (
                $cityColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $cityColumn
                    ) .
                    ' AS city';

            } else {

                $selectParts[] =
                    "'' AS city";
            }


            /**
             * State.
             */
            if (
                $stateColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $stateColumn
                    ) .
                    ' AS state';

            } else {

                $selectParts[] =
                    "'' AS state";
            }


            /**
             * Pincode.
             */
            if (
                $pincodeColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $pincodeColumn
                    ) .
                    ' AS pincode';

            } else {

                $selectParts[] =
                    "'' AS pincode";
            }


            /**
             * Status.
             */
            if (
                $statusColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $statusColumn
                    ) .
                    ' AS status';

            } else {

                $selectParts[] =
                    "'Active' AS status";
            }


            /**
             * Created date.
             */
            if (
                $createdColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    ' AS created_at';

            } else {

                $selectParts[] =
                    'NULL AS created_at';
            }


            /**
             * Updated date.
             */
            if (
                $updatedColumn !== null
            ) {

                $selectParts[] =
                    'c.' .
                    quoteIdentifier(
                        $updatedColumn
                    ) .
                    ' AS updated_at';

            } else {

                $selectParts[] =
                    'NULL AS updated_at';
            }


            /**
             * -------------------------------------------------
             * ORDER STATISTICS
             * -------------------------------------------------
             */

            $orderJoinSQL = '';


            if (
                $ordersTableExists &&
                $orderCustomerIdColumn !== null &&
                $idColumn !== null
            ) {

                $selectParts[] =
                    'COUNT(o.' .
                    (
                        $orderColumns !== []
                            ? quoteIdentifier(
                                findColumn(
                                    $orderColumns,
                                    [
                                        'id',
                                        'order_id'
                                    ]
                                ) ?? $orderCustomerIdColumn
                            )
                            : quoteIdentifier(
                                $orderCustomerIdColumn
                            )
                    ) .
                    ') AS order_count';


                if (
                    $orderTotalColumn !== null
                ) {

                    $selectParts[] =
                        'COALESCE(
                            SUM(o.' .
                        quoteIdentifier(
                            $orderTotalColumn
                        ) .
                        '),
                        0
                    ) AS total_spent';

                } else {

                    $selectParts[] =
                        '0 AS total_spent';
                }


                if (
                    $orderCreatedColumn !== null
                ) {

                    $selectParts[] =
                        'MAX(o.' .
                        quoteIdentifier(
                            $orderCreatedColumn
                        ) .
                        ') AS last_order_date';

                } else {

                    $selectParts[] =
                        'NULL AS last_order_date';
                }


                $orderJoinSQL =
                    "
                    LEFT JOIN orders o
                        ON o." .
                    quoteIdentifier(
                        $orderCustomerIdColumn
                    ) .
                    " = c." .
                    quoteIdentifier(
                        $idColumn
                    ) .
                    "
                    ";

            } else {

                $selectParts[] =
                    '0 AS order_count';

                $selectParts[] =
                    '0 AS total_spent';

                $selectParts[] =
                    'NULL AS last_order_date';
            }


            /**
             * -------------------------------------------------
             * WHERE CONDITIONS
             * -------------------------------------------------
             */

            $whereParts = [];

            $queryParams = [];


            /**
             * Search.
             */
            if (
                $search !== ''
            ) {

                $searchParts = [];


                if (
                    $nameColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $nameColumn
                        ) .
                        ' LIKE :search';

                } elseif (
                    $firstNameColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $firstNameColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $lastNameColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $lastNameColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $emailColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $emailColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $phoneColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $phoneColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $addressColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $addressColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $cityColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $cityColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    $stateColumn !== null
                ) {

                    $searchParts[] =
                        'c.' .
                        quoteIdentifier(
                            $stateColumn
                        ) .
                        ' LIKE :search';
                }


                if (
                    !empty(
                        $searchParts
                    )
                ) {

                    $whereParts[] =
                        '(' .
                        implode(
                            ' OR ',
                            $searchParts
                        ) .
                        ')';


                    $queryParams[
                        ':search'
                    ] =
                        '%' .
                        $search .
                        '%';
                }
            }


            /**
             * Status filter.
             */
            if (
                $statusFilter !== '' &&
                $statusColumn !== null
            ) {

                $whereParts[] =
                    'c.' .
                    quoteIdentifier(
                        $statusColumn
                    ) .
                    ' = :status';


                $queryParams[
                    ':status'
                ] =
                    $statusFilter;
            }


            /**
             * WHERE SQL.
             */
            $whereSQL = '';


            if (
                !empty(
                    $whereParts
                )
            ) {

                $whereSQL =
                    'WHERE ' .
                    implode(
                        ' AND ',
                        $whereParts
                    );
            }


            /**
             * -------------------------------------------------
             * GROUP BY
             * -------------------------------------------------
             */

            $groupSQL = '';


            if (
                $ordersTableExists &&
                $orderCustomerIdColumn !== null &&
                $idColumn !== null
            ) {

                $groupColumns = [];


                foreach (
                    $selectParts
                    as $selectPart
                ) {

                    /**
                     * No dynamic GROUP BY needed
                     * when ONLY_FULL_GROUP_BY is disabled.
                     *
                     * To remain compatible with MySQL
                     * configurations where ONLY_FULL_GROUP_BY
                     * is enabled, group by customer ID.
                     */

                }


                $groupSQL =
                    'GROUP BY c.' .
                    quoteIdentifier(
                        $idColumn
                    );
            }


            /**
             * -------------------------------------------------
             * COUNT CUSTOMERS
             * -------------------------------------------------
             *
             * Count distinct customers rather than
             * counting joined order rows.
             */

            if (
                $idColumn !== null
            ) {

                $countSQL =
                    "
                    SELECT COUNT(DISTINCT c." .
                    quoteIdentifier(
                        $idColumn
                    ) .
                    ")
                    FROM customers c
                    {$whereSQL}
                    ";

            } else {

                $countSQL =
                    "
                    SELECT COUNT(*)
                    FROM customers c
                    {$whereSQL}
                    ";
            }


            $countStmt =
                $pdo->prepare(
                    $countSQL
                );


            $countStmt->execute(
                $queryParams
            );


            $totalCustomers =
                (int)
                $countStmt->fetchColumn();


            /**
             * -------------------------------------------------
             * PAGINATION
             * -------------------------------------------------
             */

            $totalPages =
                max(
                    1,
                    (int) ceil(
                        $totalCustomers /
                        $perPage
                    )
                );


            if (
                $currentPage >
                $totalPages
            ) {

                $currentPage =
                    $totalPages;
            }


            $offset =
                (
                    $currentPage -
                    1
                ) *
                $perPage;


            /**
             * -------------------------------------------------
             * ORDER BY
             * -------------------------------------------------
             */

            if (
                $createdColumn !== null
            ) {

                $orderSQL =
                    'c.' .
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    ' DESC';

            } elseif (
                $idColumn !== null
            ) {

                $orderSQL =
                    'c.' .
                    quoteIdentifier(
                        $idColumn
                    ) .
                    ' DESC';

            } else {

                $orderSQL =
                    '1 DESC';
            }


            /**
             * -------------------------------------------------
             * FETCH CUSTOMERS
             * -------------------------------------------------
             */

            $customersSQL =
                "
                SELECT
                    " .
                implode(
                    ",
                    ",
                    $selectParts
                ) . "

                FROM customers c

                {$orderJoinSQL}

                {$whereSQL}

                {$groupSQL}

                ORDER BY
                    {$orderSQL}

                LIMIT
                    {$perPage}

                OFFSET
                    {$offset}
                ";


            $customersStmt =
                $pdo->prepare(
                    $customersSQL
                );


            $customersStmt->execute(
                $queryParams
            );


            $customers =
                $customersStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }
    }

} catch (Throwable $e) {

    /**
     * Do not expose database internals.
     */

    $customerError =
        'Customers could not be loaded. Please check your database configuration.';

}


/**
 * =========================================================
 * DISPLAY RANGE
 * =========================================================
 */

if (
    $totalCustomers > 0
) {

    $displayStart =
        (
            (
                $currentPage -
                1
            ) *
            $perPage
        ) +
        1;


    $displayEnd =
        min(
            $currentPage *
            $perPage,
            $totalCustomers
        );
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
        content="Ssrini Handcrafts Admin Customers"
    >

    <title>
        <?= e($pageTitle) ?>
        |
        Ssrini Handcrafts
    </title>


    <!-- =====================================================
         EXISTING ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         CUSTOMER PAGE STYLES
         ===================================================== -->

    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .customers-page {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        /* =====================================================
           FILTER CARD
           ===================================================== */

        .customers-filter-card {

            background: #ffffff;

            border: 1px solid #eeeaf2;

            border-radius: 16px;

            padding: 18px 20px;

            box-shadow:
                0 7px 22px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );

        }


        .customers-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(260px, 2fr)
                minmax(180px, 1fr)
                auto;

            gap: 12px;

            align-items: end;

        }


        .customers-filter-group {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .customers-filter-label {

            font-size: 11px;

            font-weight: 600;

            color: #77717f;

        }


        .customers-filter-input,

        .customers-filter-select {

            width: 100%;

            height: 42px;

            border:
                1px solid #e3dfea;

            border-radius: 10px;

            background: #ffffff;

            color: #25212d;

            padding:
                0 12px;

            outline: none;

            font-size: 12px;

        }


        .customers-filter-input:focus,

        .customers-filter-select:focus {

            border-color: #9b48d1;

            box-shadow:
                0 0 0 3px
                rgba(
                    155,
                    72,
                    209,
                    0.10
                );

        }


        .customers-filter-actions {

            display: flex;

            gap: 8px;

        }


        .customers-filter-button {

            height: 42px;

            border: none;

            border-radius: 10px;

            padding:
                0 17px;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            box-shadow:
                0 7px 16px
                rgba(
                    118,
                    39,
                    201,
                    0.20
                );

            white-space: nowrap;

        }


        .customers-clear-button {

            height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0 15px;

            border-radius: 10px;

            border:
                1px solid #e3dfea;

            background: #ffffff;

            color: #625b6b;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            white-space: nowrap;

        }


        .customers-clear-button:hover {

            background: #faf8fc;

        }


        /* =====================================================
           SUMMARY
           ===================================================== */

        .customers-summary {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

        }


        .customers-summary-text {

            color: #77717f;

            font-size: 12px;

        }


        .customers-summary-text strong {

            color: #25212d;

        }


        .customers-summary-refresh {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            height: 38px;

            padding:
                0 13px;

            border:
                1px solid #e3dfea;

            border-radius: 9px;

            background: #ffffff;

            color: #5f5865;

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;

        }


        .customers-summary-refresh:hover {

            border-color: #cdb7df;

            color: #7627c9;

        }


        /* =====================================================
           CUSTOMER CARD
           ===================================================== */

        .customers-card {

            background: #ffffff;

            border:
                1px solid #eeeaf2;

            border-radius: 16px;

            box-shadow:
                0 7px 22px
                rgba(
                    30,
                    20,
                    50,
                    0.05
                );

            overflow: hidden;

        }


        .customers-card-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .customers-card-title {

            font-size: 15px;

            font-weight: 700;

            color: #25212d;

        }


        .customers-card-description {

            margin-top: 4px;

            font-size: 11px;

            color: #9a94a2;

        }


        .customers-record-count {

            font-size: 11px;

            color: #9a94a2;

            white-space: nowrap;

        }


        /* =====================================================
           TABLE
           ===================================================== */

        .customers-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .customers-table {

            width: 100%;

            min-width: 1050px;

            border-collapse: collapse;

        }


        .customers-table th {

            padding:
                13px 16px;

            background: #faf9fc;

            border-bottom:
                1px solid #eeeaf2;

            color: #8a8392;

            font-size: 10px;

            font-weight: 700;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.04em;

            white-space: nowrap;

        }


        .customers-table td {

            padding:
                14px 16px;

            border-bottom:
                1px solid #f1eef4;

            vertical-align: middle;

            color: #5f5865;

            font-size: 11px;

        }


        .customers-table tbody tr {

            transition:
                background 0.2s ease;

        }


        .customers-table tbody tr:hover {

            background: #fcfbfd;

        }


        .customers-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           CUSTOMER PROFILE
           ===================================================== */

        .customer-profile {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 190px;

        }


        .customer-avatar {

            width: 36px;

            height: 36px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 10px;

            font-weight: 700;

            flex-shrink: 0;

        }


        .customer-profile-info {

            min-width: 0;

        }


        .customer-name {

            color: #403a47;

            font-size: 11px;

            font-weight: 700;

            max-width: 180px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .customer-id {

            margin-top: 3px;

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           CONTACT
           ===================================================== */

        .customer-contact {

            display: flex;

            flex-direction: column;

            gap: 4px;

            min-width: 170px;

        }


        .customer-email {

            color: #5f5865;

            max-width: 200px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .customer-phone {

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           QUICK ACTIONS
           ===================================================== */

        .customer-actions {

            display: flex;

            align-items: center;

            gap: 5px;

        }


        .customer-action-button {

            width: 29px;

            height: 29px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border:
                1px solid #e3dfea;

            border-radius: 8px;

            background: #ffffff;

            color: #6e6676;

            text-decoration: none;

            font-size: 11px;

        }


        .customer-action-button:hover {

            border-color: #cdb7df;

            color: #7627c9;

            background: #faf7fd;

        }


        /* =====================================================
           STATUS
           ===================================================== */

        .customer-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            min-height: 27px;

            padding:
                4px 9px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

        }


        .customer-status::before {

            content: '';

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background: currentColor;

        }


        .customer-status-active {

            background: #e8f8ef;

            color: #23804c;

        }


        .customer-status-inactive {

            background: #fff0f0;

            color: #c53030;

        }


        .customer-status-pending {

            background: #fff4df;

            color: #a56a00;

        }


        /* =====================================================
           ORDERS
           ===================================================== */

        .customer-orders {

            font-size: 11px;

            font-weight: 700;

            color: #403a47;

        }


        .customer-orders-label {

            margin-top: 3px;

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           SPENT
           ===================================================== */

        .customer-spent {

            font-size: 11px;

            font-weight: 700;

            color: #403a47;

            white-space: nowrap;

        }


        /* =====================================================
           DATE
           ===================================================== */

        .customer-date {

            white-space: nowrap;

        }


        .customer-date-main {

            color: #4f4857;

            font-size: 10px;

            font-weight: 600;

        }


        .customer-date-time {

            margin-top: 3px;

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           VIEW BUTTON
           ===================================================== */

        .customer-view-button {

            border:
                1px solid #e3dfea;

            background: #ffffff;

            color: #6e6676;

            border-radius: 8px;

            padding:
                6px 9px;

            cursor: pointer;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

        }


        .customer-view-button:hover {

            border-color: #cdb7df;

            color: #7627c9;

            background: #faf7fd;

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .customers-empty {

            padding:
                65px 20px;

            text-align: center;

            color: #9a94a2;

        }


        .customers-empty-icon {

            width: 58px;

            height: 58px;

            border-radius: 16px;

            background: #f1e8ff;

            display: flex;

            align-items: center;

            justify-content: center;

            margin:
                0 auto 13px;

            font-size: 25px;

        }


        .customers-empty-title {

            color: #25212d;

            font-size: 14px;

            font-weight: 700;

        }


        .customers-empty-description {

            margin-top: 6px;

            font-size: 11px;

            line-height: 1.5;

        }


        /* =====================================================
           ERROR
           ===================================================== */

        .customers-error {

            padding:
                14px 16px;

            border-radius: 10px;

            background: #fff3f3;

            border:
                1px solid #ffd5d5;

            color: #c53030;

            font-size: 12px;

        }


        /* =====================================================
           PAGINATION
           ===================================================== */

        .customers-pagination {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding:
                15px 18px;

            border-top:
                1px solid #eeeaf2;

            flex-wrap: wrap;

        }


        .customers-pagination-info {

            color: #9a94a2;

            font-size: 10px;

        }


        .customers-pagination-links {

            display: flex;

            align-items: center;

            gap: 5px;

        }


        .customers-page-link {

            min-width: 32px;

            height: 32px;

            padding:
                0 8px;

            border:
                1px solid #e3dfea;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #ffffff;

            color: #6e6676;

            text-decoration: none;

            font-size: 10px;

            font-weight: 600;

        }


        .customers-page-link:hover {

            border-color: #cdb7df;

            color: #7627c9;

        }


        .customers-page-link.active {

            border-color: #7627c9;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

        }


        .customers-page-link.disabled {

            opacity: 0.45;

            pointer-events: none;

        }


        .customers-page-dots {

            min-width: 25px;

            text-align: center;

            color: #aaa3b0;

            font-size: 10px;

        }


        /* =====================================================
           MODAL
           ===================================================== */

        .customer-modal-overlay {

            position: fixed;

            inset: 0;

            background:
                rgba(
                    27,
                    20,
                    35,
                    0.48
                );

            display: none;

            align-items: center;

            justify-content: center;

            padding: 20px;

            z-index: 9999;

        }


        .customer-modal-overlay.active {

            display: flex;

        }


        .customer-modal {

            width: min(
                680px,
                100%
            );

            max-height:
                calc(100vh - 40px);

            overflow-y: auto;

            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 25px 70px
                rgba(
                    30,
                    20,
                    50,
                    0.25
                );

        }


        .customer-modal-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .customer-modal-heading {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .customer-modal-avatar {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

            font-size: 10px;

            font-weight: 700;

        }


        .customer-modal-title {

            font-size: 15px;

            font-weight: 700;

            color: #25212d;

        }


        .customer-modal-subtitle {

            margin-top: 3px;

            font-size: 10px;

            color: #9a94a2;

        }


        .customer-modal-close {

            width: 32px;

            height: 32px;

            border: none;

            border-radius: 8px;

            background: #f4f1f6;

            color: #6c6473;

            cursor: pointer;

            font-size: 17px;

        }


        .customer-modal-body {

            padding:
                20px;

        }


        .customer-detail-grid {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 14px;

        }


        .customer-detail-item {

            padding:
                13px;

            border:
                1px solid #eeeaf2;

            border-radius: 10px;

            background: #fcfbfd;

        }


        .customer-detail-item.full {

            grid-column: 1 / -1;

        }


        .customer-detail-label {

            color: #9a94a2;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;

            margin-bottom: 6px;

        }


        .customer-detail-value {

            color: #403a47;

            font-size: 11px;

            line-height: 1.55;

            word-break: break-word;

        }


        .customer-modal-actions {

            display: flex;

            gap: 8px;

            margin-top: 18px;

            flex-wrap: wrap;

        }


        .customer-modal-action {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 6px;

            min-height: 38px;

            padding:
                0 13px;

            border:
                1px solid #e3dfea;

            border-radius: 9px;

            background: #ffffff;

            color: #5f5865;

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;

        }


        .customer-modal-action:hover {

            border-color: #cdb7df;

            color: #7627c9;

            background: #faf7fd;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1000px) {

            .customers-filter-grid {

                grid-template-columns:
                    1fr
                    1fr;

            }


            .customers-filter-actions {

                grid-column: 1 / -1;

            }

        }


        @media (max-width: 700px) {

            .customers-filter-grid {

                grid-template-columns: 1fr;

            }


            .customers-filter-actions {

                grid-column: auto;

                width: 100%;

            }


            .customers-filter-button,

            .customers-clear-button {

                flex: 1;

            }


            .customers-summary {

                align-items: flex-start;

                flex-direction: column;

            }


            .customer-detail-grid {

                grid-template-columns: 1fr;

            }


            .customer-detail-item.full {

                grid-column: auto;

            }


            .customers-pagination {

                flex-direction: column;

                align-items: flex-start;

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

    require_once dirname(__DIR__) .
        '/includes/sidebar.php';

    ?>


    <!-- =================================================
         MAIN AREA
         ================================================= -->

    <main class="main-area">


        <!-- =================================================
             HEADER
             ================================================= -->

        <?php

        require_once dirname(__DIR__) .
            '/includes/header.php';

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

                        Customers

                    </h1>


                    <p class="page-description">

                        Manage registered customers and
                        monitor their activity and orders.

                    </p>

                </div>


                <div>

                    <a
                        href="<?= e(
                            buildCustomerPageUrl(
                                $currentPage
                            )
                        ) ?>"
                        class="btn btn-primary"
                    >

                        🔄

                        Refresh

                    </a>

                </div>


            </section>


            <!-- =================================================
                 CUSTOMERS PAGE
                 ================================================= -->

            <div class="customers-page">


                <!-- =================================================
                     ERROR
                     ================================================= -->

                <?php if (
                    $customerError !== null
                ): ?>

                    <div class="customers-error">

                        <?= e(
                            $customerError
                        ) ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    $customersTableExists
                ): ?>


                    <!-- =================================================
                         FILTER
                         ================================================= -->

                    <form
                        method="GET"
                        action="customers.php"
                        class="customers-filter-card"
                    >

                        <div
                            class="customers-filter-grid"
                        >


                            <!-- SEARCH -->

                            <div
                                class="customers-filter-group"
                            >

                                <label
                                    class="customers-filter-label"
                                    for="customerSearch"
                                >

                                    Search Customer

                                </label>


                                <input
                                    type="search"
                                    id="customerSearch"
                                    name="search"
                                    class="customers-filter-input"
                                    value="<?= e(
                                        $search
                                    ) ?>"
                                    placeholder="Search name, email, phone..."
                                >

                            </div>


                            <!-- STATUS -->

                            <div
                                class="customers-filter-group"
                            >

                                <label
                                    class="customers-filter-label"
                                    for="customerStatus"
                                >

                                    Status

                                </label>


                                <select
                                    id="customerStatus"
                                    name="status"
                                    class="customers-filter-select"
                                >

                                    <option value="">

                                        All Customers

                                    </option>


                                    <option
                                        value="Active"
                                        <?= $statusFilter === 'Active'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Active

                                    </option>


                                    <option
                                        value="Inactive"
                                        <?= $statusFilter === 'Inactive'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Inactive

                                    </option>


                                    <option
                                        value="Pending"
                                        <?= $statusFilter === 'Pending'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Pending

                                    </option>

                                </select>

                            </div>


                            <!-- BUTTONS -->

                            <div
                                class="customers-filter-actions"
                            >

                                <button
                                    type="submit"
                                    class="customers-filter-button"
                                >

                                    Apply Filter

                                </button>


                                <a
                                    href="customers.php"
                                    class="customers-clear-button"
                                >

                                    Clear

                                </a>

                            </div>


                        </div>

                    </form>


                    <!-- =================================================
                         SUMMARY
                         ================================================= -->

                    <div
                        class="customers-summary"
                    >


                        <div
                            class="customers-summary-text"
                        >

                            <?php if (
                                $totalCustomers > 0
                            ): ?>

                                Showing

                                <strong>
                                    <?= e(
                                        $displayStart
                                    ) ?>
                                </strong>

                                to

                                <strong>
                                    <?= e(
                                        $displayEnd
                                    ) ?>
                                </strong>

                                of

                                <strong>
                                    <?= e(
                                        $totalCustomers
                                    ) ?>
                                </strong>

                                customers.

                            <?php else: ?>

                                No customers found.

                            <?php endif; ?>

                        </div>


                        <a
                            href="<?= e(
                                buildCustomerPageUrl(
                                    $currentPage
                                )
                            ) ?>"
                            class="customers-summary-refresh"
                        >

                            🔄

                            Refresh

                        </a>


                    </div>


                    <!-- =================================================
                         CUSTOMER CARD
                         ================================================= -->

                    <section
                        class="customers-card"
                    >


                        <div
                            class="customers-card-header"
                        >

                            <div>

                                <div
                                    class="customers-card-title"
                                >

                                    Customer Directory

                                </div>


                                <div
                                    class="customers-card-description"
                                >

                                    Registered customers and
                                    their purchase information.

                                </div>

                            </div>


                            <div
                                class="customers-record-count"
                            >

                                <?= e(
                                    $totalCustomers
                                ) ?>

                                records

                            </div>

                        </div>


                        <?php if (
                            !empty(
                                $customers
                            )
                        ): ?>


                            <!-- =================================================
                                 TABLE
                                 ================================================= -->

                            <div
                                class="customers-table-wrapper"
                            >

                                <table
                                    class="customers-table"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                Customer
                                            </th>

                                            <th>
                                                Contact
                                            </th>

                                            <th>
                                                Status
                                            </th>

                                            <th>
                                                Orders
                                            </th>

                                            <th>
                                                Total Spent
                                            </th>

                                            <th>
                                                Joined
                                            </th>

                                            <th>
                                                Actions
                                            </th>

                                            <th>
                                                Details
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php foreach (
                                            $customers
                                            as $customer
                                        ): ?>


                                            <?php

                                            $customerId =
                                                $customer[
                                                    'customer_id'
                                                ] ??
                                                '';


                                            $customerName =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'customer_name'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            if (
                                                $customerName === ''
                                            ) {

                                                $customerName =
                                                    'Customer';
                                            }


                                            $email =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'email'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $phone =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'phone'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $address =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'address'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $city =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'city'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $state =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'state'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $pincode =
                                                trim(
                                                    (string) (
                                                        $customer[
                                                            'pincode'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $status =
                                                normalizeCustomerStatus(
                                                    $customer[
                                                        'status'
                                                    ] ??
                                                    'Active'
                                                );


                                            $orderCount =
                                                (int) (
                                                    $customer[
                                                        'order_count'
                                                    ] ??
                                                    0
                                                );


                                            $totalSpent =
                                                $customer[
                                                    'total_spent'
                                                ] ??
                                                0;


                                            $createdAt =
                                                $customer[
                                                    'created_at'
                                                ] ??
                                                '';


                                            $lastOrderDate =
                                                $customer[
                                                    'last_order_date'
                                                ] ??
                                                '';


                                            $initials =
                                                getInitials(
                                                    $customerName
                                                );


                                            $whatsappNumber =
                                                preg_replace(
                                                    '/[^0-9]/',
                                                    '',
                                                    $phone
                                                );

                                            ?>


                                            <tr>


                                                <!-- CUSTOMER -->

                                                <td>

                                                    <div
                                                        class="customer-profile"
                                                    >

                                                        <div
                                                            class="customer-avatar"
                                                        >

                                                            <?= e(
                                                                $initials
                                                            ) ?>

                                                        </div>


                                                        <div
                                                            class="customer-profile-info"
                                                        >

                                                            <div
                                                                class="customer-name"
                                                                title="<?= e(
                                                                    $customerName
                                                                ) ?>"
                                                            >

                                                                <?= e(
                                                                    $customerName
                                                                ) ?>

                                                            </div>


                                                            <div
                                                                class="customer-id"
                                                            >

                                                                ID:

                                                                <?= e(
                                                                    $customerId !== ''
                                                                        ? '#' .
                                                                        $customerId
                                                                        : 'N/A'
                                                                ) ?>

                                                            </div>

                                                        </div>

                                                    </div>

                                                </td>


                                                <!-- CONTACT -->

                                                <td>

                                                    <div
                                                        class="customer-contact"
                                                    >

                                                        <div
                                                            class="customer-email"
                                                            title="<?= e(
                                                                $email
                                                            ) ?>"
                                                        >

                                                            <?= e(
                                                                $email !== ''
                                                                    ? $email
                                                                    : 'No email'
                                                            ) ?>

                                                        </div>


                                                        <div
                                                            class="customer-phone"
                                                        >

                                                            <?= e(
                                                                $phone !== ''
                                                                    ? $phone
                                                                    : 'No phone'
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                </td>


                                                <!-- STATUS -->

                                                <td>

                                                   <span class="customer-status-badge
                                                <?= e(
                                                        customerStatusClass(
                                                                            $status
                                                                                    )
                                                                                         )
                                                                                                ?>"
                                                                                                    >

                                                                                                <?= e(
                                                                                                    $status
                                                                                                ) ?>

                                                    </span>

                                                </td>


                                                <!-- ORDERS -->

                                                <td>

                                                    <div
                                                        class="customer-orders"
                                                    >

                                                        <?= e(
                                                            $orderCount
                                                        ) ?>

                                                    </div>


                                                    <div
                                                        class="customer-orders-label"
                                                    >

                                                        orders

                                                    </div>

                                                </td>


                                                <!-- TOTAL SPENT -->

                                                <td>

                                                    <div
                                                        class="customer-spent"
                                                    >

                                                        <?= e(
                                                            formatMoney(
                                                                $totalSpent
                                                            )
                                                        ) ?>

                                                    </div>

                                                </td>


                                                <!-- JOINED -->

                                                <td>

                                                    <div
                                                        class="customer-date"
                                                    >

                                                        <?php

                                                        $createdTimestamp =
                                                            !empty(
                                                                $createdAt
                                                            )
                                                                ? strtotime(
                                                                    (string)
                                                                    $createdAt
                                                                )
                                                                : false;

                                                        ?>


                                                        <?php if (
                                                            $createdTimestamp !== false
                                                        ): ?>

                                                            <div
                                                                class="customer-date-main"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'd M Y',
                                                                        $createdTimestamp
                                                                    )
                                                                ) ?>

                                                            </div>


                                                            <div
                                                                class="customer-date-time"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'h:i A',
                                                                        $createdTimestamp
                                                                    )
                                                                ) ?>

                                                            </div>

                                                        <?php else: ?>

                                                            <span
                                                                class="customer-date-time"
                                                            >

                                                                —

                                                            </span>

                                                        <?php endif; ?>

                                                    </div>

                                                </td>


                                                <!-- QUICK ACTIONS -->

                                                <td>

                                                    <div
                                                        class="customer-actions"
                                                    >


                                                        <?php if (
                                                            $phone !== ''
                                                        ): ?>

                                                            <a
                                                                href="tel:<?= e(
                                                                    $phone
                                                                ) ?>"
                                                                class="customer-action-button"
                                                                title="Call Customer"
                                                                aria-label="Call Customer"
                                                            >

                                                                📞

                                                            </a>

                                                        <?php endif; ?>


                                                        <?php if (
                                                            $email !== ''
                                                        ): ?>

                                                            <a
                                                                href="mailto:<?= e(
                                                                    $email
                                                                ) ?>"
                                                                class="customer-action-button"
                                                                title="Email Customer"
                                                                aria-label="Email Customer"
                                                            >

                                                                ✉

                                                            </a>

                                                        <?php endif; ?>


                                                        <?php if (
                                                            $whatsappNumber !== ''
                                                        ): ?>

                                                            <a
                                                                href="https://wa.me/<?= e(
                                                                    $whatsappNumber
                                                                ) ?>"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="customer-action-button"
                                                                title="WhatsApp Customer"
                                                                aria-label="WhatsApp Customer"
                                                            >

                                                                💬

                                                            </a>

                                                        <?php endif; ?>


                                                    </div>

                                                </td>


                                                <!-- DETAILS -->

                                                <td>

                                                    <button
                                                        type="button"
                                                        class="customer-view-button"
                                                        data-customer-id="<?= e(
                                                            $customerId !== ''
                                                                ? $customerId
                                                                : 'N/A'
                                                        ) ?>"
                                                        data-customer-name="<?= e(
                                                            $customerName
                                                        ) ?>"
                                                        data-email="<?= e(
                                                            $email !== ''
                                                                ? $email
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-phone="<?= e(
                                                            $phone !== ''
                                                                ? $phone
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-status="<?= e(
                                                            $status
                                                        ) ?>"
                                                        data-address="<?= e(
                                                            $address !== ''
                                                                ? $address
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-city="<?= e(
                                                            $city !== ''
                                                                ? $city
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-state="<?= e(
                                                            $state !== ''
                                                                ? $state
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-pincode="<?= e(
                                                            $pincode !== ''
                                                                ? $pincode
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-orders="<?= e(
                                                            $orderCount
                                                        ) ?>"
                                                        data-spent="<?= e(
                                                            formatMoney(
                                                                $totalSpent
                                                            )
                                                        ) ?>"
                                                        data-joined="<?= e(
                                                            formatDateTime(
                                                                $createdAt
                                                            )
                                                        ) ?>"
                                                        data-last-order="<?= e(
                                                            formatDateTime(
                                                                $lastOrderDate
                                                            )
                                                        ) ?>"
                                                    >

                                                        View

                                                    </button>

                                                </td>


                                            </tr>


                                        <?php endforeach; ?>


                                    </tbody>

                                </table>

                            </div>


                            <!-- =================================================
                                 PAGINATION
                                 ================================================= -->

                            <?php if (
                                $totalPages > 1
                            ): ?>


                                <div
                                    class="customers-pagination"
                                >


                                    <div
                                        class="customers-pagination-info"
                                    >

                                        Page

                                        <strong>
                                            <?= e(
                                                $currentPage
                                            ) ?>
                                        </strong>

                                        of

                                        <strong>
                                            <?= e(
                                                $totalPages
                                            ) ?>
                                        </strong>

                                    </div>


                                    <div
                                        class="customers-pagination-links"
                                    >


                                        <!-- PREVIOUS -->

                                        <a
                                            href="<?= e(
                                                buildCustomerPageUrl(
                                                    max(
                                                        1,
                                                        $currentPage - 1
                                                    )
                                                )
                                            ) ?>"
                                            class="
                                                customers-page-link
                                                <?= $currentPage <= 1
                                                    ? 'disabled'
                                                    : '' ?>
                                            "
                                        >

                                            ‹

                                        </a>


                                        <?php

                                        $paginationStart =
                                            max(
                                                1,
                                                $currentPage - 2
                                            );


                                        $paginationEnd =
                                            min(
                                                $totalPages,
                                                $currentPage + 2
                                            );

                                        ?>


                                        <?php if (
                                            $paginationStart > 1
                                        ): ?>


                                            <a
                                                href="<?= e(
                                                    buildCustomerPageUrl(
                                                        1
                                                    )
                                                ) ?>"
                                                class="customers-page-link"
                                            >

                                                1

                                            </a>


                                            <?php if (
                                                $paginationStart > 2
                                            ): ?>

                                                <span
                                                    class="customers-page-dots"
                                                >

                                                    ...

                                                </span>

                                            <?php endif; ?>


                                        <?php endif; ?>


                                        <?php for (
                                            $page = $paginationStart;
                                            $page <= $paginationEnd;
                                            $page++
                                        ): ?>


                                            <a
                                                href="<?= e(
                                                    buildCustomerPageUrl(
                                                        $page
                                                    )
                                                ) ?>"
                                                class="
                                                    customers-page-link
                                                    <?= $page === $currentPage
                                                        ? 'active'
                                                        : '' ?>
                                                "
                                            >

                                                <?= e(
                                                    $page
                                                ) ?>

                                            </a>


                                        <?php endfor; ?>


                                        <?php if (
                                            $paginationEnd <
                                            $totalPages
                                        ): ?>


                                            <?php if (
                                                $paginationEnd <
                                                $totalPages - 1
                                            ): ?>

                                                <span
                                                    class="customers-page-dots"
                                                >

                                                    ...

                                                </span>

                                            <?php endif; ?>


                                            <a
                                                href="<?= e(
                                                    buildCustomerPageUrl(
                                                        $totalPages
                                                    )
                                                ) ?>"
                                                class="customers-page-link"
                                            >

                                                <?= e(
                                                    $totalPages
                                                ) ?>

                                            </a>


                                        <?php endif; ?>


                                        <!-- NEXT -->

                                        <a
                                            href="<?= e(
                                                buildCustomerPageUrl(
                                                    min(
                                                        $totalPages,
                                                        $currentPage + 1
                                                    )
                                                )
                                            ) ?>"
                                            class="
                                                customers-page-link
                                                <?= $currentPage >= $totalPages
                                                    ? 'disabled'
                                                    : '' ?>
                                            "
                                        >

                                            ›

                                        </a>


                                    </div>


                                </div>


                            <?php endif; ?>


                        <?php else: ?>


                            <!-- =================================================
                                 EMPTY STATE
                                 ================================================= -->

                            <div
                                class="customers-empty"
                            >

                                <div
                                    class="customers-empty-icon"
                                >

                                    👥

                                </div>


                                <div
                                    class="customers-empty-title"
                                >

                                    No Customers Found

                                </div>


                                <div
                                    class="customers-empty-description"
                                >

                                    <?php if (
                                        $search !== '' ||
                                        $statusFilter !== ''
                                    ): ?>

                                        No customers match your
                                        current filters. Try
                                        clearing the filters and
                                        search again.

                                    <?php else: ?>

                                        Registered customers will
                                        appear here when they are
                                        added to the store.

                                    <?php endif; ?>

                                </div>

                            </div>


                        <?php endif; ?>


                    </section>


                <?php else: ?>


                    <!-- =================================================
                         TABLE NOT AVAILABLE
                         ================================================= -->

                    <section
                        class="customers-card"
                    >

                        <div
                            class="customers-empty"
                        >

                            <div
                                class="customers-empty-icon"
                            >

                                👥

                            </div>


                            <div
                                class="customers-empty-title"
                            >

                                Customer Table Not Available

                            </div>


                            <div
                                class="customers-empty-description"
                            >

                                The customer management page
                                is ready, but the
                                <strong>
                                    customers
                                </strong>
                                table is not available in the
                                current database.

                            </div>

                        </div>

                    </section>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     CUSTOMER DETAILS MODAL
     ===================================================== -->

<div
    class="customer-modal-overlay"
    id="customerDetailsModal"
    aria-hidden="true"
>


    <div
        class="customer-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="customerModalTitle"
    >


        <!-- =================================================
             MODAL HEADER
             ================================================= -->

        <div
            class="customer-modal-header"
        >


            <div
                class="customer-modal-heading"
            >

                <div
                    class="customer-modal-avatar"
                    id="customerModalAvatar"
                >

                    C

                </div>


                <div>

                    <div
                        class="customer-modal-title"
                        id="customerModalTitle"
                    >

                        Customer Details

                    </div>


                    <div
                        class="customer-modal-subtitle"
                        id="customerModalSubtitle"
                    >

                        Customer information

                    </div>

                </div>

            </div>


            <button
                type="button"
                class="customer-modal-close"
                id="closeCustomerModal"
                aria-label="Close"
            >

                ×

            </button>


        </div>


        <!-- =================================================
             MODAL BODY
             ================================================= -->

        <div
            class="customer-modal-body"
        >


            <div
                class="customer-detail-grid"
            >


                <!-- CUSTOMER ID -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Customer ID

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerId"
                    >

                        —

                    </div>

                </div>


                <!-- STATUS -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Account Status

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerStatus"
                    >

                        —

                    </div>

                </div>


                <!-- EMAIL -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Email

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerEmail"
                    >

                        —

                    </div>

                </div>


                <!-- PHONE -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Phone

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerPhone"
                    >

                        —

                    </div>

                </div>


                <!-- ADDRESS -->

                <div
                    class="
                        customer-detail-item
                        full
                    "
                >

                    <div
                        class="customer-detail-label"
                    >

                        Address

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerAddress"
                    >

                        —

                    </div>

                </div>


                <!-- CITY -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        City

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerCity"
                    >

                        —

                    </div>

                </div>


                <!-- STATE -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        State

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerState"
                    >

                        —

                    </div>

                </div>


                <!-- PINCODE -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Pincode

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerPincode"
                    >

                        —

                    </div>

                </div>


                <!-- ORDERS -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Total Orders

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerOrders"
                    >

                        —

                    </div>

                </div>


                <!-- TOTAL SPENT -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Total Spent

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerSpent"
                    >

                        —

                    </div>

                </div>


                <!-- JOINED -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Joined

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerJoined"
                    >

                        —

                    </div>

                </div>


                <!-- LAST ORDER -->

                <div
                    class="customer-detail-item"
                >

                    <div
                        class="customer-detail-label"
                    >

                        Last Order

                    </div>


                    <div
                        class="customer-detail-value"
                        id="modalCustomerLastOrder"
                    >

                        —

                    </div>

                </div>


            </div>


            <!-- =================================================
                 MODAL ACTIONS
                 ================================================= -->

            <div
                class="customer-modal-actions"
            >

                <a
                    href="#"
                    class="customer-modal-action"
                    id="modalCallButton"
                >

                    📞

                    Call Customer

                </a>


                <a
                    href="#"
                    class="customer-modal-action"
                    id="modalEmailButton"
                >

                    ✉

                    Send Email

                </a>


                <a
                    href="#"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="customer-modal-action"
                    id="modalWhatsappButton"
                >

                    💬

                    WhatsApp

                </a>

            </div>


        </div>


    </div>

</div>


<!-- =====================================================
     EXISTING ADMIN JAVASCRIPT
     ===================================================== -->

<script
    src="../assets/js/admin.js"
></script>


<!-- =====================================================
     CUSTOMER PAGE JAVASCRIPT
     ===================================================== -->

<script>

    /*
    |--------------------------------------------------------------------------
    | DOM ELEMENTS
    |--------------------------------------------------------------------------
    */

    const customerModal =
        document.getElementById(
            'customerDetailsModal'
        );


    const closeCustomerModalButton =
        document.getElementById(
            'closeCustomerModal'
        );


    const customerModalAvatar =
        document.getElementById(
            'customerModalAvatar'
        );


    const customerModalTitle =
        document.getElementById(
            'customerModalTitle'
        );


    const customerModalSubtitle =
        document.getElementById(
            'customerModalSubtitle'
        );


    const modalCustomerId =
        document.getElementById(
            'modalCustomerId'
        );


    const modalCustomerStatus =
        document.getElementById(
            'modalCustomerStatus'
        );


    const modalCustomerEmail =
        document.getElementById(
            'modalCustomerEmail'
        );


    const modalCustomerPhone =
        document.getElementById(
            'modalCustomerPhone'
        );


    const modalCustomerAddress =
        document.getElementById(
            'modalCustomerAddress'
        );


    const modalCustomerCity =
        document.getElementById(
            'modalCustomerCity'
        );


    const modalCustomerState =
        document.getElementById(
            'modalCustomerState'
        );


    const modalCustomerPincode =
        document.getElementById(
            'modalCustomerPincode'
        );


    const modalCustomerOrders =
        document.getElementById(
            'modalCustomerOrders'
        );


    const modalCustomerSpent =
        document.getElementById(
            'modalCustomerSpent'
        );


    const modalCustomerJoined =
        document.getElementById(
            'modalCustomerJoined'
        );


    const modalCustomerLastOrder =
        document.getElementById(
            'modalCustomerLastOrder'
        );


    const modalCallButton =
        document.getElementById(
            'modalCallButton'
        );


    const modalEmailButton =
        document.getElementById(
            'modalEmailButton'
        );


    const modalWhatsappButton =
        document.getElementById(
            'modalWhatsappButton'
        );


    /*
    |--------------------------------------------------------------------------
    | GET INITIALS
    |--------------------------------------------------------------------------
    */

    function getCustomerInitials(
        name
    ) {

        if (
            !name
        ) {

            return 'C';
        }


        const parts =
            name
                .trim()
                .split(
                    /\s+/
                );


        if (
            parts.length >= 2
        ) {

            return (
                parts[0].charAt(0) +
                parts[1].charAt(0)
            ).toUpperCase();

        }


        return name
            .substring(
                0,
                2
            )
            .toUpperCase();

    }


    /*
    |--------------------------------------------------------------------------
    | OPEN CUSTOMER MODAL
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '.customer-view-button'
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const customerId =
                            this.dataset.customerId ||
                            'N/A';


                        const customerName =
                            this.dataset.customerName ||
                            'Customer';


                        const email =
                            this.dataset.email ||
                            'Not available';


                        const phone =
                            this.dataset.phone ||
                            'Not available';


                        const status =
                            this.dataset.status ||
                            'Active';


                        const address =
                            this.dataset.address ||
                            'Not available';


                        const city =
                            this.dataset.city ||
                            'Not available';


                        const state =
                            this.dataset.state ||
                            'Not available';


                        const pincode =
                            this.dataset.pincode ||
                            'Not available';


                        const orders =
                            this.dataset.orders ||
                            '0';


                        const spent =
                            this.dataset.spent ||
                            '₹0.00';


                        const joined =
                            this.dataset.joined ||
                            'Not available';


                        const lastOrder =
                            this.dataset.lastOrder ||
                            'No orders';


                        /*
                        |------------------------------------------------------
                        | MODAL CONTENT
                        |------------------------------------------------------
                        */

                        customerModalAvatar.textContent =
                            getCustomerInitials(
                                customerName
                            );


                        customerModalTitle.textContent =
                            customerName;


                        customerModalSubtitle.textContent =
                            'Customer information';


                        modalCustomerId.textContent =
                            customerId;


                        modalCustomerStatus.textContent =
                            status;


                        modalCustomerEmail.textContent =
                            email;


                        modalCustomerPhone.textContent =
                            phone;


                        modalCustomerAddress.textContent =
                            address;


                        modalCustomerCity.textContent =
                            city;


                        modalCustomerState.textContent =
                            state;


                        modalCustomerPincode.textContent =
                            pincode;


                        modalCustomerOrders.textContent =
                            orders;


                        modalCustomerSpent.textContent =
                            spent;


                        modalCustomerJoined.textContent =
                            joined;


                        modalCustomerLastOrder.textContent =
                            lastOrder;


                        /*
                        |------------------------------------------------------
                        | QUICK ACTION LINKS
                        |------------------------------------------------------
                        */

                        if (
                            phone &&
                            phone !== 'Not available'
                        ) {

                            modalCallButton.href =
                                'tel:' +
                                phone;

                            modalCallButton.style.display =
                                'inline-flex';

                        } else {

                            modalCallButton.style.display =
                                'none';
                        }


                        if (
                            email &&
                            email !== 'Not available'
                        ) {

                            modalEmailButton.href =
                                'mailto:' +
                                email;

                            modalEmailButton.style.display =
                                'inline-flex';

                        } else {

                            modalEmailButton.style.display =
                                'none';
                        }


                        const whatsappNumber =
                            phone
                                .replace(
                                    /[^0-9]/g,
                                    ''
                                );


                        if (
                            whatsappNumber !== ''
                        ) {

                            modalWhatsappButton.href =
                                'https://wa.me/' +
                                whatsappNumber;

                            modalWhatsappButton.style.display =
                                'inline-flex';

                        } else {

                            modalWhatsappButton.style.display =
                                'none';
                        }


                        /*
                        |------------------------------------------------------
                        | SHOW MODAL
                        |------------------------------------------------------
                        */

                        customerModal.classList.add(
                            'active'
                        );


                        customerModal.setAttribute(
                            'aria-hidden',
                            'false'
                        );


                        document.body.style.overflow =
                            'hidden';

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | CLOSE CUSTOMER MODAL
    |--------------------------------------------------------------------------
    */

    function closeCustomerModal() {

        if (
            !customerModal
        ) {

            return;
        }


        customerModal.classList.remove(
            'active'
        );


        customerModal.setAttribute(
            'aria-hidden',
            'true'
        );


        document.body.style.overflow =
            '';

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    if (
        closeCustomerModalButton
    ) {

        closeCustomerModalButton.addEventListener(
            'click',
            closeCustomerModal
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    if (
        customerModal
    ) {

        customerModal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    customerModal
                ) {

                    closeCustomerModal();

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ESC KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
            ) {

                closeCustomerModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SEARCH ENTER HANDLING
    |--------------------------------------------------------------------------
    */

    const customerSearch =
        document.getElementById(
            'customerSearch'
        );


    if (
        customerSearch
    ) {

        customerSearch.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter'
                ) {

                    event.preventDefault();

                    this.form.submit();

                }

            }
        );

    }

</script>


</body>

</html>