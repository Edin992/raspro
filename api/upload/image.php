<?php
/**
 * api/upload/image.php - Upload slike za postojeci oglas
 *
 * Fajl je ranije bio PRASAN (0 bajtova) i endpoint je vrsio 500.
 * Sada je kompletan i koristi uploadAdImage() iz includes/functions.php
 * (validacija tipa/velicine, thumbnail+medium, upis u ad_images).
 *
 * POST (multipart/form-data):
 *   ad_id   (int, obavezno)
 *   image   (file, polje 'image' ili 'images[]' - prva slika)
 *   is_main (opciono 1)
 *   csrf_token
 */

require_once __DIR__ . '/../../config/database.php'; // sesija + konstante + recaptcha helper
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$fail = function ($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
};

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail(405, 'Samo POST metoda');
}
if (empty($_SESSION['user_id'])) {
    $fail(401, 'Morate biti prijavljeni');
}
if (!checkCSRFToken($_POST)) {
    $fail(403, 'Sesija je istekla. Osvezite stranicu i pokusajte ponovo.');
}

$userId = (int) $_SESSION['user_id'];
$adId = (int) ($_POST['ad_id'] ?? 0);
$isMain = !empty($_POST['is_main']);

if ($adId <= 0) {
    $fail(400, 'Nedostaje ID oglasa.');
}

try {
    $db = getDatabaseConnection();

    // Vlasnistvo + status
    $stmt = $db->prepare("SELECT id, user_id, status FROM ads WHERE id = ? LIMIT 1");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();
    if (!$ad || (int)$ad['user_id'] !== $userId) {
        $fail(403, 'Nemate pravo da menjate ovaj oglas.');
    }
    if ($ad['status'] === 'deleted') {
        $fail(400, 'Oglas je obrisan.');
    }

    // Limit broja slika
    $stmt = $db->prepare("SELECT COUNT(*) FROM ad_images WHERE ad_id = ?");
    $stmt->execute([$adId]);
    if ((int) $stmt->fetchColumn() >= MAX_IMAGES_PER_AD) {
        $fail(422, 'Maksimalno ' . MAX_IMAGES_PER_AD . ' slika po oglasu.');
    }

    // Uzmi prvi fajl iz 'image' ili 'images[]'
    $file = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
    } elseif (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $i => $n) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $n,
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
                break;
            }
        }
    }
    if (!$file) {
        $fail(400, 'Fajl nije poslat ili je doslo do greske pri uploadu.');
    }

    // Extra hardening pre helpera: pravi MIME iz sadrzaja (ne verujemo 'type')
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($detected, ALLOWED_IMAGE_TYPES, true)) {
            $fail(422, 'Nije dozvoljen fajl tip: ' . $detected);
        }
        $file['type'] = $detected;
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $fail(422, 'Maksimalna velicina fajla je ' . round(MAX_UPLOAD_SIZE / 1048576, 1) . ' MB.');
    }

    $result = uploadAdImage($adId, $file, $isMain);
    if (!$result) {
        $fail(500, 'Upload nije uspeo. Pokusajte sa drugom slikom.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Slika je otpremljena.',
        'image' => $result,
    ]);

} catch (Throwable $e) {
    error_log('upload/image: ' . $e->getMessage());
    $fail(500, 'Greska pri otpremanju.');
}
