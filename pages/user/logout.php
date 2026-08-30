<?php
/**
 * logout.php - Jednostavna odjava
 */

// Startuj sesiju
session_start();

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