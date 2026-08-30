<?php
/**
 * pages/errors/404.php - Stranica nije pronađena
 */

http_response_code(404);
$pageTitle = 'Stranica nije pronađena - Rasprodaja.rs';
$pageDescription = 'Tražena stranica ne postoji na Rasprodaja.rs';

// Breadcrumbs
$breadcrumbs = [
    ['text' => 'Početna', 'url' => SITE_URL, 'active' => false],
    ['text' => '404 Stranica nije pronađena', 'url' => '', 'active' => true]
];
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 text-center">
            
            <!-- Error ikonica -->
            <div class="error-icon mb-4">
                <div class="error-icon-circle">
                    <i class="fas fa-exclamation-triangle fa-4x text-warning"></i>
                </div>
            </div>
            
            <!-- Error broj -->
            <h1 class="display-1 text-muted mb-0" style="font-weight: 800;">404</h1>
            
            <!-- Naslov -->
            <h2 class="h1 mb-3">Stranica nije pronađena</h2>
            
            <!-- Poruka -->
            <p class="lead text-muted mb-4">
                Tražena stranica ne postoji ili je uklonjena sa našeg sajta.
                Proverite da li ste pravilno uneli adresu ili koristite dugmad ispod.
            </p>
            
            <!-- Dodatne informacije (opciono) -->
            <div class="alert alert-info mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-lg me-3"></i>
                    <div>
                        <strong>Šta se dogodilo?</strong>
                        <p class="mb-0 small">
                            Stranica koju tražite možda nije postavljena, 
                            ili ste uneli pogrešan URL. Takođe, moguće je da je oglas uklonjen ili istekao.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Dugmad za akciju -->
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-5">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-home me-2"></i> Nazad na početnu
                </a>
                
                <a href="/ads" class="btn btn-outline-primary btn-lg px-4">
                    <i class="fas fa-search me-2"></i> Pretraži oglase
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="/dashboard" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-tachometer-alt me-2"></i> Kontrolna tabla
                    </a>
                <?php else: ?>
                    <a href="/login" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="fas fa-sign-in-alt me-2"></i> Prijavi se
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Pretraga (opciono) -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Pretražite naš sajt</h5>
                    <form action="/ads" method="GET" class="row g-2">
                        <input type="hidden" name="page" value="ads">
                        <div class="col-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" 
                                       name="q" 
                                       class="form-control border-start-0" 
                                       placeholder="Šta tražite? (npr. iPhone, stan, auto...)"
                                       required>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i> Pretraži
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Popularne kategorije (opciono) -->
            <div class="mt-5">
                <h5 class="mb-3">Popularne kategorije</h5>
                <div class="row g-2">
                    <?php
                    $popularCategories = getPopularCategories(6);
                    if (!empty($popularCategories)):
                    ?>
                        <?php foreach ($popularCategories as $category): ?>
                            <div class="col-6 col-md-4">
                                <a href="/ads/category=<?php echo $category['slug']; ?>" 
                                   class="btn btn-outline-secondary w-100 text-start mb-2">
                                    <?php if (!empty($category['icon'])): ?>
                                        <i class="<?php echo $category['icon']; ?> me-2"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <?php if ($category['ad_count'] > 0): ?>
                                        <span class="badge bg-primary float-end"><?php echo $category['ad_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-muted">Nema dostupnih kategorija.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Kontakt (opciono) -->
            <div class="mt-5 pt-4 border-top">
                <p class="text-muted small mb-0">
                    Ako smatrate da ovo jeste greška sa našeg sajta, 
                    <a href="/contact">kontaktirajte nas</a>.
                </p>
            </div>
            
        </div>
    </div>
</div>

<style>
.error-icon-circle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background-color: rgba(255, 193, 7, 0.1);
    border: 3px solid rgba(255, 193, 7, 0.2);
    margin-bottom: 2rem;
}

/* Animacija za 404 broj */
.display-1 {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 0.8; }
    50% { opacity: 1; }
    100% { opacity: 0.8; }
}

/* Responsive stilovi */
@media (max-width: 768px) {
    .display-1 {
        font-size: 4rem;
    }
    
    .error-icon-circle {
        width: 100px;
        height: 100px;
    }
    
    .error-icon-circle i {
        font-size: 3rem;
    }
}
</style>

<!-- Dodatni JavaScript za praćenje 404 grešaka -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('404 stranica učitana: ' + window.location.href);
    
    // Opciono: Loguj 404 grešku na server
    try {
        fetch('/api/log/error.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: '404',
                url: window.location.href,
                referrer: document.referrer,
                timestamp: new Date().toISOString()
            })
        }).catch(function() {
            // Ignoriši greške pri slanju log-a
        });
    } catch (e) {
        // Ignoriši sve greške
    }
    
    // Fokusiraj se na polje za pretragu
    var searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        setTimeout(function() {
            searchInput.focus();
        }, 300);
    }
});
</script>