<?php
/**
 * api/chat/list.php - API za listu konverzacija korisnika
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

try {
    $db = getDatabaseConnection();
    
    // Dohvati sve konverzacije za korisnika (bez JSON_OBJECT)
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.uuid,
            c.user1_id,
            c.user2_id,
            c.ad_id,
            c.last_activity,
            c.created_at,
            a.title as ad_title,
            a.price as ad_price,
            a.slug as ad_slug,
            -- Poslednja poruka (odvojeno, bez JSON)
            (SELECT m.id FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_msg_id,
            (SELECT m.message FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_msg_text,
            (SELECT m.sender_id FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_msg_sender,
            (SELECT m.created_at FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_msg_created,
            (SELECT m.is_read FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_msg_is_read,
            -- Broj nepročitanih poruka
            (SELECT COUNT(*) FROM messages m 
             WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0) as unread_count
        FROM conversations c
        LEFT JOIN ads a ON c.ad_id = a.id
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY COALESCE(c.last_activity, c.created_at) DESC
    ");
    
    $stmt->execute([$userId, $userId, $userId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obradi svaku konverzaciju
    $result = [];
    foreach ($conversations as $conv) {
        // Odredi drugog učesnika
        $otherUserId = ($conv['user1_id'] == $userId) ? $conv['user2_id'] : $conv['user1_id'];
        
        // Dohvati podatke o drugom učesniku
        $stmt2 = $db->prepare("
            SELECT 
                id,
                username,
                first_name,
                last_name,
                avatar,
                is_verified
            FROM users 
            WHERE id = ?
        ");
        $stmt2->execute([$otherUserId]);
        $otherUser = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        if (!$otherUser) {
            $otherUser = [
                'id' => 0,
                'username' => 'Nepoznati',
                'first_name' => '',
                'last_name' => '',
                'avatar' => '/assets/images/defaults/avatar.png',
                'is_verified' => 0
            ];
        }
        
        // Formatiraj ime za prikaz
        $otherUserName = !empty($otherUser['first_name']) && !empty($otherUser['last_name']) 
            ? $otherUser['first_name'] . ' ' . $otherUser['last_name'] 
            : $otherUser['username'];
        
        // Kreiraj last_message niz samo ako postoji poruka
        $lastMessage = null;
        if ($conv['last_msg_id']) {
            $lastMessage = [
                'id' => (int)$conv['last_msg_id'],
                'message' => htmlspecialchars_decode($conv['last_msg_text'] ?? ''),
                'sender_id' => (int)$conv['last_msg_sender'],
                'is_own' => ($conv['last_msg_sender'] == $userId),
                'created_at' => $conv['last_msg_created'],
                'time_ago' => timeAgo($conv['last_msg_created'])
            ];
        }
        
        $result[] = [
            'id' => (int)$conv['id'],
            'uuid' => $conv['uuid'],
            'other_user' => [
                'id' => (int)$otherUser['id'],
                'username' => $otherUser['username'],
                'name' => $otherUserName,
                'avatar' => $otherUser['avatar'] ?? '/assets/images/defaults/avatar.png',
                'is_verified' => (bool)$otherUser['is_verified']
            ],
            'ad' => $conv['ad_id'] ? [
                'id' => (int)$conv['ad_id'],
                'title' => htmlspecialchars_decode($conv['ad_title'] ?? ''),
                'price' => (float)($conv['ad_price'] ?? 0),
                'slug' => $conv['ad_slug'] ?? '',
                'url' => '/?page=ad-detail&id=' . $conv['ad_id']
            ] : null,
            'last_message' => $lastMessage,
            'unread_count' => (int)$conv['unread_count'],
            'last_activity' => $conv['last_activity'] ?? $conv['created_at'],
            'created_at' => $conv['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'conversations' => $result,
        'total_unread' => array_sum(array_column($result, 'unread_count'))
    ]);
    
} catch (Exception $e) {
    error_log("Get conversations error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju konverzacija: ' . $e->getMessage()
    ]);
}