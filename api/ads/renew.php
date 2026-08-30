<?php
/**
 * api/ads/renew.php - Obnavljanje isteklog oglasa
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

// Proveri CSRF token
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (!isset($input['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token nije validan']);
    exit();
}

$adId = isset($input['ad_id']) ? intval($input['ad_id']) : 0;
$userId = $_SESSION['user_id'];

if (!$adId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID oglasa nije prosleđen']);
    exit();
}

try {
    $db = getDatabaseConnection();
    
    // Proveri da li oglas postoji i da li pripada korisniku
    $stmt = $db->prepare("
        SELECT a.*, u.package as user_package 
        FROM ads a
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$adId, $userId]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Oglas nije pronađen']);
        exit();
    }
    
    // Proveri da li je oglas istekao
    $today = new DateTime();
    $expiresAt = new DateTime($ad['expires_at']);
    
    // Proveri da li je oglas u periodu za obnovu (7 dana pre isteka ILI nakon isteka)
    $daysUntilExpiry = $today->diff($expiresAt)->days;
    $isExpired = ($ad['status'] === 'expired');
    $canRenew = ($isExpired || ($daysUntilExpiry <= 7 && $daysUntilExpiry >= 0));
    
    if (!$canRenew && !$isExpired) {
        $daysLeft = $today->diff($expiresAt)->days;
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "Oglas se može obnoviti samo 7 dana pre isteka. Preostalo dana: $daysLeft"
        ]);
        exit();
    }
    
    // Proveri limit oglasa za korisnika (samo za free paket)
    $userPackage = strtolower($ad['user_package']);
    $userLimits = getUserLimits($userId);
    $currentAds = $userLimits['current_ads'];
    $adLimit = $userLimits['ad_limit'];
    
    // Ako je free paket i oglas je obrisan (deleted), proveri limit
    if ($userPackage === 'free' && $ad['status'] === 'deleted') {
        if ($currentAds >= $adLimit) {
            http_response_code(403);
            echo json_encode([
                'success' => false, 
                'message' => "Dostigli ste limit od {$adLimit} aktivnih oglasa za FREE paket. Izbrišite neki oglas ili nadogradite paket."
            ]);
            exit();
        }
        
        // Povećaj broj oglasa korisnika
        $stmt = $db->prepare("UPDATE users SET ads_count = ads_count + 1 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Povećaj broj oglasa kategorije
        $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count + 1 WHERE id = ?");
        $stmt->execute([$ad['category_id']]);
        
        if (!empty($ad['subcategory_id'])) {
            $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count + 1 WHERE id = ?");
            $stmt->execute([$ad['subcategory_id']]);
        }
    }
    
    // Izračunaj novi datum isteka (30 dana od danas)
    $newExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    $renewCount = $ad['renew_count'] + 1;
    
    // Ažuriraj oglas
    $stmt = $db->prepare("
        UPDATE ads 
        SET status = 'active',
            expires_at = ?,
            created_at = NOW(),
            renewed_at = NOW(),
            renew_count = ?
        WHERE id = ?
    ");
    $stmt->execute([$newExpiresAt, $renewCount, $adId]);
    
    // Loguj aktivnost
    logUserActivity($userId, 'ad_renew', [
        'ad_id' => $adId,
        'ad_title' => $ad['title'],
        'old_expiry' => $ad['expires_at'],
        'new_expiry' => $newExpiresAt,
        'renew_count' => $renewCount
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Oglas je uspešno obnovljen!',
        'new_expires_at' => date('d.m.Y.', strtotime($newExpiresAt)),
        'renew_count' => $renewCount
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri obnavljanju oglasa: ' . $e->getMessage()
    ]);
    error_log("Renew ad error: " . $e->getMessage());
}