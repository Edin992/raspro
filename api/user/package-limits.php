<?php
/**
 * api/user/package-limits.php - Vraća limite za paket korisnika iz tabele subscription_plans
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php';

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
    
    // Prvo dohvati paket korisnika iz users tabele
    $stmt = $db->prepare("SELECT package FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("Korisnik nije pronađen");
    }
    
    $userPackage = strtolower($user['package']); // free, silver, gold
    
    // Zatim dohvati limite iz subscription_plans tabele
    $stmt = $db->prepare("
        SELECT 
            name,
            slug,
            max_ads,
            max_images,
            max_premium_ads,
            features
        FROM subscription_plans 
        WHERE LOWER(name) = ? AND is_active = 1
    ");
    $stmt->execute([$userPackage]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        // Ako nije pronađen plan, koristi default za free
        error_log("Plan not found for package: $userPackage, using defaults");
        $plan = [
            'name' => 'free',
            'slug' => 'free',
            'max_ads' => 10,
            'max_images' => 10,
            'max_premium_ads' => 0,
            'features' => json_encode(['Osnovni oglas', '10 slika', 'Kontakt forma'])
        ];
    }
    
    // Parsiraj features ako je JSON string
    $features = is_string($plan['features']) ? json_decode($plan['features'], true) : $plan['features'];
    
    // Dohvati trenutni broj oglasa korisnika
    $currentAds = getCurrentAdCount($userId);
    $remainingAds = $plan['max_ads'] - $currentAds;
    
    // ========== DODATO: Broj aktivnih premium oglasa ==========
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE user_id = ? 
        AND is_premium = 1 
        AND premium_until > NOW()
        AND status = 'active'
    ");
    $stmt->execute([$userId]);
    $currentPremiumAds = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    
    $maxPremiumAds = (int)$plan['max_premium_ads'];
    $remainingPremiumAds = $maxPremiumAds - $currentPremiumAds;
    // ========== KRAJ DODATOG ==========
    
    // Maksimalna veličina slike (u bajtovima)
    $maxImageSize = 5 * 1024 * 1024; // Default 5MB
    
    echo json_encode([
        'success' => true,
        'package' => $plan['name'],
        'package_slug' => $plan['slug'],
        'limits' => [
            'ads' => (int)$plan['max_ads'],
            'images' => (int)$plan['max_images'],
            'max_image_size' => $maxImageSize,
            'premium_ads' => (int)$plan['max_premium_ads']
        ],
        'features' => $features,
        'current_ads' => $currentAds,
        'remaining_ads' => $remainingAds >= 0 ? $remainingAds : 0,
        'has_reached_limit' => $currentAds >= $plan['max_ads'],
        // ========== DODATO: Premium statistika ==========
        'premium_stats' => [
            'current' => $currentPremiumAds,
            'max' => $maxPremiumAds,
            'remaining' => $remainingPremiumAds >= 0 ? $remainingPremiumAds : 0,
            'has_reached_limit' => $currentPremiumAds >= $maxPremiumAds
        ]
        // ========== KRAJ DODATOG ==========
    ]);
    
} catch (Exception $e) {
    error_log("Get package limits error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju limita paketa: ' . $e->getMessage()
    ]);
}
?>