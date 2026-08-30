<?php
/**
 * includes/packages.php - Funkcije za rad sa paketima
 * Sve funkcije čitaju limite direktno iz baze (tabela subscription_plans)
 */

/**
 * Dohvata sve aktivne pakete iz baze
 */
function getAllPackages() {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT * FROM subscription_plans 
        WHERE is_active = 1 
        ORDER BY price_monthly ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Dohvata paket po ID-u
 */
function getPackageById($id) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT * FROM subscription_plans 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Dohvata paket po imenu (free, silver, gold)
 */
function getPackageByName($name) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT * FROM subscription_plans 
        WHERE LOWER(name) = LOWER(?) AND is_active = 1
    ");
    $stmt->execute([$name]);
    return $stmt->fetch();
}

/**
 * Dohvata trenutni paket korisnika
 */
function getUserCurrentPackage($userId) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT sp.*, u.package_expires_at 
        FROM users u
        JOIN subscription_plans sp ON LOWER(u.package) = LOWER(sp.name)
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Dohvata ime paketa korisnika (free, silver, gold)
 */
function getUserPackageName($userId) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT LOWER(package) as package 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result ? $result['package'] : 'free';
}

/**
 * Vraća limit oglasa za paket korisnika
 */
function getAdLimitForUser($userId) {
    $packageName = getUserPackageName($userId);
    $package = getPackageByName($packageName);
    
    if (!$package) {
        return 10; // Default za free
    }
    
    // Proveri da li je max_ads NULL ili 0 - to znači neograničeno
    $maxAds = $package['max_ads'];
    
    // Ako je NULL ili 0, vrati veliki broj (neograničeno)
    if ($maxAds >= 99999 ) {
        return 999999; // Praktično neograničeno
    }
    
    return (int)$maxAds;
}

/**
 * Vraća maksimalan broj slika za paket korisnika
 */
function getMaxImagesForUser($userId) {
    $packageName = getUserPackageName($userId);
    $package = getPackageByName($packageName);
    
    if (!$package) {
        return 10; // Default za free
    }
    
    return (int)$package['max_images'];
}

/**
 * Vraća maksimalan broj premium oglasa za paket korisnika
 */
function getMaxPremiumAdsForUser($userId) {
    $packageName = getUserPackageName($userId);
    $package = getPackageByName($packageName);
    
    if (!$package) {
        return 0; // Default za free
    }
    
    return (int)$package['max_premium_ads'];
}

/**
 * Vraća maksimalnu veličinu slike (u bajtovima) za paket
 */
function getMaxImageSizeForUser($userId) {
    $packageName = getUserPackageName($userId);
    
    // Gold može imati veći limit
    $sizes = [
        'free' => 5 * 1024 * 1024,   // 5MB
        'silver' => 5 * 1024 * 1024, // 5MB
        'gold' => 5 * 1024 * 1024,  // 10MB
    ];
    
    return $sizes[$packageName] ?? 5 * 1024 * 1024;
}

/**
 * Proverava da li korisnik može da postavi novi oglas
 */
function canUserCreateAd($userId) {
    $limit = getAdLimitForUser($userId);
    $currentAds = getCurrentAdCount($userId);
    
    return $currentAds < $limit;
}

/**
 * Proverava da li korisnik može da postavi premium oglas
 */
function canUserMakePremiumAd($userId) {
    $limit = getMaxPremiumAdsForUser($userId);
    
    if ($limit <= 0) return false;
    
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ads 
        WHERE user_id = ? AND is_premium = 1 AND premium_until > NOW()
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    $currentPremium = $result['count'] ?? 0;
    return $currentPremium < $limit;
}

/**
 * Vraća preostali broj oglasa za korisnika
 */
function getRemainingAdsForUser($userId) {
    $limit = getAdLimitForUser($userId);
    $currentAds = getCurrentAdCount($userId);
    
    return max(0, $limit - $currentAds);
}

/**
 * Vraća sve limite za korisnika u jednom nizu
 */
function getUserLimits($userId) {
    $adLimit = getAdLimitForUser($userId);
    $currentAds = getCurrentAdCount($userId);
    
    // Da li je neograničeno?
    $isUnlimited = ($adLimit >= 99999);
    
    return [
        'package' => getUserPackageName($userId),
        'ad_limit' => $adLimit,
        'image_limit' => getMaxImagesForUser($userId),
        'premium_limit' => getMaxPremiumAdsForUser($userId),
        'max_image_size' => getMaxImageSizeForUser($userId),
        'current_ads' => $currentAds,
        'remaining_ads' => $isUnlimited ? 999999 : max(0, $adLimit - $currentAds),
        'can_create_ad' => $isUnlimited ? true : ($currentAds < $adLimit),
        'is_unlimited' => $isUnlimited
    ];
}

/**
 * Proverava da li korisnik može da nadogradi paket
 */
function canUpgradePackage($userId, $newPackageId) {
    $currentPackage = getUserCurrentPackage($userId);
    $newPackage = getPackageById($newPackageId);
    
    if (!$currentPackage || !$newPackage) {
        return false;
    }
    
    // Ako je novi paket skuplji, može da nadogradi
    return $newPackage['price_monthly'] > $currentPackage['price_monthly'];
}

/**
 * Ažurira paket korisnika
 */
function updateUserPackage($userId, $packageId) {
    $db = getDatabaseConnection();
    $package = getPackageById($packageId);
    
    if (!$package) {
        return false;
    }
    
    $expiryDate = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    $stmt = $db->prepare("
        UPDATE users 
        SET package = ?, 
            package_expires_at = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $package['name'],
        $expiryDate,
        $userId
    ]);
}

/**
 * Kreira transakciju u transactions tabeli
 */
function createTransaction($userId, $packageId, $amount, $period = 'monthly', 
                         $paymentMethod = 'card', $paymentDetails = []) {
    try {
        $db = getDatabaseConnection();
        
        // Generiši transaction_id
        $transactionId = 'TXN-' . time() . '-' . $userId . '-' . $packageId;
        
        // Izračunaj datum isteka
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
            $amount,
            $period,
            $paymentMethod,
            $transactionId,
            $paymentDetailsJson,
            $expiresAt
        ]);
        
        return [
            'id' => $db->lastInsertId(),
            'transaction_id' => $transactionId
        ];
        
    } catch (Exception $e) {
        error_log("Create transaction error: " . $e->getMessage());
        return null;
    }
}

/**
 * Dohvata transakcije korisnika
 */
function getUserTransactions($userId, $limit = 10, $status = null) {
    try {
        $db = getDatabaseConnection();
        
        // Prvo proveri da li uopšte ima transakcija
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
        $checkStmt->execute([$userId]);
        $totalCount = $checkStmt->fetchColumn();
        
        error_log("getUserTransactions: User $userId has $totalCount transactions");
        
        $sql = "
            SELECT t.*, sp.name as package_name
            FROM transactions t
            LEFT JOIN subscription_plans sp ON t.package_id = sp.id
            WHERE t.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ako nema rezultata sa JOIN, probaj bez JOIN
        if (empty($results) && $totalCount > 0) {
            error_log("getUserTransactions: JOIN failed, trying without JOIN");
            $stmt2 = $db->prepare("
                SELECT * FROM transactions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt2->execute([$userId, $limit]);
            $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            // Ručno dodaj naziv paketa
            foreach ($results as &$row) {
                $pkgStmt = $db->prepare("SELECT name FROM subscription_plans WHERE id = ?");
                $pkgStmt->execute([$row['package_id']]);
                $pkg = $pkgStmt->fetch();
                $row['package_name'] = $pkg ? $pkg['name'] : 'Nepoznat paket';
            }
        }
        
        error_log("getUserTransactions: Returning " . count($results) . " transactions");
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Get user transactions error: " . $e->getMessage());
        return [];
    }
}

/**
 * Ažurira status transakcije
 */
function updateTransactionStatus($transactionId, $status, $notes = null) {
    try {
        $db = getDatabaseConnection();
        
        $sql = "UPDATE transactions SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($notes) {
            $sql .= ", notes = ?";
            $params[] = $notes;
        }
        
        $sql .= " WHERE transaction_id = ? OR id = ?";
        $params[] = $transactionId;
        $params[] = $transactionId;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount() > 0;
        
    } catch (Exception $e) {
        error_log("Update transaction status error: " . $e->getMessage());
        return false;
    }
}

/**
 * Proverava da li je transakcija uspešna
 */
function isTransactionSuccessful($transactionId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT status 
            FROM transactions 
            WHERE (transaction_id = ? OR id = ?) 
            AND status = 'completed'
        ");
        
        $stmt->execute([$transactionId, $transactionId]);
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        error_log("Check transaction success error: " . $e->getMessage());
        return false;
    }
}

/**
 * Dohvata transakciju po ID-u
 */
function getTransactionById($transactionId) {
    try {
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT t.*, sp.name as package_name, 
                   u.username, u.email, u.first_name, u.last_name
            FROM transactions t
            JOIN subscription_plans sp ON t.package_id = sp.id
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ? OR t.transaction_id = ?
        ");
        
        $stmt->execute([$transactionId, $transactionId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Get transaction by ID error: " . $e->getMessage());
        return null;
    }
}

// ============================================
// LOG FUNKCIJE (ostaju iste)
// ============================================

/**
 * Loguje aktivnost korisnika u user_logs tabelu
 */
function logUserActivity($userId, $action, $details = []) {
    try {
        $db = getDatabaseConnection();
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $db->prepare("
            INSERT INTO user_logs (
                user_id, action, details, 
                ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $detailsJson = json_encode($details);
        
        $stmt->execute([
            $userId,
            $action,
            $detailsJson,
            $ipAddress,
            $userAgent
        ]);
        
        return $db->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Log user activity error: " . $e->getMessage());
        return false;
    }
}

/**
 * Dohvata logove korisnika
 */
function getUserLogs($userId, $limit = 20, $action = null) {
    try {
        $db = getDatabaseConnection();
        
        $sql = "SELECT * FROM user_logs WHERE user_id = ?";
        $params = [$userId];
        
        if ($action) {
            $sql .= " AND action = ?";
            $params[] = $action;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $logs = $stmt->fetchAll();
        
        // Dekodiraj details JSON
        foreach ($logs as &$log) {
            if (!empty($log['details'])) {
                $log['details'] = json_decode($log['details'], true);
            }
        }
        
        return $logs;
        
    } catch (Exception $e) {
        error_log("Get user logs error: " . $e->getMessage());
        return [];
    }
}

/**
 * Loguje login aktivnost
 */
function logUserLogin($userId, $success = true, $additionalDetails = []) {
    $action = $success ? 'login_success' : 'login_failed';
    $details = array_merge([
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ], $additionalDetails);
    
    return logUserActivity($userId, $action, $details);
}

/**
 * Loguje kreiranje oglasa
 */
function logAdCreation($userId, $adId, $adTitle) {
    $details = [
        'ad_id' => $adId,
        'ad_title' => $adTitle,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return logUserActivity($userId, 'ad_create', $details);
}

/**
 * Loguje izmenu oglasa
 */
function logAdUpdate($userId, $adId, $adTitle) {
    $details = [
        'ad_id' => $adId,
        'ad_title' => $adTitle,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return logUserActivity($userId, 'ad_update', $details);
}
?>