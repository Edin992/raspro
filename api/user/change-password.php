<?php
/**
 * api/user/change-password.php - Promena lozinke (SA EMAIL OBAVEŠTENJEM)
 */
// FIX: session_start() maknut - constants.php je pokrece sa sigurnim kolacicima
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/remember-me.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/packages.php'; // FIX: logUserActivity() je ovde - bez include-a je bio FATAL
header('Content-Type: application/json');

// Proveri da li je korisnik ulogovan
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Niste prijavljeni.']);
    exit;
}

// Proveri CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nevažeći CSRF token.']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDatabaseConnection();

// Dobij podatke
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validacija
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'Sva polja su obavezna.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Nove lozinke se ne poklapaju.']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Nova lozinka mora imati najmanje 8 karaktera.']);
    exit;
}

// Dohvati podatke o korisniku
$stmt = $db->prepare("SELECT id, email, first_name, last_name, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Korisnik nije pronađen.']);
    exit;
}

// Proveri trenutnu lozinku
// KRITICAN FIX: ranije se citala kolona `password` koja NE POSTOJI
// (u bazi je `password_hash`), pa je provera UVEK padala sa
// "Trenutna lozinka nije tačna" i promena nije radila.
$stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();
if (!$currentUser) {
    echo json_encode(['success' => false, 'message' => 'Korisnik nije pronađen.']);
    exit;
}

if (!password_verify($currentPassword, $currentUser['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Trenutna lozinka nije tačna.']);
    exit;
}

// Proveri da li je nova lozinka ista kao stara
if (password_verify($newPassword, $currentUser['password_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Nova lozinka ne može biti ista kao trenutna.']);
    exit;
}

// Ažuriraj lozinku
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$hashedPassword, $userId]);
    
    if (!$result) {
        throw new Exception("Greška pri ažuriranju lozinke");
    }
    
    // FIX: posle promene lozinke brišemo sve 'zapamti me' tokenize
    // sa drugih uredjaja (stari uređaji ostaju prijavljeni samo kroz
    // aktivne sesije, a ne kroz kolacice od 30 dana)
    if (function_exists('rememberMeClear')) {
        rememberMeClear($userId);
    }
    
    // Loguj aktivnost
    logUserActivity($userId, 'password_change', [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // ============================================
    // POŠALJI EMAIL OBAVEŠTENJE
    // ============================================
    $userName = trim($user['first_name'] . ' ' . $user['last_name']);
    if (empty($userName)) {
        $userName = $user['username'];
    }
    
    $emailSubject = "🔐 Vaša lozinka je promenjena - Rasprodaja.rs";
    
    $emailHtml = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔐 Promena lozinke</h2>
            </div>
            <div class='content'>
                <h3>Poštovani/a {$userName},</h3>
                <p>Vaša lozinka na <strong>Rasprodaja.rs</strong> je uspešno promenjena.</p>
                
                <div class='warning-box'>
                    <h4 style='margin-top: 0; color: #856404;'>
                        <i class='fas fa-exclamation-triangle'></i> Niste Vi promenili lozinku?
                    </h4>
                    <p>Ako niste Vi izvršili ovu promenu, <strong>odmah kontaktirajte našu podršku</strong> na 
                    <a href='mailto:support@rasprodaja.rs'>support@rasprodaja.rs</a>.</p>
                    <p class='mb-0'>Takođe, preporučujemo da:</p>
                    <ul>
                        <li>Odmah promenite lozinku na drugim servisima gde koristite istu lozinku</li>
                        <li>Omogućite dvofaktorsku autentifikaciju ako je dostupna</li>
                        <li>Pregledate nedavne aktivnosti na vašem nalogu</li>
                    </ul>
                </div>
                
                <hr>
                <p style='font-size: 14px; color: #666;'>
                    <strong>Informacije o promeni:</strong><br>
                    ➜ Datum i vreme: " . date('d.m.Y H:i:s') . "<br>
                    ➜ IP adresa: " . ($_SERVER['REMOTE_ADDR'] ?? 'nepoznata') . "<br>
                    ➜ Browser: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'nepoznat') . "
                </p>
                
                <p>Ako imate bilo kakvih pitanja, slobodno nas kontaktirajte.</p>
                
                <p>Srdačan pozdrav,<br>
                <strong>Tim Rasprodaja.rs</strong></p>
            </div>
            <div class='footer'>
                <p>Rasprodaja.rs - Najveći oglasnik u Srbiji</p>
                <p>© " . date('Y') . " Rasprodaja.rs. Sva prava zadržana.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $plainText = "Poštovani/a {$userName},\n\n";
    $plainText .= "Vaša lozinka na Rasprodaja.rs je uspešno promenjena.\n\n";
    $plainText .= "Niste Vi promenili lozinku? Odmah kontaktirajte našu podršku na support@rasprodaja.rs\n\n";
    $plainText .= "Datum i vreme: " . date('d.m.Y H:i:s') . "\n";
    $plainText .= "IP adresa: " . ($_SERVER['REMOTE_ADDR'] ?? 'nepoznata') . "\n\n";
    $plainText .= "Srdačan pozdrav,\nTim Rasprodaja.rs";
    
    $emailSent = sendEmail($user['email'], $emailSubject, $emailHtml, $plainText);
    
    // Loguj status email-a
    if (!$emailSent) {
        error_log("Password change email not sent to: " . $user['email']);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Lozinka je uspešno promenjena.' . ($emailSent ? '' : ' Email obaveštenje nije poslato.'),
        'email_sent' => $emailSent
    ]);
    
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Došlo je do serverske greške. Pokušajte ponovo.'
    ]);
}
?>