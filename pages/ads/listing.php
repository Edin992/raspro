<?php
/**
 * pages/ads/listing.php - Lista i pretraga oglasa
 * SEO VERZIJA - Radi sa slug-ovima umesto ID
 */

// ============================================
// 1. KONVERTOVANJE SLUGOVA U ID (ZA KATEGORIJE)
// ============================================

$categoryId = 0;
$subcategoryId = 0;
$categoryData = null;
$subcategoryData = null;

// Ako imamo category_slug (SEO URL)
if (isset($_GET['category_slug'])) {
    $categoryData = getCategoryBySlug($_GET['category_slug']);
    if ($categoryData) {
        $categoryId = $categoryData['id'];
    }
} 
// Ako imamo category (ID) - backward compatibility
elseif (isset($_GET['category'])) {
    $categoryId = intval($_GET['category']);
    $categoryData = getCategoryById($categoryId);
}

// Ako imamo subcategory_slug (SEO URL)
if (isset($_GET['subcategory_slug'])) {
    $parentSlug = isset($_GET['category_slug']) ? $_GET['category_slug'] : null;
    $subcategoryData = getSubcategoryBySlug($_GET['subcategory_slug'], $parentSlug);
    if ($subcategoryData) {
        $subcategoryId = $subcategoryData['id'];
        // Ako smo našli podkategoriju, proveri da li se poklapa sa parent kategorijom
        if ($subcategoryData['parent_id'] != $categoryId && $categoryId > 0) {
            // Ne poklapa se - ispravi
            $categoryId = $subcategoryData['parent_id'];
            $categoryData = getCategoryById($categoryId);
        }
    }
} 
// Ako imamo subcategory (ID) - backward compatibility
elseif (isset($_GET['subcategory'])) {
    $subcategoryId = intval($_GET['subcategory']);
    $subcategoryData = getCategoryById($subcategoryId);
}

// ============================================
// 2. DOHVATI OSTALE FILTER PARAMETRE
// ============================================

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$currency = isset($_GET['currency']) ? $_GET['currency'] : '';
$condition = isset($_GET['condition']) ? $_GET['condition'] : '';
$premiumOnly = isset($_GET['premium']) && $_GET['premium'] == 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$pagePos = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 40;
$offset = ($pagePos - 1) * $limit;

// ============================================
// 3. DOHVATI OGLASE
// ============================================

$ads = searchAdsAdvanced($searchQuery, $categoryId, $subcategoryId, $city, $minPrice, $maxPrice, $currency, $condition, $sort, $limit, $offset, $premiumOnly);
$totalAds = countAdsAdvanced($searchQuery, $categoryId, $subcategoryId, $city, $minPrice, $maxPrice, $currency, $condition, $premiumOnly);
$totalPages = ceil($totalAds / $limit);

// Dohvati kategorije za dropdown
$categories = getMainCategories();

// Dohvati podkategorije ako je izabrana glavna kategorija
$subcategories = [];
if ($categoryId > 0) {
    $subcategories = getSubcategories($categoryId);
}

// Dohvati popularne gradove
$popularCities = getPopularCities(10);

// ============================================
// 4. POSTAVI TITLE ZA SEO
// ============================================

if ($premiumOnly) {
    $pageTitle = 'Premium Oglasi - Rasprodaja.rs';
} elseif ($searchQuery) {
    $pageTitle = "Rezultati za '{$searchQuery}' - Rasprodaja.rs";
} elseif ($subcategoryData) {
    $pageTitle = $subcategoryData['name'] . ' - Rasprodaja.rs';
} elseif ($categoryData) {
    $pageTitle = $categoryData['name'] . ' - Rasprodaja.rs';
} else {
    $pageTitle = 'Pretraga oglasa - Rasprodaja.rs';
}

$pageDescription = 'Pretražite hiljade oglasa na Rasprodaja.rs. Pronađite auto, stan, telefon, posao i još mnogo toga.';
$pageSpecificJS = ['listing.js'];

// Sačuvaj trenutne filtere za paginaciju
$currentFilters = [
    'category_slug' => $categoryData ? $categoryData['slug'] : null,
    'subcategory_slug' => $subcategoryData ? $subcategoryData['slug'] : null,
    'q' => $searchQuery,
    'city' => $city,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'currency' => $currency,
    'condition' => $condition,
    'sort' => $sort,
    'premium' => $premiumOnly ? 1 : null
];
?>



<div class="container py-4">
    <div class="row">
        <!-- LEVA KOLONA: Filteri -->
        <div class="col-lg-3 mb-4">
            <div class="sticky-top" style="top: 20px;">
                <!-- FORMA ZA PRETRAGU -->
                <div class="card mb-4">
                    <div class="card-header <?php echo $premiumOnly ? 'bg-warning text-dark' : 'bg-primary text-white'; ?>">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i> 
                            <?php echo $premiumOnly ? 'Premium Pretraga' : 'Pretraga'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="search-form" method="GET" action="/ads">
                            <input type="hidden" name="page" value="ads">
                            <?php if ($premiumOnly): ?>
                            <input type="hidden" name="premium" value="1">
                            <?php endif; ?>
                            
                            <!-- PREMIUM BADGE -->
                            <?php if ($premiumOnly): ?>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-crown me-2"></i>
                                <strong>Premium filter aktiviran</strong>
                                <p class="small mb-0 mt-1">Prikazuju se samo premium oglasi</p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- KATEGORIJA -->
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategorija</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Sve kategorije</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        <?php if ($cat['ad_count'] > 0): ?>
                                            <span class="text-muted">(<?php echo $cat['ad_count']; ?>)</span>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- PODKATEGORIJA (prikazuje se samo ako ima podkategorija) -->
                            <div class="mb-3" id="subcategory-container" style="<?php echo empty($subcategories) ; ?>">
                                <label for="subcategory" class="form-label">Podkategorija</label>
                                <select class="form-select" id="subcategory" name="subcategory">
                                    <option value="">Sve podkategorije</option>
                                    <?php foreach ($subcategories as $subcat): ?>
                                    <option value="<?php echo $subcat['id']; ?>"
                                        <?php echo $subcategoryId == $subcat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subcat['name']); ?>
                                        <?php if ($subcat['ad_count'] > 0): ?>
                                            <span class="text-muted">(<?php echo $subcat['ad_count']; ?>)</span>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- KLJUČNA REČ -->
                            <div class="mb-3">
                                <label for="q" class="form-label">Ključna reč</label>
                                <input type="text" class="form-control" id="q" name="q" 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                                       placeholder="Šta tražite?">
                            </div>
                            
                            <!-- GRAD -->
                            <div class="mb-3">
                                <label for="city" class="form-label">Grad</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($city); ?>"
                                       placeholder="Naziv grada">
                            </div>
                            
                            <!-- CENA I VALUTA -->
                            <div class="mb-3">
                                <label class="form-label">Cena</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control" id="min_price" name="min_price" 
                                               value="<?php echo $minPrice ?: ''; ?>"
                                               placeholder="Min" min="0" step="1">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" id="max_price" name="max_price" 
                                               value="<?php echo $maxPrice ?: ''; ?>"
                                               placeholder="Max" min="0" step="1">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <select class="form-select" id="currency" name="currency">
                                            <option value="">Sve valute</option>
                                            <option value="RSD" <?php echo $currency == 'RSD' ? 'selected' : ''; ?>>RSD (DIN)</option>
                                            <option value="EUR" <?php echo $currency == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- STANJE -->
                            <div class="mb-3">
                                <label class="form-label">Stanje</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="condition_all" 
                                           value="" <?php echo empty($condition) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="condition_all">Sve</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="condition_new" 
                                           value="new" <?php echo $condition == 'new' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="condition_new">Novo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="condition" id="condition_used" 
                                           value="used" <?php echo $condition == 'used' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="condition_used">Korišćeno</label>
                                </div>
                            </div>
                            
                            <!-- SORTIRANJE -->
                            <div class="mb-3">
                                <label for="sort" class="form-label">Sortiranje</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Najnoviji prvo</option>
                                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Najstariji prvo</option>
                                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Cena: niža → viša</option>
                                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Cena: viša → niža</option>
                                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Najgledaniji</option>
                                </select>
                            </div>
                            
                            <!-- DUGMAD -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn <?php echo $premiumOnly ? 'btn-warning' : 'btn-primary'; ?>">
                                    <i class="fas fa-search me-2"></i> 
                                    <?php echo $premiumOnly ? 'Pretraži Premium' : 'Pretraži'; ?>
                                </button>
                                <button type="button" id="reset-filters" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo me-2"></i> Resetuj filtere
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- PREMIUM TOGGLE -->
                <div class="card">
                    <div class="card-header <?php echo $premiumOnly ? 'bg-warning' : 'bg-light'; ?>">
                        <h5 class="mb-0">
                            <i class="fas fa-crown me-2"></i> 
                            Premium Prikaz
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($premiumOnly): ?>
                        <p class="small mb-3">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Trenutno prikazujete <strong>samo premium oglase</strong>.
                        </p>
                        <div class="d-grid">
                            <a href="/ads" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-layer-group me-1"></i> Prikaži sve oglase
                            </a>
                        </div>
                        <?php else: ?>
                        <p class="small mb-3">
                            Premium oglasi se prikazuju na vrhu liste i dobijaju 5x više pregleda.
                        </p>
                        <div class="d-grid">
                            <a href="/ads/premium/" class="btn btn-warning btn-sm">
                                <i class="fas fa-crown me-1"></i> Prikaži samo Premium
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DESNA KOLONA: Rezultati -->
        <div class="col-lg-9">
            <!-- HEADER REZULTATA -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div>
                            <h4 class="mb-1">
                                <?php if ($premiumOnly): ?>
                                    <i class="fas fa-crown text-warning me-2"></i>
                                    Premium Oglasi
                                <?php elseif ($searchQuery): ?>
                                    Rezultati za "<?php echo htmlspecialchars($searchQuery); ?>"
                                <?php elseif ($subcategoryId): ?>
                                    <?php 
                                    $subcat = getCategoryById($subcategoryId);
                                    $parentCat = getCategoryById($subcat['parent_id'] ?? 0);
                                    ?>
                                    <i class="fas fa-folder-open text-primary me-2"></i>
                                    <?php echo htmlspecialchars($parentCat['name'] ?? ''); ?>
                                    <i class="fas fa-chevron-right mx-1 text-muted small"></i>
                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                <?php elseif ($categoryId): ?>
                                    <?php $category = getCategoryById($categoryId); ?>
                                    <i class="fas fa-folder text-primary me-2"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                <?php else: ?>
                                    <i class="fas fa-tags text-primary me-2"></i>
                                    Svi oglasi
                                <?php endif; ?>
                            </h4>
                            <p class="text-muted mb-0">
                                Pronađeno <strong><?php echo number_format($totalAds, 0, ',', '.'); ?></strong> oglasa
                                <?php if ($totalPages > 1): ?>
                                    - Strana <strong><?php echo $pagePos; ?></strong> od <?php echo $totalPages; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mt-2 mt-md-0">
                            <a href="<?php echo $premiumOnly ? '/ads' : '/ads/premium/'; ?>" class="btn btn-sm <?php echo $premiumOnly ? 'btn-outline-secondary' : 'btn-warning'; ?>">
                                <i class="fas fa-crown me-1"></i>
                                <?php echo $premiumOnly ? 'Svi oglasi' : 'Samo Premium'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- REZULTATI PRETRAGE -->
            <?php if (empty($ads)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <?php if ($premiumOnly): ?>
                        <i class="fas fa-crown fa-4x text-warning mb-4"></i>
                        <h4 class="text-warning mb-3">Nema premium oglasa</h4>
                        <p class="text-muted mb-4">
                            Trenutno nema premium oglasa koji odgovaraju vašim kriterijumima pretrage.
                        </p>
                    <?php else: ?>
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted mb-3">Nema rezultata</h4>
                        <p class="text-muted mb-4">
                            Nije pronađen nijedan oglas koji odgovara vašim kriterijumima pretrage.
                        </p>
                    <?php endif; ?>
                    
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <button id="reset-search" class="btn <?php echo $premiumOnly ? 'btn-warning' : 'btn-primary'; ?>">
                            <i class="fas fa-redo me-2"></i> Resetuj pretragu
                        </button>
                        <a href="/create-ad" class="btn btn-outline-primary">
                            <i class="fas fa-plus me-2"></i> Postavite oglas
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- LISTA OGLASA -->
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <?php foreach ($ads as $ad): ?>
                <?php 
                $images = getAdImages($ad['id']);
                $mainImage = !empty($images) ? $images[0] : null;
                $category = getCategoryById($ad['category_id']);
                ?>
                <div class="col">
                    <div class="card listing-card h-100">
                        <div class="row g-0 h-100">
                            <!-- SLIKA -->
                            <div class="col-4 position-relative">
                                <a href="/ad/<?php echo $ad['slug']; ?>" class="stretched-link">
                                    <?php if ($mainImage): ?>
                                    <img src="<?php echo SITE_URL . ($mainImage['thumbnail_path'] ?? $mainImage['image_path']); ?>" 
                                         class="img-fluid rounded-start h-100" 
                                         alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                         style="object-fit: cover; width: 100%;">
                                    <?php else: ?>
                                    <div class="h-100 d-flex align-items-center justify-content-center bg-light">
                                        <i class="fas fa-image fa-2x text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </a>
                                
                                <?php if ($ad['is_premium'] == 1): ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-warning">
                                        <i class="fas fa-crown me-1"></i> Premium
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- INFORMACIJE -->
                            <div class="col-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <small class="text-muted mb-1">
                                        <a href="/ads/category/<?php echo $ad['category_id']; ?>" 
                                           class="text-decoration-none text-muted">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </a>
                                    </small>
                                    
                                    <h6 class="card-title mb-2">
                                        <a href="/ad/<?php echo $ad['slug']; ?>" 
                                           class="text-decoration-none text-dark stretched-link">
                                            <?php echo htmlspecialchars($ad['title']); ?>
                                        </a>
                                    </h6>
                                    
                                    <div class="mt-auto">
                                        <h5 class="text-primary mb-1">
                                            <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
                                            <?php if ($ad['price_negotiable'] == 1): ?>
                                            <small class="text-warning">(po dogovoru)</small>
                                            <?php endif; ?>
                                        </h5>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($ad['city']); ?>
                                            </small>
                                            <small class="text-muted">
                                                <?php echo timeAgo($ad['created_at']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-eye me-1"></i>
                                                <?php echo number_format($ad['views'], 0, ',', '.'); ?>
                                            </small>
                                            <?php if (!empty($images) && count($images) > 1): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-images me-1"></i>
                                                <?php echo count($images); ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- PAGINACIJA -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $pagePos <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=ads&p=<?php echo $pagePos - 1; ?><?php echo buildQueryStringAdvanced($currentFilters, ['p' => null]); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    
                    <?php
                    $startPage = max(1, $pagePos - 2);
                    $endPage = min($totalPages, $pagePos + 2);
                    
                    if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=ads&p=1<?php echo buildQueryStringAdvanced($currentFilters, ['p' => null]); ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?php echo $i == $pagePos ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=ads&p=<?php echo $i; ?><?php echo buildQueryStringAdvanced($currentFilters, ['p' => null]); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=ads&p=<?php echo $totalPages; ?><?php echo buildQueryStringAdvanced($currentFilters, ['p' => null]); ?>">
                            <?php echo $totalPages; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="page-item <?php echo $pagePos >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=ads&p=<?php echo $pagePos + 1; ?><?php echo buildQueryStringAdvanced($currentFilters, ['p' => null]); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    Prikazano <?php echo min($limit, count($ads)); ?> od <?php echo number_format($totalAds, 0, ',', '.'); ?> oglasa
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- POPULARNE KATEGORIJE -->
<?php if (empty($searchQuery) && !$categoryId && !$city && !$minPrice && !$maxPrice && !$condition && !$premiumOnly): ?>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-fire me-2"></i> Popularne kategorije</h5>
        </div>
        <div class="card-body">
            <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
                <?php
                $popularCats = getPopularCategories(12);
                foreach ($popularCats as $cat):
                ?>
                <div class="col">
                    <a href="/ads/category/<?php echo $cat['id']; ?>" class="text-decoration-none">
                        <div class="card category-card h-100 text-center border-0 shadow-sm">
                            <div class="card-body">
                                <?php if (!empty($cat['icon'])): ?>
                                <div class="mb-2">
                                    <i class="<?php echo $cat['icon']; ?> fa-2x text-primary"></i>
                                </div>
                                <?php endif; ?>
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($cat['name']); ?></h6>
                                <small class="text-muted"><?php echo $cat['ad_count']; ?> oglasa</small>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.listing-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #e9ecef;
}
.listing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.category-card {
    transition: all 0.3s ease;
}
.category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}
.city-btn {
    font-size: 0.8rem;
    padding: 2px 8px;
}
.sticky-top {
    z-index: 100;
}
</style>