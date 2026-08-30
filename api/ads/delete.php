<?php
/**
 * api/ads/delete.php - Brisanje oglasa (sa razlozima)
 * - Razlog 'sold' -> status 'sold'
 * - Ostali razlozi -> status 'deleted'
 */

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Morate biti prijavljeni']);
    exit();
}

// Proveri da li je prosleđen ID (iz POST body)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$adId = isset($input['ad_id']) ? intval($input['ad_id']) : 0;
$reason = isset($input['reason']) ? $input['reason'] : '';
$userId = $_SESSION['user_id'];

if (!$adId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID oglasa nije prosleđen']);
    exit();
}

// Validacija razloga
$validReasons = ['sold', 'not_available', 'wrong_info', 'other'];
if (!in_array($reason, $validReasons)) {
    $reason = 'other';
}

// Mape za prikaz razloga
$reasonLabels = [
    'sold' => 'Prodat',
    'not_available' => 'Više nije dostupno',
    'wrong_info' => 'Pogrešne informacije',
    'other' => 'Drugi razlog'
];

// Odredi status na osnovu razloga
// Ako je prodato -> status 'sold', inače -> 'deleted'
$newStatus = ($reason === 'sold') ? 'sold' : 'deleted';

try {
    $db = getDatabaseConnection();
    
    // Proveri da li oglas postoji i da li pripada korisniku
    $stmt = $db->prepare("SELECT user_id, category_id, subcategory_id, title, status FROM ads WHERE id = ? AND status NOT IN ('deleted', 'sold', 'expired')");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Oglas nije pronađen ili je već obrisan/prodat']);
        exit();
    }
    
    // Proveri da li korisnik ima pravo da obriše oglas
    if ($ad['user_id'] != $userId && !isAdmin($userId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nemate pravo da obrišete ovaj oglas']);
        exit();
    }
    
    $db->beginTransaction();
    
    // Ažuriraj status oglasa (sold ili deleted)
    $stmt = $db->prepare("
        UPDATE ads 
        SET status = ?, 
            deleted_at = NOW(),
            delete_reason = ?,
            deleted_by = ?
        WHERE id = ?
    ");
    $stmt->execute([$newStatus, $reason, $userId, $adId]);
    
    // Ako nije prodato (tj. briše se), smanji broj oglasa
   
        // Smanji broj oglasa korisnika
        $stmt = $db->prepare("UPDATE users SET ads_count = ads_count - 1 WHERE id = ? AND ads_count > 0");
        $stmt->execute([$ad['user_id']]);
        
        // Smanji broj oglasa kategorije
        $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count - 1 WHERE id = ? AND ad_count > 0");
        $stmt->execute([$ad['category_id']]);
        
        // Ako postoji podkategorija, smanji i njen broj
        if (!empty($ad['subcategory_id'])) {
            $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count - 1 WHERE id = ? AND ad_count > 0");
            $stmt->execute([$ad['subcategory_id']]);
        }
    
    
    // Loguj brisanje
    if (function_exists('logUserActivity')) {
        logUserActivity($userId, 'ad_delete', [
            'ad_id' => $adId,
            'ad_title' => $ad['title'],
            'reason' => $reason,
            'reason_label' => $reasonLabels[$reason],
            'new_status' => $newStatus
        ]);
    }
    
    $db->commit();
    
    $responseMessage = ($newStatus === 'sold') 
        ? 'Oglas je označen kao prodat' 
        : 'Oglas je uspešno obrisan';
    
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'reason' => $reason,
        'reason_label' => $reasonLabels[$reason],
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri brisanju oglasa: ' . $e->getMessage()
    ]);
    error_log("Delete ad error: " . $e->getMessage());
}