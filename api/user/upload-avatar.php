<?php
/**
 * api/user/upload-avatar.php - Upload avatar slike
 * OPTIMIZOVANA VERZIJA - Čuva samo thumbnail (200x200)
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Uključi potrebne fajlove
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

try {
    
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

    // CSRF protection
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF token nevalidan.');
    }

    // Proveri da li je upload fajla
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Nijedna slika nije uploadovana.');
    }

    $file = $_FILES['avatar'];

    // Validacija tipa fajla
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Dozvoljeni formati: JPG, PNG, GIF.');
    }

    // Validacija veličine - 5MB max
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Slika je prevelika. Maksimalna veličina je 5MB.');
    }

    // DEFINICIJA PUTANJA
    $rootDir = $_SERVER['DOCUMENT_ROOT'];
    if (empty($rootDir)) {
        $rootDir = __DIR__ . '/../../';
    }
    $rootDir = rtrim($rootDir, '/');
    
    $userUploadDir = $rootDir . '/assets/uploads/avatars/' . $userId . '/';
    
    // Kreiraj folder ako ne postoji
    if (!file_exists($userUploadDir)) {
        if (!mkdir($userUploadDir, 0755, true)) {
            throw new Exception('Ne mogu da kreiram folder za upload.');
        }
    }

    // ============================================
    // OPTIMIZACIJA: KREIRAJ THUMBNAIL ODMAH
    // ============================================
    
    // Prvo sačuvaj privremeni fajl
    $tempFile = $userUploadDir . 'temp_' . time() . '.tmp';
    
    if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
        throw new Exception('Greška pri čuvanju privremene slike.');
    }
    
    // Kreiraj thumbnail (200x200) - OVO ĆEMO ČUVATI
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $thumbPath = $userUploadDir . $fileName;
    $relativePath = '/assets/uploads/avatars/' . $userId . '/' . $fileName;
    
    // Kreiraj thumbnail sa fiksnom veličinom 200x200
    $thumbCreated = createAvatarThumbnail($tempFile, $thumbPath, 200);
    
    // Obriši privremeni fajl (original)
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    if (!$thumbCreated) {
        throw new Exception('Greška pri kreiranju slike.');
    }
    
    // Optimizuj thumbnail dodatno (smanji kvalitet za manju veličinu)
    optimizeImage($thumbPath, $extension);
    
    // Ažuriraj bazu
    $db = getDatabaseConnection();
    
    if (!$db) {
        throw new Exception('Greška u konekciji sa bazom podataka.');
    }
    
    // Dobij staru sliku
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();
    
    // Ažuriraj sa novom
    $stmt = $db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt->execute([$relativePath, $userId])) {
        // Obriši novu sliku ako baza nije uspela
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        throw new Exception('Greška pri ažuriranju baze podataka.');
    }
    
    // Obriši staru sliku ako postoji
    if ($oldAvatar && strpos($oldAvatar, '/uploads/avatars/') !== false) {
        $oldPath = $rootDir . $oldAvatar;
        if (file_exists($oldPath)) {
            unlink($oldPath);
            
            // Nema thumbnail-a jer smo već obrisali original
            // (thumbnail je zapravo jedina slika)
        }
    }
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Slika profila je uspešno ažurirana.',
        'avatar_url' => (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . $relativePath
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    error_log("Avatar upload error: " . $e->getMessage());
}

// Očisti output buffere
while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response);
exit;

/**
 * Kreira thumbnail za avatar - OPTIMIZOVANO
 */
function createAvatarThumbnail($sourcePath, $destPath, $size = 200) {
    if (!extension_loaded('gd')) {
        error_log("GD library not available");
        return false;
    }
    
    if (!file_exists($sourcePath)) {
        error_log("Source file not found: $sourcePath");
        return false;
    }
    
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Kreiraj resurs slike
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = @imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Izračunaj dimenzije za crop (napravi kvadrat)
    $src_x = $src_y = 0;
    $src_w = $width;
    $src_h = $height;
    
    if ($width > $height) {
        $src_x = ($width - $height) / 2;
        $src_w = $height;
    } elseif ($height > $width) {
        $src_y = ($height - $width) / 2;
        $src_h = $width;
    }
    
    // Kreiraj thumbnail
    $thumb = imagecreatetruecolor($size, $size);
    
    // Sačuvaj transparentnost za PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $size, $size, $transparent);
    } else {
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
    }
    
    // Kopiraj i resize
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $size, $size, $src_w, $src_h);
    
    // Sačuvaj
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumb, $destPath, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumb, $destPath, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumb, $destPath);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $result;
}

/**
 * Dodatna optimizacija - smanji veličinu fajla
 */
function optimizeImage($filePath, $extension) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $size = filesize($filePath);
    
    // Ako je slika već manja od 50KB, ne treba optimizacija
    if ($size < 50 * 1024) {
        return true;
    }
    
    try {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($filePath);
                // Smanji kvalitet za manji fajl
                imagejpeg($img, $filePath, 75);
                imagedestroy($img);
                break;
            case 'png':
                $img = imagecreatefrompng($filePath);
                // Sačuvaj sa većom kompresijom
                imagepng($img, $filePath, 9);
                imagedestroy($img);
                break;
        }
    } catch (Exception $e) {
        error_log("Image optimization failed: " . $e->getMessage());
    }
    
    return true;
}
?>