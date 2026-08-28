<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN ACTIVITY LOGS
 * =========================================================
 *
 * Purpose:
 * - Display administrator activity history
 * - Search activity logs
 * - Filter by action
 * - Filter by date
 * - Pagination
 * - View complete log details
 *
 * Existing project structure:
 * - config/database.php
 * - includes/auth.php
 * - includes/sidebar.php
 * - includes/header.php
 * - assets/css/admin.css
 * - assets/js/admin.js
 *
 * No database structure is modified by this page.
 */


/**
 * =========================================================
 * DATABASE + AUTHENTICATION
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

$pageTitle = 'Activity Logs';


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

    if (!tableExists($pdo, $tableName)) {
        return [];
    }

    try {

        $safeTable =
            str_replace(
                '`',
                '``',
                $tableName
            );

        $stmt = $pdo->query(
            "SHOW COLUMNS FROM `{$safeTable}`"
        );

        $columns = [];

        while (
            $row =
            $stmt->fetch(PDO::FETCH_ASSOC)
        ) {

            if (
                isset($row['Field'])
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

    foreach ($columns as $column) {

        $lowerColumns[
            strtolower($column)
        ] = $column;
    }

    foreach ($candidates as $candidate) {

        $key =
            strtolower($candidate);

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
 * Create readable action label.
 */
function formatActionLabel(
    $action
): string {

    $action =
        trim(
            (string) $action
        );

    if (
        $action === ''
    ) {

        return 'Activity';
    }

    $action =
        str_replace(
            [
                '_',
                '-'
            ],
            ' ',
            $action
        );

    return ucwords(
        strtolower($action)
    );
}


/**
 * Return action CSS class.
 */
function actionClass(
    $action
): string {

    $value =
        strtolower(
            trim(
                (string) $action
            )
        );

    if (
        strpos(
            $value,
            'delete'
        ) !== false ||
        strpos(
            $value,
            'remove'
        ) !== false
    ) {

        return 'action-danger';
    }


    if (
        strpos(
            $value,
            'login'
        ) !== false
    ) {

        return 'action-login';
    }


    if (
        strpos(
            $value,
            'logout'
        ) !== false
    ) {

        return 'action-logout';
    }


    if (
        strpos(
            $value,
            'create'
        ) !== false ||
        strpos(
            $value,
            'add'
        ) !== false
    ) {

        return 'action-create';
    }


    if (
        strpos(
            $value,
            'update'
        ) !== false ||
        strpos(
            $value,
            'edit'
        ) !== false
    ) {

        return 'action-update';
    }


    if (
        strpos(
            $value,
            'order'
        ) !== false
    ) {

        return 'action-order';
    }


    return 'action-default';
}


/**
 * =========================================================
 * DEFAULT VALUES
 * =========================================================
 */

$activityLogs = [];

$actionOptions = [];

$totalLogs = 0;

$currentPage = 1;

$perPage = 15;

$totalPages = 1;

$activityError = null;

$activityTableExists = false;


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


$actionFilter =
    isset($_GET['action'])
        ? trim(
            (string) $_GET['action']
        )
        : '';


$dateFrom =
    isset($_GET['date_from'])
        ? trim(
            (string) $_GET['date_from']
        )
        : '';


$dateTo =
    isset($_GET['date_to'])
        ? trim(
            (string) $_GET['date_to']
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
     * CHECK ACTIVITY LOG TABLE
     * -----------------------------------------------------
     */

    $activityTableExists =
        tableExists(
            $pdo,
            'activity_logs'
        );


    if (
        !$activityTableExists
    ) {

        $activityError =
            'The activity_logs table was not found in the current database.';

    } else {

        /**
         * -------------------------------------------------
         * DETECT ACTIVITY LOG COLUMNS
         * -------------------------------------------------
         */

        $activityColumns =
            getTableColumns(
                $pdo,
                'activity_logs'
            );


        /**
         * ID
         */
        $idColumn =
            findColumn(
                $activityColumns,
                [
                    'id',
                    'log_id',
                    'activity_id'
                ]
            );


        /**
         * Admin ID
         */
        $adminIdColumn =
            findColumn(
                $activityColumns,
                [
                    'admin_id',
                    'user_id',
                    'administrator_id'
                ]
            );


        /**
         * Action
         */
        $actionColumn =
            findColumn(
                $activityColumns,
                [
                    'action',
                    'activity',
                    'event',
                    'event_type',
                    'action_type'
                ]
            );


        /**
         * Description
         */
        $descriptionColumn =
            findColumn(
                $activityColumns,
                [
                    'description',
                    'details',
                    'message',
                    'activity_description',
                    'log_message'
                ]
            );


        /**
         * IP address
         */
        $ipColumn =
            findColumn(
                $activityColumns,
                [
                    'ip_address',
                    'ip',
                    'client_ip'
                ]
            );


        /**
         * User agent
         */
        $userAgentColumn =
            findColumn(
                $activityColumns,
                [
                    'user_agent',
                    'browser',
                    'agent'
                ]
            );


        /**
         * Created date
         */
        $createdColumn =
            findColumn(
                $activityColumns,
                [
                    'created_at',
                    'created_date',
                    'logged_at',
                    'activity_date',
                    'date',
                    'timestamp'
                ]
            );


        /**
         * -------------------------------------------------
         * BASIC VALIDATION
         * -------------------------------------------------
         *
         * An activity log should have at least an action,
         * description or date column.
         */

        if (
            $actionColumn === null &&
            $descriptionColumn === null &&
            $createdColumn === null
        ) {

            $activityError =
                'The activity_logs table structure could not be recognised.';

        } else {

            /**
             * -------------------------------------------------
             * ADMIN TABLE DETECTION
             * -------------------------------------------------
             */

            $adminsExists =
                tableExists(
                    $pdo,
                    'admins'
                );


            $adminNameColumn = null;

            $adminIdReferenceColumn = null;

            if (
                $adminsExists &&
                $adminIdColumn !== null
            ) {

                $adminColumns =
                    getTableColumns(
                        $pdo,
                        'admins'
                    );


                $adminIdReferenceColumn =
                    findColumn(
                        $adminColumns,
                        [
                            'id',
                            'admin_id',
                            'user_id'
                        ]
                    );


                $adminNameColumn =
                    findColumn(
                        $adminColumns,
                        [
                            'name',
                            'full_name',
                            'admin_name',
                            'username',
                            'email'
                        ]
                    );
            }


            /**
             * -------------------------------------------------
             * ACTION OPTIONS
             * -------------------------------------------------
             */

            if (
                $actionColumn !== null
            ) {

                $actionStmt =
                    $pdo->query(
                        "
                        SELECT DISTINCT
                            " .
                            quoteIdentifier(
                                $actionColumn
                            ) .
                            " AS action_value

                        FROM activity_logs

                        WHERE " .
                            quoteIdentifier(
                                $actionColumn
                            ) .
                            " IS NOT NULL

                        AND TRIM(" .
                            quoteIdentifier(
                                $actionColumn
                            ) .
                            ") <> ''

                        ORDER BY
                            action_value ASC
                        "
                    );


                $actionRows =
                    $actionStmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );


                foreach (
                    $actionRows
                    as $row
                ) {

                    $value =
                        trim(
                            (string) (
                                $row[
                                    'action_value'
                                ] ?? ''
                            )
                        );


                    if (
                        $value !== ''
                    ) {

                        $actionOptions[] =
                            $value;
                    }
                }
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
                    $actionColumn !== null
                ) {

                    $searchParts[] =
                        quoteIdentifier(
                            $actionColumn
                        ) .
                        " LIKE :search";
                }


                if (
                    $descriptionColumn !== null
                ) {

                    $searchParts[] =
                        quoteIdentifier(
                            $descriptionColumn
                        ) .
                        " LIKE :search";
                }


                if (
                    $ipColumn !== null
                ) {

                    $searchParts[] =
                        quoteIdentifier(
                            $ipColumn
                        ) .
                        " LIKE :search";
                }


                if (
                    $userAgentColumn !== null
                ) {

                    $searchParts[] =
                        quoteIdentifier(
                            $userAgentColumn
                        ) .
                        " LIKE :search";
                }


                if (
                    !empty($searchParts)
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
             * Action filter.
             */
            if (
                $actionFilter !== '' &&
                $actionColumn !== null
            ) {

                $whereParts[] =
                    quoteIdentifier(
                        $actionColumn
                    ) .
                    " = :action";


                $queryParams[
                    ':action'
                ] =
                    $actionFilter;
            }


            /**
             * Date from.
             */
            if (
                $dateFrom !== '' &&
                $createdColumn !== null
            ) {

                $whereParts[] =
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    " >= :date_from";


                $queryParams[
                    ':date_from'
                ] =
                    $dateFrom .
                    ' 00:00:00';
            }


            /**
             * Date to.
             */
            if (
                $dateTo !== '' &&
                $createdColumn !== null
            ) {

                $whereParts[] =
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    " <= :date_to";


                $queryParams[
                    ':date_to'
                ] =
                    $dateTo .
                    ' 23:59:59';
            }


            /**
             * Build WHERE.
             */
            $whereSQL = '';

            if (
                !empty($whereParts)
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
             * COUNT TOTAL LOGS
             * -------------------------------------------------
             */

            $countStmt =
                $pdo->prepare(
                    "
                    SELECT COUNT(*)
                    FROM activity_logs
                    {$whereSQL}
                    "
                );


            $countStmt->execute(
                $queryParams
            );


            $totalLogs =
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
                        $totalLogs /
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
             * SELECT COLUMNS
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
                    'al.' .
                    quoteIdentifier(
                        $idColumn
                    ) .
                    ' AS log_id';

            } else {

                $selectParts[] =
                    'NULL AS log_id';
            }


            /**
             * Action.
             */
            if (
                $actionColumn !== null
            ) {

                $selectParts[] =
                    'al.' .
                    quoteIdentifier(
                        $actionColumn
                    ) .
                    ' AS action';

            } else {

                $selectParts[] =
                    "'Activity' AS action";
            }


            /**
             * Description.
             */
            if (
                $descriptionColumn !== null
            ) {

                $selectParts[] =
                    'al.' .
                    quoteIdentifier(
                        $descriptionColumn
                    ) .
                    ' AS description';

            } else {

                $selectParts[] =
                    "'' AS description";
            }


            /**
             * IP.
             */
            if (
                $ipColumn !== null
            ) {

                $selectParts[] =
                    'al.' .
                    quoteIdentifier(
                        $ipColumn
                    ) .
                    ' AS ip_address';

            } else {

                $selectParts[] =
                    "'' AS ip_address";
            }


            /**
             * User agent.
             */
            if (
                $userAgentColumn !== null
            ) {

                $selectParts[] =
                    'al.' .
                    quoteIdentifier(
                        $userAgentColumn
                    ) .
                    ' AS user_agent';

            } else {

                $selectParts[] =
                    "'' AS user_agent";
            }


            /**
             * Created at.
             */
            if (
                $createdColumn !== null
            ) {

                $selectParts[] =
                    'al.' .
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    ' AS created_at';

            } else {

                $selectParts[] =
                    'NULL AS created_at';
            }


            /**
             * Admin name.
             */
            $adminJoinSQL = '';

            if (
                $adminsExists &&
                $adminIdColumn !== null &&
                $adminIdReferenceColumn !== null &&
                $adminNameColumn !== null
            ) {

                $selectParts[] =
                    'adm.' .
                    quoteIdentifier(
                        $adminNameColumn
                    ) .
                    ' AS admin_name';


                $adminJoinSQL =
                    "
                    LEFT JOIN admins adm
                        ON adm." .
                    quoteIdentifier(
                        $adminIdReferenceColumn
                    ) .
                    " = al." .
                    quoteIdentifier(
                        $adminIdColumn
                    ) .
                    "
                    ";

            } else {

                $selectParts[] =
                    "'' AS admin_name";
            }


            /**
             * -------------------------------------------------
             * ORDER BY
             * -------------------------------------------------
             */

            if (
                $createdColumn !== null
            ) {

                $orderSQL =
                    'al.' .
                    quoteIdentifier(
                        $createdColumn
                    ) .
                    ' DESC';

            } elseif (
                $idColumn !== null
            ) {

                $orderSQL =
                    'al.' .
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
             * FETCH ACTIVITY LOGS
             * -------------------------------------------------
             *
             * LIMIT and OFFSET are integers already validated
             * and cast, so they are safely embedded.
             */

            $logsSQL =
                "
                SELECT
                    " .
                    implode(
                        ",
                    ",
                        $selectParts
                    ) . "

                FROM activity_logs al

                {$adminJoinSQL}

                {$whereSQL}

                ORDER BY
                    {$orderSQL}

                LIMIT
                    {$perPage}

                OFFSET
                    {$offset}
                ";


            $logsStmt =
                $pdo->prepare(
                    $logsSQL
                );


            $logsStmt->execute(
                $queryParams
            );


            $activityLogs =
                $logsStmt->fetchAll(
                    PDO::FETCH_ASSOC
                );
        }
    }


} catch (Throwable $e) {

    /**
     * Do not expose database internals.
     */

    $activityError =
        'Activity logs could not be loaded. Please check your database configuration.';

}


/**
 * =========================================================
 * PAGE URL HELPER
 * =========================================================
 */

function buildPageUrl(
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
        isset($_GET['action']) &&
        trim(
            (string) $_GET['action']
        ) !== ''
    ) {

        $params['action'] =
            trim(
                (string) $_GET['action']
            );
    }


    if (
        isset($_GET['date_from']) &&
        trim(
            (string) $_GET['date_from']
        ) !== ''
    ) {

        $params['date_from'] =
            trim(
                (string) $_GET['date_from']
            );
    }


    if (
        isset($_GET['date_to']) &&
        trim(
            (string) $_GET['date_to']
        ) !== ''
    ) {

        $params['date_to'] =
            trim(
                (string) $_GET['date_to']
            );
    }


    $params['page'] =
        $page;


    return
        'activity-logs.php?' .
        http_build_query(
            $params
        );
}


/**
 * =========================================================
 * DISPLAY RANGE
 * =========================================================
 */

$displayStart = 0;

$displayEnd = 0;


if (
    $totalLogs > 0
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
            $totalLogs
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
        content="Ssrini Handcrafts Admin Activity Logs"
    >

    <title>
        <?= e($pageTitle) ?>
        | Ssrini Handcrafts
    </title>


    <!-- =====================================================
         EXISTING ADMIN CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >


    <!-- =====================================================
         ACTIVITY LOG PAGE STYLES
         ===================================================== -->

    <style>

        /* =====================================================
           PAGE
           ===================================================== */

        .activity-page {

            display: flex;

            flex-direction: column;

            gap: 20px;

        }


        /* =====================================================
           FILTER CARD
           ===================================================== */

        .activity-filter-card {

            background: #ffffff;

            border: 1px solid #eeeaf2;

            border-radius: 16px;

            padding: 18px 20px;

            box-shadow:
                0 7px 22px
                rgba(30, 20, 50, 0.05);

        }


        .activity-filter-grid {

            display: grid;

            grid-template-columns:
                minmax(220px, 2fr)
                minmax(160px, 1fr)
                minmax(150px, 1fr)
                minmax(150px, 1fr)
                auto;

            gap: 12px;

            align-items: end;

        }


        .activity-filter-group {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .activity-filter-label {

            font-size: 11px;

            font-weight: 600;

            color: #77717f;

        }


        .activity-filter-input,

        .activity-filter-select {

            width: 100%;

            height: 42px;

            border: 1px solid #e3dfea;

            border-radius: 10px;

            background: #ffffff;

            color: #25212d;

            padding:
                0 12px;

            outline: none;

            font-size: 12px;

        }


        .activity-filter-input:focus,

        .activity-filter-select:focus {

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


        .activity-filter-actions {

            display: flex;

            gap: 8px;

        }


        .activity-filter-button {

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


        .activity-clear-button {

            height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                0 15px;

            border-radius: 10px;

            border: 1px solid #e3dfea;

            background: #ffffff;

            color: #625b6b;

            font-size: 12px;

            font-weight: 600;

            text-decoration: none;

            white-space: nowrap;

        }


        .activity-clear-button:hover {

            background: #faf8fc;

        }


        /* =====================================================
           SUMMARY
           ===================================================== */

        .activity-summary {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;

        }


        .activity-summary-text {

            color: #77717f;

            font-size: 12px;

        }


        .activity-summary-text strong {

            color: #25212d;

        }


        .activity-summary-refresh {

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


        /* =====================================================
           LOG TABLE CARD
           ===================================================== */

        .activity-card {

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


        .activity-card-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .activity-card-title {

            font-size: 15px;

            font-weight: 700;

            color: #25212d;

        }


        .activity-card-description {

            margin-top: 4px;

            font-size: 11px;

            color: #9a94a2;

        }


        .activity-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .activity-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 950px;

        }


        .activity-table th {

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


        .activity-table td {

            padding:
                14px 16px;

            border-bottom:
                1px solid #f1eef4;

            vertical-align: middle;

            color: #5f5865;

            font-size: 11px;

        }


        .activity-table tbody tr {

            transition:
                background 0.2s ease;

        }


        .activity-table tbody tr:hover {

            background: #fcfbfd;

        }


        .activity-table tbody tr:last-child td {

            border-bottom: none;

        }


        /* =====================================================
           LOG ID
           ===================================================== */

        .activity-id {

            color: #9a94a2;

            font-size: 10px;

            font-weight: 600;

        }


        /* =====================================================
           ACTION
           ===================================================== */

        .activity-action-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 27px;

            padding:
                4px 9px;

            border-radius: 8px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;

        }


        .action-default {

            background: #f1eafd;

            color: #7627c9;

        }


        .action-create {

            background: #e8f8ef;

            color: #23804c;

        }


        .action-update {

            background: #fff4df;

            color: #a56a00;

        }


        .action-danger {

            background: #fff0f0;

            color: #c53030;

        }


        .action-login {

            background: #eaf4ff;

            color: #2369a8;

        }


        .action-logout {

            background: #f1eef3;

            color: #69616f;

        }


        .action-order {

            background: #f4eaff;

            color: #7b38a9;

        }


        /* =====================================================
           DESCRIPTION
           ===================================================== */

        .activity-description {

            max-width: 380px;

            color: #4f4857;

            line-height: 1.5;

        }


        .activity-description-text {

            display: -webkit-box;

            -webkit-line-clamp: 2;

            -webkit-box-orient: vertical;

            overflow: hidden;

        }


        /* =====================================================
           ADMIN
           ===================================================== */

        .activity-admin {

            display: flex;

            align-items: center;

            gap: 9px;

            min-width: 130px;

        }


        .activity-admin-avatar {

            width: 30px;

            height: 30px;

            border-radius: 9px;

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


        .activity-admin-name {

            font-size: 11px;

            color: #403a47;

            font-weight: 600;

            max-width: 130px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .activity-admin-empty {

            color: #aaa3b0;

            font-size: 11px;

        }


        /* =====================================================
           IP
           ===================================================== */

        .activity-ip {

            font-family:
                Consolas,
                Monaco,
                monospace;

            font-size: 10px;

            color: #706976;

            white-space: nowrap;

        }


        /* =====================================================
           DATE
           ===================================================== */

        .activity-date {

            white-space: nowrap;

        }


        .activity-date-main {

            color: #4f4857;

            font-weight: 600;

            font-size: 10px;

        }


        .activity-date-time {

            margin-top: 3px;

            color: #aaa3b0;

            font-size: 9px;

        }


        /* =====================================================
           DETAILS BUTTON
           ===================================================== */

        .activity-details-button {

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


        .activity-details-button:hover {

            border-color: #cdb7df;

            color: #7627c9;

            background: #faf7fd;

        }


        /* =====================================================
           EMPTY STATE
           ===================================================== */

        .activity-empty {

            padding:
                65px 20px;

            text-align: center;

            color: #9a94a2;

        }


        .activity-empty-icon {

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


        .activity-empty-title {

            color: #25212d;

            font-size: 14px;

            font-weight: 700;

        }


        .activity-empty-description {

            margin-top: 6px;

            font-size: 11px;

            line-height: 1.5;

        }


        /* =====================================================
           ERROR
           ===================================================== */

        .activity-error {

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

        .activity-pagination {

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


        .activity-pagination-info {

            color: #9a94a2;

            font-size: 10px;

        }


        .activity-pagination-links {

            display: flex;

            align-items: center;

            gap: 5px;

        }


        .activity-page-link {

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


        .activity-page-link:hover {

            border-color: #cdb7df;

            color: #7627c9;

        }


        .activity-page-link.active {

            border-color: #7627c9;

            background:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            color: #ffffff;

        }


        .activity-page-link.disabled {

            opacity: 0.45;

            pointer-events: none;

        }


        .activity-page-dots {

            min-width: 25px;

            text-align: center;

            color: #aaa3b0;

            font-size: 10px;

        }


        /* =====================================================
           MODAL
           ===================================================== */

        .activity-modal-overlay {

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


        .activity-modal-overlay.active {

            display: flex;

        }


        .activity-modal {

            width: min(
                620px,
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


        .activity-modal-header {

            padding:
                18px 20px;

            border-bottom:
                1px solid #eeeaf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .activity-modal-title {

            font-size: 15px;

            font-weight: 700;

            color: #25212d;

        }


        .activity-modal-close {

            width: 32px;

            height: 32px;

            border: none;

            border-radius: 8px;

            background: #f4f1f6;

            color: #6c6473;

            cursor: pointer;

            font-size: 17px;

        }


        .activity-modal-body {

            padding:
                20px;

        }


        .activity-detail-grid {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 14px;

        }


        .activity-detail-item {

            padding:
                13px;

            border:
                1px solid #eeeaf2;

            border-radius: 10px;

            background: #fcfbfd;

        }


        .activity-detail-item.full {

            grid-column: 1 / -1;

        }


        .activity-detail-label {

            color: #9a94a2;

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 0.05em;

            margin-bottom: 6px;

        }


        .activity-detail-value {

            color: #403a47;

            font-size: 11px;

            line-height: 1.55;

            word-break: break-word;

        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 1150px) {

            .activity-filter-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

            }


            .activity-filter-actions {

                grid-column: 1 / -1;

            }

        }


        @media (max-width: 700px) {

            .activity-filter-grid {

                grid-template-columns: 1fr;

            }


            .activity-filter-actions {

                grid-column: auto;

                width: 100%;

            }


            .activity-filter-button,

            .activity-clear-button {

                flex: 1;

            }


            .activity-summary {

                align-items: flex-start;

                flex-direction: column;

            }


            .activity-detail-grid {

                grid-template-columns: 1fr;

            }


            .activity-detail-item.full {

                grid-column: auto;

            }


            .activity-pagination {

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

                        Activity Logs

                    </h1>


                    <p class="page-description">

                        Monitor administrator actions and
                        important changes made in the store.

                    </p>

                </div>


                <div>

                    <a
                        href="<?= e(
                            buildPageUrl(
                                $currentPage
                            )
                        ) ?>"
                        class="btn btn-primary"
                    >

                        🔄

                        Refresh Logs

                    </a>

                </div>


            </section>


            <!-- =================================================
                 ACTIVITY PAGE
                 ================================================= -->

            <div class="activity-page">


                <!-- =================================================
                     ERROR
                     ================================================= -->

                <?php if (
                    $activityError !== null
                ): ?>

                    <div class="activity-error">

                        <?= e(
                            $activityError
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     FILTER
                     ================================================= -->

                <?php if (
                    $activityTableExists
                ): ?>

                    <form
                        method="GET"
                        action="activity-logs.php"
                        class="activity-filter-card"
                    >

                        <div
                            class="activity-filter-grid"
                        >


                            <!-- SEARCH -->

                            <div
                                class="activity-filter-group"
                            >

                                <label
                                    class="activity-filter-label"
                                    for="activitySearch"
                                >

                                    Search Activity

                                </label>


                                <input
                                    type="search"
                                    id="activitySearch"
                                    name="search"
                                    class="activity-filter-input"
                                    value="<?= e(
                                        $search
                                    ) ?>"
                                    placeholder="Search action, description or IP..."
                                >

                            </div>


                            <!-- ACTION -->

                            <div
                                class="activity-filter-group"
                            >

                                <label
                                    class="activity-filter-label"
                                    for="activityAction"
                                >

                                    Action

                                </label>


                                <select
                                    id="activityAction"
                                    name="action"
                                    class="activity-filter-select"
                                >

                                    <option value="">

                                        All Actions

                                    </option>


                                    <?php foreach (
                                        $actionOptions
                                        as $option
                                    ): ?>

                                        <option
                                            value="<?= e(
                                                $option
                                            ) ?>"
                                            <?= $actionFilter === $option
                                                ? 'selected'
                                                : '' ?>
                                        >

                                            <?= e(
                                                formatActionLabel(
                                                    $option
                                                )
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- DATE FROM -->

                            <div
                                class="activity-filter-group"
                            >

                                <label
                                    class="activity-filter-label"
                                    for="activityDateFrom"
                                >

                                    From Date

                                </label>


                                <input
                                    type="date"
                                    id="activityDateFrom"
                                    name="date_from"
                                    class="activity-filter-input"
                                    value="<?= e(
                                        $dateFrom
                                    ) ?>"
                                >

                            </div>


                            <!-- DATE TO -->

                            <div
                                class="activity-filter-group"
                            >

                                <label
                                    class="activity-filter-label"
                                    for="activityDateTo"
                                >

                                    To Date

                                </label>


                                <input
                                    type="date"
                                    id="activityDateTo"
                                    name="date_to"
                                    class="activity-filter-input"
                                    value="<?= e(
                                        $dateTo
                                    ) ?>"
                                >

                            </div>


                            <!-- BUTTONS -->

                            <div
                                class="activity-filter-actions"
                            >

                                <button
                                    type="submit"
                                    class="activity-filter-button"
                                >

                                    Apply Filter

                                </button>


                                <a
                                    href="activity-logs.php"
                                    class="activity-clear-button"
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
                        class="activity-summary"
                    >


                        <div
                            class="activity-summary-text"
                        >

                            <?php if (
                                $totalLogs > 0
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
                                        $totalLogs
                                    ) ?>
                                </strong>

                                activity logs.

                            <?php else: ?>

                                No activity logs found.

                            <?php endif; ?>

                        </div>


                        <a
                            href="<?= e(
                                buildPageUrl(
                                    $currentPage
                                )
                            ) ?>"
                            class="activity-summary-refresh"
                        >

                            🔄

                            Refresh

                        </a>


                    </div>


                    <!-- =================================================
                         LOG CARD
                         ================================================= -->

                    <section
                        class="activity-card"
                    >


                        <div
                            class="activity-card-header"
                        >

                            <div>

                                <div
                                    class="activity-card-title"
                                >

                                    Administrator Activity

                                </div>


                                <div
                                    class="activity-card-description"
                                >

                                    Recent actions performed
                                    inside the admin panel.

                                </div>

                            </div>


                            <div
                                style="
                                    font-size: 11px;
                                    color: #9a94a2;
                                "
                            >

                                <?= e(
                                    $totalLogs
                                ) ?>

                                records

                            </div>

                        </div>


                        <?php if (
                            !empty(
                                $activityLogs
                            )
                        ): ?>


                            <div
                                class="activity-table-wrapper"
                            >

                                <table
                                    class="activity-table"
                                >

                                    <thead>

                                        <tr>

                                            <th>
                                                ID
                                            </th>

                                            <th>
                                                Action
                                            </th>

                                            <th>
                                                Description
                                            </th>

                                            <th>
                                                Admin
                                            </th>

                                            <th>
                                                IP Address
                                            </th>

                                            <th>
                                                Date & Time
                                            </th>

                                            <th>
                                                Details
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody>


                                        <?php foreach (
                                            $activityLogs
                                            as $log
                                        ): ?>


                                            <?php

                                            $action =
                                                trim(
                                                    (string) (
                                                        $log[
                                                            'action'
                                                        ] ??
                                                        'Activity'
                                                    )
                                                );


                                            if (
                                                $action === ''
                                            ) {

                                                $action =
                                                    'Activity';
                                            }


                                            $description =
                                                trim(
                                                    (string) (
                                                        $log[
                                                            'description'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $adminName =
                                                trim(
                                                    (string) (
                                                        $log[
                                                            'admin_name'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $ipAddress =
                                                trim(
                                                    (string) (
                                                        $log[
                                                            'ip_address'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $userAgent =
                                                trim(
                                                    (string) (
                                                        $log[
                                                            'user_agent'
                                                        ] ??
                                                        ''
                                                    )
                                                );


                                            $createdAt =
                                                $log[
                                                    'created_at'
                                                ] ??
                                                '';


                                            $logId =
                                                $log[
                                                    'log_id'
                                                ] ??
                                                '';

                                            ?>


                                            <tr>


                                                <!-- ID -->

                                                <td>

                                                    <span
                                                        class="activity-id"
                                                    >

                                                        #<?= e(
                                                            $logId !== ''
                                                                ? $logId
                                                                : '—'
                                                        ) ?>

                                                    </span>

                                                </td>


                                                <!-- ACTION -->

                                                <td>

                                                    <span
                                                        class="activity-action-badge <?= e(
                                                            actionClass(
                                                                $action
                                                            )
                                                        ) ?>"
                                                    >

                                                        <?= e(
                                                            formatActionLabel(
                                                                $action
                                                            )
                                                        ) ?>

                                                    </span>

                                                </td>


                                                <!-- DESCRIPTION -->

                                                <td>

                                                    <div
                                                        class="activity-description"
                                                        title="<?= e(
                                                            $description
                                                        ) ?>"
                                                    >

                                                        <div
                                                            class="activity-description-text"
                                                        >

                                                            <?= e(
                                                                $description !== ''
                                                                    ? $description
                                                                    : 'No description available.'
                                                            ) ?>

                                                        </div>

                                                    </div>

                                                </td>


                                                <!-- ADMIN -->

                                                <td>

                                                    <?php if (
                                                        $adminName !== ''
                                                    ): ?>


                                                        <div
                                                            class="activity-admin"
                                                        >

                                                            <div
                                                                class="activity-admin-avatar"
                                                            >

                                                                <?= e(
                                                                    strtoupper(
                                                                        substr(
                                                                            trim(
                                                                                $adminName
                                                                            ),
                                                                            0,
                                                                            1
                                                                        )
                                                                    )
                                                                ) ?>

                                                            </div>


                                                            <div
                                                                class="activity-admin-name"
                                                                title="<?= e(
                                                                    $adminName
                                                                ) ?>"
                                                            >

                                                                <?= e(
                                                                    $adminName
                                                                ) ?>

                                                            </div>

                                                        </div>


                                                    <?php else: ?>

                                                        <span
                                                            class="activity-admin-empty"
                                                        >

                                                            System / Admin

                                                        </span>

                                                    <?php endif; ?>

                                                </td>


                                                <!-- IP -->

                                                <td>

                                                    <span
                                                        class="activity-ip"
                                                    >

                                                        <?= e(
                                                            $ipAddress !== ''
                                                                ? $ipAddress
                                                                : '—'
                                                        ) ?>

                                                    </span>

                                                </td>


                                                <!-- DATE -->

                                                <td>

                                                    <div
                                                        class="activity-date"
                                                    >

                                                        <?php

                                                        $dateTimestamp =
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
                                                            $dateTimestamp !== false
                                                        ): ?>

                                                            <div
                                                                class="activity-date-main"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'd M Y',
                                                                        $dateTimestamp
                                                                    )
                                                                ) ?>

                                                            </div>


                                                            <div
                                                                class="activity-date-time"
                                                            >

                                                                <?= e(
                                                                    date(
                                                                        'h:i A',
                                                                        $dateTimestamp
                                                                    )
                                                                ) ?>

                                                            </div>

                                                        <?php else: ?>

                                                            <span
                                                                class="activity-date-time"
                                                            >

                                                                —

                                                            </span>

                                                        <?php endif; ?>

                                                    </div>

                                                </td>


                                                <!-- DETAILS -->

                                                <td>

                                                    <button
                                                        type="button"
                                                        class="activity-details-button"
                                                        data-action="<?= e(
                                                            formatActionLabel(
                                                                $action
                                                            )
                                                        ) ?>"
                                                        data-description="<?= e(
                                                            $description
                                                        ) ?>"
                                                        data-admin="<?= e(
                                                            $adminName !== ''
                                                                ? $adminName
                                                                : 'System / Admin'
                                                        ) ?>"
                                                        data-ip="<?= e(
                                                            $ipAddress !== ''
                                                                ? $ipAddress
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-user-agent="<?= e(
                                                            $userAgent !== ''
                                                                ? $userAgent
                                                                : 'Not available'
                                                        ) ?>"
                                                        data-date="<?= e(
                                                            formatDateTime(
                                                                $createdAt
                                                            )
                                                        ) ?>"
                                                        data-log-id="<?= e(
                                                            $logId !== ''
                                                                ? $logId
                                                                : 'Not available'
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
                                    class="activity-pagination"
                                >


                                    <div
                                        class="activity-pagination-info"
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
                                        class="activity-pagination-links"
                                    >


                                        <!-- PREVIOUS -->

                                        <a
                                            href="<?= e(
                                                buildPageUrl(
                                                    max(
                                                        1,
                                                        $currentPage - 1
                                                    )
                                                )
                                            ) ?>"
                                            class="
                                                activity-page-link
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
                                                    buildPageUrl(
                                                        1
                                                    )
                                                ) ?>"
                                                class="activity-page-link"
                                            >

                                                1

                                            </a>


                                            <?php if (
                                                $paginationStart > 2
                                            ): ?>

                                                <span
                                                    class="activity-page-dots"
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
                                                    buildPageUrl(
                                                        $page
                                                    )
                                                ) ?>"
                                                class="
                                                    activity-page-link
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
                                                    class="activity-page-dots"
                                                >

                                                    ...

                                                </span>

                                            <?php endif; ?>


                                            <a
                                                href="<?= e(
                                                    buildPageUrl(
                                                        $totalPages
                                                    )
                                                ) ?>"
                                                class="activity-page-link"
                                            >

                                                <?= e(
                                                    $totalPages
                                                ) ?>

                                            </a>

                                        <?php endif; ?>


                                        <!-- NEXT -->

                                        <a
                                            href="<?= e(
                                                buildPageUrl(
                                                    min(
                                                        $totalPages,
                                                        $currentPage + 1
                                                    )
                                                )
                                            ) ?>"
                                            class="
                                                activity-page-link
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
                                class="activity-empty"
                            >

                                <div
                                    class="activity-empty-icon"
                                >

                                    📝

                                </div>


                                <div
                                    class="activity-empty-title"
                                >

                                    No Activity Logs Found

                                </div>


                                <div
                                    class="activity-empty-description"
                                >

                                    <?php if (
                                        $search !== '' ||
                                        $actionFilter !== '' ||
                                        $dateFrom !== '' ||
                                        $dateTo !== ''
                                    ): ?>

                                        No activity logs match
                                        your current filters.
                                        Try clearing the filters
                                        and search again.

                                    <?php else: ?>

                                        Administrator activity
                                        will appear here when
                                        actions are recorded.

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
                        class="activity-card"
                    >

                        <div
                            class="activity-empty"
                        >

                            <div
                                class="activity-empty-icon"
                            >

                                📋

                            </div>


                            <div
                                class="activity-empty-title"
                            >

                                Activity Log Table Not Available

                            </div>


                            <div
                                class="activity-empty-description"
                            >

                                The admin activity log page
                                is ready, but the
                                <strong>
                                    activity_logs
                                </strong>
                                table is not available
                                in the current database.

                            </div>

                        </div>

                    </section>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     ACTIVITY DETAILS MODAL
     ===================================================== -->

<div
    class="activity-modal-overlay"
    id="activityDetailsModal"
    aria-hidden="true"
>


    <div
        class="activity-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="activityModalTitle"
    >


        <!-- MODAL HEADER -->

        <div
            class="activity-modal-header"
        >

            <div>

                <div
                    class="activity-modal-title"
                    id="activityModalTitle"
                >

                    Activity Details

                </div>

            </div>


            <button
                type="button"
                class="activity-modal-close"
                id="closeActivityModal"
                aria-label="Close"
            >

                ×

            </button>

        </div>


        <!-- MODAL BODY -->

        <div
            class="activity-modal-body"
        >


            <div
                class="activity-detail-grid"
            >


                <!-- LOG ID -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        Log ID

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalLogId"
                    >

                        —

                    </div>

                </div>


                <!-- ACTION -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        Action

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalAction"
                    >

                        —

                    </div>

                </div>


                <!-- ADMIN -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        Administrator

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalAdmin"
                    >

                        —

                    </div>

                </div>


                <!-- DATE -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        Date & Time

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalDate"
                    >

                        —

                    </div>

                </div>


                <!-- IP -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        IP Address

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalIp"
                    >

                        —

                    </div>

                </div>


                <!-- USER AGENT -->

                <div
                    class="activity-detail-item"
                >

                    <div
                        class="activity-detail-label"
                    >

                        User Agent

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalUserAgent"
                    >

                        —

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div
                    class="
                        activity-detail-item
                        full
                    "
                >

                    <div
                        class="activity-detail-label"
                    >

                        Description

                    </div>


                    <div
                        class="activity-detail-value"
                        id="modalDescription"
                    >

                        —

                    </div>

                </div>


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
     ACTIVITY LOG JAVASCRIPT
     ===================================================== -->

<script>

    /*
    |--------------------------------------------------------------------------
    | DOM ELEMENTS
    |--------------------------------------------------------------------------
    */

    const activityModal =
        document.getElementById(
            'activityDetailsModal'
        );


    const closeActivityModalButton =
        document.getElementById(
            'closeActivityModal'
        );


    const modalLogId =
        document.getElementById(
            'modalLogId'
        );


    const modalAction =
        document.getElementById(
            'modalAction'
        );


    const modalAdmin =
        document.getElementById(
            'modalAdmin'
        );


    const modalDate =
        document.getElementById(
            'modalDate'
        );


    const modalIp =
        document.getElementById(
            'modalIp'
        );


    const modalUserAgent =
        document.getElementById(
            'modalUserAgent'
        );


    const modalDescription =
        document.getElementById(
            'modalDescription'
        );


    /*
    |--------------------------------------------------------------------------
    | OPEN ACTIVITY DETAILS
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '.activity-details-button'
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        modalLogId.textContent =
                            this.dataset.logId ||
                            'Not available';


                        modalAction.textContent =
                            this.dataset.action ||
                            'Not available';


                        modalAdmin.textContent =
                            this.dataset.admin ||
                            'System / Admin';


                        modalDate.textContent =
                            this.dataset.date ||
                            'Not available';


                        modalIp.textContent =
                            this.dataset.ip ||
                            'Not available';


                        modalUserAgent.textContent =
                            this.dataset.userAgent ||
                            'Not available';


                        modalDescription.textContent =
                            this.dataset.description ||
                            'No description available.';


                        activityModal.classList.add(
                            'active'
                        );


                        activityModal.setAttribute(
                            'aria-hidden',
                            'false'
                        );

                    }
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    function closeActivityModal() {

        if (
            !activityModal
        ) {

            return;
        }


        activityModal.classList.remove(
            'active'
        );


        activityModal.setAttribute(
            'aria-hidden',
            'true'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    if (
        closeActivityModalButton
    ) {

        closeActivityModalButton.addEventListener(
            'click',
            closeActivityModal
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    if (
        activityModal
    ) {

        activityModal.addEventListener(
            'click',
            function (event) {

                if (
                    event.target ===
                    activityModal
                ) {

                    closeActivityModal();

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

                closeActivityModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DATE VALIDATION
    |--------------------------------------------------------------------------
    */

    const dateFromInput =
        document.getElementById(
            'activityDateFrom'
        );


    const dateToInput =
        document.getElementById(
            'activityDateTo'
        );


    if (
        dateFromInput &&
        dateToInput
    ) {

        dateFromInput.addEventListener(
            'change',
            function () {

                if (
                    dateToInput.value !== '' &&
                    this.value !== '' &&
                    dateToInput.value <
                    this.value
                ) {

                    dateToInput.value =
                        this.value;

                }

            }
        );


        dateToInput.addEventListener(
            'change',
            function () {

                if (
                    dateFromInput.value !== '' &&
                    this.value !== '' &&
                    this.value <
                    dateFromInput.value
                ) {

                    dateFromInput.value =
                        this.value;

                }

            }
        );

    }

</script>


</body>

</html>