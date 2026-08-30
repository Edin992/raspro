<?php
/**
 * api/ads/premium-list.php - Vraća premium oglase za slider
 * ISPRAVLJENO ZA VAŠU BAZU PODATAKA
 */

// Start session za potencijalne dodatne checkove
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDatabaseConnection();
    
    // PARAMETRI
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 12;
    $limit = min($limit, 50); // Max 50 za sigurnost
    
    $categoryName = isset($_GET['category']) ? trim($_GET['category']) : '';
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';
    
    // SQL UPIT - PODEŠEN ZA VAŠE TABELE
    $sql = "
        SELECT 
            a.id,
            a.user_id,
            a.category_id,
            a.title,
            a.slug,
            a.description,
            a.price,
            a.currency,
            a.price_negotiable,
            a.item_condition,
            a.city,
            a.is_premium,
            a.premium_until,
            a.views,
            a.created_at,
            a.expires_at,
            c.name as category_name,
            u.username as seller_username,
            u.first_name as seller_first_name,
            u.last_name as seller_last_name,
            u.avatar as seller_avatar,
            u.package as seller_package,
            u.is_verified as seller_verified,
            (
                SELECT COUNT(*) 
                FROM ad_images ai 
                WHERE ai.ad_id = a.id
            ) as image_count,
            (
                SELECT ai.thumbnail_path 
                FROM ad_images ai 
                WHERE ai.ad_id = a.id 
                ORDER BY ai.is_main DESC, ai.display_order 
                LIMIT 1
            ) as main_image
        FROM ads a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active'
        AND a.is_premium = 1
        AND a.premium_until > NOW()
    ";
    
    $params = [];
    
    // DODAJ FILTERE
    if (!empty($categoryName)) {
        $sql .= " AND c.name = ?";
        $params[] = $categoryName;
    }
    
    if (!empty($city)) {
        $sql .= " AND a.city LIKE ?";
        $params[] = "%$city%";
    }
    
    // SORTIRANJE - najskorije premium oglase prvo
    $sql .= " ORDER BY a.premium_until DESC, a.created_at DESC";
    
    // LIMIT
    $sql .= " LIMIT ?";
    $params[] = $limit;
    
    // IZVRŠI UPIT
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ads = $stmt->fetchAll();
    
    // UKUPAN BROJ PREMIUM OGLASA (za info)
    $countSql = "
        SELECT COUNT(*) as total 
        FROM ads a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'active' 
        AND a.is_premium = 1 
        AND a.premium_until > NOW()
    ";
    
    $countParams = [];
    
    if (!empty($categoryName)) {
        $countSql .= " AND c.name = ?";
        $countParams[] = $categoryName;
    }
    
    if (!empty($city)) {
        $countSql .= " AND a.city LIKE ?";
        $countParams[] = "%$city%";
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $countResult = $countStmt->fetch();
    $total = $countResult['total'] ?? 0;
    
    // FORMATIRAJ PODATKE
    $formattedAds = [];
    foreach ($ads as $ad) {
        // Formatiraj ime prodavca
        $sellerName = '';
        if (!empty($ad['seller_first_name']) && !empty($ad['seller_last_name'])) {
            $sellerName = $ad['seller_first_name'] . ' ' . $ad['seller_last_name'];
        } else {
            $sellerName = $ad['seller_username'];
        }
        
        // Formatiraj sliku
        $imageUrl = null;
        if ($ad['main_image']) {
            $imageUrl = $ad['main_image'];
        } elseif ($ad['thumbnail_path']) {
            $imageUrl = $ad['thumbnail_path'];
        }
        
        // Ako nema slike, koristi default
        if (!$imageUrl) {
            $imageUrl = '/assets/images/no-image.png';
        }
        
        // Ako je relativna putanja, dodaj SITE_URL
        if ($imageUrl && strpos($imageUrl, 'http') !== 0 && $imageUrl[0] === '/') {
            // Već je relativna putanja - ostaviti je takvu
            // JavaScript će je koristiti direktno
        }
        
        // Formatiraj vreme
        $createdAt = new DateTime($ad['created_at']);
        $now = new DateTime();
        $diff = $now->diff($createdAt);
        
        $timeAgo = '';
        if ($diff->days > 0) {
            $timeAgo = "pre {$diff->days} dana";
        } elseif ($diff->h > 0) {
            $timeAgo = "pre {$diff->h} sati";
        } elseif ($diff->i > 0) {
            $timeAgo = "pre {$diff->i} minuta";
        } else {
            $timeAgo = "upravo sada";
        }
        
        function formatPriceWithCurrency($price, $currency = 'RSD') {
            $formatted = number_format($price, 0, ',', '.');
            
            if ($currency === 'EUR') {
                return '€ ' . $formatted;
            }
            
            return $formatted . ' RSD';
        }
        
        // Proveri da li je premium još aktivan
        $isPremiumActive = false;
        if ($ad['premium_until']) {
            $premiumUntil = new DateTime($ad['premium_until']);
            $isPremiumActive = $premiumUntil > $now;
        }
        
        // Formatiraj cenu
        $priceFormatted = formatPriceWithCurrency($ad['price'], $ad['currency']);
        
        
        $formattedAds[] = [
            'id' => $ad['id'],
            'title' => $ad['title'],
            'slug' => $ad['slug'],
            'price' => $ad['price'],
            'price_formatted' => $priceFormatted,
            'price_negotiable' => (bool)$ad['price_negotiable'],
            'city' => $ad['city'],
            'category' => $ad['category_name'],
            'image' => $imageUrl,
            'image_count' => $ad['image_count'] ?? 0,
            'views' => $ad['views'],
            'created_at' => $ad['created_at'],
            'expires_at' => $ad['expires_at'],
            'time_ago' => $timeAgo,
            'seller' => [
                'name' => $sellerName,
                'username' => $ad['seller_username'],
                'avatar' => $ad['seller_avatar'],
                'package' => $ad['seller_package'],
                'verified' => (bool)$ad['seller_verified']
            ],
            'is_premium' => $isPremiumActive,
            'premium_until' => $ad['premium_until']
        ];
    }
    
    // VRATI ODGOVOR
    echo json_encode([
        'success' => true,
        'ads' => $formattedAds,
        'total' => $total,
        'limit' => $limit,
        'has_more' => $total > $limit
    ]);
    
} catch (Exception $e) {
    error_log("Premium list API error: " . $e->getMessage());
    
    // Debug info
    if (isset($db)) {
        $errorInfo = $db->errorInfo();
        error_log("PDO Error: " . json_encode($errorInfo));
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri učitavanju premium oglasa',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>