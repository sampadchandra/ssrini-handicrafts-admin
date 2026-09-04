<?php

/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * CUSTOMERS LIST PAGE
 * =========================================================
 *
 * File: admin/customers.php
 * Purpose: Fetch & display registered customers with search, stats, and pagination.
 * Connected with: customer-details.php
 * =========================================================
 */

/**
 * =========================================================
 * DATABASE + AUTHENTICATION
 * =========================================================
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (function_exists('requireAdminLogin')) {
    requireAdminLogin();
}

/**
 * =========================================================
 * HELPER FUNCTIONS (Safe Redeclaration Check)
 * =========================================================
 */

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($value): string {
        if (!is_numeric($value)) {
            return '₹0.00';
        }
        return '₹' . number_format((float) $value, 2);
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($value): string {
        if (empty($value)) return '—';
        $timestamp = strtotime((string) $value);
        return $timestamp ? date('d M Y', $timestamp) : e($value);
    }
}

if (!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $name = trim($name);
        if ($name === '') return 'C';
        $parts = preg_split('/\s+/', $name);
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

/**
 * =========================================================
 * PAGE CONFIGURATION & SEARCH / PAGINATION
 * =========================================================
 */

$pageTitle = 'Customers';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$customers = [];
$totalCustomers = 0;
$errorMessage = null;

/**
 * =========================================================
 * FETCH CUSTOMERS FROM DATABASE
 * =========================================================
 */

try {
    $whereClause = "";
    $params = [];

    if ($search !== '') {
        $whereClause = " WHERE c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.city LIKE ? ";
        $searchTerm = "%{$search}%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
    }

    // Get Total Count for Pagination
    $countSql = "SELECT COUNT(*) FROM customers c {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCustomers = (int) $countStmt->fetchColumn();

    // Fetch Customers with aggregated order metrics
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.email,
            c.phone,
            c.address,
            c.city,
            c.state,
            c.pincode,
            c.created_at,
            COUNT(o.id) AS total_orders,
            COALESCE(SUM(o.total_amount), 0) AS total_spent
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id
        {$whereClause}
        GROUP BY c.id
        ORDER BY c.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorMessage = 'Database Query Error: ' . $e->getMessage();
} catch (Throwable $e) {
    $errorMessage = 'An error occurred while loading customers data.';
}

$totalPages = max(1, ceil($totalCustomers / $limit));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Customers Management - Ssrini Handicrafts Admin Panel">
    <title><?= e($pageTitle) ?> | Ssrini Handicrafts Admin</title>

    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .customers-container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        .customers-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .customers-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary, #111827);
        }

        .customers-title p {
            margin: 4px 0 0;
            color: var(--text-muted, #6b7280);
            font-size: 13px;
        }

        /* SEARCH BAR & FILTER BAR */
        .customers-controls {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border-light, #e5e7eb);
            border-radius: var(--radius-lg, 12px);
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }

        .search-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 450px;
        }

        .search-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-light, #d1d5db);
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }

        .btn-search, .btn-reset {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-search {
            background: #8b5cf6;
            color: #ffffff;
        }

        .btn-search:hover {
            background: #7c3aed;
        }

        .btn-reset {
            background: #f3f4f6;
            color: #4b5563;
        }

        .btn-reset:hover {
            background: #e5e7eb;
        }

        .customers-count-badge {
            font-size: 13px;
            color: #6b7280;
            font-weight: 600;
        }

        /* TABLE STYLING */
        .customers-card {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border-light, #e5e7eb);
            border-radius: var(--radius-lg, 14px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .customers-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            min-width: 850px;
        }

        .customers-table th {
            padding: 14px 18px;
            background: var(--surface-soft, #f9fafb);
            color: var(--text-muted, #6b7280);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            border-bottom: 1px solid var(--border-light, #e5e7eb);
        }

        .customers-table td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-light, #e5e7eb);
            color: var(--text-secondary, #374151);
            font-size: 13px;
            vertical-align: middle;
        }

        .customers-table tbody tr:hover {
            background: rgba(139, 92, 246, 0.02);
        }

        /* CUSTOMER CELL */
        .customer-info-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .customer-avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8b5cf6, #ec4899);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .customer-name-box {
            display: flex;
            flex-direction: column;
        }

        .customer-name-link {
            color: var(--text-primary, #111827);
            font-weight: 700;
            text-decoration: none;
            font-size: 14px;
        }

        .customer-name-link:hover {
            color: #8b5cf6;
        }

        .customer-id-sub {
            font-size: 11px;
            color: #9ca3af;
        }

        /* CONTACT CELL */
        .contact-box {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .contact-email {
            color: #4b5563;
            font-weight: 500;
        }

        .contact-phone {
            color: #6b7280;
            font-size: 12px;
        }

        /* ACTION BUTTONS */
        .actions-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action-view {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 6px;
            background: rgba(139, 92, 246, 0.1);
            color: #7c3aed;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-action-view:hover {
            background: #7c3aed;
            color: #ffffff;
        }

        .btn-action-wa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-action-wa:hover {
            background: #16a34a;
            color: #ffffff;
        }

        /* PAGINATION */
        .pagination-wrapper {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border-light, #e5e7eb);
            flex-wrap: wrap;
            gap: 10px;
        }

        .pagination-info {
            font-size: 12px;
            color: #6b7280;
        }

        .pagination-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .page-link {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-link:hover, .page-link.active {
            background: #8b5cf6;
            border-color: #8b5cf6;
            color: #ffffff;
        }

        .customer-empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #6b7280;
        }

        .error-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }
    </style>
</head>

<body>
<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <?php 
    if (file_exists(dirname(__DIR__) . '/includes/sidebar.php')) {
        require_once dirname(__DIR__) . '/includes/sidebar.php';
    }
    ?>

    <!-- MAIN AREA -->
    <main class="main-area">

        <!-- HEADER -->
        <?php 
        if (file_exists(dirname(__DIR__) . '/includes/header.php')) {
            require_once dirname(__DIR__) . '/includes/header.php';
        }
        ?>

        <!-- PAGE CONTENT -->
        <div class="page-content">
            <div class="customers-container">

                <!-- TOP HEADER -->
                <div class="customers-header">
                    <div class="customers-title">
                        <h1>Customers</h1>
                        <p>Manage registered customers and monitor their activity and orders.</p>
                    </div>
                </div>

                <?php if ($errorMessage !== null): ?>
                    <div class="error-alert">
                        ⚠️ <?= e($errorMessage) ?>
                    </div>
                <?php endif; ?>

                <!-- CONTROLS & SEARCH -->
                <div class="customers-controls">
                    <form method="GET" action="customers.php" class="search-form">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search by Name, Email, Phone or City..." 
                            value="<?= e($search) ?>"
                        >
                        <button type="submit" class="btn-search">Search</button>
                        <?php if ($search !== ''): ?>
                            <a href="customers.php" class="btn-reset">Reset</a>
                        <?php endif; ?>
                    </form>

                    <div class="customers-count-badge">
                        Total Customers: <strong><?= e($totalCustomers) ?></strong>
                    </div>
                </div>

                <!-- CUSTOMERS TABLE CARD -->
                <div class="customers-card">
                    <div class="table-responsive">
                        <table class="customers-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact Info</th>
                                    <th>Location</th>
                                    <th>Orders Placed</th>
                                    <th>Total Spent</th>
                                    <th>Joined Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customers)): ?>
                                    <?php foreach ($customers as $cust): ?>
                                        <?php 
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $cust['phone'] ?? '');
                                            $waPhone = (strlen($cleanPhone) === 10) ? '91' . $cleanPhone : $cleanPhone;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="customer-info-cell">
                                                    <div class="customer-avatar-sm">
                                                        <?= e(getInitials($cust['name'] ?? 'Customer')) ?>
                                                    </div>
                                                    <div class="customer-name-box">
                                                        <a href="customer-details.php?id=<?= e($cust['id']) ?>" class="customer-name-link">
                                                            <?= e($cust['name'] ?? 'Unknown Customer') ?>
                                                        </a>
                                                        <span class="customer-id-sub">ID: #<?= e($cust['id']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="contact-box">
                                                    <span class="contact-email"><?= !empty($cust['email']) ? e($cust['email']) : '—' ?></span>
                                                    <span class="contact-phone"><?= !empty($cust['phone']) ? e($cust['phone']) : '—' ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <?= !empty($cust['city']) ? e($cust['city']) : '—' ?><?= !empty($cust['state']) ? ', ' . e($cust['state']) : '' ?>
                                            </td>
                                            <td>
                                                <strong><?= e($cust['total_orders']) ?> orders</strong>
                                            </td>
                                            <td>
                                                <strong><?= e(formatMoney($cust['total_spent'])) ?></strong>
                                            </td>
                                            <td>
                                                <?= e(formatDateTime($cust['created_at'])) ?>
                                            </td>
                                            <td>
                                                <div class="actions-cell">
                                                    <a href="customer-details.php?id=<?= e($cust['id']) ?>" class="btn-action-view" title="View Customer Details">
                                                        👁️ View Details
                                                    </a>
                                                    <?php if ($cleanPhone !== ''): ?>
                                                        <a href="https://wa.me/<?= e($waPhone) ?>" target="_blank" class="btn-action-wa" title="Chat on WhatsApp">
                                                            💬
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="customer-empty-state">
                                                <h3>No Customers Found</h3>
                                                <p>No customer records match your current criteria or database is empty.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-wrapper">
                            <div class="pagination-info">
                                Showing Page <strong><?= e($page) ?></strong> of <strong><?= e($totalPages) ?></strong>
                            </div>
                            <div class="pagination-links">
                                <?php if ($page > 1): ?>
                                    <a href="customers.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="page-link">‹ Prev</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="customers.php?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= ($i === $page) ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="customers.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="page-link">Next ›</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>

    </main>
</div>
</body>
</html>