<?php
/**
 * pages/legal/cookies.php - Politika kolačića (Cookies Policy)
 */

$pageTitle = 'Politika kolačića - Rasprodaja.rs';
$pageDescription = 'Politika kolačića platforme Rasprodaja.rs. Kako koristimo kolačiće i kako možete da ih kontrolišete.';
$showBreadcrumbs = true;
$currentPage = 'cookies';
?>

<!-- HERO SECTION -->
<section class="cookies-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold text-white mb-3">
                    Politika kolačića
                </h1>
                <p class="lead text-white mb-0">
                    Kako koristimo kolačiće i kako možete da ih kontrolišete
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="cookies-hero-icon">
                    <i class="fas fa-cookie-bite fa-6x text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COOKIES CONSENT BANNER (AKO NIJE PRIHVATIO) -->
<div id="cookiesConsentBanner" class="fixed-bottom p-3 bg-dark text-white d-none">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="mb-0">
                    <i class="fas fa-cookie-bite me-2"></i>
                    Koristimo kolačiće za poboljšanje vašeg iskustva. 
                    Nastavkom korišćenja prihvatate našu Politiku kolačića.
                    <a href="/cookies" class="text-warning text-decoration-none ms-1">Saznaj više</a>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
                <button class="btn btn-outline-light btn-sm" id="rejectCookies">
                    Odbij neophodne
                </button>
                <button class="btn btn-primary btn-sm ms-2" id="acceptAllCookies">
                    Prihvatam sve
                </button>
                <button class="btn btn-success btn-sm ms-2" id="manageCookies">
                    Prilagodi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- COOKIES SETTINGS MODAL -->
<div class="modal fade" id="cookiesSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sliders-h me-2"></i> Podešavanje kolačića
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Izaberite koje vrste kolačića želite da prihvatite. 
                    Neophodni kolačići su uvek aktivni jer su potrebni za osnovno funkcionisanje sajta.
                </p>
                
                <div class="cookies-category mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-primary me-2">Neophodni</span>
                                Uvek aktivni
                            </h6>
                            <small class="text-muted">Session, sigurnosni, autentifikacioni kolačići</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked disabled>
                        </div>
                    </div>
                    <p class="small mb-0">
                        Ovi kolačići su neophodni za funkcionisanje sajta. Bez njih ne možete da se prijavite 
                        ili koristite osnovne funkcionalnosti.
                    </p>
                </div>
                
                <div class="cookies-category mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-success me-2">Funkcionalni</span>
                            </h6>
                            <small class="text-muted">Jezičke postavke, tema, preferencije</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="functionalCookies" checked>
                        </div>
                    </div>
                    <p class="small mb-0">
                                Omogućavaju da sajt zapamti vaše izbore (kao što su jezik, region ili temu) 
                        i pružaju poboljšane, ličnije funkcije.
                    </p>
                </div>
                
                <div class="cookies-category mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-info me-2">Analitički</span>
                            </h6>
                            <small class="text-muted">Google Analytics, statistika poseta</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="analyticsCookies">
                        </div>
                    </div>
                    <p class="small mb-0">
                        Omogućavaju nam da razumemo kako posetioci koriste naš sajt. 
                        Ovi podaci su anonimni i pomažu nam da poboljšamo funkcionalnost.
                    </p>
                </div>
                
                <div class="cookies-category">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-warning me-2">Marketinški</span>
                            </h6>
                            <small class="text-muted">Reklamne mreže, targetiranje</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="marketingCookies">
                        </div>
                    </div>
                    <p class="small mb-0">
                                Koriste se za praćenje posetilaca na različitim sajtovima. Cilj je prikazati 
                        relevantne oglase za pojedinačnog korisnika.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                <button type="button" class="btn btn-primary" id="saveCookiesSettings">Sačuvaj izbore</button>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 d-none d-lg-block " style="z-index: 10;">
                <!-- SIDEBAR NAVIGACIJA -->
                <div class="sticky-top" style="top: 100px;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title fw-bold mb-3">
                                <i class="fas fa-list me-2"></i> Sadržaj
                            </h6>
                            <nav class="nav flex-column cookies-nav">
                                <a class="nav-link py-2" href="#section-1">
                                    <i class="fas fa-chevron-right me-2"></i> Šta su kolačići?
                                </a>
                                <a class="nav-link py-2" href="#section-2">
                                    <i class="fas fa-chevron-right me-2"></i> Kako ih koristimo?
                                </a>
                                <a class="nav-link py-2" href="#section-3">
                                    <i class="fas fa-chevron-right me-2"></i> Vrste kolačića
                                </a>
                                <a class="nav-link py-2" href="#section-4">
                                    <i class="fas fa-chevron-right me-2"></i> Kolačići trećih strana
                                </a>
                                <a class="nav-link py-2" href="#section-5">
                                    <i class="fas fa-chevron-right me-2"></i> Kako da ih kontrolišete?
                                </a>
                                <a class="nav-link py-2" href="#section-6">
                                    <i class="fas fa-chevron-right me-2"></i> Često postavljana pitanja
                                </a>
                            </nav>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" id="openCookiesSettings">
                                <i class="fas fa-sliders-h me-2"></i> Podešavanje kolačića
                            </button>
                            <a href="/privacy" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-shield-alt me-2"></i> Politika privatnosti
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <!-- GLAVNI SADRŽAJ -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-5">
                            <p class="text-muted">
                                <strong>Datum stupanja na snagu:</strong> <?php echo date('d.m.Y'); ?><br>
                                <strong>Poslednja izmena:</strong> <?php echo date('d.m.Y'); ?>
                            </p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Ova Politika kolačića objašnjava kako Rasprodaja.rs koristi kolačiće i slične tehnologije. 
                                Korišćenjem naše platforme prihvatate upotrebu kolačića u skladu sa ovom politikom.
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 1 -->
                        <div id="section-1" class="cookies-section mb-5">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">1</span>
                                Šta su kolačići (cookies)?
                            </h2>
                            <div class="ms-5">
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-8">
                                        <p>
                                            <strong>Kolačići</strong> su male tekstualne datoteke koje se čuvaju na vašem 
                                            uređaju (računaru, tabletu ili telefonu) kada posetite web stranicu. 
                                            Oni omogućavaju web lokaciji da zapamti vaše akcije i preferencije 
                                            (kao što su prijava, jezik, font veličina i druge postavke prikaza) 
                                            tokom određenog vremenskog perioda.
                                        </p>
                                        <p class="mb-0">
                                            Kolačići <strong>nisu virusi</strong> i ne mogu da pokreću programe na vašem računaru. 
                                            Ne mogu da pristupe ili iskopaju vaše lične podatke sa vašeg uređaja.
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="cookies-illustration">
                                            <i class="fas fa-cookie fa-5x text-primary"></i>
                                            <i class="fas fa-laptop fa-4x text-secondary position-absolute" style="top: 20px; left: 60px; opacity: 0.7;"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Šta kolačići rade?
                                                </h6>
                                                <ul class="small mb-0">
                                                    <li>Zapamte vaše prijave</li>
                                                    <li>Sačuvaju postavke i preferencije</li>
                                                    <li>Poboljšaju performanse sajta</li>
                                                    <li>Omoguće personalizaciju</li>
                                                    <li>Pomažu u analizi poseta</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-times-circle text-danger me-2"></i>
                                                    Šta kolačići NE rade?
                                                </h6>
                                                <ul class="small mb-0">
                                                    <li>Ne pokreću programe</li>
                                                    <li>Ne sadrže viruse</li>
                                                    <li>Ne pristupaju ličnim fajlovima</li>
                                                    <li>Ne kradu lozinke direktno</li>
                                                    <li>Ne špijuniraju vas bez vaše saglasnosti</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 2 -->
                        <div id="section-2" class="cookies-section mb-5">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">2</span>
                                Kako Rasprodaja.rs koristi kolačiće?
                            </h2>
                            <div class="ms-5">
                                <p>
                                    Koristimo kolačiće za nekoliko ključnih svrha:
                                </p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-user-check fa-3x text-primary"></i>
                                            </div>
                                            <h5>Autentifikacija</h5>
                                            <p class="small">
                                                Da zapamtimo da ste prijavljeni i omogućimo vam pristup 
                                                vašem nalogu i oglasima.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-cogs fa-3x text-success"></i>
                                            </div>
                                            <h5>Postavke</h5>
                                            <p class="small">
                                                Da zapamtimo vaše preferencije kao što su jezik, tema 
                                                (svetla/tamna) i druge personalizacije.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-shield-alt fa-3x text-danger"></i>
                                            </div>
                                            <h5>Bezbednost</h5>
                                            <p class="small">
                                                Da sprečimo zloupotrebe, prepoznamo prevare i zaštitimo 
                                                vaše podatke i naš sistem.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-chart-line fa-3x text-info"></i>
                                            </div>
                                            <h5>Analitika</h5>
                                            <p class="small">
                                                Da razumemo kako korisnici koriste naš sajt i gde možemo 
                                                napraviti poboljšanja.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-shopping-cart fa-3x text-warning"></i>
                                            </div>
                                            <h5>Funkcionalnost</h5>
                                            <p class="small">
                                                Da omogućimo napredne funkcije kao što su pretraga, filteri, 
                                                čuvanje istorije pregleda.
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-4">
                                        <div class="text-center p-3 h-100">
                                            <div class="cookies-purpose-icon mb-3">
                                                <i class="fas fa-bell fa-3x text-secondary"></i>
                                            </div>
                                            <h5>Obaveštenja</h5>
                                            <p class="small">
                                                Da upravljamo obaveštenjima i prikažemo relevantne informacije 
                                                i ažuriranja.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 3 -->
                        <div id="section-3" class="cookies-section mb-5">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">3</span>
                                Vrste kolačića koje koristimo
                            </h2>
                            <div class="ms-5">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="20%">Kategorija</th>
                                                <th width="25%">Naziv kolačića</th>
                                                <th width="35%">Svrha</th>
                                                <th width="20%">Trajanje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- NEOPHODNI -->
                                            <tr>
                                                <td rowspan="3" class="align-middle">
                                                    <span class="badge bg-primary">Neophodni</span>
                                                    <small class="d-block mt-1">Uvek aktivni</small>
                                                </td>
                                                <td><code>session_id</code></td>
                                                <td>Odražava sesiju korisnika, omogućava prijavu</td>
                                                <td>Sesija</td>
                                            </tr>
                                            <tr>
                                                <td><code>csrf_token</code></td>
                                                <td>Zaštita od CSRF napada, bezbednost forme</td>
                                                <td>Sesija</td>
                                            </tr>
                                            <tr>
                                                <td><code>cookie_consent</code></td>
                                                <td>Zapamti vaš izbor o kolačićima</td>
                                                <td>1 godina</td>
                                            </tr>
                                            
                                            <!-- FUNKCIONALNI -->
                                            <tr>
                                                <td rowspan="3" class="align-middle">
                                                    <span class="badge bg-success">Funkcionalni</span>
                                                </td>
                                                <td><code>user_language</code></td>
                                                <td>Zapamti izabrani jezik interfejsa</td>
                                                <td>1 godina</td>
                                            </tr>
                                            <tr>
                                                <td><code>site_theme</code></td>
                                                <td>Zapamti izabranu temu (svetlu/tamnu)</td>
                                                <td>1 godina</td>
                                            </tr>
                                            <tr>
                                                <td><code>search_filters</code></td>
                                                <td>Zapamti poslednje korišćene filtere pretrage</td>
                                                <td>7 dana</td>
                                            </tr>
                                            
                                            <!-- ANALITIČKI -->
                                            <tr>
                                                <td rowspan="2" class="align-middle">
                                                    <span class="badge bg-info">Analitički</span>
                                                </td>
                                                <td><code>_ga</code></td>
                                                <td>Google Analytics - razlikuje korisnike</td>
                                                <td>2 godine</td>
                                            </tr>
                                            <tr>
                                                <td><code>_gid</code></td>
                                                <td>Google Analytics - razlikuje sesije</td>
                                                <td>24 sata</td>
                                            </tr>
                                            
                                            <!-- MARKETINŠKI -->
                                            <tr>
                                                <td class="align-middle">
                                                    <span class="badge bg-warning">Marketinški</span>
                                                </td>
                                                <td><code>fbp</code></td>
                                                <td>Facebook Pixel - praćenje konverzija</td>
                                                <td>3 meseca</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="alert alert-light mt-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Trajanje kolačića:</strong> "Sesija" znači da se kolačić briše kada zatvorite browser. 
                                    Ostali ostaju na vašem uređaju dok ne isteknu ili dok ih ručno ne obrišete.
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 4 -->
                        <div id="section-4" class="cookies-section mb-5">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">4</span>
                                Kolačići trećih strana
                            </h2>
                            <div class="ms-5">
                                <p>
                                    Neki kolačići na našem sajtu postavljaju treće strane. Ovi kolačići 
                                    mogu da prikupljaju informacije koje se koriste za merenje efikasnosti 
                                    reklama, personalizaciju sadržaja i druge svrhe.
                                </p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="me-3">
                                                        <i class="fab fa-google fa-2x" style="color: #4285F4;"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="card-title mb-0">Google Analytics</h5>
                                                        <small class="text-muted">Analitički servis</small>
                                                    </div>
                                                </div>
                                                <p class="card-text small">
                                                    Koristimo Google Analytics da razumemo kako posetioci koriste naš sajt. 
                                                    Google koristi kolačiće za prikupljanje anonimnih statističkih podataka.
                                                </p>
                                                <a href="https://policies.google.com/technologies/cookies" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-external-link-alt me-1"></i> Saznaj više
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="me-3">
                                                        <i class="fab fa-facebook fa-2x" style="color: #1877F2;"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="card-title mb-0">Facebook Pixel</h5>
                                                        <small class="text-muted">Reklamna mreža</small>
                                                    </div>
                                                </div>
                                                <p class="card-text small">
                                                    Facebook Pixel pomaže da merimo efektivnost naših reklama i 
                                                    da prikažemo relevantne oglase na Facebooku i Instagramu.
                                                </p>
                                                <a href="https://www.facebook.com/policies/cookies/" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-external-link-alt me-1"></i> Saznaj više
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Imajte na umu da mi ne kontrolišemo kolačiće trećih strana i ne možemo da 
                                    pristupimo podacima koje oni prikupljaju. Za više informacija posetite 
                                    politike privatnosti tih kompanija.
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 5 -->
                        <div id="section-5" class="cookies-section mb-5">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">5</span>
                                Kako da kontrolišete kolačiće?
                            </h2>
                            <div class="ms-5">
                                <p>
                                    Imate potpunu kontrolu nad kolačićima. Možete da:
                                </p>
                                
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="card border-primary h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-check-circle text-primary me-2"></i>
                                                    Prihvatite ili odbijete
                                                </h5>
                                                <p class="card-text">
                                                    Prilikom prve posete, možete da prihvatite sve kolačiće, 
                                                    odbijete sve osim neophodnih, ili da podesite svoje preference.
                                                </p>
                                                <button class="btn btn-primary btn-sm" id="openSettingsFromContent">
                                                    <i class="fas fa-sliders-h me-2"></i> Podešavanje kolačića
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card border-success h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <i class="fas fa-cog text-success me-2"></i>
                                                    Podesite browser
                                                </h5>
                                                <p class="card-text">
                                                    Većina web browsera vam dozvoljava da kontrolišete kolačiće 
                                                    kroz svoje postavke. Obično možete da:
                                                </p>
                                                <ul class="small mb-0">
                                                    <li>Vidite koje kolačiće imate</li>
                                                    <li>Obrišete pojedinačne ili sve kolačiće</li>
                                                    <li>Blokirate kolačiće od određenih sajtova</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h5 class="h6 mb-3">Uputstva za popularne browsere:</h5>
                                    <div class="row">
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 border rounded">
                                                <i class="fab fa-chrome fa-2x mb-2" style="color: #4285F4;"></i>
                                                <h6>Google Chrome</h6>
                                                <small>
                                                    <a href="https://support.google.com/chrome/answer/95647" 
                                                       target="_blank" class="text-decoration-none">
                                                        Uputstva →
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 border rounded">
                                                <i class="fab fa-firefox fa-2x mb-2" style="color: #FF7139;"></i>
                                                <h6>Mozilla Firefox</h6>
                                                <small>
                                                    <a href="https://support.mozilla.org/kb/cookies-information-websites-store-on-your-computer" 
                                                       target="_blank" class="text-decoration-none">
                                                        Uputstva →
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 border rounded">
                                                <i class="fab fa-safari fa-2x mb-2" style="color: #000000;"></i>
                                                <h6>Apple Safari</h6>
                                                <small>
                                                    <a href="https://support.apple.com/guide/safari/manage-cookies-and-website-data-sfri11471/mac" 
                                                       target="_blank" class="text-decoration-none">
                                                        Uputstva →
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 text-center mb-3">
                                            <div class="p-3 border rounded">
                                                <i class="fab fa-edge fa-2x mb-2" style="color: #0078D7;"></i>
                                                <h6>Microsoft Edge</h6>
                                                <small>
                                                    <a href="https://support.microsoft.com/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" 
                                                       target="_blank" class="text-decoration-none">
                                                        Uputstva →
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-mobile-alt me-2"></i>
                                    <strong>Na mobilnim uređajima:</strong> Postupak je sličan. Idite u postavke browsera 
                                    na vašem telefonu ili tabletu i potražite opciju "Kolačići" ili "Privatnost".
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEKCIJA 6 -->
                        <div id="section-6" class="cookies-section">
                            <h2 class="h3 mb-4">
                                <span class="badge bg-primary me-3">6</span>
                                Često postavljana pitanja (FAQ)
                            </h2>
                            <div class="ms-5">
                                <div class="accordion" id="cookiesFAQ">
                                    <!-- PITANJE 1 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq1">
                                                Da li moram da prihvatim kolačiće?
                                            </button>
                                        </h2>
                                        <div id="faq1" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                <strong>Neophodne kolačiće ne možete da odbijete</strong> jer su potrebni 
                                                za osnovno funkcionisanje sajta (prijava, bezbednost). Međutim, možete 
                                                da odbijete sve ostale kategorije kolačića (funkcionalne, analitičke, 
                                                marketinške). Imajte na umu da odbijanje određenih kolačića može 
                                                ograničiti funkcionalnost sajta.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- PITANJE 2 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq2">
                                                Kako da obrišem postojeće kolačiće?
                                            </button>
                                        </h2>
                                        <div id="faq2" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                Možete obrisati kolačiće kroz postavke vašeg web browsera. 
                                                Proces varira od browsera do browsera, ali obično možete da 
                                                pronađete opciju "Obriši istoriju" ili "Obriši podatke pretraživanja" 
                                                gde možete da izaberete da obrišete kolačiće. Takođe možete da 
                                                obrišete kolačiće za određene sajtove ili za sve sajtove.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- PITANJE 3 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq3">
                                                Da li kolačići ugrožavaju moju privatnost?
                                            </button>
                                        </h2>
                                        <div id="faq3" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                Sami kolačići nisu opasni po privatnost jer su samo tekstualne 
                                                datoteke. Međutim, mogu se koristiti za praćenje vašeg ponašanja 
                                                na internetu. Mi koristimo kolačiće transparentno i u skladu sa 
                                                našom Politikom privatnosti. Ne koristimo kolačiće za prikupljanje 
                                                osetljivih ličnih podataka bez vaše saglasnosti.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- PITANJE 4 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq4">
                                                Koliko dugo kolačići ostaju na mom uređaju?
                                            </button>
                                        </h2>
                                        <div id="faq4" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                Trajanje kolačića varira. Neki su "sesijski" kolačići koji se brišu 
                                                kada zatvorite browser. Drugi su "trajni" kolačići koji ostaju na 
                                                vašem uređaju određeno vreme (npr. 24 sata, 7 dana, 1 godina, 2 godine). 
                                                Svaki kolačić ima svoj rok trajanja koji je naveden u tabeli iznad.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- PITANJE 5 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq5">
                                                Da li mogu da koristim Rasprodaja.rs bez kolačića?
                                            </button>
                                        </h2>
                                        <div id="faq5" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                Možete da pregledate javne oglase bez kolačića, ali <strong>nećete moći 
                                                da se prijavite, postavljate oglase ili koristite većinu funkcionalnosti</strong>. 
                                                Neophodni kolačići su potrebni za bezbednost i autentifikaciju. 
                                                Ako blokirate sve kolačiće, mnoge funkcionalnosti neće raditi ispravno.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- PITANJE 6 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#faq6">
                                                Da li menjate ovu Politiku kolačića?
                                            </button>
                                        </h2>
                                        <div id="faq6" class="accordion-collapse collapse" 
                                             data-bs-parent="#cookiesFAQ">
                                            <div class="accordion-body">
                                                Da, možemo da ažuriramo ovu Politiku kolačića da odražavamo promene 
                                                u našoj praksi ili izmenama zakona. O svim bitnim promenama bićete 
                                                obavešteni putem emaila ili obaveštenja na platformi. Datum poslednje 
                                                izmene uvek je naveden na vrhu ove stranice.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <p>
                                        Ako imate dodatna pitanja o našoj Politici kolačića, kontaktirajte nas na:
                                    </p>
                                    <ul>
                                        <li><strong>Email:</strong> info@rasprodaja.rs</li>
                                        <!-- <li><strong>Telefon:</strong> +381 11 123 4567</li>-->
                                        <li><strong>Povezano:</strong> 
                                            <a href="/privacy" class="text-decoration-none">Politika privatnosti</a> | 
                                            <a href="/terms" class="text-decoration-none">Uslovi korišćenja</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FOOTER SEKCIJA -->
                        <div class="mt-5 pt-4 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">
                                        <i class="far fa-copyright me-1"></i>
                                        <?php echo date('Y'); ?> Rasprodaja.rs - Sva prava zadržana.
                                    </small>
                                </div>
                                <div>
                                    <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-print me-2"></i> Štampaj
                                    </button>
                                    <button class="btn btn-primary btn-sm ms-2" id="finalCookiesSettings">
                                        <i class="fas fa-sliders-h me-2"></i> Upravljaj kolačićima
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .cookies-hero {
        background: linear-gradient(135deg, #ff6b6b 0%, #ffd93d 100%);
        border-radius: 0 0 20px 20px;
        margin-top: -1rem;
    }
    
    .cookies-hero-icon i {
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));
        animation: cookieBounce 3s infinite;
    }
    
    @keyframes cookieBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .cookies-nav .nav-link {
        color: var(--bs-body-color);
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
    }
    
    .cookies-nav .nav-link:hover,
    .cookies-nav .nav-link.active {
        color: var(--bs-primary);
        border-left-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    
    .cookies-section {
        scroll-margin-top: 100px;
    }
    
    .cookies-section h2 {
        border-bottom: 2px solid var(--bs-primary);
        padding-bottom: 10px;
    }
    
    .cookies-illustration {
        position: relative;
        width: 200px;
        height: 150px;
        margin: 0 auto;
    }
    
    .cookies-purpose-icon {
        width: 80px;
        height: 80px;
        line-height: 80px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-radius: 50%;
        margin: 0 auto;
    }
    
    .cookies-category {
        padding: 1rem;
        border-radius: 8px;
        background-color: rgba(var(--bs-light-rgb), 0.5);
        border-left: 4px solid var(--bs-primary);
    }
    
    #cookiesConsentBanner {
        z-index: 9999;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    
    @media print {
        .cookies-hero,
        .sticky-top,
        .cookies-nav,
        .alert,
        button,
        .btn,
        #cookiesConsentBanner,
        .modal {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        a {
            color: #000 !important;
            text-decoration: none !important;
        }
    }
    
    @media (max-width: 992px) {
        .sticky-top {
            position: static !important;
        }
        
        #cookiesConsentBanner .row {
            flex-direction: column;
            text-align: center;
        }
        
        #cookiesConsentBanner .text-lg-end {
            text-align: center !important;
        }
    }
</style>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const banner = document.getElementById('cookiesConsentBanner');
        const modalElement = document.getElementById('cookiesSettingsModal');
        const modal = new bootstrap.Modal(modalElement);
        
        // Proveri da li je već prihvatio kolačiće
        const cookiesAccepted = localStorage.getItem('cookiesAccepted');
        const cookiesSettings = JSON.parse(localStorage.getItem('cookiesSettings') || '{}');
        
        // Ako nije prihvatio, pokaži banner
        if (!cookiesAccepted) {
            setTimeout(() => {
                banner.classList.remove('d-none');
            }, 1000);
        }
        
        // Postavi prethodne postavke ako postoje
        if (cookiesSettings.functional !== undefined) {
            document.getElementById('functionalCookies').checked = cookiesSettings.functional;
        }
        if (cookiesSettings.analytics !== undefined) {
            document.getElementById('analyticsCookies').checked = cookiesSettings.analytics;
        }
        if (cookiesSettings.marketing !== undefined) {
            document.getElementById('marketingCookies').checked = cookiesSettings.marketing;
        }
        
        // Event listeners za banner dugmad
        document.getElementById('acceptAllCookies').addEventListener('click', function() {
            acceptAllCookies();
        });
        
        document.getElementById('rejectCookies').addEventListener('click', function() {
            rejectNonEssentialCookies();
        });
        
        document.getElementById('manageCookies').addEventListener('click', function() {
            modal.show();
        });
        
        // Event listeners za otvaranje modala
        document.getElementById('openCookiesSettings').addEventListener('click', function() {
            modal.show();
        });
        
        document.getElementById('openSettingsFromContent').addEventListener('click', function() {
            modal.show();
        });
        
        document.getElementById('finalCookiesSettings').addEventListener('click', function() {
            modal.show();
        });
        
        // ISPRAVLJENO: Event listener za save dugme
        document.getElementById('saveCookiesSettings').addEventListener('click', function() {
            // Prvo sačuvaj postavke
            saveCookieSettings();
            
            // Zatvori modal bez fokusa na dugme
            modal.hide();
            
            // Vrati fokus na bezbedno mesto
            setTimeout(() => {
                document.querySelector('body').focus();
            }, 150);
        });
        
        // Dodaj event listener za ESC i klik izvan modala
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Kada se modal zatvori, ukloni fokus
            document.activeElement.blur();
        });
        
        // Smooth scroll za navigaciju
        const navLinks = document.querySelectorAll('.cookies-nav .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                    
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
        
        // Update active link on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('.cookies-section');
            const scrollPos = window.scrollY + 150;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                const sectionId = section.getAttribute('id');
                
                if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + sectionId) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        });
        
        // Funkcije za upravljanje kolačićima
        function acceptAllCookies() {
            const settings = {
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true,
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem('cookiesAccepted', 'true');
            localStorage.setItem('cookiesSettings', JSON.stringify(settings));
            
            banner.classList.add('d-none');
            showSuccessMessage('Hvala! Prihvatili ste sve kolačiće.');
            
            // Postavi stvarne kolačiće (simulacija)
            setCookie('functional_cookies', 'accepted', 365);
            setCookie('analytics_cookies', 'accepted', 365);
            setCookie('marketing_cookies', 'accepted', 365);
        }
        
        function rejectNonEssentialCookies() {
            const settings = {
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false,
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem('cookiesAccepted', 'true');
            localStorage.setItem('cookiesSettings', JSON.stringify(settings));
            
            banner.classList.add('d-none');
            showSuccessMessage('Prihvatili ste samo neophodne kolačiće.');
            
            // Obriši neophodne kolačiće (simulacija)
            deleteCookie('functional_cookies');
            deleteCookie('analytics_cookies');
            deleteCookie('marketing_cookies');
        }
        
        function saveCookieSettings() {
            const settings = {
                necessary: true,
                functional: document.getElementById('functionalCookies').checked,
                analytics: document.getElementById('analyticsCookies').checked,
                marketing: document.getElementById('marketingCookies').checked,
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem('cookiesAccepted', 'true');
            localStorage.setItem('cookiesSettings', JSON.stringify(settings));
            
            banner.classList.add('d-none');
            showSuccessMessage('Podešavanja kolačića su sačuvana.');
            
            // Postavi/obriši kolačiće prema postavkama
            if (settings.functional) {
                setCookie('functional_cookies', 'accepted', 365);
            } else {
                deleteCookie('functional_cookies');
            }
            
            if (settings.analytics) {
                setCookie('analytics_cookies', 'accepted', 365);
            } else {
                deleteCookie('analytics_cookies');
            }
            
            if (settings.marketing) {
                setCookie('marketing_cookies', 'accepted', 365);
            } else {
                deleteCookie('marketing_cookies');
            }
        }
        
        function setCookie(name, value, days) {
            const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Lax`;
        }
        
        function deleteCookie(name) {
            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        }
        
        function showSuccessMessage(message) {
            // Kreiraj toast notifikaciju
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '10000';
            toast.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header bg-success text-white">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong class="me-auto">Uspešno</strong>
                        <button type="button" class="btn-close btn-close-white" 
                                onclick="this.closest('.toast').remove()"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Automatski ukloni nakon 5 sekundi
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }
    });
</script>