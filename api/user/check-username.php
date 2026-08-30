<?php
/**
 * api/user/check-username.php - Provera dostupnosti username
 * ISPRAVLJENA VERZIJA
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Samo POST metoda'
    ]);
    exit();
}

// Pročitaj JSON input
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';

if (empty($username)) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Korisničko ime je obavezno'
    ]);
    exit();
}

if (strlen($username) < 3) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Korisničko ime mora imati najmanje 3 karaktera'
    ]);
    exit();
}

// Proveri dozvoljene karaktere
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Dozvoljena samo slova, brojevi i donja crta'
    ]);
    exit();
}

try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'available' => false,
            'message' => 'Korisničko ime je već zauzeto'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'available' => true,
            'message' => 'Korisničko ime je dostupno'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Check username error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'available' => false,
        'message' => 'Greška pri proveri'
    ]);
}
?>