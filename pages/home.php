<?php
/**
 * pages/home.php - Početna strana sa premium sliderom
 */

// Postavi title i meta tagove
$pageTitle = 'Rasprodaja.rs - Najveći oglasnik u Srbiji';
$pageDescription = 'Kupujte i prodajte brzo, lako i bezbedno. Preko 100.000 oglasa, automobili, nekretnine, elektronika i više.';
$pageSpecificJS = ['home.js'];
$showBreadcrumbs = false;

// Učitaj kategorije za PHP
$popularCategories = getPopularCategories(10);
?>

<!-- HERO SECTION - SAKRIVEN NA MOBILNIM UREĐAJIMA -->
<section class="hero-section mb-5 d-none d-md-block">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-12">
                <h1 class="display-4 fw-bold mb-3">
                    Pronađite savršen <span class="text-primary">proizvod</span> ili 
                    <span class="text-success">prodajte</span> brzo
                </h1>
                <p class="lead mb-4">
                    Najveća online zajednica za kupovinu i prodaju u Srbiji. 
                    Preko 100.000 aktivnih oglasa, dnevno 5.000 novih.
                </p>
                
                <div class="d-flex flex-wrap gap-3">
                    <a href="/create-ad" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle me-2"></i> Postavi besplatan oglas
                    </a>
                    <a href="/ads" class="btn btn-outline-dark btn-lg">
                        <i class="fas fa-search me-2"></i> Pretraži oglase
                    </a>
                </div>
                
                <!-- QUICK STATS -->
                <div class="row mt-5">
                    <div class="col-4">
                        <div class="text-center">
                            <h3 class="text-primary fw-bold" id="total-ads">100K+</h3>
                            <p class="text-muted mb-0">Aktivnih oglasa</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center">
                            <h3 class="text-success fw-bold" id="daily-ads">5K+</h3>
                            <p class="text-muted mb-0">Novih oglasa dnevno</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center">
                            <h3 class="text-warning fw-bold" id="active-users">50K+</h3>
                            <p class="text-muted mb-0">Aktivnih korisnika</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Uključi premium slider
include __DIR__ . '/premium-slider.php';
?>

<!-- NOVI OGLASI -->
<section class="mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3">
                <i class="fas fa-bolt text-success me-2"></i> Najnoviji oglasi
            </h2>
            <a href="/ads" class="btn btn-outline-success">
                Vidi sve <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <!-- ✅ OVO JE BITNO: div sa id="new-ads" -->
        <div class="row" id="new-ads">
            <!-- Novi oglasi će se učitati preko JavaScript -->
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Učitavanje novih oglasa...</span>
                </div>
                <p class="mt-2 text-muted">Učitavam najnovije oglase...</p>
            </div>
        </div>
    </div>
</section>

<!-- KATEGORIJE -->
<section class="mb-5">
    <div class="container">
        <h2 class="h3 mb-4">Istražite kategorije</h2>
        
        <div class="row" id="categories-grid">
            <?php if (!empty($popularCategories)): ?>
                <?php foreach ($popularCategories as $category): ?>
                    <div class="col-md-3 col-sm-6 mb-4">
                        <a href="/ads/category/<?php echo $category['id']; ?>" 
                           class="card category-card text-decoration-none h-100">
                            <div class="card-body text-center p-4">
                                <div class="category-icon mb-3">
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-folder'; ?> fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                <p class="card-text text-muted small">
                                    <?php echo $category['ad_count'] ?? 0; ?> oglasa
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Nema dostupnih kategorija</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="/categories" class="btn btn-outline-dark">
                <i class="fas fa-list me-2"></i> Vidi sve kategorije
            </a>
        </div>
    </div>
</section>

<!-- KAKO RADI? - SAKRIVEN NA MOBILNIM UREĐAJIMA -->
<section class="bg-light py-5 mb-5 d-none d-md-block">
    <div class="container">
        <h2 class="h3 text-center mb-5">Kako radi Rasprodaja.rs?</h2>
        
        <div class="row">
            <div class="col-md-3 text-center mb-4">
                <div class="step-number mb-3">1</div>
                <h5>Registruj se</h5>
                <p class="text-muted">Napravi nalog za 30 sekundi</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="step-number mb-3">2</div>
                <h5>Postavi oglas</h5>
                <p class="text-muted">Dodaj fotografije i opis</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="step-number mb-3">3</div>
                <h5>Komuniciraj</h5>
                <p class="text-muted">Odgovori na poruke kupaca</p>
            </div>
            <div class="col-md-3 text-center mb-4">
                <div class="step-number mb-3">4</div>
                <h5>Prodaj</h5>
                <p class="text-muted">Dogovori se i prodaј</p>
            </div>
        </div>
    </div>
</section>

<!-- ✅ DODAJ OVO ZA DEBUG: -->
<!-- <div id="debug-info" class="d-none"></div> -->