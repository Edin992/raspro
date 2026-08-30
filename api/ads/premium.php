<?php
/**
 * api/ads/premium.php - Vraća premium oglase za početnu stranu
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Parametri
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1;
$limit = min($limit, 20); // Max 20 oglasa

try {
    $db = getDatabaseConnection();
    
    // Dohvati premium oglase
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.price,
            a.price_negotiable,
            a.city,  
            a.created_at,
            a.views,
            u.username as seller_username,
            c.name as category_name,
            (
                SELECT thumbnail_path 
                FROM ad_images 
                WHERE ad_id = a.id AND is_main = 1 
                LIMIT 1
            ) as main_image
        FROM ads a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'active'
        AND a.is_premium = 1
        AND (a.premium_until IS NULL OR a.premium_until > NOW())
        ORDER BY 
            a.premium_until DESC,  
            a.created_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$limit]);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatiraj odgovor
    $formattedAds = [];
    if ($ads && count($ads) > 0) {
        foreach ($ads as $ad) {
            $formattedAds[] = [
                'id' => (int)$ad['id'],
                'title' => $ad['title'],
                'slug' => $ad['slug'],
                'price' => (float)$ad['price'],
                'price_negotiable' => (bool)$ad['price_negotiable'],
                'city' => $ad['city'],  // Grad iz direktnog polja
                'views' => (int)$ad['views'],
                'created_at' => $ad['created_at'],
                'seller' => $ad['seller_username'],
                'category' => $ad['category_name'],
                'image' => $ad['main_image'] ?: null,
                'time_ago' => timeAgo($ad['created_at']),
                'is_premium' => true  // Dodajemo ovo za JavaScript
            ];
        }
        
        echo json_encode([
            'success' => true,
            'ads' => $formattedAds,
            'count' => count($formattedAds),
            'limit' => $limit,
            'message' => count($formattedAds) . ' premium oglasa pronađeno'
        ]);
    } else {
        // NEMA premium oglasa - vraća praznu listu
        echo json_encode([
            'success' => true,
            'ads' => [],
            'count' => 0,
            'limit' => $limit,
            'message' => 'Trenutno nema premium oglasa. Budite prvi!'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Premium ads API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju premium oglasa',
        'ads' => [],
        'count' => 0
    ]);
}

// Funkcija timeAgo ako je nema u database.php
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'upravo sada';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "pre $minutes min";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "pre $hours sati";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "pre $days dana";
        } else {
            return date('d.m.Y', $time);
        }
    }
}
?>