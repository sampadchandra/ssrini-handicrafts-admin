<?php
/**
 * =========================================================
 * SSRINI HANDICRAFTS - ADMIN INVOICES
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$pageTitle = 'Invoices';

// Helper Functions
function invoiceEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function findInvoiceColumn($columns, $candidates) {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) return $candidate;
    }
    return null;
}

function formatInvoiceAmount($amount) {
    return ($amount === null || $amount === '') ? '₹0.00' : '₹' . number_format((float)$amount, 2, '.', ',');
}

function formatInvoiceDate($date) {
    if (!$date) return '-';
    $timestamp = strtotime($date);
    return ($timestamp === false) ? invoiceEscape($date) : date('d M Y', $timestamp);
}

function invoiceStatusClass($status) {
    $status = strtolower(trim((string)$status));
    if (in_array($status, ['paid', 'completed', 'complete', 'success', 'successful'], true)) return 'invoice-status-success';
    if (in_array($status, ['pending', 'unpaid', 'due', 'processing'], true)) return 'invoice-status-warning';
    if (in_array($status, ['cancelled', 'canceled', 'failed', 'refunded'], true)) return 'invoice-status-danger';
    return 'invoice-status-neutral';
}

// Data Variables
$invoices = [];
$invoiceColumns = [];
$invoiceTableExists = false;
$databaseError = null;
$statusOptions = [];

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

try {
    // Check Table Existence
    $tableCheck = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'invoices'");
    $invoiceTableExists = ((int)$tableCheck->fetchColumn() > 0);

    if ($invoiceTableExists) {
        $columnStatement = $pdo->query("SHOW COLUMNS FROM invoices");
        $columnRows = $columnStatement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columnRows as $columnRow) {
            if (isset($columnRow['Field'])) $invoiceColumns[] = $columnRow['Field'];
        }

        // Map Columns
        $invoiceNumberColumn = findInvoiceColumn($invoiceColumns, ['invoice_number', 'invoice_no', 'invoice_num', 'number', 'id']);
        $orderIdColumn      = findInvoiceColumn($invoiceColumns, ['order_id', 'order', 'order_number']);
        $customerNameColumn = findInvoiceColumn($invoiceColumns, ['customer_name', 'customer', 'name', 'billing_name']);
        $customerEmailColumn= findInvoiceColumn($invoiceColumns, ['customer_email', 'email', 'billing_email']);
        $customerPhoneColumn= findInvoiceColumn($invoiceColumns, ['customer_phone', 'phone', 'mobile', 'contact']);
        $totalColumn        = findInvoiceColumn($invoiceColumns, ['total_amount', 'grand_total', 'total', 'amount', 'invoice_total']);
        $paymentMethodColumn= findInvoiceColumn($invoiceColumns, ['payment_method', 'payment_type', 'method']);
        $paymentStatusColumn= findInvoiceColumn($invoiceColumns, ['payment_status', 'paid_status']);
        $statusColumn       = findInvoiceColumn($invoiceColumns, ['status', 'invoice_status']);
        $dateColumn         = findInvoiceColumn($invoiceColumns, ['invoice_date', 'created_at', 'date', 'created_on']);

        $activeStatusCol = $paymentStatusColumn ?: $statusColumn;

        // FETCH ALL STATUS OPTIONS (FIXED: Fetches distinct status from whole DB for dropdown)
        if ($activeStatusCol) {
            $statusStmt = $pdo->query("SELECT DISTINCT `{$activeStatusCol}` FROM invoices WHERE `{$activeStatusCol}` IS NOT NULL AND `{$activeStatusCol}` != ''");
            $statusOptions = $statusStmt->fetchAll(PDO::FETCH_COLUMN);
            sort($statusOptions);
        }

        // Build Query Conditions
        $whereParts = [];
        $parameters = [];

        if ($search !== '') {
            $searchCols = array_filter([$invoiceNumberColumn, $orderIdColumn, $customerNameColumn, $customerEmailColumn, $customerPhoneColumn]);
            if (!empty($searchCols)) {
                $searchParts = [];
                foreach (array_values($searchCols) as $idx => $col) {
                    $param = ':search' . $idx;
                    $searchParts[] = "`{$col}` LIKE {$param}";
                    $parameters[$param] = '%' . $search . '%';
                }
                $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
            }
        }

        if ($statusFilter !== '' && $activeStatusCol) {
            $whereParts[] = "`{$activeStatusCol}` = :status";
            $parameters[':status'] = $statusFilter;
        }

        $whereSql = !empty($whereParts) ? 'WHERE ' . implode(' AND ', $whereParts) : '';
        $orderSql = $dateColumn ? "ORDER BY `{$dateColumn}` DESC" : "ORDER BY 1 DESC";

        $invoiceQuery = "SELECT * FROM invoices {$whereSql} {$orderSql}";
        $invoiceStatement = $pdo->prepare($invoiceQuery);
        $invoiceStatement->execute($parameters);
        $invoices = $invoiceStatement->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $exception) {
    $databaseError = $exception->getMessage();
    $invoices = [];
}

// Calculate Summary Statistics
$totalInvoices = count($invoices);
$totalInvoiceValue = 0;
$paidInvoices = 0;

foreach ($invoices as $inv) {
    if ($totalColumn) $totalInvoiceValue += (float)($inv[$totalColumn] ?? 0);
    
    $st = strtolower(trim((string)($inv[$paymentStatusColumn] ?? $inv[$statusColumn] ?? '')));
    if (in_array($st, ['paid', 'completed', 'complete', 'success', 'successful'], true)) {
        $paidInvoices++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= invoiceEscape($pageTitle) ?> | Ssrini Handicrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        /* Modern Glassmorphic Dashboard Layout with Visible Background */
        body {
            color: #0f172a;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            position: relative;
        }

        /* 40% Background Image Visibility Layer */
        .main-area {
            position: relative;
            background-color: #f8fafc;
        }

        .main-area::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: inherit; /* Uses page pattern */
            opacity: 0.4; /* 40% Visibility constraint */
            pointer-events: none;
            z-index: 0;
        }

        .page-content {
            position: relative;
            z-index: 1;
        }

        /* Glass Cards with Clear Contrast */
        .invoice-card, 
        .invoice-filter-panel, 
        .invoice-summary-card, 
        .invoice-table-card {
            background: rgba(255, 255, 255, 0.92) !important;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 14px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        }

        /* Summary Cards Header styling */
        .invoice-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .invoice-summary-card { padding: 20px; }
        .invoice-summary-label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .invoice-summary-value {
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Filter Panel */
        .invoice-filter-panel { padding: 18px; margin-bottom: 24px; }
        .invoice-filter-form {
            display: grid;
            grid-template-columns: 1fr 220px auto auto;
            gap: 12px;
            align-items: center;
        }

        .invoice-search-input, .invoice-filter-select {
            height: 42px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0 14px;
            background: #ffffff;
            color: #0f172a;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }

        .invoice-search-input:focus, .invoice-filter-select:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
        }

        /* High Contrast Table Controls */
        .invoice-table-wrapper { width: 100%; overflow-x: auto; }
        .invoice-table { width: 100%; border-collapse: collapse; min-width: 850px; }
        
        .invoice-table th {
            padding: 14px 18px;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
        }

        .invoice-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #1e293b;
            vertical-align: middle;
        }

        .invoice-table tbody tr:hover { background: rgba(248, 250, 252, 0.9); }
        .invoice-number { font-weight: 700; color: #7c3aed; }
        .invoice-customer-name { font-weight: 600; color: #0f172a; }
        .invoice-customer-contact { color: #64748b; font-size: 12px; margin-top: 2px; }
        .invoice-amount { font-weight: 700; color: #0f172a; }

        /* Badges */
        .invoice-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }
        .invoice-status-success { background: #dcfce7; color: #15803d; }
        .invoice-status-warning { background: #fef9c3; color: #a16207; }
        .invoice-status-danger  { background: #fee2e2; color: #b91c1c; }
        .invoice-status-neutral { background: #f1f5f9; color: #475569; }

        /* Buttons */
        .invoice-actions { display: flex; gap: 8px; }
        .invoice-action-btn {
            height: 34px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .invoice-action-btn:hover { border-color: #8b5cf6; color: #7c3aed; }
        .invoice-action-btn.primary { background: #7c3aed; border: none; color: #ffffff; }
        .invoice-action-btn.primary:hover { background: #6d28d9; }

        /* Modal View */
        .invoice-modal-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .invoice-modal-overlay.active { display: flex; }
        .invoice-modal {
            width: min(750px, 100%); max-height: calc(100vh - 40px); overflow-y: auto;
            background: #ffffff; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }
        .invoice-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
        .invoice-modal-body { padding: 24px; }
        .invoice-view-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .invoice-view-item { padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
        .invoice-view-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px; }
        .invoice-view-value { font-size: 14px; font-weight: 600; color: #0f172a; word-break: break-word; }
        .invoice-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #e2e8f0; }

        @media (max-width: 768px) {
            .invoice-filter-form { grid-template-columns: 1fr; }
            .invoice-view-grid { grid-template-columns: 1fr; }
        }
        @media print {
            body * { visibility: hidden !important; }
            #printInvoiceArea, #printInvoiceArea * { visibility: visible !important; }
            #printInvoiceArea { position: absolute; left: 0; top: 0; width: 100%; background: #fff; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content">
            <!-- Page Header -->
            <section class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 class="page-title" style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Invoices</h1>
                    <p class="page-description" style="color: #64748b; margin: 4px 0 0;">Manage and view your store invoices</p>
                </div>
                <button type="button" class="btn btn-secondary invoice-action-btn" onclick="window.location.reload();">🔄 Refresh</button>
            </section>

            <?php if ($databaseError): ?>
                <div style="background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; padding:14px; border-radius:10px; margin-bottom:20px;">
                    <strong>Unable to load invoices:</strong> <?= invoiceEscape($databaseError) ?>
                </div>
            <?php endif; ?>

            <!-- Summary Cards -->
            <section class="invoice-summary-grid">
                <div class="invoice-summary-card">
                    <div class="invoice-summary-label">Total Invoices</div>
                    <div class="invoice-summary-value"><?= number_format($totalInvoices) ?></div>
                </div>
                <div class="invoice-summary-card">
                    <div class="invoice-summary-label">Invoice Value</div>
                    <div class="invoice-summary-value"><?= formatInvoiceAmount($totalInvoiceValue) ?></div>
                </div>
                <div class="invoice-summary-card">
                    <div class="invoice-summary-label">Paid Invoices</div>
                    <div class="invoice-summary-value"><?= number_format($paidInvoices) ?></div>
                </div>
            </section>

            <!-- Filter Panel -->
            <section class="invoice-filter-panel">
                <form method="GET" action="" class="invoice-filter-form">
                    <input type="search" name="search" class="invoice-search-input" placeholder="Search invoice, order or customer..." value="<?= invoiceEscape($search) ?>" autocomplete="off">
                    
                    <select name="status" class="invoice-filter-select">
                        <option value="">All Status</option>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?= invoiceEscape($option) ?>" <?= ($statusFilter === $option) ? 'selected' : '' ?>>
                                <?= invoiceEscape(ucwords(str_replace('_', ' ', $option))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="invoice-action-btn primary" style="height:42px;">🔍 Filter</button>
                    <a href="invoices.php" class="invoice-action-btn" style="height:42px; display:inline-flex; align-items:center;">Reset</a>
                </form>
            </section>

            <!-- Table Card -->
            <section class="invoice-table-card">
                <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin:0; font-size: 16px; font-weight:700; color:#0f172a;">Invoice List</h3>
                        <span style="color:#64748b; font-size:12px;">All invoices generated for your store</span>
                    </div>
                    <span style="background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;">
                        <?= number_format($totalInvoices) ?> Invoice(s)
                    </span>
                </div>

                <?php if (!empty($invoices)): ?>
                    <div class="invoice-table-wrapper">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Order</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $idx => $inv): 
                                    $invNum = !empty($inv[$invoiceNumberColumn]) ? $inv[$invoiceNumberColumn] : 'INV-' . ($idx + 1);
                                    $custName = !empty($inv[$customerNameColumn]) ? $inv[$customerNameColumn] : 'Customer';
                                    $contact = $inv[$customerPhoneColumn] ?? $inv[$customerEmailColumn] ?? '';
                                    $amount = $inv[$totalColumn] ?? 0;
                                    $payMethod = $inv[$paymentMethodColumn] ?? 'COD';
                                    $displayStatus = $inv[$paymentStatusColumn] ?? $inv[$statusColumn] ?? 'Pending';
                                    $date = $inv[$dateColumn] ?? null;
                                    $invJson = json_encode($inv, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                ?>
                                    <tr>
                                        <td><div class="invoice-number"><?= invoiceEscape($invNum) ?></div></td>
                                        <td>
                                            <div class="invoice-customer-name"><?= invoiceEscape($custName) ?></div>
                                            <?php if ($contact): ?><div class="invoice-customer-contact"><?= invoiceEscape($contact) ?></div><?php endif; ?>
                                        </td>
                                        <td><?= invoiceEscape($inv[$orderIdColumn] ?? '-') ?></td>
                                        <td><span class="invoice-amount"><?= formatInvoiceAmount($amount) ?></span></td>
                                        <td><?= invoiceEscape(strtoupper($payMethod)) ?></td>
                                        <td>
                                            <span class="invoice-status <?= invoiceStatusClass($displayStatus) ?>">
                                                ● <?= invoiceEscape(ucwords(str_replace('_', ' ', $displayStatus))) ?>
                                            </span>
                                        </td>
                                        <td><?= formatInvoiceDate($date) ?></td>
                                        <td>
                                            <div class="invoice-actions">
                                                <button type="button" class="invoice-action-btn primary" onclick='viewInvoice(<?= $invJson ?>)'>View</button>
                                                <button type="button" class="invoice-action-btn" onclick='printInvoice(<?= $invJson ?>)'>Print</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px 20px;">
                        <div style="font-size: 40px; margin-bottom: 10px;">🧾</div>
                        <h3 style="margin: 0 0 6px; font-size: 16px; color:#0f172a;">No invoices found</h3>
                        <p style="margin: 0; color: #64748b; font-size: 13px;">Try adjusting your filter or search criteria.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<!-- Invoice Modal -->
<div class="invoice-modal-overlay" id="invoiceModal">
    <div class="invoice-modal">
        <div class="invoice-modal-header">
            <h3 style="margin:0; font-size:18px; font-weight:700;" id="invoiceModalTitle">Invoice Details</h3>
            <button type="button" style="border:none; background:none; font-size:22px; cursor:pointer;" id="invoiceModalClose">&times;</button>
        </div>
        <div class="invoice-modal-body" id="invoiceModalBody"></div>
        <div class="invoice-modal-footer">
            <button type="button" class="invoice-action-btn" id="invoiceModalCancel">Close</button>
            <button type="button" class="invoice-action-btn primary" id="invoiceModalPrint">🖨️ Print Invoice</button>
        </div>
    </div>
</div>

<div id="printInvoiceArea" style="display:none;"></div>

<script src="../assets/js/admin.js"></script>
<script>
    const modal = document.getElementById('invoiceModal');
    const modalBody = document.getElementById('invoiceModalBody');
    let currentInvoice = null;

    function viewInvoice(invoice) {
        currentInvoice = invoice;
        modalBody.innerHTML = '';
        const grid = document.createElement('div');
        grid.className = 'invoice-view-grid';

        Object.keys(invoice).forEach(key => {
            const item = document.createElement('div');
            item.className = 'invoice-view-item';
            
            let val = invoice[key] ?? '-';
            if (key.toLowerCase().includes('amount') || key.toLowerCase().includes('total')) {
                val = '₹' + Number(val).toLocaleString('en-IN', {minimumFractionDigits: 2});
            }

            item.innerHTML = `
                <div class="invoice-view-label">${key.replace(/_/g, ' ')}</div>
                <div class="invoice-view-value">${val}</div>
            `;
            grid.appendChild(item);
        });

        modalBody.appendChild(grid);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        currentInvoice = null;
    }

    document.getElementById('invoiceModalClose').addEventListener('click', closeModal);
    document.getElementById('invoiceModalCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if(e.target === modal) closeModal(); });

    document.getElementById('invoiceModalPrint').addEventListener('click', () => {
        if(currentInvoice) printInvoice(currentInvoice);
    });

    function printInvoice(invoice) {
        const area = document.getElementById('printInvoiceArea');
        let html = `
            <div style="font-family: Arial, sans-serif; padding: 20px;">
                <div style="border-bottom:2px solid #7c3aed; padding-bottom:10px; margin-bottom:20px;">
                    <h2 style="margin:0;">Ssrini Handicrafts</h2>
                    <p style="margin:0; color:#666;">Invoice Details</p>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
        `;
        Object.keys(invoice).forEach(k => {
            html += `<div style="border:1px solid #ddd; padding:8px;"><strong>${k.replace(/_/g, ' ')}:</strong> ${invoice[k] ?? '-'}</div>`;
        });
        html += `</div></div>`;
        area.innerHTML = html;
        area.style.display = 'block';
        window.print();
        area.style.display = 'none';
    }
</script>
</body>
</html>