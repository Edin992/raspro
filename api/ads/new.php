<?php
/**
 * api/ads/new.php - Vraća SVE oglase sortirane od najnovijih
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Parametri
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
$limit = min($limit, 50); // Max 50 oglasa

try {
    $db = getDatabaseConnection();
    
    // Dohvati SVE aktivne oglase (ne samo nove)
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.price,
            a.currency,
            a.price_negotiable,
            a.city,  -- Vaše polje 'city'
            a.created_at,
            a.views,
            a.is_premium,
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
        ORDER BY a.created_at DESC  -- Najnovije na početku
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
                'currency' => $ad['currency'],
                'price_negotiable' => (bool)$ad['price_negotiable'],
                'city' => $ad['city'],  // Grad iz polja
                'created_at' => $ad['created_at'],
                'views' => (int)$ad['views'],
                'seller' => $ad['seller_username'],
                'category' => $ad['category_name'],
                'image' => $ad['main_image'] ?: null,
                'time_ago' => timeAgo($ad['created_at']),
                'is_premium' => (bool)$ad['is_premium']  // Da znamo da li je premium
            ];
        }
        
        echo json_encode([
            'success' => true,
            'ads' => $formattedAds,
            'count' => count($formattedAds),
            'limit' => $limit,
            'message' => count($formattedAds) . ' oglasa pronađeno'
        ]);
    } else {
        // NEMA oglasa uopšte
        echo json_encode([
            'success' => true,
            'ads' => [],
            'count' => 0,
            'limit' => $limit,
            'message' => 'Trenutno nema oglasa. Budite prvi koji će postaviti oglas!'
        ]);
    }
    
} catch (Exception $e) {
    error_log("All ads API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju oglasa',
        'ads' => [],
        'count' => 0
    ]);
}

// Funkcija timeAgo
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