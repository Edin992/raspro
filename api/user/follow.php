<?php
/**
 * api/user/follow.php - Zaprati korisnika (sa email notifikacijom)
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

// 1. PROVERA AUTENTIFIKACIJE
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Morate biti ulogovani']);
    exit;
}

// 2. PROVERA METODA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Samo POST metoda je dozvoljena']);
    exit;
}

// 3. DOHVAĆANJE PODATAKA
$data = json_decode(file_get_contents('php://input'), true);
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// 4. VALIDACIJA
if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID korisnika je obavezan']);
    exit;
}

$userIdToFollow = (int)$data['user_id'];
$currentUserId = $_SESSION['user_id'];

// 5. NE MOŽEŠ PRATITI SEBE
if ($userIdToFollow === $currentUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ne možete pratiti sami sebe']);
    exit;
}

// 6. PROVERA DA LI KORISNIK POSTOJI
$db = getDatabaseConnection();

$stmt = $db->prepare("SELECT id, username, email, first_name FROM users WHERE id = ?");
$stmt->execute([$userIdToFollow]);
$userToFollow = $stmt->fetch();

if (!$userToFollow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Korisnik nije pronađen']);
    exit;
}

// 7. PROVERA DA LI VEĆ PRATI
$stmt = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$currentUserId, $userIdToFollow]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Već pratite ovog korisnika']);
    exit;
}

// 8. DODAVANJE FOLLOW-a
try {
    $db->beginTransaction();
    
    // Dodaj u followers tabelu
    $stmt = $db->prepare("INSERT INTO followers (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$currentUserId, $userIdToFollow]);
    
    // Dohvati podatke o korisniku koji prati
    $stmt = $db->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $follower = $stmt->fetch();
    
    $followerName = $follower['first_name'] . ' ' . $follower['last_name'];
    if (trim($followerName) === '') {
        $followerName = $follower['username'];
    }
    
    // ============================================
    // KREIRAJ NOTIFIKACIJU U BAZI
    // ============================================
    $notificationData = [
        'follower_id' => $currentUserId,
        'follower_username' => $follower['username'],
        'follower_name' => $followerName,
        'action' => 'follow'
    ];
    
    $stmt = $db->prepare("
        INSERT INTO notifications 
        (user_id, type, title, message, data, is_read, created_at) 
        VALUES (?, 'follow', ?, ?, ?, 0, NOW())
    ");
    
    $notificationTitle = "🎉 Novi pratilac";
    $notificationMessage = $followerName . " (@{$follower['username']}) je zapratio/la vaš profil.";
    
    $stmt->execute([
        $userIdToFollow,
        $notificationTitle,
        $notificationMessage,
        json_encode($notificationData, JSON_UNESCAPED_UNICODE)
    ]);
    
    $notificationId = $db->lastInsertId();
    
    // ============================================
    // POŠALJI EMAIL OBAVEŠTENJE
    // ============================================
    $emailSubject = "Novi pratilac na Rasprodaja.rs";
    
    $emailHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Novi pratilac!</h2>
            </div>
            <div class='content'>
                <h3>Zdravo {$userToFollow['first_name']},</h3>
                <p><strong>{$followerName}</strong> (@{$follower['username']}) je zapratio/la vaš profil na <strong>Rasprodaja.rs</strong>.</p>
                <p>To znači da će pratiti vaše aktivnosti i nove oglase.</p>
                <p>
                    <a href='" . SITE_URL . "/profile/{$currentUserId}' class='btn'>
                        Pogledajte profil
                    </a>
                </p>
                <hr>
                <p style='font-size: 14px; color: #666;'>
                    <strong>Statistika:</strong><br>
                    ➜ Ukupan broj pratilaca: " . (getFollowersCount($userIdToFollow) + 1) . "<br>
                    ➜ Vaš profil: " . SITE_URL . "/profile/{$userIdToFollow}
                </p>
            </div>
            <div class='footer'>
                <p>Rasprodaja.rs - Najveći oglasnik u Srbiji</p>
                <p>© " . date('Y') . " Rasprodaja.rs. Sva prava zadržana.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $emailSent = sendEmail(
        $userToFollow['email'],
        $emailSubject,
        $emailHtml
    );
    
    $db->commit();
    
    // 9. VRATI ODGOVOR
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste zapratili korisnika',
        'data' => [
            'user_id' => $userIdToFollow,
            'username' => $userToFollow['username'],
            'followers_count' => getFollowersCount($userIdToFollow),
            'notification_sent' => $emailSent
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Greška pri praćenju: ' . $e->getMessage()]);
}
?>