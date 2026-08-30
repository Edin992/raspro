<?php
/**
 * pages/faq.php - Često postavljana pitanja (FAQ)
 */

$pageTitle = 'Često postavljana pitanja (FAQ) - Rasprodaja.rs';
$pageDescription = 'Odgovori na najčešća pitanja o korišćenju Rasprodaja.rs platforme. Pomoć za registraciju, oglase, plaćanja i više.';
$pageSpecificCSS = ['faq.css'];
$pageSpecificJS = ['faq.js'];

$currentPage = 'faq';

// FAQ podaci - u praksi bi ovo bilo u bazi
$faqCategories = [
    [
        'id' => 'general',
        'title' => 'Opšta pitanja',
        'icon' => 'fas fa-question-circle',
        'color' => 'primary',
        'questions' => [
            [
                'q' => 'Šta je Rasprodaja.rs?',
                'a' => 'Rasprodaja.rs je najveća online platforma za oglašavanje u Srbiji koja povezuje kupce i prodavce. Omogućava jednostavno postavljanje oglasa, pretragu proizvoda i usluga, te sigurnu komunikaciju između korisnika.',
                'popular' => true
            ],
            [
                'q' => 'Da li je korišćenje platforme besplatno?',
                'a' => 'Da, osnovno korišćenje platforme je potpuno besplatno! Možete se registrovati, postavljati oglase, pretraživati i kontaktirati prodavce bez ikakvih troškova. Premium funkcije su opciono dostupne za korisnike koji žele dodatnu vidljivost svojih oglasa.',
                'popular' => true
            ],
            [
                'q' => 'Da li je platforma bezbedna?',
                'a' => 'Da, bezbednost naših korisnika nam je prioritet. Koristimo SSL šifrovanje, verifikaciju korisnika, sistem prijava za zloupotrebe i redovne sigurnosne provere. Međutim, savetujemo oprez prilikom transakcija i sastanaka.',
                'popular' => false
            ],
            [
                'q' => 'Da li imate mobilnu aplikaciju?',
                'a' => 'Trenutno radimo na razvoju mobilne aplikacije. Za sada možete koristiti našu potpuno responsive web platformu koja savršeno radi na svim mobilnim uređajima putem browser-a.',
                'popular' => false
            ],
            [
                'q' => 'Ko može da koristi Rasprodaja.rs?',
                'a' => 'Platformu mogu koristiti svi punoletni građani Srbije i regiona, kao i poslovni subjekti registrovani u Republici Srbiji. Za registraciju je potrebna validna email adresa i broj telefona.',
                'popular' => false
            ]
        ]
    ],
    [
        'id' => 'registration',
        'title' => 'Registracija i nalog',
        'icon' => 'fas fa-user-plus',
        'color' => 'success',
        'questions' => [
            [
                'q' => 'Kako da se registrujem?',
                'a' => 'Kliknite na dugme "Registruj se" u gornjem desnom uglu stranice. Popunite formu sa vašim podacima (ime, prezime, email, lozinka), prihvatite uslove korišćenja i kliknite na link za verifikaciju koji će vam stići na email.',
                'popular' => true
            ],
            [
                'q' => 'Zašto mi treba verifikacija email-a?',
                'a' => 'Email verifikacija je neophodna radi vaše bezbednosti i sprečavanja zloupotrebe. Potvrđuje da ste vi vlasnik email adrese i omogućava oporavak naloga u slučaju da zaboravite lozinku.',
                'popular' => false
            ],
            [
                'q' => 'Šta ako zaboravim lozinku?',
                'a' => 'Kliknite na "Zaboravili ste lozinku?" na stranici za prijavu. Unesite svoju email adresu i dobićete link za resetovanje lozinke. Link je validan 24 časa.',
                'popular' => true
            ],
            [
                'q' => 'Kako da promenim podatke na mom nalogu?',
                'a' => 'Nakon prijave, idite na "Moj profil" → "Podešavanja naloga". Tamo možete ažurirati svoje lične podatke, promeniti lozinku, email i druge postavke.',
                'popular' => false
            ],
            [
                'q' => 'Da li mogu da imam više naloga?',
                'a' => 'Ne, prema našim uslovima korišćenja, jedna osoba može imati samo jedan nalog. Više naloga od strane iste osobe će biti obrisano.',
                'popular' => false
            ]
        ]
    ],
    [
        'id' => 'ads',
        'title' => 'Oglasi',
        'icon' => 'fas fa-ad',
        'color' => 'warning',
        'questions' => [
            [
                'q' => 'Kako da postavim oglas?',
                'a' => 'Nakon prijave, kliknite na "Postavi oglas" u glavnom meniju. Popunite formu sa detaljima proizvoda/usluge, dodajte fotografije, odaberite kategoriju i cenu. Pred pregledom proverite sve informacije pre nego što objavite.',
                'popular' => true
            ],
            [
                'q' => 'Koliko fotografija mogu da dodam?',
                'a' => 'Za besplatne oglase možete dodati do 8 fotografija. Premium korisnici mogu dodati do 15 fotografija. Preporučujemo kvalitetne fotografije u svetlim uslovima koje prikazuju proizvod sa svih strana.',
                'popular' => false
            ],
            [
                'q' => 'Kako da uredim ili obrišem oglas?',
                'a' => 'Idite na "Moji oglasi" u vašem nalogu. Pored svakog oglasa imate opcije "Uredi" i "Obriši". Nakon uređivanja, oglas će ponovo proći moderaciju pre ponovnog objavljivanja.',
                'popular' => false
            ],
            [
                'q' => 'Koliko dugo važi moj oglas?',
                'a' => 'Besplatni oglasi važe 30 dana od objavljivanja. 7 dana pre isteka dobijate obaveštenje na email da produžite oglas. Premium oglasi važe 60 dana sa opcijom automatskog obnavljanja.',
                'popular' => true
            ],
            [
                'q' => 'Zašto je moj oglas obrisan/odbijen?',
                'a' => 'Oglasi se brišu ako krše naša pravila: lažne informacije, zabranjeni proizvodi, neprikladan sadržaj, duplikati ili ako nisu prošli moderaciju. Detaljan razlog dobijate na email.',
                'popular' => false
            ],
            [
                'q' => 'Šta su premium oglasi?',
                'a' => 'Premium oglasi su istaknuti oglasi koji se prikazuju na vidljivijim pozicijama, imaju posebno označenje i veću vidljivost. Dostupni su uz dodatnu naknadu.',
                'popular' => true
            ]
        ]
    ],
    [
        'id' => 'buying',
        'title' => 'Kupovina',
        'icon' => 'fas fa-shopping-cart',
        'color' => 'info',
        'questions' => [
            [
                'q' => 'Kako da kontaktiraм prodavca?',
                'a' => 'Na stranici oglasa kliknite na dugme "Kontaktiraj prodavca". Možete poslati direktnu poruku putem našeg sistema ili, ako je dostupan, videti kontakt telefon/email prodavca.',
                'popular' => true
            ],
            [
                'q' => 'Da li Rasprodaja.rs garantuje kupovinu?',
                'a' => 'Ne, mi smo samo platforma za oglašavanje. Ne garantujemo kvalitet, autentičnost ili isporuku proizvoda. Transakcije se obavljaju direktno između korisnika. Preporučujemo oprez i proveru prodavca pre kupovine.',
                'popular' => true
            ],
            [
                'q' => 'Kako da prepoznam pouzdanog prodavca?',
                'a' => 'Proverite: verifikaciju naloga, starost naloga, broj oglasa, ocene i komentare drugih kupaca, kvalitet opisa i fotografija. Preporučujemo sastanak na javnom mestu za pregled robe.',
                'popular' => false
            ],
            [
                'q' => 'Šta da uradim ako nisam zadovoljan kupovinom?',
                'a' => 'Prvo pokušajte da rešite problem direktno sa prodavcem. Ako to ne uspe, prijavite problem putem dugmeta "Prijavi oglas" na stranici oglasa. Naš tim će istražiti slučaj i preduzeti odgovarajuće mere.',
                'popular' => false
            ],
            [
                'q' => 'Da li mogu da ostavim ocenu prodavcu?',
                'a' => 'Da, nakon uspešne transakcije možete ostaviti ocenu i komentar prodavcu. Ovo pomaže drugim korisnicima da prepoznaju pouzdane prodavce.',
                'popular' => false
            ]
        ]
    ],
    [
        'id' => 'payments',
        'title' => 'Plaćanja i premium',
        'icon' => 'fas fa-credit-card',
        'color' => 'danger',
        'questions' => [
            [
                'q' => 'Koje načine plaćanja prihvatate?',
                'a' => 'Prihvatamo: kreditne/debitne kartice (Visa, Mastercard), PayPal, bankovne transfere i uplate na račun. Sve transakcije su šifrovane i bezbedne.',
                'popular' => true
            ],
            [
                'q' => 'Kako funkcionišu premium paketi?',
                'a' => 'Premium paketi omogućavaju istaknuto postavljanje oglasa, veći broj fotografija, prioritet u pretrazi i druge prednosti. Paketi se plaćaju mesečno/godišnje i mogu se otkazati u bilo kom trenutku.',
                'popular' => true
            ],
            [
                'q' => 'Da li se naplaćuje provizija od prodaje?',
                'a' => 'Ne, mi ne naplaćujemo proviziju od prodaje. Transakcije se obavljaju direktno između kupca i prodavca. Naš prihod dolazi isključivo od premium paketa i reklama.',
                'popular' => false
            ],
            [
                'q' => 'Kako da otkažem premium pretplatu?',
                'a' => 'Idite na "Moj profil" → "Premium paketi" → "Upravljaj pretplatom". Tamo možete otkazati pretplatu. Vaš premium status će važiti do kraja plaćenog perioda.',
                'popular' => false
            ],
            [
                'q' => 'Da li nude refundaciju?',
                'a' => 'Da, nudimo 14-dnevnu garanciju povraćaja novca za premium pakete ako niste zadovoljni. Kontaktirajte našu podršku za detalje.',
                'popular' => false
            ]
        ]
    ],
    [
        'id' => 'technical',
        'title' => 'Tehnička podrška',
        'icon' => 'fas fa-cogs',
        'color' => 'secondary',
        'questions' => [
            [
                'q' => 'Stranica se ne učitava kako treba, šta da radim?',
                'a' => 'Pokušajte: osvežite stranicu (Ctrl+F5), obrišite cache browser-a, proverite internet konekciju. Ako problem traje, kontaktirajte našu tehničku podršku sa detaljima problema i korišćenim browser-om.',
                'popular' => false
            ],
            [
                'q' => 'Kako da prijavim bag ili tehnički problem?',
                'a' => 'Koristite kontakt formu i odaberite "Tehnička podrška" kao naslov. Opširno opišite problem, dodajte screenshot-ove ako je moguće i navedite korake za reprodukciju.',
                'popular' => false
            ],
            [
                'q' => 'Da li podržavate sve browser-e?',
                'a' => 'Podržavamo moderne browser-e: Chrome, Firefox, Safari, Edge (najnovije verzije). Za optimalno iskustvo preporučujemo korišćenje najnovije verzije browser-a.',
                'popular' => false
            ],
            [
                'q' => 'Kako da onemogućim notifikacije?',
                'a' => 'Idite na "Moj profil" → "Podešavanja naloga" → "Notifikacije". Tamo možete podesiti koje notifikacije želite da primate (email, push, SMS).',
                'popular' => false
            ]
        ]
    ]
];

// Izračunaj ukupan broj pitanja
$totalQuestions = 0;
foreach ($faqCategories as $category) {
    $totalQuestions += count($category['questions']);
}
?>

<!-- HERO SECTION -->
<section class="faq-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold text-white mb-3">
                    Često postavljana pitanja
                </h1>
                <p class="lead text-white mb-4">
                    Pronađite brze odgovore na <?php echo $totalQuestions; ?> najčešćih pitanja o korišćenju Rasprodaja.rs
                </p>
                
                <!-- BRZA PRETRAGA -->
                <div class="faq-search-container mb-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" 
                               class="form-control border-start-0" 
                               id="faqSearch" 
                               placeholder="Pretražite pitanja (npr. 'kako postaviti oglas', 'premium', 'plaćanje')"
                               aria-label="Pretražite FAQ">
                        <button class="btn btn-outline-light" type="button" id="clearSearch" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="search-suggestions mt-2">
                        <small class="text-white opacity-75">
                            Pokušajte: 
                            <a href="#!" class="text-white text-decoration-underline search-suggestion">registracija</a>, 
                            <a href="#!" class="text-white text-decoration-underline search-suggestion">oglas</a>, 
                            <a href="#!" class="text-white text-decoration-underline search-suggestion">premium</a>, 
                            <a href="#!" class="text-white text-decoration-underline search-suggestion">kontakt</a>
                        </small>
                    </div>
                </div>
                
                <!-- STATISTIKA -->
                <div class="d-flex flex-wrap gap-3 text-white">
                    <div class="faq-stat">
                        <i class="fas fa-folder me-2"></i>
                        <?php echo count($faqCategories); ?> kategorija
                    </div>
                    <div class="faq-stat">
                        <i class="fas fa-question-circle me-2"></i>
                        <?php echo $totalQuestions; ?> pitanja
                    </div>
                    <div class="faq-stat">
                        <i class="fas fa-star me-2"></i>
                        <?php 
                        $popularCount = 0;
                        foreach ($faqCategories as $category) {
                            foreach ($category['questions'] as $question) {
                                if ($question['popular']) $popularCount++;
                            }
                        }
                        echo $popularCount;
                        ?> popularnih
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 text-center">
                <div class="faq-hero-icon">
                    <i class="fas fa-question-circle fa-6x text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KATEGORIJE I FAQ -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <!-- SIDEBAR SA KATEGORIJAMA -->
            <div class="col-lg-3 mb-5 mb-lg-0" style="z-index: 10;">
                <div class="sticky-top" style="top: 100px;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-list me-2"></i> Kategorije
                            </h5>
                            
                            <nav class="nav flex-column faq-category-nav" id="faqCategoriesNav">
                                <?php foreach ($faqCategories as $index => $category): ?>
                                <a class="nav-link py-2 d-flex align-items-center <?php echo $index === 0 ? 'active' : ''; ?>" 
                                   href="#category-<?php echo $category['id']; ?>"
                                   data-category="<?php echo $category['id']; ?>">
                                    <div class="faq-category-icon me-3">
                                        <i class="<?php echo $category['icon']; ?> text-<?php echo $category['color']; ?>"></i>
                                    </div>
                                    <div>
                                        <span class="fw-medium"><?php echo $category['title']; ?></span>
                                        <small class="d-block text-muted">
                                            <?php echo count($category['questions']); ?> pitanja
                                        </small>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </nav>
                            
                            <hr class="my-4">
                            
                            <div class="text-center">
                                <p class="small text-muted mb-2">Niste pronašli odgovor?</p>
                                <a href="/contact" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-headset me-2"></i> Kontaktirajte nas
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- POPULARNA PITANJA -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-body">
                            <h6 class="card-title mb-3">
                                <i class="fas fa-fire text-warning me-2"></i> Popularna pitanja
                            </h6>
                            
                            <div class="popular-questions">
                                <?php 
                                $popularQuestions = [];
                                foreach ($faqCategories as $category) {
                                    foreach ($category['questions'] as $question) {
                                        if ($question['popular']) {
                                            $popularQuestions[] = [
                                                'category' => $category['id'],
                                                'title' => $category['title'],
                                                'color' => $category['color'],
                                                'question' => $question['q']
                                            ];
                                        }
                                    }
                                }
                                $popularQuestions = array_slice($popularQuestions, 0, 5);
                                ?>
                                
                                <?php foreach ($popularQuestions as $pq): ?>
                                <a href="#category-<?php echo $pq['category']; ?>" 
                                   class="d-block text-decoration-none mb-2 popular-question-link">
                                    <div class="d-flex align-items-start">
                                        <span class="badge bg-<?php echo $pq['color']; ?> me-2 mt-1">●</span>
                                        <small class="text-muted"><?php echo $pq['question']; ?></small>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- GLAVNI SADRŽAJ - FAQ -->
            <div class="col-lg-9">
                <!-- REZULTATI PRETRAGE -->
                <div class="card border-0 shadow-sm mb-4" id="searchResults" style="display: none;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-search me-2"></i> Rezultati pretrage
                            </h5>
                            <button class="btn btn-sm btn-outline-secondary" id="clearResults">
                                <i class="fas fa-times me-1"></i> Obriši pretragu
                            </button>
                        </div>
                        <div id="searchResultsContent">
                            <!-- Rezultati će se dinamički popuniti -->
                        </div>
                    </div>
                </div>
                
                <!-- FAQ KATEGORIJE -->
                <div id="faqContent">
                    <?php foreach ($faqCategories as $category): ?>
                    <div class="faq-category-section mb-5" id="category-<?php echo $category['id']; ?>">
                        <div class="d-flex align-items-center mb-4">
                            <div class="faq-category-header-icon me-3">
                                <i class="<?php echo $category['icon']; ?> fa-2x text-<?php echo $category['color']; ?>"></i>
                            </div>
                            <div>
                                <h2 class="h3 mb-1"><?php echo $category['title']; ?></h2>
                                <p class="text-muted mb-0">
                                    <?php echo count($category['questions']); ?> pitanja u ovoj kategoriji
                                </p>
                            </div>
                        </div>
                        
                        <div class="accordion faq-accordion" id="accordion-<?php echo $category['id']; ?>">
                            <?php foreach ($category['questions'] as $qIndex => $question): 
                                $itemId = "faq-{$category['id']}-{$qIndex}";
                                $isPopular = $question['popular'] ?? false;
                            ?>
                            <div class="accordion-item faq-item" data-search-text="<?php echo htmlspecialchars(strtolower($question['q'] . ' ' . $question['a'])); ?>">
                                <h3 class="accordion-header">
                                    <button class="accordion-button collapsed <?php echo $isPopular ? 'popular-question' : ''; ?>" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#<?php echo $itemId; ?>" 
                                            aria-expanded="false" 
                                            aria-controls="<?php echo $itemId; ?>">
                                        <div class="d-flex align-items-center w-100">
                                            <div class="me-3">
                                                <i class="fas fa-question-circle text-<?php echo $category['color']; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1 text-start">
                                                <?php echo $question['q']; ?>
                                                <?php if ($isPopular): ?>
                                                <span class="badge bg-warning ms-2">
                                                    <i class="fas fa-fire me-1"></i> Popularno
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </button>
                                </h3>
                                <div id="<?php echo $itemId; ?>" 
                                     class="accordion-collapse collapse" 
                                     data-bs-parent="#accordion-<?php echo $category['id']; ?>">
                                    <div class="accordion-body">
                                        <div class="faq-answer">
                                            <?php echo nl2br(htmlspecialchars($question['a'])); ?>
                                            
                                            <!-- AKCIJE ZA ODPOVOR -->
                                            <div class="faq-answer-actions mt-4 pt-3 border-top">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <small class="text-muted">
                                                            <i class="far fa-clock me-1"></i>
                                                            Poslednje ažuriranje: <?php echo date('d.m.Y'); ?>
                                                        </small>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary faq-helpful-btn" 
                                                                data-question="<?php echo htmlspecialchars($question['q']); ?>">
                                                            <i class="fas fa-thumbs-up me-1"></i> Korisno
                                                            <span class="faq-helpful-count">0</span>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary ms-2" 
                                                                onclick="window.location.href='/contact'">
                                                            <i class="fas fa-question-circle me-1"></i> Još pitanja?
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- KATEGORIJA FOOTER -->
                        <div class="mt-4 text-end">
                            <a href="#!" class="btn btn-sm btn-outline-<?php echo $category['color']; ?> expand-all-category" 
                               data-category="<?php echo $category['id']; ?>">
                                <i class="fas fa-expand-alt me-1"></i> Proširi sve
                            </a>
                            <a href="#!" class="btn btn-sm btn-outline-<?php echo $category['color']; ?> collapse-all-category ms-2" 
                               data-category="<?php echo $category['id']; ?>">
                                <i class="fas fa-compress-alt me-1"></i> Skupi sve
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- NIJE PRONAĐEN ODPOVOR? -->
                <div class="card border-0 shadow-sm mt-5">
                    <div class="card-body text-center p-5">
                        <div class="faq-help-icon mb-4">
                            <i class="fas fa-hands-helping fa-4x text-primary opacity-50"></i>
                        </div>
                        <h3 class="h4 mb-3">Niste pronašli odgovor?</h3>
                        <p class="text-muted mb-4">
                            Naš tim podrške je tu da vam pomogne. Kontaktirajte nas i dobićete odgovor u najkraćem mogućem roku.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="/contact" class="btn btn-primary">
                                <i class="fas fa-headset me-2"></i> Kontakt forma
                            </a>
                            <a href="mailto:info@rasprodaja.rs" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i> Pošaljite email
                            </a>
                            <a href="tel:+381111234567" class="btn btn-outline-success">
                                <i class="fas fa-phone me-2"></i> Pozovite nas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATISTIKA KORISNOSTI -->
<section class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center mb-4 mb-md-0">
                <div class="faq-stat-large">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3 class="display-6 fw-bold">95%</h3>
                    <p class="text-muted mb-0">Korisnika pronalazi odgovore ovde</p>
                </div>
            </div>
            <div class="col-md-4 text-center mb-4 mb-md-0">
                <div class="faq-stat-large">
                    <i class="fas fa-clock fa-3x text-success mb-3"></i>
                    <h3 class="display-6 fw-bold">< 2min</h3>
                    <p class="text-muted mb-0">Prosečno vreme za pronalaženje odgovora</p>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="faq-stat-large">
                    <i class="fas fa-check-circle fa-3x text-warning mb-3"></i>
                    <h3 class="display-6 fw-bold">87%</h3>
                    <p class="text-muted mb-0">Zadovoljstvo korisnika odgovorima</p>
                </div>
            </div>
        </div>
    </div>
</section>

