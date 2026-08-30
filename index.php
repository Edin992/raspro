<?php
/**
 * index.php - GLAVNI RUTER
 */
//require_once 'config/constants.php';
// 1. POKRENI SESIJU - OVO PRVO!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// 🔥 DODAJ OVO - PROVERA DA LI JE KORISNIK PRIJAVLJEN
// ============================================
// Ako je korisnik prijavljen, ali sesija nema podatke - odjavi ga
if (isset($_SESSION['user_id']) && empty($_SESSION['user_id'])) {
    logout();
    redirect('/');
    exit;
}



// 2. OUTPUT BUFFERING - za sigurnost
ob_start();

// 3. UČITAJ KONFIGURACIJU
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/router.php';
require_once 'includes/messages.php';
require_once 'includes/categories.php';
require_once 'includes/packages.php';

// 4. RUTIRANJE
$page = $_GET['page'] ?? 'home';

// ============================================
// 🔥 DODAJ OVO - DIREKTAN PRISTUP LOGOUTU
// ============================================
if ($page === 'logout') {
    // Pozovi logout funkciju iz auth.php
    logout();
    
    // Sačuvaj poruku
    session_start();
    $_SESSION['logout_message'] = 'Uspešno ste se odjavili.';
    session_write_close();
    
    // Preusmeri na home
    redirect('/');
    exit;
}

$validPages = getValidPages();

// Proveri da li stranica postoji
if (!array_key_exists($page, $validPages)) {
    $page = '404';
}

// Proveri pristup (zaštićene stranice)
$protectedPages = ['create-ad', 'edit-ad', 'profile', 'dashboard', 'messages'];
if (in_array($page, $protectedPages) && !isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    redirect('/login');
    exit;
}

// Učitaj odgovarajuću stranicu
$pageFile = $validPages[$page];

// ============================================
// Učitaj stranicu da dobijemo $pageSpecificCSS i $inlineScripts
// ============================================
ob_start();
$pageSpecificCSS = [];
$pageSpecificJS = [];
$pageTitle = '';
$pageDescription = '';

if (file_exists($pageFile)) {
    include $pageFile;
} else {
    // Fallback na 404
    echo '<div class="alert alert-danger mt-5">';
    echo '<h4>Greška 404</h4>';
    echo '<p>Stranica "' . htmlspecialchars($page) . '" nije pronađena.</p>';
    echo '<p><a href="?page=home" class="btn btn-primary">Vratite se na početnu</a></p>';
    echo '</div>';
}
$pageContent = ob_get_clean();
// ============================================

// 5. UČITAJ LAYOUT
include 'layout/header.php';
include 'layout/navbar.php';

// 6. PRIKAŽI SADRŽAJ STRANICE
echo '<main class="container mt-4" id="main-content" style="padding-bottom: 80px;">';
echo $pageContent;
echo '</main>';

// 7. UČITAJ FOOTER
include 'layout/footer.php';
include 'layout/scripts.php';

// 8. POŠALJI OUTPUT
ob_end_flush();
?>