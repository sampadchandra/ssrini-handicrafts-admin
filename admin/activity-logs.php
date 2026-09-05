<?php
/**
 * =========================================================
 * SSRINI HANDICRAFTS
 * ADMIN ACTIVITY LOGS
 * =========================================================
 * File: admin/activity-logs.php
 * Database: ssrini_handcrafts
 *
 * Uses the existing activity_logs table:
 * id, admin_id, action, description, ip_address, created_at
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

if(!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function activityUrl(array $params = []): string {
    $allowed = ['search','action','date_from','date_to','page'];
    $query = [];
    foreach($allowed as $key) {
        if(isset($params[$key]) && (string)$params[$key] !== '') $query[$key] = $params[$key];
    }
    return 'activity-logs.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

function actionLabel(string $action): string {
    $action = trim($action);
    return $action === '' ? 'Activity' : ucwords(str_replace(['_','-'],' ',strtolower($action)));
}

function actionClass(string $action): string {
    $action = strtolower($action);
    if(strpos($action,'delete') !== false || strpos($action,'remove') !== false) return 'danger';
    if(strpos($action,'login') !== false) return 'login';
    if(strpos($action,'logout') !== false) return 'logout';
    if(strpos($action,'create') !== false || strpos($action,'add') !== false) return 'create';
    if(strpos($action,'update') !== false || strpos($action,'edit') !== false) return 'update';
    if(strpos($action,'order') !== false) return 'order';
    return 'default';
}

function formatActivityDate($value): string {
    if(empty($value)) return '—';
    $time = strtotime((string)$value);
    return $time !== false ? date('d M Y, h:i A',$time) : (string)$value;
}

$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$actionFilter = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';
$currentPage = max(1,(int)($_GET['page'] ?? 1));
$perPage = 15;

if($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)) $dateFrom = '';
if($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)) $dateTo = '';
if($dateFrom !== '' && $dateTo !== '' && $dateTo < $dateFrom) [$dateFrom,$dateTo] = [$dateTo,$dateFrom];

$activityLogs = [];
$actionOptions = [];
$totalLogs = 0;
$totalPages = 1;
$displayStart = 0;
$displayEnd = 0;
$activityError = null;

try {
    $actionStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs WHERE action IS NOT NULL AND TRIM(action) <> '' ORDER BY action ASC");
    $actionOptions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

    $where = [];
    $params = [];

    if($search !== '') {
        $where[] = "(action LIKE :search_action OR description LIKE :search_description OR ip_address LIKE :search_ip)";
        $term = '%' . $search . '%';
        $params[':search_action'] = $term;
        $params[':search_description'] = $term;
        $params[':search_ip'] = $term;
    }

    if($actionFilter !== '') {
        $where[] = "action = :action_filter";
        $params[':action_filter'] = $actionFilter;
    }

    if($dateFrom !== '') {
        $where[] = "created_at >= :date_from";
        $params[':date_from'] = $dateFrom . ' 00:00:00';
    }

    if($dateTo !== '') {
        $where[] = "created_at <= :date_to";
        $params[':date_to'] = $dateTo . ' 23:59:59';
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ',$where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs {$whereSql}");
    $countStmt->execute($params);
    $totalLogs = (int)$countStmt->fetchColumn();

    $totalPages = max(1,(int)ceil($totalLogs / $perPage));
    if($currentPage > $totalPages) $currentPage = $totalPages;

    $offset = ($currentPage - 1) * $perPage;

    $sql = "
        SELECT id, admin_id, action, description, ip_address, created_at
        FROM activity_logs
        {$whereSql}
        ORDER BY created_at DESC, id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($totalLogs > 0) {
        $displayStart = $offset + 1;
        $displayEnd = min($offset + $perPage,$totalLogs);
    }
} catch(Throwable $e) {
    error_log('Activity Logs Error: ' . $e->getMessage());
    $activityError = 'Activity logs could not be loaded. Please check the database connection and activity_logs table.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ssrini Handicrafts Admin Activity Logs">
    <title>Activity Logs | Ssrini Handicrafts</title>
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        /* Activity page must stay above the global artwork layer. */
        .page-content.activity-content{position:relative!important;isolation:isolate!important;min-height:calc(100vh - 70px);overflow:visible!important}
        .page-content.activity-content::before,.page-content.activity-content::after{z-index:-10!important;pointer-events:none!important}
        .page-content.activity-content>.activity-content-inner{position:relative!important;z-index:20!important;width:100%;max-width:1750px;margin:0 auto}
        .activity-content-inner *{box-sizing:border-box}
        .activity-page-header{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
        .activity-title h1{margin:0;color:#171a2d;font-size:23px;font-weight:780;line-height:1.2}
        .activity-title p{margin:5px 0 0;color:#667085;font-size:11.5px;line-height:1.5}
        .activity-refresh{display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 15px;border:1px solid #e3dfea;border-radius:10px;background:#fff;color:#4f4857;text-decoration:none;font-size:12px;font-weight:700;box-shadow:0 4px 12px rgba(25,30,55,.05)}
        .activity-refresh:hover{border-color:#cdb7df;color:#7627c9}
        .activity-filter-card,.activity-card{background:rgba(255,255,255,.97);border:1px solid #eeeaf2;border-radius:16px;box-shadow:0 7px 22px rgba(30,20,50,.07);backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px)}
        .activity-filter-card{padding:18px 20px;margin-bottom:18px}
        .activity-filter-grid{display:grid;grid-template-columns:minmax(210px,2fr) minmax(150px,1fr) minmax(145px,1fr) minmax(145px,1fr) auto;gap:12px;align-items:end}
        .activity-filter-group{display:flex;flex-direction:column;gap:7px;min-width:0}
        .activity-filter-label{font-size:11px;font-weight:700;color:#77717f}
        .activity-filter-input,.activity-filter-select{width:100%;height:42px;border:1px solid #e3dfea;border-radius:10px;background:#fff;color:#25212d;padding:0 12px;outline:none;font-size:12px}
        .activity-filter-input:focus,.activity-filter-select:focus{border-color:#9b48d1;box-shadow:0 0 0 3px rgba(155,72,209,.1)}
        .activity-filter-actions{display:flex;gap:8px}
        .activity-filter-button,.activity-clear-button{height:42px;padding:0 15px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;white-space:nowrap;text-decoration:none}
        .activity-filter-button{border:0;background:linear-gradient(135deg,#7627c9,#c52b9f);color:#fff;cursor:pointer;box-shadow:0 7px 16px rgba(118,39,201,.2)}
        .activity-clear-button{border:1px solid #e3dfea;background:#fff;color:#625b6b}
        .activity-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;color:#77717f;font-size:12px}
        .activity-summary strong{color:#25212d}
        .activity-card{overflow:hidden}
        .activity-card-head{padding:18px 20px;border-bottom:1px solid #eeeaf2;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .activity-card-title{font-size:15px;font-weight:750;color:#25212d}
        .activity-card-desc{margin-top:4px;color:#9a94a2;font-size:11px}
        .activity-record-count{color:#9a94a2;font-size:11px}
        .activity-table-wrap{width:100%;overflow-x:auto}
        .activity-table{width:100%;min-width:880px;border-collapse:collapse;text-align:left}
        .activity-table th{padding:13px 16px;background:#faf9fc;border-bottom:1px solid #eeeaf2;color:#8a8392;font-size:10px;font-weight:750;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
        .activity-table td{padding:14px 16px;border-bottom:1px solid #f1eef4;vertical-align:middle;color:#5f5865;font-size:11px}
        .activity-table tbody tr:hover{background:#fcfbfd}
        .activity-id{color:#9a94a2;font-size:10px;font-weight:700;white-space:nowrap}
        .activity-badge{display:inline-flex;align-items:center;justify-content:center;min-height:27px;padding:4px 9px;border-radius:8px;font-size:10px;font-weight:750;white-space:nowrap}
        .activity-badge.default{background:#f1eafd;color:#7627c9}.activity-badge.create{background:#e8f8ef;color:#23804c}.activity-badge.update{background:#fff4df;color:#a56a00}.activity-badge.danger{background:#fff0f0;color:#c53030}.activity-badge.login{background:#eaf4ff;color:#2369a8}.activity-badge.logout{background:#f1eef3;color:#69616f}.activity-badge.order{background:#f4eaff;color:#7b38a9}
        .activity-description{max-width:420px;color:#4f4857;line-height:1.5}
        .activity-admin{font-weight:700;color:#403a47;white-space:nowrap}
        .activity-ip{font-family:Consolas,Monaco,monospace;color:#706976;font-size:10px;white-space:nowrap}
        .activity-date{white-space:nowrap;color:#4f4857;font-weight:600}
        .activity-view{border:1px solid #e3dfea;background:#fff;color:#6e6676;border-radius:8px;padding:6px 10px;cursor:pointer;font-size:10px;font-weight:700}
        .activity-view:hover{border-color:#cdb7df;color:#7627c9;background:#faf7fd}
        .activity-empty,.activity-error{padding:55px 20px;text-align:center}
        .activity-empty{color:#9a94a2}
        .activity-empty-icon{font-size:28px;margin-bottom:10px}
        .activity-empty-title{color:#25212d;font-size:14px;font-weight:750}
        .activity-empty-desc{margin-top:6px;font-size:11px;line-height:1.5}
        .activity-error{margin-bottom:18px;padding:14px 16px;border-radius:10px;background:#fff3f3;border:1px solid #ffd5d5;color:#c53030;text-align:left;font-size:12px}
        .activity-pagination{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:15px 18px;border-top:1px solid #eeeaf2;flex-wrap:wrap}
        .activity-pagination-links{display:flex;gap:5px;align-items:center}
        .activity-page-link{min-width:32px;height:32px;padding:0 8px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e3dfea;border-radius:8px;background:#fff;color:#6e6676;text-decoration:none;font-size:10px;font-weight:700}
        .activity-page-link:hover{border-color:#cdb7df;color:#7627c9}
        .activity-page-link.active{border-color:#7627c9;background:linear-gradient(135deg,#7627c9,#c52b9f);color:#fff}
        .activity-page-link.disabled{opacity:.45;pointer-events:none}
        .activity-modal{display:none;position:fixed;inset:0;z-index:99999;background:rgba(27,20,35,.48);align-items:center;justify-content:center;padding:20px}
        .activity-modal.show{display:flex}
        .activity-modal-box{width:min(620px,100%);max-height:calc(100vh - 40px);overflow:auto;background:#fff;border-radius:18px;box-shadow:0 25px 70px rgba(30,20,50,.25)}
        .activity-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #eeeaf2}
        .activity-modal-head strong{font-size:15px;color:#25212d}.activity-modal-close{width:32px;height:32px;border:0;border-radius:8px;background:#f4f1f6;color:#6c6473;cursor:pointer;font-size:17px}
        .activity-modal-body{padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .activity-modal-item{padding:13px;border:1px solid #eeeaf2;border-radius:10px;background:#fcfbfd}
        .activity-modal-item.full{grid-column:1/-1}.activity-modal-label{margin-bottom:6px;color:#9a94a2;font-size:9px;font-weight:750;text-transform:uppercase;letter-spacing:.05em}.activity-modal-value{color:#403a47;font-size:11px;line-height:1.55;word-break:break-word}
        @media(max-width:1150px){.activity-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.activity-filter-actions{grid-column:1/-1}}
        @media(max-width:700px){.activity-page-header{align-items:flex-start;flex-direction:column}.activity-filter-grid{grid-template-columns:1fr}.activity-filter-actions{grid-column:auto}.activity-filter-button,.activity-clear-button{flex:1}.activity-summary{align-items:flex-start;flex-direction:column}.activity-modal-body{grid-template-columns:1fr}.activity-modal-item.full{grid-column:auto}}
    </style>
</head>
<body>
<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="page-content activity-content">
            <div class="activity-content-inner">

                <section class="activity-page-header">
                    <div class="activity-title">
                        <h1>Activity Logs</h1>
                        <p>Monitor administrator actions and important changes made in the store.</p>
                    </div>
                    <a class="activity-refresh" href="<?=e(activityUrl(['search'=>$search,'action'=>$actionFilter,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$currentPage]))?>">🔄 Refresh Logs</a>
                </section>

                <?php if($activityError !== null): ?>
                    <div class="activity-error">⚠️ <?=e($activityError)?></div>
                <?php endif; ?>

                <form method="GET" action="activity-logs.php" class="activity-filter-card">
                    <div class="activity-filter-grid">
                        <div class="activity-filter-group">
                            <label class="activity-filter-label" for="activitySearch">Search Activity</label>
                            <input class="activity-filter-input" id="activitySearch" type="search" name="search" value="<?=e($search)?>" placeholder="Search action, description or IP..." autocomplete="off">
                        </div>

                        <div class="activity-filter-group">
                            <label class="activity-filter-label" for="activityAction">Action</label>
                            <select class="activity-filter-select" id="activityAction" name="action">
                                <option value="">All Actions</option>
                                <?php foreach($actionOptions as $option): ?>
                                    <option value="<?=e($option)?>" <?=$actionFilter === $option ? 'selected' : ''?>><?=e(actionLabel((string)$option))?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="activity-filter-group">
                            <label class="activity-filter-label" for="activityFrom">From Date</label>
                            <input class="activity-filter-input" id="activityFrom" type="date" name="date_from" value="<?=e($dateFrom)?>">
                        </div>

                        <div class="activity-filter-group">
                            <label class="activity-filter-label" for="activityTo">To Date</label>
                            <input class="activity-filter-input" id="activityTo" type="date" name="date_to" value="<?=e($dateTo)?>">
                        </div>

                        <div class="activity-filter-actions">
                            <button class="activity-filter-button" type="submit">Apply Filter</button>
                            <a class="activity-clear-button" href="activity-logs.php">Clear</a>
                        </div>
                    </div>
                </form>

                <div class="activity-summary">
                    <div>
                        <?php if($totalLogs > 0): ?>
                            Showing <strong><?=e($displayStart)?></strong> to <strong><?=e($displayEnd)?></strong> of <strong><?=e($totalLogs)?></strong> activity logs.
                        <?php else: ?>
                            No activity logs found.
                        <?php endif; ?>
                    </div>
                    <div>Database: <strong>activity_logs</strong></div>
                </div>

                <section class="activity-card">
                    <div class="activity-card-head">
                        <div>
                            <div class="activity-card-title">Administrator Activity</div>
                            <div class="activity-card-desc">Recent actions recorded inside the admin panel.</div>
                        </div>
                        <div class="activity-record-count"><?=e($totalLogs)?> records</div>
                    </div>

                    <?php if(!empty($activityLogs)): ?>
                        <div class="activity-table-wrap">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Admin ID</th>
                                        <th>IP Address</th>
                                        <th>Date &amp; Time</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($activityLogs as $log): ?>
                                        <?php
                                        $action = trim((string)($log['action'] ?? '')) ?: 'Activity';
                                        $description = trim((string)($log['description'] ?? ''));
                                        $adminId = (int)($log['admin_id'] ?? 0);
                                        $ip = trim((string)($log['ip_address'] ?? ''));
                                        $createdAt = (string)($log['created_at'] ?? '');
                                        $modalData = [
                                            'id'=>(string)($log['id'] ?? ''),
                                            'action'=>actionLabel($action),
                                            'description'=>$description ?: 'No description available.',
                                            'admin'=>$adminId > 0 ? 'Admin #' . $adminId : 'System / Admin',
                                            'ip'=>$ip ?: 'Not available',
                                            'date'=>formatActivityDate($createdAt)
                                        ];
                                        ?>
                                        <tr>
                                            <td><span class="activity-id">#<?=e($log['id'] ?? '—')?></span></td>
                                            <td><span class="activity-badge <?=e(actionClass($action))?>"><?=e(actionLabel($action))?></span></td>
                                            <td><div class="activity-description"><?=e($description ?: 'No description available.')?></div></td>
                                            <td><span class="activity-admin"><?=e($adminId > 0 ? 'Admin #' . $adminId : 'System / Admin')?></span></td>
                                            <td><span class="activity-ip"><?=e($ip ?: '—')?></span></td>
                                            <td><span class="activity-date"><?=e(formatActivityDate($createdAt))?></span></td>
                                            <td><button type="button" class="activity-view" data-log="<?=e(json_encode($modalData,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP))?>">View</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if($totalPages > 1): ?>
                            <div class="activity-pagination">
                                <div>Page <strong><?=e($currentPage)?></strong> of <strong><?=e($totalPages)?></strong></div>
                                <div class="activity-pagination-links">
                                    <a class="activity-page-link <?=$currentPage <= 1 ? 'disabled' : ''?>" href="<?=e(activityUrl(['search'=>$search,'action'=>$actionFilter,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>max(1,$currentPage-1)]))?>">‹</a>
                                    <?php
                                    $start = max(1,$currentPage-2);
                                    $end = min($totalPages,$currentPage+2);
                                    ?>
                                    <?php for($i=$start;$i<=$end;$i++): ?>
                                        <a class="activity-page-link <?=$i === $currentPage ? 'active' : ''?>" href="<?=e(activityUrl(['search'=>$search,'action'=>$actionFilter,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>$i]))?>"><?=e($i)?></a>
                                    <?php endfor; ?>
                                    <a class="activity-page-link <?=$currentPage >= $totalPages ? 'disabled' : ''?>" href="<?=e(activityUrl(['search'=>$search,'action'=>$actionFilter,'date_from'=>$dateFrom,'date_to'=>$dateTo,'page'=>min($totalPages,$currentPage+1)]))?>">›</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="activity-empty">
                            <div class="activity-empty-icon">📝</div>
                            <div class="activity-empty-title">No Activity Logs Found</div>
                            <div class="activity-empty-desc">
                                <?php if($search !== '' || $actionFilter !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                                    No activity logs match your current filters. Try clearing the filters.
                                <?php else: ?>
                                    Administrator activity will appear here when actions are recorded.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

            </div>
        </div>
    </main>
</div>

<div class="activity-modal" id="activityModal" aria-hidden="true">
    <div class="activity-modal-box" role="dialog" aria-modal="true" aria-labelledby="activityModalTitle">
        <div class="activity-modal-head">
            <strong id="activityModalTitle">Activity Details</strong>
            <button type="button" class="activity-modal-close" id="activityModalClose" aria-label="Close">×</button>
        </div>
        <div class="activity-modal-body">
            <div class="activity-modal-item"><div class="activity-modal-label">Log ID</div><div class="activity-modal-value" id="modalId">—</div></div>
            <div class="activity-modal-item"><div class="activity-modal-label">Action</div><div class="activity-modal-value" id="modalAction">—</div></div>
            <div class="activity-modal-item"><div class="activity-modal-label">Administrator</div><div class="activity-modal-value" id="modalAdmin">—</div></div>
            <div class="activity-modal-item"><div class="activity-modal-label">Date &amp; Time</div><div class="activity-modal-value" id="modalDate">—</div></div>
            <div class="activity-modal-item"><div class="activity-modal-label">IP Address</div><div class="activity-modal-value" id="modalIp">—</div></div>
            <div class="activity-modal-item full"><div class="activity-modal-label">Description</div><div class="activity-modal-value" id="modalDescription">—</div></div>
        </div>
    </div>
</div>

<script src="../assets/js/admin.js"></script>
<script>
document.querySelectorAll('.activity-view').forEach(function(button){
    button.addEventListener('click',function(){
        try{
            const data=JSON.parse(this.getAttribute('data-log')||'{}');
            document.getElementById('modalId').textContent=data.id||'—';
            document.getElementById('modalAction').textContent=data.action||'—';
            document.getElementById('modalAdmin').textContent=data.admin||'—';
            document.getElementById('modalDate').textContent=data.date||'—';
            document.getElementById('modalIp').textContent=data.ip||'—';
            document.getElementById('modalDescription').textContent=data.description||'—';
            const modal=document.getElementById('activityModal');
            modal.classList.add('show');
            modal.setAttribute('aria-hidden','false');
        }catch(error){}
    });
});
function closeActivityModal(){
    const modal=document.getElementById('activityModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden','true');
}
document.getElementById('activityModalClose').addEventListener('click',closeActivityModal);
document.getElementById('activityModal').addEventListener('click',function(event){if(event.target===this) closeActivityModal();});
document.addEventListener('keydown',function(event){if(event.key==='Escape') closeActivityModal();});
</script>
</body>
</html>
