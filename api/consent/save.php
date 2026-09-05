<?php
/**
 * api/consent/save.php - Upis izbora kolačića u SQL (tabela cookie_consents)
 *
 * Radi i za goste (vezano za device UUID) i za prijavljene korisnike (user_id).
 * Nema CSRF zahteva - podatak je bezazlen i dolazi iz samog consent UI-ja.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit();
}

// JSON ili form data
$input = [];
if (!empty($_POST)) {
    $input = $_POST;
} else {
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $input = json_decode($json, true) ?: [];
    }
}

$functional = !empty($input['functional']) ? 1 : 0;
$analytics  = !empty($input['analytics'])  ? 1 : 0;
$marketing  = !empty($input['marketing'])  ? 1 : 0;

// Device token za anonimne (validan UUID)
$deviceId = isset($input['device_id']) ? trim((string) $input['device_id']) : '';
if (!preg_match('/^[a-f0-9\-]{36}$/i', $deviceId)) {
    $deviceId = null;
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
if ($userId === 0) {
    $userId = null;
}

try {
    $db = getDatabaseConnection();

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250) ?: null;

    if ($userId !== null) {
        // Prijavljeni korisnik: jedan red po korisniku (azurira se)
        $stmt = $db->prepare("
            INSERT INTO cookie_consents
                (user_id, consent_token, necessary, functional, analytics, marketing, ip_address, user_agent)
            VALUES (?, ?, 1, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                consent_token = COALESCE(VALUES(consent_token), consent_token),
                functional = VALUES(functional),
                analytics  = VALUES(analytics),
                marketing  = VALUES(marketing),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([$userId, $deviceId, $functional, $analytics, $marketing, $ip, $ua]);
    } elseif ($deviceId !== null) {
        // Gost sa poznatim uredjajem: jedan red po uredjaju
        $stmt = $db->prepare("
            INSERT INTO cookie_consents
                (user_id, consent_token, necessary, functional, analytics, marketing, ip_address, user_agent)
            VALUES (NULL, ?, 1, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                functional = VALUES(functional),
                analytics  = VALUES(analytics),
                marketing  = VALUES(marketing),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");
        $stmt->execute([$deviceId, $functional, $analytics, $marketing, $ip, $ua]);
    } else {
        // Nema ni korisnika ni uredjaja - samo log, upasti necemo
        error_log('cookie consent bez identiteta: ' . json_encode([$ip, $functional, $analytics, $marketing]));
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('cookie consent save: ' . $e->getMessage());
    // Klijent ne mora da zna razlog - consent je i dalje validan lokalno
    echo json_encode(['success' => true, 'synced' => false]);
}
