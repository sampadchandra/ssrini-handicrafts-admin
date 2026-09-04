<?php

/**
 * ============================================================
 * SSRINI HANDICRAFTS - ADMIN DASHBOARD
 * ============================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

// Fetch summary metrics for dashboard cards
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN total_amount ELSE 0 END) AS total_revenue
        FROM orders
    ");
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalOrders = (int) ($orderData['total_orders'] ?? 0);
    $totalRevenue = (float) ($orderData['total_revenue'] ?? 0);

    $totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

    // Recent orders fetch
    $recentStmt = $pdo->query("
        SELECT id, customer_name, total_amount, order_status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $totalOrders = 0;
    $totalRevenue = 0.0;
    $totalProducts = 0;
    $totalCustomers = 0;
    $recentOrders = [];
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@ssrini.com';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SSRINI HANDICRAFTS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --blue-dark: #101e4a;
            --blue: #224ac7;
            --blue-light: #568eff;
            --pink: #e03b75;
            --pink-light: #ffb4cd;
            --pink-soft: #ffe8f0;
            --sky-soft: #e5f1ff;
            --blue-soft: #cfe0ff;
            --white: #ffffff;
            --cream: #fffdfd;
            --text: #192233;       /* High-contrast dark text */
            --muted: #48546b;      /* Darkened muted text for sharp visibility */
            --border: #cbd5e5;     /* Clearer definition borders */
            --shadow: rgba(30, 50, 95, 0.16);
        }

        body {
            font-family: Inter, Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #e9f4ff 0%, #f2f7ff 30%, #fffafd 62%, #ffe8f2 100%);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 260px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 24px 20px;
            font-family: "Playfair Display", Georgia, serif;
            font-size: 20px;
            color: var(--blue-dark);
            font-weight: 700;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand span {
            color: var(--pink);
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 12px;
            flex: 1;
        }

        .menu-category {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 1px;
            padding: 10px 12px 6px;
            margin-top: 10px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: var(--sky-soft);
            color: var(--blue);
            font-weight: 700;
        }

        .sidebar-menu li a i {
            font-size: 15px;
            width: 20px;
            text-align: center;
            color: var(--muted);
        }

        .sidebar-menu li a:hover i,
        .sidebar-menu li a.active i {
            color: var(--blue);
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #b82d55;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            padding: 10px 14px;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background: #fff3f7;
        }

        /* ================= MAIN CONTENT ================= */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ================= HEADER ================= */
        .top-header {
            height: 70px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .header-title {
            font-family: "Playfair Display", Georgia, serif;
            font-size: 22px;
            color: var(--blue-dark);
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .refresh-btn {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 8px;
            color: var(--text);
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.04);
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: var(--sky-soft);
            color: var(--blue);
            border-color: #a8bce0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--white);
            padding: 6px 14px 6px 6px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pink), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
        }

        .admin-info h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
        }

        .admin-info span {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--muted);
        }

        /* ================= PAGE CONTAINER ================= */
        .dashboard-body {
            padding: 30px 35px;
            flex: 1;
        }

        /* BANNER */
        .banner {
            background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(252,242,247,0.95));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 35px 40px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .banner::after {
            content: "";
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 300px;
            height: 120px;
            background: radial-gradient(circle, rgba(224,59,117,0.12), transparent 70%);
            pointer-events: none;
        }

        .banner-tag {
            color: var(--pink);
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner h1 {
            font-family: "Playfair Display", Georgia, serif;
            font-size: clamp(28px, 3vw, 42px);
            color: var(--blue-dark);
            margin-bottom: 8px;
            font-weight: 800;
        }

        .banner p {
            color: var(--muted);
            font-size: 14.5px;
            font-weight: 500;
            max-width: 500px;
        }

        .banner-divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--pink), var(--blue));
            border-radius: 2px;
            margin-top: 15px;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 24px;
            box-shadow: 0 10px 25px var(--shadow);
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(30, 50, 95, 0.2);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--sky-soft);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .stat-card:nth-child(2) .stat-icon { background: var(--pink-soft); color: var(--pink); }
        .stat-card:nth-child(3) .stat-icon { background: #eef9f0; color: #2ecc71; }
        .stat-card:nth-child(4) .stat-icon { background: #fef5e7; color: #f39c12; }

        .stat-label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: var(--blue-dark);
        }

        .stat-sub {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-top: 6px;
        }

        /* CONTENT SECTION GRID */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* PANEL CARDS */
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 25px var(--shadow);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .panel-title h3 {
            font-size: 17px;
            font-weight: 800;
            color: var(--blue-dark);
        }

        .panel-title p {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
            margin-top: 2px;
        }

        .view-all-btn {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--blue);
            background: var(--sky-soft);
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }

        .view-all-btn:hover {
            background: var(--blue-soft);
        }

        /* TABLE */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        th {
            color: var(--blue-dark);
            font-weight: 800;
            font-size: 11.5px;
            text-transform: uppercase;
            padding: 12px;
            border-bottom: 2px solid var(--border);
            letter-spacing: 0.6px;
            background: #f7faff;
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e8edf5;
            color: var(--text);
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 5px 11px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-pending { background: #fef5e7; color: #b75100; }
        .badge-delivered { background: #eef9f0; color: #1e8449; }
        .badge-shipped { background: var(--sky-soft); color: var(--blue); }
        .badge-processing { background: #f4ecf7; color: #7d3c98; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
            font-weight: 600;
        }

        .empty-state i {
            font-size: 36px;
            color: var(--pink);
            margin-bottom: 12px;
            display: block;
        }

        /* QUICK ACTIONS */
        .quick-actions-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font-family: inherit;
        }

        .action-btn.primary {
            background: linear-gradient(100deg, #f45188 0%, #c85eb2 48%, #4e83e5 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(94, 107, 204, 0.25);
        }

        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(94, 107, 204, 0.35);
        }

        .action-btn.secondary {
            background: var(--white);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .action-btn.secondary:hover {
            background: var(--sky-soft);
            color: var(--blue);
            border-color: #a8bce0;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 16px;
            width: 22px;
            text-align: center;
        }

        /* STORE STATUS FOOTER BAR */
        .store-status-bar {
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 8px 20px var(--shadow);
        }

        .store-status-bar h4 {
            font-size: 14px;
            font-weight: 800;
            color: var(--blue-dark);
            margin-bottom: 2px;
        }

        .store-status-bar p {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--muted);
        }

        .status-badge {
            background: #eef9f0;
            color: #1e8449;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge i {
            font-size: 8px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-brand span,
            .sidebar-menu span,
            .sidebar-footer span,
            .menu-category {
                display: none;
            }
            .main-content {
                margin-left: 70px;
            }
            .sidebar-brand {
                justify-content: center;
                padding: 24px 10px;
            }
            .sidebar-menu li a {
                justify-content: center;
                padding: 14px;
            }
            .sidebar-menu li a i {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-gem" style="color: var(--pink);"></i>
            <span>Ssrini</span>
        </div>

        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i><span>Dashboard</span></a></li>

            <div class="menu-category">Sales</div>
            <li><a href="orders.php"><i class="fa-solid fa-box-archive"></i><span>Orders</span></a></li>

            <div class="menu-category">Catalogue</div>
            <li><a href="products.php"><i class="fa-solid fa-bag-shopping"></i><span>Products</span></a></li>
            <li><a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Invoices</span></a></li>
            <li><a href="categories.php"><i class="fa-solid fa-tags"></i><span>Categories</span></a></li>

            <div class="menu-category">Website</div>
            <li><a href="filter-config.php"><i class="fa-solid fa-filter"></i><span>Filter Configuration</span></a></li>
            <li><a href="front-page.php"><i class="fa-solid fa-house-laptop"></i><span>Front Page Content</span></a></li>
            <li><a href="about-details.php"><i class="fa-solid fa-circle-info"></i><span>About Details</span></a></li>

            <div class="menu-category">Management</div>
            <li><a href="customers.php"><i class="fa-solid fa-users"></i><span>Customers</span></a></li>
            <li><a href="reviews.php"><i class="fa-solid fa-star"></i><span>Reviews</span></a></li>
            <li><a href="notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span></a></li>
            <li><a href="analytics.php"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></li>
            <li><a href="activity-logs.php"><i class="fa-solid fa-clock-rotate-left"></i><span>Activity Logs</span></a></li>
            <li><a href="settings.php"><i class="fa-solid fa-gear"></i><span>Settings</span></a></li>
        </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="main-content">

        <!-- HEADER -->
        <header class="top-header">
            <div class="header-title">Dashboard</div>
            <div class="header-right">
                <a href="index.php" class="refresh-btn">
                    <i class="fa-solid fa-rotate"></i> Refresh
                </a>
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?= strtoupper(substr($adminName, 0, 1)) ?>
                    </div>
                    <div class="admin-info">
                        <h4><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></h4>
                        <span>Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- DASHBOARD BODY -->
        <div class="dashboard-body">

            <!-- WELCOME BANNER -->
            <div class="banner">
                <div class="banner-tag">
                    <i class="fa-solid fa-sparkles"></i> Welcome back,
                </div>
                <h1>SSRINI HANDICRAFTS</h1>
                <p>Empowering rural artisans of Bengal with elegance and authenticity.</p>
                <div class="banner-divider"></div>
            </div>

            <!-- STATS CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= number_format($totalOrders) ?></div>
                    <div class="stat-sub">All store orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                    <div class="stat-label">Products</div>
                    <div class="stat-value"><?= number_format($totalProducts) ?></div>
                    <div class="stat-sub">Active inventory</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-label">Customers</div>
                    <div class="stat-value"><?= number_format($totalCustomers) ?></div>
                    <div class="stat-sub">Registered customers</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value">₹<?= number_format($totalRevenue, 2) ?></div>
                    <div class="stat-sub">Total sales volume</div>
                </div>
            </div>

            <!-- GRID SECTION: RECENT ORDERS & QUICK ACTIONS -->
            <div class="dashboard-grid">

                <!-- Recent Orders Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <h3>Recent Orders</h3>
                            <p>Latest orders from your store</p>
                        </div>
                        <a href="orders.php" class="view-all-btn">View All</a>
                    </div>

                    <div class="table-responsive">
                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-state">
                                <i class="fa-solid fa-box-open"></i>
                                <strong>No orders yet</strong>
                                <p>New customer orders will appear here.</p>
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><strong>#<?= $order['id'] ?></strong></td>
                                            <td><?= htmlspecialchars($order['customer_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($order['order_status']) ?>">
                                                    <?= htmlspecialchars($order['order_status'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <h3>Quick Actions</h3>
                            <p>Frequently used actions</p>
                        </div>
                    </div>

                    <div class="quick-actions-list">
                        <button type="button" id="qaAddProduct" class="action-btn primary">
                            <i class="fa-solid fa-plus"></i> Add Product
                        </button>
                        <button type="button" id="qaNewOrder" class="action-btn secondary">
                            <i class="fa-solid fa-box"></i> New Order
                        </button>
                        <button type="button" id="qaCreateInvoice" class="action-btn secondary">
                            <i class="fa-solid fa-file-invoice"></i> Create Invoice
                        </button>
                        <button type="button" id="qaAddCategory" class="action-btn secondary">
                            <i class="fa-solid fa-tag"></i> Add Category
                        </button>
                    </div>
                </div>

            </div>

            <!-- STORE STATUS FOOTER BAR -->
            <div class="store-status-bar">
                <div>
                    <h4>Store Status</h4>
                    <p>Your current store configuration is online and operating smoothly.</p>
                </div>
                <div class="status-badge">
                    <i class="fa-solid fa-circle"></i> Store Active
                </div>
            </div>

        </div>

    </div>

    <!-- Script to handle Quick Actions redirection seamlessly -->
    <script>
        document.getElementById('qaAddProduct').addEventListener('click', function() {
            window.location.href = 'products.php?open_add=true';
        });

        document.getElementById('qaNewOrder').addEventListener('click', function() {
            window.location.href = 'orders.php';
        });

        document.getElementById('qaCreateInvoice').addEventListener('click', function() {
            window.location.href = 'invoices.php';
        });

        document.getElementById('qaAddCategory').addEventListener('click', function() {
            window.location.href = 'products.php?open_category=true';
        });
    </script>

</body>
</html>