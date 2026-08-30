<?php
/**
 * pages/packages.php - Stranica za pregled paketa
 * SAMO BANKOVNI RAČUN - Bez kartičnog plaćanja
 * DODATA TABELA TRANSAKCIJA
 */

// Ne zahtevamo login - stranica je javna!
$isLoggedIn = isLoggedIn();

// Ako je ulogovan, učitaj njegove podatke
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    
    // Dohvati trenutni paket
    $currentPackage = getUserCurrentPackage($userId);
    
    // FALLBACK - ako nema paket, koristi Free
    if (!$currentPackage) {
        $currentPackage = [
            'id' => 1,
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_ads' => 10,
            'max_images' => 10,
            'max_premium_ads' => 0,
            'features' => json_encode(['Osnovni paket']),
            'package_expires_at' => null,
            'is_active' => 1
        ];
    }
    
    // Dohvati transakcije korisnika (SVE, ne samo 5)
    $transactions = getUserTransactions($userId, 50);
    $limits = getUserLimits($userId);
}

// Učitaj sve pakete iz baze
$allPackages = getAllPackages();

// FALLBACK - ako nema paketa u bazi, kreiraj default
if (empty($allPackages)) {
    $allPackages = [
        [
            'id' => 1,
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_ads' => 10,
            'max_images' => 10,
            'max_premium_ads' => 0,
            'features' => json_encode(['Osnovni paket']),
            'is_active' => 1
        ],
        [
            'id' => 2,
            'name' => 'Silver',
            'slug' => 'silver',
            'price_monthly' => 490,
            'price_yearly' => 4900,
            'max_ads' => 20,
            'max_images' => 15,
            'max_premium_ads' => 5,
            'features' => json_encode([
                'Istaknuti oglasi',
                'Veća vidljivost',
                'Prioritetna podrška'
            ]),
            'is_active' => 1
        ],
        [
            'id' => 3,
            'name' => 'Gold',
            'slug' => 'gold',
            'price_monthly' => 990,
            'price_yearly' => 9900,
            'max_ads' => 999999,
            'max_images' => 20,
            'max_premium_ads' => 20,
            'features' => json_encode([
                'Neograničeni oglasi',
                'Premium pozicija',
                'VIP podrška',
                'Statistika oglasa'
            ]),
            'is_active' => 1
        ]
    ];
}

// Generiši CSRF token
$csrfToken = generateCSRFToken();

// Postavi title
$pageTitle = 'Paketi - Rasprodaja.rs';
$pageDescription = 'Nadogradite svoj nalog i povećajte vidljivost vaših oglasa.';
$pageSpecificCSS = ['packages.css'];
$pageSpecificJS = ['packages.js'];

$inlineScripts = "
    window.SITE_CONFIG = {
        url: '" . SITE_URL . "',
        userId: '" . ($_SESSION['user_id'] ?? 0) . "',
        csrfToken: '$csrfToken'
    };
";
?>

<div class="packages-page">
    <div class="container py-5">
        
        <!-- HERO SECTION -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-crown text-warning me-2"></i>
                Nadogradite svoj nalog
            </h1>
            <p class="lead text-muted mx-auto" style="max-width: 700px;">
                Povećajte vidljivost vaših oglasa i prodajte brže uz naše premium pakete.
            </p>
        </div>
        
        <!-- TRENUTNI PAKET (SAMO ZA ULOGOVANE) -->
        <?php if ($isLoggedIn && $currentPackage): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-crown me-2"></i> Vaš trenutni paket
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h3 class="text-success mb-2"><?php echo htmlspecialchars($currentPackage['name']); ?></h3>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <small class="text-muted">Preostalo oglasa</small>
                                        <h4 class="mb-0">
                                            <?php 
                                            $remaining = $limits['remaining_ads'] ?? 0;
                                            $isUnlimited = ($limits['ad_limit'] ?? 0) >= 999999;
                                            if ($isUnlimited): ?>
                                                <i class="fas fa-infinity text-success"></i>
                                            <?php else: ?>
                                                <?php echo max(0, $remaining); ?>
                                                <small class="text-muted">/ <?php echo $limits['ad_limit']; ?></small>
                                            <?php endif; ?>
                                        </h4>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Premium oglasa</small>
                                        <h4 class="mb-0">
                                            <?php 
                                            $premiumRemaining = ($limits['premium_limit'] ?? 0) - getCurrentPremiumAdCount($userId);
                                            echo max(0, $premiumRemaining);
                                            ?>
                                            <small class="text-muted">/ <?php echo $limits['premium_limit'] ?? 0; ?></small>
                                        </h4>
                                    </div>
                                </div>
                                <?php if (!empty($currentPackage['package_expires_at']) && $currentPackage['package_expires_at'] !== '0000-00-00 00:00:00'): ?>
                                <p class="text-muted mt-3 mb-0">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Važi do: <strong><?php echo date('d.m.Y', strtotime($currentPackage['package_expires_at'])); ?></strong>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="h2 mb-0">
                                    <?php if ($currentPackage['price_monthly'] > 0): ?>
                                        <?php echo number_format($currentPackage['price_monthly'], 0, ',', '.'); ?> RSD
                                        <small class="text-muted fs-6">/mesečno</small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary fs-6">BESPLATAN</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ============================================ -->
        <!-- TABELA TRANSAKCIJA - NOVO! -->
        <!-- ============================================ -->
        <?php if ($isLoggedIn && !empty($transactions)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2 text-primary"></i>
                            Moje transakcije
                            <span class="badge bg-secondary ms-2"><?php echo count($transactions); ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Paket</th>
                                        <th>Iznos</th>
                                        <th>Period</th>
                                        <th>Poziv na broj</th>
                                        <th>Status</th>
                                        <th>Datum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $index => $transaction): 
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'refunded' => 'secondary'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Na čekanju',
                                            'completed' => 'Aktivan',
                                            'failed' => 'Neuspešno',
                                            'refunded' => 'Refundirano'
                                        ];
                                        $statusColor = $statusColors[$transaction['status']] ?? 'secondary';
                                        $statusLabel = $statusLabels[$transaction['status']] ?? $transaction['status'];
                                        
                                        // Dohvati poziv na broj iz payment_details
                                        $referenceNumber = '';
                                        if (!empty($transaction['payment_details'])) {
                                            $details = is_string($transaction['payment_details']) 
                                                ? json_decode($transaction['payment_details'], true) 
                                                : $transaction['payment_details'];
                                            $referenceNumber = $details['reference_number'] ?? $details['reference'] ?? '';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="fw-semibold">
                                                <?php echo htmlspecialchars($transaction['package_name'] ?? 'Nepoznat'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>
                                                <?php echo number_format($transaction['amount'], 0, ',', '.'); ?> RSD
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($transaction['period'] === 'yearly'): ?>
                                                <span class="badge bg-info">Godišnje</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Mesečno</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code class="small"><?php echo htmlspecialchars($referenceNumber ?: '-'); ?></code>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                            <?php if ($transaction['status'] === 'pending'): ?>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Čeka potvrdu
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if (count($transactions) > 10): ?>
                    <div class="card-footer text-center">
                        <button class="btn btn-sm btn-outline-primary" id="showAllTransactions">
                            <i class="fas fa-chevron-down me-1"></i>
                            Prikaži sve transakcije
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- PAKETI (KARTICE) -->
        <div class="row justify-content-center g-4 mb-5">
            <?php foreach ($allPackages as $package):
                $isCurrent = $isLoggedIn && $currentPackage && $currentPackage['id'] == $package['id'];
                $isPopular = $package['name'] == 'Silver';
                $isPremium = $package['name'] == 'Gold';
                $features = is_string($package['features']) ? json_decode($package['features'], true) : $package['features'];
                $isUnlimited = ($package['max_ads'] >= 999999);
            ?>
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="card h-100 package-card <?php echo $isPopular ? 'border-primary shadow-lg' : ''; ?> <?php echo $isCurrent ? 'border-success' : ''; ?>">
                    
                    <?php if ($isPopular): ?>
                    <div class="popular-badge">
                        <i class="fas fa-star me-1"></i> Najpopularnije
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-header text-center py-4 <?php 
                        echo $isPremium ? 'bg-gradient-warning' : ($isPopular ? 'bg-gradient-primary' : 'bg-gradient-secondary'); 
                    ?>">
                        <h3 class="mb-0 text-white"><?php echo htmlspecialchars($package['name']); ?></h3>
                        <?php if ($isCurrent): ?>
                        <span class="badge bg-success mt-2">
                            <i class="fas fa-check-circle me-1"></i> Trenutni paket
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body text-center">
                        <!-- CENA -->
                        <div class="mb-3">
                            <?php if ($package['price_monthly'] > 0): ?>
                                <span class="display-4 fw-bold text-primary">
                                    <?php echo number_format($package['price_monthly'], 0, ',', '.'); ?>
                                </span>
                                <span class="text-muted">RSD</span>
                                <div class="text-muted small">mesečno</div>
                                
                                <?php if ($package['price_yearly'] > 0): ?>
                                <div class="mt-2 p-2 bg-light rounded">
                                    <span class="text-success fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo number_format($package['price_yearly'], 0, ',', '.'); ?> RSD
                                    </span>
                                    <div class="small text-muted">godišnje (ušteda 17%)</div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="display-4 fw-bold text-success">BESPLATNO</span>
                                <div class="text-muted small">zauvek</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- KARAKTERISTIKE -->
                        <ul class="list-unstyled text-start mt-4">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>
                                    <?php if ($isUnlimited): ?>
                                        <i class="fas fa-infinity me-1"></i> Neograničeno
                                    <?php else: ?>
                                        <?php echo $package['max_ads']; ?>
                                    <?php endif; ?>
                                </strong>
                                oglasa mesečno
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong><?php echo $package['max_images']; ?></strong> slika po oglasu
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong><?php echo $package['max_premium_ads']; ?></strong> premium oglasa
                            </li>
                            <?php if ($features && is_array($features)): ?>
                                <?php foreach ($features as $feature): ?>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <?php echo htmlspecialchars($feature); ?>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="small text-muted mt-3">
                            <i class="fas fa-sync-alt me-1"></i>
                            Limit se obnavlja svakog meseca
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent border-0 pb-4">
                        <?php if (!$isLoggedIn): ?>
                            <a href="/login" class="btn btn-outline-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i> Prijavite se
                            </a>
                        <?php elseif ($isCurrent): ?>
                            <button class="btn btn-success w-100 py-2" disabled>
                                <i class="fas fa-check-circle me-2"></i> Vaš trenutni paket
                            </button>
                        <?php elseif ($package['name'] === 'Free'): ?>
                            <a href="/dashboard" class="btn btn-outline-secondary w-100 py-2">
                                <i class="fas fa-user-edit me-2"></i> Ostani na Free paketu
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary w-100 py-2 select-package" 
                                    data-package-id="<?php echo $package['id']; ?>"
                                    data-package-name="<?php echo htmlspecialchars($package['name']); ?>"
                                    data-package-price="<?php echo $package['price_monthly']; ?>"
                                    data-package-yearly="<?php echo $package['price_yearly']; ?>">
                                <i class="fas fa-shopping-cart me-2"></i> 
                                Izaberi paket
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- UPOREDNA TABELA -->
        <div class="card mt-5 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-simple me-2 text-primary"></i>
                    Uporedi sve pakete
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%">Karakteristika</th>
                                <?php foreach ($allPackages as $package): ?>
                                <th class="text-center">
                                    <?php echo htmlspecialchars($package['name']); ?>
                                    <?php if ($package['name'] == 'Gold'): ?>
                                        <i class="fas fa-crown text-warning ms-1"></i>
                                    <?php endif; ?>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Cena mesečno</td>
                                <?php foreach ($allPackages as $package): ?>
                                <td class="text-center">
                                    <?php if ($package['price_monthly'] > 0): ?>
                                        <?php echo number_format($package['price_monthly'], 0, ',', '.'); ?> RSD
                                    <?php else: ?>
                                        <span class="badge bg-success">Besplatno</span>
                                    <?php endif; ?>
                                 </td>
                                <?php endforeach; ?>
                             </tr>
                            <tr>
                                <td class="fw-semibold">Cena godišnje</td>
                                <?php foreach ($allPackages as $package): ?>
                                <td class="text-center">
                                    <?php if ($package['price_yearly'] > 0): ?>
                                        <?php echo number_format($package['price_yearly'], 0, ',', '.'); ?> RSD
                                        <div class="small text-success">ušteda 17%</div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                 </td>
                                <?php endforeach; ?>
                             </tr>
                            <tr>
                                <td class="fw-semibold">Oglasa mesečno</td>
                                <?php foreach ($allPackages as $package): ?>
                                <td class="text-center fw-bold">
                                    <?php if ($package['max_ads'] >= 999999): ?>
                                        <i class="fas fa-infinity text-warning"></i> Neograničeno
                                    <?php else: ?>
                                        <?php echo $package['max_ads']; ?>
                                    <?php endif; ?>
                                 </td>
                                <?php endforeach; ?>
                             </tr>
                            <tr>
                                <td class="fw-semibold">Slika po oglasu</td>
                                <?php foreach ($allPackages as $package): ?>
                                <td class="text-center"><?php echo $package['max_images']; ?></td>
                                <?php endforeach; ?>
                             </tr>
                            <tr>
                                <td class="fw-semibold">Premium oglasa</td>
                                <?php foreach ($allPackages as $package): ?>
                                <td class="text-center"><?php echo $package['max_premium_ads']; ?></td>
                                <?php endforeach; ?>
                             </tr>
                            <?php if (!empty($features)): ?>
                            <tr>
                                <td class="fw-semibold">Dodatne pogodnosti</td>
                                <?php foreach ($allPackages as $package): 
                                    $pkgFeatures = json_decode($package['features'], true);
                                ?>
                                <td class="text-center">
                                    <?php if ($pkgFeatures && is_array($pkgFeatures)): ?>
                                        <ul class="list-unstyled small mb-0">
                                            <?php foreach ($pkgFeatures as $feature): ?>
                                            <li><i class="fas fa-check-circle text-success me-1"></i> <?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                     </table>
                </div>
            </div>
        </div>
        
        <!-- FOOTNOTE -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                * Limit oglasa se obnavlja svakog meseca. Neiskorišćeni oglasi se ne prenose.
                Gold paket se automatski obnavlja svakih 60 dana.
            </small>
        </div>
        
        <!-- FAQ SEKCIJA -->
        <div class="mt-5">
            <h4 class="text-center mb-4">
                <i class="fas fa-question-circle me-2 text-primary"></i>
                Često postavljana pitanja
            </h4>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="accordion" id="packagesFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Šta znači "mesečno obnavljanje limita"?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#packagesFAQ">
                                <div class="accordion-body">
                                    Svakog meseca dobijate svežu "dozu" oglasa. Na primer, Silver paket vam daje 20 oglasa mesečno. 
                                    Ako ste u januaru postavili 15 oglasa, u februaru dobijate novih 20 (ukupno 25 aktivnih, ali ne možete postaviti više od 20 novih mesečno).
                                    Neiskorišćeni oglasi se ne prenose u naredni mesec.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Da li se godišnja pretplata isplati?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#packagesFAQ">
                                <div class="accordion-body">
                                    Da! Godišnjom pretplatom štedite 17% u odnosu na mesečno plaćanje. 
                                    Na primer, Silver paket mesečno košta 490 RSD, a godišnje 4.900 RSD (ušteda od 980 RSD).
                                    I dalje dobijate isti broj oglasa svakog meseca, samo je jeftinije.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Šta se dešava sa oglasima kada istekne paket?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#packagesFAQ">
                                <div class="accordion-body">
                                    Svi vaši aktivni oglasi ostaju vidljivi, ali se vraćate na Free paket ograničenja:
                                    <ul class="mt-2 mb-0">
                                        <li>Maksimalno 10 aktivnih oglasa</li>
                                        <li>Maksimalno 10 slika po oglasu</li>
                                        <li>Premium oglasi gube status</li>
                                    </ul>
                                    Ako imate više od 10 oglasa, nećete moći postavljati nove dok ne obrišete višak.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Kako mogu da platim?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#packagesFAQ">
                                <div class="accordion-body">
                                    Prihvatamo sledeće načine plaćanja:
                                    <ul class="mt-2 mb-0">
                                        <li><i class="fas fa-university me-2"></i> Bankovni prenos</li>
                                    </ul>
                                    Nakon uplate, paket se aktivira automatski (do 24h za bankovni prenos).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL ZA PLAĆANJE - SAMO BANKOVNI RAČUN -->
<!-- ============================================ -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-university me-2"></i>
                    <span id="modalPackageName">Silver</span> - Uplata na račun
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm" method="POST" action="/api/package/upgrade.php">
                    <input type="hidden" name="package_id" id="modalPackageId">
                    <input type="hidden" name="period" value="monthly" id="modalPeriod">
                    <input type="hidden" name="payment_method" value="bank">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="reference_number" id="modalReferenceNumber">
                    
                    <!-- PODACI O PAKETU -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="text-muted mb-2">Izabrani paket</h6>
                                    <h4 id="modalPackageNameDisplay" class="text-primary">Silver</h4>
                                    <div class="h3 mb-2" id="modalPackagePrice">490 RSD</div>
                                    <div class="text-muted small">/ mesečno</div>
                                    
                                    <hr>
                                    
                                    <div class="mb-2">
                                        <label class="form-label fw-semibold">Period plaćanja</label>
                                        <select class="form-select" id="paymentPeriod">
                                            <option value="monthly">Mesečno</option>
                                            <option value="yearly">Godišnje (ušteda 17%)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PODACI ZA UPLATU -->
                        <div class="col-md-6">
                            <div class="card h-100 border-success">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Podaci za uplatu
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6 class="mb-2"><i class="fas fa-building me-2"></i> Podaci o primaocu:</h6>
                                        <p class="mb-1"><strong>Primaoc:</strong> Rasprodaja DOO Novi Pazar</p>
                                        <p class="mb-1"><strong>PIB:</strong> 115816367</p>
                                        <p class="mb-1"><strong>Matični broj:</strong> 22318454</p>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <h6 class="mb-2"><i class="fas fa-university me-2"></i> Bankovni račun:</h6>
                                        <p class="mb-1"><strong>Banka:</strong> RAIFFEISEN BANKA</p>
                                        <p class="mb-1"><strong>Račun:</strong> <code class="fw-bold">265-1100310108783-08</code></p>
                                    </div>
                                    
                                    <div class="alert alert-secondary">
                                        <h6 class="mb-2"><i class="fas fa-hashtag me-2"></i> Poziv na broj:</h6>
                                        <p class="mb-0">
                                            <code class="fw-bold fs-5" id="referenceNumber"><?php echo time(); ?>-<?php echo $_SESSION['user_id'] ?? 'XXXX'; ?></code>
                                        </p>
                                        <small class="text-muted">Obavezno navedite poziv na broj prilikom uplate!</small>
                                    </div>
                                    
                                    <div class="alert alert-success">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Važno:</strong> Nakon uplate, paket se aktivira u roku od 24h. 
                                        Dobijate potvrdu na email.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- INSTRUKCIJE -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-2">
                                <i class="fas fa-list-check text-primary me-2"></i>
                                Uputstvo za uplatu:
                            </h6>
                            <ol class="mb-0">
                                <li>Izaberite period plaćanja (mesečno ili godišnje)</li>
                                <li>Kopirajte <strong>poziv na broj</strong> (xxxxx-xxx)</li>
                                <li>Izvršite uplatu na gore navedeni račun</li>
                                <li>Paket će biti aktiviran u roku od 24h</li>
                            </ol>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Zatvori
                </button>
                <button type="button" class="btn btn-success" id="confirmPaymentBtn">
                    <i class="fas fa-check-circle me-2"></i> 
                    <span id="confirmButtonText">Potvrdi i uplati</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Paket kartice stilovi */
.package-card {
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 20px;
    overflow: hidden;
}

.package-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.popular-badge {
    position: absolute;
    top: 15px;
    right: -30px;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
    padding: 5px 40px;
    font-size: 0.75rem;
    font-weight: 600;
    transform: rotate(45deg);
    z-index: 10;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #f59e0b, #f97316);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
}

.bg-gradient-secondary {
    background: linear-gradient(135deg, #64748b, #475569);
}

@media (max-width: 768px) {
    .package-card:hover {
        transform: translateY(-5px);
    }
    
    .popular-badge {
        font-size: 0.65rem;
        padding: 3px 35px;
    }
}

/* Tabela transakcija - hover efekat */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: default;
}
</style>