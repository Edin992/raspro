<?php
/**
 * api/chat/unread-count.php - API za broj nepročitanih poruka
 */
session_start();

require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');


require_once __DIR__ . '/../../includes/functions.php';


// Samo GET zahtevi
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nije dozvoljena']);
    exit;
}

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Morate biti prijavljeni'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

try {
    $db = getDatabaseConnection();
    
    // Broj nepročitanih poruka
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUnread = $result['count'] ?? 0;
    
    // Broj konverzacija sa nepročitanim porukama
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT conversation_id) as count
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $conversationsWithUnread = $result['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'count' => (int)$totalUnread,
        'conversations_with_unread' => (int)$conversationsWithUnread
    ]);
    
} catch (Exception $e) {
    error_log("Get unread count error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju broja poruka'
    ]);
}