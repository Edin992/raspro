<?php
/**
 * pages/home.php - Početna strana sa premium sliderom
 */

// Postavi title i meta tagove
$pageTitle = 'Rasprodaja.rs - Najveći oglasnik u Srbiji';
$pageDescription = 'Kupujte i prodajte brzo, lako i bezbedno. Preko 100.000 oglasa, automobili, nekretnine, elektronika i više.';
$pageSpecificJS = ['home.js'];
$showBreadcrumbs = false;

// FIX: 'logout_message' u sesiji nikad nije prikazivan jer logout()
// unisti sesiju - poruka se sada nosi preko ?logged_out=1 parametra
$showLoggedOutNotice = isset($_GET['logged_out']);

// Učitaj kategorije za PHP
$popularCategories = getPopularCategories(10);
?>

<?php if ($showLoggedOutNotice): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
        <i class="fas fa-check-circle me-2"></i> Uspešno ste se odjavili.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Zatvori"></button>
    </div>
</div>
<?php endif; ?>

<!-- HERO SECTION - RESPONZIVAN (VIDLJIV NA SVIM UREĐAJIMA) -->
<section class="hero-section mb-5 py-4 py-md-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 col-md-8 mx-auto text-center text-md-start">
                
                <!-- NASLOV SA SLOGANOM -->
                <h1 class="display-5 display-md-4 fw-bold mb-3">
                    <span class="text-gradient">Rasprodaja.rs</span>
                    <span class="text-dark d-block mt-2">Vaša svakodnevna rasprodaja</span>
                </h1>
                
                <!-- PODSLOGAN -->
                <p class="lead mb-4 fs-6 fs-md-5 text-muted">
                    Kupujte povoljno ili prodajte brzo. 
                    <span class="text-dark fw-semibold">Bez provizije</span>, 
                    <span class="text-dark fw-semibold">bez cimanja</span>.
                </p>
                
                <!-- CTA DUGMAD -->
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-md-start">
                    <a href="/create-ad" class="btn btn-cta-primary btn-lg px-5 py-3">
                        <i class="fas fa-plus-circle me-2"></i> Postavi besplatan oglas
                    </a>
                    <a href="/ads" class="btn btn-cta-secondary btn-lg px-5 py-3">
                        <i class="fas fa-search me-2"></i> Pretraži oglase
                    </a>
                </div>
                
                <!-- TRUST SIGNAL BEDŽEVI -->
                <div class="mt-4 d-flex flex-wrap gap-3 justify-content-center justify-content-md-start">
                    <span class="badge-trust">
                        <i class="fas fa-shield-alt text-success me-1"></i> Besplatno postavljanje
                    </span>
                    <span class="badge-trust">
                        <i class="fas fa-clock text-primary me-1"></i> Oglas aktivan 30 dana
                    </span>
                    <span class="badge-trust">
                        <i class="fas fa-users text-warning me-1"></i> 15+ korisnika
                    </span>
                </div>
                
                <!-- STATISTIKE SA ANIMIRANIM BROJAČEM -->
                <div class="row mt-5 g-3">
                    <div class="col-4">
                        <div class="stat-box">
                            <h3 class="stat-number text-primary fw-bold" id="total-ads">15+</h3>
                            <p class="text-muted mb-0 small">Aktivnih oglasa</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <h3 class="stat-number text-success fw-bold" id="daily-ads">0</h3>
                            <p class="text-muted mb-0 small">Novih oglasa dnevno</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <h3 class="stat-number text-warning fw-bold" id="active-users">5+</h3>
                            <p class="text-muted mb-0 small">Aktivnih korisnika</p>
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
