<?php
/**
 * admin/includes/auth.php - Admin autentikacija
 */
ob_start();
// Ne pokreći session ako je već aktivan


// Putanje do root fajlova
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * Proveri da li je korisnik admin
 */
if (!function_exists('isAdmin')) {

    function isAdmin() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        static $isAdmin = null;
        
        if ($isAdmin !== null) {
            return $isAdmin;
        }
        
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $count = $stmt->fetchColumn();
            
            $isAdmin = ($count > 0);
            $_SESSION['is_admin'] = $isAdmin;
            
            return $isAdmin;
        } catch (Exception $e) {
            error_log("isAdmin error: " . $e->getMessage());
            return false;
        }
    }}

    /**
     * Prikazuje error stranicu iz pages/errors foldera
     */
    function showErrorPage($errorCode, $customMessage = null) {
        http_response_code($errorCode);
        
        // Apsolutna putanja do error stranice
        $errorFile = $_SERVER['DOCUMENT_ROOT'] . '/pages/errors/' . $errorCode . '.php';
        
        if (file_exists($errorFile)) {
            // Prosledi custom poruku ako postoji
            if ($customMessage) {
                $_SESSION['error_message'] = $customMessage;
            }
            include $errorFile;
        } else {
            // Fallback ako fajl ne postoji
            echo "<h1>{$errorCode} - Greška</h1>";
            if ($customMessage) echo "<p>{$customMessage}</p>";
            echo "<a href='/'>Povratak na početnu</a>";
        }
        exit;
    }

    /**
     * Zahteva admin pristup - PRAVILNA PROVERA!
     */
    function requireAdmin() {
        // KORAK 1: Proveri da li je korisnik ulogovan
        if (!isset($_SESSION['user_id'])) {
            // Nije ulogovan -> prikaži 403 (ili redirect na login)
            // Opcija A: Redirect na login (preporučeno)
            if (function_exists('redirect')) {
                redirect('/login');
            } else {
                header('Location: /login');
            }
            exit;
        }
        
        // KORAK 2: Proveri da li je ulogovani korisnik admin
        if (!isAdmin()) {
            // Jeste ulogovan, ali nije admin -> prikaži 403 iz pages/errors/403.php
            showErrorPage(403, 'Nemate dozvolu za pristup admin panelu. Ova stranica je rezervisana samo za administratore.');
        }
        
        // KORAK 3: Jestе admin -> dozvoli pristup
        return true;
    }

    /**
     * Opciono: Proveri da li admin ima specifičnu permisiju
     */
    function adminHasPermission($permission) {
        if (!isAdmin()) return false;
        return true;
    }

    /**
     * Dohvati admin statistike
     */
    function getAdminStats() {
        $db = getDatabaseConnection();
        $stats = [];
        
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $stats['total_users'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
            $stats['new_users_today'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(CURDATE())");
            $stats['new_users_month'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM ads");
            $stats['total_ads'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM ads WHERE status = 'active'");
            $stats['active_ads'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM ads WHERE DATE(created_at) = CURDATE()");
            $stats['new_ads_today'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM ads WHERE is_premium = 1");
            $stats['premium_ads'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM ads WHERE status = 'sold'");
            $stats['sold_ads'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT SUM(views) FROM ads");
            $stats['total_views'] = $stmt->fetchColumn() ?: 0;
            
            $stmt = $db->query("SELECT COUNT(*) FROM categories");
            $stats['total_categories'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT COUNT(*) FROM admins");
            $stats['total_admins'] = $stmt->fetchColumn();
            
            $stmt = $db->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed'");
            $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
            
            $stmt = $db->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURDATE())");
            $stats['monthly_revenue'] = $stmt->fetchColumn() ?: 0;
            
            $stmt = $db->query("
                SELECT COUNT(*) FROM user_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stats['activities_24h'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("getAdminStats error: " . $e->getMessage());
        }
        
        return $stats;
    }

    /**
     * Dohvati podatke za grafikone
     */
    function getChartData() {
        $db = getDatabaseConnection();
        $data = ['labels' => [], 'users' => [], 'ads' => []];
        
        try {
            for ($i = 29; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $data['labels'][] = date('d.m', strtotime($date));
                
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
                $stmt->execute([$date]);
                $data['users'][] = (int)$stmt->fetchColumn();
                
                $stmt = $db->prepare("SELECT COUNT(*) FROM ads WHERE DATE(created_at) = ?");
                $stmt->execute([$date]);
                $data['ads'][] = (int)$stmt->fetchColumn();
            }
        } catch (Exception $e) {
            error_log("getChartData error: " . $e->getMessage());
        }
        
        return $data;
    }

    /**
     * Dohvati top kategorije
     */
    function getTopCategories($limit = 5) {
        $db = getDatabaseConnection();
        try {
            $stmt = $db->prepare("
                SELECT c.name, c.icon, COUNT(a.id) as ads_count
                FROM categories c
                LEFT JOIN ads a ON c.id = a.category_id AND a.status = 'active'
                GROUP BY c.id
                ORDER BY ads_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("getTopCategories error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Dohvati najaktivnije korisnike
     */
    function getTopUsers($limit = 5) {
        $db = getDatabaseConnection();
        try {
            $stmt = $db->prepare("
                SELECT u.id, u.username, u.avatar, COUNT(a.id) as ads_count,
                       COALESCE(SUM(a.views), 0) as total_views
                FROM users u
                LEFT JOIN ads a ON u.id = a.user_id
                GROUP BY u.id
                ORDER BY ads_count DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("getTopUsers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Dohvati podešavanje
     */
    function getSetting($key, $default = null) {
        $db = getDatabaseConnection();
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : $default;
        } catch (Exception $e) {
            error_log("getSetting error: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Ažuriraj podešavanje
     */
    function updateSetting($key, $value) {
        $db = getDatabaseConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                setting_value = ?, updated_at = NOW()
            ");
            return $stmt->execute([$key, $value, $value]);
        } catch (Exception $e) {
            error_log("updateSetting error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dohvati sva podešavanja po grupi
     */
    function getSettingsByGroup($group = null) {
        $db = getDatabaseConnection();
        try {
            if ($group) {
                $stmt = $db->prepare("SELECT * FROM settings WHERE `group` = ? ORDER BY sort_order");
                $stmt->execute([$group]);
            } else {
                $stmt = $db->query("SELECT * FROM settings ORDER BY `group`, sort_order");
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("getSettingsByGroup error: " . $e->getMessage());
            return [];
        }
    }

?>