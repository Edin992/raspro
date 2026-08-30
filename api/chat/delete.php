<?php
/**
 * api/chat/delete.php - Brisanje konverzacije
 */
session_start();


require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : '*'));
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
    echo json_encode([
        'success' => false, 
        'message' => 'Morate biti prijavljeni'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// Uzmi input (JSON ili POST)
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

$conversationId = $input['conversation_id'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID konverzacije nije prosleđen']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // Proveri da li korisnik učestvuje u konverzaciji
    $stmt = $db->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nemate pristup ovoj konverzaciji']);
        exit;
    }
    
    // Opcija 1: Arhiviraj konverzaciju (soft delete)
    $stmt = $db->prepare("
        UPDATE conversations 
        SET is_archived = TRUE, 
            archived_at = NOW(),
            archived_by = ?
        WHERE id = ?
    ");
    $stmt->execute([$userId, $conversationId]);
    
    // Ili Opcija 2: Potpuno brisanje (otkomentariši ako želiš)
    /*
    // Prvo obriši sve poruke u konverzaciji
    $stmt = $db->prepare("DELETE FROM messages WHERE conversation_id = ?");
    $stmt->execute([$conversationId]);
    
    // Onda obriši konverzaciju
    $stmt = $db->prepare("DELETE FROM conversations WHERE id = ?");
    $stmt->execute([$conversationId]);
    */
    
    echo json_encode([
        'success' => true,
        'message' => 'Konverzacija je uspešno arhivirana'
    ]);
    
} catch (Exception $e) {
    error_log("Delete conversation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri brisanju konverzacije: ' . $e->getMessage()
    ]);
}