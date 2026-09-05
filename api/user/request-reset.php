<?php
/**
 * api/user/request-reset.php - Zahtev za resetovanje lozinke
 */

// NAPOMENKA: rucni session_start() je uklonjen; constants.php
// (preko database.php) pokrece sesiju sa HttpOnly/SameSite kolacicima.

// CORS headers
header('Content-Type: application/json');
// FIX: 'echo bilo kog Origin + credentials' je opasno; endpoint je
// same-origin, pa CORS zaglavlja nisu potrebna.
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

// ============================================
// ANTI-BOT: reCAPTCHA v3 (action 'reset_password')
// ============================================
$recaptcha = recaptcha_check_submission($input, 'reset_password');
if (!$recaptcha['success']) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $recaptcha['message'] ?: 'reCAPTCHA provera nije uspela.',
        'recaptcha_failed' => true
    ]);
    exit();
}

// VALIDACIJA EMAILA
if (empty($input['email'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Email adresa je obavezna'
    ]);
    exit();
}

$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Email adresa nije validna'
    ]);
    exit();
}

try {
    $db = getDatabaseConnection();
    
    // PRONAĐI KORISNIKA PO EMAILU
           $stmt = $db->prepare("
            SELECT id, email, username, first_name, last_name, 
                   reset_attempts, last_reset_request, is_verified
            FROM users 
            WHERE email = ?  
        ");
    
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Uvek vraćaj istu poruku (security best practice)
    if (!$user) {
        // Loguj pokušaj za nepostojeći email (bez user_id)
        error_log("Password reset request for non-existent email: $email");
        
        // Simuliraj uspeh radi sigurnosti
        sleep(1); // Small delay to prevent timing attacks
        
        echo json_encode([
            'success' => true,
            'message' => 'Ako email postoji u našem sistemu, poslaćemo Vam reset link.'
        ]);
        exit();
    }
    
    // RATE LIMITING PROVERA
    $now = new DateTime();
    $lastRequest = $user['last_reset_request'] ? new DateTime($user['last_reset_request']) : null;
    
    if ($lastRequest) {
        $diff = $now->getTimestamp() - $lastRequest->getTimestamp();
        $hoursSinceLastRequest = $diff / 3600;
        
        // Ako je poslednji zahtev bio pre manje od 1 sata i ima 3+ pokušaja
        if ($hoursSinceLastRequest < 1 && $user['reset_attempts'] >= 3) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Previše zahteva. Pokušajte ponovo za 1 sat.'
            ]);
            exit();
        }
        
        // Resetuj brojač ako je prošlo više od 1 sata
        if ($hoursSinceLastRequest >= 1) {
            $user['reset_attempts'] = 0;
        }
    }
    
    // PROVERI DA LI JE KORISNIK VERIFIKOVAN
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Morate verifikovati nalog pre resetovanja lozinke.',
            'needs_verification' => true,
            'redirect' => '?page=verify-email'
        ]);
        exit();
    }
    
    // GENERIŠI TOKEN
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    // AŽURIRAJ BAZU
    $stmt = $db->prepare("
        UPDATE users 
        SET reset_token = ?,
            reset_expires = ?,
            reset_attempts = reset_attempts + 1,
            last_reset_request = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$resetToken, $expiresAt, $user['id']]);
    
    // KREIRAJ RESET LINK
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $resetLink = $siteUrl . '/?page=reset-password&token=' . $resetToken;
    
    // PRIKAŽI IMENA KORISNIKA
    $userName = !empty($user['first_name']) ? $user['first_name'] : $user['username'];
    
    // GENERIŠI EMAIL SADRŽAJ
    $emailContent = generatePasswordResetEmail($userName, $resetLink);
    
    // POŠALJI EMAIL
    $emailSent = sendEmail(
        $user['email'],
        'Resetovanje lozinke - Rasprodaja.rs',
        $emailContent,
        "Kliknite na link da resetujete lozinku: $resetLink"
    );
    
    // LOGUJ AKCIJU
    error_log("Password reset requested for user ID: {$user['id']}, Email: {$user['email']}, IP: {$_SERVER['REMOTE_ADDR']}");
    
    // VRATI ODGOVOR
    echo json_encode([
        'success' => true,
        'message' => 'Ako email postoji u našem sistemu, poslaćemo Vam reset link.',
        'email_sent' => $emailSent,
        'rate_info' => [
            'attempts' => $user['reset_attempts'] + 1,
            'max_attempts' => 3
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Password reset request error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri obradi zahteva. Pokušajte ponovo.'
    ]);
}
?>