<?php
/**
 * api/package/upgrade.php - Nadogradnja paketa
 * SA SLANJEM PREDRAČUNA (PDF)
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/packages.php';

// Proveri da li je korisnik ulogovan
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Morate biti prijavljeni'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];

// Proveri CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'CSRF token validation failed'
    ]);
    exit();
}

// Proveri da li je prosleđen ID paketa
if (!isset($_POST['package_id']) || empty($_POST['package_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'ID paketa nije prosleđen'
    ]);
    exit();
}

$packageId = intval($_POST['package_id']);
$period = isset($_POST['period']) && $_POST['period'] === 'yearly' ? 'yearly' : 'monthly';

// Dohvati reference_number iz POST-a
$referenceNumber = isset($_POST['reference_number']) ? $_POST['reference_number'] : null;

// Uvek je bankovni račun
$paymentMethod = 'bank';

try {
    $db = getDatabaseConnection();
    
    // Dohvati trenutni paket korisnika
    $currentPackage = getUserCurrentPackage($userId);
    
    if (!$currentPackage) {
        $currentPackage = [
            'id' => 1,
            'name' => 'Free',
            'price_monthly' => 0
        ];
    }
    
    // Dohvati novi paket
    $newPackage = getPackageById($packageId);
    
    if (!$newPackage) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Paket nije pronađen'
        ]);
        exit();
    }
    
    // Proveri da li je paket aktivan
    if (!$newPackage['is_active']) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Ovaj paket trenutno nije dostupan'
        ]);
        exit();
    }
    
    // Proveri da li je nadogradnja moguća
    if (strtolower($currentPackage['name']) === strtolower($newPackage['name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Već imate ovaj paket'
        ]);
        exit();
    }
    
    // Ako je Free, ne treba naplata
    if (strtolower($newPackage['name']) === 'free') {
        // Samo prebaci na Free
        $stmt = $db->prepare("UPDATE users SET package = 'free', package_expires_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Uspešno ste prešli na Free paket.',
            'redirect' => '/packages'
        ]);
        exit();
    }
    
    // Izračunaj cenu
    $price = ($period === 'yearly') ? $newPackage['price_yearly'] : $newPackage['price_monthly'];
    
    // Generiši transaction_id
    $transactionId = $referenceNumber;
    
    // Detalji transakcije
    $paymentDetails = [
        'bank_name' => 'RAIFFEISEN BANKA',
        'account_number' => '265-1100310108783-08',
        'reference_number' => $referenceNumber,
        'period' => $period,
        'amount' => $price,
        'currency' => 'RSD'
    ];
    
    // Kreiraj transakciju sa statusom 'pending'
    $expiryMonths = ($period === 'yearly') ? 12 : 1;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMonths months"));
    
    $stmt = $db->prepare("
        INSERT INTO transactions (
            user_id, package_id, amount, period, payment_method, 
            status, transaction_id, payment_details, expires_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)
    ");
    
    $paymentDetailsJson = json_encode($paymentDetails);
    
    $stmt->execute([
        $userId,
        $packageId,
        $price,
        $period,
        $paymentMethod,
        $transactionId,
        $paymentDetailsJson,
        $expiresAt
    ]);
    
    $transactionDbId = $db->lastInsertId();
    
    // ============================================
    // 🔥 GENERIŠI I POŠALJI PREDRAČUN (PDF)
    // ============================================
    $predracunSent = false;
    
    if (function_exists('sendPredracunEmail')) {
        // Dohvati podatke o korisniku
        $user = getUserById($userId);
        
        if ($user) {
            // Pripremi podatke za transakciju
            $transactionData = [
                'id' => $transactionDbId,
                'amount' => $price,
                'period' => $period,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ];
            
            // Pošalji predračun
            $predracunSent = sendPredracunEmail(
                $user,
                $newPackage,
                $transactionData,
                $referenceNumber
            );
            
            if ($predracunSent) {
                error_log("✅ Predračun poslat korisniku: $userId");
            } else {
                error_log("❌ Greška pri slanju predračuna za: $userId");
            }
        }
    } else {
        // Fallback - pošalji običan email sa podacima za uplatu
        error_log("⚠️ Funkcija sendPredracunEmail ne postoji, šaljem običan email");
        $emailSent = sendPackagePaymentEmail(
            $userId, 
            $packageId, 
            $price, 
            $period, 
            $referenceNumber
        );
        
        if (!$emailSent) {
            error_log("Warning: Payment email could not be sent to user $userId");
        }
    }
    
    // Loguj aktivnost
    logUserActivity($userId, 'package_request', [
        'package_id' => $packageId,
        'package_name' => $newPackage['name'],
        'period' => $period,
        'amount' => $price,
        'transaction_id' => $transactionId,
        'reference_number' => $referenceNumber,
        'predracun_sent' => $predracunSent,
        'status' => 'pending'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => $predracunSent 
            ? 'Zahtev za paket je poslat. Predračun je poslat na Vaš email.'
            : 'Zahtev za paket je poslat. Na Vaš email su poslati podaci za uplatu.',
        'predracun_sent' => $predracunSent,
        'package' => [
            'id' => $newPackage['id'],
            'name' => $newPackage['name'],
            'period' => $period,
            'price' => $price
        ],
        'transaction' => [
            'id' => $transactionDbId,
            'transaction_id' => $transactionId,
            'reference_number' => $referenceNumber,
            'status' => 'pending'
        ],
        'bank_details' => [
            'bank' => 'RAIFFEISEN BANKA',
            'account' => '265-1100310108783-08',
            'reference' => $referenceNumber,
            'amount' => $price,
            'currency' => 'RSD'
        ],
        'redirect' => '/packages'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Greška: ' . $e->getMessage()
    ]);
    error_log("Package upgrade error: " . $e->getMessage());
}