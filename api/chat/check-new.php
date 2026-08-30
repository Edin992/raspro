<?php
/**
 * api/chat/check-new.php - API za proveru novih poruka
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
$conversationId = $_GET['conversation_id'] ?? null;
$lastMessageId = $_GET['last_message_id'] ?? 0;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID konverzacije nije prosleđen']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // Proveri da li korisnik ima pristup konverzaciji
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
    
    // Dohvati nove poruke (posle lastMessageId)
    $stmt = $db->prepare("
        SELECT 
            m.id,
            m.sender_id,
            m.receiver_id,
            m.message,
            m.is_read,
            m.read_at,
            m.created_at,
            u.username as sender_username,
            u.avatar as sender_avatar,
            u.is_verified as sender_verified
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ? AND m.id > ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId, $lastMessageId]);
    $newMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatiraj nove poruke
    $formattedMessages = [];
    foreach ($newMessages as $msg) {
        $formattedMessages[] = [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'receiver_id' => (int)$msg['receiver_id'],
            'message' => htmlspecialchars_decode($msg['message']),
            'is_read' => (bool)$msg['is_read'],
            'created_at' => $msg['created_at'],
            'time_ago' => timeAgo($msg['created_at']),
            'sender_username' => $msg['sender_username'],
            'sender_avatar' => $msg['sender_avatar'] ?? '/assets/images/defaults/avatar.png',
            'sender_verified' => (bool)$msg['sender_verified']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'new_messages' => $formattedMessages,
        'count' => count($formattedMessages)
    ]);
    
} catch (Exception $e) {
    error_log("Check new messages error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri proveri novih poruka'
    ]);
}