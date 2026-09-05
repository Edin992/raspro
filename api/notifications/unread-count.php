<?php
/**
 * api/notifications/unread-count.php - Broj neprocitanih obavestenja (za zvonce)
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'unread_count' => 0, 'logged_in' => false]);
    exit();
}

try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $count = (int) $stmt->fetchColumn();

    // Poslednje neprocitano (za toast najavu)
    $stmt = $db->prepare("
        SELECT id, type, title, message
        FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $latest = $stmt->fetch() ?: null;

    echo json_encode([
        'success' => true,
        'unread_count' => $count,
        'logged_in' => true,
        'latest' => $latest,
    ]);
} catch (Throwable $e) {
    error_log('notifications/unread-count: ' . $e->getMessage());
    echo json_encode(['success' => false, 'unread_count' => 0]);
}
