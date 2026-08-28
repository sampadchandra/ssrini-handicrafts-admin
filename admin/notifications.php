<?php

/**
 * ============================================================
 * SSRINI HANDICRAFTS - NOTIFICATIONS MANAGEMENT
 * ============================================================
 *
 * File:
 *     notifications.php
 *
 * Database:
 *     ssrini_handcrafts
 *
 * Table:
 *     notifications
 *
 * Expected columns:
 *     id
 *     title
 *     message
 *     type
 *     is_read
 *     created_at
 *
 * Features:
 *     - View all notifications
 *     - Search notifications
 *     - Filter All / Unread / Read
 *     - Filter notification type
 *     - Mark notification as read
 *     - Mark notification as unread
 *     - Mark all as read
 *     - Delete notification
 *     - Delete all read notifications
 *     - Responsive UI
 *     - CSRF protection
 *     - PDO prepared statements
 *
 * ============================================================
 */


/*
|--------------------------------------------------------------------------
| DATABASE + AUTHENTICATION
|--------------------------------------------------------------------------
*/



require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();


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
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
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


function redirectToNotifications()
{
    header('Location: notifications.php');
    exit;
}


function setFlash($type, $message)
{
    $_SESSION['notification_flash'] = [
        'type' => $type,
        'message' => $message
    ];
}


function getFlash()
{
    if (!isset($_SESSION['notification_flash'])) {
        return null;
    }

    $flash = $_SESSION['notification_flash'];

    unset($_SESSION['notification_flash']);

    return $flash;
}


/*
|--------------------------------------------------------------------------
| VERIFY CSRF
|--------------------------------------------------------------------------
*/

function verifyCsrf($token)
{
    return isset($_SESSION['csrf_token'])
        && hash_equals(
            $_SESSION['csrf_token'],
            (string) $token
        );
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatNotificationDate($date)
{
    if (empty($date)) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d M Y, h:i A', $timestamp);
}


/*
|--------------------------------------------------------------------------
| GET NOTIFICATION ICON
|--------------------------------------------------------------------------
*/

function getNotificationIcon($type)
{
    $type = strtolower(trim((string) $type));

    switch ($type) {

        case 'order':
        case 'new_order':
            return 'fa-cart-shopping';

        case 'review':
            return 'fa-star';

        case 'customer':
        case 'user':
            return 'fa-user';

        case 'product':
            return 'fa-box';

        case 'stock':
        case 'low_stock':
            return 'fa-triangle-exclamation';

        case 'payment':
            return 'fa-credit-card';

        case 'success':
            return 'fa-circle-check';

        case 'warning':
            return 'fa-triangle-exclamation';

        case 'error':
            return 'fa-circle-xmark';

        default:
            return 'fa-bell';
    }
}


/*
|--------------------------------------------------------------------------
| GET NOTIFICATION TYPE LABEL
|--------------------------------------------------------------------------
*/

function getNotificationTypeLabel($type)
{
    $type = trim((string) $type);

    if ($type === '') {
        return 'General';
    }

    $type = str_replace(
        ['_', '-'],
        ' ',
        $type
    );

    return ucwords($type);
}


/*
|--------------------------------------------------------------------------
| GET NOTIFICATION TYPE CLASS
|--------------------------------------------------------------------------
*/

function getNotificationTypeClass($type)
{
    $type = strtolower(trim((string) $type));

    switch ($type) {

        case 'order':
        case 'new_order':
            return 'type-order';

        case 'review':
            return 'type-review';

        case 'customer':
        case 'user':
            return 'type-customer';

        case 'product':
            return 'type-product';

        case 'stock':
        case 'low_stock':
            return 'type-stock';

        case 'payment':
            return 'type-payment';

        case 'success':
            return 'type-success';

        case 'warning':
            return 'type-warning';

        case 'error':
            return 'type-error';

        default:
            return 'type-general';
    }
}


/*
|--------------------------------------------------------------------------
| PROCESS ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($token)) {

        setFlash(
            'error',
            'Security verification failed. Please try again.'
        );

        redirectToNotifications();
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | MARK SINGLE NOTIFICATION AS READ
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_read') {

            $notificationId = (int) ($_POST['notification_id'] ?? 0);

            if ($notificationId <= 0) {

                setFlash(
                    'error',
                    'Invalid notification.'
                );

                redirectToNotifications();
            }


            $stmt = $pdo->prepare(
                "
                UPDATE notifications
                SET is_read = 1
                WHERE id = :id
                LIMIT 1
                "
            );

            $stmt->execute([
                ':id' => $notificationId
            ]);


            setFlash(
                'success',
                'Notification marked as read.'
            );

            redirectToNotifications();
        }


        /*
        |--------------------------------------------------------------------------
        | MARK SINGLE NOTIFICATION AS UNREAD
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_unread') {

            $notificationId = (int) ($_POST['notification_id'] ?? 0);

            if ($notificationId <= 0) {

                setFlash(
                    'error',
                    'Invalid notification.'
                );

                redirectToNotifications();
            }


            $stmt = $pdo->prepare(
                "
                UPDATE notifications
                SET is_read = 0
                WHERE id = :id
                LIMIT 1
                "
            );

            $stmt->execute([
                ':id' => $notificationId
            ]);


            setFlash(
                'success',
                'Notification marked as unread.'
            );

            redirectToNotifications();
        }


        /*
        |--------------------------------------------------------------------------
        | MARK ALL AS READ
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_all_read') {

            $stmt = $pdo->prepare(
                "
                UPDATE notifications
                SET is_read = 1
                WHERE is_read = 0
                "
            );

            $stmt->execute();


            setFlash(
                'success',
                'All notifications marked as read.'
            );

            redirectToNotifications();
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE SINGLE NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if ($action === 'delete') {

            $notificationId = (int) ($_POST['notification_id'] ?? 0);

            if ($notificationId <= 0) {

                setFlash(
                    'error',
                    'Invalid notification.'
                );

                redirectToNotifications();
            }


            $stmt = $pdo->prepare(
                "
                DELETE FROM notifications
                WHERE id = :id
                LIMIT 1
                "
            );

            $stmt->execute([
                ':id' => $notificationId
            ]);


            setFlash(
                'success',
                'Notification deleted successfully.'
            );

            redirectToNotifications();
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE ALL READ NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        if ($action === 'delete_read') {

            $stmt = $pdo->prepare(
                "
                DELETE FROM notifications
                WHERE is_read = 1
                "
            );

            $stmt->execute();


            setFlash(
                'success',
                'All read notifications deleted.'
            );

            redirectToNotifications();
        }


        /*
        |--------------------------------------------------------------------------
        | UNKNOWN ACTION
        |--------------------------------------------------------------------------
        */

        setFlash(
            'error',
            'Invalid action.'
        );

        redirectToNotifications();


    } catch (PDOException $e) {

        setFlash(
            'error',
            'Database operation failed. Please try again.'
        );

        redirectToNotifications();
    }
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$statusFilter = $_GET['status'] ?? 'all';

$typeFilter = $_GET['type'] ?? 'all';


/*
|--------------------------------------------------------------------------
| NORMALIZE STATUS
|--------------------------------------------------------------------------
*/

$allowedStatuses = [
    'all',
    'unread',
    'read'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}


/*
|--------------------------------------------------------------------------
| GET NOTIFICATION TYPES
|--------------------------------------------------------------------------
*/

$notificationTypes = [];

try {

    $typeQuery = $pdo->query(
        "
        SELECT DISTINCT type
        FROM notifications
        WHERE type IS NOT NULL
          AND TRIM(type) <> ''
        ORDER BY type ASC
        "
    );

    $notificationTypes = $typeQuery->fetchAll(
        PDO::FETCH_COLUMN
    );

} catch (PDOException $e) {

    $notificationTypes = [];
}


/*
|--------------------------------------------------------------------------
| BUILD NOTIFICATION QUERY
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

    $where[] = "
        (
            title LIKE :search
            OR message LIKE :search
            OR type LIKE :search
        )
    ";

    $params[':search'] = '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| READ / UNREAD FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter === 'unread') {

    $where[] = "is_read = 0";

}

elseif ($statusFilter === 'read') {

    $where[] = "is_read = 1";
}


/*
|--------------------------------------------------------------------------
| TYPE FILTER
|--------------------------------------------------------------------------
*/

if ($typeFilter !== 'all') {

    $where[] = "type = :type";

    $params[':type'] = $typeFilter;
}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSql = '';

if (!empty($where)) {

    $whereSql = 'WHERE ' . implode(
        ' AND ',
        $where
    );
}


/*
|--------------------------------------------------------------------------
| FETCH NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$notifications = [];

$totalNotifications = 0;

$unreadNotifications = 0;

$readNotifications = 0;


try {

    /*
    |--------------------------------------------------------------------------
    | TOTAL COUNT
    |--------------------------------------------------------------------------
    */

    $countStmt = $pdo->prepare(
        "
        SELECT COUNT(*)
        FROM notifications
        $whereSql
        "
    );

    $countStmt->execute($params);

    $totalNotifications = (int) $countStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | UNREAD COUNT
    |--------------------------------------------------------------------------
    */

    $unreadStmt = $pdo->query(
        "
        SELECT COUNT(*)
        FROM notifications
        WHERE is_read = 0
        "
    );

    $unreadNotifications = (int) $unreadStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | READ COUNT
    |--------------------------------------------------------------------------
    */

    $readStmt = $pdo->query(
        "
        SELECT COUNT(*)
        FROM notifications
        WHERE is_read = 1
        "
    );

    $readNotifications = (int) $readStmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | FETCH DATA
    |--------------------------------------------------------------------------
    */

    $notificationStmt = $pdo->prepare(
        "
        SELECT
            id,
            title,
            message,
            type,
            is_read,
            created_at
        FROM notifications
        $whereSql
        ORDER BY created_at DESC, id DESC
        "
    );

    $notificationStmt->execute($params);

    $notifications = $notificationStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    $notifications = [];

    $databaseError =
        'Unable to load notifications. Please check the notifications table and database connection.';
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$flash = getFlash();

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
        Notifications | Ssrini Handicrafts
    </title>


    <!-- GOOGLE FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <style>

        /*
        ==========================================================
        GLOBAL
        ==========================================================
        */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --primary: #7627c9;

            --primary-dark: #5d1ba5;

            --secondary: #c52b9f;

            --gradient:
                linear-gradient(
                    135deg,
                    #7627c9,
                    #c52b9f
                );

            --bg:
                #f7f5fb;

            --card:
                #ffffff;

            --text:
                #25212d;

            --muted:
                #77717f;

            --border:
                #e8e3ef;

            --success:
                #168a54;

            --success-bg:
                #eaf8f0;

            --danger:
                #d33c55;

            --danger-bg:
                #fff0f2;

            --warning:
                #c47a00;

            --warning-bg:
                #fff7e6;

            --info:
                #3c64d8;

            --info-bg:
                #eef3ff;

            --shadow:
                0 8px 28px
                rgba(30, 20, 50, 0.07);

            --shadow-hover:
                0 14px 34px
                rgba(30, 20, 50, 0.11);

            --radius:
                16px;
        }


        body {

            font-family:
                Inter,
                Arial,
                sans-serif;

            background:
                var(--bg);

            color:
                var(--text);

            min-height:
                100vh;
        }


        button,
        input,
        select {

            font-family:
                inherit;
        }


        button {

            border:
                none;

            cursor:
                pointer;
        }


        a {

            color:
                inherit;

            text-decoration:
                none;
        }


        /*
        ==========================================================
        PAGE
        ==========================================================
        */

        .page {

            min-height:
                100vh;

            padding:
                32px;
        }


        /*
        ==========================================================
        HEADER
        ==========================================================
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


        .title-area h1 {

            font-size:
                30px;

            font-weight:
                800;

            letter-spacing:
                -0.5px;

            margin-bottom:
                6px;
        }


        .title-area p {

            color:
                var(--muted);

            font-size:
                14px;
        }


        .header-actions {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            flex-wrap:
                wrap;
        }


        .btn {

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

            font-size:
                13px;

            font-weight:
                700;

            transition:
                all 0.22s ease;
        }


        .btn-primary {

            background:
                var(--gradient);

            color:
                #ffffff;

            box-shadow:
                0 7px 18px
                rgba(118, 39, 201, 0.22);
        }


        .btn-primary:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 11px 24px
                rgba(118, 39, 201, 0.30);
        }


        .btn-light {

            background:
                #ffffff;

            border:
                1px solid var(--border);

            color:
                #514a5b;
        }


        .btn-light:hover {

            background:
                #faf8fc;

            border-color:
                #d7cfe0;

            transform:
                translateY(-1px);
        }


        .btn-danger {

            background:
                var(--danger-bg);

            color:
                var(--danger);
        }


        .btn-danger:hover {

            background:
                #ffe2e7;

            transform:
                translateY(-1px);
        }


        /*
        ==========================================================
        FLASH
        ==========================================================
        */

        .flash {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                14px 16px;

            border-radius:
                12px;

            margin-bottom:
                20px;

            font-size:
                14px;

            font-weight:
                600;
        }


        .flash.success {

            background:
                var(--success-bg);

            color:
                var(--success);

            border:
                1px solid #ccebd9;
        }


        .flash.error {

            background:
                var(--danger-bg);

            color:
                var(--danger);

            border:
                1px solid #ffd0d8;
        }


        /*
        ==========================================================
        STAT CARDS
        ==========================================================
        */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                18px;

            margin-bottom:
                24px;
        }


        .stat-card {

            background:
                var(--card);

            border:
                1px solid var(--border);

            border-radius:
                var(--radius);

            padding:
                20px;

            box-shadow:
                var(--shadow);

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

            transition:
                all 0.22s ease;
        }


        .stat-card:hover {

            transform:
                translateY(-3px);

            box-shadow:
                var(--shadow-hover);
        }


        .stat-icon {

            width:
                50px;

            height:
                50px;

            min-width:
                50px;

            border-radius:
                14px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                19px;
        }


        .stat-icon.total {

            background:
                #f0e8ff;

            color:
                var(--primary);
        }


        .stat-icon.unread {

            background:
                #fff0f4;

            color:
                #d43b66;
        }


        .stat-icon.read {

            background:
                var(--success-bg);

            color:
                var(--success);
        }


        .stat-info span {

            display:
                block;

            color:
                var(--muted);

            font-size:
                12px;

            font-weight:
                600;

            margin-bottom:
                4px;
        }


        .stat-info strong {

            display:
                block;

            font-size:
                24px;

            font-weight:
                800;
        }


        /*
        ==========================================================
        MAIN CARD
        ==========================================================
        */

        .notifications-card {

            background:
                var(--card);

            border:
                1px solid var(--border);

            border-radius:
                var(--radius);

            box-shadow:
                var(--shadow);

            overflow:
                hidden;
        }


        /*
        ==========================================================
        FILTER BAR
        ==========================================================
        */

        .filter-bar {

            padding:
                18px;

            border-bottom:
                1px solid var(--border);

            display:
                grid;

            grid-template-columns:
                2fr 1fr 1fr auto;

            gap:
                12px;

            background:
                #fcfbfd;
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
                #a39ba9;

            font-size:
                14px;
        }


        .search-wrapper input {

            width:
                100%;

            height:
                44px;

            padding:
                0 14px 0 42px;

            border:
                1px solid var(--border);

            border-radius:
                10px;

            background:
                #ffffff;

            outline:
                none;

            font-size:
                13px;

            color:
                var(--text);

            transition:
                all 0.2s ease;
        }


        .search-wrapper input:focus {

            border-color:
                #a45bd4;

            box-shadow:
                0 0 0 3px
                rgba(118, 39, 201, 0.08);
        }


        .filter-select {

            width:
                100%;

            height:
                44px;

            padding:
                0 12px;

            border:
                1px solid var(--border);

            border-radius:
                10px;

            background:
                #ffffff;

            outline:
                none;

            color:
                #514a5b;

            font-size:
                13px;

            cursor:
                pointer;
        }


        .filter-select:focus {

            border-color:
                #a45bd4;
        }


        .filter-btn {

            height:
                44px;

            padding:
                0 16px;

            border-radius:
                10px;

            background:
                #2d2635;

            color:
                #ffffff;

            font-size:
                13px;

            font-weight:
                700;
        }


        .filter-btn:hover {

            transform:
                translateY(-1px);

            background:
                #201b27;
        }


        /*
        ==========================================================
        TOOLBAR
        ==========================================================
        */

        .toolbar {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            padding:
                16px 18px;

            border-bottom:
                1px solid var(--border);
        }


        .toolbar-left {

            color:
                var(--muted);

            font-size:
                13px;
        }


        .toolbar-left strong {

            color:
                var(--text);
        }


        .toolbar-actions {

            display:
                flex;

            align-items:
                center;

            gap:
                8px;

            flex-wrap:
                wrap;
        }


        .small-btn {

            height:
                36px;

            padding:
                0 12px;

            border-radius:
                9px;

            display:
                inline-flex;

            align-items:
                center;

            gap:
                7px;

            font-size:
                12px;

            font-weight:
                700;

            transition:
                all 0.2s ease;
        }


        .small-btn.read-all {

            background:
                var(--success-bg);

            color:
                var(--success);
        }


        .small-btn.delete-read {

            background:
                var(--danger-bg);

            color:
                var(--danger);
        }


        .small-btn:hover {

            transform:
                translateY(-1px);
        }


        /*
        ==========================================================
        NOTIFICATION LIST
        ==========================================================
        */

        .notification-list {

            display:
                flex;

            flex-direction:
                column;
        }


        .notification-item {

            display:
                grid;

            grid-template-columns:
                48px 1fr auto;

            gap:
                15px;

            padding:
                20px;

            border-bottom:
                1px solid #eeeaf1;

            transition:
                all 0.2s ease;

            position:
                relative;
        }


        .notification-item:last-child {

            border-bottom:
                none;
        }


        .notification-item:hover {

            background:
                #fcfaff;
        }


        .notification-item.unread {

            background:
                #faf7ff;
        }


        .notification-item.unread::before {

            content:
                "";

            position:
                absolute;

            left:
                0;

            top:
                0;

            bottom:
                0;

            width:
                4px;

            background:
                var(--gradient);
        }


        .notification-icon {

            width:
                46px;

            height:
                46px;

            border-radius:
                13px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                16px;

            flex-shrink:
                0;
        }


        .type-order {

            background:
                #eee8ff;

            color:
                #7135bd;
        }


        .type-review {

            background:
                #fff3d8;

            color:
                #c17b00;
        }


        .type-customer {

            background:
                #eaf2ff;

            color:
                #3967c9;
        }


        .type-product {

            background:
                #e9f8f1;

            color:
                #178553;
        }


        .type-stock {

            background:
                #fff0e2;

            color:
                #bd6417;
        }


        .type-payment {

            background:
                #eaf8ff;

            color:
                #19739e;
        }


        .type-success {

            background:
                var(--success-bg);

            color:
                var(--success);
        }


        .type-warning {

            background:
                var(--warning-bg);

            color:
                var(--warning);
        }


        .type-error {

            background:
                var(--danger-bg);

            color:
                var(--danger);
        }


        .type-general {

            background:
                #f0edf3;

            color:
                #665d6e;
        }


        /*
        ==========================================================
        NOTIFICATION CONTENT
        ==========================================================
        */

        .notification-content {

            min-width:
                0;
        }


        .notification-title-row {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            flex-wrap:
                wrap;

            margin-bottom:
                6px;
        }


        .notification-title {

            font-size:
                14px;

            font-weight:
                750;

            color:
                var(--text);

            line-height:
                1.4;
        }


        .unread-dot {

            width:
                7px;

            height:
                7px;

            border-radius:
                50%;

            background:
                #b52bd2;

            display:
                inline-block;
        }


        .notification-message {

            color:
                #716a78;

            font-size:
                13px;

            line-height:
                1.65;

            margin-bottom:
                9px;

            max-width:
                800px;
        }


        .notification-meta {

            display:
                flex;

            align-items:
                center;

            gap:
                9px;

            flex-wrap:
                wrap;

            color:
                #97909e;

            font-size:
                11px;
        }


        .type-badge {

            display:
                inline-flex;

            align-items:
                center;

            padding:
                4px 8px;

            border-radius:
                6px;

            background:
                #f3f0f5;

            color:
                #625a69;

            font-size:
                10px;

            font-weight:
                700;

            text-transform:
                uppercase;

            letter-spacing:
                0.3px;
        }


        /*
        ==========================================================
        ACTIONS
        ==========================================================
        */

        .notification-actions {

            display:
                flex;

            align-items:
                center;

            justify-content:
                flex-end;

            gap:
                6px;

            align-self:
                center;
        }


        .icon-btn {

            width:
                34px;

            height:
                34px;

            border-radius:
                9px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                12px;

            transition:
                all 0.2s ease;
        }


        .icon-btn.mark {

            background:
                #f0eaff;

            color:
                var(--primary);
        }


        .icon-btn.mark:hover {

            background:
                #e4d8ff;

            transform:
                translateY(-1px);
        }


        .icon-btn.delete {

            background:
                var(--danger-bg);

            color:
                var(--danger);
        }


        .icon-btn.delete:hover {

            background:
                #ffe0e5;

            transform:
                translateY(-1px);
        }


        /*
        ==========================================================
        EMPTY STATE
        ==========================================================
        */

        .empty-state {

            text-align:
                center;

            padding:
                75px 25px;
        }


        .empty-icon {

            width:
                72px;

            height:
                72px;

            border-radius:
                20px;

            margin:
                0 auto 18px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #f0eafa;

            color:
                #8964aa;

            font-size:
                27px;
        }


        .empty-state h3 {

            font-size:
                17px;

            margin-bottom:
                7px;
        }


        .empty-state p {

            color:
                var(--muted);

            font-size:
                13px;
        }


        /*
        ==========================================================
        DATABASE ERROR
        ==========================================================
        */

        .database-error {

            margin:
                20px;

            padding:
                18px;

            border:
                1px solid #ffd0d8;

            background:
                #fff3f5;

            color:
                #a92e42;

            border-radius:
                12px;

            font-size:
                13px;

            line-height:
                1.6;
        }


        /*
        ==========================================================
        MOBILE
        ==========================================================
        */

        @media (max-width: 1000px) {

            .filter-bar {

                grid-template-columns:
                    1fr 1fr;

            }

            .filter-btn {

                width:
                    100%;
            }

            .stats-grid {

                grid-template-columns:
                    1fr 1fr;
            }
        }


        @media (max-width: 700px) {

            .page {

                padding:
                    20px 14px;
            }


            .page-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .header-actions {

                width:
                    100%;
            }


            .header-actions .btn {

                flex:
                    1;
            }


            .title-area h1 {

                font-size:
                    25px;
            }


            .stats-grid {

                grid-template-columns:
                    1fr;
            }


            .filter-bar {

                grid-template-columns:
                    1fr;
            }


            .toolbar {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .toolbar-actions {

                width:
                    100%;
            }


            .small-btn {

                flex:
                    1;

                justify-content:
                    center;
            }


            .notification-item {

                grid-template-columns:
                    42px 1fr;

                gap:
                    12px;

                padding:
                    17px 14px;
            }


            .notification-icon {

                width:
                    42px;

                height:
                    42px;
            }


            .notification-actions {

                grid-column:
                    2;

                justify-content:
                    flex-start;

                margin-top:
                    3px;
            }
        }


        @media (max-width: 450px) {

            .page {

                padding:
                    16px 10px;
            }


            .stat-card {

                padding:
                    16px;
            }


            .notification-item {

                grid-template-columns:
                    1fr;
            }


            .notification-icon {

                width:
                    40px;

                height:
                    40px;
            }


            .notification-actions {

                grid-column:
                    1;
            }
        }

    </style>

</head>


<body>


<div class="page">


    <!-- ========================================================
         PAGE HEADER
    ========================================================= -->

    <header class="page-header">

        <div class="title-area">

            <h1>
                Notifications
            </h1>

            <p>
                Manage store alerts, orders, reviews and system notifications.
            </p>

        </div>


        <div class="header-actions">

            <a
                href="index.php"
                class="btn btn-light"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Dashboard

            </a>

        </div>

    </header>



    <!-- ========================================================
         FLASH MESSAGE
    ========================================================= -->

    <?php if ($flash): ?>

        <div
            class="flash <?= e($flash['type']) ?>"
            id="flashMessage"
        >

            <?php if ($flash['type'] === 'success'): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php endif; ?>


            <span>
                <?= e($flash['message']) ?>
            </span>

        </div>

    <?php endif; ?>



    <!-- ========================================================
         STATISTICS
    ========================================================= -->

    <section class="stats-grid">


        <!-- TOTAL -->

        <div class="stat-card">

            <div class="stat-icon total">

                <i class="fa-solid fa-bell"></i>

            </div>

            <div class="stat-info">

                <span>
                    Total Notifications
                </span>

                <strong>
                    <?= number_format($totalNotifications) ?>
                </strong>

            </div>

        </div>


        <!-- UNREAD -->

        <div class="stat-card">

            <div class="stat-icon unread">

                <i class="fa-solid fa-envelope"></i>

            </div>

            <div class="stat-info">

                <span>
                    Unread Notifications
                </span>

                <strong>
                    <?= number_format($unreadNotifications) ?>
                </strong>

            </div>

        </div>


        <!-- READ -->

        <div class="stat-card">

            <div class="stat-icon read">

                <i class="fa-solid fa-envelope-open"></i>

            </div>

            <div class="stat-info">

                <span>
                    Read Notifications
                </span>

                <strong>
                    <?= number_format($readNotifications) ?>
                </strong>

            </div>

        </div>


    </section>



    <!-- ========================================================
         MAIN NOTIFICATION CARD
    ========================================================= -->

    <section class="notifications-card">


        <!-- ====================================================
             FILTER BAR
        ===================================================== -->

        <form
            method="GET"
            action="notifications.php"
            class="filter-bar"
        >


            <!-- SEARCH -->

            <div class="search-wrapper">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="search"
                    name="search"
                    placeholder="Search notifications..."
                    value="<?= e($search) ?>"
                >

            </div>


            <!-- STATUS -->

            <select
                name="status"
                class="filter-select"
            >

                <option
                    value="all"
                    <?= $statusFilter === 'all' ? 'selected' : '' ?>
                >
                    All Notifications
                </option>

                <option
                    value="unread"
                    <?= $statusFilter === 'unread' ? 'selected' : '' ?>
                >
                    Unread
                </option>

                <option
                    value="read"
                    <?= $statusFilter === 'read' ? 'selected' : '' ?>
                >
                    Read
                </option>

            </select>


            <!-- TYPE -->

            <select
                name="type"
                class="filter-select"
            >

                <option value="all">
                    All Types
                </option>


                <?php foreach ($notificationTypes as $notificationType): ?>

                    <option
                        value="<?= e($notificationType) ?>"
                        <?= $typeFilter === $notificationType ? 'selected' : '' ?>
                    >
                        <?= e(
                            getNotificationTypeLabel(
                                $notificationType
                            )
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <!-- APPLY -->

            <button
                type="submit"
                class="filter-btn"
            >

                <i class="fa-solid fa-filter"></i>

                Filter

            </button>


        </form>



        <!-- ====================================================
             TOOLBAR
        ===================================================== -->

        <div class="toolbar">


            <div class="toolbar-left">

                Showing

                <strong>
                    <?= number_format($totalNotifications) ?>
                </strong>

                notification(s)

            </div>


            <div class="toolbar-actions">


                <!-- MARK ALL READ -->

                <form
                    method="POST"
                    action="notifications.php"
                    onsubmit="
                        return confirm(
                            'Mark all notifications as read?'
                        );
                    "
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="mark_all_read"
                    >

                    <button
                        type="submit"
                        class="small-btn read-all"
                    >

                        <i class="fa-solid fa-check-double"></i>

                        Mark All Read

                    </button>

                </form>


                <!-- DELETE READ -->

                <form
                    method="POST"
                    action="notifications.php"
                    onsubmit="
                        return confirm(
                            'Delete all read notifications? This action cannot be undone.'
                        );
                    "
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="delete_read"
                    >

                    <button
                        type="submit"
                        class="small-btn delete-read"
                    >

                        <i class="fa-solid fa-trash"></i>

                        Delete Read

                    </button>

                </form>


            </div>

        </div>



        <!-- ====================================================
             DATABASE ERROR
        ===================================================== -->

        <?php if (isset($databaseError)): ?>

            <div class="database-error">

                <strong>
                    Database Error
                </strong>

                <br>

                <?= e($databaseError) ?>

            </div>

        <?php endif; ?>



        <!-- ====================================================
             NOTIFICATION LIST
        ===================================================== -->

        <div class="notification-list">


            <?php if (empty($notifications)): ?>


                <!-- EMPTY STATE -->

                <div class="empty-state">

                    <div class="empty-icon">

                        <i class="fa-regular fa-bell-slash"></i>

                    </div>

                    <h3>
                        No notifications found
                    </h3>

                    <p>

                        <?php if (
                            $search !== ''
                            || $statusFilter !== 'all'
                            || $typeFilter !== 'all'
                        ): ?>

                            Try changing your search or filter.

                        <?php else: ?>

                            There are no notifications available right now.

                        <?php endif; ?>

                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($notifications as $notification): ?>


                    <?php

                    $notificationId =
                        (int) $notification['id'];

                    $isRead =
                        (int) $notification['is_read'] === 1;

                    $notificationType =
                        $notification['type'] ?? 'general';

                    $icon =
                        getNotificationIcon(
                            $notificationType
                        );

                    $typeClass =
                        getNotificationTypeClass(
                            $notificationType
                        );

                    ?>


                    <!-- =================================================
                         SINGLE NOTIFICATION
                    ================================================== -->

                    <article
                        class="
                            notification-item
                            <?= !$isRead ? 'unread' : '' ?>
                        "
                    >


                        <!-- ICON -->

                        <div
                            class="
                                notification-icon
                                <?= e($typeClass) ?>
                            "
                        >

                            <i
                                class="
                                    fa-solid
                                    <?= e($icon) ?>
                                "
                            ></i>

                        </div>



                        <!-- CONTENT -->

                        <div class="notification-content">


                            <div class="notification-title-row">

                                <span class="notification-title">

                                    <?= e(
                                        $notification['title']
                                        ?? 'Notification'
                                    ) ?>

                                </span>


                                <?php if (!$isRead): ?>

                                    <span
                                        class="unread-dot"
                                        title="Unread"
                                    ></span>

                                <?php endif; ?>

                            </div>


                            <div class="notification-message">

                                <?= nl2br(
                                    e(
                                        $notification['message']
                                        ?? ''
                                    )
                                ) ?>

                            </div>


                            <div class="notification-meta">


                                <span class="type-badge">

                                    <?= e(
                                        getNotificationTypeLabel(
                                            $notificationType
                                        )
                                    ) ?>

                                </span>


                                <span>
                                    <i class="fa-regular fa-clock"></i>

                                    <?= formatNotificationDate(
                                        $notification['created_at']
                                        ?? null
                                    ) ?>

                                </span>


                                <?php if ($isRead): ?>

                                    <span>

                                        <i
                                            class="
                                                fa-solid
                                                fa-check
                                            "
                                        ></i>

                                        Read

                                    </span>

                                <?php else: ?>

                                    <span>

                                        <i
                                            class="
                                                fa-solid
                                                fa-envelope
                                            "
                                        ></i>

                                        Unread

                                    </span>

                                <?php endif; ?>


                            </div>

                        </div>



                        <!-- ACTIONS -->

                        <div class="notification-actions">


                            <?php if (!$isRead): ?>


                                <!-- MARK READ -->

                                <form
                                    method="POST"
                                    action="notifications.php"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="mark_read"
                                    >

                                    <input
                                        type="hidden"
                                        name="notification_id"
                                        value="<?= $notificationId ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="icon-btn mark"
                                        title="Mark as read"
                                    >

                                        <i
                                            class="
                                                fa-solid
                                                fa-check
                                            "
                                        ></i>

                                    </button>

                                </form>


                            <?php else: ?>


                                <!-- MARK UNREAD -->

                                <form
                                    method="POST"
                                    action="notifications.php"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="mark_unread"
                                    >

                                    <input
                                        type="hidden"
                                        name="notification_id"
                                        value="<?= $notificationId ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="icon-btn mark"
                                        title="Mark as unread"
                                    >

                                        <i
                                            class="
                                                fa-solid
                                                fa-envelope
                                            "
                                        ></i>

                                    </button>

                                </form>


                            <?php endif; ?>



                            <!-- DELETE -->

                            <form
                                method="POST"
                                action="notifications.php"
                                onsubmit="
                                    return confirm(
                                        'Delete this notification?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e($csrfToken) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="delete"
                                >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= $notificationId ?>"
                                >

                                <button
                                    type="submit"
                                    class="icon-btn delete"
                                    title="Delete notification"
                                >

                                    <i
                                        class="
                                            fa-solid
                                            fa-trash
                                        "
                                    ></i>

                                </button>

                            </form>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>


    </section>


</div>



<script>

    /*
    ==========================================================
    AUTO HIDE FLASH MESSAGE
    ==========================================================
    */

    const flashMessage =
        document.getElementById(
            'flashMessage'
        );


    if (flashMessage) {

        setTimeout(
            function () {

                flashMessage.style.transition =
                    'opacity 0.4s ease, transform 0.4s ease';

                flashMessage.style.opacity =
                    '0';

                flashMessage.style.transform =
                    'translateY(-5px)';

                setTimeout(
                    function () {

                        flashMessage.remove();

                    },
                    450
                );

            },
            3500
        );

    }


    /*
    ==========================================================
    SEARCH - ENTER KEY
    ==========================================================
    */

    const searchInput =
        document.querySelector(
            'input[name="search"]'
        );


    if (searchInput) {

        searchInput.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Enter') {

                    event.preventDefault();

                    this.form.submit();

                }

            }
        );

    }

</script>


</body>

</html>