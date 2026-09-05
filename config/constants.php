<?php
/**
 * config/constants.php - Konfiguracione konstante
 */
if (session_status() === PHP_SESSION_NONE) {
    // Bezbednosni parametri kolacica sesije (PHP >= 7.3)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,   // JS ne sme da cita kolacic sesije
        'samesite' => 'Lax',  // blaga CSRF zastita; same-origin AJAX radi normalno
    ]);
    session_start();
}

// VAŽNO: Postavi isti domen za sve
// Ako koristiš www, stavi '.raspriodaja.rs' (sa tačkom na početku)
// Ovo omogućava da sesija radi i na www i bez www


// Prvo probaj da postaviš dinamički URL
if (!defined('SITE_URL')) {
    // Proveri da li se izvršava preko CLI (cron)
    if (php_sapi_name() === 'cli') {
        // Ako je CLI, koristi fiksni URL
        define('SITE_URL', 'https://rasprodaja.rs');
    } else {
        // Normalan web zahtev
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'rasprodaja.rs';
        define('SITE_URL', $protocol . $host);
    }
}

define('SITE_NAME', 'rasprodaja.rs');

// ============================================
// EMAIL KONFIGURACIJA (SMTP)
// ============================================
define('SMTP_HOST', 'mail.rasprodaja.rs');
define('SMTP_PORT', 465); // SSL port
define('SMTP_USERNAME', 'noreply@rasprodaja.rs');
define('SMTP_PASSWORD', '************'); // ZAMENI SA PRAVOM LOZINKOM!
define('SMTP_FROM_EMAIL', 'noreply@rasprodaja.rs');
define('SMTP_FROM_NAME', 'Rasprodaja.rs');
define('SMTP_SECURE', 'ssl'); // 'ssl' za port 465
define('SMTP_AUTH', true);
define('SMTP_DEBUG', 0); // 0 = off, 1 = client messages, 2 = client and server messages

/*define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 25);
define('SMTP_SECURE', '');
define('SMTP_AUTH', false);*/
// ============================================
// VERIFIKACIJA
// ============================================
define('VERIFICATION_TOKEN_EXPIRY', 24); // 24 sata
define('PASSWORD_RESET_TOKEN_EXPIRY', 2); // 2 sata

// ============================================
// UPLOAD OGRANIČENJA
// ============================================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB u bajtovima
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif']);
define('MAX_IMAGES_PER_AD', 20);


// ============================================
// SESSION KONFIGURACIJA
// ============================================
define('SESSION_TIMEOUT', 1800); // 30 minuta u sekundama

// ============================================
// SISTEMSKE PUTANJE
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads/');


// ============================================
// RECAPTCHA v3 (Google) - zastita login/registration od botova
// ============================================
// Kljuceve napravite na: https://www.google.com/recaptcha/admin
// Tip: reCAPTCHA v3 ("Nisam robot", domen: rasprodaja.rs + www.rasprodaja.rs)
// Kad unesete oba kljuca, zastita se AUTOMATSKI ukljucuje za login i register.
// Ostavite prazno dok ne napravite kljuceve (tada se provera preskace).
if (!defined('RECAPTCHA_SITE_KEY')) {
    define('RECAPTCHA_SITE_KEY', '');   // <-- ovde vase SITE (public) key
}
if (!defined('RECAPTCHA_SECRET_KEY')) {
    define('RECAPTCHA_SECRET_KEY', ''); // <-- ovde vase SECRET key
}
// minimalni Google score (0.0 - 1.0) ispod koga zahtev padas
// 0.5 je preporuka za login/register; ako ima laznih odbijanja spusti na 0.4
if (!defined('RECAPTCHA_MIN_SCORE')) {
    define('RECAPTCHA_MIN_SCORE', 0.5);
}
// timeout (sekunde) za poziv Google siteverify API-ja
if (!defined('RECAPTCHA_VERIFY_TIMEOUT')) {
    define('RECAPTCHA_VERIFY_TIMEOUT', 5);
}

// ============================================
// DEBUG MODE
// ============================================
define('DEBUG_MODE', false); // Stavi na false u produkciji

// ============================================
// CACHE KONFIGURACIJA
// ============================================
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600); // 1 sat

// ============================================
// CURRENCY
// ============================================
define('DEFAULT_CURRENCY', 'RSD');

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'din');
}
// ============================================
// ERROR HANDLING
// ============================================
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Greske se LOGGUJU (error_log), ali NE prikazuju posetiocima.
    // Ranije: error_reporting(0) - potpuno gusenje gresaka otezava debugging.
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// ============================================
// SECURITY
// ============================================
define('CSRF_TOKEN_EXPIRY', 3600); // 1 sat
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minuta

// ============================================
// EMAIL TEMPLATE PATHS (za budućnost)
// ============================================
define('EMAIL_TEMPLATE_PATH', ROOT_PATH . '/templates/emails/');

// ============================================
// API KEYS (za budućnost)
// ============================================
define('GOOGLE_MAPS_API_KEY', ''); // Ostavi prazno za sada

// Google Analytics ID (npr. 'G-XXXXXXXXXX').  Skripta se ucitava SAMO ako
// korisnik prihvati analiticke kolacice (assets/js/cookies.js).
define('GOOGLE_ANALYTICS_ID', '');

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Europe/Belgrade');



?>