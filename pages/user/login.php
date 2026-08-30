<?php
/**
 * pages/user/login.php - Stranica za prijavu (CLEAN VERZIJA)
 */

// Ako je korisnik već ulogovan, preusmeri
if (isLoggedIn()) {
    redirect('?page=dashboard');
}

// Postavi title
$pageTitle = 'Prijavi se - Rasprodaja.rs';
$pageDescription = 'Prijavite se na svoj nalog na Rasprodaja.rs';
$pageSpecificCSS = ['auth.css'];
$pageSpecificJS = ['login.js']; // ✅ DODAJEMO NOVI JS FAJL


?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- LOGIN KARTICA -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="h3 mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i> Prijava
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">Prijavite se na svoj nalog</p>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <!-- FORMA ZA PRIJAVU -->
                    <form id="login-form" novalidate>
                        <div id="login-error"></div>
                        <div style="display:none !important; height:0 !important; width:0 !important; overflow:hidden !important; opacity:0 !important; position:absolute !important; left:-9999px !important;">
                                <label for="ime">Ime</label>
                                <input type="text" name="ime" id="ime" value="" autocomplete="off" tabindex="-1">
                            </div>
                        <!-- KORISNIČKO IME ILI EMAIL -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Korisničko ime ili Email *</label>
                            <input type="text" class="form-control" id="username" 
                                   name="username" required
                                   placeholder="Unesite korisničko ime ili email">
                            <div class="invalid-feedback">
                                Unesite korisničko ime ili email.
                            </div>
                        </div>
                        
                        <!-- LOZINKA -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Lozinka *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" 
                                       name="password" required
                                       placeholder="Vaša lozinka">
                                <button class="btn btn-outline-secondary" type="button" 
                                        id="toggle-password-login">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Unesite vašu lozinku.
                            </div>
                        </div>
                        
                        <!-- ZAPAMTI ME I ZABORAVLJENA LOZINKA -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Zapamti me
                                </label>
                            </div>
                            <a href="/forgot-password" class="text-decoration-none small">
                                Zaboravili ste lozinku?
                            </a>
                        </div>
                        
                        <!-- DUGME ZA PRIJAVU -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Prijavi se
                            </button>
                        </div>
                        
                        <!-- REGISTRACIJA LINK -->
                        <div class="text-center">
                            <p class="mb-0">Nemate nalog? 
                                <a href="/register" class="text-decoration-none fw-bold">
                                    Registrujte se
                                </a>
                            </p>
                        </div>
                    </form>
                    
                    <!-- SOCIAL LOGIN 
                    <div class="mt-4 pt-4 border-top text-center">
                        <p class="text-muted mb-3">Ili se prijavite putem</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn btn-outline-dark btn-sm">
                                <i class="fab fa-google me-2"></i> Google
                            </button>
                            <button class="btn btn-outline-primary btn-sm">
                                <i class="fab fa-facebook me-2"></i> Facebook
                            </button>
                        </div>
                    </div>
                    -->
                </div>
            </div>
            
            <!-- INFO PORUKA -->
            <div class="mt-4">
                <div class="alert alert-info">
                    <h5 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i> Zašto da se prijavite?
                    </h5>
                    <ul class="mb-0">
                        <li>Postavljajte i upravljajte oglasima</li>
                        <li>Komunikujte direktno sa kupcima/prodavcima</li>
                        <li>Sačuvajte omiljene oglase</li>
                        <li>Primajte notifikacije</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

