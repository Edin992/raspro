<?php
/**
 * api/ads/create.php - ISPRAVLJENA VERZIJA (grad iz baze)
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

// PROVERI DA LI JE KORISNIK ULOGOVAN
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Morate biti prijavljeni'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// DOHVATI GRAD IZ BAZE (umesto iz POST-a)
$db = getDatabaseConnection();
$userStmt = $db->prepare("SELECT city FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$userCity = $user['city'] ?? '';

// Provera da li korisnik ima grad u profilu
if (empty($userCity)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Nemate podešen grad u profilu. Molimo vas da prvo ažurirate profil.'
    ]);
    exit();
}

// DOHVATI LIMITE IZ BAZE PREKO packages.php
$userLimits = getUserLimits($userId);
$userPackage = $userLimits['package'];
$adLimit = $userLimits['ad_limit'];
$currentAds = $userLimits['current_ads'];
$maxImages = $userLimits['image_limit'];

// PROVERI LIMIT OGLASA
if ($currentAds >= $adLimit && $userPackage == 'free') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => "Dostigli ste limit od {$adLimit} oglasa za FREE paket"
    ]);
    exit();
}

// VALIDACIJA OBAVEZNIH POLJA (city se NE validira više)
$required = ['title', 'description', 'price', 'category_id', 'item_condition'];
$errors = [];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $errors[] = "Polje '$field' je obavezno";
    }
}

// PROVERA CENE
if (!empty($_POST['price']) && (!is_numeric($_POST['price']) || $_POST['price'] <= 0)) {
    $errors[] = "Cena mora biti validan broj veći od 0";
}

// PROVERA KATEGORIJE
if (!empty($_POST['category_id'])) {
    try {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$_POST['category_id']]);
        if (!$stmt->fetch()) {
            $errors[] = "Kategorija ne postoji";
        }
    } catch (Exception $e) {
        $errors[] = "Greška pri proveri kategorije";
    }
}

// AKO IMA GREŠAKA
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validacione greške',
        'errors' => $errors
    ]);
    exit();
}

// KREIRAJ OGLAS
try {
    $db->beginTransaction();
    
    // VALIDACIJA SLIKA
    $uploadedImages = [];
    if (!empty($_FILES['images'])) {
        $images = rearrayFiles($_FILES['images']);
        
        // Validacija broja slika - koristi $maxImages iz limita
        if (count($images) > $maxImages) {
            throw new Exception("Možete dodati najviše {$maxImages} slika za vaš paket");
        }
        
        // Proveri da li je postavljen primary_image_index
        $primaryImageIndex = isset($_POST['primary_image_index']) ? intval($_POST['primary_image_index']) : 0;
    }
    
    // KREIRAJ SLUG OD NASLOVA
    $slug = createSlug($_POST['title']);
    
    // DODAJ ROK TRAJANJA (30 dana)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // DA LI JE PREMIUM OGLAS?
    $isPremium = isset($_POST['is_premium']) && $_POST['is_premium'] == '1';
    $premiumUntil = $isPremium ? date('Y-m-d H:i:s', strtotime('+7 days')) : null;
    
    // INSERT OGLASA (KORISTI $userCity iz baze, ne iz POST-a)
    $stmt = $db->prepare("
        INSERT INTO ads (
            user_id, category_id, subcategory_id, title, slug, description,
            price, currency, price_negotiable, item_condition, city, address,
            is_premium, premium_until, status, views, expires_at, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $userId,
        $_POST['category_id'],
        $_POST['subcategory_id'] ?? null,
        $_POST['title'],
        $slug,
        $_POST['description'],
        $_POST['price'],
        $_POST['currency'] ?? 'RSD',
        isset($_POST['price_negotiable']) ? 1 : 0,
        $_POST['item_condition'],
        $userCity,  // KORISTI GRAD IZ BAZE
        $_POST['address'] ?? null,
        $isPremium ? 1 : 0,
        $premiumUntil,
        $expiresAt
    ]);
    
    if (!$result) {
        throw new Exception("Greška pri kreiranju oglasa");
    }
    
    $adId = $db->lastInsertId();
    
    // OBRADA SLIKA AKO POSTOJE
    if (!empty($_FILES['images']) && !empty($images)) {
        $imageCount = 0;
        
        foreach ($images as $index => $image) {
            if ($imageCount >= $maxImages) break;
            
            if ($image['error'] === UPLOAD_ERR_OK) {
                // Proveri veličinu slike (koristi limit iz paketa)
                $maxImageSize = $userLimits['max_image_size'];
                if ($image['size'] > $maxImageSize) {
                    throw new Exception("Slika je prevelika. Maksimalna veličina je " . ($maxImageSize / 1024 / 1024) . "MB");
                }
                
                // Proveri da li je ovo glavna slika
                $isMain = ($index === $primaryImageIndex);
                
                $imageResult = uploadAdImage($adId, $image, $isMain);
                
                if ($imageResult) {
                    $uploadedImages[] = [
                        'path' => $imageResult['original'],
                        'thumbnail' => $imageResult['thumbnail'],
                        'medium' => $imageResult['medium'],
                        'is_main' => $isMain
                    ];
                    $imageCount++;
                }
            }
        }
    }
    
    // AŽURIRAJ BROJ OGLASA KORISNIKA
    $stmt = $db->prepare("UPDATE users SET ads_count = ads_count + 1 WHERE id = ?");
    $stmt->execute([$userId]);
    
    // AŽURIRAJ BROJ OGLASA GLAVNE KATEGORIJE
    $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count + 1 WHERE id = ?");
    $stmt->execute([$_POST['category_id']]);
    
    // AŽURIRAJ BROJ OGLASA PODKATEGORIJE (ako postoji)
    if (!empty($_POST['subcategory_id'])) {
        $stmt = $db->prepare("UPDATE categories SET ad_count = ad_count + 1 WHERE id = ?");
        $stmt->execute([$_POST['subcategory_id']]);
    }
    
    $db->commit();
    
    logAdCreation($userId, $adId, $_POST['title']);
    
    // VRATI USPEH
    echo json_encode([
        'success' => true,
        'message' => 'Oglas je uspešno kreiran',
        'ad_id' => $adId,
        'slug' => $slug,
        'images' => $uploadedImages,
        'redirect' => "?page=ad-detail&id=$adId"
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri kreiranju oglasa: ' . $e->getMessage()
    ]);
    error_log("Ad creation error: " . $e->getMessage());
}