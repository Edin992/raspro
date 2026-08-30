<?php
/**
 * api/ads/make-premium.php - Označavanje oglasa kao premium
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Hvataj sve greške
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php';
require_once __DIR__ . '/../../includes/auth.php';

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

$userId = $_SESSION['user_id'];

// Proveri CSRF token
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$csrfToken = $input['csrf_token'] ?? '';
if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nevažeći CSRF token']);
    exit();
}

$adId = isset($input['ad_id']) ? intval($input['ad_id']) : 0;

if (!$adId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID oglasa nije prosleđen']);
    exit();
}

try {
    $db = getDatabaseConnection();
    
    // Proveri da li oglas postoji i da li pripada korisniku
    $stmt = $db->prepare("
        SELECT id, user_id, title, is_premium, premium_until, status 
        FROM ads 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Oglas nije pronađen ili nije aktivan']);
        exit();
    }
    
    if ($ad['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nemate pravo da menjate ovaj oglas']);
        exit();
    }
    
    // Proveri da li je oglas već premium
    if ($ad['is_premium'] == 1 && strtotime($ad['premium_until']) > time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Oglas je već premium do ' . date('d.m.Y.', strtotime($ad['premium_until']))]);
        exit();
    }
    
    // Dohvati korisničke limite
    $userLimits = getUserLimits($userId);
    $maxPremiumAds = $userLimits['premium_limit'];
    
    // Proveri koliko premium oglasa korisnik već ima
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE user_id = ? AND is_premium = 1 AND premium_until > NOW()
    ");
    $stmt->execute([$userId]);
    $currentPremiumCount = $stmt->fetch()['count'];
    
    if ($currentPremiumCount >= $maxPremiumAds) {
        $remaining = $maxPremiumAds - $currentPremiumCount;
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => "Dostigli ste maksimalan broj premium oglasa za vaš paket ($maxPremiumAds).",
            'current_premium' => $currentPremiumCount,
            'max_premium' => $maxPremiumAds
        ]);
        exit();
    }
    
    // Postavi premium do 30 dana
    $premiumUntil = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Ažuriraj oglas kao premium
    $stmt = $db->prepare("
        UPDATE ads 
        SET is_premium = 1, 
            premium_until = ?
        WHERE id = ?
    ");
    $stmt->execute([$premiumUntil, $adId]);
    
    // Loguj aktivnost
    if (function_exists('logUserActivity')) {
        logUserActivity($userId, 'ad_make_premium', [
            'ad_id' => $adId,
            'ad_title' => $ad['title'],
            'premium_until' => $premiumUntil
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Oglas je uspešno označen kao premium!',
        'premium_until' => date('d.m.Y.', strtotime($premiumUntil)),
        'current_premium' => $currentPremiumCount + 1,
        'max_premium' => $maxPremiumAds,
        'remaining' => $maxPremiumAds - ($currentPremiumCount + 1)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri postavljanju premium oglasa: ' . $e->getMessage()
    ]);
    error_log("Make premium error: " . $e->getMessage());
}