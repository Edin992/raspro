<?php
/**
 * pages/info/safety.php - Saveti o bezbednosti pri kupovini/prodaji
 */

$pageTitle = 'Saveti o bezbednosti - Rasprodaja.rs';
$pageDescription = 'Vodič za sigurnu kupovinu i prodaju na Rasprodaja.rs. Saveti kako da se zaštitite od prevara i obavite bezbedne transakcije.';
$pageSpecificCSS = ['safety.css'];



// Breadcrumbs

?>

<!-- HERO SECTION -->
<section class="safety-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold text-white mb-3">
                    Saveti o bezbednosti
                </h1>
                <p class="lead text-white mb-4">
                    Vaša bezbednost nam je prioritet. Saznajte kako da se zaštitite od prevara i obavite sigurne transakcije na Rasprodaja.rs.
                </p>
                
                <!-- STATISTIKE -->
                <div class="d-flex flex-wrap gap-3 text-white">
                    <div class="safety-stat">
                        <i class="fas fa-shield-alt me-2"></i>
                        99.7% bezbednih transakcija
                    </div>
                    
                    <div class="safety-stat">
                        <i class="fas fa-headset me-2"></i>
                        24/7 podrška za prijave prevara
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 text-center">
                <div class="safety-hero-icon">
                    <i class="fas fa-shield-alt fa-6x text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SAVETI -->
<div class="container py-4">
    <!-- NAJVAŽNIJI SAVETI -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <h2 class="h3 mb-4">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i> 
                Najvažniji saveti
            </h2>
            
            <div class="row">
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card safety-tip-card h-100 border-warning">
                        <div class="card-body text-center p-4">
                            <div class="safety-tip-icon mb-3">
                                <i class="fas fa-handshake fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Lično preuzimanje</h5>
                            <p class="card-text small">Uvek se sastanite lično da pregledate robu pre kupovine.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card safety-tip-card h-100 border-danger">
                        <div class="card-body text-center p-4">
                            <div class="safety-tip-icon mb-3">
                                <i class="fas fa-money-bill-wave fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">Plaćanje pouzećem</h5>
                            <p class="card-text small">Platite pouzećem i otvorite paket pre nego što platite.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card safety-tip-card h-100 border-primary">
                        <div class="card-body text-center p-4">
                            <div class="safety-tip-icon mb-3">
                                <i class="fas fa-user-check fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Provera prodavca</h5>
                            <p class="card-text small">Proverite ocene i starost naloga prodavca.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card safety-tip-card h-100 border-success">
                        <div class="card-body text-center p-4">
                            <div class="safety-tip-icon mb-3">
                                <i class="fas fa-map-marker-alt fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title">Bezbedno mesto</h5>
                            <p class="card-text small">Sastanite se na javnom mestu tokom dana.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- DETALJNI VODIČ -->
    <div class="row">
        <!-- LEVA KOLONA - KUPOVINA -->
        <div class="col-lg-6 mb-5">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h3 class="h4 mb-0">
                        <i class="fas fa-shopping-cart me-2"></i> 
                        Saveti za kupce
                    </h3>
                </div>
                <div class="card-body">
                    <div class="accordion" id="buyerAccordion">
                        <!-- Savet 1 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#buyer1" aria-expanded="true">
                                    <i class="fas fa-search me-2 text-primary"></i>
                                    Provera prodavca
                                </button>
                            </h3>
                            <div id="buyer1" class="accordion-collapse collapse show" 
                                 data-bs-parent="#buyerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li>Proverite <strong>starost naloga</strong> - stariji nalog = pouzdaniji</li>
                                        <li>Pogledajte <strong>ocene i komentare</strong> drugih kupaca</li>
                                        <li>Proverite broj <strong>aktivnih i prodatih oglasa</strong></li>
                                        <li>Kontaktirajte prodavca putem našeg <strong>sistema poruka</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 2 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#buyer2">
                                    <i class="fas fa-money-check-alt me-2 text-success"></i>
                                    Načini plaćanja
                                </button>
                            </h3>
                            <div id="buyer2" class="accordion-collapse collapse" 
                                 data-bs-parent="#buyerAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-success">
                                        <strong>Preporučeno:</strong> Lično preuzimanje i plaćanje
                                    </div>
                                    <ul class="mb-0">
                                        <li><strong>Pouzećem</strong> - najsigurnije za pošiljke</li>
                                        <li><strong>Lično plaćanje</strong> - posle pregleda robe</li>
                                        <li><strong>Preko banke</strong> - samo za verifikovane prodavce</li>
                                        <li class="text-danger"><strong>NE šaljite novac unapred!</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 3 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#buyer3">
                                    <i class="fas fa-box-open me-2 text-warning"></i>
                                    Pregled robe
                                </button>
                            </h3>
                            <div id="buyer3" class="accordion-collapse collapse" 
                                 data-bs-parent="#buyerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li>Uvek <strong>pregledajte robu lično</strong> pre kupovine</li>
                                        <li>Proverite <strong>funkcionalnost</strong> uredaja</li>
                                        <li>Uporedite sa <strong>fotografijama iz oglasa</strong></li>
                                        <li>Tražite <strong>garantni list ili račun</strong> ako postoji</li>
                                        <li>Testirajte sve funkcije pre nego što platite</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 4 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#buyer4">
                                    <i class="fas fa-map-marked-alt me-2 text-info"></i>
                                    Bezbedni sastanci
                                </button>
                            </h3>
                            <div id="buyer4" class="accordion-collapse collapse" 
                                 data-bs-parent="#buyerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li>Sastanite se <strong>na javnom mestu</strong></li>
                                        <li><strong>Tržni centri, kafići, banke</strong> su dobar izbor</li>
                                        <li><strong>Izbegavajte mračna i izolovana mesta</strong></li>
                                        <li>Obavestite prijatelja/porodicu gde idete</li>
                                        <li>Planirajte sastanak <strong>tokom dana</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- DESNA KOLONA - PRODAJA -->
        <div class="col-lg-6 mb-5">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h3 class="h4 mb-0">
                        <i class="fas fa-store me-2"></i> 
                        Saveti za prodavce
                    </h3>
                </div>
                <div class="card-body">
                    <div class="accordion" id="sellerAccordion">
                        <!-- Savet 1 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#seller1" aria-expanded="true">
                                    <i class="fas fa-camera me-2 text-primary"></i>
                                    Kvalitetne fotografije
                                </button>
                            </h3>
                            <div id="seller1" class="accordion-collapse collapse show" 
                                 data-bs-parent="#sellerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li>Koristite <strong>dobar danji svetlost</strong></li>
                                        <li>Fotografišite <strong>sa svih strana</strong></li>
                                        <li>Pokažite <strong>moguće nedostatke</strong> (iskreno)</li>
                                        <li>Dodajte fotografiju <strong>garantnog lista/računa</strong></li>
                                        <li>Koristite <strong>visoku rezoluciju</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 2 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#seller2">
                                    <i class="fas fa-file-alt me-2 text-info"></i>
                                    Detaljan opis
                                </button>
                            </h3>
                            <div id="seller2" class="accordion-collapse collapse" 
                                 data-bs-parent="#sellerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li><strong>Iskreno opišite</strong> stanje proizvoda</li>
                                        <li>Navedite <strong>sve nedostatke i greške</strong></li>
                                        <li>Dodajte <strong>tehničke specifikacije</strong></li>
                                        <li>Navedite <strong>razlog prodaje</strong></li>
                                        <li>Obavezno navedite <strong>garanciju ako postoji</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 3 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#seller3">
                                    <i class="fas fa-user-shield me-2 text-warning"></i>
                                    Zaštita od prevara
                                </button>
                            </h3>
                            <div id="seller3" class="accordion-collapse collapse" 
                                 data-bs-parent="#sellerAccordion">
                                <div class="accordion-body">
                                    <div class="alert alert-warning">
                                        <strong>Upozorenje:</strong> Česte prevare za prodavce
                                    </div>
                                    <ul class="mb-0">
                                        <li><strong>Lažni čekovi</strong> - prihvatite samo gotovinu</li>
                                        <li><strong>Preplata</strong> - ne vraćajte višak novca</li>
                                        <li><strong>Falsifikovani računi</strong> - proverite uplatnice</li>
                                        <li><strong>Krađa</strong> - ne ostavljajte robu bez nadzora</li>
                                        <li><strong>Lažna dostava</strong> - potvrdite preuzimanje</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Savet 4 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#seller4">
                                    <i class="fas fa-hand-holding-usd me-2 text-success"></i>
                                    Sigurna naplata
                                </button>
                            </h3>
                            <div id="seller4" class="accordion-collapse collapse" 
                                 data-bs-parent="#sellerAccordion">
                                <div class="accordion-body">
                                    <ul class="mb-0">
                                        <li><strong>Gotovina</strong> - preporučeno za lične sastanke</li>
                                        <li><strong>Bankovni transfer</strong> - čekajte potvrdu banke</li>
                                        <li><strong>Pouzeće</strong> - sigurno za pošiljke</li>
                                        <li><strong>NE prihvatajte</strong> čekove, western union, crypto</li>
                                        <li>Proverite novac na licu mesta (pogotovo veće sume)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- UPUTSTVA ZA PRIJAVU -->
    <div class="row mb-5">
        <div class="col-lg-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="h4 mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i> 
                        Kako prijaviti prevaru?
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="report-step-icon mb-3">
                                    <span class="badge bg-danger rounded-circle p-3">1</span>
                                </div>
                                <h5>Prijavite na sajtu</h5>
                                <p class="small text-muted">Kliknite na "Prijavi oglas" ili "Prijavi korisnika"</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="report-step-icon mb-3">
                                    <span class="badge bg-danger rounded-circle p-3">2</span>
                                </div>
                                <h5>Dostavite dokaze</h5>
                                <p class="small text-muted">Screenshot-ovi, poruke, transakcije</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="report-step-icon mb-3">
                                    <span class="badge bg-danger rounded-circle p-3">3</span>
                                </div>
                                <h5>Kontaktirajte policiju</h5>
                                <p class="small text-muted">Za veće iznose, prijavite i policiji</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <div class="d-flex">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <h6 class="mb-1">Naša podrška vam stoji na raspolaganju</h6>
                                <p class="mb-0">Kontaktirajte nas na <strong>info@rasprodaja.rs</strong> za pomoć pri prijavama prevara. Odgovaramo u roku od 24h.</p>
                            </div>
                        </div>
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
                        Česta pitanja o bezbednosti
                    </h3>
                </div>
                <div class="card-body">
                    <div class="accordion" id="safetyFAQ">
                        <!-- FAQ 1 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#faq1" aria-expanded="true">
                                    Šta da radim ako mislim da sam prevaren?
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" 
                                 data-bs-parent="#safetyFAQ">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Odmah <strong>prijavite na našem sajtu</strong> koristeći dugme "Prijavi"</li>
                                        <li>Sačuvajte <strong>sve dokaze</strong> (screenshot-ovi, poruke, transakcije)</li>
                                        <li>Kontaktirajte <strong>našu podršku</strong> na info@rasprodaja.rs</li>
                                        <li>Za veće iznose, prijavite <strong>policiji</strong></li>
                                        <li>Blokirajte prevarenog korisnika u našem sistemu</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 2 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Da li Rasprodaja.rs garantuje transakcije?
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" 
                                 data-bs-parent="#safetyFAQ">
                                <div class="accordion-body">
                                    <p><strong>Ne, mi smo samo platforma za oglašavanje.</strong> Transakcije se obavljaju direktno između korisnika. Naša uloga je da vam pružimo alate za proveru i da reagujemo na prijavljene prevare.</p>
                                    <p>Zato je <strong>važno da pratite naše savete o bezbednosti</strong> i da budete oprezni prilikom transakcija.</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 3 -->
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Kako prepoznati sumnjivog prodavca/kupca?
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" 
                                 data-bs-parent="#safetyFAQ">
                                <div class="accordion-body">
                                    <div class="alert alert-warning">
                                        <strong>Crveni flag-ovi:</strong>
                                    </div>
                                    <ul>
                                        <li>Nov nalog (manje od 1 meseca)</li>
                                        <li>Nema ocena ili komentara</li>
                                        <li>Insistira na plaćanju unapred</li>
                                        <li>Žuri sa transakcijom</li>
                                        <li>Ne želi lični sastanak</li>
                                        <li>Nudi cenu ispod tržišne</li>
                                        <li>Koristi neobične načine plaćanja</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>