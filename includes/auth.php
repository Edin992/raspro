<?php
/**
 * includes/auth.php - Autentifikacione funkcije
 * OVO JE JEDINO MESTO GDE TREBA DA BUDU OVE FUNKCIJE
 */

// Proveri da li je korisnik ulogovan
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Proveri da li je admin
function isAdmin($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) return false;
    
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT 1 FROM admins WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() !== false;
}

// Dohvati podatke korisnika
function getUserData($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) return null;
    
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT id, username, email, first_name, last_name, 
               phone, city, avatar, package, ads_count, 
               is_verified, created_at
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Proveri da li korisnik može da postavi oglas
function canPostAd($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) return false;
    
    $user = getUserData($userId);
    if (!$user) return false;
    
    // Ako je free paket, proveri limit
    if ($user['package'] === 'free') {
        return $user['ads_count'] < 5; // Free limit
    }
    
    return true;
}

// ============================================
// 🔥 ISPRAVLJENA LOGOUT FUNKCIJA
// ============================================
function logout() {
    // Ako sesija nije aktivna, samo preusmeri
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Obriši sve promenljive sesije
    $_SESSION = array();
    
    // Obriši cookie sesije
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Uništi sesiju
    session_destroy();
    
    // Zatvori sesiju
    session_write_close();
}

// ============================================
// 🔥 ISPRAVLJENA REDIRECT FUNKCIJA
// ============================================
function redirect($url, $permanent = false) {
    // Sačuvaj sesiju pre redirect-a
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Obriši sve buffere pre header-a
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    if ($permanent) {
        header('HTTP/1.1 301 Moved Permanently');
    }
    
    // Ako URL nije pun, dodaj osnovni URL
    if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
        // Ako počinje sa ?, dodaj site_url
        if (strpos($url, '?') === 0) {
            $url = SITE_URL . $url;
        } elseif (strpos($url, '/') !== 0) {
            $url = SITE_URL . '/' . $url;
        } else {
            $url = SITE_URL . $url;
        }
    }
    
    header('Location: ' . $url);
    exit();
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

// ============================================
// CSRF FUNKCIJE (VEĆ IMAŠ DOBRE)
// ============================================
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ============================================
// VERIFIKACIJA
// ============================================
function isUserVerified($userId = null) {
    if (!$userId && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    if (!$userId) return false;
    
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT is_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return ($result && $result['is_verified'] == 1);
        
    } catch (Exception $e) {
        error_log("Check user verification error: " . $e->getMessage());
        return false;
    }
}

function requireVerifiedUser() {
    if (!isLoggedIn()) {
        redirect('?page=login');
    }
    
    if (!isUserVerified()) {
        $_SESSION['warning_message'] = 'Morate verifikovati Vaš email pre pristupa ovoj stranici.';
        redirect('?page=verify-email');
    }
}

function canPostAdWithVerification($userId = null) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (!isUserVerified($userId)) {
        return false;
    }
    
    return canPostAd($userId);
}