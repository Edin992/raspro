<?php
/**
 * api/user/register.php - Registracija korisnika
 *
 * IZMENE:
 *  - reCAPTCHA v3 provera (action: 'register')
 *  - CSRF provera (forma vec slavi csrf_token, samo nije proveravan)
 *  - validacija korisnickog imena i emaila na SERVERU (ne samo u JS)
 *  - validacija tipa naloga (account_type)
 *  - visestruki insert -> transaction
 *  - interni exception message se ne salje klijentu
 *  - vise se ne vraca user_id u odgovoru (nepotreban curenje informacija)
 */

header('Content-Type: application/json; charset=utf-8');

// NAPOMENKA: stari kod je slao "Access-Control-Allow-Origin: *" — suvisno
// (forma i API su na istom domenu), a " *" je opasno ako endpoint ikad primi
// poverljive podatke. Sklonjeno.

// Handle preflight OPTIONS request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// UČITAJ KONFIGURACIJU
// (database.php -> constants.php pokreće sesiju sa HttpOnly/SameSite kolacicima,
//  pa ne zovemo session_start() rucno pre include-a)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// SAMO POST METODA
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'message' => 'Samo POST metoda je dozvoljena'
    ]);
    exit();
}

// PROVERI DA LI JE JSON ILI FORM DATA
$inputData = [];
if (!empty($_POST)) {
    $inputData = $_POST;
} else {
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $inputData = json_decode($json, true) ?: [];
    }
}

$fail = function ($code, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit();
};

// ============================================
// ANTI-BOT: HONEYPOT - PRVA LINIJA ODBRANE
// ============================================
if (!empty($inputData['ime'])) {
    // Bot detektovan - glumi uspeh da bot ne promeni taktiku
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste se registrovali!'
    ]);
    exit;
}

// ============================================
// ANTI-BOT: RECAPTCHA v3
// ============================================
$recaptcha = recaptcha_check_submission($inputData, 'register');
if (!$recaptcha['success']) {
    $fail(422, $recaptcha['message'] ?: 'reCAPTCHA provera nije uspela.', ['recaptcha_failed' => true]);
}

// ============================================
// CSRF
// ============================================
$csrfToken = $inputData['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrfToken)) {
    $fail(403, 'Sesija je istekla. Osvežite stranicu i pokušajte ponovo.');
}

// ============================================
// VALIDACIJA POLJA
// ============================================
$errors = [];
$required = ['username', 'email', 'password', 'confirm_password', 'phone', 'city'];

// Dodatna validacija za tip naloga
$accountType = $inputData['account_type'] ?? 'private';
if (!in_array($accountType, ['private', 'company'], true)) {
    $errors[] = "Neispravan tip naloga.";
    $accountType = 'private';
}

if ($accountType === 'company') {
    // Za firmu: obavezan je naziv firme
    $required[] = 'company_name';
    // Ime i prezime nisu obavezni za firmu (ali ih čuvamo ako korisnik unese)
} else {
    // Za privatno lice: obavezni su ime i prezime
    $required[] = 'first_name';
    $required[] = 'last_name';
}

foreach ($required as $field) {
    if (empty($inputData[$field])) {
        $errors[] = "Polje '$field' je obavezno.";
    }
}

// USERNAME - isto pravilo kao u HTML (3-30, slova/brojevi/donja crta)
if (!empty($inputData['username']) && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $inputData['username'])) {
    $errors[] = "Korisničko ime sme imati 3-30 karaktera (samo slova, brojevi i donja crta).";
}

// EMAIL VALIDACIJA
if (!empty($inputData['email'])) {
    if (!filter_var($inputData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email adresa nije validna.";
    } elseif (strlen($inputData['email']) > 190) {
        $errors[] = "Email adresa je predugačka.";
    }
}

// LOZINKE SE POKLAPAJU
if (!empty($inputData['password']) && !empty($inputData['confirm_password']) && 
    $inputData['password'] !== $inputData['confirm_password']) {
    $errors[] = "Lozinke se ne poklapaju.";
}

// USLOVI KORISCENJA - obavezni i na serveru (ne samo u JS)
// FIX: ranije se postojanje checkbox-a proveravalo SAMO u browseru
if (empty($inputData['terms'])) {
    $errors[] = "Morate prihvatiti uslove korišćenja.";
}

// PROVERI DUŽINU LOZINKE
if (!empty($inputData['password'])) {
    if (strlen($inputData['password']) < 8) {
        $errors[] = "Lozinka mora imati najmanje 8 karaktera.";
    } elseif (strlen($inputData['password']) > 255) {
        $errors[] = "Lozinka je predugačka.";
    }
}

// AKO IMA GREŠAKA, VRATI IH
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validacione greške',
        'errors' => $errors
    ]);
    exit();
}

// POVEŽI SE SA BAZOM
try {
    $db = getDatabaseConnection();
    
    $username = trim($inputData['username']);
    $email = strtolower(trim($inputData['email']));
    
    // PROVERI DA LI KORISNIČKO IME VEĆ POSTOJI
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Korisničko ime ili email već postoje'
        ]);
        exit();
    }
    
    // HASH LOZINKE
    $passwordHash = password_hash($inputData['password'], PASSWORD_DEFAULT);
    
    // GENERIŠI VERIFIKACIONI TOKEN
    $verificationToken = bin2hex(random_bytes(16));
    $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Pripremi podatke za unos
    $firstName = trim($inputData['first_name'] ?? '');
    $lastName = trim($inputData['last_name'] ?? '');
    $companyName = ($accountType === 'company') ? trim($inputData['company_name'] ?? '') : null;
    $normalizedPhone = normalizePhoneNumber($inputData['phone']);
    $newsletter = !empty($inputData['newsletter']) ? 1 : 0;
    
    // KREIRAJ KORISNIKA (u transakciji)
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("
            INSERT INTO users (
                username, email, password_hash, 
                first_name, last_name, phone, city,
                account_type, company_name,
                verification_token, verification_expires,
                is_verified, package, newsletter, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Free', ?, NOW())
        ");
        
        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            $firstName,
            $lastName,
            $normalizedPhone,
            $inputData['city'],
            $accountType,
            $companyName,
            $verificationToken,
            $verificationExpires,
            $newsletter
        ]);
        
        $userId = (int) $db->lastInsertId();
        $db->commit();
    } catch (Throwable $insertError) {
        $db->rollBack();
        // 23000 = duplicate key (race condition izmedju provere i insert-a)
        if ($insertError instanceof PDOException && $insertError->getCode() === '23000') {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Korisničko ime ili email već postoje'
            ]);
            exit();
        }
        throw $insertError;
    }
    
    // POŠALJI VERIFIKACIONI EMAIL (ako slanje padne, nalog ipak postoji)
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $verificationLink = $siteUrl . '/?page=verify-email&token=' . $verificationToken;
    
    // Prikaz imena za email (naziv firme ili ime+prezime)
    if ($accountType === 'company' && !empty($companyName)) {
        $userName = $companyName;
    } else {
        $userName = trim($firstName . ' ' . $lastName);
        if (empty($userName)) {
            $userName = $username;
        }
    }
    
    $emailContent = generateVerificationEmail($userName, $verificationLink);
    
    // Pošalji email
    $emailSent = sendEmail(
        $email,
        'Verifikujte Vaš nalog na Rasprodaja.rs',
        $emailContent
    );
    if (!$emailSent) {
        error_log('Verification email slanje NEUSPEŠNO za user_id=' . $userId);
    }
    
    // VRATI USPEH
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste registrovali nalog! Molimo proverite email za verifikaciju.',
        'account_type' => $accountType,
        'email_sent' => (bool) $emailSent,
        'redirect' => '/login'
    ]);
    
} catch (Throwable $e) {
    // Detalji samo u log!
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri registraciji. Pokušajte ponovo.'
    ]);
}
