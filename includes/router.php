<?php
/**
 * includes/router.php - Ruter za upravljanje stranicama
 */

function getValidPages() {
    return [
        // Glavne stranice
        'home' => 'pages/home.php',
        'ads' => 'pages/ads/listing.php',
        'ad-detail' => 'pages/ads/detail.php',
        'create-ad' => 'pages/ads/create.php',
        'edit-ad' => 'pages/ads/edit.php',
        
        // Korisničke stranice
        'login' => 'pages/user/login.php',
        'logout' => 'pages/user/logout.php',
        'register' => 'pages/user/register.php',
        'profile' => 'pages/user/profile.php',
        'dashboard' => 'pages/user/dashboard.php',
        'messages' => 'pages/user/messages.php',
        'notifications' => 'pages/user/notifications.php',
        'verify-email' => 'pages/user/verify-email.php',
        'resend-verification' => 'pages/user/resend-verification.php',
        'forgot-password' => 'pages/user/forgot-password.php',
        'reset-password' => 'pages/user/reset-password.php',
        
        // Informativne stranice
        'how-it-works' => 'pages/info/how-it-works.php',
        'contact' => 'pages/info/contact.php',
        'about' => 'pages/info/about.php',
        'faq' => 'pages/info/faq.php',
        'safety' => 'pages/info/safety.php',
        
        // Pravne stranice
        'terms' => 'pages/legal/terms.php',
        'privacy' => 'pages/legal/privacy.php',
        'cookies' => 'pages/legal/cookies.php',
        
        // Kategorije i paketi
        'categories' => 'pages/categories.php',
        'packages' => 'pages/packages.php',
        'premium-ads' => 'pages/ads/listing.php', // FIX: pages/premium-ads.php ne postoji - listing sa premium filterom
        
        
        
        // Greške
        '404' => 'pages/errors/404.php',
        '403' => 'pages/errors/403.php',
        '500' => 'pages/errors/500.php'
    ];
}

function getPageTitle($page) {
    $titles = [
        'home' => 'Rasprodaja.rs - Najveći oglasnik u Srbiji',
        'ads' => 'Pretraga oglasa - Rasprodaja.rs',
        'ad-detail' => 'Detalji oglasa - Rasprodaja.rs',
        'create-ad' => 'Postavi novi oglas - Rasprodaja.rs',
        'edit-ad' => 'Izmeni oglas - Rasprodaja.rs',
        'login' => 'Prijavi se - Rasprodaja.rs',
        'register' => 'Registruj se - Rasprodaja.rs',
        'profile' => 'Moj profil - Rasprodaja.rs',
        'dashboard' => 'Kontrolna tabla - Rasprodaja.rs',
        'messages' => 'Poruke - Rasprodaja.rs',
        'notifications' => 'Obaveštenja - Rasprodaja.rs',
        'how-it-works' => 'Kako radi? - Rasprodaja.rs',
        'contact' => 'Kontakt - Rasprodaja.rs',
        'about' => 'O nama - Rasprodaja.rs',
        'faq' => 'Često postavljana pitanja - Rasprodaja.rs',
        'terms' => 'Uslovi korišćenja - Rasprodaja.rs',
        'privacy' => 'Politika privatnosti - Rasprodaja.rs',
        'cookies' => 'Politika kolačića - Rasprodaja.rs',
        'categories' => 'Kategorije - Rasprodaja.rs',
        'packages' => 'Paketi - Rasprodaja.rs',
        'premium-ads' => 'Premium oglasi - Rasprodaja.rs',
        '404' => 'Stranica nije pronađena - Rasprodaja.rs',
        '403' => 'Pristup zabranjen - Rasprodaja.rs',
        '500' => 'Greška servera - Rasprodaja.rs'
    ];
    
    return isset($titles[$page]) ? $titles[$page] : 'Rasprodaja.rs';
}

function checkPageAccess($page) {
    $protectedPages = [
        'create-ad', 'edit-ad', 'profile', 'dashboard', 'messages'
    ];
    
    
    // Proveri da li stranica zahteva login
    if (in_array($page, $protectedPages) && !isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('/login');
        return false;
    }
    
    
    
    return true;
}
?>