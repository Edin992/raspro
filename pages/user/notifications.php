<?php
/**
 * pages/user/notifications.php - Historija obaveštenja
 *
 * Server-side render (radi i bez JS-a) + akcije preko forme sa CSRF:
 *  - pojedinačno označi kao pročitano / obriši
 *  - "Označi sva kao pročitana" / "Ukloni pročitana"
 */

if (!isLoggedIn()) {
    redirect('/login');
}

$userId = (int) $_SESSION['user_id'];
$pageTitle = 'Obaveštenja - Rasprodaja.rs';
$pageDescription = 'Vaša obaveštenja na Rasprodaja.rs';
$db = getDatabaseConnection();

// ============================================
// AKCIJE (POST)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCSRFToken($_POST)) {
        $_SESSION['error_message'] = 'Sesija je istekla. Osvežite stranicu.';
        redirect('/notifications');
        exit;
    }
    $action = $_POST['action'] ?? '';
    $nid = (int) ($_POST['id'] ?? 0);

    if ($action === 'read' && $nid > 0) {
        $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?")
           ->execute([$nid, $userId]);
    } elseif ($action === 'delete' && $nid > 0) {
        $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")
           ->execute([$nid, $userId]);
    } elseif ($action === 'read_all') {
        $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0")
           ->execute([$userId]);
    } elseif ($action === 'clear_read') {
        $db->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1")
           ->execute([$userId]);
    }
    redirect('/notifications');
    exit;
}

// ============================================
// LISTA (paginacija 25 po strani)
// ============================================
$pageNum = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset = ($pageNum - 1) * $perPage;

// FIX: bez SQL_CALC_FOUND_ROWS (deprekirano u MySQL 8) - obican COUNT je dovoljan
$stmt = $db->prepare("
    SELECT id, type, title, message, data, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT " . (int) $perPage . " OFFSET " . (int) $offset . "
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
$stmt->execute([$userId]);
$total = (int) $stmt->fetchColumn();
$pages = (int) ceil($total / $perPage);

$iconMap = [
    'message' => ['envelope', 'text-primary'],
    'follow' => ['user-plus', 'text-success'],
    'review' => ['star', 'text-warning'],
    'ad_view' => ['eye', 'text-info'],
    'ad_sold' => ['handshake', 'text-success'],
    'package' => ['crown', 'text-warning'],
    'system' => ['bell', 'text-secondary'],
];

// link po tipu (ista logika kao api/notifications/list.php)
$notifLink = function ($n) {
    $data = !empty($n['data']) ? (is_array($n['data']) ? $n['data'] : json_decode($n['data'], true)) : [];
    switch ($n['type']) {
        case 'message':
            return !empty($data['conversation_id']) ? '/messages/?conversation=' . (int) $data['conversation_id'] : '/messages/';
        case 'follow':
            return !empty($data['follower_id']) ? '/profile/' . (int) $data['follower_id'] : null;
        case 'review':
            return !empty($data['reviewer_id']) ? '/profile/' . (int) $data['reviewer_id'] : null;
        case 'ad_view':
        case 'ad_sold':
            return !empty($data['ad_id']) ? '/?page=ad-detail&id=' . (int) $data['ad_id'] : null;
        case 'package':
            return '/packages/';
        case 'system':
            return $data['link'] ?? null;
    }
    return null;
};
?>

<div class="container py-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="far fa-bell me-2 text-primary"></i> Obaveštenja</h1>
        <div class="d-flex gap-2">
            <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="read_all">
                <button class="btn btn-sm btn-outline-primary" type="submit">
                    <i class="fas fa-check-double me-1"></i> Pročitaj sva
                </button>
            </form>
            <form method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="clear_read">
                <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="fas fa-broom me-1"></i> Ukloni pročitana
                </button>
            </form>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-5">
            <i class="far fa-bell fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">Nema obaveštenja</h5>
            <p class="text-muted small">Obaveštenja o porukama, ocenama i praćenjima pojaviće se ovde.</p>
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($notifications as $n):
                [$ico, $icoClass] = $iconMap[$n['type']] ?? $iconMap['system'];
                $link = $notifLink($n);
                $data = !empty($n['data']) ? (is_array($n['data']) ? $n['data'] : json_decode($n['data'], true)) : [];
            ?>
            <div class="list-group-item d-flex align-items-start gap-3 <?php echo $n['is_read'] ? '' : 'bg-light'; ?>">
                <div class="mt-1"><i class="fas <?php echo $ico; ?> <?php echo $icoClass; ?> fa-lg"></i></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1 <?php echo $n['is_read'] ? '' : 'fw-bold'; ?>">
                            <?php echo htmlspecialchars($n['title']); ?>
                        </h6>
                        <small class="text-muted text-nowrap ms-2"><?php echo timeAgo($n['created_at']); ?></small>
                    </div>
                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars($n['message']); ?></p>
                    <div class="d-flex gap-3 mt-2">
                        <?php if ($link): ?>
                            <a href="<?php echo htmlspecialchars($link); ?>" class="small text-decoration-none">
                                <i class="fas fa-arrow-right me-1"></i>Otvori
                            </a>
                        <?php endif; ?>
                        <?php if (!$n['is_read']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="read">
                            <input type="hidden" name="id" value="<?php echo (int) $n['id']; ?>">
                            <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none small">
                                <i class="fas fa-check me-1"></i>Pročitano
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $n['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Obriši">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php echo $i === $pageNum ? 'active' : ''; ?>">
                        <a class="page-link" href="/notifications/?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
