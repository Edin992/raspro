<?php
/**
 * layout/cookie-consent.php - Globalni cookie consent (banner + modal)
 * Ukljucuje se na SVIM stranicama iz layout/scripts.php.
 * Logika: assets/js/cookies.js ; cuvanje izbora: api/consent/save.php (SQL)
 */
?>

<!-- ============ COOKIE CONSENT BANNER (ceo sajt) ============ -->
<div id="cookieConsentBanner" class="fixed-bottom p-3 bg-dark text-white d-none shadow-lg" style="z-index:1055;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <p class="mb-0 small">
                    <i class="fas fa-cookie-bite me-2 text-warning"></i>
                    Mi koristimo kolačiće da bismo poboljšali vaše iskustvo,
                    prikazivali relevantne reklame i merili posećenost.
                    Vaš izbor se čuva i možete ga promeniti bilo kada.
                    <a href="/cookies" class="text-warning text-decoration-none ms-1">Saznaj više</a>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
                <button class="btn btn-outline-light btn-sm" id="cookieRejectBtn">
                    Samo neophodni
                </button>
                <button class="btn btn-outline-info btn-sm ms-1" id="cookieManageBtn">
                    Prilagodi
                </button>
                <button class="btn btn-primary btn-sm ms-1" id="cookieAcceptBtn">
                    <i class="fas fa-check me-1"></i> Prihvatam sve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============ COOKIE SETTINGS MODAL (ceo sajt) ============ -->
<div class="modal fade" id="cookieSettingsModal" tabindex="-1" aria-hidden="true" aria-labelledby="cookieSettingsModalTitle">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cookieSettingsModalTitle">
                    <i class="fas fa-sliders-h me-2"></i> Podešavanje kolačića
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Izaberite koje vrste kolačića želite da prihvatite.
                    Neophodni kolačići su uvek aktivni jer su potrebni za osnovno funkcionisanje sajta.
                </p>

                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-primary me-2">Neophodni</span>
                                Uvek aktivni
                            </h6>
                            <small class="text-muted">Session, sigurnosni, autentifikacioni kolačići</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked disabled aria-label="Neophodni kolačići (uvvek aktivni)">
                        </div>
                    </div>
                    <p class="small mb-0">
                        Ovi kolačići su neophodni za funkcionisanje sajta. Bez njih ne možete da se prijavite
                        ili koristite osnovne funkcionalnosti.
                    </p>
                </div>

                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-success me-2">Funkcionalni</span>
                            </h6>
                            <small class="text-muted">Jezičke postavke, tema, preferencije</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cookieFunctional" checked aria-label="Funkcionalni kolačići">
                        </div>
                    </div>
                    <p class="small mb-0">
                        Omogućavaju da sajt zapamti vaše izbore (kao što su jezik, region ili tema)
                        i pružaju poboljšane, ličnije funkcije.
                    </p>
                </div>

                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-info me-2">Analitički</span>
                            </h6>
                            <small class="text-muted">Google Analytics, statistika poseta</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cookieAnalytics" aria-label="Analitički kolačići">
                        </div>
                    </div>
                    <p class="small mb-0">
                        Omogućavaju nam da razumemo kako posetioci koriste naš sajt.
                        Ovi podaci su anonimni i pomažu nam da poboljšamo funkcionalnost.
                    </p>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="mb-0">
                                <span class="badge bg-warning me-2">Marketinški</span>
                            </h6>
                            <small class="text-muted">Reklamne mreže, targetiranje</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cookieMarketing" aria-label="Marketinški kolačići">
                        </div>
                    </div>
                    <p class="small mb-0">
                        Koriste se za praćenje posetilaca na različitim sajtovima. Cilj je prikazati
                        relevantne oglase za pojedinog korisnika.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                <button type="button" class="btn btn-primary" id="cookieSaveBtn">
                    <i class="fas fa-save me-2"></i> Sačuvaj izbore
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo SITE_URL; ?>/assets/js/cookies.js" defer></script>
