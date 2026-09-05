<?php
/**
 * DATABASE KONFIGURACIJA
 * Ne zaboravite promeniti podatke pre postavljanja na server!
 */


// Vremezone za Srbiju
date_default_timezone_set('Europe/Belgrade');

// Definiši konstante za putanje
require_once 'constants.php';
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads');

// reCAPTCHA helper (dostupan svim entry point-ima: index.php i api/*)
require_once ROOT_PATH . '/includes/recaptcha.php';

// DATABASE CONFIG
define('DB_HOST', 'localhost');
define('DB_NAME', 'rasprodajars_db');
define('DB_USER', 'rasprodajars_sajt');
define('DB_PASS', '**********');

// Povezivanje sa bazom
function getDatabaseConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            // Log error (NE prikazuj korisniku detalje!)
            error_log("Database connection failed: " . $e->getMessage());
            
            // Prijateljska poruka korisniku
            die("Trenutno ne možemo da se povezemo sa bazom podataka. Pokušajte ponovo kasnije.");
        }
    }
    
    return $connection;
}

// Pokreni sesiju
if (session_status() === PHP_SESSION_NONE) {
    // SIGURNOSNI Cookie parametri za PHP >= 7.3
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax', // stedimo CSRF povrsinu; AJAX na istom domenu radi normalno
    ]);
    session_start();
    // NAPOMENKA: session_regenerate_id() je otuda - regeneration na SVAKOM zahtevu
    // je pravila race-condition kod paralelnih AJAX poziva (destroy sesije).
    // Regenerate se sada radi samo pri login-u (api/user/login.php).
}

// ============================================
// "ZAPAMTI ME" - auto-login iz kolacica (ako sesija istekne)
// ============================================
require_once ROOT_PATH . '/includes/remember-me.php';
rememberMeTryLogin();


?>