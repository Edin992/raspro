<?php
/**
 * sitemap-view.php - HTML pregled Sitemap-a
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDatabaseConnection();
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
$baseUrl = rtrim($baseUrl, '/');
$pageTitle = 'Pregled mape sajta - Rasprodaja.rs';
$pageDescription = 'Pregled svih indeksiranih stranica na sajtu Rasprodaja.rs.';

// Učitaj sve URL-ove iz baze (ista logika kao u sitemap.php)
$urls = [];

// Statičke stranice
$staticPages = [
    '/', '/ads/', '/how-it-works/', '/contact/', '/faq/', '/safety/',
    '/terms/', '/privacy/', '/cookies/', '/categories/', '/packages/', '/ads/premium/'
];
foreach ($staticPages as $page) {
    $urls[] = ['loc' => $baseUrl . $page, 'priority' => '0.6', 'changefreq' => 'weekly', 'type' => 'static'];
}

// Kategorije i podkategorije (isti upit kao iz sitemap.php)
try {
    // Glavne kategorije
    $stmt = $db->query("SELECT slug, name FROM categories WHERE parent_id IS NULL ORDER BY sort_order");
    $mainCats = $stmt->fetchAll();
    foreach ($mainCats as $cat) {
        $urls[] = ['loc' => $baseUrl . '/ads/category/' . $cat['slug'], 'priority' => '0.8', 'type' => 'category'];
    }
    // Podkategorije
    $stmt = $db->query("SELECT c.slug, p.slug as parent_slug FROM categories c JOIN categories p ON c.parent_id = p.id WHERE c.parent_id IS NOT NULL");
    $subCats = $stmt->fetchAll();
    foreach ($subCats as $cat) {
        $urls[] = ['loc' => $baseUrl . '/ads/category/' . $cat['parent_slug'] . '/' . $cat['slug'], 'priority' => '0.7', 'type' => 'subcategory'];
    }
} catch (Exception $e) { error_log("Sitemap view error: " . $e->getMessage()); }

// Aktivni oglasi
try {
    $stmt = $db->prepare("SELECT slug, updated_at FROM ads WHERE status = 'active' ORDER BY is_premium DESC LIMIT 5000");
    $stmt->execute();
    $ads = $stmt->fetchAll();
    foreach ($ads as $ad) {
        $urls[] = ['loc' => $baseUrl . '/ad/' . $ad['slug'], 'priority' => '0.7', 'type' => 'ad', 'lastmod' => date('Y-m-d', strtotime($ad['updated_at']))];
    }
} catch (Exception $e) { error_log("Sitemap view ads error: " . $e->getMessage()); }

// Korisnici
try {
    $stmt = $db->query("SELECT id FROM users WHERE is_verified = 1 LIMIT 1000");
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        $urls[] = ['loc' => $baseUrl . '/profile/' . $user['id'], 'priority' => '0.4', 'type' => 'profile'];
    }
} catch (Exception $e) { error_log("Sitemap view users error: " . $e->getMessage()); }

?>

<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sitemap-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .sitemap-card { border: none; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .sitemap-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .type-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 20px; }
        .badge-static { background-color: #6c757d; color: white; }
        .badge-category { background-color: #007bff; color: white; }
        .badge-ad { background-color: #28a745; color: white; }
        .badge-profile { background-color: #fd7e14; color: white; }
        .priority-high { color: #28a745; font-weight: bold; }
        .priority-medium { color: #fd7e14; }
        .priority-low { color: #dc3545; }
        .search-box { margin-bottom: 30px; }
        .url-truncate { max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (max-width: 768px) { .url-truncate { max-width: 180px; } }
    </style>
</head>
<body>

<div class="sitemap-header">
    <div class="container">
        <h1><i class="fas fa-sitemap me-3"></i>Mapa sajta - Rasprodaja.rs</h1>
        <p class="lead mb-0">Pregled svih indeksiranih stranica</p>
    </div>
</div>

<div class="container">
    <div class="card search-box shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="🔍 Pretraži URL...">
                </div>
                <div class="col-md-3">
                    <select id="typeFilter" class="form-select">
                        <option value="">Svi tipovi</option>
                        <option value="static">Statičke stranice</option>
                        <option value="category">Kategorije</option>
                        <option value="subcategory">Podkategorije</option>
                        <option value="ad">Oglasi</option>
                        <option value="profile">Korisnici</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="priorityFilter" class="form-select">
                        <option value="">Svi prioriteti</option>
                        <option value="high">Visok (>= 0.8)</option>
                        <option value="medium">Srednji (0.5-0.7)</option>
                        <option value="low">Nizak (< 0.5)</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card sitemap-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%">📍 URL</th>
                            <th style="width: 15%">⭐ Prioritet</th>
                            <th style="width: 20%">📁 Tip</th>
                            <th style="width: 15%">🔄 Učestalost</th>
                        </tr>
                    </thead>
                    <tbody id="urlTableBody">
                        <?php foreach ($urls as $url): ?>
                        <tr class="url-row" data-type="<?php echo $url['type']; ?>" data-priority="<?php echo $url['priority']; ?>">
                            <td class="url-truncate"><a href="<?php echo htmlspecialchars($url['loc']); ?>" target="_blank"><?php echo htmlspecialchars($url['loc']); ?></a></td>
                            <td class="priority-<?php echo $url['priority'] >= 0.8 ? 'high' : ($url['priority'] >= 0.5 ? 'medium' : 'low'); ?>"><?php echo $url['priority']; ?></td>
                            <td>
                                <?php
                                $typeMap = ['static' => 'Statička', 'category' => 'Kategorija', 'subcategory' => 'Podkategorija', 'ad' => 'Oglas', 'profile' => 'Profil'];
                                $typeClassMap = ['static' => 'secondary', 'category' => 'primary', 'subcategory' => 'info', 'ad' => 'success', 'profile' => 'warning'];
                                ?>
                                <span class="badge bg-<?php echo $typeClassMap[$url['type']] ?? 'secondary'; ?>"><?php echo $typeMap[$url['type']] ?? $url['type']; ?></span>
                            </td>
                            <td><?php echo isset($url['changefreq']) ? ucfirst($url['changefreq']) : 'Weekly'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4 mb-5">
        <small class="text-muted">Ukupno <span id="totalCount"><?php echo count($urls); ?></span> URL-ova | <a href="/sitemap.xml">Prikaži XML sitemap</a> (za pretraživače)</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const rows = document.querySelectorAll('.url-row');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedType = typeFilter.value;
        const selectedPriority = priorityFilter.value;
        let visibleCount = 0;

        rows.forEach(row => {
            const url = row.querySelector('td:first-child a').innerText.toLowerCase();
            const type = row.dataset.type;
            const priority = parseFloat(row.dataset.priority);
            
            let priorityMatch = true;
            if (selectedPriority === 'high') priorityMatch = priority >= 0.8;
            else if (selectedPriority === 'medium') priorityMatch = priority >= 0.5 && priority < 0.8;
            else if (selectedPriority === 'low') priorityMatch = priority < 0.5;
            
            const typeMatch = selectedType === '' || type === selectedType;
            const searchMatch = searchTerm === '' || url.includes(searchTerm);
            
            if (typeMatch && priorityMatch && searchMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        document.getElementById('totalCount').innerText = visibleCount;
    }

    searchInput.addEventListener('keyup', filterTable);
    typeFilter.addEventListener('change', filterTable);
    priorityFilter.addEventListener('change', filterTable);
    filterTable();
</script>
</body>
</html>