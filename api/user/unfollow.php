<?php
/**
 * api/user/unfollow.php - Otprati korisnika (bez email notifikacije)
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

$userIdToUnfollow = (int)$data['user_id'];
$currentUserId = $_SESSION['user_id'];

// 5. PROVERA DA LI KORISNIK POSTOJI
$db = getDatabaseConnection();

$stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userIdToUnfollow]);
$userToUnfollow = $stmt->fetch();

if (!$userToUnfollow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Korisnik nije pronađen']);
    exit;
}

// 6. PROVERA DA LI PRATI
$stmt = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$currentUserId, $userIdToUnfollow]);
$follow = $stmt->fetch();

if (!$follow) {
    echo json_encode(['success' => false, 'error' => 'Ne pratite ovog korisnika']);
    exit;
}

// 7. UKLANJANJE FOLLOW-a
try {
    $db->beginTransaction();
    
    // Ukloni iz followers tabele
    $stmt = $db->prepare("DELETE FROM followers WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$currentUserId, $userIdToUnfollow]);
    
    // KREIRAJ NOTIFIKACIJU ZA UNFOLLOW (opciono - možeš izostaviti)
    // Preporučujem da ne šalješ notifikaciju za unfollow
    // Samo ažuriraj notifikaciju za follow da bude "is_read = 1" ako želiš
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste otpratili korisnika',
        'data' => [
            'user_id' => $userIdToUnfollow,
            'username' => $userToUnfollow['username'],
            'followers_count' => getFollowersCount($userIdToUnfollow)
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Greška pri otpraćivanju: ' . $e->getMessage()]);
}
?>