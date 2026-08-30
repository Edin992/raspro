<?php
/**
 * api/stats/home.php - Vraća statistiku za početnu stranu
 * KORISTI POSTOJEĆE FUNKCIJE IZ includes/categories.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/categories.php'; // ✅ DODAJ OVO!

try {
    $db = getDatabaseConnection();
    
    // 1. Ukupan broj aktivnih oglasa
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM ads WHERE status = 'active'");
    $stmt->execute();
    $totalAds = (int)($stmt->fetch()['count'] ?? 0);
    
    // 2. Oglasi dodati danas
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE status = 'active' 
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $dailyAds = (int)($stmt->fetch()['count'] ?? 0);
    
    // 3. Aktivni korisnici (verifikovani sa login-om u poslednjih 30 dana)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE is_verified = 1 
        AND last_login >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND last_login IS NOT NULL
    ");
    $stmt->execute();
    $activeUsers = (int)($stmt->fetch()['count'] ?? 0);
    
    // 4. Broj kategorija - ✅ KORISTIMO POSTOJEĆU FUNKCIJU!
    $allCategories = getAllCategories();
    $totalCategories = count($allCategories);
    
    // 5. Premium oglasi
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE status = 'active' 
        AND is_premium = 1
        AND (premium_until IS NULL OR premium_until > NOW())
    ");
    $stmt->execute();
    $premiumAds = (int)($stmt->fetch()['count'] ?? 0);
    
    // 6. Broj verifikovanih korisnika ukupno
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE is_verified = 1");
    $stmt->execute();
    $totalVerifiedUsers = (int)($stmt->fetch()['count'] ?? 0);
    
    // 7. Novi korisnici danas
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $newUsersToday = (int)($stmt->fetch()['count'] ?? 0);
    
    // 8. Popularne kategorije (prvih 5 po broju oglasa) - ✅ DODAJEMO!
    $mainCategories = getMainCategories();
    usort($mainCategories, function($a, $b) {
        return ($b['ad_count'] ?? 0) <=> ($a['ad_count'] ?? 0);
    });
    $popularCategories = array_slice($mainCategories, 0, 5);
    
    // 9. Broj oglasa po paketima korisnika - ✅ DODAJEMO!
    $stmt = $db->prepare("
        SELECT 
            u.package,
            COUNT(a.id) as ad_count
        FROM users u
        LEFT JOIN ads a ON u.id = a.user_id AND a.status = 'active'
        WHERE u.is_verified = 1
        GROUP BY u.package
    ");
    $stmt->execute();
    $packageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatiranje za prikaz
    function formatNumberForDisplay($number) {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M+';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K+';
        } else {
            return $number . '+';
        }
    }
    
    $totalAdsDisplay = formatNumberForDisplay($totalAds);
    $dailyAdsDisplay = formatNumberForDisplay($dailyAds);
    $activeUsersDisplay = formatNumberForDisplay($activeUsers);
    
    // Vrati rezultat
    echo json_encode([
        'success' => true,
        'stats' => [
            // Oglasi
            'total_ads' => $totalAds,
            'total_ads_display' => $totalAdsDisplay,
            'daily_ads' => $dailyAds,
            'daily_ads_display' => $dailyAdsDisplay,
            'premium_ads' => $premiumAds,
            
            // Korisnici
            'active_users' => $activeUsers,
            'active_users_display' => $activeUsersDisplay,
            'total_verified_users' => $totalVerifiedUsers,
            'new_users_today' => $newUsersToday,
            
            // Kategorije
            'total_categories' => $totalCategories,
            'popular_categories' => $popularCategories,
            
            // Paketi
            'package_stats' => $packageStats,
            
            // Metapodaci
            'updated_at' => date('Y-m-d H:i:s'),
            'note' => 'Live statistics from database'
        ],
        'meta' => [
            'functions_used' => [
                'getAllCategories' => !empty($allCategories),
                'getMainCategories' => !empty($mainCategories)
            ],
            'database_tables' => ['ads', 'users', 'categories']
        ],
        'timestamp' => time()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Stats API error: " . $e->getMessage());
    
    // Fallback statistika
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_ads' => 12543,
            'total_ads_display' => '12K+',
            'daily_ads' => 243,
            'daily_ads_display' => '240+',
            'premium_ads' => 45,
            'active_users' => 8567,
            'active_users_display' => '8.5K+',
            'total_verified_users' => 10567,
            'new_users_today' => 12,
            'total_categories' => 15,
            'popular_categories' => [
                ['id' => 1, 'name' => 'Automobili', 'ad_count' => 1234],
                ['id' => 2, 'name' => 'Nekretnine', 'ad_count' => 987],
                ['id' => 3, 'name' => 'Telefoni', 'ad_count' => 654],
                ['id' => 4, 'name' => 'Računari', 'ad_count' => 321],
                ['id' => 5, 'name' => 'Odeća', 'ad_count' => 210]
            ],
            'package_stats' => [
                ['package' => 'Free', 'ad_count' => 8900],
                ['package' => 'Silver', 'ad_count' => 2500],
                ['package' => 'Gold', 'ad_count' => 1143]
            ],
            'updated_at' => date('Y-m-d H:i:s'),
            'note' => 'Using fallback statistics'
        ],
        'timestamp' => time()
    ]);
}
?>