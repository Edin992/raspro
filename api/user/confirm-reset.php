<?php
/**
 * api/user/confirm-reset.php - Potvrda resetovanja lozinke
 */

// OBAVEZNO na početku
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Samo POST metoda']);
    exit();
}

// PROČITAJ INPUT
$input = [];
if (!empty($_POST)) {
    $input = $_POST;
} else {
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $input = json_decode($json, true);
    }
}

// VALIDACIJA
$errors = [];
$required = ['token', 'password', 'confirm_password'];

foreach ($required as $field) {
    if (empty($input[$field])) {
        $errors[] = "Polje '$field' je obavezno";
    }
}

if (!empty($input['password']) && !empty($input['confirm_password']) && 
    $input['password'] !== $input['confirm_password']) {
    $errors[] = "Lozinke se ne poklapaju";
}

if (!empty($input['password']) && strlen($input['password']) < 8) {
    $errors[] = "Lozinka mora imati najmanje 8 karaktera";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validacione greške',
        'errors' => $errors
    ]);
    exit();
}

$token = trim($input['token']);
$password = $input['password'];

try {
    $db = getDatabaseConnection();
    
    // PRONAĐI KORISNIKA SA VALIDNIM TOKENOM
    $stmt = $db->prepare("
        SELECT id, email, username, reset_expires, is_verified
        FROM users 
        WHERE reset_token = ? 
        AND reset_expires > NOW()
    ");
    
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Token nije validan ili je istekao. Zatražite novi link.'
        ]);
        exit();
    }
    
    // PROVERI DA LI JE KORISNIK VERIFIKOVAN
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Morate verifikovati nalog pre promene lozinke.',
            'needs_verification' => true,
            'redirect' => '?page=verify-email'
        ]);
        exit();
    }
    
    // HASH NOVE LOZINKE
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // AŽURIRAJ BAZU - resetuj token i postavi novu lozinku
    $stmt = $db->prepare("
        UPDATE users 
        SET password_hash = ?,
            reset_token = NULL,
            reset_expires = NULL,
            reset_attempts = 0,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$passwordHash, $user['id']]);
    
    // LOGUJ AKCIJU
    error_log("Password reset confirmed for user ID: {$user['id']}, Email: {$user['email']}, IP: {$_SERVER['REMOTE_ADDR']}");
    
    // POŠALJI EMAIL POTVRDE
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $loginLink = $siteUrl . '/?page=login';
    
    $emailContent = generatePasswordChangedEmail($user['username'], $loginLink);
    
    sendEmail(
        $user['email'],
        'Lozinka je uspešno promenjena - Rasprodaja.rs',
        $emailContent,
        "Vaša lozinka je uspešno promenjena. Možete se prijaviti na: $loginLink"
    );
    
    // VRATI ODGOVOR
    echo json_encode([
        'success' => true,
        'message' => 'Lozinka je uspešno promenjena. Možete se prijaviti sa novom lozinkom.',
        'redirect' => '?page=login'
    ]);
    
} catch (Exception $e) {
    error_log("Password reset confirmation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri promeni lozinke. Pokušajte ponovo.'
    ]);
}
?>