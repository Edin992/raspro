<?php
http_response_code(403);
$pageTitle = 'Pristup zabranjen - Rasprodaja.rs';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <i class="fas fa-ban fa-5x text-danger mb-4"></i>
            <h1 class="display-1 text-muted fw-bold">403</h1>
            <h2 class="h3 mb-4">Pristup zabranjen</h2>
            <p class="lead mb-4">
                Nemate potrebna prava za pristup ovoj stranici.
                Ova stranica je zaštićena i zahteva odgovarajuća ovlašćenja.
            </p>
            
            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary px-4">
                    <i class="fas fa-home me-2"></i> Početna strana
                </a>
                <?php if (!isLoggedIn()): ?>
                    <a href="/login" class="btn btn-outline-primary px-4 ms-2">
                        <i class="fas fa-sign-in-alt me-2"></i> Prijavi se
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>