<?php
/**
 * pages/user/register.php - Stranica za registraciju novog korisnika
 */

// Ako je korisnik već ulogovan, preusmeri na profil
if (isLoggedIn()) {
    redirect('/profile');
}

// Postavi title i meta tagove
$pageTitle = 'Registruj se - Rasprodaja.rs';
$pageDescription = 'Napravite nalog na Rasprodaja.rs i počnite da kupujete i prodajete.';
$pageSpecificCSS = ['auth.css'];
$pageSpecificJS = ['register.js'];

// Gradovi iz baze
$popularCities = getPopularCities(20);

$inlineScripts = "
    window.pageInit = function() {
        // Sada će se automatski inicijalizovati RegistrationForm klasa
        // iz register.js (u DOMContentLoaded eventu)
        // Ne treba ništa ovde jer je sve u register.js
        console.log('Register page initialized');
        
        // Opciono: Inicijalizuj city autocomplete ako je potrebno
        //initCityAutocomplete();
    };
    
    // Globalne JS funkcije za ovu stranu
    function initCityAutocomplete() {
        const cityInput = document.getElementById('city');
        const datalist = document.getElementById('cities-list');
        
        if (!cityInput || !datalist) return;
        
        // Dohvati gradove iz baze preko API-ja
        fetch(SITE_CONFIG.url + '/api/cities/popular.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cities) {
                    // Popuni datalist
                    data.cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        datalist.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading cities:', error));
    }
";
?>

<?php echo recaptcha_render_scripts(); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- REGISTRACIJA KARTICA -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h2 class="h3 mb-0">
                        <i class="fas fa-user-plus me-2"></i> Kreiraj nalog
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">Besplatna registracija za sve korisnike</p>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <!-- FORMA ZA REGISTRACIJU -->
                    <form id="register-form" novalidate>
                        <!-- CSRF TOKEN -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="recaptcha_token" id="recaptcha-token">
                        
                        <!-- JEDNOSTEPENA FORMA (nema više koraka) -->
                        <div class="form-step">
                            <div style="display:none !important; height:0 !important; width:0 !important; overflow:hidden !important; opacity:0 !important; position:absolute !important; left:-9999px !important;">
                                <label for="ime">Ime</label>
                                <input type="text" name="ime" id="ime" value="" autocomplete="off" tabindex="-1">
                            </div>

                            
                            <!-- TIP NALOGA -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tip naloga *</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="account_type" id="account_private" 
                                               value="private" checked>
                                        <label class="form-check-label" for="account_private">
                                            <i class="fas fa-user me-1"></i> Privatno lice
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="account_type" id="account_company" 
                                               value="company">
                                        <label class="form-check-label" for="account_company">
                                            <i class="fas fa-building me-1"></i> Firma / Preduzetnik
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- POLJE ZA NAZIV FIRME (sakriveno početno) -->
                            <div id="company-name-field" style="display: none;">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Naziv firme *</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" maxlength="100">
                                    <div class="form-text">Unesite pun naziv vaše firme.</div>
                                    <div class="invalid-feedback">Naziv firme je obavezan.</div>
                                </div>
                            </div>
                            
                            <!-- IME I PREZIME (originalno) -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label" id="first_name_label">Ime *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="50">
                                    <div class="invalid-feedback">Unesite vaše ime.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label" id="last_name_label">Prezime *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="50">
                                    <div class="invalid-feedback">Unesite vaše prezime.</div>
                                </div>
                            </div>
                            
                            <!-- KORISNIČKO IME -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Korisničko ime *</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="username" 
                                           name="username" required minlength="3" maxlength="30"
                                           placeholder="izaberite_korisnicko"
                                           pattern="[a-zA-Z0-9_]+">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            id="check-username">
                                        Proveri
                                    </button>
                                </div>
                                <div class="form-text">
                                    Dozvoljena slova, brojevi i donja crta. Minimum 3 karaktera.
                                </div>
                                <div class="invalid-feedback" id="username-feedback">
                                    Korisničko ime nije validno.
                                </div>
                            </div>
                            
                            <!-- EMAIL -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email adresa *</label>
                                <input type="email" class="form-control" id="email" 
                                       name="email" required
                                       placeholder="vas.email@primer.com">
                                <div class="form-text">
                                    Koristićemo ovaj email za verifikaciju i notifikacije.
                                </div>
                                <div class="invalid-feedback">Unesite validnu email adresu.</div>
                            </div>
                            
                            <!-- TELEFON -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Broj telefona *</label>
                                <div class="input-group">
                                    <span class="input-group-text">+381</span>
                                    <input type="tel" class="form-control" id="phone" 
                                           name="phone" required
                                           placeholder="60 123 4567"
                                           pattern="[0-9\s]{8,15}">
                                </div>
                                <div class="form-text">
                                    Kupci će videti ovaj broj kada kontaktiraju vaše oglase.
                                </div>
                                <div class="invalid-feedback">Unesite validan broj telefona.</div>
                            </div>
                            
                            <!-- LOZINKA -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Lozinka *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" 
                                           name="password" required minlength="8"
                                           placeholder="Najmanje 8 karaktera">
                                    <button class="btn btn-outline-secondary" type="button" 
                                            id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                
                                <!-- PROGRESS BAR ZA LOZINKU -->
                                <div class="mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="password-strength-bar" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted" id="password-strength-text">
                                        Jačina lozinke: slaba
                                    </small>
                                </div>
                                
                                <!-- ZAHTEVI ZA LOZINKU -->
                                <div class="mt-2">
                                    <small class="d-block mb-1">Lozinka mora sadržati:</small>
                                    <div class="row small">
                                        <div class="col-6">
                                            <span class="password-requirement" data-rule="length">
                                                <i class="fas fa-times text-danger me-1"></i>
                                                Najmanje 8 karaktera
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="password-requirement" data-rule="uppercase">
                                                <i class="fas fa-times text-danger me-1"></i>
                                                Veliko slovo
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="password-requirement" data-rule="lowercase">
                                                <i class="fas fa-times text-danger me-1"></i>
                                                Malo slovo
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="password-requirement" data-rule="number">
                                                <i class="fas fa-times text-danger me-1"></i>
                                                Broj
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- POTVRDA LOZINKE -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    Potvrdite lozinku *
                                </label>
                                <input type="password" class="form-control" 
                                       id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Lozinke se ne poklapaju.</div>
                            </div>
                            
                            
                            
                            <!-- LOKACIJA - JEDNOSTAVAN DROPDOWN -->
                            <div class="mb-4">
                                <label for="city" class="form-label">Grad/Mesto *</label>
                                <select class="form-select" id="city" name="city" required>
                                    <option value="">Izaberite grad</option>
                                    <?php
                                    $cities = getCitiesList();
                                    foreach ($cities as $city):
                                    ?>
                                        <option value="<?php echo $city['name']; ?>">
                                            <?php echo htmlspecialchars($city['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Izaberite vaš grad. Ovo će biti vaša primarna lokacija za oglase.
                                </div>
                                <div class="invalid-feedback">Molimo izaberite grad.</div>
                            </div>
                            
                            <!-- USLOVI -->
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" 
                                           id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Prihvatam <a href="/terms" target="_blank">Uslove korišćenja</a> *
                                    </label>
                                    <div class="invalid-feedback">
                                        Morate prihvatiti uslove korišćenja.
                                    </div>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="newsletter" name="newsletter" >
                                    <label class="form-check-label" for="newsletter">
                                        Želim da primam promotivne poruke i novosti
                                    </label>
                                </div>
                            </div>
                            
                            <?php if (recaptcha_is_enabled()): ?>
                            <p class="text-center small text-muted mb-2">
                                Zaštićeno reCAPTCHA tehnologijom — primenjuju se
                                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google Politika privatnosti</a>
                                i <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Uslovi korišćenja</a>.
                            </p>
                            <?php endif; ?>

                            <!-- DUGMAD -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i> Kreiraj nalog
                                </button>
                                
                                <a href="/login" class="btn btn-outline-secondary">
                                    Već imate nalog? Prijavite se
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- SOCIAL LOGIN 
                    <div class="mt-4 pt-4 border-top text-center">
                        <p class="text-muted mb-3">Ili se prijavite putem</p>
                        <div class="d-flex justify-content-center gap-3">
                            <button class="btn btn-outline-dark">
                                <i class="fab fa-google me-2"></i> Google
                            </button>
                            <button class="btn btn-outline-primary">
                                <i class="fab fa-facebook me-2"></i> Facebook
                            </button>
                        </div>
                    </div>
                    -->
                </div>
            </div>
            
            <!-- INFORMACIJE NAKON REGISTRACIJE -->
            <div class="mt-4">
                <div class="card border-info">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle text-info me-2"></i> Šta dobijate registracijom?
                        </h5>
                        <ul class="mb-0">
                            <li>Besplatno postavljanje oglasa</li>
                            <li>Direktnu komunikaciju sa kupcima</li>
                            <li>Upravljanje oglasima iz jednog mesta</li>
                            <li>Obaveštenja o novim porukama</li>
                            <li>Mogućnost nadogradnje paketa kasnije</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LOADING OVERLAY -->
<div class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none" 
     id="register-loading" style="z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center bg-white p-5 rounded shadow">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Učitavanje...</span>
            </div>
            <h5>Kreiram vaš nalog...</h5>
            <p class="text-muted">Molimo sačekajte</p>
        </div>
    </div>
</div>