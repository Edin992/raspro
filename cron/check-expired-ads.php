<?php
/**
 * cron/check-expired-ads.php - Provera i obrada isteklih oglasa
 * 
 * Ovaj skript treba da se izvršava jednom dnevno (npr. u 02:00)
 * Cron komanda: 0 2 * * * php /putanja/do/sajta/cron/check-expired-ads.php
 */

// Postavi vremensku zonu
date_default_timezone_set('Europe/Belgrade');

if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://rasprodaja.rs');
}
// Putanje - prilagodite vašoj strukturi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// Omogući izvršavanje preko CLI ili browser-a (samo admin)
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    // Ako nije CLI, proveri admin privilegije
    session_start();
    if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
        die('Access denied. Only CLI or admin can run this script.');
    }
}

// Log funkcija
function cronLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    echo $logMessage;
    
    // Upis u log fajl
    $logFile = __DIR__ . '/logs/expired-ads.log';
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

cronLog("========== START: Provera isteklih oglasa ==========");

try {
    $db = getDatabaseConnection();
    
    // ============================================
    // 1. OGLASI KOJI ISTIČU ZA 7 DANA (obavesti korisnike)
    // ============================================
    $sevenDaysFromNow = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $stmt = $db->prepare("
        SELECT a.*, u.email, u.username, u.first_name, u.last_name, u.package
        FROM ads a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active' 
        AND a.expires_at <= ?
        AND a.expires_at > NOW()
        AND a.renew_notification_sent = 0
    ");
    $stmt->execute([$sevenDaysFromNow]);
    $expiringSoonAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifiedCount = 0;
    
    foreach ($expiringSoonAds as $ad) {
        $userName = !empty($ad['first_name']) && !empty($ad['last_name']) 
            ? $ad['first_name'] . ' ' . $ad['last_name'] 
            : $ad['username'];
        
        $expiresAt = date('d.m.Y.', strtotime($ad['expires_at']));
        $daysLeft = ceil((strtotime($ad['expires_at']) - time()) / (60 * 60 * 24));
        
        // Pošalji email obaveštenje
        $subject = "Vaš oglas ističe za $daysLeft dana - Rasprodaja.rs";
        $htmlContent = generateExpiryWarningEmail($userName, $ad['title'], $expiresAt, $daysLeft, $ad['id'], $ad['package']);
        
        if (sendEmail($ad['email'], $subject, $htmlContent)) {
            $notifiedCount++;
            cronLog("✅ Obaveštenje poslato za oglas ID {$ad['id']} - '{$ad['title']}' (ističe za $daysLeft dana)");
            
            // Označi da je obaveštenje poslato (potrebna kolona)
            $updateStmt = $db->prepare("UPDATE ads SET renew_notification_sent = 1 WHERE id = ?");
            $updateStmt->execute([$ad['id']]);
        } else {
            cronLog("❌ Greška pri slanju emaila za oglas ID {$ad['id']}");
        }
    }
    
    cronLog("📧 Poslato {$notifiedCount} obaveštenja za oglase koji ističu za 7 dana");
    
    // ============================================
    // 2. OGLASI KOJI SU ISTEKLI (postavi status na 'expired')
    // ============================================
    $stmt = $db->prepare("
        SELECT a.*, u.email, u.username, u.first_name, u.last_name, u.package
        FROM ads a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'active' 
        AND a.expires_at < NOW()
    ");
    $stmt->execute();
    $expiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $expiredCount = 0;
    $emailCount = 0;
    
    foreach ($expiredAds as $ad) {
        $userName = !empty($ad['first_name']) && !empty($ad['last_name']) 
            ? $ad['first_name'] . ' ' . $ad['last_name'] 
            : $ad['username'];
        
        // Ažuriraj status oglasa na 'expired'
        $updateStmt = $db->prepare("
            UPDATE ads 
            SET status = 'expired', 
                expired_notification_sent = 1
            WHERE id = ?
        ");
        $updateStmt->execute([$ad['id']]);
        $expiredCount++;
        
        cronLog("⏰ Oglas ID {$ad['id']} - '{$ad['title']}' je postavljen na status 'expired'");
        
        // Pošalji email obaveštenje o isteku (samo ako nije poslato ranije)
        if (empty($ad['expired_notification_sent'])) {
            $subject = "Vaš oglas je istekao - Rasprodaja.rs";
            $htmlContent = generateExpiredEmail($userName, $ad['title'], $ad['id'], $ad['package']);
            
            if (sendEmail($ad['email'], $subject, $htmlContent)) {
                $emailCount++;
                cronLog("📧 Email o isteku poslat za oglas ID {$ad['id']}");
            } else {
                cronLog("❌ Greška pri slanju emaila o isteku za oglas ID {$ad['id']}");
            }
        }
    }
    
    cronLog("📌 Ukupno isteklih oglasa: {$expiredCount}");
    cronLog("📧 Poslato {$emailCount} emailova o isteku");
    
    // ============================================
    // 3. AUTOMATSKO OBNAVLJANJE ZA GOLD PAKET (opciono)
    // ============================================
    $autoRenewedCount = 0;
    
    // Gold korisnici imaju automatsko obnavljanje isteklih oglasa
    $stmt = $db->prepare("
        SELECT a.*, u.email, u.username, u.first_name, u.last_name
        FROM ads a
        JOIN users u ON a.user_id = u.id
        WHERE a.status = 'expired' 
        AND u.package = 'Gold'
        AND a.auto_renew_enabled = 1
    ");
    $stmt->execute();
    $goldExpiredAds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($goldExpiredAds as $ad) {
        $userName = !empty($ad['first_name']) && !empty($ad['last_name']) 
            ? $ad['first_name'] . ' ' . $ad['last_name'] 
            : $ad['username'];
        
        // Obnovi oglas
        $newExpiresAt = date('Y-m-d H:i:s', strtotime('+60 days'));
        $renewCount = $ad['renew_count'] + 1;
        
        $updateStmt = $db->prepare("
            UPDATE ads 
            SET status = 'active',
                created_at = NOW(),
                expires_at = ?,
                renewed_at = NOW(),
                renew_count = ?,
                expired_notification_sent = 0
            WHERE id = ?
        ");
        $updateStmt->execute([$newExpiresAt, $renewCount, $ad['id']]);
        $autoRenewedCount++;
        
        cronLog("🔄 Automatski obnovljen GOLD oglas ID {$ad['id']} - '{$ad['title']}'");
        
        // Pošalji obaveštenje o automatskom obnavljanju
        $subject = "Vaš oglas je automatski obnovljen - Rasprodaja.rs";
        $htmlContent = generateAutoRenewEmail($userName, $ad['title'], $newExpiresAt);
        sendEmail($ad['email'], $subject, $htmlContent);
    }
    
    cronLog("🔄 Automatski obnovljeno {$autoRenewedCount} GOLD oglasa");
    
    // ============================================
    // 4. ČIŠĆENJE STARIH OGLASA (opciono - nakon 90 dana)
    // ============================================
    $ninetyDaysAgo = date('Y-m-d H:i:s', strtotime('-90 days'));
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE status = 'deleted' 
        AND deleted_at < ?
    ");
    $stmt->execute([$ninetyDaysAgo]);
    $oldDeletedCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    if ($oldDeletedCount > 0) {
        // Opciono: fizičko brisanje starih obrisanih oglasa
        // $stmt = $db->prepare("DELETE FROM ads WHERE status = 'deleted' AND deleted_at < ?");
        // $stmt->execute([$ninetyDaysAgo]);
        cronLog("🗑️ Starih obrisanih oglasa (starijih od 90 dana): {$oldDeletedCount}");
    }
    
    // ============================================
    // 5. GENERIŠI IZVEŠTAJ
    // ============================================
    $report = [
        'expiring_soon_notified' => $notifiedCount,
        'expired_processed' => $expiredCount,
        'expired_emails_sent' => $emailCount,
        'auto_renewed_gold' => $autoRenewedCount,
        'old_deleted_count' => $oldDeletedCount,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    cronLog("========== IZVEŠTAJ ==========");
    cronLog(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    cronLog("========== KRAJ ==========");
    
    // Sačuvaj izveštaj u bazu (opciono)
    $stmt = $db->prepare("
        INSERT INTO cron_logs (cron_name, executed_at, status, details) 
        VALUES ('check_expired_ads', NOW(), 'success', ?)
    ");
    $stmt->execute([json_encode($report, JSON_UNESCAPED_UNICODE)]);
    
} catch (Exception $e) {
    cronLog("❌ GREŠKA: " . $e->getMessage());
    cronLog("Stack trace: " . $e->getTraceAsString());
    
    // Sačuvaj grešku u bazu
    if (isset($db)) {
        $stmt = $db->prepare("
            INSERT INTO cron_logs (cron_name, executed_at, status, error_message) 
            VALUES ('check_expired_ads', NOW(), 'error', ?)
        ");
        $stmt->execute([$e->getMessage()]);
    }
}

// ============================================
// EMAIL TEMPLATE FUNKCIJE
// ============================================

/**
 * Generiše HTML za obaveštenje da oglas ističe za 7 dana
 */
function generateExpiryWarningEmail($userName, $adTitle, $expiresAt, $daysLeft, $adId, $userPackage) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $renewUrl = $siteUrl . "/ad/" . $adId;
    $upgradeUrl = $siteUrl . "/packages/";
    $currentYear = date('Y');
    
    $autoRenewNote = '';
    if ($userPackage === 'Gold') {
        $autoRenewNote = '
        <div class="auto-renew-box" style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <h4 style="margin-top: 0; color: #155724;">
                <i class="fas fa-sync-alt"></i> Automatsko obnavljanje za GOLD paket
            </h4>
            <p>Kao GOLD korisnik, vaš oglas će biti automatski obnovljen nakon isteka.</p>
        </div>';
    }
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oglas ističe za $daysLeft dana</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .email-header { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #333; padding: 30px 20px; text-align: center; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .email-logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        .email-body { padding: 30px; }
        .warning-box { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .renew-button { display: inline-block; background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #333; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; font-size: 16px; margin: 20px 0; text-align: center; }
        .upgrade-button { display: inline-block; background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; margin-top: 10px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #718096; font-size: 14px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-logo">⚠️ Rasprodaja.rs</div>
            <h1>Oglas ističe za $daysLeft dana</h1>
        </div>
        
        <div class="email-body">
            <p>Poštovani <strong>$userName</strong>,</p>
            
            <div class="warning-box">
                <h3 style="margin-top: 0; color: #856404;">Vaš oglas uskoro ističe!</h3>
                <p><strong>Oglas:</strong> $adTitle</p>
                <p><strong>Datum isteka:</strong> $expiresAt</p>
                <p><strong>Preostalo dana:</strong> $daysLeft dana</p>
            </div>
            
            <p>Da biste produžili važenje oglasa, možete ga besplatno obnoviti.</p>
            <p>Prijavite se na svoj profil i obnovite svoj oglas.</p>
            
            <div style="text-align: center;">
                <a href="$renewUrl" class="renew-button">
                    🔄 OBNOVI OGLAS BESPLATNO
                </a>
            </div>
            
            $autoRenewNote
            
            <div style="background-color: #e7f1ff; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4 style="margin-top: 0;">💡 Savet:</h4>
                <ul>
                    <li>Obnovite oglas da biste zadržali sve preglede i statistiku</li>
                    <li>Nadogradite na Silver ili Gold paket za automatsko obnavljanje</li>
                    <li>Premium oglasi dobijaju veću vidljivost i više pregleda</li>
                </ul>
                <div style="text-align: center;">
                    <a href="$upgradeUrl" class="upgrade-button">
                        ⭐ NADOGRADI PAKET
                    </a>
                </div>
            </div>
            
            <p>Obnavljanjem oglasa, on će biti aktivan narednih 30 dana.</p>
        </div>
        
        <div class="footer">
            <p><strong>$siteName</strong> - Najveća oglasna tabla u Srbiji</p>
            <p style="font-size: 12px;">&copy; $currentYear $siteName. Sva prava zadržana.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Generiše HTML za obaveštenje da je oglas istekao
 */
function generateExpiredEmail($userName, $adTitle, $adId, $userPackage) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $renewUrl = $siteUrl . "/ad/" . $adId;
    $upgradeUrl = $siteUrl . "/packages/";
    $currentYear = date('Y');
    
    $autoRenewNote = '';
    $upgradeSuggestion = '';
    
    if ($userPackage === 'Free') {
        $upgradeSuggestion = '
        <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0;">📈 Želite automatsko obnavljanje?</h4>
            <p>Nadogradite na Silver ili Gold paket i vaši oglasi će se automatski obnavljati!</p>
            <div style="text-align: center;">
                <a href="' . $upgradeUrl . '" class="upgrade-button" style="background: #ffc107; color: #333; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block;">
                    ⭐ NADOGRADI PAKET
                </a>
            </div>
        </div>';
    }
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oglas je istekao - Rasprodaja.rs</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .email-header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px 20px; text-align: center; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .email-body { padding: 30px; }
        .expired-box { background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .renew-button { display: inline-block; background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; font-size: 16px; margin: 20px 0; text-align: center; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #718096; font-size: 14px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-logo">⏰ Rasprodaja.rs</div>
            <h1>Vaš oglas je istekao</h1>
        </div>
        
        <div class="email-body">
            <p>Poštovani <strong>$userName</strong>,</p>
            
            <div class="expired-box">
                <h3 style="margin-top: 0; color: #721c24;">Vaš oglas više nije aktivan!</h3>
                <p><strong>Oglas:</strong> $adTitle</p>
                <p><strong>Status:</strong> Istekao</p>
            </div>
            
            <p>Vaš oglas je istekao i više se ne prikazuje u pretrazi. Da biste ga ponovo aktivirali, možete ga besplatno obnoviti.</p>
            <p>Prijavite se na svoj profil i obnovite svoj oglas.</p>
            
            <div style="text-align: center;">
                <a href="$renewUrl" class="renew-button">
                    🔄 OBNOVI OGLAS BESPLATNO
                </a>
            </div>
            
            $upgradeSuggestion
            
            <p>Obnovite oglas već danas i vratite ga na vrh liste!</p>
        </div>
        
        <div class="footer">
            <p><strong>$siteName</strong> - Najveća oglasna tabla u Srbiji</p>
            <p style="font-size: 12px;">&copy; $currentYear $siteName. Sva prava zadržana.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Generiše HTML za automatsko obnavljanje (GOLD paket)
 */
function generateAutoRenewEmail($userName, $adTitle, $newExpiresAt) {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
    $siteName = 'Rasprodaja.rs';
    $currentYear = date('Y');
    $newExpiryDate = date('d.m.Y.', strtotime($newExpiresAt));
    
    return <<<HTML
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oglas automatski obnovljen - Rasprodaja.rs</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .email-header { background: linear-gradient(135deg, #28a745 0%, #218838 100%); color: white; padding: 30px 20px; text-align: center; }
        .email-body { padding: 30px; }
        .success-box { background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin: 25px 0; border-radius: 4px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #718096; font-size: 14px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-logo">🔄 Rasprodaja.rs</div>
            <h1>Oglas automatski obnovljen</h1>
        </div>
        
        <div class="email-body">
            <p>Poštovani <strong>$userName</strong>,</p>
            
            <div class="success-box">
                <h3 style="margin-top: 0; color: #155724;">
                    <i class="fas fa-check-circle"></i> Oglas je automatski obnovljen!
                </h3>
                <p><strong>Oglas:</strong> $adTitle</p>
                <p><strong>Novi datum isteka:</strong> $newExpiryDate</p>
            </div>
            
            <p>Zahvaljujući vašem <strong>GOLD paketu</strong>, vaš oglas je automatski obnovljen i ponovo je aktivan.</p>
            
            <p>Hvala vam što koristite <strong>$siteName</strong>!</p>
        </div>
        
        <div class="footer">
            <p><strong>$siteName</strong> - Najveća oglasna tabla u Srbiji</p>
            <p style="font-size: 12px;">&copy; $currentYear $siteName. Sva prava zadržana.</p>
        </div>
    </div>
</body>
</html>
HTML;
}