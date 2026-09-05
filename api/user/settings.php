<?php
/**
 * api/user/settings.php - Korisnicka podesavanja (privatnost + notifikacije)
 *
 * GET  -> vraca settings JSON (+ sinhronizovane kolone users.*)
 * POST -> updejtuje DOZVOLJENE kljuceve (merge, ne overwrite celog objekta)
 *
 * Dozvoljeni kljucevi:
 *   privacy.profile_visibility : public|registered|none
 *   privacy.show_email         : bool (sinhronizuje users.show_email)
 *   privacy.show_phone         : bool
 *   privacy.message_privacy    : everyone|registered|none
 *   notifications.email_messages   : bool
 *   notifications.email_replies    : bool
 *   notifications.email_newsletter : bool (sinhronizuje users.newsletter)
 *   notifications.push_messages    : bool
 *   notifications.push_sales       : bool
 *
 * Zahteva: prijavljen korisnik + CSRF token (POST).
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$fail = function ($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
};

if (empty($_SESSION['user_id'])) {
    $fail(401, 'Niste prijavljeni.');
}
$userId = (int) $_SESSION['user_id'];
$db = getDatabaseConnection();

$defaults = [
    'privacy' => [
        'profile_visibility' => 'public',
        'show_email' => false,
        'show_phone' => false,
        'message_privacy' => 'everyone',
    ],
    'notifications' => [
        'email_messages' => true,
        'email_replies' => true,
        'email_newsletter' => true,
        'push_messages' => true,
        'push_sales' => false,
    ],
];

function loadSettings($db, $userId, $defaults) {
    $settings = $defaults;
    try {
        $stmt = $db->prepare("SELECT settings FROM user_settings WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && !empty($row['settings'])) {
            $saved = is_string($row['settings']) ? json_decode($row['settings'], true) : $row['settings'];
            if (is_array($saved)) {
                foreach ($saved as $group => $vals) {
                    if (is_array($vals) && isset($defaults[$group])) {
                        foreach ($vals as $k => $v) {
                            if (array_key_exists($k, $defaults[$group])) {
                                $settings[$group][$k] = $v;
                            }
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // tabela mozda jos nije kreirana (migracija) - vracamo defaulte
        error_log('user settings load: ' . $e->getMessage());
    }
    return $settings;
}

// ============================================
// GET
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = loadSettings($db, $userId, $defaults);
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $fail(405, 'Samo GET/POST metoda');
}

// ============================================
// POST
// ============================================
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// CSRF
$csrf = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string) $csrf)) {
    $fail(403, 'Sesija je istekla. Osvezite stranicu i pokusajte ponovo.');
}

$incoming = [];
if (isset($input['privacy']) && is_array($input['privacy'])) {
    $incoming['privacy'] = $input['privacy'];
}
if (isset($input['notifications']) && is_array($input['notifications'])) {
    $incoming['notifications'] = $input['notifications'];
}
if (empty($incoming)) {
    $fail(400, 'Nema podataka za cuvavanje.');
}

// Whitelist + sanitizacija
$clean = [];
$visc = $incoming['privacy']['profile_visibility'] ?? null;
$msgp = $incoming['privacy']['message_privacy'] ?? null;

if (isset($incoming['privacy'])) {
    $p = [];
    if (in_array($visc, ['public', 'registered', 'none'], true)) $p['profile_visibility'] = $visc;
    if (in_array($msgp, ['everyone', 'registered', 'none'], true)) $p['message_privacy'] = $msgp;
    foreach (['show_email', 'show_phone'] as $k) {
        if (array_key_exists($k, $incoming['privacy'])) {
            $p[$k] = !empty($incoming['privacy'][$k]) && $incoming['privacy'][$k] !== '0';
        }
    }
    if ($p) $clean['privacy'] = $p;
}
if (isset($incoming['notifications'])) {
    $n = [];
    foreach (['email_messages', 'email_replies', 'email_newsletter', 'push_messages', 'push_sales'] as $k) {
        if (array_key_exists($k, $incoming['notifications'])) {
            $n[$k] = !empty($incoming['notifications'][$k]) && $incoming['notifications'][$k] !== '0';
        }
    }
    if ($n) $clean['notifications'] = $n;
}

if (empty($clean)) {
    $fail(400, 'Nijedno validno polje nije poslato.');
}

try {
    // Merge sa postojecim (deep za dva nivoa)
    $current = loadSettings($db, $userId, $defaults);
    foreach ($clean as $group => $vals) {
        foreach ($vals as $k => $v) {
            $current[$group][$k] = $v;
        }
    }
    $json = json_encode($current, JSON_UNESCAPED_UNICODE);

    $stmt = $db->prepare("
        INSERT INTO user_settings (user_id, settings)
        VALUES (?, CAST(? AS JSON))
        ON DUPLICATE KEY UPDATE settings = VALUES(settings)
    ");
    $stmt->execute([$userId, $json]);

    // Sinhronizuj kolone koje drugi kodovi direktno citaju
    if (isset($clean['privacy']['show_email'])) {
        $db->prepare("UPDATE users SET show_email = ? WHERE id = ?")
           ->execute([$current['privacy']['show_email'] ? 1 : 0, $userId]);
    }
    if (isset($clean['notifications']['email_newsletter'])) {
        $db->prepare("UPDATE users SET newsletter = ? WHERE id = ?")
           ->execute([$current['notifications']['email_newsletter'] ? 1 : 0, $userId]);
    }

    echo json_encode(['success' => true, 'message' => 'Podešavanja su sačuvana.', 'settings' => $current]);

} catch (Throwable $e) {
    error_log('user settings save: ' . $e->getMessage());
    $fail(500, 'Došlo je do greške pri čuvanju. Pokušajte ponovo.');
}
