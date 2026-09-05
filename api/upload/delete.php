<?php
/**
 * api/upload/delete.php - Brisanje slike oglasa (red iz ad_images + fajlovi na disku)
 *
 * Fajl je ranije bio PRASAN (0 bajtova). Sada radi, sa:
 *  - proverom vlasništva oglasa
 *  - CSRF tokenom
 *  - brisanjem originala/thumbnail/medium fajlova
 *  - promocijom prve preostale slike u "glavnu" ako smo obrisali glavnu
 *
 * POST (JSON ili form): image_id (int), csrf_token
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$fail = function ($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail(405, 'Samo POST metoda');
}
if (empty($_SESSION['user_id'])) {
    $fail(401, 'Morate biti prijavljeni');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
if (!checkCSRFToken($input)) {
    $fail(403, 'Sesija je istekla. Osvezite stranicu i pokusajte ponovo.');
}

$imageId = (int) ($input['image_id'] ?? 0);
if ($imageId <= 0) {
    $fail(400, 'Nedostaje ID slike.');
}

try {
    $db = getDatabaseConnection();

    $stmt = $db->prepare("
        SELECT ai.*, a.user_id AS ad_owner, a.status AS ad_status
        FROM ad_images ai
        JOIN ads a ON a.id = ai.ad_id
        WHERE ai.id = ?
        LIMIT 1
    ");
    $stmt->execute([$imageId]);
    $img = $stmt->fetch();

    if (!$img) {
        $fail(404, 'Slika nije pronadjena.');
    }
    if ((int) $img['ad_owner'] !== (int) $_SESSION['user_id']) {
        $fail(403, 'Nemate pravo da obrišete ovu sliku.');
    }

    $db->beginTransaction();

    // 1) obrisaj red
    $db->prepare("DELETE FROM ad_images WHERE id = ?")->execute([$imageId]);

    // 2) obrisaj fajlove (putanje su relativne: /assets/uploads/ads/ID/...)
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? ROOT_PATH;
    foreach (['image_path', 'thumbnail_path', 'medium_path'] as $col) {
        $rel = $img[$col] ?? null;
        if (!$rel) continue;
        $path = $docRoot . '/' . ltrim((string) $rel, '/');
        // safety: dozvoljeno brisati SAMO unutar assets/uploads/
        $real = realpath($path);
        $allowedBase = realpath($docRoot . '/assets/uploads');
        if ($real !== false && $allowedBase !== false && strpos($real, $allowedBase . DIRECTORY_SEPARATOR) === 0) {
            @unlink($real);
        }
    }

    // 3) ako smo obrisali glavnu sliku, promocija prve preostale
    if (!empty($img['is_main'])) {
        $stmt = $db->prepare("
            SELECT id FROM ad_images
            WHERE ad_id = ?
            ORDER BY display_order ASC, id ASC
            LIMIT 1
        ");
        $stmt->execute([(int) $img['ad_id']]);
        $next = $stmt->fetch();
        if ($next) {
            $db->prepare("UPDATE ad_images SET is_main = 1 WHERE id = ?")
               ->execute([(int) $next['id']]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Slika je obrisana.']);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('upload/delete: ' . $e->getMessage());
    $fail(500, 'Greska pri brisanju slike.');
}
