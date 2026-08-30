<?php
/**
 * pages/info/how-it-works.php - Kako radi Rasprodaja.rs
 */

$pageTitle = 'Kako radi Rasprodaja.rs - Vodič za korisnike';
$pageDescription = 'Saznajte kako da koristite Rasprodaja.rs platformu. Detaljan vodič za postavljanje oglasa, pretragu, komunikaciju i sigurne transakcije.';
$pageSpecificCSS = ['how-it-works.css'];
$showBreadcrumbs = true;


?>

<!-- HERO SECTION -->
<section class="how-it-works-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold text-white mb-3">
                    Kako radi Rasprodaja.rs?
                </h1>
                <p class="lead text-white mb-4">
                    Najjednostavniji način da kupite ili prodate bilo šta u Srbiji. <br>
                    Učite kroz 4 jednostavna koraka.
                </p>
                
                <!-- BRZI LINKOVI -->
                <div class="d-flex flex-wrap gap-3">
                    <a href="#korak1" class="btn btn-light btn-sm rounded-pill">
                        <i class="fas fa-user-plus me-2"></i>1. Registracija
                    </a>
                    <a href="#korak2" class="btn btn-outline-light btn-sm rounded-pill">
                        <i class="fas fa-bullhorn me-2"></i>2. Postavi oglas
                    </a>
                    <a href="#korak3" class="btn btn-outline-light btn-sm rounded-pill">
                        <i class="fas fa-comments me-2"></i>3. Komuniciraj
                    </a>
                    <a href="#korak4" class="btn btn-outline-light btn-sm rounded-pill">
                        <i class="fas fa-handshake me-2"></i>4. Prodaj/Kupi
                    </a>
                </div>
            </div>
            
            <div class="col-lg-4 text-center">
                <div class="how-it-works-hero-icon">
                    <i class="fas fa-cogs fa-6x text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- GLAVNI KORACI -->
<div class="container py-4">
    <!-- 4 GLAVNA KORAKA -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <h2 class="h3 mb-4 text-center">
                <i class="fas fa-road me-2 text-primary"></i> 
                4 jednostavna koraka do uspešne transakcije
            </h2>
            
            <div class="row g-4">
                <!-- KORAK 1 -->
                <div class="col-md-6 col-lg-3" id="korak1">
                    <div class="card how-it-works-step-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="step-number mb-3">
                                <span class="badge bg-primary rounded-circle p-3">1</span>
                            </div>
                            <h5 class="card-title mb-3">
                                <i class="fas fa-user-plus me-2"></i>Registruj se
                            </h5>
                            <p class="card-text small mb-4">
                                Napravite nalog za 30 sekundi. Potrebni su vam email, lozinka i broj telefona.
                            </p>
                            <div class="step-details">
                                <ul class="list-unstyled text-start small">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verifikuj email</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Popuni profil</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Prikaži se pouzdanim</li>
                                </ul>
                            </div>
                            <?php if (!isLoggedIn()): ?>
                                <a href="/register" class="btn btn-primary btn-sm mt-3">
                                    Registruj se besplatno
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- KORAK 2 -->
                <div class="col-md-6 col-lg-3" id="korak2">
                    <div class="card how-it-works-step-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="step-number mb-3">
                                <span class="badge bg-success rounded-circle p-3">2</span>
                            </div>
                            <h5 class="card-title mb-3">
                                <i class="fas fa-bullhorn me-2"></i>Postavi oglas
                            </h5>
                            <p class="card-text small mb-4">
                                Dodaj fotografije, opišite proizvod, odaberite kategoriju i postavite cenu.
                            </p>
                            <div class="step-details">
                                <ul class="list-unstyled text-start small">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Kvalitetne fotografije</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Detaljan opis</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Realna cena</li>
                                </ul>
                            </div>
                            <?php if (isLoggedIn()): ?>
                                <a href="/create-ad" class="btn btn-success btn-sm mt-3">
                                    Postavi oglas
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- KORAK 3 -->
                <div class="col-md-6 col-lg-3" id="korak3">
                    <div class="card how-it-works-step-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="step-number mb-3">
                                <span class="badge bg-warning rounded-circle p-3">3</span>
                            </div>
                            <h5 class="card-title mb-3">
                                <i class="fas fa-comments me-2"></i>Komuniciraj
                            </h5>
                            <p class="card-text small mb-4">
                                Odgovorite na poruke kupaca, dogovorite detalje i sastanak.
                            </p>
                            <div class="step-details">
                                <ul class="list-unstyled text-start small">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Odgovori brzo</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Budite jasni</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Dogovorite sastanak</li>
                                </ul>
                            </div>
                            <?php if (isLoggedIn()): ?>
                                <a href="/messages" class="btn btn-warning btn-sm mt-3">
                                    Proveri poruke
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- KORAK 4 -->
                <div class="col-md-6 col-lg-3" id="korak4">
                    <div class="card how-it-works-step-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="step-number mb-3">
                                <span class="badge rounded-circle p-3 step-badge step-badge-4">4</span>
                            </div>
                            <h5 class="card-title mb-3">
                                <i class="fas fa-handshake me-2"></i>Prodaj/Kupi
                            </h5>
                            <p class="card-text small mb-4">
                                Sastanite se na sigurnom mestu, pregledajte robu i obavite transakciju.
                            </p>
                            <div class="step-details">
                                <ul class="list-unstyled text-start small">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lični sastanak</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Pregledaj robu</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Bezbedna transakcija</li>
                                </ul>
                            </div>
                            <a href="/safety" class="btn btn-danger btn-sm mt-3">
                                Bezbednost prvo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- DETALJNI VODIČ -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h4 mb-0">
                        <i class="fas fa-book me-2"></i> 
                        Detaljan vodič za početnike
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- LEVA KOLONA -->
                        <div class="col-lg-6">
                            <div class="guide-section mb-5">
                                <h4 class="h5 mb-3">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    Za prodavce
                                </h4>
                                
                                <div class="accordion" id="sellerGuide">
                                    <!-- Kako postaviti oglas -->
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#sellerGuide1" aria-expanded="true">
                                                <i class="fas fa-plus-circle me-2 text-success"></i>
                                                Kako postaviti oglas?
                                            </button>
                                        </h3>
                                        <div id="sellerGuide1" class="accordion-collapse collapse show" 
                                             data-bs-parent="#sellerGuide">
                                            <div class="accordion-body">
                                                <ol class="mb-0">
                                                    <li class="mb-2"><strong>Kliknite "Postavi oglas"</strong> u glavnom meniju</li>
                                                    <li class="mb-2"><strong>Odaberite kategoriju</strong> koja najbolje odgovara vašem proizvodu</li>
                                                    <li class="mb-2"><strong>Dodajte fotografije</strong> (do 10 za Free paket, 20 za Gold Paket)</li>
                                                    <li class="mb-2"><strong>Napišite detaljan opis</strong> sa svim specifikacijama</li>
                                                    <li class="mb-2"><strong>Postavite realnu cenu</strong> i odaberite da li je pogodba</li>
                                                    <li><strong>Objavite oglas</strong> koji je odmah vidljiv</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Kako poboljšati oglas -->
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#sellerGuide2">
                                                <i class="fas fa-chart-line me-2 text-warning"></i>
                                                Kako poboljšati vidljivost oglasa?
                                            </button>
                                        </h3>
                                        <div id="sellerGuide2" class="accordion-collapse collapse" 
                                             data-bs-parent="#sellerGuide">
                                            <div class="accordion-body">
                                                <ul class="mb-0">
                                                    <li class="mb-2"><strong>Premium paketi</strong> - istaknuto mesto na početnoj</li>
                                                    <li class="mb-2"><strong>Obnavljajte oglas</strong> svakih 7 dana za bolju poziciju</li>
                                                    <li class="mb-2"><strong>Kvalitetne fotografije</strong> - 5x više pregleda</li>
                                                    <li class="mb-2"><strong>Detaljan opis</strong> sa ključnim rečima</li>
                                                    <li><strong>Realna cena</strong> - po tržišnoj vrednosti</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DESNA KOLONA -->
                        <div class="col-lg-6">
                            <div class="guide-section mb-5">
                                <h4 class="h5 mb-3">
                                    <i class="fas fa-shopping-cart text-success me-2"></i>
                                    Za kupce
                                </h4>
                                
                                <div class="accordion" id="buyerGuide">
                                    <!-- Kako pronaći proizvod -->
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                                    data-bs-target="#buyerGuide1" aria-expanded="true">
                                                <i class="fas fa-search me-2 text-primary"></i>
                                                Kako pronaći proizvod?
                                            </button>
                                        </h3>
                                        <div id="buyerGuide1" class="accordion-collapse collapse show" 
                                             data-bs-parent="#buyerGuide">
                                            <div class="accordion-body">
                                                <ul class="mb-0">
                                                    <li class="mb-2"><strong>Pretraga</strong> - koristite search polje na vrhu stranice</li>
                                                    <li class="mb-2"><strong>Filteri</strong> - po ceni, lokaciji, kategoriji, stanju</li>
                                                    <li class="mb-2"><strong>Kategorije</strong> - pregledajte po oblastima</li>
                                                    <li class="mb-2"><strong>Mapa</strong> - pronađite oglase u vašem kraju</li>
                                                    <li><strong>Sačuvane pretrage</strong> - dobijte obaveštenja za nova pojavljivanja</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Kako kontaktirati prodavca -->
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#buyerGuide2">
                                                <i class="fas fa-envelope me-2 text-info"></i>
                                                Kako kontaktirati prodavca?
                                            </button>
                                        </h3>
                                        <div id="buyerGuide2" class="accordion-collapse collapse" 
                                             data-bs-parent="#buyerGuide">
                                            <div class="accordion-body">
                                                <ol class="mb-0">
                                                    <li class="mb-2"><strong>Prijavite se</strong> na svoj nalog (ako već niste)</li>
                                                    <li class="mb-2"><strong>Kliknite "Kontaktiraj prodavca"</strong> na stranici oglasa</li>
                                                    <li class="mb-2"><strong>Pošaljite poruku</strong> sa vašim pitanjima</li>
                                                    <li class="mb-2"><strong>Čekajte odgovor</strong> - većina prodavaca odgovara u roku od 24h</li>
                                                    <li><strong>Dogovorite sastanak</strong> na sigurnom mestu</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PREMIUM PREDNOSTI -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h3 class="h4 mb-0 text-dark">
                        <i class="fas fa-crown me-2"></i> 
                        Zašto postati premium korisnik?
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <div class="premium-icon mb-3">
                                    <i class="fas fa-eye fa-3x text-primary"></i>
                                </div>
                                <h5>10x više pregleda</h5>
                                <p class="small text-muted">Premium oglasi se prikazuju na početnoj stranici</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <div class="premium-icon mb-3">
                                    <i class="fas fa-camera fa-3x text-success"></i>
                                </div>
                                <h5>Više fotografija</h5>
                                <p class="small text-muted">Do 20 fotografija umesto 10</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="text-center">
                                <div class="premium-icon mb-3">
                                    <i class="fas fa-bolt fa-3x text-danger"></i>
                                </div>
                                <h5>Istaknuto mesto</h5>
                                <p class="small text-muted">Uvek na vrhu rezultata pretrage</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="/packages" class="btn btn-warning btn-lg">
                            <i class="fas fa-crown me-2"></i> 
                            Pogledaj premium pakete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="h4 mb-0">
                        <i class="fas fa-question-circle me-2"></i> 
                        Česta pitanja
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="faq-item mb-4">
                                <h5 class="mb-2">
                                    <i class="fas fa-question text-primary me-2"></i>
                                    Da li je korišćenje besplatno?
                                </h5>
                                <p class="text-muted small mb-0">
                                    <strong>Da!</strong> Osnovno korišćenje je potpuno besplatno. Možete se registrovati, postavljati oglase, pretraživati i kontaktirati druge korisnike bez ikakvih troškova.
                                </p>
                            </div>
                            
                            <div class="faq-item mb-4">
                                <h5 class="mb-2">
                                    <i class="fas fa-question text-primary me-2"></i>
                                    Koliko dugo važi oglas?
                                </h5>
                                <p class="text-muted small mb-0">
                                    Besplatni oglasi važe <strong>30 dana</strong>. 7 dana pre isteka dobijate obaveštenje na email da ga produžite. Premium oglasi važe <strong>60 dana</strong>.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="faq-item mb-4">
                                <h5 class="mb-2">
                                    <i class="fas fa-question text-primary me-2"></i>
                                    Kako da oglas bude uspešan?
                                </h5>
                                <p class="text-muted small mb-0">
                                    1. Kvalitetne fotografije<br>
                                    2. Detaljan opis<br>
                                    3. Realna cena<br>
                                    4. Brzi odgovori na poruke<br>
                                    5. Obnavljanje oglasa
                                </p>
                            </div>
                            
                            <div class="faq-item mb-4">
                                <h5 class="mb-2">
                                    <i class="fas fa-question text-primary me-2"></i>
                                    Kako da prepoznam pouzdanog prodavca?
                                </h5>
                                <p class="text-muted small mb-0">
                                    Proverite: starost naloga, ocene, broj oglasa, kvalitet opisa i fotografija, brzinu odgovora na poruke.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CALL TO ACTION -->
    <div class="text-center mt-5">
        <h3 class="h4 mb-3">Spremni da počnete?</h3>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <?php if (!isLoggedIn()): ?>
                <a href="/register" class="btn btn-primary btn-lg">
                    <i class="fas fa-user-plus me-2"></i> Registruj se besplatno
                </a>
            <?php endif; ?>
            
            <a href="/create-ad" class="btn btn-success btn-lg">
                <i class="fas fa-bullhorn me-2"></i> Postavi oglas
            </a>
            
            <a href="/ads" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-search me-2"></i> Pretraži oglase
            </a>
        </div>
    </div>
</div>