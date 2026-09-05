<?php
/**
 * includes/recaptcha.php - Google reCAPTCHA v3 helper (login, register, ...)
 *
 * Koriscenje:
 *  - Frontend: u stranici pozvati recaptcha_render_scripts();
 *  - JS: window.RecaptchaHelper.getToken('login') vraca token za slanje
 *  - API: $check = recaptcha_check_submission($input, 'login');
 *
 * Ako su RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY prazni,
 * provera se preskace (sajt radi normalno dok ne unesete kljuceve).
 */

if (!defined('RECAPTCHA_SITE_KEY')) {
    // Fallback ako constants.php nije ucitan
    define('RECAPTCHA_SITE_KEY', '');
    define('RECAPTCHA_SECRET_KEY', '');
    define('RECAPTCHA_MIN_SCORE', 0.5);
}

/**
 * Da li je reCAPTCHA konfigurisan (oba kljuca popunjena)?
 */
function recaptcha_is_enabled() {
    return !empty(RECAPTCHA_SITE_KEY) && !empty(RECAPTCHA_SECRET_KEY);
}

/**
 * Publicni config koji se salje u JS
 */
function recaptcha_get_public_config() {
    return [
        'enabled'  => recaptcha_is_enabled(),
        'siteKey'  => recaptcha_is_enabled() ? RECAPTCHA_SITE_KEY : '',
        'minScore' => defined('RECAPTCHA_MIN_SCORE') ? (float) RECAPTCHA_MIN_SCORE : 0.5,
    ];
}

/**
 * Ispisuje <script> za reCAPTCHA v3 + globalni window.RECAPTCHA.
 * Vraca prazan string ako reCAPTCHA nije ukljucena.
 */
function recaptcha_render_scripts() {
    if (!recaptcha_is_enabled()) {
        return '';
    }
    $siteKey = htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8');
    $cfg = json_encode(recaptcha_get_public_config());
    return '<script src="https://www.google.com/recaptcha/api.js?render=' . $siteKey . '"></script>'
         . "<script>window.RECAPTCHA = {$cfg};</script>";
}

/**
 * Izvlacenje tokena iz input-a (podrzava i g-recaptcha-response za v2).
 */
function recaptcha_extract_token($input = []) {
    if (is_array($input)) {
        if (!empty($input['recaptcha_token'])) {
            return (string) $input['recaptcha_token'];
        }
        if (!empty($input['g-recaptcha-response'])) {
            return (string) $input['g-recaptcha-response'];
        }
    }
    if (!empty($_POST['g-recaptcha-response'])) {
        return (string) $_POST['g-recaptcha-response'];
    }
    return '';
}

/**
 * Serverska provera tokena preko siteverify endpoventa.
 *
 * @return array ['success'=>bool, 'score'=>float|null, 'action'=>string|null, 'errors'=>array]
 */
function recaptcha_verify($token, $expectedAction = null) {
    $result = [
        'success' => false,
        'score'   => null,
        'action'  => null,
        'errors'  => [],
    ];

    if (!recaptcha_is_enabled()) {
        // Nije konfigurisano -> ne blokiramo (development mode)
        $result['success'] = true;
        return $result;
    }

    if (empty($token) || strlen($token) < 20) {
        $result['errors'][] = 'missing-input-response';
        return $result;
    }

    $fields = [
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query($fields);
    $timeout = defined('RECAPTCHA_VERIFY_TIMEOUT') ? (int) RECAPTCHA_VERIFY_TIMEOUT : 5;

    $raw = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            error_log('reCAPTCHA cURL error: ' . curl_error($ch));
        }
        curl_close($ch);
    }

    if ($raw === false || $raw === '') {
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'timeout' => $timeout,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
    }

    if ($raw === false) {
        // Google nedostupan - fail closed (zastita je prioritet), ali logaj problem
        error_log('reCAPTCHA: siteverify nedostupan (mrezna greska)');
        $result['errors'][] = 'siteverify-unreachable';
        return $result;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        error_log('reCAPTCHA: neispravan odgovor siteverify: ' . substr((string) $raw, 0, 200));
        $result['errors'][] = 'invalid-siteverify-response';
        return $result;
    }

    $result['success'] = !empty($data['success']);
    $result['score']   = isset($data['score']) ? (float) $data['score'] : null;
    $result['action']  = $data['action'] ?? null;
    $result['errors']  = $data['error-codes'] ?? [];

    if ($result['success']) {
        // Proveri action (v3) - token generisan za drugi form ne sme da radi
        if ($expectedAction !== null && $result['action'] !== null && $result['action'] !== $expectedAction) {
            $result['success'] = false;
            $result['errors'][] = 'action-mismatch';
        }
        // Proveri score (v3)
        $minScore = defined('RECAPTCHA_MIN_SCORE') ? (float) RECAPTCHA_MIN_SCORE : 0.5;
        if ($result['score'] !== null && $result['score'] < $minScore) {
            $result['success'] = false;
            $result['errors'][] = 'score-below-threshold';
        }
    }

    return $result;
}

/**
 * Kompletna provera za API endpoint.
 * Uvek vraca ['success'=>bool, 'message'=>string|null].
 */
function recaptcha_check_submission($input = [], $expectedAction = null) {
    if (!recaptcha_is_enabled()) {
        return ['success' => true, 'message' => null];
    }

    $token  = recaptcha_extract_token($input);
    $verify = recaptcha_verify($token, $expectedAction);

    if (!$verify['success']) {
        error_log('reCAPTCHA FAIL action=' . $expectedAction
            . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '?')
            . ' score=' . var_export($verify['score'], true)
            . ' errors=' . implode(',', $verify['errors']));
        return [
            'success' => false,
            'message' => 'reCAPTCHA provera nije uspela. Osvezite stranicu i pokusajte ponovo.',
            'recaptcha_failed' => true,
            'errors'  => $verify['errors'],
        ];
    }

    return ['success' => true, 'message' => null];
}
