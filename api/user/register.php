<?php
/**
 * api/user/register.php - ISPRAVLJENA VERZIJA SA EMAIL VERIFIKACIJOM I TIPOM NALOGA
 */

// START SESSION NA POČETKU
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// DODAJ CORS HEADERS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// UČITAJ KONFIGURACIJU
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
        $inputData = json_decode($json, true);
    }
}

// HONEYPOT VERIFIKACIJA - PRVA LINIJA ODBRANE
if (!empty($inputData['ime'])) {  
    // Bot detektovan
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste se registrovali!'
    ]);
    exit;
}




// VALIDACIJA POLJA
$errors = [];
$required = ['username', 'email', 'password', 'confirm_password', 'phone', 'city'];

// Dodatna validacija za tip naloga
$accountType = $inputData['account_type'] ?? 'private';

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

// EMAIL VALIDACIJA
if (!empty($inputData['email']) && !filter_var($inputData['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email adresa nije validna.";
}

// LOZINKE SE POKLAPAJU
if (!empty($inputData['password']) && !empty($inputData['confirm_password']) && 
    $inputData['password'] !== $inputData['confirm_password']) {
    $errors[] = "Lozinke se ne poklapaju.";
}

// PROVERI DUŽINU LOZINKE
if (!empty($inputData['password']) && strlen($inputData['password']) < 8) {
    $errors[] = "Lozinka mora imati najmanje 8 karaktera.";
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
    
    // PROVERI DA LI KORISNIČKO IME VEĆ POSTOJI
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$inputData['username'], $inputData['email']]);
    
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
    $firstName = $inputData['first_name'] ?? '';
    $lastName = $inputData['last_name'] ?? '';
    $companyName = ($accountType === 'company') ? ($inputData['company_name'] ?? '') : null;
    $normalizedPhone = normalizePhoneNumber($inputData['phone']);
    // KREIRAJ KORISNIKA
    $stmt = $db->prepare("
        INSERT INTO users (
            username, email, password_hash, 
            first_name, last_name, phone, city,
            account_type, company_name,
            verification_token, verification_expires,
            is_verified, package, newsletter, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'Free', ?, NOW())
    ");
    
    $newsletter = isset($inputData['newsletter']) ? 1 : 0;
    
    $result = $stmt->execute([
        $inputData['username'],
        $inputData['email'],
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
    
    if (!$result) {
        throw new Exception("Greška pri kreiranju korisnika.");
    }
    
    $userId = $db->lastInsertId();
    
    // POŠALJI VERIFIKACIONI EMAIL
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $verificationLink = $siteUrl . '/?page=verify-email&token=' . $verificationToken;
    
    // Prikaz imena za email (naziv firme ili ime+prezime)
    if ($accountType === 'company' && !empty($companyName)) {
        $userName = $companyName;
    } else {
        $userName = trim($firstName . ' ' . $lastName);
        if (empty($userName)) {
            $userName = $inputData['username'];
        }
    }
    
    $emailContent = generateVerificationEmail($userName, $verificationLink);
    
    // Pošalji email
    $emailSent = sendEmail(
        $inputData['email'],
        'Verifikujte Vaš nalog na Rasprodaja.rs',
        $emailContent
    );
    
    // VRATI USPEH
    echo json_encode([
        'success' => true,
        'message' => 'Uspešno ste registrovali nalog! Molimo proverite email za verifikaciju.',
        'user_id' => $userId,
        'account_type' => $accountType,
        'email_sent' => $emailSent,
        'redirect' => '/login'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška pri registraciji: ' . $e->getMessage()
    ]);
    error_log("Registration error: " . $e->getMessage());
}
?>