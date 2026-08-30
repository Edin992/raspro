<?php
/**
 * admin/logs.php - Pregled svih logova
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Logovi aktivnosti';
$db = getDatabaseConnection();

// ============================================
// FILTERI I PAGINACIJA
// ============================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$actionFilter = isset($_GET['action']) ? $_GET['action'] : 'all';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Mapiranje akcija
$actionLabels = [
    'login_success' => 'Prijava (uspešna)',
    'login_failed' => 'Prijava (neuspešna)',
    'logout' => 'Odjava',
    'register' => 'Registracija',
    'ad_create' => 'Kreiran oglas',
    'ad_update' => 'Izmena oglasa',
    'ad_delete' => 'Obrisan oglas',
    'ad_view' => 'Pregled oglasa',
    'message_sent' => 'Poslata poruka',
    'package_upgrade' => 'Nadogradnja paketa',
    'package_activated' => 'Paket aktiviran',
    'package_request' => 'Zahtev za paket',
    'payment_email_sent' => 'Email za uplatu poslat'
];

$actionColors = [
    'login_success' => 'success',
    'login_failed' => 'danger',
    'logout' => 'secondary',
    'register' => 'info',
    'ad_create' => 'primary',
    'ad_update' => 'warning',
    'ad_delete' => 'danger',
    'ad_view' => 'info',
    'message_sent' => 'primary',
    'package_upgrade' => 'success',
    'package_activated' => 'success',
    'package_request' => 'warning',
    'payment_email_sent' => 'info'
];

// ============================================
// IZGRADNJA UPITA ZA user_logs (JEDNOSTAVNIJE)
// ============================================
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR ul.action LIKE ? OR ul.ip_address LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
}

if ($actionFilter !== 'all') {
    $where[] = "ul.action = ?";
    $params[] = $actionFilter;
}

if ($dateFrom) {
    $where[] = "DATE(ul.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(ul.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ============================================
// DOHVATANJE LOGOVA (SAMO IZ user_logs)
// ============================================
$sql = "
    SELECT 
        ul.*,
        u.username,
        u.email,
        'user' as log_source
    FROM user_logs ul
    LEFT JOIN users u ON ul.user_id = u.id
    $whereSql
    ORDER BY ul.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll();

// ============================================
// UKUPAN BROJ LOGOVA ZA PAGINACIJU
// ============================================
$countSql = "SELECT COUNT(*) FROM user_logs ul LEFT JOIN users u ON ul.user_id = u.id $whereSql";
$countStmt = $db->prepare($countSql);
$countStmt->execute(array_slice($params, 0, -2));
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// ============================================
// STATISTIKE
// ============================================
$statsStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM user_logs WHERE DATE(created_at) = CURDATE()) as today_logs,
        (SELECT COUNT(*) FROM user_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as last24h,
        (SELECT COUNT(DISTINCT user_id) FROM user_logs WHERE DATE(created_at) = CURDATE()) as active_users,
        (SELECT COUNT(DISTINCT action) FROM user_logs) as unique_actions
");
$stats = $statsStmt->fetch();

// Dohvati sve jedinstvene akcije za filter
$actionsStmt = $db->query("SELECT DISTINCT action FROM user_logs ORDER BY action");
$allActions = $actionsStmt->fetchAll();

// ============================================
// UKLJUČI HEADER I SIDEBAR
// ============================================
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- ============================================ -->
<!-- STATISTIKE -->
<!-- ============================================ -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-primary"><i class="fas fa-file-alt"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($totalLogs); ?></h3>
                <p class="stat-card-label">Ukupno logova</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-success"><i class="fas fa-user-check"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-success"><?php echo number_format($stats['active_users'] ?? 0); ?></h3>
                <p class="stat-card-label">Aktivnih korisnika danas</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-warning"><?php echo number_format($stats['last24h'] ?? 0); ?></h3>
                <p class="stat-card-label">Poslednjih 24h</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-info"><i class="fas fa-tags"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-info"><?php echo number_format($stats['unique_actions'] ?? 0); ?></h3>
                <p class="stat-card-label">Različitih akcija</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TABELA LOGOVA -->
<!-- ============================================ -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Logovi aktivnosti</h5>
        <div class="card-tools">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Pretraži..." value="<?php echo htmlspecialchars($search); ?>" style="width:160px;">
                
                <select name="action" class="form-select form-select-sm" style="width:auto">
                    <option value="all" <?php echo $actionFilter === 'all' ? 'selected' : ''; ?>>Sve akcije</option>
                    <?php foreach ($allActions as $act): ?>
                    <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $actionFilter === $act['action'] ? 'selected' : ''; ?>>
                        <?php echo $actionLabels[$act['action']] ?? $act['action']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date_from" class="form-control form-control-sm" 
                       value="<?php echo htmlspecialchars($dateFrom); ?>" style="width:130px;">
                <input type="date" name="date_to" class="form-control form-control-sm" 
                       value="<?php echo htmlspecialchars($dateTo); ?>" style="width:130px;">
                
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <?php if ($search || $actionFilter !== 'all' || $dateFrom || $dateTo): ?>
                <a href="?page=admin/logs" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:150px">Vreme</th>
                        <th>Korisnik</th>
                        <th>Akcija</th>
                        <th>Detalji</th>
                        <th>IP adresa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">Nema logova koji odgovaraju kriterijumima pretrage.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log):
                        $actionColor = $actionColors[$log['action']] ?? 'secondary';
                        $actionLabel = $actionLabels[$log['action']] ?? $log['action'];
                        $username = $log['username'] ?? 'Sistem';
                        
                        // Detalji
                        $details = '';
                        if (!empty($log['details'])) {
                            $detailsData = json_decode($log['details'], true);
                            if ($detailsData) {
                                $detailParts = [];
                                foreach ($detailsData as $key => $value) {
                                    if (is_string($value) && strlen($value) > 50) {
                                        $value = substr($value, 0, 50) . '...';
                                    }
                                    $detailParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . (is_string($value) ? $value : json_encode($value));
                                }
                                $details = implode(', ', $detailParts);
                            }
                        }
                        
                        if (empty($details)) {
                            $details = '—';
                        }
                    ?>
                    <tr>
                        <td>
                            <small class="text-muted">
                                <?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                <?php echo htmlspecialchars($username); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $actionColor; ?>">
                                <?php echo htmlspecialchars($actionLabel); ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted" title="<?php echo htmlspecialchars($details); ?>">
                                <?php echo htmlspecialchars(substr($details, 0, 80)) . (strlen($details) > 80 ? '...' : ''); ?>
                            </small>
                        </td>
                        <td>
                            <code class="small"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- PAGINACIJA -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/logs&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=admin/logs&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/logs&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $actionFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- GRAFIKON -->
<!-- ============================================ -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title">Aktivnosti u poslednjih 7 dana</h5>
    </div>
    <div class="card-body">
        <?php
        $activityData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d.m.', strtotime("-$i days"));
            
            $stmt = $db->prepare("SELECT COUNT(*) FROM user_logs WHERE DATE(created_at) = ?");
            $stmt->execute([$date]);
            $count = $stmt->fetchColumn();
            
            $activityData['labels'][] = $label;
            $activityData['counts'][] = (int)$count;
        }
        ?>
        <canvas id="activityChart" height="200"></canvas>
    </div>
</div>

<style>
.stat-card .stat-card-value.text-warning { color: #f59e0b !important; }
.stat-card .stat-card-value.text-success { color: #10b981 !important; }
.stat-card .stat-card-value.text-info { color: #3b82f6 !important; }

.stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
}
.stat-card-icon.bg-primary { background: #4f46e5; }
.stat-card-icon.bg-success { background: #10b981; }
.stat-card-icon.bg-warning { background: #f59e0b; }
.stat-card-icon.bg-info { background: #3b82f6; }
.stat-card-info { flex: 1; min-width: 0; }
.stat-card-value { font-size: 1.5rem; font-weight: 700; margin: 0; line-height: 1.2; }
.stat-card-label { color: #6b7280; font-size: 0.75rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }

.table-responsive { overflow-x: auto; }
.table th, .table td { vertical-align: middle; }
code { font-size: 0.8rem; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    const chartData = <?php echo json_encode($activityData); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Broj aktivnosti',
                data: chartData.counts,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: '#3b82f6',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>