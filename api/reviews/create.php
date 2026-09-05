<?php
/**
 * api/reviews/create.php - Ocena prodavca/kupca nakon komunikacije
 *
 * POST JSON: { conversation_id, rating (1-5), title?, comment }
 *
 * Pravila:
 *  - korisnik mora biti ucesnik konverzacije
 *  - mora postojati bar po 1 poruka sa SVAKE strane (stvarna komunikacija)
 *  - ne sme se oceniti samog sebe
 *  - jedan pregled = jedna recenzija (unique reviewer+target+conversation)
 *  - ocenjeni korisnik mora biti verifikovan
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$fail = function ($code, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit();
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $fail(405, 'Samo POST metoda');
}

if (empty($_SESSION['user_id'])) {
    $fail(401, 'Morate biti prijavljeni da biste ostavili recenziju.');
}

// CSRF (forma salje i preko headera i preko polja)
$csrf = '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$csrf = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $csrf)) {
    $fail(403, 'Sesija je istekla. Osvezite stranicu i pokusajte ponovo.');
}

$userId = (int) $_SESSION['user_id'];
$conversationId = (int) ($input['conversation_id'] ?? 0);
$rating   = (int) ($input['rating'] ?? 0);
$title    = trim((string) ($input['title'] ?? ''));
$comment  = trim((string) ($input['comment'] ?? ($input['text'] ?? '')));

// ============================================
// VALIDACIJA
// ============================================
if ($conversationId <= 0) {
    $fail(400, 'Nedostaje ID razgovora.');
}
if ($rating < 1 || $rating > 5) {
    $fail(400, 'Ocena mora biti izmedju 1 i 5 zvezdica.');
}
if ($comment === '') {
    $fail(400, 'Kratko obrazlozenje je obavezno.');
}
if (mb_strlen($comment) > 1000) {
    $fail(400, 'Obrazlozenje je predugo (max 1000 karaktera).');
}
if (mb_strlen($title) > 100) {
    $title = mb_substr($title, 0, 100);
}
// ocisti HTML - recenzije su cist tekst sa novim redovima
$comment = strip_tags($comment);
$title   = strip_tags($title);

try {
    $db = getDatabaseConnection();

    // ============================================
    // KONVERZACIJA - da li ucestvujem?
    // ============================================
    $stmt = $db->prepare("
        SELECT c.id, c.user1_id, c.user2_id, c.ad_id,
               COALESCE(a.user_id, 0) as ad_owner_id
        FROM conversations c
        LEFT JOIN ads a ON a.id = c.ad_id
        WHERE c.id = ? AND (c.user1_id = ? OR c.user2_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$conversationId, $userId, $userId]);
    $conv = $stmt->fetch();

    if (!$conv) {
        $fail(404, 'Razgovor nije pronađen.');
    }

    $otherUserId = ($conv['user1_id'] == $userId)
        ? (int) $conv['user2_id']
        : (int) $conv['user1_id'];

    if ($otherUserId === $userId || $otherUserId <= 0) {
        $fail(400, 'Ne možete oceniti sami sebe.');
    }

    // ============================================
    // USLOV: obostrana komunikacija (bar 1 poruka sa svake strane)
    // ============================================
    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN sender_id = ? THEN 1 ELSE 0 END) as my_msgs,
            SUM(CASE WHEN sender_id = ? THEN 1 ELSE 0 END) as their_msgs
        FROM messages
        WHERE conversation_id = ?
    ");
    $stmt->execute([$userId, $otherUserId, $conversationId]);
    $counts = $stmt->fetch();
    if ((int) ($counts['my_msgs'] ?? 0) < 1 || (int) ($counts['their_msgs'] ?? 0) < 1) {
        $fail(422, 'Ocena je moguća tek kada razmenite bar po jednu poruku.', ['reason' => 'no_exchange']);
    }

    // ============================================
    // OCENJENI KORISNIK - mora postojati i biti verifikovan
    // ============================================
    $stmt = $db->prepare("SELECT id, username, is_verified FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$otherUserId]);
    $reviewed = $stmt->fetch();
    if (!$reviewed || !$reviewed['is_verified']) {
        $fail(422, 'Ovaj korisnik se ne može oceniti.', ['reason' => 'target_not_verifiable']);
    }

    // ============================================
    // DUPRIKAT - jedna recenzija po razgovoru
    // ============================================
    $stmt = $db->prepare("
        SELECT id FROM user_reviews
        WHERE reviewer_id = ? AND user_id = ? AND conversation_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId, $otherUserId, $conversationId]);
    if ($stmt->fetch()) {
        $fail(409, 'Vi ste već ocenili ovaj razgovor.', ['reason' => 'already_reviewed']);
    }

    // ============================================
    // TIP: koga ocenjujem (prodavca ako je on vlasnik oglasa, kupca inace)
    // ============================================
    $reviewType = ($otherUserId === (int) $conv['ad_owner_id']) ? 'seller' : 'buyer';

    // UUID za user_reviews.UUID (char(36) NOT NULL)
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $stmt = $db->prepare("
        INSERT INTO user_reviews
            (UUID, reviewer_id, user_id, ad_id, conversation_id, review_type,
             rating, title, `COMMENT`, is_approved, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([
        $uuid,
        $userId,
        $otherUserId,
        $conv['ad_id'] ?: null,
        $conversationId,
        $reviewType,
        $rating,
        $title !== '' ? $title : null,
        $comment
    ]);
    $reviewId = (int) $db->lastInsertId();

    // ============================================
    // NOTIFIKACIJA ocenjenom korisniku
    // ============================================
    $stmt = $db->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $me = $stmt->fetch();
    notifyUser(
        $otherUserId,
        'review',
        'Nova ocena ' . $rating . '/5',
        '@' . ($me['username'] ?? 'Korisnik') . ' vas je ocenio: ' . mb_substr($comment, 0, 80) . (mb_strlen($comment) > 80 ? '...' : ''),
        ['reviewer_id' => $userId, 'review_id' => $reviewId, 'rating' => $rating]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Hvala! Vaša ocena je sačuvana.',
        'review' => [
            'id' => $reviewId,
            'rating' => $rating,
            'review_type' => $reviewType === 'seller' ? 'seller' : 'general',
        ]
    ]);

} catch (Throwable $e) {
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        $fail(409, 'Vi ste već ocenili ovaj razgovor.', ['reason' => 'already_reviewed']);
    }
    error_log('review create: ' . $e->getMessage());
    $fail(500, 'Došlo je do greške. Pokušajte ponovo.');
}
