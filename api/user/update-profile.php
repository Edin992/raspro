<?php
/**
 * api/user/update-profile.php - Ažuriranje korisničkog profila
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Proveri da li je korisnik ulogovan
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni.']);
    exit;
}

// Proveri CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nevažeći CSRF token.']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDatabaseConnection();

// Validacija podataka
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$city = trim($_POST['city'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$showEmail = isset($_POST['show_email']) ? 1 : 0;

// Validacija obaveznih polja
if (empty($firstName) || empty($lastName)) {
    echo json_encode(['success' => false, 'message' => 'Ime i prezime su obavezna polja.']);
    exit;
}

// Validacija telefona (opciono)
if (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]{6,20}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Broj telefona nije validan.']);
    exit;
}

// Ažuriraj profil (bez username-a)
try {
    $stmt = $db->prepare("
        UPDATE users SET
            first_name = ?,
            last_name = ?,
            bio = ?,
            city = ?,
            phone = ?,
            show_email = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $firstName,
        $lastName,
        $bio,
        $city,
        $phone,
        $showEmail,
        $userId
    ]);
    
    if ($result) {
        // Ažuriraj sesiju
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Profil je uspešno ažuriran.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Došlo je do greške prilikom ažuriranja.'
        ]);
    }
} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Došlo je do serverske greške.'
    ]);
}
?>