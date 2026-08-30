/**
 * GLAVNI JAVASCRIPT FAJL ZA RASPRODAJA.RS
 * ISPRAVLJENA VERZIJA - sa SITE_URL fallback
 */

// GLOBALNE VARIJABLE FALLBACK
(function() {
    'use strict';
    
    
    // Proveri i postavi SITE_URL ako ne postoji
    if (typeof SITE_URL === 'undefined') {
        // Proveri različite izvore
        if (typeof window.SITE_CONFIG !== 'undefined' && window.SITE_CONFIG.url) {
            window.SITE_URL = window.SITE_CONFIG.url;
        } 
        else if (typeof window.SITE_URL_PHP !== 'undefined') {
            window.SITE_URL = window.SITE_URL_PHP;
        }
        else {
            window.SITE_URL = window.location.origin;
            console.warn('SITE_URL nije definisan, koristim:', window.SITE_URL);
        }
    }
    
    // Proveri USER_ID
    if (typeof USER_ID === 'undefined') {
        window.USER_ID = '0';
    }
    
    // Proveri USER_PACKAGE
    if (typeof USER_PACKAGE === 'undefined') {
        window.USER_PACKAGE = 'free';
    }
})();

// MODULE PATTERN za organizaciju koda
const RasprodajaApp = (function() {
    'use strict';
    
    // PRIVATNE VARIJABLE I FUNKCIJE
    let currentUser = null;
    let notifications = [];
    
    /**
     * Dobija SITE_URL bezbedno
     */
    function getSiteUrl() {
        return window.SITE_URL || window.location.origin;
    }
    
    /**
     * Učitava premium oglase na početnoj stranici
     */
    function loadPremiumAds() {
        const container = document.getElementById('premium-ads-container');
        if (!container) return;
        
        fetch(`${getSiteUrl()}/api/ads/list.php?premium=true&limit=6`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.ads.length > 0) {
                    renderPremiumAds(data.ads, container);
                } else {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Trenutno nema premium oglasa</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading premium ads:', error);
                container.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning">
                            Greška pri učitavanju oglasa. Pokušajte ponovo.
                        </div>
                    </div>
                `;
            });
    }
    
    /**
     * Renderuje premium oglase
     */
    function renderPremiumAds(ads, container) {
        let html = '';
        
        ads.forEach(ad => {
            html += `
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card ad-card h-100">
                        <span class="premium-badge">
                            <i class="fas fa-crown"></i> PREMIUM
                        </span>
                        <div class="position-relative">
                            <img src="${getSiteUrl()}/api/images.php?type=thumb&path=${ad.main_image}" 
                                 class="card-img-top" 
                                 alt="${ad.title}"
                                 onerror="this.src='${getSiteUrl()}/assets/images/defaults/no-image.jpg'">
                            <div class="position-absolute bottom-0 end-0 m-2">
                                <span class="badge bg-dark">${ad.category_name}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title ad-title">${ad.title}</h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-map-marker-alt"></i> ${ad.city}
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="ad-price mb-0">${formatPrice(ad.price)} RSD</h4>
                                <a href="?page=ad-detail&id=${ad.id}" 
                                   class="btn btn-sm btn-outline-primary">
                                    Detalji
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top">
                            <small class="text-muted">
                                <i class="far fa-clock"></i> ${formatDate(ad.created_at)}
                            </small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Formatira cenu sa tačkama za hiljade
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('sr-RS').format(price);
    }
    
    /**
     * Formatira datum u čitljiv oblik
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffDays === 0) {
            return 'Danas';
        } else if (diffDays === 1) {
            return 'Juče';
        } else if (diffDays < 7) {
            return `Pre ${diffDays} dana`;
        } else {
            return date.toLocaleDateString('sr-RS');
        }
    }
    
    /**
     * Validacija forme za oglas
     */
    function validateAdForm(formData) {
        const errors = [];
        
        // Proveri naslov
        if (!formData.title || formData.title.trim().length < 10) {
            errors.push('Naslov mora imati najmanje 10 karaktera');
        }
        
        // Proveri opis
        if (!formData.description || formData.description.trim().length < 20) {
            errors.push('Opis mora imati najmanje 20 karaktera');
        }
        
        // Proveri cenu
        if (!formData.price || isNaN(formData.price) || formData.price <= 0) {
            errors.push('Unesite validnu cenu');
        }
        
        // Proveri kategoriju
        if (!formData.category_id || formData.category_id === '0') {
            errors.push('Izaberite kategoriju');
        }
        
        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Prikazuje notifikaciju korisniku
     */
    function showNotification(type, message, duration = 5000) {
        // Kreiraj element notifikacije
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Dodaj u body
        document.body.appendChild(notification);
        
        // Automatski ukloni nakon trajanja
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        }
        
        // Čuvaj u nizu za debug
        notifications.push({
            type: type,
            message: message,
            timestamp: new Date()
        });
    }
    
    
    
    /**
     * Inicijalizuje tooltip-ove i popover-e
     */
    function initBootstrapComponents() {
        // Inicijalizuj Bootstrap tooltip-ove
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Inicijalizuj Bootstrap popover-e
        const popoverTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="popover"]')
        );
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    
    
    // JAVNO DOSTUPNE FUNKCIJE
    return {
        init: function() {
            // Inicijalizacija aplikacije
            initBootstrapComponents();
            
            
            // Globalni error handler za fetch
            window.addEventListener('unhandledrejection', function(event) {
                console.error('Unhandled promise rejection:', event.reason);
                showNotification('danger', 'Došlo je do greške. Pokušajte ponovo.');
            });
            
            console.log('Rasprodaja.rs aplikacija inicijalizovana');
        },
        
        loadPremiumAds: loadPremiumAds,
        
        validateAdForm: validateAdForm,
        
        showNotification: showNotification,
        
        formatPrice: formatPrice,
        
        formatDate: formatDate
    };
})();

// Pokreni aplikaciju kada se stranica učita
document.addEventListener('DOMContentLoaded', function() {
    RasprodajaApp.init();
});

// Globalne helper funkcije
window.Rasprodaja = RasprodajaApp;