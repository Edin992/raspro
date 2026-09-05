<?php
/**
 * api/notifications/mark-read.php - Oznaciti obavestenja procitanim
 *
 * POST JSON: { "ids": [1,2,3] }  ILI  { "all": true }
 * Uvek radi SAMO nad sopstvenim obavestenjima (user_id = sesija).
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Samo POST']);
    exit();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = (int) $_SESSION['user_id'];

try {
    $db = getDatabaseConnection();

    if (!empty($input['all'])) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $affected = $stmt->rowCount();
    } else {
        $ids = array_values(array_filter(array_map('intval', (array)($input['ids'] ?? []))));
        if (empty($ids)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nema ID-ja']);
            exit();
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND id IN ($placeholders)");
        $stmt->execute(array_merge([$userId], $ids));
        $affected = $stmt->rowCount();
    }

    echo json_encode(['success' => true, 'updated' => $affected]);
} catch (Throwable $e) {
    error_log('notifications/mark-read: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Greška']);
}
