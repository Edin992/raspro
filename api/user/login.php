<?php
/**
 * api/user/login.php - Login endpoint
 *
 * IZMENE:
 *  - reCAPTCHA v3 provera (action: 'login')
 *  - CSRF provera (token dolazi iz forme / SITE_CONFIG)
 *  - Honeypot polje 'ime' (potpunja samo bot)
 *  - session_regenerate_id() pri loginu (anti session-fixation)
 *  - ispravljen redirect_url (bio je postavljen na NULL pre koriscenja)
 *  - vise ne curi verifikacioni token niti interni exception message
 *  - brojenje neuspesnih pokusaja po IP adresi (rate limit)
 */

// CORS - DOZVOLI SAMO ISTI DOMEN (bez echo bilo kog Origin-a sa credentials!)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$selfOrigin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
if ($origin !== '' && $origin === $selfOrigin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Vary: Origin');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// database.php -> constants.php pokreće SESIJU sa sigurnim kolacici
// (HttpOnly, SameSite, Secure). Zato NE zovemo session_start() pre ovoga.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/packages.php'; // logUserLogin()

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
        $input = json_decode($json, true) ?: [];
    }
}

$fail = function ($code, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit();
};

// ============================================
// ANTI-BOT: HONEYPOT
// ============================================
if (!empty($input['ime'])) {
    // Bot je popunio skriveno polje - glumi uspeh, ne loguj nista
    echo json_encode(['success' => true, 'message' => 'Uspešno ste prijavljeni', 'redirect' => '/']);
    exit();
}

// ============================================
// ANTI-BOT: RECAPTCHA v3
// ============================================
$recaptcha = recaptcha_check_submission($input, 'login');
if (!$recaptcha['success']) {
    $fail(422, $recaptcha['message'] ?: 'reCAPTCHA provera nije uspela.', ['recaptcha_failed' => true]);
}

// ============================================
// CSRF
// ============================================
$csrfToken = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrfToken)) {
    $fail(403, 'Sesija je istekla. Osvežite stranicu i pokušajte ponovo.');
}

// ============================================
// VALIDACIJA
// ============================================
// FIX: normailizuj input - bot moze poslati username[] (array) i oboriti PHP
$usernameInput = is_string($input['username'] ?? null) ? trim($input['username']) : '';
$passwordInput = is_string($input['password'] ?? null) ? $input['password'] : '';
if ($usernameInput === '' || $passwordInput === '') {
    $fail(400, 'Korisničko ime i lozinka su obavezni');
}

// RATE LIMIT po IP adresi (radi i bez dodatnih tabella - preko APCu,
// a ako APCu nije dostupan, oslanjamo se na reCAPTCHA + honeypot)
$rateKey = 'raspro_login_rl_' . md5((string)($_SERVER['REMOTE_ADDR'] ?? 'na'));
$loginBumpFail = function () use ($rateKey) {
    if (function_exists('apc_inc')) {
        if (!@apc_inc($rateKey, 1, $ok)) {
            apc_add($rateKey, 1, LOGIN_LOCKOUT_TIME);
        }
    }
};
if (function_exists('apc_fetch')) {
    $ok = false;
    $attempts = (int) apc_fetch($rateKey, $ok);
    if ($ok && $attempts >= MAX_LOGIN_ATTEMPTS) {
        error_log('Login rate limit: ' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' pokusaja=' . $attempts);
        $fail(429, 'Previše neuspešnih pokušaja. Sačekajte ' . round(LOGIN_LOCKOUT_TIME / 60) . ' minuta i pokušajte ponovo.');
    }
}

try {
    $db = getDatabaseConnection();
    
    // PRONAĐI KORISNIKA - proveri i username i email
    $stmt = $db->prepare("
        SELECT id, username, email, password_hash, first_name, 
               last_name, package, is_verified
        FROM users 
        WHERE username = ? OR email = ?
        LIMIT 1
    ");
    $stmt->execute([$usernameInput, $usernameInput]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $loginBumpFail();
        if (function_exists('logUserLogin')) {
            logUserLogin(null, false, [
                'identifier' => mb_substr($usernameInput, 0, 100),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'reason' => 'user_not_found'
            ]);
        }
        $fail(401, 'Pogrešno korisničko ime ili lozinka');
    }
    
    // VERIFIKUJ LOZINKU
    if (!password_verify($passwordInput, $user['password_hash'])) {
        $loginBumpFail();
        if (function_exists('logUserLogin')) {
            logUserLogin($user['id'], false, [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'reason' => 'wrong_password'
            ]);
        }
        $fail(401, 'Pogrešno korisničko ime ili lozinka');
    }
    
    // EMAIL VERIFIKACIJA
    if (!$user['is_verified']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Vaš email nije verifikovan. Proverite Vaš inbox ili kliknite "Pošalji ponovo".',
            'needs_verification' => true,
            // NAPOMENKA: namerno NE vracamo verification_token u odgovoru
            'redirect' => '/resend-verification?email=' . urlencode($user['email'])
        ]);
        exit();
    }
    
    // ============================================
    // USPESAN LOGIN
    // ============================================
    // Anti session-fixation: novi ID sesije tek kad je identitet potvrđen
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_package'] = $user['package'];
    $_SESSION['is_verified'] = true;
    $_SESSION['last_activity'] = time();
    
    // FIX: redirect_url se sada cita PRE brisanja (stari kod ga je prvo
    // postavljao na NULL, pa je svaki login vracio '/profile')
    $redirectUrl = $_SESSION['redirect_url'] ?? '/profile';
    unset($_SESSION['redirect_url']);
    
    // Resetuj brojač neuspešnih pokušaja
    if (function_exists('apc_delete')) { apc_delete($rateKey); }
    
    // FIX: "Zapamti me" - ranije je checkbox samo postojao, nista ga je citaо
    $rememberRequested = !empty($input['remember']);
    if (function_exists('rememberMeIssue')) {
        rememberMeIssue($user['id'], $rememberRequested);
    }
    
    // AŽURIRAJ POSLEDNJI LOGIN
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Loguj uspešan login
    if (function_exists('logUserLogin')) {
        logUserLogin($user['id'], true, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
    }
    
    // Resetuj lozinku ako je hash star (transparentni rehash)
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
    }
    
    session_write_close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste prijavljeni',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => ($user['first_name'] && $user['last_name'])
                      ? $user['first_name'] . ' ' . $user['last_name']
                      : $user['username'],
            'is_verified' => true,
            'package' => $user['package']
        ],
        'redirect' => $redirectUrl
        // NAPOMENKA: session_id nikad ne šaljemo klijentu
    ]);
    
} catch (Throwable $e) {
    // Interni detalji idu SAMO u log, ne klijentu
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri prijavi. Pokušajte ponovo.'
    ]);
}
