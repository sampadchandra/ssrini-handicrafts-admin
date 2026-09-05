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
            /* Updated to ensure high contrast and readability */
            --blue-dark: #0f172a; 
            --blue: #2563eb;
            --blue-light: #76a7ff;
            --pink: #db2777;
            --pink-light: #fbcfe8;
            --pink-soft: #fdf2f8;
            --sky-soft: #eff6ff;
            --blue-soft: #dceaff;
            --white: #ffffff;
            --cream: #f8fafc;
            --text: #1e293b; /* Darker for high visibility */
            --muted: #475569; /* Clearer muted text */
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.025);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.025);
        }

        body {
            font-family: Inter, Arial, sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #f1f5f9 0%, #f8fafc 50%, #fdf2f8 100%);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ================= SIDEBAR ================= */
        .sidebar {
            width: 260px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02);
        }

        .sidebar-brand {
            padding: 24px 20px;
            font-family: "Playfair Display", Georgia, serif;
            font-size: 22px;
            color: var(--blue-dark);
            font-weight: 700;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 1px;
            padding: 12px 12px 6px;
            margin-top: 10px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: var(--sky-soft);
            color: var(--blue);
            font-weight: 600;
            transform: translateX(4px);
        }

        .sidebar-menu li a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
            color: var(--muted);
            transition: color 0.2s;
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
            color: #e11d48;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 14px;
            border-radius: 10px;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #ffe4e6;
            transform: translateY(-1px);
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
            height: 74px;
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
            box-shadow: var(--shadow-sm);
        }

        .header-title {
            font-family: "Playfair Display", Georgia, serif;
            font-size: 24px;
            color: var(--blue-dark);
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .refresh-btn {
            background: var(--white);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 8px;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: var(--sky-soft);
            color: var(--blue);
            border-color: #bfdbfe;
            transform: translateY(-1px);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--white);
            padding: 6px 16px 6px 6px;
            border-radius: 30px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .admin-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pink), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 15px;
        }

        .admin-info h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--blue-dark);
            line-height: 1.2;
        }

        .admin-info span {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        /* ================= PAGE CONTAINER ================= */
        .dashboard-body {
            padding: 30px 35px;
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* BANNER */
        .banner {
            background: linear-gradient(135deg, rgba(255,255,255,1), rgba(253,242,248,0.9));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 35px 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .banner::after {
            content: "";
            position: absolute;
            right: 0;
            top: 0;
            width: 400px;
            height: 100%;
            background: radial-gradient(circle at right, rgba(219,39,119,0.08), transparent 70%);
            pointer-events: none;
        }

        .banner-tag {
            color: var(--pink);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner h1 {
            font-family: "Playfair Display", Georgia, serif;
            font-size: clamp(32px, 3vw, 42px);
            color: var(--blue-dark);
            margin-bottom: 10px;
            font-weight: 800;
        }

        .banner p {
            color: var(--muted);
            font-size: 15px;
            max-width: 500px;
            font-weight: 500;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            position: relative;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: #cbd5e1;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--sky-soft);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .stat-card:nth-child(2) .stat-icon { background: var(--pink-soft); color: var(--pink); }
        .stat-card:nth-child(3) .stat-icon { background: #dcfce7; color: #16a34a; }
        .stat-card:nth-child(4) .stat-icon { background: #fef9c3; color: #ca8a04; }

        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--blue-dark);
        }

        .stat-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
            font-weight: 500;
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

        /* PANEL CARDS (Premium Feel) */
        .panel {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            transition: box-shadow 0.3s ease;
        }

        .panel:hover {
            box-shadow: var(--shadow-lg);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .panel-title h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--blue-dark);
            margin-bottom: 4px;
        }

        .panel-title p {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .view-all-btn {
            font-size: 13px;
            font-weight: 700;
            color: var(--blue);
            background: var(--sky-soft);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid #bfdbfe;
        }

        .view-all-btn:hover {
            background: #dbeafe;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        /* PREMIUM TABLE (Fixed Text Visibility) */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            color: var(--blue-dark);
            background: var(--cream);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border);
            letter-spacing: 0.5px;
            border-radius: 6px 6px 0 0;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
            font-weight: 500;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: var(--cream);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending { background: #fef9c3; color: #ca8a04; border: 1px solid #fde047; }
        .badge-delivered { background: #dcfce7; color: #16a34a; border: 1px solid #86efac; }
        .badge-shipped { background: var(--sky-soft); color: var(--blue); border: 1px solid #bfdbfe; }
        .badge-processing { background: #f3e8ff; color: #9333ea; border: 1px solid #d8b4fe; }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 42px;
            color: var(--pink-light);
            margin-bottom: 16px;
            display: block;
        }
        
        .empty-state strong {
            color: var(--blue-dark);
            font-size: 16px;
            display: block;
            margin-bottom: 6px;
        }

        /* QUICK ACTIONS (Workable & Premium) */
        .quick-actions-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(236, 72, 153, 0.25);
            border: none;
        }

        .action-btn.primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(236, 72, 153, 0.4);
        }

        .action-btn.secondary {
            background: var(--white);
            border: 1px solid #cbd5e1;
            color: var(--blue-dark);
            box-shadow: var(--shadow-sm);
        }

        .action-btn.secondary:hover {
            background: var(--cream);
            border-color: #94a3b8;
            transform: translateY(-3px) scale(1.02);
            box-shadow: var(--shadow-md);
            color: var(--blue);
        }

        .action-btn i {
            font-size: 16px;
            width: 24px;
            text-align: center;
        }

        /* STORE STATUS FOOTER BAR */
        .store-status-bar {
            margin-top: 30px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--shadow-md);
        }

        .store-status-bar h4 {
            font-size: 15px;
            font-weight: 700;
            color: var(--blue-dark);
            margin-bottom: 4px;
        }

        .store-status-bar p {
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .status-badge {
            background: #dcfce7;
            color: #16a34a;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #bbf7d0;
        }

        .status-badge i {
            font-size: 8px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                width: 76px;
            }
            .sidebar-brand span,
            .sidebar-menu span,
            .sidebar-footer span,
            .menu-category {
                display: none;
            }
            .main-content {
                margin-left: 76px;
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
                font-size: 20px;
            }
            .dashboard-body {
                padding: 20px;
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
                                            <td style="font-weight: 700; color: #15803d;">₹<?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($order['order_status']) ?>">
                                                    <?= htmlspecialchars($order['order_status'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-size: 13px;"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
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




                    <!-- Enhanced anchor tags for guaranteed navigation -->
                   
                <div class="quick-actions-list">
                        <a href="../products.php" class="action-btn primary">
                            <i class="fa-solid fa-plus"></i> Add Product
                        </a>
                        <a href="../admin/orders.php" class="action-btn secondary">
                            <i class="fa-solid fa-box"></i> New Orders
                        </a>
                        <a href="../invoices.php" class="action-btn secondary">
                            <i class="fa-solid fa-file-invoice"></i> Create Invoice
                        </a>
                        <a href="../categories.php" class="action-btn secondary">
                            <i class="fa-solid fa-tag"></i> Add Category
                        </a>
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

</body>
</html>