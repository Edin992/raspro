<?php
/**
 * api/user/login.php - ISPRAVLJENA VERZIJA SA VERIFIKACIJOM
 */

// OBAVEZNO na početku
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dodaj CORS headers
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

// UČITAJ BAZU
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/packages.php';


// HONEYPOT VERIFIKACIJA


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Samo POST metoda']);
    exit();
}



// PROČITAJ INPUT - podrška za JSON i FormData
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
if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Korisničko ime i lozinka su obavezni'
    ]);
    exit();
}

try {
    $db = getDatabaseConnection();
    
    // PRONAĐI KORISNIKA - proveri i username i email
    $stmt = $db->prepare("
        SELECT id, username, email, password_hash, first_name, 
               last_name, package, is_verified, verification_token
        FROM users 
        WHERE username = ? OR email = ?
    ");
    
    $stmt->execute([$input['username'], $input['username']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Pogrešno korisničko ime ili lozinka'
        ]);
        
        
        if (function_exists('logUserLogin')) {
            logUserLogin(null, false, [
                'identifier' => $input['username'],
                'ip' => $_SERVER['REMOTE_ADDR'],
                'reason' => 'user_not_found'
            ]);
        }
        exit();
    }
    
    // VERIFIKUJ LOZINKU
    if (!password_verify($input['password'], $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Pogrešno korisničko ime ili lozinka'
        ]);
        
        // Loguj pogrešnu lozinku
        if (function_exists('logUserLogin')) {
            logUserLogin($user['id'], false, [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'reason' => 'wrong_password'
            ]);
        }
        exit();
    }
    
    // ✅ ISPRAVLJENA PROVERA VERIFIKACIJE:
    if (!$user['is_verified']) {
        // DODAJEMO HTTP STATUS CODE 403 (Forbidden)
        http_response_code(403);
        
        echo json_encode([
            'success' => false,
            'message' => 'Vaš email nije verifikovan. Proverite Vaš inbox ili kliknite "Pošalji ponovo".',
            'needs_verification' => true,
            'email' => $user['email'],
            'has_token' => !empty($user['verification_token']),
            'redirect' => '?page=resend-verification&email=' . urlencode($user['email']),
            'verification_link' => !empty($user['verification_token']) 
                ? '?page=verify-email&token=' . $user['verification_token'] 
                : null
        ]);
        exit();
    }
    
    // POSTAVI SESSION
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_package'] = $user['package'];
    $_SESSION['is_verified'] = true; // ✅ DODAJEMO OVO!
    $_SESSION['redirect_url'] = NULL;
    
    // AŽURIRAJ POSLEDNJI LOGIN
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Loguj uspešan login
    if (function_exists('logUserLogin')) {
        logUserLogin($user['id'], true, ['ip' => $_SERVER['REMOTE_ADDR']]);
    }
    session_write_close();
    // VRATI USPĚH
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste prijavljeni',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => ($user['first_name'] && $user['last_name']) 
                      ? $user['first_name'] . ' ' . $user['last_name'] 
                      : $user['username'],
            'is_verified' => true, // ✅ DODAJEMO
            'package' => $user['package']
        ],
        'redirect' => $_SESSION['redirect_url'] ?? '/profile',
        'session_id' => session_id()
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri prijavi: ' . $e->getMessage()
    ]);
}
?>