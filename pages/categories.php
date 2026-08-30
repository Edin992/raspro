<?php
/**
 * pages/categories.php - Stranica sa svim kategorijama (REDIZAJNIRANA)
 */

// Postavi meta tagove
$pageTitle = 'Sve kategorije - Rasprodaja.rs';
$pageDescription = 'Pregledajte sve kategorije oglasa na Rasprodaja.rs. Automobili, nekretnine, elektronika, odeća i više.';
$pageSpecificCSS = ['categories.css'];
$showBreadcrumbs = false;

// Dohvati sve kategorije hijerarhijski
$allCategories = getAllCategories();

// Grupiši kategorije po roditeljima

$categoriesByParent = [];
foreach ($allCategories as $category) {
    $parentId = $category['parent_id'] ?? 0;
    if (!isset($categoriesByParent[$parentId])) {
        $categoriesByParent[$parentId] = [];
    }
    $categoriesByParent[$parentId][] = $category;
}
// Sortiraj podkategorije po broju oglasa (od najvećeg ka najmanjem)
foreach ($categoriesByParent as $parentId => &$subcats) {
    usort($subcats, function($a, $b) {
        return ($b['ad_count'] ?? 0) - ($a['ad_count'] ?? 0);
    });
}
// Dohvati glavne kategorije
$mainCategories = getMainCategories();

// Izračunaj ukupan broj oglasa
$totalAdsInAllCategories = getActiveAdCount();




// Sortiraj glavne kategorije po broju oglasa (najpopularnije prvo)
usort($mainCategories, function($a, $b) {
    return ($b['ad_count'] ?? 0) - ($a['ad_count'] ?? 0);
});
?>

<!-- HERO SECTION -->
<section class="categories-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold text-white mb-3">
                    Sve kategorije
                </h1>
                <p class="lead text-white mb-4">
                    Pregledajte preko <strong><?php echo count($allCategories); ?></strong> kategorija 
                    sa <strong><?php echo number_format($totalAdsInAllCategories); ?>+</strong> aktivnih oglasa.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="/create-ad" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-plus-circle me-2"></i> Postavi oglas
                    </a>
                    <a href="/ads" class="btn btn-outline-light btn-lg px-4">
                        <i class="fas fa-search me-2"></i> Pretraži oglase
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <i class="fas fa-folder-tree fa-6x text-white opacity-50"></i>
            </div>
        </div>
    </div>
</section>

<div class="container">
    
    <!-- POPULARNE KATEGORIJE (Grid) -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">
                <i class="fas fa-fire text-danger me-2"></i> Popularne kategorije
            </h2>
            <span class="badge bg-primary rounded-pill px-3 py-2">
                <?php echo count($mainCategories); ?> kategorija
            </span>
        </div>
        
        <div class="row g-4">
            <?php 
            $popularCategories = array_slice($mainCategories, 0, 8);
            foreach ($popularCategories as $category): 
            ?>
                <div class="col-md-3 col-6">
                    <a href="/ads/category/<?php echo $category['slug']; ?>" 
                       class="category-card text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 text-center category-card-inner">
                            <div class="card-body p-4">
                                <div class="category-icon mb-3">
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-folder'; ?> fa-3x text-primary"></i>
                                </div>
                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo number_format($category['ad_count'] ?? 0); ?> oglasa
                                </small>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($mainCategories) > 8): ?>
        <div class="text-center mt-4">
            <button class="btn btn-outline-primary" id="show-all-categories-btn">
                <i class="fas fa-list me-2"></i> Prikaži sve kategorije
            </button>
        </div>
        <?php endif; ?>
    </section>
    
    
    <!-- SVE KATEGORIJE SA PODKATEGORIJAMA (ELEGANTNI KONTEJNERI) -->
    <section class="mb-5" id="all-categories-section" style="display: none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">
                <i class="fas fa-sitemap text-primary me-2"></i> Sve kategorije
            </h2>
            <button class="btn btn-sm btn-outline-secondary" id="hide-all-categories-btn">
                <i class="fas fa-times me-1"></i> Sakrij
            </button>
        </div>
        
        <div class="row g-4">
            <?php foreach ($mainCategories as $mainCategory): 
                $subcats = $categoriesByParent[$mainCategory['id']] ?? [];
                // Sortiranje je već urađeno gore, ali za svaki slučaj:
                usort($subcats, function($a, $b) {
                    return ($b['ad_count'] ?? 0) - ($a['ad_count'] ?? 0);
                });
            ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 category-group-card">
                        <div class="card-header bg-white border-0 pt-4 pb-0">
                            <a href="/ads/category/<?php echo $mainCategory['slug']; ?>" 
                               class="text-decoration-none">
                                <h4 class="h5 mb-0">
                                    <i class="<?php echo $mainCategory['icon'] ?? 'fas fa-folder'; ?> text-primary me-2"></i>
                                    <?php echo htmlspecialchars($mainCategory['name']); ?>
                                    <span class="badge bg-primary rounded-pill ms-2">
                                        <?php echo number_format($mainCategory['ad_count'] ?? 0); ?>
                                    </span>
                                </h4>
                            </a>
                        </div>
                        <div class="card-body pt-3">
                            <?php if (!empty($subcats)): ?>
                                <div class="row g-2">
                                    <?php foreach ($subcats as $subcat): 
                                        // Ručno dohvati broj oglasa za ovu podkategoriju
                                        $subcatAdCount = getAdCountByCategory($subcat['id']);
                                    ?>
                                        <div class="col-12">
                                            <a href="/ads/category/<?php echo $mainCategory['slug']; ?>/<?php echo $subcat['slug']; ?>" 
                                               class="subcategory-link d-flex justify-content-between align-items-center text-decoration-none py-1 px-2 rounded">
                                                <span>
                                                    <i class="fas fa-angle-right text-muted me-1 small"></i>
                                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                                </span>
                                                <small class="text-muted"><?php echo number_format($subcatAdCount); ?> oglasa</small>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-0">
                                    <i class="fas fa-info-circle me-1"></i> Nema podkategorija
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- STATISTIKA -->
    <section class="bg-light rounded-4 p-5 mb-5 text-center">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="display-4 text-primary fw-bold"><?php echo count($allCategories); ?></div>
                <div class="text-muted">Kategorija</div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="display-4 text-success fw-bold"><?php echo number_format($totalAdsInAllCategories); ?>+</div>
                <div class="text-muted">Aktivnih oglasa</div>
            </div>
            <div class="col-md-4">
                <div class="display-4 text-warning fw-bold"><?php echo count($mainCategories); ?></div>
                <div class="text-muted">Glavnih kategorija</div>
            </div>
        </div>
    </section>
    
    <!-- POMOĆ I PREPORUKE -->
    <section class="mb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center p-4">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h5>Pretražite kategoriju</h5>
                    <p class="text-muted small">Kliknite na kategoriju da vidite sve oglase u njoj.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <i class="fas fa-filter fa-3x text-primary mb-3"></i>
                    <h5>Filtriranje</h5>
                    <p class="text-muted small">Koristite podkategorije za precizniju pretragu.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center p-4">
                    <i class="fas fa-tag fa-3x text-primary mb-3"></i>
                    <h5>Postavite oglas</h5>
                    <p class="text-muted small">Odaberite odgovarajuću kategoriju za vaš oglas.</p>
                </div>
            </div>
        </div>
    </section>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle za sve kategorije
    const showBtn = document.getElementById('show-all-categories-btn');
    const hideBtn = document.getElementById('hide-all-categories-btn');
    const allCategoriesSection = document.getElementById('all-categories-section');
    
    if (showBtn && allCategoriesSection) {
        showBtn.addEventListener('click', function() {
            allCategoriesSection.style.display = 'block';
            // Scroll to section
            allCategoriesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
    
    if (hideBtn && allCategoriesSection) {
        hideBtn.addEventListener('click', function() {
            allCategoriesSection.style.display = 'none';
        });
    }
    
    // Hover efekti za kartice
    const cards = document.querySelectorAll('.category-card-inner');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>