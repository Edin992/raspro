<?php
/**
 * includes/remember-me.php - "Zapamti me" prijava
 *
 * Token cuva u kolacicu `raspro_remember` u formatu "userId:token",
 * a u bazi se cuva SAMO SHA-256 hash tokena (curi-li kolacic, kradi se samo
 * nalog sa 30-dnevnim vazenjem, a rotacijom se svaki token koristi jednom).
 *
 * Potrebna tabela: remember_tokens (vidi sql/update-2026-09-features.sql)
 */

define('REMEMBER_COOKIE', 'raspro_remember');
define('REMEMBER_DAYS', 30);

/**
 * Koliko dugo vazi "zapamti me" (iz borde settings-a ako postoji)
 */
function rememberMeCookieOpts() {
    return [
        'expires'  => time() + REMEMBER_DAYS * 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/**
 * Pozvati ODMAH nakon uspesne prijave ako je korisnik ckirao "Zapamti me".
 * Ako ckira iskljucena - brise se postojeci token (da se ne "zaboravi" stari).
 */
function rememberMeIssue($userId, $rememberRequested = true) {
    $userId = (int) $userId;
    if ($userId <= 0) {
        return;
    }
    try {
        $db = getDatabaseConnection();
        // Uvek ocisti stare tokene ovog korisnika - samo jedan aktivan "device"
        $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([$userId]);

        if (!$rememberRequested) {
            // Gasi postojecki kolacic
            if (isset($_COOKIE[REMEMBER_COOKIE])) {
                setcookie(REMEMBER_COOKIE, '', array_merge(rememberMeCookieOpts(), ['expires' => time() - 3600]));
            }
            return;
        }

        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);
        $stmt = $db->prepare("
            INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_ip, created_ua)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . (int) REMEMBER_DAYS . " DAY), ?, ?)
        ");
        $stmt->execute([
            $userId,
            $hash,
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250) ?: null
        ]);

        setcookie(REMEMBER_COOKIE, $userId . ':' . $token, rememberMeCookieOpts());
    } catch (Throwable $e) {
        //remember_me je opciona udobnost - ne prekida login
        error_log('rememberMeIssue: ' . $e->getMessage());
    }
}

/**
 * Auto-login iz kolacica. Pozivlje se iz config/database.php za svaki zahtev.
 */
function rememberMeTryLogin() {
    if (php_sapi_name() === 'cli') {
        return; // cron ne treba kolacice
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    if (!empty($_SESSION['user_id'])) {
        return; // vec prijavljen
    }
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';
    if ($cookie === '' || substr_count($cookie, ':') !== 1) {
        return;
    }
    [$uidPart, $token] = explode(':', $cookie, 2);
    $userId = (int) $uidPart;
    if ($userId <= 0 || strlen($token) < 32 || strlen($token) > 128) {
        return;
    }

    try {
        $db = getDatabaseConnection();

        $hash = hash('sha256', $token);
        $stmt = $db->prepare("
            SELECT rt.id AS token_id, u.id, u.username, u.email, u.package, u.is_verified
            FROM remember_tokens rt
            JOIN users u ON u.id = rt.user_id
            WHERE rt.user_id = ? AND rt.token_hash = ? AND rt.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$userId, $hash]);
        $row = $stmt->fetch();

        if (!$row || !$row['is_verified']) {
            // Nevazeci / istekli token -> brisi kolacic
            setcookie(REMEMBER_COOKIE, '', array_merge(rememberMeCookieOpts(), ['expires' => time() - 3600]));
            return;
        }

        // === Rotacija tokena: stari se baca, novi se izdaje ===
        $newToken = bin2hex(random_bytes(32));
        $newHash  = hash('sha256', $newToken);
        $db->prepare("UPDATE remember_tokens SET token_hash = ?, last_used_at = NOW() WHERE id = ?")
           ->execute([$newHash, $row['token_id']]);
        setcookie(REMEMBER_COOKIE, $userId . ':' . $newToken, rememberMeCookieOpts());

        session_regenerate_id(true); // anti session-fixation pri auto-loginu
        $_SESSION['user_id']       = $row['id'];
        $_SESSION['user_name']     = $row['username'];
        $_SESSION['user_email']    = $row['email'];
        $_SESSION['user_package']  = $row['package'];
        $_SESSION['is_verified']   = true;
        $_SESSION['remember_me']   = true;
        $_SESSION['last_activity'] = time();
    } catch (Throwable $e) {
        error_log('rememberMeTryLogin: ' . $e->getMessage());
    }
}

/**
 * Brisanje tokena + kolacica (logout, promena lozinke).
 */
function rememberMeClear($userId = null) {
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        setcookie(REMEMBER_COOKIE, '', array_merge(rememberMeCookieOpts(), ['expires' => time() - 3600]));
    }
    if (!empty($userId)) {
        try {
            $db = getDatabaseConnection();
            $db->prepare("DELETE FROM remember_tokens WHERE user_id = ?")->execute([(int) $userId]);
        } catch (Throwable $e) {
            error_log('rememberMeClear: ' . $e->getMessage());
        }
    }
}
