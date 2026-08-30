<?php
/**
 * api/chat/send.php - ISPRAVLJENA VERZIJA sa boljom proverom duplih razgovora
 */
session_start();
error_log("=== SEND.PHP POKRENUT ===");
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once __DIR__ . '/../../includes/functions.php';

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Morate biti prijavljeni']);
    exit();
}

$senderId = $_SESSION['user_id'];
$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : null;
$adId = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : null;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

// Validacija...
if (!$receiverId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Primaoc nije određen']);
    exit();
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Poruka ne može biti prazna']);
    exit();
}

if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token nije validan']);
    exit();
}

try {
    $db = getDatabaseConnection();
    $db->beginTransaction();
    
    // ===== PROVERI POSTOJEĆU KONVERZACIJU =====
    $conversationId = null;
    
    // Prvo proveri da li već postoji konverzacija za OVE korisnike i OVAJ oglas
    if ($adId) {
        $stmt = $db->prepare("
            SELECT id FROM conversations 
            WHERE ad_id = ? 
            AND ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
            AND is_archived = 0
        ");
        $stmt->execute([$adId, $senderId, $receiverId, $receiverId, $senderId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $conversationId = $existing['id'];
            error_log("✓ Pronađena postojeća konverzacija ID: $conversationId za ad_id: $adId");
        }
    }
    
    // Ako nema konverzacije sa oglasom, proveri da li postoji bilo kakva konverzacija između ova dva korisnika
    if (!$conversationId) {
        $stmt = $db->prepare("
            SELECT id FROM conversations 
            WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?))
            AND ad_id IS NULL
            AND is_archived = 0
            LIMIT 1
        ");
        $stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $conversationId = $existing['id'];
            error_log("✓ Pronađena postojeća konverzacija bez oglasa ID: $conversationId");
        }
    }
    
    // Ako i dalje nema konverzacije, kreiraj novu
    if (!$conversationId) {
        $uuid = bin2hex(random_bytes(16));
        error_log("✗ Nema postojeće konverzacije, kreiram novu sa UUID: $uuid");
        
        $stmt = $db->prepare("
            INSERT INTO conversations (uuid, user1_id, user2_id, ad_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$uuid, $senderId, $receiverId, $adId]);
        $conversationId = $db->lastInsertId();
        error_log("✓ Nova konverzacija kreirana sa ID: $conversationId");
    }
    
    // Kreiraj poruku
    $stmt = $db->prepare("
        INSERT INTO messages (conversation_id, sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$conversationId, $senderId, $receiverId, $message]);
    $messageId = $db->lastInsertId();
    error_log("✓ Poruka kreirana sa ID: $messageId");
    
    // Ažuriraj last_activity
    $stmt = $db->prepare("UPDATE conversations SET last_activity = NOW() WHERE id = ?");
    $stmt->execute([$conversationId]);
    
    // Kreiraj notifikaciju (opciono)
    try {
        $notificationData = json_encode([
            'sender_id' => $senderId,
            'conversation_id' => $conversationId,
            'message_id' => $messageId
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, is_read, created_at)
            VALUES (?, 'message', 'Nova poruka', ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $receiverId,
            substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
            $notificationData
        ]);
    } catch (Exception $e) {
        error_log("Notifikacija nije uspela: " . $e->getMessage());
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Poruka je uspešno poslata!',
        'message_id' => $messageId,
        'conversation_id' => $conversationId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    error_log("GREŠKA: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri slanju poruke: ' . $e->getMessage()
    ]);
}