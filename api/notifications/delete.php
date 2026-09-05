<?php
/**
 * api/notifications/delete.php - Brisanje obavestenja (samo sopstvenih)
 * POST JSON: { "id": 5 }  ILI { "all_read": true }
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

    if (!empty($input['all_read'])) {
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
        $stmt->execute([$userId]);
    } else {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nevažeći ID']);
            exit();
        }
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ? AND id = ? LIMIT 1");
        $stmt->execute([$userId, $id]);
    }

    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
} catch (Throwable $e) {
    error_log('notifications/delete: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Greška']);
}
