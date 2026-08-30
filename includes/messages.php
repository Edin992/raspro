<?php
/**
 * includes/messages.php - Funkcije za rad sa porukama
 */

/**
 * Dohvata sve razgovore za korisnika
 */
/**
 * Dohvata sve razgovore za korisnika
 */
function getUserConversations($userId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.uuid,
            c.user1_id,
            c.user2_id,
            c.ad_id,
            a.title as ad_title,
            a.price as ad_price,
            c.last_activity,
            c.created_at,
            (SELECT COUNT(*) FROM messages m 
             WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0) as unread_count,
            (SELECT m.message FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_message,
            (SELECT m.sender_id FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_sender_id,
            (SELECT m.created_at FROM messages m 
             WHERE m.conversation_id = c.id 
             ORDER BY m.created_at DESC LIMIT 1) as last_message_created
        FROM conversations c
        LEFT JOIN ads a ON c.ad_id = a.id AND a.status = 'active'
        WHERE (c.user1_id = ? OR c.user2_id = ?) 
        AND c.is_archived = FALSE
        ORDER BY c.last_activity DESC, c.created_at DESC
    ");
    
    $stmt->execute([$userId, $userId, $userId]);
    $conversations = $stmt->fetchAll();
    
    // Transformiši podatke da last_message bude niz (array)
    foreach ($conversations as &$conv) {
        $conv['last_message'] = [
            'message' => $conv['last_message'] ?? '',
            'created_at' => $conv['last_message_created'] ?? date('Y-m-d H:i:s'),
            'sender_id' => $conv['last_sender_id'] ?? 0
        ];
        unset($conv['last_message_created']);
        unset($conv['last_sender_id']);
    }
    
    return $conversations;
}

/**
 * Dohvata poruke za određeni razgovor
 */
function getConversationMessages($conversationId, $userId, $limit = 50, $offset = 0) {
    $db = getDatabaseConnection();
    
    // Proveri da li korisnik učestvuje u razgovoru
    $stmt = $db->prepare("
        SELECT id FROM conversations 
        WHERE id = ? AND (user1_id = ? OR user2_id = ?)
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        return []; // Korisnik nije učesnik
    }
    
    // Dohvati poruke
    $stmt = $db->prepare("
        SELECT 
            m.*,
            u.username as sender_username,
            u.avatar as sender_avatar,
            u.is_verified as sender_verified
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$conversationId, $limit, $offset]);
    $messages = $stmt->fetchAll();
    
    // Oznaci poruke kao pročitane
    markMessagesAsRead($conversationId, $userId);
    
    return $messages;
}

/**
 * Oznaci poruke kao pročitane
 */
function markMessagesAsRead($conversationId, $userId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        UPDATE messages 
        SET is_read = TRUE, 
            read_at = NOW()
        WHERE conversation_id = ? 
        AND receiver_id = ? 
        AND is_read = FALSE
    ");
    
    return $stmt->execute([$conversationId, $userId]);
}

/**
 * Kreira novi razgovor
 */
function createConversation($user1Id, $user2Id, $adId = null) {
    $db = getDatabaseConnection();
    
    // Proveri da li razgovor već postoji
    $stmt = $db->prepare("
        SELECT id FROM conversations 
        WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
        AND ad_id = ? AND is_archived = FALSE
        LIMIT 1
    ");
    
    $stmt->execute([$user1Id, $user2Id, $user2Id, $user1Id, $adId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        return $existing['id']; // Vrati postojeći razgovor
    }
    
    // Kreiraj novi razgovor
    $uuid = bin2hex(random_bytes(16));
    
    $stmt = $db->prepare("
        INSERT INTO conversations (uuid, user1_id, user2_id, ad_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$uuid, $user1Id, $user2Id, $adId]);
    return $db->lastInsertId();
}

/**
 * Dohvata drugog korisnika u razgovoru
 */
function getOtherUserInConversation($conversation, $currentUserId) {
    $db = getDatabaseConnection();
    
    $otherUserId = ($conversation['user1_id'] == $currentUserId) 
        ? $conversation['user2_id'] 
        : $conversation['user1_id'];
    
    $stmt = $db->prepare("
        SELECT id, username, email, avatar, is_verified,
               CONCAT(first_name, ' ', last_name) as name
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->execute([$otherUserId]);
    $user = $stmt->fetch();
    
    // Zaštita – ako nema korisnika, vrati prazan niz
    if (!$user) {
        return [
            'id' => 0,
            'username' => 'Nepoznati korisnik',
            'name' => 'Nepoznati korisnik',
            'avatar' => SITE_URL . '/assets/images/defaults/avatar.png',
            'is_verified' => false
        ];
    }
    
    return $user;
}

/**
 * Dohvata aktivne oglase korisnika
 */
function getUserActiveAds($userId) {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("
        SELECT id, title, price, city, created_at
        FROM ads 
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}