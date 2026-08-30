<?php
/**
 * admin/transactions.php - Upravljanje transakcijama
 * SA SLANJEM RAČUNA (PDF) KADA ADMIN ODOBRI
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// 1. AUTENTIKACIJA
// ============================================
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

// ============================================
// 2. BAZA I PODEŠAVANJA
// ============================================
$db = getDatabaseConnection();
$pageTitle = 'Upravljanje transakcijama';
$message = '';
$messageType = '';

// ============================================
// 3. AKCIJE
// ============================================
if (isset($_POST['update_status']) && isset($_POST['transaction_id']) && isset($_POST['status'])) {
    $transactionId = (int)$_POST['transaction_id'];
    $newStatus = $_POST['status'];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT t.*, u.id as user_id FROM transactions t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("Transakcija nije pronađena");
        }
        
        $stmt = $db->prepare("UPDATE transactions SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $transactionId]);
        
        if ($newStatus === 'completed') {
            $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$transaction['package_id']]);
            $package = $stmt->fetch();
            
            if ($package) {
                $expiryMonths = ($transaction['period'] === 'yearly') ? 12 : 1;
                $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMonths months"));
                
                $stmt = $db->prepare("UPDATE users SET package = ?, package_expires_at = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([strtolower($package['name']), $expiresAt, $transaction['user_id']]);
                
                // ============================================
                // 🔥 GENERIŠI I POŠALJI RAČUN (PDF)
                // ============================================
                if (function_exists('sendRacunEmail')) {
                    // Dohvati podatke o korisniku
                    $user = getUserById($transaction['user_id']);
                    
                    // Dohvati reference number iz payment_details
                    $referenceNumber = '';
                    if (!empty($transaction['payment_details'])) {
                        $details = json_decode($transaction['payment_details'], true);
                        $referenceNumber = $details['reference_number'] ?? $details['reference'] ?? '';
                    }
                    
                    if ($user && $package) {
                        // Pripremi podatke za transakciju
                        $transactionData = [
                            'id' => $transaction['id'],
                            'amount' => $transaction['amount'],
                            'period' => $transaction['period'],
                            'created_at' => $transaction['created_at'],
                            'updated_at' => date('Y-m-d H:i:s'),
                            'expires_at' => $expiresAt
                        ];
                        
                        // Pošalji račun
                        $racunSent = sendRacunEmail(
                            $user,
                            $package,
                            $transactionData,
                            $referenceNumber
                        );
                        
                        if ($racunSent) {
                            error_log("✅ Račun poslat korisniku: " . $transaction['user_id']);
                        } else {
                            error_log("❌ Greška pri slanju računa za: " . $transaction['user_id']);
                        }
                    }
                } else {
                    error_log("⚠️ Funkcija sendRacunEmail ne postoji!");
                }
                // ============================================
                // KRAJ - SLANJE RAČUNA
                // ============================================
            }
        }
        
        if (in_array($newStatus, ['failed', 'refunded'])) {
            $stmt = $db->prepare("UPDATE users SET package = 'free', package_expires_at = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transaction['user_id']]);
        }
        
        $db->commit();
        $message = 'Status transakcije je uspešno ažuriran.';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Greška: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// ============================================
// 4. FILTERI I PAGINACIJA
// ============================================
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

$where = [];
$params = [];

if ($search) {
    $where[] = "(u.username LIKE ? OR u.email LIKE ? OR t.transaction_id LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
}

if ($statusFilter !== 'all') {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Dohvati transakcije
$stmt = $db->prepare("
    SELECT 
        t.*,
        u.username,
        u.email,
        u.first_name,
        u.last_name,
        sp.name as package_name
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN subscription_plans sp ON t.package_id = sp.id
    $whereSql
    ORDER BY t.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Broj transakcija
$countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON t.user_id = u.id $whereSql");
$countStmt->execute(array_slice($params, 0, count($params) - 2));
$totalTransactions = $countStmt->fetchColumn();
$totalPages = ceil($totalTransactions / $limit);

// Statistike
$statsStmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue
    FROM transactions
");
$stats = $statsStmt->fetch();

// ============================================
// 5. UKLJUČI HEADER I SIDEBAR
// ============================================
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- ============================================ -->
<!-- SADRŽAJ - SVE JE UNUTAR admin-content -->
<!-- ============================================ -->

<!-- Statistike -->
<div class="row g-4 mb-4">
    <div class="col-xl-2 col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-primary"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($stats['total'] ?? 0); ?></h3>
                <p class="stat-card-label">Ukupno</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-warning"><i class="fas fa-clock"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-warning"><?php echo number_format($stats['pending'] ?? 0); ?></h3>
                <p class="stat-card-label">Na čekanju</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-success"><?php echo number_format($stats['completed'] ?? 0); ?></h3>
                <p class="stat-card-label">Aktiviran</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-3 col-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-danger"><i class="fas fa-times-circle"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-danger"><?php echo number_format($stats['failed'] ?? 0); ?></h3>
                <p class="stat-card-label">Neuspešno</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6 col-12">
        <div class="stat-card">
            <div class="stat-card-icon bg-info"><i class="fas fa-arrow-left"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-info"><?php echo number_format($stats['refunded'] ?? 0); ?></h3>
                <p class="stat-card-label">Refundirano</p>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-6 col-12">
        <div class="stat-card">
            <div class="stat-card-icon bg-success"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-card-info">
                <h3 class="stat-card-value text-success"><?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?> RSD</h3>
                <p class="stat-card-label">Ukupni prihodi</p>
            </div>
        </div>
    </div>
</div>

<!-- Tabela -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Transakcije</h5>
        <div class="card-tools">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Pretraži..." value="<?php echo htmlspecialchars($search); ?>" style="width:180px;">
                <select name="status" class="form-select form-select-sm" style="width:auto">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Svi statusi</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Na čekanju</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Aktiviran</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Neuspešno</option>
                    <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refundirano</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <?php if ($search || $statusFilter !== 'all'): ?>
                <a href="?page=admin/transactions" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> m-3">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">ID</th>
                        <th>Korisnik</th>
                        <th>Paket</th>
                        <th>Iznos</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Datum</th>
                        <th style="width:150px">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">Nema transakcija.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $tx): 
                        $statusColors = ['pending' => 'warning', 'completed' => 'success', 'failed' => 'danger', 'refunded' => 'secondary'];
                        $statusLabels = ['pending' => 'Na čekanju', 'completed' => 'Aktiviran', 'failed' => 'Neuspešno', 'refunded' => 'Refundirano'];
                        $statusColor = $statusColors[$tx['status']] ?? 'secondary';
                        $statusLabel = $statusLabels[$tx['status']] ?? $tx['status'];
                        $userName = trim($tx['first_name'] . ' ' . $tx['last_name']) ?: $tx['username'];
                    ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $tx['id']; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($userName); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($tx['email']); ?></small>
                        </td>
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($tx['package_name'] ?? 'Nepoznat'); ?></span></td>
                        <td class="fw-bold text-primary"><?php echo number_format($tx['amount'], 0, ',', '.'); ?> RSD</td>
                        <td><?php echo $tx['period'] === 'yearly' ? '<span class="badge bg-info">Godišnje</span>' : '<span class="badge bg-secondary">Mesečno</span>'; ?></td>
                        <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusLabel; ?></span></td>
                        <td><small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($tx['created_at'])); ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo $tx['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($tx['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Aktiviraj transakciju?')">
                                    <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" name="update_status" class="btn btn-outline-success"><i class="fas fa-check-circle"></i></button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Odbij transakciju?')">
                                    <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                    <input type="hidden" name="status" value="failed">
                                    <button type="submit" name="update_status" class="btn btn-outline-danger"><i class="fas fa-times-circle"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- MODAL -->
                    <div class="modal fade" id="modal<?php echo $tx['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Detalji transakcije #<?php echo $tx['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Korisnik</h6>
                                            <p><strong><?php echo htmlspecialchars($userName); ?></strong><br>
                                            <small><?php echo htmlspecialchars($tx['email']); ?></small></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Paket</h6>
                                            <p><strong><?php echo htmlspecialchars($tx['package_name'] ?? 'Nepoznat'); ?></strong></p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Iznos</h6>
                                            <p class="h5 text-primary"><?php echo number_format($tx['amount'], 0, ',', '.'); ?> RSD</p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Period</h6>
                                            <p><?php echo $tx['period'] === 'yearly' ? 'Godišnje' : 'Mesečno'; ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-muted">Status</h6>
                                            <span class="badge bg-<?php echo $statusColor; ?> fs-6"><?php echo $statusLabel; ?></span>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Transaction ID</h6>
                                            <code><?php echo htmlspecialchars($tx['transaction_id']); ?></code>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Kreirano</h6>
                                            <p><?php echo date('d.m.Y H:i:s', strtotime($tx['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <?php if ($tx['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" name="update_status" class="btn btn-success">Odobri</button>
                                    </form>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="transaction_id" value="<?php echo $tx['id']; ?>">
                                        <input type="hidden" name="status" value="failed">
                                        <button type="submit" name="update_status" class="btn btn-danger">Odbij</button>
                                    </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <a class="page-link" href="?page=admin/transactions&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=admin/transactions&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/transactions&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $statusFilter; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- CSS -->
<style>
.stat-card .stat-card-value.text-warning { color: #f59e0b !important; }
.stat-card .stat-card-value.text-success { color: #10b981 !important; }
.stat-card .stat-card-value.text-danger { color: #ef4444 !important; }
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
.stat-card-icon.bg-warning { background: #f59e0b; }
.stat-card-icon.bg-success { background: #10b981; }
.stat-card-icon.bg-danger { background: #ef4444; }
.stat-card-icon.bg-info { background: #3b82f6; }
.stat-card-info { flex: 1; min-width: 0; }
.stat-card-value { font-size: 1.5rem; font-weight: 700; margin: 0; line-height: 1.2; }
.stat-card-label { color: #6b7280; font-size: 0.75rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
.table-responsive { overflow-x: auto; }
.table th, .table td { vertical-align: middle; }
.btn-group-sm > .btn { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.modal-body { max-height: 70vh; overflow-y: auto; }
</style>

<!-- JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal').forEach(function(modal) {
        new bootstrap.Modal(modal);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>