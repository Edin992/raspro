<?php
/**
 * pages/user/forgot-password.php - Stranica za zaboravljenu lozinku
 */

$pageTitle = 'Zaboravili ste lozinku? - Rasprodaja.rs';
$pageDescription = 'Resetujte svoju lozinku na Rasprodaja.rs';
$pageSpecificCSS = [];
$pageSpecificJS = ['forgot-password.js'];
//$showBreadcrumbs = true;
?>

<!-- BREADCRUMBS -->
<?php if ($showBreadcrumbs): ?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?page=home">Početna</a></li>
        <li class="breadcrumb-item"><a href="?page=login">Prijava</a></li>
        <li class="breadcrumb-item active" aria-current="page">Zaboravljena lozinka</li>
    </ol>
</nav>
<?php endif; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- CARD -->
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i> Resetovanje lozinke
                    </h4>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <!-- SUCCESS MESSAGE (hidden initially) -->
                    <div class="alert alert-success d-none" id="success-message">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="success-text"></span>
                    </div>
                    
                    <!-- ERROR MESSAGE (hidden initially) -->
                    <div class="alert alert-danger d-none" id="error-message">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="error-text"></span>
                    </div>
                    
                    <!-- VERIFICATION REQUIRED MESSAGE -->
                    <div class="alert alert-warning d-none" id="verification-message">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="verification-text"></span>
                        <div class="mt-2">
                            <a href="/verify-email" class="btn btn-sm btn-warning">
                                Verifikuj nalog
                            </a>
                        </div>
                    </div>
                    
                    <!-- RATE LIMIT MESSAGE -->
                    <div class="alert alert-info d-none" id="rate-limit-message">
                        <i class="fas fa-clock me-2"></i>
                        <span id="rate-limit-text"></span>
                    </div>
                    
                    <!-- FORM -->
                    <form id="forgot-password-form" novalidate>
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i> Unesite Vašu email adresu
                            </label>
                            <input type="email" 
                                   class="form-control form-control-lg" 
                                   id="email" 
                                   name="email"
                                   placeholder="npr. pera.peric@example.com"
                                   required
                                   autofocus>
                            <div class="invalid-feedback">
                                Unesite validnu email adresu.
                            </div>
                            <small class="form-text text-muted">
                                Poslaćemo Vam link za resetovanje lozinke.
                            </small>
                        </div>
                        
                        <!-- RATE LIMIT INFO -->
                        <div class="alert alert-light border" id="rate-info" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-primary me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Sigurnosna informacija:</small>
                                    <small>
                                        <span id="attempts-count">0</span> od 3 pokušaja u poslednjih sat vremena.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                <i class="fas fa-paper-plane me-2"></i> Pošalji reset link
                            </button>
                            
                            <a href="/login" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Nazad na prijavu
                            </a>
                        </div>
                    </form>
                    
                    <!-- ADDITIONAL HELP -->
                    <div class="mt-5 pt-4 border-top text-center">
                        <p class="text-muted small mb-2">Imate problema sa resetovanjem?</p>
                        <a href="/contact" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-headset me-1"></i> Kontaktirajte podršku
                        </a>
                    </div>
                </div>
                
                <!-- CARD FOOTER -->
                <div class="card-footer bg-light text-center py-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Link za resetovanje važi 2 sata i može se koristiti samo jednom.
                    </small>
                </div>
            </div>
            
            <!-- SECURITY TIPS -->
            <div class="mt-4">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-shield-alt text-success me-2"></i> Sigurnosni saveti
                        </h6>
                        <ul class="small mb-0">
                            <li>Link za resetovanje šaljemo samo na email povezan sa nalogom</li>
                            <li>Nikada ne delite svoj reset link sa drugima</li>
                            <li>Koristite jaku, jedinstvenu lozinku</li>
                            <li>Ako niste zatražili reset, ignorišite email</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>