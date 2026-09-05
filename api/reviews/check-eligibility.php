<?php
/**
 * api/reviews/check-eligibility.php - Da li smem oceniti drugog korisnika?
 *
 * GET: ?conversation_id=X
 * Vraca: { can_review, already_reviewed, other_user, reason? }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'can_review' => false, 'reason' => 'not_logged_in']);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$conversationId = (int) ($_GET['conversation_id'] ?? 0);

$out = [
    'success' => true,
    'can_review' => false,
    'already_reviewed' => false,
    'other_user' => null,
];

if ($conversationId <= 0) {
    $out['reason'] = 'no_conversation';
    echo json_encode($out);
    exit();
}

try {
    $db = getDatabaseConnection();

    $stmt = $db->prepare("
        SELECT id, user1_id, user2_id FROM conversations
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    $conv = $stmt->fetch();

    if (!$conv) {
        $out['reason'] = 'not_participant';
        echo json_encode($out);
        exit();
    }

    $otherId = ($conv['user1_id'] == $userId) ? (int) $conv['user2_id'] : (int) $conv['user1_id'];
    $out['other_user'] = ['id' => $otherId];

    // Vec ocenjeno?
    $stmt = $db->prepare("
        SELECT id FROM user_reviews
        WHERE reviewer_id = ? AND user_id = ? AND conversation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $otherId, $conversationId]);
    if ($stmt->fetch()) {
        $out['already_reviewed'] = true;
        $out['reason'] = 'already_reviewed';
        echo json_encode($out);
        exit();
    }

    // Obostrana komunikacija
    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN sender_id = ? THEN 1 ELSE 0 END) as my_msgs,
            SUM(CASE WHEN sender_id = ? THEN 1 ELSE 0 END) as their_msgs
        FROM messages WHERE conversation_id = ?
    ");
    $stmt->execute([$userId, $otherId, $conversationId]);
    $c = $stmt->fetch();
    if ((int) ($c['my_msgs'] ?? 0) < 1 || (int) ($c['their_msgs'] ?? 0) < 1) {
        $out['reason'] = 'no_exchange';
        echo json_encode($out);
        exit();
    }

    $out['can_review'] = true;
    echo json_encode($out);

} catch (Throwable $e) {
    error_log('review eligibility: ' . $e->getMessage());
    $out['reason'] = 'error';
    echo json_encode($out);
}
