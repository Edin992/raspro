<?php
/**
 * logout.php - Jednostavna odjava
 */

// Startuj sesiju
session_start();

// FIX: obrisi i "zapamti me" token - bez ovoga bi remember kolacic
// automatski vratio korisnika u sesiju na prvom sledecem ucitavanju
// (config/database.php -> rememberMeTryLogin), tj. logout ne bi radio.
require_once __DIR__ . '/../config/database.php'; // + includes/remember-me.php
if (function_exists('rememberMeClear') && !empty($_SESSION['user_id'])) {
    rememberMeClear($_SESSION['user_id']);
}

// Obriši sve podatke iz sesije
$_SESSION = [];

// Obriši cookie sesije
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Uništi sesiju
session_destroy();

// Preusmeri na početnu stranicu
header('Location: /login');
exit;
?>