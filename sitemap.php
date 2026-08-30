<?php
/**
 * sitemap.php - Automatska generacija XML mape sajta
 * SEO VERZIJA - Generiše lepe URL-ove sa slug-ovima
 */

// Isključi sve greške koje bi mogle da poremete XML
error_reporting(0);
ini_set('display_errors', 0);



// Postavi ispravne headere za XML
header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-cache, must-revalidate', true);

// Uključi bazu i funkcije
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Proveri da li headeri mogu biti poslati
if (headers_sent($file, $line)) {
    die("<!-- Headers already sent in $file on line $line -->");
}

$db = getDatabaseConnection();
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$baseUrl = rtrim($baseUrl, '/');

// Početak XML-a
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

// ============================================
// 1. STATIČKE STRANICE
// ============================================
$staticPages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')],
    ['loc' => '/ads/', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => date('Y-m-d')],
    ['loc' => '/how-it-works/', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/contact/', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/faq/', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/safety/', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/terms/', 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => '/privacy/', 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => '/cookies/', 'priority' => '0.4', 'changefreq' => 'yearly'],
    ['loc' => '/categories/', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => '/packages/', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => '/ads/premium/', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => '/login/', 'priority' => '0.3', 'changefreq' => 'monthly'],
    ['loc' => '/register/', 'priority' => '0.3', 'changefreq' => 'monthly'],
    ['loc' => '/create-ad/', 'priority' => '0.4', 'changefreq' => 'weekly'],
];

foreach ($staticPages as $page) {
    echo '<url>' . "\n";
    echo '  <loc>' . xml_escape($baseUrl . $page['loc']) . '</loc>' . "\n";
    echo '  <priority>' . $page['priority'] . '</priority>' . "\n";
    echo '  <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
    if (isset($page['lastmod'])) {
        echo '  <lastmod>' . $page['lastmod'] . '</lastmod>' . "\n";
    }
    echo '</url>' . "\n";
}

// ============================================
// 2. GLAVNE KATEGORIJE (SAMO SLUG, BEZ ID)
// ============================================
try {
    $stmt = $db->query("
        SELECT c.id, c.name, c.slug, 
               (SELECT COUNT(*) FROM ads WHERE category_id = c.id AND status = 'active') as ad_count
        FROM categories c
        WHERE c.parent_id = 0 OR c.parent_id IS NULL
        ORDER BY c.sort_order, c.name
    ");
    $mainCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($mainCategories as $cat) {
        // Koristi slug iz baze, ili kreiraj ako ne postoji
        $slug = !empty($cat['slug']) ? $cat['slug'] : createSlug($cat['name']);
        $url = $baseUrl . '/ads/category/' . rawurlencode($slug);
        
        // Ako kategorija nema oglasa, daj joj manji priority
        $priority = $cat['ad_count'] > 0 ? '0.8' : '0.4';
        
        echo '<url>' . "\n";
        echo '  <loc>' . xml_escape($url) . '</loc>' . "\n";
        echo '  <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '  <changefreq>weekly</changefreq>' . "\n";
        echo '  <priority>' . $priority . '</priority>' . "\n";
        echo '</url>' . "\n";
    }
} catch (Exception $e) {
    error_log("Sitemap main categories error: " . $e->getMessage());
}

// ============================================
// 3. PODKATEGORIJE (SA RODITELJSKIM SLUG-OM)
// ============================================
try {
    $stmt = $db->query("
        SELECT c.id, c.name, c.slug, c.parent_id,
               p.slug as parent_slug, p.name as parent_name,
               (SELECT COUNT(*) FROM ads WHERE subcategory_id = c.id AND status = 'active') as ad_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        WHERE c.parent_id IS NOT NULL AND c.parent_id != 0
        ORDER BY p.sort_order, c.sort_order, c.name
    ");
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subcategories as $cat) {
        $parentSlug = !empty($cat['parent_slug']) ? $cat['parent_slug'] : createSlug($cat['parent_name']);
        $childSlug = !empty($cat['slug']) ? $cat['slug'] : createSlug($cat['name']);
        
        $url = $baseUrl . '/ads/category/' . rawurlencode($parentSlug) . '/' . rawurlencode($childSlug);
        
        // Ako podkategorija nema oglasa, daj joj manji priority
        $priority = $cat['ad_count'] > 0 ? '0.7' : '0.3';
        
        echo '<url>' . "\n";
        echo '  <loc>' . xml_escape($url) . '</loc>' . "\n";
        echo '  <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        echo '  <changefreq>weekly</changefreq>' . "\n";
        echo '  <priority>' . $priority . '</priority>' . "\n";
        echo '</url>' . "\n";
    }
} catch (Exception $e) {
    error_log("Sitemap subcategories error: " . $e->getMessage());
}

// ============================================
// 4. AKTIVNI OGLASI (SAMO SLUG, BEZ ID)
// ============================================
try {
    // Dohvati sve aktivne oglase sa njihovim slug-ovima
    $stmt = $db->prepare("
        SELECT id, slug, created_at, updated_at, 
               is_premium, views, category_id
        FROM ads 
        WHERE status = 'active' 
        ORDER BY is_premium DESC, updated_at DESC
        LIMIT 50000
    ");
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($ads as $ad) {
        // Koristi slug iz baze
        if (!empty($ad['slug'])) {
            $slug = $ad['slug'];
        } else {
            // Ako nema slug, kreiraj ga (ali bi trebalo da ga ima)
            $slug = 'ad-' . $ad['id'];
        }
        
        $lastmod = date('Y-m-d', strtotime($ad['updated_at'] ?? $ad['created_at']));
        $url = $baseUrl . '/ad/' . rawurlencode($slug);
        
        // Premium oglasi imaju veći priority
        $priority = ($ad['is_premium'] == 1) ? '0.9' : '0.7';
        
        // Češće menjani oglasi imaju veći changefreq
        $isRecentlyUpdated = (strtotime($ad['updated_at']) > strtotime('-7 days'));
        $changefreq = $isRecentlyUpdated ? 'daily' : 'weekly';
        
        echo '<url>' . "\n";
        echo '  <loc>' . xml_escape($url) . '</loc>' . "\n";
        echo '  <lastmod>' . $lastmod . '</lastmod>' . "\n";
        echo '  <changefreq>' . $changefreq . '</changefreq>' . "\n";
        echo '  <priority>' . $priority . '</priority>' . "\n";
        echo '</url>' . "\n";
    }
} catch (Exception $e) {
    error_log("Sitemap ads error: " . $e->getMessage());
}

// ============================================
// 5. VERIFIKOVANI KORISNICI (PROFILI)
// ============================================
try {
    $stmt = $db->query("
        SELECT id, username, updated_at, created_at, first_name, last_name
        FROM users 
        WHERE is_verified = 1 
        ORDER BY id DESC 
        LIMIT 1000
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $lastmod = date('Y-m-d', strtotime($user['updated_at'] ?? $user['created_at']));
        $url = $baseUrl . '/profile/' . $user['id'];
        
        echo '<url>' . "\n";
        echo '  <loc>' . xml_escape($url) . '</loc>' . "\n";
        echo '  <lastmod>' . $lastmod . '</lastmod>' . "\n";
        echo '  <changefreq>monthly</changefreq>' . "\n";
        echo '  <priority>0.4</priority>' . "\n";
        echo '</url>' . "\n";
    }
} catch (Exception $e) {
    error_log("Sitemap users error: " . $e->getMessage());
}



// Zatvori XML
echo '</urlset>';

// Završi izvršavanje - ne dozvoli nikakav dodatni output
exit;

/**
 * Escape XML specijalne karaktere
 */
function xml_escape($string) {
    if ($string === null) {
        return '';
    }
    $string = htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    // Ispravi duple escape-ove
    $string = str_replace('&amp;amp;', '&amp;', $string);
    $string = str_replace('&amp;quot;', '&quot;', $string);
    return $string;
}
?>