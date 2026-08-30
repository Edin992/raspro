<?php
/**
 * api/chat/conversation.php - API za poruke jedne konverzacije
 */
session_start();

require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : '*'));
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
$conversationId = $_GET['id'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID konverzacije nije prosleđen']);
    exit;
}

try {
    $db = getDatabaseConnection();
    
    // Proveri da li korisnik ima pristup ovoj konverzaciji
    $stmt = $db->prepare("
        SELECT id, user1_id, user2_id, ad_id 
        FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Konverzacija nije pronađena']);
        exit;
    }
    
    // Odredi drugog učesnika
    $otherUserId = ($conversation['user1_id'] == $userId) 
        ? $conversation['user2_id'] 
        : $conversation['user1_id'];
    
    // Dohvati sve poruke u konverzaciji
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
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatiraj poruke
    $formattedMessages = [];
    foreach ($messages as $msg) {
        $formattedMessages[] = [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'receiver_id' => (int)$msg['receiver_id'],
            'message' => htmlspecialchars_decode($msg['message'] ?? ''),
            'is_read' => (bool)$msg['is_read'],
            'read_at' => $msg['read_at'],
            'created_at' => $msg['created_at'],
            'time_ago' => timeAgo($msg['created_at']),
            'sender_username' => $msg['sender_username'] ?? '',
            'sender_avatar' => $msg['sender_avatar'] ?? '/assets/images/defaults/avatar.png',
            'sender_verified' => (bool)$msg['sender_verified']
        ];
    }
    
    // Dohvati podatke o drugom učesniku
    $stmt = $db->prepare("
        SELECT id, username, first_name, last_name, avatar, is_verified
        FROM users WHERE id = ?
    ");
    $stmt->execute([$otherUserId]);
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otherUser) {
        $otherUser = [
            'id' => 0,
            'username' => 'Nepoznati korisnik',
            'first_name' => '',
            'last_name' => '',
            'avatar' => '/assets/images/defaults/avatar.png',
            'is_verified' => 0
        ];
    }
    
    $otherUserName = !empty($otherUser['first_name']) && !empty($otherUser['last_name'])
        ? $otherUser['first_name'] . ' ' . $otherUser['last_name']
        : $otherUser['username'];
    
    // Dohvati podatke o oglasu ako postoji
    $adInfo = null;
    if ($conversation['ad_id']) {
        $stmt = $db->prepare("
            SELECT id, title, price, slug 
            FROM ads 
            WHERE id = ?
        ");
        $stmt->execute([$conversation['ad_id']]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ad) {
            $adInfo = [
                'id' => (int)$ad['id'],
                'title' => htmlspecialchars_decode($ad['title'] ?? ''),
                'price' => (float)$ad['price'],
                'slug' => $ad['slug'] ?? '',
                'url' => '/?page=ad-detail&id=' . $ad['id']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'conversation_id' => (int)$conversationId,
        'other_user' => [
            'id' => (int)$otherUser['id'],
            'username' => $otherUser['username'],
            'name' => $otherUserName,
            'avatar' => $otherUser['avatar'] ?? '/assets/images/defaults/avatar.png',
            'is_verified' => (bool)$otherUser['is_verified']
        ],
        'ad' => $adInfo,
        'messages' => $formattedMessages
    ]);
    
} catch (Exception $e) {
    error_log("Get conversation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju poruka: ' . $e->getMessage()
    ]);
}