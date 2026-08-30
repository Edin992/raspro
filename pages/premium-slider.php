<?php
/**
 * premium-slider.php - Premium slider za home page (SMANJENA VERZIJA)
 */

// Proveri da li su CSS i JS već dodati
if (!isset($pageSpecificCSS)) {
    $pageSpecificCSS = [];
}
if (!isset($pageSpecificJS)) {
    $pageSpecificJS = [];
}

// Dodaj CSS i JS samo jednom
if (!in_array('premium-slider.css', $pageSpecificCSS)) {
    $pageSpecificCSS[] = 'premium-slider.css';
}
if (!in_array('premium-slider.js', $pageSpecificJS)) {
    $pageSpecificJS[] = 'premium-slider.js';
}
?>

<!-- PREMIUM SLIDER SECTION - SMANJENA VERZIJA -->
<section class="premium-slider-section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h4 mb-0">
                    <i class="fas fa-crown text-warning me-2"></i> Premium Oglasi
                </h3>
                <p class="text-muted small mb-0">
                    Istaknuti oglasi sa VIP tretmanom
                </p>
            </div>
            <a href="/ads/premium/" class="btn btn-sm btn-outline-warning">
                Vidi sve <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        
        <!-- SLIDER CONTAINER -->
        <div class="premium-slider-container position-relative">
            <!-- SLIDER TRACK -->
            <div class="premium-slider-track" id="premium-slider-track">
                <!-- Loading state -->
                <div class="slider-loading">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <!-- NAVIGATION BUTTONS -->
            <button class="slider-btn slider-btn-prev" id="slider-prev" aria-label="Prethodni" disabled>
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-btn slider-btn-next" id="slider-next" aria-label="Sledeći" disabled>
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- SCROLL INDICATOR -->
            <div class="slider-scrollbar mt-2 d-none d-md-block">
                <div class="scrollbar-track">
                    <div class="scrollbar-thumb" id="scrollbar-thumb"></div>
                </div>
            </div>
        </div>
    </div>
</section>