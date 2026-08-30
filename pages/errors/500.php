<?php
http_response_code(500);
$pageTitle = 'Greška servera - Rasprodaja.rs';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <i class="fas fa-server fa-5x text-danger mb-4"></i>
            <h1 class="display-1 text-muted fw-bold">500</h1>
            <h2 class="h3 mb-4">Greška servera</h2>
            <p class="lead mb-4">
                Došlo je do greške na našem serveru.
                Naši inženjeri su obavešteni i rade na rešavanju problema.
            </p>
            
            <div class="alert alert-warning mb-4">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Savet:</strong> Pokušajte ponovo za nekoliko minuta.
                Ako se problem nastavi, kontaktirajte naš tim.
            </div>
            
            <div class="mt-4">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary px-4">
                    <i class="fas fa-home me-2"></i> Početna strana
                </a>
                <a href="/contact" class="btn btn-outline-primary px-4 ms-2">
                    <i class="fas fa-envelope me-2"></i> Kontaktirajte nas
                </a>
            </div>
        </div>
    </div>
</div>