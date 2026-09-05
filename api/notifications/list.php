<?php
/**
 * api/notifications/list.php - Lista obaveštenja prijavljenog korisnika
 *
 * GET: ?limit=20&offset=0&type=message|follow|package|system|ad_view|ad_sold
 * vracaju se samo SOPSTVENA obaveštenja (user_id = sesija).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$limit  = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));
$type   = isset($_GET['type']) && preg_match('/^[a-z_]{3,20}$/', $_GET['type']) ? $_GET['type'] : null;

try {
    $db = getDatabaseConnection();

    $where = 'user_id = ?';
    $params = [$userId];
    if ($type) {
        $where .= ' AND type = ?';
        $params[] = $type;
    }

    $stmt = $db->prepare("
        SELECT id, type, title, message, data, is_read, created_at
        FROM notifications
        WHERE $where
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Ukupno + nepročitano
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = (int) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unread = (int) $stmt->fetchColumn();

    // Mapiranje tipa -> link + ikonica (link je RELATIVAN - bez domena)
    $items = [];
    foreach ($rows as $row) {
        $data = null;
        if (!empty($row['data'])) {
            $data = is_array($row['data']) ? $row['data'] : json_decode($row['data'], true);
        }

        $link = null;
        $icon = 'info-circle';
        switch ($row['type']) {
            case 'message':
                $icon = 'envelope';
                $link = !empty($data['conversation_id'])
                    ? '/messages/?conversation=' . (int) $data['conversation_id']
                    : '/messages/';
                break;
            case 'follow':
                $icon = 'user-plus';
                $link = !empty($data['follower_id'])
                    ? '/profile/' . (int) $data['follower_id']
                    : null;
                break;
            case 'review':
                $icon = 'star';
                $link = !empty($data['reviewer_id'])
                    ? '/profile/' . (int) $data['reviewer_id']
                    : null;
                break;
            case 'ad_view':
            case 'ad_sold':
                $icon = $row['type'] === 'ad_sold' ? 'handshake' : 'eye';
                $link = !empty($data['ad_id'])
                    ? '/?page=ad-detail&id=' . (int) $data['ad_id']
                    : null;
                break;
            case 'package':
                $icon = 'crown';
                $link = '/packages/';
                break;
            case 'system':
            default:
                $icon = 'bell';
                if (!empty($data['link'])) {
                    $link = $data['link'];
                }
                break;
        }

        $items[] = [
            'id'       => (int) $row['id'],
            'type'     => $row['type'],
            'icon'     => $icon,
            'title'    => $row['title'],
            'message'  => $row['message'],
            'link'     => $link,
            'is_read'  => (bool) $row['is_read'],
            'time_ago' => timeAgo($row['created_at']),
            'created'  => $row['created_at'],
        ];
    }

    echo json_encode([
        'success' => true,
        'notifications' => $items,
        'total' => $total,
        'unread_count' => $unread,
    ]);

} catch (Throwable $e) {
    error_log('notifications/list: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Greška pri učitavanju obaveštenja']);
}
