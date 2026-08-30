<?php
/**
 * api/ads/update.php - Ažuriranje oglasa
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Proveri da li je korisnik ulogovan
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Morate biti prijavljeni']);
    exit();
}

$userId = $_SESSION['user_id'];

// Proveri da li je prosleđen ID oglasa
if (!isset($_POST['ad_id']) || empty($_POST['ad_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID oglasa nije prosleđen']);
    exit();
}

$adId = intval($_POST['ad_id']);

try {
    $db = getDatabaseConnection();
    
    // Proveri da li oglas postoji i da li pripada korisniku
    $stmt = $db->prepare("SELECT user_id, status, title, category_id, subcategory_id FROM ads WHERE id = ?");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Oglas nije pronađen']);
        exit();
    }
    
    if ($ad['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Nemate pravo da menjate ovaj oglas']);
        exit();
    }
    
    // DOHVATI GRAD IZ BAZE
    $userStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    $userCity = $user['city'] ?? '';
    
    // DOHVATI LIMITE KORISNIKA
    $userLimits = getUserLimits($userId);
    $maxImages = $userLimits['image_limit'];
    
    // Validacija obaveznih polja
    $required = ['title', 'description', 'price', 'category_id', 'item_condition'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Polje '$field' je obavezno";
        }
    }
    
    // Validacija cene
    if (!empty($_POST['price']) && (!is_numeric($_POST['price']) || $_POST['price'] <= 0)) {
        $errors[] = "Cena mora biti validan broj veći od 0";
    }
    
    // Validacija kategorije
    if (!empty($_POST['category_id'])) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$_POST['category_id']]);
        if (!$stmt->fetch()) {
            $errors[] = "Kategorija ne postoji";
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Validacione greške', 'errors' => $errors]);
        exit();
    }
    
    $db->beginTransaction();
    
    // Ažuriraj osnovne podatke oglasa
    $stmt = $db->prepare("
        UPDATE ads SET
            title = ?,
            slug = ?,
            description = ?,
            price = ?,
            currency = ?,
            category_id = ?,
            subcategory_id = ?,
            item_condition = ?,
            city = ?,
            address = ?,
            price_negotiable = ?
        WHERE id = ?
    ");
    
    $subcategoryId = intval($_POST['subcategory_id']);
    $slug = createSlug($_POST['title']);
    
    $result = $stmt->execute([
        $_POST['title'],
        $slug,
        $_POST['description'],
        $_POST['price'],
        $_POST['currency'] ?? 'RSD',
        $_POST['category_id'],
        $subcategoryId,
        $_POST['item_condition'],
        $userCity,
        $_POST['address'] ?? null,
        isset($_POST['price_negotiable']) ? 1 : 0,
        $adId
    ]);
    
    if (!$result) {
        throw new Exception("Greška pri ažuriranju oglasa");
    }
    
    // ============================================
    // 1. Obrada brisanja postojećih slika
    // ============================================
    $deletedImageIds = [];
    if (!empty($_POST['delete_images'])) {
        $deleteStmt = $db->prepare("SELECT image_path, thumbnail_path, medium_path FROM ad_images WHERE id = ? AND ad_id = ?");
        $deleteImagesStmt = $db->prepare("DELETE FROM ad_images WHERE id = ? AND ad_id = ?");
        
        foreach ($_POST['delete_images'] as $imageId) {
            $deleteStmt->execute([$imageId, $adId]);
            $image = $deleteStmt->fetch();
            
            if ($image) {
                $basePath = $_SERVER['DOCUMENT_ROOT'];
                if (!empty($image['image_path']) && file_exists($basePath . $image['image_path'])) {
                    unlink($basePath . $image['image_path']);
                }
                if (!empty($image['thumbnail_path']) && file_exists($basePath . $image['thumbnail_path'])) {
                    unlink($basePath . $image['thumbnail_path']);
                }
                if (!empty($image['medium_path']) && file_exists($basePath . $image['medium_path'])) {
                    unlink($basePath . $image['medium_path']);
                }
                
                $deleteImagesStmt->execute([$imageId, $adId]);
                $deletedImageIds[] = $imageId;
            }
        }
    }
    
    // ============================================
    // 2. Postavi glavnu sliku
    // ============================================
    if (!empty($_POST['main_image_id'])) {
        $resetStmt = $db->prepare("UPDATE ad_images SET is_main = 0 WHERE ad_id = ?");
        $resetStmt->execute([$adId]);
        
        $mainStmt = $db->prepare("UPDATE ad_images SET is_main = 1 WHERE id = ? AND ad_id = ?");
        $mainStmt->execute([$_POST['main_image_id'], $adId]);
    }
    
    // ============================================
    // 3. Upload novih slika (ISTA LOGIKA KAO U CREATE.PHP)
    // ============================================
    $uploadedImages = [];
    
    if (!empty($_FILES['new_images'])) {
        $newimages = rearrayFiles($_FILES['new_images']);
    }
    
    // OBRADA SLIKA AKO POSTOJE
    if (!empty($_FILES['new_images']) && !empty($newimages)) {
        $imageCount = 0;
        
        // Proveri trenutni broj slika nakon brisanja
        $countStmt = $db->prepare("SELECT COUNT(*) as count FROM ad_images WHERE ad_id = ?");
        $countStmt->execute([$adId]);
        $currentCount = $countStmt->fetch()['count'];
        
        // Broj novih slika
        $newImagesCount = count($newimages);
        
        // Proveri limit
        if ($newImagesCount + $currentCount > $maxImages) {
            throw new Exception("Možete imati najviše {$maxImages} slika za vaš paket");
        }
        
        foreach ($newimages as $index => $image) {
            if ($imageCount >= $maxImages) break;
            
            if ($image['error'] === UPLOAD_ERR_OK) {
                // Proveri veličinu slike (koristi limit iz paketa)
                $maxImageSize = $userLimits['max_image_size'];
                if ($image['size'] > $maxImageSize) {
                    throw new Exception("Slika je prevelika. Maksimalna veličina je " . ($maxImageSize / 1024 / 1024) . "MB");
                }
                
                $imageResult = uploadAdImage($adId, $image, false);
                
                if ($imageResult) {
                    $uploadedImages[] = [
                        'path' => $imageResult['original'],
                        'thumbnail' => $imageResult['thumbnail'],
                        'medium' => $imageResult['medium'],
                        'is_main' => false
                    ];
                    $imageCount++;
                }
            }
        }
    }
    
   
    
    // Loguj izmenu
    if (function_exists('logAdUpdate')) {
        logAdUpdate($userId, $adId, $ad['title']);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Oglas je uspešno ažuriran',
        'ad_id' => $adId,
        'deleted_images' => $deletedImageIds,
        'uploaded_images' => $uploadedImages,
        'redirect' => "/ad/$adId"
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri ažuriranju oglasa: ' . $e->getMessage()
    ]);
    error_log("Update ad error: " . $e->getMessage());
}
?>