<?php 
require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../includes/auth.php'; 
requireAdminLogin(); 
$pageTitle = 'Dashboard'; 
$totalOrders = 0; $totalProducts = 0; $totalCustomers = 0; $totalRevenue = 0; $recentOrders = [];
try {
    $db = $pdo ?? $conn ?? null;
    if ($db) {
        $orderQuery = $db->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as revenue FROM orders");
        if ($orderData = $orderQuery->fetch(PDO::FETCH_ASSOC)) { $totalOrders = $orderData['total_orders']; $totalRevenue = $orderData['revenue']; }
        $prodQuery = $db->query("SELECT COUNT(*) as total_products FROM products");
        if ($prodData = $prodQuery->fetch(PDO::FETCH_ASSOC)) { $totalProducts = $prodData['total_products']; }
        $custQuery = $db->query("SELECT COUNT(*) as total_customers FROM customers");
        if ($custData = $custQuery->fetch(PDO::FETCH_ASSOC)) { $totalCustomers = $custData['total_customers']; }
        $recQuery = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
        $recentOrders = $recQuery->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {} ?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <meta name="description" content="Ssrini Handicrafts Admin Panel"> 
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Ssrini Handicrafts</title> 
    <link rel="stylesheet" href="../assets/css/admin.css"> 
    <style>
        html,body{width:100%;max-width:100%;overflow-x:hidden} *,*::before,*::after{box-sizing:border-box}
        .mobile-menu-button,.mobile-sidebar-overlay{display:none}
        .craft-hero{width:100%;max-width:100%;overflow:hidden;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--surface),var(--surface-soft));border:1px solid var(--border-light);border-radius:var(--radius-lg,16px);padding:30px;margin-bottom:24px;box-shadow:0 4px 20px rgba(0,0,0,0.03)}
        .craft-hero-content{min-width:0;flex:1} .craft-hero-eyebrow{font-size:14px;color:var(--text-muted);font-weight:500;margin-bottom:6px}
        .craft-hero-content h1{font-size:32px;font-weight:700;color:var(--text-primary);margin:0 0 8px 0;letter-spacing:-0.5px}
        .craft-hero-content p{color:var(--text-secondary);font-size:14px;margin:0 0 16px 0}
        .craft-hero-divider{display:flex;align-items:center;gap:10px;color:var(--primary);font-size:12px}
        .craft-hero-divider span{height:1px;width:40px;background:var(--border-light)}
        .craft-hero-art{flex-shrink:0;margin-left:20px}
        .craft-hero-art img{display:block;max-width:180px;height:auto;border-radius:12px}
        .table-wrapper{width:100%;max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
        .admin-table{width:100%;min-width:650px;border-collapse:collapse}
        @media (max-width:768px){
            #mobileMenuButton,.mobile-menu-button{display:flex!important;position:fixed!important;top:14px!important;left:14px!important;width:42px!important;height:42px!important;align-items:center!important;justify-content:center!important;border:1px solid var(--border-light)!important;border-radius:10px!important;background:var(--surface)!important;color:var(--text-primary)!important;box-shadow:0 5px 18px rgba(0,0,0,0.12)!important;cursor:pointer!important;z-index:10002!important;font-size:20px!important;line-height:1!important;padding:0!important}
            .admin-wrapper>aside,.admin-wrapper .sidebar,.admin-wrapper .admin-sidebar,.admin-wrapper .side-bar{position:fixed!important;top:0!important;left:0!important;bottom:0!important;width:min(290px,84vw)!important;max-width:84vw!important;height:100vh!important;z-index:10001!important;transform:translateX(-110%)!important;transition:transform 0.28s ease,visibility 0.28s ease!important;overflow-y:auto!important;box-shadow:12px 0 35px rgba(0,0,0,0.2);visibility:hidden}
            body.sidebar-mobile-open .admin-wrapper>aside,body.sidebar-mobile-open .admin-wrapper .sidebar,body.sidebar-mobile-open .admin-wrapper .admin-sidebar,body.sidebar-mobile-open .admin-wrapper .side-bar,.admin-wrapper>aside.mobile-sidebar-open,.admin-wrapper .sidebar.mobile-sidebar-open,.admin-wrapper .admin-sidebar.mobile-sidebar-open,.admin-wrapper .side-bar.mobile-sidebar-open{transform:translateX(0)!important;visibility:visible!important}
            .mobile-sidebar-overlay{position:fixed!important;inset:0!important;display:block!important;background:rgba(0,0,0,0.4)!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;transition:opacity 0.28s ease,visibility 0.28s ease!important;z-index:10000!important}
            body.sidebar-mobile-open .mobile-sidebar-overlay,.mobile-sidebar-overlay.active{opacity:1!important;visibility:visible!important;pointer-events:auto!important}
            .main-area{width:100%!important;margin-left:0!important} .top-header{padding-left:68px!important} .page-content{padding:18px 14px !important; margin-top: 60px !important;}
            .craft-hero{flex-direction:column-reverse;align-items:flex-start;gap:16px;padding:20px} .craft-hero-art{margin-left:0} .craft-hero-art img{max-width:100px}
            .stats-grid{grid-template-columns:1fr!important;gap:14px!important} .page-content>section[style*="grid-template-columns"]{display:grid!important;grid-template-columns:1fr!important;gap:16px!important}
        }
        @media (min-width:769px){#mobileMenuButton,.mobile-menu-button,#mobileSidebarOverlay{display:none!important}}
    </style> 
</head> 
<body> 
<button type="button" class="mobile-menu-button" id="mobileMenuButton" aria-label="Toggle Menu" aria-expanded="false">☰</button>
<div class="admin-wrapper"> 
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?> 
    <main class="main-area"> 
        <?php require_once __DIR__ . '/../includes/header.php'; ?> 
        <div class="page-content"> 
            <section class="craft-hero">
                <div class="craft-hero-content">
                    <div class="craft-hero-eyebrow">✦ Welcome back, Admin</div>
                    <h1>SSRINI HANDICRAFTS</h1>
                    <p>Empowering rural artisans of Bengal with premium digital management.</p>
                    <div class="craft-hero-divider"><span></span>✦<span></span></div>
                </div>
                <div class="craft-hero-art"><img src="../assets/images/folk-art.png" alt="Ssrini Handicrafts Folk Art"></div>
            </section>
            <section class="stats-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;margin-bottom:24px;"> 
                <div class="card stat-card card-hover" style="padding:20px;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">📦</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Total Orders</div> 
                    <div class="stat-value" id="totalOrders" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalOrders) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">All store orders</div> 
                </div> 
                <div class="card stat-card card-hover" style="padding:20px;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">🛍️</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Products</div> 
                    <div class="stat-value" id="totalProducts" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalProducts) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Active inventory</div> 
                </div> 
                <div class="card stat-card card-hover" style="padding:20px;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">👥</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Customers</div> 
                    <div class="stat-value" id="totalCustomers" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;"><?= number_format($totalCustomers) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Registered accounts</div> 
                </div> 
                <div class="card stat-card card-hover" style="padding:20px;background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div class="stat-icon" style="font-size:24px;margin-bottom:8px;">₹</div> 
                    <div class="stat-label" style="color:var(--text-secondary);font-size:13px;">Revenue</div> 
                    <div class="stat-value" id="totalRevenue" style="font-size:24px;font-weight:700;color:var(--text-primary);margin:4px 0;">₹<?= number_format($totalRevenue, 2) ?></div> 
                    <div class="stat-change" style="font-size:11px;color:var(--text-muted);">Gross merchandise value</div> 
                </div> 
            </section> 
            <section style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:20px;"> 
                <div class="table-card card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);overflow:hidden;"> 
                    <div class="table-header" style="padding:20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border-light);"> 
                        <div> 
                            <div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Recent Orders</div> 
                            <p style="margin-top:4px;color:var(--text-muted);font-size:11px;">Latest transactions from your store</p> 
                        </div> 
                        <a href="orders.php" class="btn btn-secondary" style="text-decoration:none;padding:6px 14px;font-size:12px;">View All</a> 
                    </div> 
                    <div class="table-wrapper"> 
                        <table class="admin-table"> 
                            <thead> 
                                <tr style="text-align:left;background:var(--surface-soft);font-size:12px;color:var(--text-secondary);"> 
                                    <th style="padding:12px 16px;">Order ID</th><th style="padding:12px 16px;">Customer</th><th style="padding:12px 16px;">Amount</th><th style="padding:12px 16px;">Status</th><th style="padding:12px 16px;">Date</th> 
                                </tr> 
                            </thead> 
                            <tbody> 
                                <?php if (!empty($recentOrders)): foreach ($recentOrders as $order): ?>
                                <tr style="border-bottom:1px solid var(--border-light);font-size:13px;"> 
                                    <td style="padding:12px 16px;font-weight:500;">#<?= htmlspecialchars($order['id'] ?? $order['order_id'] ?? 'N/A') ?></td> 
                                    <td style="padding:12px 16px;"><?= htmlspecialchars($order['customer_name'] ?? $order['shipping_name'] ?? 'Guest Customer') ?></td> 
                                    <td style="padding:12px 16px;">₹<?= number_format($order['total_amount'] ?? 0, 2) ?></td> 
                                    <td style="padding:12px 16px;"><span class="badge" style="padding:3px 8px;font-size:11px;border-radius:4px;background:var(--surface-soft);"><?= htmlspecialchars($order['status'] ?? 'Pending') ?></span></td> 
                                    <td style="padding:12px 16px;color:var(--text-muted);"><?= htmlspecialchars($order['created_at'] ?? 'N/A') ?></td> 
                                </tr> 
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" style="padding:0;"><div class="empty-state" style="text-align:center;padding:40px 20px;"><div class="empty-state-icon" style="font-size:32px;margin-bottom:10px;">📦</div><h3 style="font-size:16px;font-weight:600;color:var(--text-primary);margin-bottom:4px;">No orders yet</h3><p style="color:var(--text-muted);font-size:12px;">New customer orders will appear here automatically.</p></div></td></tr> 
                                <?php endif; ?>
                            </tbody> 
                        </table> 
                    </div> 
                </div> 
                <div class="card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);height:fit-content;"> 
                    <div style="padding:20px;border-bottom:1px solid var(--border-light);"> 
                        <div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Quick Actions</div> 
                        <p style="margin-top:4px;color:var(--text-muted);font-size:11px;">Frequently used admin shortcuts</p> 
                    </div> 
                    <div style="padding:16px;display:flex;flex-direction:column;gap:10px;"> 
                        <button type="button" class="btn btn-primary" onclick="window.location.href='products.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:none;background:var(--primary);color:#fff;font-weight:500;">🛍️ Add Product</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='orders.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">📦 Manage Orders</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='customers.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">👥 View Customers</button> 
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='invoices.php';" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;cursor:pointer;border-radius:8px;border:1px solid var(--border-light);background:var(--surface-soft);color:var(--text-primary);font-weight:500;">📄 Create Invoice</button> 
                    </div> 
                </div> 
            </section> 
            <section style="margin-top:20px;"> 
                <div class="card" style="background:var(--surface);border:1px solid var(--border-light);border-radius:var(--radius-md,12px);"> 
                    <div style="padding:20px;"> 
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap;"> 
                            <div><div class="table-title" style="font-size:16px;font-weight:600;color:var(--text-primary);">Store Status</div><p style="margin-top:5px;color:var(--text-secondary);font-size:12px;">Your current store configuration and gateway status.</p></div> 
                            <span class="badge badge-success" style="padding:4px 10px;font-size:12px;border-radius:6px;background:rgba(16,185,129,0.1);color:#10b981;font-weight:600;">● Store Active</span> 
                        </div> 
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:20px;"> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">💵</div><strong style="font-size:13px;color:var(--text-primary);">Cash on Delivery</strong><div style="margin-top:5px;font-size:11px;color:#10b981;font-weight:500;">Available</div></div> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">💳</div><strong style="font-size:13px;color:var(--text-primary);">Online Payment</strong><div style="margin-top:5px;font-size:11px;color:#f59e0b;font-weight:500;">Coming Soon</div></div> 
                            <div style="padding:15px;border:1px solid var(--border-light);border-radius:var(--radius-md,8px);background:var(--surface-soft);"><div style="font-size:20px;margin-bottom:8px;">🌐</div><strong style="font-size:13px;color:var(--text-primary);">Website</strong><div style="margin-top:5px;font-size:11px;color:#10b981;font-weight:500;">Connected & Live</div></div> 
                        </div> 
                    </div> 
                </div> 
            </section> 
        </div> 
    </main> 
</div>
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay" aria-hidden="true"></div>
<script>
(function(){"use strict";const sidebar=document.querySelector(".sidebar")||document.querySelector(".admin-sidebar")||document.querySelector(".side-bar")||document.querySelector(".admin-wrapper > aside");const menuButton=document.getElementById("mobileMenuButton");const overlay=document.getElementById("mobileSidebarOverlay");if(!sidebar||!menuButton||!overlay)return;function openSidebar(){document.body.classList.add("sidebar-mobile-open");sidebar.classList.add("mobile-sidebar-open");overlay.classList.add("active");menuButton.setAttribute("aria-expanded","true");menuButton.innerHTML="✕";document.body.style.overflow="hidden"}function closeSidebar(){document.body.classList.remove("sidebar-mobile-open");sidebar.classList.remove("mobile-sidebar-open");overlay.classList.remove("active");menuButton.setAttribute("aria-expanded","false");menuButton.innerHTML="☰";document.body.style.overflow=""}menuButton.addEventListener("click",function(){if(sidebar.classList.contains("mobile-sidebar-open")||document.body.classList.contains("sidebar-mobile-open")){closeSidebar()}else{openSidebar()}});overlay.addEventListener("click",closeSidebar);sidebar.addEventListener("click",function(event){if(event.target.closest("a")&&window.innerWidth<=768){closeSidebar()}});document.addEventListener("keydown",function(event){if(event.key==="Escape"&&window.innerWidth<=768){closeSidebar()}});window.addEventListener("resize",function(){if(window.innerWidth>768){closeSidebar()}})})();
</script>
</body> 
</html>