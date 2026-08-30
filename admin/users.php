<?php
/**
 * admin/users.php - Upravljanje korisnicima
 */

require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pageTitle = 'Upravljanje korisnicima';
$db = getDatabaseConnection();
$message = '';
$messageType = '';

// Brisanje korisnika
if (isset($_POST['delete_user']) && $_SESSION['admin_role'] === 'superadmin') {
    $userId = (int)$_POST['user_id'];
    if ($userId != $_SESSION['admin_id']) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $message = 'Korisnik je uspešno obrisan.';
        $messageType = 'success';
    }
}

// Toggle admin status
if (isset($_POST['toggle_admin']) && $_SESSION['admin_role'] === 'superadmin') {
    $userId = (int)$_POST['user_id'];
    $isAdmin = (int)$_POST['is_admin'];
    
    if ($isAdmin) {
        $stmt = $db->prepare("INSERT INTO admins (user_id, role) VALUES (?, 'admin')");
        $stmt->execute([$userId]);
        $message = 'Korisnik je postavljen za administratora.';
    } else {
        $stmt = $db->prepare("DELETE FROM admins WHERE user_id = ?");
        $stmt->execute([$userId]);
        $message = 'Administratorske privilegije su uklonjene.';
    }
    $messageType = 'success';
}

// Verifikacija korisnika
if (isset($_POST['verify_user'])) {
    $userId = (int)$_POST['user_id'];
    $stmt = $db->prepare("UPDATE users SET is_verified = 1, verified_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    $message = 'Korisnik je verifikovan.';
    $messageType = 'success';
}

// Paginacija
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = '';
$params = [];

if ($search) {
    $where = "WHERE (u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $sp = "%$search%";
    $params = [$sp, $sp, $sp, $sp];
}

$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM ads WHERE user_id = u.id) as ads_count,
           (SELECT COUNT(*) FROM admins WHERE user_id = u.id) as is_admin
    FROM users u
    $where
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$users = $stmt->fetchAll();

$countStmt = $db->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute(array_slice($params, 0, count($params) - 2));
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title">Korisnici</h5>
        <div class="card-tools">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Pretraži..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                <?php if ($search): ?>
                <a href="?page=admin/users" class="btn btn-sm btn-secondary"><i class="fas fa-times"></i></a>
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
                        <th>Korisnik</th>
                        <th>Email</th>
                        <th>Lokacija</th>
                        <th>Oglasi</th>
                        <th>Datum</th>
                        <th>Status</th>
                        <th style="width:150px">Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $user['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : '/assets/images/defaults/avatar.svg'; ?>" 
                                     class="rounded-circle me-2" width="40" height="40">
                                <div>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($user['username']); ?></div>
                                    <?php if ($user['first_name']): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['city'] ?: '-'); ?></td>
                        <td><span class="badge bg-primary"><?php echo $user['ads_count']; ?></span></td>
                        <td><small><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <?php if ($user['is_verified']): ?>
                                <span class="badge bg-success">Verifikovan</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Neverifikovan</span>
                                <?php endif; ?>
                                <?php if ($user['is_admin']): ?>
                                <span class="badge bg-warning text-dark">Admin</span>
                                <?php endif; ?>
                                <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                <span class="badge bg-info">Vi</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/profile/<?php echo $user['id']; ?>" class="btn btn-outline-primary" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if (! $user['is_verified']): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="verify_user" class="btn btn-outline-success" title="Verifikuj">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['admin_role'] === 'superadmin' && $user['id'] != $_SESSION['admin_id']): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_admin" value="<?php echo $user['is_admin'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_admin" class="btn btn-outline-<?php echo $user['is_admin'] ? 'danger' : 'success'; ?>">
                                        <i class="fas fa-<?php echo $user['is_admin'] ? 'user-minus' : 'user-plus'; ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if ($_SESSION['admin_role'] === 'superadmin' && $user['id'] != $_SESSION['admin_id'] && !$user['is_admin']): ?>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Da li ste sigurni?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
                    <a class="page-link" href="?page=admin/users&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=admin/users&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=admin/users&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>