<?php
/**
 * includes/functions.php - Opšte helper funkcije
 */



/**
 * Vraća trenutni broj aktivnih oglasa korisnika
 */
function getCurrentAdCount($userId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM ads 
            WHERE user_id = ? 
            AND status IN ('active')
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get ad count error: " . $e->getMessage());
        return 0;
    }
}
function getActiveAdCount() {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM ads 
            WHERE status = 'active'
        ");
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get ad count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Dohvata broj premium oglasa korisnika
 */
function getCurrentPremiumAdCount($userId) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM ads 
        WHERE user_id = ? AND is_premium = 1 AND premium_until > NOW()
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}


/**
 * Dohvata ime korisnika za prikaz
 */
function getUserDisplayName($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) return '';
    
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT first_name, last_name, username 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return '';
    
    if (!empty($user['first_name']) && !empty($user['last_name'])) {
        return $user['first_name'] . ' ' . $user['last_name'];
    }
    
    return $user['username'];
}


/**
 * Kreira slug za URL
 */
function createSlug($text) {
    $text = preg_replace('~[^\p{L}\p{N}]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    if (empty($text)) {
        return 'ad-' . uniqid();
    }
    
    return $text;
}

/**
 * Reorganizuje $_FILES niz za lakšu obradu
 */
function rearrayFiles($fileArray) {
    $rearranged = [];
    
    if (is_array($fileArray['name'])) {
        foreach ($fileArray['name'] as $key => $value) {
            if ($fileArray['error'][$key] === UPLOAD_ERR_OK) {
                $rearranged[] = [
                    'name' => $fileArray['name'][$key],
                    'type' => $fileArray['type'][$key],
                    'tmp_name' => $fileArray['tmp_name'][$key],
                    'error' => $fileArray['error'][$key],
                    'size' => $fileArray['size'][$key]
                ];
            }
        }
    } else {
        if ($fileArray['error'] === UPLOAD_ERR_OK) {
            $rearranged[] = $fileArray;
        }
    }
    
    return $rearranged;
}

/**
 * Upload slike za oglas - ISPRAVLJENA VERZIJA za VAŠU BAZU
 */
function uploadAdImage($adId, $image, $isMain = false) {
    $userId = $_SESSION['user_id'] ?? 0;
    
    // Validacija
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($image['type'], $allowedTypes)) {
        error_log("Invalid image type: " . $image['type']);
        return false;
    }
    
    if ($image['size'] > $maxSize) {
        error_log("Image too large: " . $image['size'] . " bytes");
        return false;
    }
    
    // Apsolutna putanja do root foldera
    $rootDir = $_SERVER['DOCUMENT_ROOT'];
    
    // Kreiraj folder za ovaj oglas
    $uploadDir = $rootDir . '/assets/uploads/ads/' . $adId . '/';
    
    // Debug: proveri putanju
    error_log("Upload directory: " . $uploadDir);
    
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create directory: " . $uploadDir);
            return false;
        }
        error_log("Directory created: " . $uploadDir);
    }
    
    // Proveri dozvole foldera
    if (!is_writable($uploadDir)) {
        error_log("Directory not writable: " . $uploadDir);
        chmod($uploadDir, 0755);
    }
    
    // Generiši ime fajla
    $fileName = 'img_' . time() . '_' . bin2hex(random_bytes(8));
    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
    // Putanje za različite verzije
    $originalFileName = $fileName . '.' . $extension;
    $thumbFileName = $fileName . '_thumb.' . $extension;
    $mediumFileName = $fileName . '_medium.' . $extension;
    
    $originalPath = $uploadDir . $originalFileName;
    $thumbPath = $uploadDir . $thumbFileName;
    $mediumPath = $uploadDir . $mediumFileName;
    
    // Debug
    error_log("Original path: " . $originalPath);
    error_log("Temp file: " . $image['tmp_name']);
    
    // Sačuvaj original
    if (!move_uploaded_file($image['tmp_name'], $originalPath)) {
        $error = error_get_last();
        error_log("Failed to move uploaded file: " . ($error['message'] ?? 'Unknown error'));
        error_log("is_uploaded_file: " . (is_uploaded_file($image['tmp_name']) ? 'YES' : 'NO'));
        error_log("file_exists(temp): " . (file_exists($image['tmp_name']) ? 'YES' : 'NO'));
        return false;
    }
    
    // Proveri da li je fajl sačuvan
    if (!file_exists($originalPath)) {
        error_log("Original file not created: " . $originalPath);
        return false;
    }
    
    error_log("Original file saved: " . $originalPath . " (" . filesize($originalPath) . " bytes)");
    
    // Kreiraj thumbnail (300x300)
    $thumbCreated = createImageThumbnail($originalPath, $thumbPath, 300, 300);
    error_log("Thumb created: " . ($thumbCreated ? 'YES' : 'NO') . " - " . $thumbPath);
    
    // Kreiraj medium (800x600)
    $mediumCreated = createImageThumbnail($originalPath, $mediumPath, 800, 600);
    error_log("Medium created: " . ($mediumCreated ? 'YES' : 'NO') . " - " . $mediumPath);
    
    // Relativne putanje za bazu (od assets foldera)
    $originalRelative = '/assets/uploads/ads/' . $adId . '/' . $originalFileName;
    $thumbRelative = $thumbCreated ? '/assets/uploads/ads/' . $adId . '/' . $thumbFileName : null;
    $mediumRelative = $mediumCreated ? '/assets/uploads/ads/' . $adId . '/' . $mediumFileName : null;
    
    // Debug putanja
    error_log("Relative paths - Original: " . $originalRelative);
    error_log("Relative paths - Thumb: " . $thumbRelative);
    error_log("Relative paths - Medium: " . $mediumRelative);
    
    // Sačuvaj u bazi - KORISTITE thumbnail_path i medium_path (kao u vašoj tabeli)
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            INSERT INTO ad_images (
                ad_id, user_id, 
                image_path, thumbnail_path, medium_path, 
                is_main, display_order, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $order = $isMain ? 0 : 1;
        
        error_log("Inserting into DB - ad_id: $adId, user_id: $userId");
        error_log("Image paths - original: $originalRelative, thumb: $thumbRelative, medium: $mediumRelative");
        
        $result = $stmt->execute([
            $adId, 
            $userId, 
            $originalRelative,
            $thumbRelative,
            $mediumRelative,
            $isMain ? 1 : 0, 
            $order
        ]);
        
        if (!$result) {
            error_log("DB insert failed");
            $errorInfo = $stmt->errorInfo();
            error_log("PDO error: " . ($errorInfo[2] ?? 'Unknown error'));
            
            // Obriši fajlove ako je došlo do greške u bazi
            @unlink($originalPath);
            @unlink($thumbPath);
            @unlink($mediumPath);
            
            return false;
        }
        
        $imageId = $db->lastInsertId();
        error_log("Image saved to DB with ID: " . $imageId);
        
        // Vrati array sa svim putanjama
        return [
            'id' => $imageId,
            'original' => $originalRelative,
            'thumbnail' => $thumbRelative,
            'medium' => $mediumRelative,
            'is_main' => $isMain
        ];
        
    } catch (Exception $e) {
        error_log("Save image to DB error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Obriši fajlove ako je došlo do greške u bazi
        @unlink($originalPath);
        @unlink($thumbPath);
        @unlink($mediumPath);
        
        return false;
    }
}

/**
 * Kreira thumbnail sliku
 */
function createImageThumbnail($sourcePath, $destPath, $width, $height = null) {
    if (!function_exists('gd_info')) return false;
    
    list($srcWidth, $srcHeight, $type) = getimagesize($sourcePath);
    
    if (!$srcWidth || !$srcHeight) return false;
    
    // Odredi tip slike
    switch ($type) {
        case IMAGETYPE_JPEG: $source = imagecreatefromjpeg($sourcePath); break;
        case IMAGETYPE_PNG: $source = imagecreatefrompng($sourcePath); break;
        case IMAGETYPE_GIF: $source = imagecreatefromgif($sourcePath); break;
        case IMAGETYPE_WEBP: $source = imagecreatefromwebp($sourcePath); break;
        default: return false;
    }
    
    if (!$source) return false;
    
    // Izračunaj nove dimenzije
    if (!$height) $height = $width;
    
    $srcRatio = $srcWidth / $srcHeight;
    $dstRatio = $width / $height;
    
    if ($dstRatio > $srcRatio) {
        $newHeight = $height;
        $newWidth = $height * $srcRatio;
    } else {
        $newWidth = $width;
        $newHeight = $width / $srcRatio;
    }
    
    // Kreiraj novu sliku
    $thumb = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($thumb, 255, 255, 255);
    imagefill($thumb, 0, 0, $white);
    
    // Centriraj
    $xOffset = ($width - $newWidth) / 2;
    $yOffset = ($height - $newHeight) / 2;
    
    // Kopiraj i smanji
    imagecopyresampled($thumb, $source, $xOffset, $yOffset, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
    
    // Sačuvaj
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($thumb, $destPath, 85); break;
        case IMAGETYPE_PNG: imagepng($thumb, $destPath, 8); break;
        case IMAGETYPE_GIF: imagegif($thumb, $destPath); break;
        case IMAGETYPE_WEBP: imagewebp($thumb, $destPath, 85); break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return true;
}

/**
 * Proveri da li korisnik može da postavi premium oglas
 */
function canMakePremiumAd($userId) {
    $db = getDatabaseConnection();
    
    // Proveri trenutni paket
    $stmt = $db->prepare("SELECT package FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    // Proveri koliko premium oglasa već ima
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE user_id = ? AND is_premium = 1 AND premium_until > NOW()
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    $currentPremium = $result['count'] ?? 0;
    
    // Limit po paketu
    $limits = [
        'free' => 0,
        'silver' => 1,
        'gold' => 5
    ];
    
    $limit = $limits[$user['package']] ?? 0;
    return $currentPremium < $limit;
}

function getAdCountByCategory($categoryId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM ads 
            WHERE (category_id = ? OR subcategory_id = ?) 
            AND status = 'active'
        ");
        $stmt->execute([$categoryId, $categoryId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}



/**
 * Dohvata oglas po ID-u (POJEDNOSTAVLJENA VERZIJA)
 */
        function getAdById($adId) {
            try {
                $db = getDatabaseConnection();
                $stmt = $db->prepare("
                    SELECT * FROM ads 
                    WHERE id = ? 
                    
                ");
                $stmt->execute([$adId]);
                
                $ad = $stmt->fetch();
                
                if (!$ad) {
                    error_log("Ad not found with ID: $adId or status is 'deleted'");
                    return null;
                }
                
                return $ad;
                
            } catch (Exception $e) {
                error_log("Get ad by ID error: " . $e->getMessage());
                return null;
            }
        }
/**
 * Dohvata slike oglasa
 */
function getAdImages($adId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT * FROM ad_images 
            WHERE ad_id = ? 
            ORDER BY is_main DESC, display_order, id
        ");
        $stmt->execute([$adId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get ad images error: " . $e->getMessage());
        return [];
    }
}

/**
 * Povećava broj pregleda oglasa sa višestrukom zaštitom
 */
function incrementAdViews($adId) {
    $db = getDatabaseConnection();
    
    // Dohvati IP adresu (uzimamo u obzir proxy)
    $ipAddress = getRealIpAddress();
    
    // Dohvati User Agent (da razlikujemo različite browser-e)
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Kreiraj jedinstveni identifikator za ovu sesiju
    $sessionId = session_id();
    
    // Proveri da li je ovaj oglas već pregledan u poslednjih 24h
    // od strane iste IP adrese, istog browser-a ili iste sesije
    $stmt = $db->prepare("
        SELECT id FROM ad_views 
        WHERE ad_id = ? 
        AND (
            ip_address = ? 
            OR session_id = ?
            OR user_agent = ?
        )
        AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 1
    ");
    
    $stmt->execute([$adId, $ipAddress, $sessionId, $userAgent]);
    
    if ($stmt->fetch()) {
        return false; // Već je pregledao u poslednjih 24h
    }
    
    try {
        $db->beginTransaction();
        
        // Uvećaj broj pregleda u ads tabeli
        $stmt = $db->prepare("UPDATE ads SET views = views + 1 WHERE id = ?");
        $stmt->execute([$adId]);
        
        // Zabeleži pregled u posebnu tabelu
        $stmt = $db->prepare("
            INSERT INTO ad_views (ad_id, ip_address, session_id, user_agent, viewed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$adId, $ipAddress, $sessionId, $userAgent]);
        
        $db->commit();
        
        // Opciono: postavi i cookie za dodatnu zaštitu (ako browser dozvoljava)
        $cookieKey = 'ad_viewed_' . $adId;
        if (!isset($_COOKIE[$cookieKey])) {
            setcookie($cookieKey, '1', time() + (24 * 3600), '/', '', false, true);
        }
        
        return true;
        
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        error_log("Increment ad views error: " . $e->getMessage());
        return false;
    }
}

/**
 * Dohvata stvarnu IP adresu korisnika (uzimajući u obzir proxy)
 */
function getRealIpAddress() {
    $ipHeaders = [
        'HTTP_CF_CONNECTING_IP',   // Cloudflare
        'HTTP_X_FORWARDED_FOR',    // Proxy
        'HTTP_X_REAL_IP',          // Nginx
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($ipHeaders as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Dohvata korisnika po ID-u
 */
function getUserById($userId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get user by ID error: " . $e->getMessage());
        return null;
    }
}




function getPopularCategories($limit = 8) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.icon, COUNT(a.id) as ad_count
        FROM categories c
        LEFT JOIN ads a ON c.id = a.category_id AND a.status = 'active'
        WHERE c.parent_id IS NULL
        GROUP BY c.id
        ORDER BY ad_count DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}


function getTotalAdCount() {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM ads WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

function getUnreadMessageCount($userId) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}



/**
 * Konvertuje datum u "pre X vremena" format
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'upravo sada';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "pre {$mins} " . ($mins == 1 ? 'minuta' : 'minuta');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "pre {$hours} " . ($hours == 1 ? 'sata' : 'sati');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "pre {$days} " . ($days == 1 ? 'dana' : 'dana');
    } else {
        return date('d.m.Y', $time);
    }
}

/**
 * Pretraga oglasa sa filterima
 */
function searchAds($searchQuery = '', $categoryId = 0, $city = '', $minPrice = 0, $maxPrice = 0, $condition = '', $limit = 20, $offset = 0, $premiumOnly = false) {
    try {
        $db = getDatabaseConnection();
        
        // Početak upita
        $sql = "
            SELECT a.*, 
                   (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id LIMIT 1) as main_image
            FROM ads a
            WHERE a.status = 'active'
        ";
        
        // DODAJ PREMIUM FILTER AKO JE TRAŽENO
        if ($premiumOnly) {
            $sql .= " AND a.is_premium = 1 AND a.premium_until > NOW()";
        }
        
        $params = [];
        $conditions = [];
        
        // Dodaj filtere
        if (!empty($searchQuery)) {
            $conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%$searchQuery%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($categoryId > 0) {
            $conditions[] = "a.category_id = ?";
            $params[] = $categoryId;
        }
        
        if (!empty($city)) {
            $conditions[] = "a.city LIKE ?";
            $params[] = "%$city%";
        }
        
        if ($minPrice > 0) {
            $conditions[] = "a.price >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice > 0) {
            $conditions[] = "a.price <= ?";
            $params[] = $maxPrice;
        }
        
        if (!empty($condition)) {
            $conditions[] = "a.item_condition = ?";
            $params[] = $condition;
        }
        
        // Spoji uslove
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        if (!$premiumOnly) {
            $sql .= " ORDER BY a.is_premium DESC, a.created_at DESC";
        } else {
            $sql .= " ORDER BY a.premium_until DESC, a.created_at DESC";
        }
        
        // Limit i offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Search ads error: " . $e->getMessage());
        return [];
    }
}

/**
 * Broji oglase sa filterima
 */
function countAds($searchQuery = '', $categoryId = 0, $city = '', $minPrice = 0, $maxPrice = 0, $condition = '', $premiumOnly = false) {
    try {
        $db = getDatabaseConnection();
        
        $sql = "SELECT COUNT(*) as count FROM ads WHERE status = 'active'";
        
        // DODAJ PREMIUM FILTER
        if ($premiumOnly) {
            $sql .= " AND is_premium = 1 AND premium_until > NOW()";
        }
        
        
        // Dodaj filtere (isti kao gore)
        if (!empty($searchQuery)) {
            $conditions[] = "(title LIKE ? OR description LIKE ?)";
            $searchTerm = "%$searchQuery%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($categoryId > 0) {
            $conditions[] = "category_id = ?";
            $params[] = $categoryId;
        }
        
        if (!empty($city)) {
            $conditions[] = "city LIKE ?";
            $params[] = "%$city%";
        }
        
        if ($minPrice > 0) {
            $conditions[] = "price >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice > 0) {
            $conditions[] = "price <= ?";
            $params[] = $maxPrice;
        }
        
        if (!empty($condition)) {
            $conditions[] = "item_condition = ?";
            $params[] = $condition;
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Count ads error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Gradi query string za paginaciju
 */
function buildQueryString($excludeParams = []) {
    $queryParams = $_GET;
    
    // Ukloni parametre koje ne želimo
    foreach ($excludeParams as $param) {
        unset($queryParams[$param]);
    }
    
    // Ukloni 'page' parametar (jer je već u URL-u)
    unset($queryParams['page']);
    
    if (empty($queryParams)) {
        return '';
    }
    
    return '&' . http_build_query($queryParams);
}

/**
 * Vraća popularne gradove
 */
        function getPopularCities($limit = 10) {
            try {
                $db = getDatabaseConnection();
                $stmt = $db->prepare("
                    SELECT city as name, COUNT(*) as count
                    FROM ads
                    WHERE status = 'active' 
                    AND city IS NOT NULL 
                    AND city != ''
                    GROUP BY city
                    ORDER BY count DESC
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                return $stmt->fetchAll();
            } catch (Exception $e) {
                error_log("Get popular cities error: " . $e->getMessage());
                return [];
            }
        }

function generateBreadcrumbs($page, $params = []) {
    $breadcrumbs = [];
    
    switch ($page) {
        case 'ad-detail':
            $breadcrumbs = [
                ['text' => 'Početna', 'url' => SITE_URL, 'active' => false],
                ['text' => 'Oglasi', 'url' => '?page=ads', 'active' => false],
                ['text' => 'Detalji oglasa', 'url' => '', 'active' => true]
            ];
            break;
            
        case 'ads':
            $breadcrumbs = [
                ['text' => 'Početna', 'url' => SITE_URL, 'active' => false],
                ['text' => 'Pretraga oglasa', 'url' => '', 'active' => true]
            ];
            break;
            
        case 'profile':
            $breadcrumbs = [
                ['text' => 'Početna', 'url' => SITE_URL, 'active' => false],
                ['text' => 'Moj profil', 'url' => '', 'active' => true]
            ];
            break;
            
        default:
            $breadcrumbs = [
                ['text' => 'Početna', 'url' => SITE_URL, 'active' => false],
                ['text' => ucfirst(str_replace('-', ' ', $page)), 'url' => '', 'active' => true]
            ];
    }
    
    return $breadcrumbs;
}

 function sendEmail($to, $subject, $htmlContent, $plainText = '') {
    // Prvo probaj PHPMailer
    $phpmailerPath = __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    
    if (file_exists($phpmailerPath)) {
        error_log("📧 Using PHPMailer for $to");
        return sendEmailWithPHPMailer($to, $subject, $htmlContent, $plainText);
    } else {
        error_log("📧 PHPMailer not found, using mail() for $to");
        return sendEmailWithMail($to, $subject, $htmlContent, $plainText);
    }
}



/**
 * Šalje email koristeći PHP mail() funkciju sa poboljšanim header-ima
 */
function sendEmailWithMail($to, $subject, $htmlContent, $plainText = '') {
    try {
        ini_set('sendmail_from', SMTP_FROM_EMAIL);
        // Ako nema plain text, ekstraktuj ga iz HTML-a
        if (empty($plainText)) {
            $plainText = strip_tags($htmlContent);
            $plainText = preg_replace('/\s+/', ' ', $plainText);
            $plainText = trim($plainText);
        }
        
        // Za multi-part email (HTML i Plain Text)
        $boundary = md5(uniqid(time()));
        
        // Headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL,
            'Return-Path: ' . SMTP_FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1',
            'X-MS-Exchange-Priority: High',
            'Importance: High'
        ];
        
        // Body
        $body = "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $plainText . "\r\n\r\n";
        
        $body .= "--" . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlContent . "\r\n\r\n";
        
        $body .= "--" . $boundary . "--";
        
        // Pošalji email
        $result = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
        
        if ($result) {
            error_log("✅ Email sent successfully to: $to");
            return true;
        } else {
            error_log("❌ Failed to send email to: $to");
            
            // ✅ ISPRAVLJENO:
            $lastError = error_get_last();
            error_log("Last error: " . ($lastError['message'] ?? 'Unknown error'));
            
            // Fallback - probaj bez multipart
            return sendEmailSimple($to, $subject, $htmlContent);
        }
        
    } catch (Exception $e) {
        error_log("❌ Email sending error: " . $e->getMessage());
        
        // Fallback - probaj simple verziju
        return sendEmailSimple($to, $subject, $htmlContent);
    }
}
/**
 * Simple email sending fallback
 */
function sendEmailSimple($to, $subject, $htmlContent) {
    try {
        ini_set('sendmail_from', SMTP_FROM_EMAIL);
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $result = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlContent, implode("\r\n", $headers));
        
        if ($result) {
            error_log("✅ Email sent (simple method) to: $to");
            return true;
        } else {
            error_log("❌ Simple email method also failed for: $to");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Simple email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Šalje email koristeći PHPMailer
 */
function sendEmailWithPHPMailer($to, $subject, $htmlContent, $plainText = '') {
    try {
        // Učitaj PHPMailer
        require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
        
        // Kreiraj PHPMailer instancu
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;              
        $mail->SMTPAuth   = SMTP_AUTH;              
        $mail->Username   = SMTP_USERNAME;         
        $mail->Password   = SMTP_PASSWORD;         
        $mail->SMTPSecure = SMTP_SECURE;            
        $mail->Port       = SMTP_PORT;              
        
        // UTF-8 encoding
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        
        if (!empty($plainText)) {
            $mail->AltBody = $plainText;
        }
        
        // Pošalji
        $mail->send();
        
        error_log("✅ PHPMailer: Email sent to $to");
        return true;
        
    } catch (Exception $e) {
        error_log("❌ PHPMailer Error: " . $mail->ErrorInfo);
        error_log("❌ Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Generiše HTML template za verifikacioni email
 */
function generateVerificationEmail($userName, $verificationLink) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y'); // OVO JE KLJUČNO!
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikujte Vaš nalog</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
        }
        .welcome-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2d3748;
        }
        .verification-box {
            background-color: #f7fafc;
            border-left: 4px solid #4299e1;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .verification-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .verification-link {
            word-break: break-all;
            background-color: #edf2f7;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            margin: 15px 0;
        }
        .steps {
            margin: 25px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .step-number {
            background-color = #4299e1;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-weight: bold;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-link {
            display: inline-block;
            margin: 0 10px;
            color: #4a5568;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .email-body {
                padding: 20px;
            }
            .verification-button {
                display: block;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="email-logo">🎯 Rasprodaja.rs</div>
            <h1>Verifikacija Email Adrese</h1>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <p class="welcome-text">Zdravo <strong>$userName</strong>,</p>
            
            <p>Hvala Vam što ste se registrovali na <strong>$siteName</strong>! 
            Da biste počeli da koristite sve mogućnosti našeg sajta, 
            potrebno je da verifikujete svoju email adresu.</p>
            
            <div class="verification-box">
                <h3 style="margin-top: 0; color: #2d3748;">Jednostavna verifikacija u 2 koraka:</h3>
                
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div>
                            <strong>Kliknite na dugme ispod</strong><br>
                            To će potvrditi da ste Vi vlasnik ove email adrese.
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div>
                            <strong>Počnite da koristite svoj nalog</strong><br>
                            Nakon verifikacije, možete da postavljate oglase i komunicirate sa drugim korisnicima.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Glavno dugme za verifikaciju -->
            <div style="text-align: center; margin: 30px 0;">
                <a href="$verificationLink" class="verification-button">
                    ✅ VERIFIKUJ SVOJ NALOG
                </a>
            </div>
            
            <p><strong>Ovaj link važi 24 sata.</strong> Ako istekne, možete zatražiti novi link na stranici za prijavu.</p>
            
            <!-- Alternativni link (za slučaj da dugme ne radi) -->
            <p>Ako dugme ne radi, kopirajte ovaj link u pretraživač:</p>
            <div class="verification-link">
                $verificationLink
            </div>
            
            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <small>
                    <strong>Napomena:</strong> Ako se niste registrovali na $siteName, 
                    molimo Vas da ignorisete ovaj email. Vaša email adresa će biti obrisana iz našeg sistema.
                </small>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>$siteName</strong> - Najveća oglasna tabla u Srbiji</p>
            <p>Kupujte i prodajte brzo, lako i sigurno</p>
            
            <div class="social-links">
                <a href="$siteUrl" class="social-link">🌐 Posetite naš sajt</a><br>
                <a href="$siteUrl?page=contact" class="social-link">📧 Kontaktirajte nas</a><br>
                <a href="$siteUrl?page=faq" class="social-link">❔ Pomoć i podrška</a>
            </div>
            
            <p style="font-size: 12px; color: #a0aec0; margin-top: 20px;">
                &copy; $currentYear $siteName. Sva prava zadržana.<br>
                Ovaj email je poslat korisniku koji se registrovao na našem sajtu.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Generiše verifikacioni token
 */
function generateVerificationToken($userId) {
    try {
        $db = getDatabaseConnection();
        
        // Generiši token (32 karaktera)
        $token = bin2hex(random_bytes(16));
        
        // Postavi istek za 24 sata
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Sačuvaj u bazi
        $stmt = $db->prepare("
            UPDATE users 
            SET verification_token = ?, 
                verification_expires = ?,
                is_verified = 0
            WHERE id = ?
        ");
        
        $stmt->execute([$token, $expiresAt, $userId]);
        
        return $token;
        
    } catch (Exception $e) {
        error_log("Generate verification token error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifikuje korisnika pomoću tokena
 */
function verifyUserByToken($token) {
    try {
        $db = getDatabaseConnection();
        
        // Proveri da li token postoji i nije istekao
        $stmt = $db->prepare("
            SELECT id, email, username 
            FROM users 
            WHERE verification_token = ? 
            AND verification_expires > NOW()
            AND is_verified = 0
        ");
        
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Token nije validan ili je istekao'
            ];
        }
        
        // Ažuriraj korisnika kao verifikovanog
        $stmt = $db->prepare("
            UPDATE users 
            SET is_verified = 1,
                verification_token = NULL,
                verification_expires = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$user['id']]);
        
        return [
            'success' => true,
            'message' => 'Email je uspešno verifikovan',
            'user' => $user
        ];
        
    } catch (Exception $e) {
        error_log("Verify user by token error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Došlo je do greške prilikom verifikacije'
        ];
    }
}

/**
 * Generiše password reset email template
 */
function generatePasswordResetEmail($userName, $resetLink) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y');
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetovanje lozinke - Rasprodaja.rs</title>
    <style>
        /* Kopiran iz verification email template za konzistentnost */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
        }
        .welcome-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2d3748;
        }
        .reset-box {
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .reset-link {
            word-break: break-all;
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            margin: 15px 0;
        }
        .security-tips {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        @media (max-width: 600px) {
            .email-body {
                padding: 20px;
            }
            .reset-button {
                display: block;
                margin: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="email-logo">🔐 Rasprodaja.rs</div>
            <h1>Resetovanje Lozinke</h1>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <p class="welcome-text">Zdravo <strong>$userName</strong>,</p>
            
            <p>Primili smo zahtev za resetovanje lozinke za Vaš nalog na <strong>$siteName</strong>.</p>
            
            <div class="reset-box">
                <h3 style="margin-top: 0; color: #2d3748;">Da biste resetovali lozinku:</h3>
                
                <div style="text-align: center; margin: 25px 0;">
                    <a href="$resetLink" class="reset-button">
                        🔐 RESETUJ LOZINKU
                    </a>
                </div>
                
                <p><strong>Ovaj link važi 2 sata.</strong> Nakon isteka, moraćete da zatražite novi link.</p>
            </div>
            
            <!-- Alternativni link -->
            <p>Ako dugme ne radi, kopirajte ovaj link u pretraživač:</p>
            <div class="reset-link">
                $resetLink
            </div>
            
            <!-- Security warning -->
            <div class="warning-box">
                <h4 style="margin-top: 0; color: #856404;">
                    <i class="fas fa-shield-alt" style="margin-right: 8px;"></i> Sigurnosna napomena
                </h4>
                <p style="margin-bottom: 0;">
                    <strong>Ako niste Vi zatražili resetovanje lozinke,</strong> 
                    molimo Vas da ignorišete ovaj email. Vaša lozinka ostaje nepromenjena.
                </p>
            </div>
            
            <!-- Security tips -->
            <div class="security-tips">
                <h5 style="margin-top: 0; color: #856404;">Saveti za sigurnost:</h5>
                <ul style="margin-bottom: 0;">
                    <li>Nikada ne delite svoj reset link sa drugima</li>
                    <li>Koristite jaku, jedinstvenu lozinku</li>
                    <li>Promenite lozinku ako sumnjate da je kompromitovana</li>
                </ul>
            </div>
            
            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <small>
                    <strong>Napomena:</strong> Ovo je automatski generisan email. 
                    Molimo ne odgovarajte na ovu poruku.
                </small>
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>$siteName</strong> - Najveća oglasna tabla u Srbiji</p>
            
            <div style="margin: 15px 0;">
                <a href="$siteUrl?page=contact" style="color: #4a5568; text-decoration: none; margin: 0 10px;">
                    📧 Kontaktirajte nas
                </a><br>
                <a href="$siteUrl?page=faq" style="color: #4a5568; text-decoration: none; margin: 0 10px;">
                    ❔ Pomoć i podrška
                </a>
            </div>
            
            <p style="font-size: 12px; color: #a0aec0; margin-top: 20px;">
                &copy; $currentYear $siteName. Sva prava zadržana.<br>
                Ovaj email je poslat kao odgovor na zahtev za resetovanje lozinke.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Generiše password changed email template
 */
function generatePasswordChangedEmail($userName, $loginLink) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y');
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lozinka promenjena - Rasprodaja.rs</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
            text-align: center;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            margin: 15px 0;
        }
        .security-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div style="font-size: 28px; font-weight: bold; margin-bottom: 10px;">✅</div>
            <h1>Lozinka Uspešno Promenjena</h1>
        </div>
        
        <div class="email-body">
            <p>Zdravo <strong>$userName</strong>,</p>
            
            <p>Obaveštavamo Vas da je lozinka za Vaš nalog na <strong>$siteName</strong> uspešno promenjena.</p>
            
            <div class="success-box">
                <h3 style="margin-top: 0; color: #155724;">
                    <i class="fas fa-check-circle"></i> Promena potvrđena
                </h3>
                <p>Vaša lozinka je ažurirana. Sada se možete prijaviti sa novom lozinkom.</p>
                
                <a href="$loginLink" class="login-button">
                    <i class="fas fa-sign-in-alt"></i> Prijavi se sada
                </a>
            </div>
            
            <div class="security-alert">
                <h4 style="margin-top: 0; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> Važno
                </h4>
                <p>Ako <strong>Vi niste</strong> izvršili ovu promenu lozinke:</p>
                <ol>
                    <li>Odmah prijavite incident našoj podršci</li>
                    <li>Promenite lozinku na svim drugim nalozima gde koristite istu lozinku</li>
                    <li>Aktivirajte 2FA ako je dostupno</li>
                </ol>
            </div>
            
            <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <small>
                    <strong>Napomena:</strong> Ako imate bilo kakvih pitanja, 
                    kontaktirajte našu podršku.
                </small>
            </p>
        </div>
        
        <div class="footer">
            <p><strong>$siteName</strong></p>
            <p style="font-size: 12px; color: #a0aec0; margin-top: 20px;">
                &copy; $currentYear $siteName. Ovaj email je automatski generisan.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}


/**
 * Provera da li jedan korisnik prati drugog
 */
function isFollowing($followerId, $followingId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id FROM followers WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        error_log("Is following error: " . $e->getMessage());
        return false;
    }
}

/**
 * Broj pratilaca korisnika
 */
function getFollowersCount($userId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM followers WHERE following_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Get followers count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Broj korisnika koje korisnik prati
 */
function getFollowingCount($userId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM followers WHERE follower_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Get following count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Provera follow relacije između dva korisnika
 */
function getFollowStatus($currentUserId, $targetUserId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT 
                CASE 
                    WHEN EXISTS(
                        SELECT 1 FROM followers 
                        WHERE follower_id = ? AND following_id = ?
                    ) THEN 1 
                    ELSE 0 
                END as is_following,
                CASE 
                    WHEN EXISTS(
                        SELECT 1 FROM followers 
                        WHERE follower_id = ? AND following_id = ?
                    ) THEN 1 
                    ELSE 0 
                END as is_followed_by
        ");
        
        $stmt->execute([
            $currentUserId, $targetUserId,
            $targetUserId, $currentUserId
        ]);
        
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get follow status error: " . $e->getMessage());
        return ['is_following' => 0, 'is_followed_by' => 0];
    }
}

/**
 * Dohvati follow statistike za korisnika
 */
function getFollowStats($userId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM followers WHERE following_id = ?) as followers_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
        ");
        
        $stmt->execute([$userId, $userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get follow stats error: " . $e->getMessage());
        return ['followers_count' => 0, 'following_count' => 0];
    }
}

/**
 * Kreiraj notifikaciju za follow
 */
function createFollowNotification($followerId, $followingId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT 
                u1.username as follower_username,
                u1.first_name as follower_first_name,
                u1.last_name as follower_last_name,
                u2.id as following_user_id,
                u2.username as following_username
            FROM users u1, users u2
            WHERE u1.id = ? AND u2.id = ?
        ");
        
        $stmt->execute([$followerId, $followingId]);
        $users = $stmt->fetch();
        
        if (!$users) return false;
        
        $followerName = trim($users['follower_first_name'] . ' ' . $users['follower_last_name']);
        if (empty($followerName)) {
            $followerName = $users['follower_username'];
        }
        
        $notificationData = [
            'follower_id' => $followerId,
            'follower_username' => $users['follower_username'],
            'follower_name' => $followerName,
            'action' => 'follow',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $stmt = $db->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, data, is_read, created_at) 
            VALUES (?, 'follow', ?, ?, ?, 0, NOW())
        ");
        
        $title = "🎉 Novi pratilac";
        $message = $followerName . " (@{$users['follower_username']}) je zapratio/la vaš profil.";
        
        return $stmt->execute([
            $followingId,
            $title,
            $message,
            json_encode($notificationData, JSON_UNESCAPED_UNICODE)
        ]);
        
    } catch (Exception $e) {
        error_log("Create follow notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Formatira cenu sa valutom
 */
function formatPriceWithCurrency($price, $currency = 'RSD') {
    $formatted = number_format($price, 0, ',', '.');
    
    if ($currency === 'EUR') {
        return '€ ' . $formatted;
    }
    
    return $formatted . ' RSD';
}

function searchAdsAdvanced($searchQuery = '', $categoryId = 0, $subcategoryId = 0, $city = '', 
                          $minPrice = 0, $maxPrice = 0, $currency = '', $condition = '', 
                          $sort = 'newest', $limit = 20, $offset = 0, $premiumOnly = false) {
    try {
        $db = getDatabaseConnection();
        
        $sql = "SELECT a.* FROM ads a WHERE a.status = 'active'";
        $params = [];
        $conditions = [];
        
        // Premium filter
        if ($premiumOnly) {
            $sql .= " AND a.is_premium = 1 AND a.premium_until > NOW()";
        }
        
        // Pretraga po ključnoj reči
        if (!empty($searchQuery)) {
            $conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%$searchQuery%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // ===== OVO JE KLJUČNO ZA PODKATEGORIJE =====
        if ($subcategoryId > 0) {
            $conditions[] = "a.subcategory_id = ?";
            $params[] = $subcategoryId;
        } elseif ($categoryId > 0) {
            $conditions[] = "a.category_id = ?";
            $params[] = $categoryId;
        }
        // ===== KRAJ =====
        
        // Grad
        if (!empty($city)) {
            $conditions[] = "a.city LIKE ?";
            $params[] = "%$city%";
        }
        
        // Cena i valuta
        if ($minPrice > 0) {
            $conditions[] = "a.price >= ?";
            $params[] = $minPrice;
        }
        if ($maxPrice > 0) {
            $conditions[] = "a.price <= ?";
            $params[] = $maxPrice;
        }
        if (!empty($currency)) {
            $conditions[] = "a.currency = ?";
            $params[] = $currency;
        }
        
        // Stanje
        if (!empty($condition)) {
            $conditions[] = "a.item_condition = ?";
            $params[] = $condition;
        }
        
        // Spoji uslove
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        // SORTIRANJE
        switch ($sort) {
            case 'oldest':
                $sql .= " ORDER BY a.created_at ASC";
                break;
            case 'price_low':
                $sql .= " ORDER BY CAST(a.price AS DECIMAL) ASC";
                break;
            case 'price_high':
                $sql .= " ORDER BY CAST(a.price AS DECIMAL) DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY a.views DESC, a.created_at DESC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY a.is_premium DESC, a.created_at DESC";
                break;
        }
        
        // Limit i offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Search ads advanced error: " . $e->getMessage());
        return [];
    }
}

function countAdsAdvanced($searchQuery = '', $categoryId = 0, $subcategoryId = 0, $city = '', 
                         $minPrice = 0, $maxPrice = 0, $currency = '', $condition = '', $premiumOnly = false) {
    try {
        $db = getDatabaseConnection();
        
        $sql = "SELECT COUNT(*) as count FROM ads a WHERE a.status = 'active'";
        $params = [];
        $conditions = [];
        
        if ($premiumOnly) {
            $sql .= " AND a.is_premium = 1 AND a.premium_until > NOW()";
        }
        
        if (!empty($searchQuery)) {
            $conditions[] = "(a.title LIKE ? OR a.description LIKE ?)";
            $searchTerm = "%$searchQuery%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // ===== OVO JE KLJUČNO ZA PODKATEGORIJE =====
        if ($subcategoryId > 0) {
            $conditions[] = "a.subcategory_id = ?";
            $params[] = $subcategoryId;
        } elseif ($categoryId > 0) {
            $conditions[] = "a.category_id = ?";
            $params[] = $categoryId;
        }
        // ===== KRAJ =====
        
        if (!empty($city)) {
            $conditions[] = "a.city LIKE ?";
            $params[] = "%$city%";
        }
        
        if ($minPrice > 0) {
            $conditions[] = "a.price >= ?";
            $params[] = $minPrice;
        }
        if ($maxPrice > 0) {
            $conditions[] = "a.price <= ?";
            $params[] = $maxPrice;
        }
        if (!empty($currency)) {
            $conditions[] = "a.currency = ?";
            $params[] = $currency;
        }
        if (!empty($condition)) {
            $conditions[] = "a.item_condition = ?";
            $params[] = $condition;
        }
        
        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return $result['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Count ads advanced error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Dohvata podkategorije za glavnu kategoriju
 */
function getSubcategories($parentId) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT * FROM categories 
            WHERE parent_id = ? 
            ORDER BY sort_order, name
        ");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get subcategories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Dohvata popularne podkategorije
 */
function getPopularSubcategories($limit = 12) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM ads WHERE subcategory_id = c.id AND status = 'active') as ad_count
            FROM categories c
            WHERE c.parent_id IS NOT NULL AND c.parent_id != 0
            ORDER BY ad_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get popular subcategories error: " . $e->getMessage());
        return [];
    }
}
/**
 * Grafi query string za napredne filtere
 */
function buildQueryStringAdvanced($filters, $excludeParams = []) {
    $params = [];
    
    foreach ($filters as $key => $value) {
        if (in_array($key, $excludeParams)) continue;
        if ($value !== null && $value !== '' && $value !== 0) {
            $params[$key] = $value;
        }
    }
    
    if (empty($params)) {
        return '';
    }
    
    return '&' . http_build_query($params);
}

/**
 * Dohvata sve gradove za dropdown
 */
function getCitiesList() {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->query("
            SELECT id, name, postal_code 
            FROM cities 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get cities list error: " . $e->getMessage());
        return [];
    }
}

/**
 * Dohvata naziv grada po ID-u
 */
function getCityNameById($id) {
    if (!$id) return '';
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT name FROM cities WHERE id = ?");
        $stmt->execute([$id]);
        $city = $stmt->fetch();
        return $city ? $city['name'] : '';
    } catch (Exception $e) {
        error_log("Get city name error: " . $e->getMessage());
        return '';
    }
}

function normalizePhoneNumber($phone) {
    // Ukloni sve što nije broj ili plus
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Ako već ima +381, vrati kako jeste
    if (preg_match('/^\+381/', $phone)) {
        return $phone;
    }
    
    // Ako počinje sa 381 (bez plusa), dodaj plus
    if (preg_match('/^381/', $phone)) {
        return '+' . $phone;
    }
    
    // Ako počinje sa 06 (domaći mobilni), zameni 06 sa +3816
    if (preg_match('/^06/', $phone)) {
        $phone = preg_replace('/^06/', '+3816', $phone);
        return $phone;
    }
    
    // Ako počinje sa 6 (bez nule), dodaj +381 ispred
    if (preg_match('/^6/', $phone)) {
        return '+381' . $phone;
    }
    
    // Ako počinje sa 0 (fiksni), zameni 0 sa +381
    if (preg_match('/^0/', $phone)) {
        $phone = preg_replace('/^0/', '+381', $phone);
        return $phone;
    }
    
    // Ako je ostalo nešto drugo, vrati original
    return $phone;
}


// ============================================
// SEO FUNKCIJE ZA SLUGOVE (POŠTO VEĆ IMATE SLUGOVE U BAZI)
// ============================================





/**
 * Generiše lep SEO URL za kategoriju (bez ID)
 * Primer: /ads/category/auto
 */
function getSeoCategoryUrl($category) {
    if (is_array($category)) {
        $slug = $category['slug'];
    } else {
        $slug = getCategorySlug($category);
    }
    return '/ads/category/' . $slug;
}

/**
 * Generiše lep SEO URL za podkategoriju (bez ID)
 * Primer: /ads/category/auto/limuzine
 */
function getSeoSubcategoryUrl($mainCategory, $subcategory) {
    $mainSlug = is_array($mainCategory) ? $mainCategory['slug'] : getCategorySlug($mainCategory);
    $subSlug = is_array($subcategory) ? $subcategory['slug'] : getCategorySlug($subcategory);
    return '/ads/category/' . $mainSlug . '/' . $subSlug;
}

/**
 * Generiše lep SEO URL za oglas (samo slug, bez ID)
 * Primer: /ad/nova-ponuda-123
 */
function getSeoAdUrl($ad) {
    if (is_array($ad)) {
        $slug = $ad['slug'];
        return '/ad/' . $slug;
    }
    return '/ad/' . $ad;
}





// ============================================
// EMAIL FUNKCIJE ZA PAKETE I TRANSAKCIJE
// ============================================

/**
 * Generiše HTML email sa podacima za uplatu paketa
 */
function generatePackagePaymentEmail($userName, $userEmail, $packageName, $amount, $period, $referenceNumber, $bankDetails) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y');
    
    $periodText = ($period === 'yearly') ? 'Godišnje' : 'Mesečno';
    $formattedAmount = number_format($amount, 0, ',', '.') . ' RSD';
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uplata za {$packageName} paket</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .email-body {
            padding: 30px;
        }
        .welcome-text {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2d3748;
        }
        .package-details {
            background-color: #f7fafc;
            border-left: 4px solid #4299e1;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .bank-details {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box {
            background-color: #e3f2fd;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #4a5568;
        }
        .info-value {
            color: #2d3748;
            font-weight: 500;
        }
        .reference-number {
            background-color: #edf2f7;
            padding: 12px 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            color: #2d3748;
            letter-spacing: 1px;
        }
        .steps {
            margin: 20px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .step-number {
            background-color: #4299e1;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            margin: 10px 0;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        @media (max-width: 600px) {
            .email-body {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <div class="email-logo">🎯 {$siteName}</div>
            <h1>Uplata za {$packageName} Paket</h1>
        </div>
        
        <!-- Body -->
        <div class="email-body">
            <p class="welcome-text">Poštovani/a <strong>{$userName}</strong>,</p>
            
            <p>Primili smo Vaš zahtev za nadogradnju na <strong>{$packageName}</strong> paket.</p>
            
            <!-- Detalji paketa -->
            <div class="package-details">
                <h3 style="margin-top: 0; color: #2d3748;">📦 Detalji paketa</h3>
                <div class="info-row">
                    <span class="info-label">Paket:</span>
                    <span class="info-value"><strong>{$packageName}</strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <span class="info-value">{$periodText}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Iznos:</span>
                    <span class="info-value" style="font-size: 18px; color: #28a745;">
                        <strong>{$formattedAmount}</strong>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Poziv na broj:</span>
                    <span class="info-value">
                        <code style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 16px;">
                            <strong>{$referenceNumber}</strong>
                        </code>
                    </span>
                </div>
            </div>
            
            <!-- Bankovni podaci -->
            <div class="bank-details">
                <h3 style="margin-top: 0; color: #856404;">🏦 Podaci za uplatu</h3>
                <div class="info-row">
                    <span class="info-label">Primaoc:</span>
                    <span class="info-value">{$bankDetails['recipient']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">PIB:</span>
                    <span class="info-value">{$bankDetails['pib']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Matični broj:</span>
                    <span class="info-value">{$bankDetails['maticni']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Banka:</span>
                    <span class="info-value">{$bankDetails['bank']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Račun:</span>
                    <span class="info-value">
                        <code style="font-size: 16px; font-weight: bold;">{$bankDetails['account']}</code>
                    </span>
                </div>
            </div>
            
            <!-- Poziv na broj - ISTAKNUTO -->
            <div style="background: #e8f5e9; padding: 15px; border-radius: 4px; margin: 20px 0; text-align: center; border: 2px dashed #4caf50;">
                <h4 style="margin: 0 0 8px 0; color: #2e7d32;">📌 VAŽNO: Poziv na broj</h4>
                <div class="reference-number">{$referenceNumber}</div>
                <p style="margin: 8px 0 0 0; color: #555; font-size: 14px;">
                    <strong>Obavezno navedite ovaj broj prilikom uplate!</strong>
                </p>
            </div>
            
            <!-- Uputstvo -->
            <h3 style="margin-top: 25px;">📋 Uputstvo za uplatu:</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Izvršite uplatu</strong> na gore navedeni račun<br>
                        <span style="font-size: 14px; color: #666;">Iznos: <strong>{$formattedAmount}</strong></span>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div>
                        <strong>U polje "Poziv na broj"</strong> upišite: <br>
                        <code style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-weight: bold;">{$referenceNumber}</code>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Pošaljite potvrdu</strong> (opciono) na email: <br>
                        <a href="mailto:kontakt@rasprodaja.rs" style="color: #4299e1;">kontakt@rasprodaja.rs</a>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Paket se aktivira</strong> u roku od 24h od prijema uplate.<br>
                        <span style="font-size: 14px; color: #666;">Dobićete potvrdu na email.</span>
                    </div>
                </div>
            </div>
            
            <!-- Važna napomena -->
            <div class="warning-box">
                <h4 style="margin-top: 0; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i> Važna napomena
                </h4>
                <ul style="margin-bottom: 0;">
                    <li>Uplata mora biti izvršena u roku od <strong>48 sati</strong></li>
                    <li>Nakon isteka roka, zahtev se poništava</li>
                    <li>Ako imate pitanja, kontaktirajte nas na <a href="mailto:kontakt@rasprodaja.rs">kontakt@rasprodaja.rs</a></li>
                </ul>
            </div>
            
            <!-- Linkovi -->
            <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="{$siteUrl}/packages" class="button">📦 Pregled paketa</a>
                <br><br>
                <a href="{$siteUrl}/dashboard" style="color: #718096; text-decoration: none; font-size: 14px;">
                    <i class="fas fa-arrow-right"></i> Idi na kontrolnu tablu
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>{$siteName}</strong> - Najveća oglasna tabla u Srbiji</p>
            <p style="font-size: 12px; color: #a0aec0; margin-top: 10px;">
                &copy; {$currentYear} {$siteName}. Sva prava zadržana.<br>
                Ovaj email je poslat kao potvrda zahteva za nadogradnju paketa.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Šalje email sa podacima za uplatu paketa
 */
function sendPackagePaymentEmail($userId, $packageId, $amount, $period, $referenceNumber) {
    try {
        // Dohvati podatke o korisniku
        $user = getUserById($userId);
        if (!$user) {
            error_log("User not found for package payment email: $userId");
            return false;
        }
        
        // Dohvati podatke o paketu
        $package = getPackageById($packageId);
        if (!$package) {
            error_log("Package not found for payment email: $packageId");
            return false;
        }
        
        // Pripremi podatke
        $userName = getUserDisplayName($userId);
        $userEmail = $user['email'];
        $packageName = $package['name'];
        
        // Bankovni podaci (iz tvojih konstanti ili fiksni)
        $bankDetails = [
            'recipient' => 'Rasprodaja DOO Novi Pazar',
            'pib' => '115816367',
            'maticni' => '22318454',
            'bank' => 'RAIFFEISEN BANKA',
            'account' => '265-1100310108783-08',
        ];
        
        // Generiši HTML email
        $htmlContent = generatePackagePaymentEmail(
            $userName,
            $userEmail,
            $packageName,
            $amount,
            $period,
            $referenceNumber,
            $bankDetails
        );
        
        // Subject
        $subject = "Uplata za {$packageName} paket - Poziv na broj: {$referenceNumber}";
        
        // Pošalji email
        $result = sendEmail($userEmail, $subject, $htmlContent);
        
        if ($result) {
            error_log("Package payment email sent to: $userEmail for package: $packageName");
            
            // Loguj aktivnost
            logUserActivity($userId, 'payment_email_sent', [
                'package_id' => $packageId,
                'package_name' => $packageName,
                'amount' => $amount,
                'period' => $period,
                'reference_number' => $referenceNumber,
                'email' => $userEmail
            ]);
            
            return true;
        } else {
            error_log("Failed to send package payment email to: $userEmail");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Send package payment email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generiše HTML email za potvrdu aktivacije paketa
 */
function generatePackageActivationEmail($userName, $packageName, $expiresAt) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y');
    
    $formattedExpiry = date('d.m.Y', strtotime($expiresAt));
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket aktiviran - {$siteName}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px;
        }
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }
        .package-features {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div style="font-size: 48px; margin-bottom: 10px;">🎉</div>
            <h1>Paket aktiviran!</h1>
        </div>
        
        <div class="email-body">
            <p>Poštovani/a <strong>{$userName}</strong>,</p>
            
            <div class="success-box">
                <h3 style="margin-top: 0; color: #155724;">
                    ✅ Vaš {$packageName} paket je aktiviran!
                </h3>
                <p>Važi do: <strong>{$formattedExpiry}</strong></p>
            </div>
            
            <p>Sada imate pristup svim pogodnostima <strong>{$packageName}</strong> paketa.</p>
            
            <div style="text-align: center; margin-top: 25px;">
                <a href="{$siteUrl}/dashboard" class="button">
                    🚀 Idi na kontrolnu tablu
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>{$siteName}</strong></p>
            <p style="font-size: 12px; color: #a0aec0;">
                &copy; {$currentYear} {$siteName}. Sva prava zadržana.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Šalje email za potvrdu aktivacije paketa
 */
function sendPackageActivationEmail($userId, $packageId, $expiresAt) {
    try {
        $user = getUserById($userId);
        if (!$user) return false;
        
        $package = getPackageById($packageId);
        if (!$package) return false;
        
        $userName = getUserDisplayName($userId);
        $userEmail = $user['email'];
        $packageName = $package['name'];
        
        $htmlContent = generatePackageActivationEmail($userName, $packageName, $expiresAt);
        $subject = "✅ {$packageName} paket aktiviran!";
        
        $result = sendEmail($userEmail, $subject, $htmlContent);
        
        if ($result) {
            error_log("Package activation email sent to: $userEmail");
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Send package activation email error: " . $e->getMessage());
        return false;
    }
}







// ============================================
// TCPDF - GENERISANJE PDF DOKUMENATA
// ============================================

/**
 * Generiše predračun PDF (KLASIČNI TCPDF)
 * 
 * @param array $user Podaci o korisniku (username, email, city)
 * @param array $package Podaci o paketu (name, price_monthly)
 * @param array $transaction Podaci o transakciji (id, amount, period, created_at, updated_at, expires_at)
 * @param string $referenceNumber Poziv na broj
 * @return string|bool Putanja do PDF fajla ili false ako ne uspe
 */
function generatePredracunPDF($user, $package, $transaction, $referenceNumber) {
    // Putanja do klasičnog TCPDF-a
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
    
    if (!file_exists($tcpdfPath)) {
        error_log("TCPDF not found at: " . $tcpdfPath);
        return false;
    }
    
    require_once $tcpdfPath;
    
    // Kreiraj PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // ⚠️ POSTAVI FONT NA dejavusans
    $pdf->SetFont('dejavusans', '', 12); // DODAJ OVO!
    
    // Postavke
    $pdf->SetCreator('Rasprodaja.rs');
    $pdf->SetAuthor('Rasprodaja.rs');
    $pdf->SetTitle('Predračun ' . $transaction['id']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Dodaj stranicu
    $pdf->AddPage();
    
    // Postavi marginu
    $pdf->SetMargins(15, 15, 15);
    
    // ============================================
    // HTML SADRŽAJ ZA PREDRAČUN
    // ============================================
    $html = '
    <style>
        body { font-family: helvetica, sans-serif; }
        .header { text-align: center; padding-bottom: 15px; border-bottom: 3px solid #4f46e5; }
        .header h1 { color: #4f46e5; font-size: 24px; margin: 0; }
        .header .subtitle { color: #64748b; font-size: 12px; }
        .company-info { text-align: right; font-size: 10px; color: #64748b; margin-top: 5px; }
        .document-title { 
            background: #4f46e5; 
            color: white; 
            padding: 6px 15px; 
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
        }
        .info-row { margin: 4px 0; }
        .info-label { font-weight: bold; color: #475569; }
        .info-value { color: #1e293b; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th { 
            background: #4f46e5; 
            color: white; 
            padding: 8px; 
            text-align: left;
            font-size: 12px;
        }
        .table td { 
            padding: 8px; 
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .table .total-row td { 
            border-top: 2px solid #4f46e5; 
            font-weight: bold;
            padding: 10px 8px;
            font-size: 14px;
        }
        .bank-details { 
            background: #f8fafc; 
            padding: 12px; 
            margin: 10px 0;
            border-left: 4px solid #4f46e5;
        }
        .bank-details p { margin: 3px 0; font-size: 11px; }
        .reference-box {
            background: #fef9c3;
            padding: 10px;
            border: 2px dashed #f59e0b;
            text-align: center;
            margin: 10px 0;
        }
        .reference-box code {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            background: white;
            padding: 4px 10px;
        }
        .footer-note {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .signature {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
    
    <div class="header">
        <h1>RASPRODAJA.RS</h1>
        <div class="subtitle">Najveća oglasna tabla u Srbiji</div>
        <div class="company-info">
            Rasprodaja DOO Novi Pazar<br>
            PIB: 115816367 | Matični broj: 22318454
        </div>
    </div>
    
    <div class="document-title">PREDRAČUN</div>
    
    <div class="info-row">
        <span class="info-label">Broj predračuna:</span>
        <span class="info-value">PR-' . date('Y') . '-' . str_pad($transaction['id'], 4, '0', STR_PAD_LEFT) . '</span>
    </div>
    <div class="info-row">
        <span class="info-label">Datum izdavanja:</span>
        <span class="info-value">' . date('d.m.Y') . '</span>
    </div>
    <div class="info-row">
        <span class="info-label">Rok plaćanja:</span>
        <span class="info-value">' . date('d.m.Y', strtotime('+3 days')) . '</span>
    </div>
    
    <br>
    
    <h3>Podaci o kupcu:</h3>
    <p style="font-size: 12px;">
        Ime: <strong>' . htmlspecialchars($user['first_name']) . '</strong> <strong>' . htmlspecialchars($user['last_name']) . '</strong><br>
        Korisničko ime: <strong>' . htmlspecialchars($user['username']) . '</strong><br>
        Email: ' . htmlspecialchars($user['email']) . '<br>
        Grad: ' . (!empty($user['city']) ? htmlspecialchars($user['city']) : 'Beograd') . '
    </p>
    
    <br>
    
    <h3>Stavke predračuna:</h3>
    <table class="table">
        <thead>
            <tr>
                <th style="width:50%;">Opis usluge</th>
                <th style="width:15%;">Količina</th>
                <th style="width:20%;">Cena</th>
                <th style="width:15%;">Ukupno</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>' . htmlspecialchars($package['name']) . ' paket</strong><br>
                    <span style="font-size: 10px; color: #64748b;">
                        ' . ($transaction['period'] === 'yearly' ? 'Godišnja pretplata' : 'Mesečna pretplata') . '
                    </span>
                </td>
                <td>1</td>
                <td>' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
                <td>' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">UKUPNO (sa PDV):</td>
                <td style="color: #4f46e5; font-size: 16px;">' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
            </tr>
        </tfoot>
    </table>
    
    <div style="font-size: 10px; color: #64748b; margin-top: -5px; margin-bottom: 10px;">
        * Cena uključuje PDV (19%)
    </div>
    
    <div class="bank-details">
        <h4 style="color: #4f46e5; margin: 0 0 8px 0;">Podaci za uplatu:</h4>
        <p><strong>Primaoc:</strong> Rasprodaja DOO Novi Pazar</p>
        <p><strong>Banka:</strong> RAIFFEISEN BANKA</p>
        <p><strong>Račun:</strong> <b>265-1100310108783-08</b></p>
        <p><strong>PIB:</strong> 115816367</p>
    </div>
    
    <div class="reference-box">
        <p style="margin: 0; font-weight: bold; color: #854d0e;">POZIV NA BROJ:</p>
        <code>' . $referenceNumber . '</code>
        <p style="margin: 5px 0 0 0; font-size: 11px; color: #854d0e;">
            <strong>Obavezno navedite poziv na broj prilikom uplate!</strong>
        </p>
    </div>
    
    <div style="background: #f0f9ff; padding: 10px; margin: 10px 0; border: 1px solid #bae6fd;">
        <p style="margin: 0; font-size: 11px; color: #0369a1;">
            <strong>Napomena:</strong> Nakon izvršene uplate, paket se aktivira u roku od 24h.
        </p>
    </div>
    
    <div class="signature">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <p><strong>Ovlašćeno lice:</strong></p>
                <p style="margin-top: 25px;">Rasprodaja DOO </p>
                <p style="font-size: 10px; color: #64748b;">(Ovaj dokument je važeći bez potpisa)</p>
            </div>
            <div style="text-align: right;">
                <p><strong>Datum:</strong></p>
                <p style="margin-top: 25px;">' . date('d.m.Y') . '</p>
            </div>
        </div>
    </div>
    
    <div class="footer-note">
        Predračun je generisan automatski. Molimo vas da sačuvate ovaj dokument za vašu evidenciju.<br>
        © ' . date('Y') . ' Rasprodaja.rs - Sva prava zadržana.
    </div>
    ';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Sačuvaj PDF
    $filename = 'predracun_' . $transaction['id'] . '_' . date('Ymd') . '.pdf';
    $filepath = __DIR__ . '/../assets/uploads/predracuni/' . $filename;
    
    // Kreiraj folder ako ne postoji
    $dir = dirname($filepath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $pdf->Output($filepath, 'F');
    
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    error_log("Failed to save PDF: " . $filepath);
    return false;
}

/**
 * Generiše račun PDF (KLASIČNI TCPDF)
 * 
 * @param array $user Podaci o korisniku (username, email, city)
 * @param array $package Podaci o paketu (name, price_monthly)
 * @param array $transaction Podaci o transakciji (id, amount, period, created_at, updated_at, expires_at)
 * @param string $referenceNumber Poziv na broj
 * @return string|bool Putanja do PDF fajla ili false ako ne uspe
 */
function generateRacunPDF($user, $package, $transaction, $referenceNumber) {
    // Putanja do klasičnog TCPDF-a
    $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
    
    if (!file_exists($tcpdfPath)) {
        error_log("TCPDF not found at: " . $tcpdfPath);
        return false;
    }
    
    require_once $tcpdfPath;
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // ⚠️ POSTAVI FONT NA dejavusans
    $pdf->SetFont('dejavusans', '', 12); 
    
    $pdf->SetCreator('Rasprodaja.rs');
    $pdf->SetAuthor('Rasprodaja.rs');
    $pdf->SetTitle('Račun ' . $transaction['id']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    
    $expiresAt = date('d.m.Y', strtotime($transaction['expires_at']));
    $activatedAt = date('d.m.Y', strtotime($transaction['updated_at'] ?? $transaction['created_at']));
    
    // ============================================
    // HTML SADRŽAJ ZA RAČUN
    // ============================================
    $html = '
    <style>
        body { font-family: helvetica, sans-serif; }
        .header { text-align: center; padding-bottom: 15px; border-bottom: 3px solid #10b981; }
        .header h1 { color: #10b981; font-size: 24px; margin: 0; }
        .header .subtitle { color: #64748b; font-size: 12px; }
        .company-info { text-align: right; font-size: 10px; color: #64748b; margin-top: 5px; }
        .document-title { 
            background: #10b981; 
            color: white; 
            padding: 6px 15px; 
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
        }
        .status-paid {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            padding: 4px 16px;
            font-weight: bold;
            font-size: 13px;
            border: 1px solid #10b981;
            text-align: center;
            margin: 5px 0;
        }
        .info-row { margin: 4px 0; }
        .info-label { font-weight: bold; color: #475569; }
        .info-value { color: #1e293b; }
        .table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .table th { 
            background: #10b981; 
            color: white; 
            padding: 8px; 
            text-align: left;
            font-size: 12px;
        }
        .table td { 
            padding: 8px; 
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .table .total-row td { 
            border-top: 2px solid #10b981; 
            font-weight: bold;
            padding: 10px 8px;
            font-size: 14px;
        }
        .activation-details {
            background: #f0fdf4;
            padding: 12px;
            margin: 10px 0;
            border-left: 4px solid #10b981;
        }
        .activation-details p { margin: 3px 0; font-size: 11px; }
        .thank-you {
            text-align: center;
            font-size: 18px;
            color: #10b981;
            font-weight: bold;
            margin: 15px 0;
        }
        .footer-note {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
    
    <div class="header">
        <h1>RASPRODAJA.RS</h1>
        <div class="subtitle">Najveća oglasna tabla u Srbiji</div>
        <div class="company-info">
            Rasprodaja DOO Novi Pazar<br>
            PIB: 115816367 | Matični broj: 22318454
        </div>
    </div>
    
    <div class="document-title">RAČUN / POTVRDA O AKTIVACIJI</div>
    
    <div style="text-align: center;">
        <span class="status-paid">PLAĆENO / AKTIVIRANO</span>
    </div>
    
    <div class="info-row">
        <span class="info-label">Broj računa:</span>
        <span class="info-value">R-' . date('Y') . '-' . str_pad($transaction['id'], 4, '0', STR_PAD_LEFT) . '</span>
    </div>
    <div class="info-row">
        <span class="info-label">Datum izdavanja:</span>
        <span class="info-value">' . date('d.m.Y') . '</span>
    </div>
    <div class="info-row">
        <span class="info-label">Datum uplate:</span>
        <span class="info-value">' . $activatedAt . '</span>
    </div>
    
    <br>
    
    <h3>Podaci o kupcu:</h3>
    <p style="font-size: 12px;">
        Ime: <strong>' . htmlspecialchars($user['first_name']) . '</strong> <strong>' . htmlspecialchars($user['last_name']) . '</strong><br>
        Korisničko ime: <strong>' . htmlspecialchars($user['username']) . '</strong><br>
        Email: ' . htmlspecialchars($user['email']) . '<br>
        Grad: ' . (!empty($user['city']) ? htmlspecialchars($user['city']) : 'Beograd') . '
    </p>
    
    <br>
    
    <h3>Stavke računa:</h3>
    <table class="table">
        <thead>
            <tr>
                <th style="width:50%;">Opis usluge</th>
                <th style="width:15%;">Količina</th>
                <th style="width:20%;">Cena</th>
                <th style="width:15%;">Ukupno</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>' . htmlspecialchars($package['name']) . ' paket</strong><br>
                    <span style="font-size: 10px; color: #64748b;">
                        ' . ($transaction['period'] === 'yearly' ? 'Godišnja pretplata' : 'Mesečna pretplata') . '
                    </span>
                </td>
                <td>1</td>
                <td>' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
                <td>' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">UKUPNO (sa PDV):</td>
                <td style="color: #10b981; font-size: 16px;">' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</td>
            </tr>
        </tfoot>
    </table>
    
    <div style="font-size: 10px; color: #64748b; margin-top: -5px; margin-bottom: 10px;">
        * Cena uključuje PDV (19%)
    </div>
    
    <div class="activation-details">
        <h4 style="color: #10b981; margin: 0 0 8px 0;"> Detalji aktivacije:</h4>
        <p><strong> Paket:</strong> ' . htmlspecialchars($package['name']) . '</p>
        <p><strong> Datum aktivacije:</strong> ' . $activatedAt . '</p>
        <p><strong> Datum isteka:</strong> ' . $expiresAt . '</p>
        <p><strong> Period:</strong> ' . ($transaction['period'] === 'yearly' ? 'Godišnje' : 'Mesečno') . '</p>
    </div>
    
    <div class="thank-you">
        Hvala na poverenju!
    </div>
    
    <div class="footer-note">
        Račun je generisan automatski. Molimo vas da sačuvate ovaj dokument za vašu evidenciju.<br>
        © ' . date('Y') . ' Rasprodaja.rs - Sva prava zadržana.
    </div>
    ';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    $filename = 'racun_' . $transaction['id'] . '_' . date('Ymd') . '.pdf';
    $filepath = __DIR__ . '/../assets/uploads/racuni/' . $filename;
    
    $dir = dirname($filepath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $pdf->Output($filepath, 'F');
    
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    error_log("Failed to save PDF: " . $filepath);
    return false;
}

// ============================================
// FUNKCIJE ZA SLANJE EMAILA SA PDF ATTACHMENT-OM
// ============================================

/**
 * Šalje predračun na email
 * 
 * @param array $user Podaci o korisniku
 * @param array $package Podaci o paketu
 * @param array $transaction Podaci o transakciji
 * @param string $referenceNumber Poziv na broj
 * @return bool True ako je email poslat, false ako nije
 */
function sendPredracunEmail($user, $package, $transaction, $referenceNumber) {
    try {
        // Generiši PDF
        $pdfPath = generatePredracunPDF($user, $package, $transaction, $referenceNumber);
        
        if (!$pdfPath || !file_exists($pdfPath)) {
            error_log("Failed to generate predračun PDF");
            return false;
        }
        
        // HTML email sadržaj
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
        $siteName = 'Rasprodaja.rs';
        $logoUrl = $siteUrl . '/assets/images/logo/logo.png';
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #4f46e5; }
                .header h1 { color: #4f46e5; margin: 0; }
                .content { padding: 20px 0; }
                .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #4f46e5; }
                .reference { background: #fef9c3; padding: 10px; border-radius: 4px; border: 2px dashed #f59e0b; text-align: center; font-size: 18px; font-weight: bold; }
                .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
                .button { display: inline-block; background: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . $logoUrl . '" alt="' . $siteName . '" style="max-width: 180px; height: auto;">
                    <h1>Predračun</h1>
                </div>
                <div class="content">
                    <h3>Poštovani/a ' . htmlspecialchars($user['username']) . ',</h3>
                    
                    <p>U prilogu vam dostavljamo <strong>predračun</strong> za odabrani paket.</p>
                    
                    <div class="info-box">
                        <p><strong>Paket:</strong> ' . htmlspecialchars($package['name']) . '</p>
                        <p><strong>Period:</strong> ' . ($transaction['period'] === 'yearly' ? 'Godišnje' : 'Mesečno') . '</p>
                        <p><strong>Iznos:</strong> ' . number_format($transaction['amount'], 0, ',', '.') . ' RSD</p>
                        <p><strong>Rok plaćanja:</strong> ' . date('d.m.Y', strtotime('+3 days')) . '</p>
                    </div>
                    
                    <div class="reference">
                        Poziv na broj: ' . $referenceNumber . '
                    </div>
                    
                    <p><strong>Podaci za uplatu:</strong></p>
                    <div class="info-box">
                        <p><strong>Primaoc:</strong> Rasprodaja DOO Novi Pazar</p>
                        <p><strong>Banka:</strong> RAIFFEISEN BANKA</p>
                        <p><strong>Račun:</strong> 265-1100310108783-08</p>
                        <p><strong>PIB:</strong> 115816367</p>
                    </div>
                    
                    <p style="color: #ef4444; font-weight: bold;">
                        ⚠️ Obavezno navedite POZIV NA BROJ prilikom uplate!
                    </p>
                    
                    <p>
                        Nakon izvršene uplate, paket će biti aktiviran u roku od 24h.
                        Dobijate potvrdu na email.
                    </p>
                    
                    <p style="text-align: center;">
                        <a href="' . $siteUrl . '/packages" class="button">📦 Pregled paketa</a>
                    </p>
                    
                    <p>Ako imate bilo kakvih pitanja, kontaktirajte nas na <strong>kontakt@rasprodaja.rs</strong></p>
                </div>
                <div class="footer">
                    <p>' . $siteName . ' - Najveća oglasna tabla u Srbiji</p>
                    <p>&copy; ' . date('Y') . ' ' . $siteName . ' - Sva prava zadržana.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $subject = "📄 Predračun za " . $package['name'] . " paket";
        
        // Pošalji email sa attachmentom
        return sendEmailWithAttachment(
            $user['email'],
            $subject,
            $htmlContent,
            $pdfPath,
            basename($pdfPath)
        );
        
    } catch (Exception $e) {
        error_log("Send predračun email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Šalje račun na email
 * 
 * @param array $user Podaci o korisniku
 * @param array $package Podaci o paketu
 * @param array $transaction Podaci o transakciji
 * @param string $referenceNumber Poziv na broj
 * @return bool True ako je email poslat, false ako nije
 */
function sendRacunEmail($user, $package, $transaction, $referenceNumber) {
    try {
        // Generiši PDF
        $pdfPath = generateRacunPDF($user, $package, $transaction, $referenceNumber);
        
        if (!$pdfPath || !file_exists($pdfPath)) {
            error_log("Failed to generate račun PDF");
            return false;
        }
        
        // HTML email sadržaj
        $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
        $siteName = 'Rasprodaja.rs';
        $logoUrl = $siteUrl . '/assets/images/logo/logo.png';
        $expiresAt = date('d.m.Y', strtotime($transaction['expires_at']));
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #10b981; }
                .header h1 { color: #10b981; margin: 0; }
                .content { padding: 20px 0; }
                .success-box { background: #d1fae5; padding: 15px; border-radius: 8px; text-align: center; margin: 15px 0; border: 1px solid #10b981; }
                .success-box h2 { color: #065f46; margin: 0; }
                .info-box { background: #f8fafc; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #10b981; }
                .footer { text-align: center; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; }
                .button { display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . $logoUrl . '" alt="' . $siteName . '" style="max-width: 180px; height: auto;">
                    <h1>✅ Račun / Potvrda o aktivaciji</h1>
                </div>
                <div class="content">
                    <div class="success-box">
                        <h2>🎉 Vaš paket je aktiviran!</h2>
                        <p style="margin: 5px 0 0 0; color: #065f46; font-weight: bold;">
                            ' . htmlspecialchars($package['name']) . ' paket
                        </p>
                    </div>
                    
                    <p>Poštovani/a <strong>' . htmlspecialchars($user['username']) . '</strong>,</p>
                    
                    <p>Sa zadovoljstvom vas obaveštavamo da je vaš paket uspešno aktiviran.</p>
                    
                    <div class="info-box">
                        <h4 style="margin: 0 0 10px 0; color: #065f46;">📋 Detalji aktivacije:</h4>
                        <p><strong>Paket:</strong> ' . htmlspecialchars($package['name']) . '</p>
                        <p><strong>Period:</strong> ' . ($transaction['period'] === 'yearly' ? 'Godišnje' : 'Mesečno') . '</p>
                        <p><strong>Datum aktivacije:</strong> ' . date('d.m.Y') . '</p>
                        <p><strong>Datum isteka:</strong> ' . $expiresAt . '</p>
                    </div>
                    
                    <p>U prilogu vam dostavljamo <strong>račun</strong> za vašu evidenciju.</p>
                    
                    <div style="background: #f0f9ff; padding: 12px; border-radius: 4px; margin: 15px 0; border: 1px solid #bae6fd;">
                        <p style="margin: 0; font-size: 13px; color: #0369a1;">
                            🚀 Sada možete koristiti sve pogodnosti ' . htmlspecialchars($package['name']) . ' paketa!
                        </p>
                    </div>
                    
                    <p style="text-align: center;">
                        <a href="' . $siteUrl . '/dashboard" class="button">🚀 Idi na kontrolnu tablu</a>
                    </p>
                    
                    <p>Hvala na poverenju! 😊</p>
                    
                    <p style="margin-top: 20px;">
                        Srdačan pozdrav,<br>
                        <strong>' . $siteName . ' tim</strong>
                    </p>
                </div>
                <div class="footer">
                    <p>' . $siteName . ' - Najveća oglasna tabla u Srbiji</p>
                    <p>&copy; ' . date('Y') . ' ' . $siteName . ' - Sva prava zadržana.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $subject = "✅ " . $package['name'] . " paket aktiviran - Račun";
        
        return sendEmailWithAttachment(
            $user['email'],
            $subject,
            $htmlContent,
            $pdfPath,
            basename($pdfPath)
        );
        
    } catch (Exception $e) {
        error_log("Send račun email error: " . $e->getMessage());
        return false;
    }
}

/**
 * Šalje email sa PDF attachmentom
 * 
 * @param string $to Email primaoca
 * @param string $subject Naslov emaila
 * @param string $htmlContent HTML sadržaj emaila
 * @param string $attachmentPath Putanja do PDF fajla
 * @param string $attachmentName Ime PDF fajla
 * @return bool True ako je email poslat, false ako nije
 */
function sendEmailWithAttachment($to, $subject, $htmlContent, $attachmentPath, $attachmentName) {
    // Prvo probaj sa PHPMailer ako postoji
    $phpmailerPath = __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    
    if (file_exists($phpmailerPath)) {
        try {
            require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
            require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = SMTP_AUTH;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlContent;
            
            // Dodaj attachment
            if (file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, $attachmentName);
            }
            
            $mail->send();
            
            error_log("✅ Email with attachment sent to: $to");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ PHPMailer Error: " . $e->getMessage());
            // Fallback na običan email bez attachmenta
            return sendEmail($to, $subject, $htmlContent);
        }
    }
    
    // Fallback - pošalji bez attachmenta
    error_log("⚠️ PHPMailer not found, sending email without attachment");
    return sendEmail($to, $subject, $htmlContent);
}


?>