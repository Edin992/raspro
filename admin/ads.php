<?php
/**
 * admin/ads.php - Upravljanje oglasima
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Upravljanje oglasima';
$db = getDatabaseConnection();
$message = '';
$messageType = '';

// Brisanje oglasa
if (isset($_POST['delete_ad']) && adminHasPermission('delete_ads')) {
    $adId = (int)$_POST['ad_id'];
    $stmt = $db->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->execute([$adId]);
    $message = 'Oglas je uspešno obrisan.';
    $messageType = 'success';
}

// Promena statusa
if (isset($_POST['toggle_status'])) {
    $adId = (int)$_POST['ad_id'];
    $status = $_POST['status'];
    $stmt = $db->prepare("UPDATE ads SET status = ? WHERE id = ?");
    $stmt->execute([$status, $adId]);
    $message = 'Status oglasa je promenjen.';
    $messageType = 'success';
}

// Premium toggle
if (isset($_POST['toggle_premium'])) {
    $adId = (int)$_POST['ad_id'];
    $isPremium = (int)$_POST['is_premium'];
    $premiumUntil = $isPremium ? date('Y-m-d H:i:s', strtotime('+30 days')) : null;
    $stmt = $db->prepare("UPDATE ads SET is_premium = ?, premium_until = ? WHERE id = ?");
    $stmt->execute([$isPremium, $premiumUntil, $adId]);
    $message = $isPremium ? 'Oglas je postavljen kao premium.' : 'Premium status je uklonjen.';
    $messageType = 'success';
}

// Paginacija i filteri
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = [];
$params = [];

if ($search) {
    $where[] = "(a.title LIKE ? OR a.description LIKE ? OR a.city LIKE ? OR u.username LIKE ?)";
    $sp = "%$search%";
    $params = array_merge($params, [$sp, $sp, $sp, $sp]);
}
if ($filter === 'active') $where[] = "a.status = 'active'";
elseif ($filter === 'sold') $where[] = "a.status = 'sold'";
elseif ($filter === 'expired') $where[] = "a.status = 'expired'";
elseif ($filter === 'premium') $where[] = "a.is_premium = 1";

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT a.*, u.username, u.id as user_id, c.name as category_name,
           (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as thumb
    FROM ads a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN categories c ON a.category_id = c.id
    $whereSql
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$ads = $stmt->fetchAll();

$countStmt = $db->prepare("SELECT COUNT(*) FROM ads a LEFT JOIN users u ON a.user_id = u.id $whereSql");
$countStmt->execute(array_slice($params, 0, -2));
$totalAds = $countStmt->fetchColumn();
$totalPages = ceil($totalAds / $limit);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Oglasi</h5>
        <div class="card-tools">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Pretraži..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="filter" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Svi oglasi</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Aktivni</option>
                    <option value="sold" <?php echo $filter === 'sold' ? 'selected' : ''; ?>>Prodati</option>
                    <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Istekli</option>
                    <option value="premium" <?php echo $filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <?php if ($search || $filter !== 'all'): ?>
                <a href="?page=admin/ads" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
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
                        <th style="width:60px">ID</th>
                        <th>Oglas</th>
                        <th>Korisnik</th>
                        <th>Kategorija</th>
                        <th>Cena</th>
                        <th>Lokacija</th>
                        <th>Pregledi</th>
                        <th>Status</th>
                        <th style="width:150px">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ads as $ad): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $ad['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($ad['thumb']): ?>
                                <img src="<?php echo htmlspecialchars($ad['thumb']); ?>" class="rounded me-2" width="50" height="50" style="object-fit:cover">
                                <?php else: ?>
                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width:50px;height:50px">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold">
                                        <a href="/ad/<?php echo $ad['id']; ?>" target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($ad['title']); ?>
                                        </a>
                                    </div>
                                    <small class="text-muted"><?php echo date('d.m.Y', strtotime($ad['created_at'])); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="?page=admin/users&search=<?php echo urlencode($ad['username']); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($ad['username']); ?>
                            </a>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($ad['category_name'] ?? 'Bez kategorije'); ?></span></td>
                        <td class="fw-bold text-primary"><?php echo number_format($ad['price'], 0, ',', '.'); ?> RSD</td>
                        <td><i class="fas fa-map-marker-alt text-muted me-1"></i><?php echo htmlspecialchars($ad['city']); ?></td>
                        <td><i class="fas fa-eye text-muted me-1"></i><?php echo number_format($ad['views']); ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if ($ad['status'] === 'active'): ?>
                                <span class="badge bg-success">Aktivan</span>
                                <?php elseif ($ad['status'] === 'sold'): ?>
                                <span class="badge bg-info">Prodat</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Istekao</span>
                                <?php endif; ?>
                                <?php if ($ad['is_premium']): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> Premium</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/ad/<?php echo $ad['id']; ?>" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                    <select name="status" class="form-select form-select-sm" style="width:auto;display:inline-block" onchange="this.form.submit()">
                                        <option value="active" <?php echo $ad['status'] === 'active' ? 'selected' : ''; ?>>Aktivan</option>
                                        <option value="sold" <?php echo $ad['status'] === 'sold' ? 'selected' : ''; ?>>Prodat</option>
                                        <option value="expired" <?php echo $ad['status'] === 'expired' ? 'selected' : ''; ?>>Istekao</option>
                                    </select>
                                    <input type="hidden" name="toggle_status" value="1">
                                </form>
                                
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                    <input type="hidden" name="is_premium" value="<?php echo $ad['is_premium'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_premium" class="btn btn-outline-<?php echo $ad['is_premium'] ? 'danger' : 'success'; ?>">
                                        <i class="fas fa-crown"></i>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display:inline" onsubmit="return confirm('Da li ste sigurni?')">
                                    <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                    <button type="submit" name="delete_ad" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                         </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/ads&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=admin/ads&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/ads&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>