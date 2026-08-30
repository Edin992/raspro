<?php
/**
 * api/user/remove-avatar.php - Uklanjanje avatar slike
 * KONAČNA RADNA VERZIJA - po uzoru na upload-avatar.php
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

// Error reporting - loguj ali ne prikazuj
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

    // ČITANJE JSON INPUTA
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Nevalidan JSON format.');
    }

    // CSRF protection
    if (!isset($data['csrf_token']) || !isset($_SESSION['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF token nevalidan.');
    }

    // DEFINICIJA PUTANJA - isti princip kao u upload-avatar.php
    $rootDir = $_SERVER['DOCUMENT_ROOT'];
    
    // Proveri da li je DOCUMENT_ROOT ispravan
    if (empty($rootDir)) {
        // Fallback za CLI ili neke servere
        $rootDir = __DIR__ . '/../../';
    }
    
    // Ukloni duple slash-eve
    $rootDir = rtrim($rootDir, '/');
    
    $userUploadDir = $rootDir . '/assets/uploads/avatars/' . $userId . '/';
    
    error_log("Remove avatar - User ID: " . $userId);
    error_log("Remove avatar - Upload directory: " . $userUploadDir);

    // Ažuriraj bazu
    $db = getDatabaseConnection();
    
    if (!$db) {
        throw new Exception('Greška u konekciji sa bazom podataka.');
    }
    
    // Dobij trenutnu sliku pre brisanja
    $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentAvatar = $stmt->fetchColumn();
    
    error_log("Current avatar path: " . ($currentAvatar ?: 'NULL'));
    
    // Default avatar putanja - proveri da li postoji
    $defaultAvatar = '/assets/images/defaults/avatar.svg';
    $defaultPath = $rootDir . $defaultAvatar;
    
    // Fallback na png ako svg ne postoji
    if (!file_exists($defaultPath)) {
        $defaultAvatar = '/assets/images/defaults/avatar.png';
        $defaultPath = $rootDir . $defaultAvatar;
        
        if (!file_exists($defaultPath)) {
            // Ako ni jedan default ne postoji, koristi prazan string
            $defaultAvatar = '';
            error_log("Warning: Default avatar file not found!");
        }
    }
    
    // Ažuriraj bazu sa default avatarom
    $stmt = $db->prepare("UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt->execute([$defaultAvatar, $userId])) {
        throw new Exception('Greška pri ažuriranju baze podataka.');
    }
    
    error_log("Database updated for user $userId with default avatar: " . $defaultAvatar);
    
    // Obriši staru sliku SAMO ako je u uploads/avatars/ folderu (nije default)
    if ($currentAvatar && strpos($currentAvatar, '/uploads/avatars/') !== false) {
        $oldPath = $rootDir . $currentAvatar;
        
        if (file_exists($oldPath)) {
            if (unlink($oldPath)) {
                error_log("Deleted old avatar file: " . $oldPath);
                
                // Obriši i thumbnail ako postoji
                $oldThumb = dirname($oldPath) . '/thumb_' . basename($oldPath);
                if (file_exists($oldThumb)) {
                    unlink($oldThumb);
                    error_log("Deleted old thumbnail: " . $oldThumb);
                }
                
                // Pokušaj da obrišeš prazan user folder
                $userFolder = dirname($oldPath);
                if (is_dir($userFolder)) {
                    $files = scandir($userFolder);
                    // Ako su samo . i ..
                    if (count($files) <= 2) {
                        if (rmdir($userFolder)) {
                            error_log("Removed empty user folder: " . $userFolder);
                        }
                    }
                }
            } else {
                error_log("Failed to delete old avatar file: " . $oldPath);
            }
        } else {
            error_log("Old avatar file not found: " . $oldPath);
        }
    }
    
    // Web putanja za default avatar
    $webDefaultAvatar = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . $defaultAvatar;
    
    // Success response
    $response = [
        'success' => true,
        'message' => 'Slika profila je uspešno uklonjena.',
        'default_avatar' => $webDefaultAvatar
    ];

} catch (Exception $e) {
    // Error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    error_log("Avatar remove error for user " . ($_SESSION['user_id'] ?? 'unknown') . ": " . $e->getMessage());
}

// Očisti sve output buffere
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Pošalji JSON odgovor
echo json_encode($response);
exit;
?>