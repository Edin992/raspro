<?php
/**
 * api/user/logout.php - Odjava korisnika - ISPRAVLJENO
 */

// PRVO učitajte konfiguraciju da biste imali SITE_URL
require_once __DIR__ . '/../../config/database.php';

// Onda pokrenite session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Uništi sesiju
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// ISPRAVLJENO: Koristite SITE_URL konstantu
header('Location: ' . SITE_URL);
exit();
?>