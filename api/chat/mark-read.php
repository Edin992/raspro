<?php
/**
 * api/chat/mark-read.php - POJEDNOSTAVLJENA VERZIJA
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');


require_once __DIR__ . '/../../includes/functions.php';

// Samo POST zahtevi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoda nije dozvoljena']);
    exit;
}

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Morate biti prijavljeni']);
    exit;
}

// Uzmi input (JSON)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Proveri CSRF token
if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token nije validan']);
    exit;
}

$userId = $_SESSION['user_id'];
$conversationId = $input['conversation_id'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID konverzacije nije prosleđen']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // DIREKTNO ažuriranje - bez JOIN, jednostavnije
    $stmt = $db->prepare("
        UPDATE messages 
        SET is_read = 1, read_at = NOW()
        WHERE conversation_id = ? 
        AND receiver_id = ? 
        AND is_read = 0
    ");
    
    $stmt->execute([$conversationId, $userId]);
    $updatedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Poruke su označene kao pročitane',
        'updated_count' => $updatedCount
    ]);
    
} catch (Exception $e) {
    error_log("Mark messages as read error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri označavanju poruka'
    ]);
}