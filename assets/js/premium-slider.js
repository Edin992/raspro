/**
 * premium-slider.js - JavaScript za premium slider na home page
 * KOMPLETNA IMPLEMENTACIJA SA ERROR HANDLINGOM
 */

class PremiumSlider {
    constructor() {
        this.sliderTrack = document.getElementById('premium-slider-track');
        this.btnPrev = document.getElementById('slider-prev');
        this.btnNext = document.getElementById('slider-next');
        this.scrollbarThumb = document.getElementById('scrollbar-thumb');
        
        this.cardWidth = 320; // Širina kartice + gap
        this.visibleCards = 3;
        this.currentPosition = 0;
        this.maxPosition = 0;
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        if (!this.sliderTrack) {
            console.error('Premium slider track not found');
            return;
        }
        
        console.log('PremiumSlider initialized');
        this.loadPremiumAds();
        this.initEventListeners();
        this.updateScrollbar();
        
        // Update on window resize
        window.addEventListener('resize', () => {
            setTimeout(() => {
                this.calculateMaxPosition();
                this.updateNavigationButtons();
                this.updateScrollbar();
            }, 100);
        });
    }
    
    async loadPremiumAds() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            // Koristi novi premium API endpoint
            const apiUrl = '/api/ads/premium-list.php?limit=20';
            console.log('Loading premium ads from:', apiUrl);
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('Premium API response:', data);
            
            if (data.success && data.ads && data.ads.length > 0) {
                this.renderAds(data.ads);
                this.calculateMaxPosition();
                this.updateNavigationButtons();
            } else {
                this.showEmptyState(data.message || 'Trenutno nema premium oglasa');
            }
            
        } catch (error) {
            console.error('Error loading premium ads:', error);
            this.showError('Greška pri učitavanju premium oglasa');
        } finally {
            this.isLoading = false;
        }
    }
    
    renderAds(ads) {
        if (!ads || ads.length === 0) {
            this.showEmptyState();
            return;
        }
        
        // Očisti slider track
        this.sliderTrack.innerHTML = '';
        
        // Dodaj kartice
        ads.forEach(ad => {
            const card = this.createAdCard(ad);
            this.sliderTrack.appendChild(card);
        });
        
        // Sakrij loading
        const loadingEl = this.sliderTrack.querySelector('.slider-loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
        
        console.log(`Rendered ${ads.length} premium ads`);
    }
    
    createAdCard(ad) {
    const timeAgo = ad.time_ago || 
        (ad.created_at ? this.formatTimeAgo(ad.created_at) : 'Nedavno');
    
    const imageUrl = ad.image || '/assets/images/no-image.png';
    const sellerName = ad.seller?.name || ad.seller?.username || 'Korisnik';
    const sellerVerified = ad.seller?.verified ? 
        '<i class="fas fa-check-circle text-success ms-1" title="Verifikovan korisnik"></i>' : '';
    
    // Truncate naslov na 25 karaktera
    const truncatedTitle = this.truncateText(ad.title, 25);
    const fullTitle = this.escapeHtml(ad.title);
    
    const card = document.createElement('div');
    card.className = 'premium-slider-card';
    card.innerHTML = `
        <div class="card-img-top position-relative">
            <img src="${imageUrl}" 
                 class="img-fluid w-100" 
                 alt="${fullTitle}"
                 loading="lazy"
                 onerror="this.src='/assets/images/no-image.png'">
            
            <div class="premium-badge">
                <span class="badge">
                    <i class="fas fa-crown me-1"></i>
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <h5 class="card-title" title="${fullTitle}">
                <a href="/ad/${ad.id}/${ad.slug}">
                    ${truncatedTitle}
                </a>
            </h5>
            
            <div class="price">${ad.price_formatted}</div>
            
            <div class="location">
                <i class="fas fa-map-marker-alt"></i>
                ${this.escapeHtml(ad.city || 'Nije navedeno')}
            </div>
            
            <div class="seller-info mt-2">
                <div class="seller-info-wrapper">
                    <small class="text-muted d-flex align-items-center w-100">
                        <i class="fas fa-user me-2"></i> 
                        <span class="seller-name text-truncate">
                            ${this.escapeHtml(sellerName)}
                        </span>
                        <span class="seller-verified ms-auto">
                            ${sellerVerified}
                        </span>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <small><i class="far fa-clock"></i> ${timeAgo}</small>
            <small><i class="fas fa-eye"></i> ${ad.views || 0}</small>
        </div>
    `;
    
    card.addEventListener('click', (e) => {
        if (!e.target.closest('a')) {
            const link = card.querySelector('a');
            if (link) {
                window.location.href = link.href;
            }
        }
    });
    
    return card;
}
    
    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 60) {
            return `pre ${diffMins} min`;
        } else if (diffHours < 24) {
            return `pre ${diffHours} h`;
        } else if (diffDays < 7) {
            return `pre ${diffDays} d`;
        } else {
            return date.toLocaleDateString('sr-RS');
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    // Dodaj ovu metodu u PremiumSlider klasu (posle escapeHtml metode)
        
    truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return this.escapeHtml(text);
        return this.escapeHtml(text.substring(0, maxLength)) + '...';
    }
    
    calculateMaxPosition() {
        if (!this.sliderTrack) return;
        
        const totalWidth = this.sliderTrack.scrollWidth;
        const containerWidth = this.sliderTrack.parentElement.clientWidth;
        this.maxPosition = Math.max(0, totalWidth - containerWidth);
        
        console.log('Max position calculated:', this.maxPosition);
    }
    
    scrollTo(position) {
        if (!this.sliderTrack) return;
        
        this.currentPosition = Math.max(0, Math.min(position, this.maxPosition));
        this.sliderTrack.scrollTo({
            left: this.currentPosition,
            behavior: 'smooth'
        });
        
        this.updateNavigationButtons();
        this.updateScrollbar();
    }
    
    scrollLeft() {
        const newPosition = this.currentPosition - (this.cardWidth * this.visibleCards);
        this.scrollTo(newPosition);
    }
    
    scrollRight() {
        const newPosition = this.currentPosition + (this.cardWidth * this.visibleCards);
        this.scrollTo(newPosition);
    }
    
    updateNavigationButtons() {
        if (this.btnPrev) {
            this.btnPrev.disabled = this.currentPosition <= 0;
        }
        
        if (this.btnNext) {
            this.btnNext.disabled = this.currentPosition >= this.maxPosition;
        }
    }
    
    updateScrollbar() {
        if (this.scrollbarThumb && this.maxPosition > 0) {
            const percentage = (this.currentPosition / this.maxPosition) * 70 + 15;
            this.scrollbarThumb.style.width = `${percentage}%`;
        }
    }
    
    initEventListeners() {
        // Navigation buttons
        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', () => this.scrollLeft());
        }
        
        if (this.btnNext) {
            this.btnNext.addEventListener('click', () => this.scrollRight());
        }
        
        // Scrollbar click
        if (this.scrollbarThumb && this.scrollbarThumb.parentElement) {
            this.scrollbarThumb.parentElement.addEventListener('click', (e) => {
                const trackRect = e.currentTarget.getBoundingClientRect();
                const clickPosition = e.clientX - trackRect.left;
                const percentage = clickPosition / trackRect.width;
                
                const newPosition = percentage * this.maxPosition;
                this.scrollTo(newPosition);
            });
        }
        
        // Mouse wheel scroll
        if (this.sliderTrack) {
            this.sliderTrack.addEventListener('wheel', (e) => {
                e.preventDefault();
                const scrollAmount = e.deltaY * 0.5; // Umanjimo brzinu scroll-a
                this.sliderTrack.scrollLeft += scrollAmount;
                this.currentPosition = this.sliderTrack.scrollLeft;
                this.updateNavigationButtons();
                this.updateScrollbar();
            });
        }
        
        // Touch events za mobile
        let touchStartX = 0;
        let touchStartScrollLeft = 0;
        
        if (this.sliderTrack) {
            this.sliderTrack.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
                touchStartScrollLeft = this.sliderTrack.scrollLeft;
            });
            
            this.sliderTrack.addEventListener('touchmove', (e) => {
                if (!touchStartX) return;
                
                const touchX = e.touches[0].clientX;
                const diff = touchStartX - touchX;
                this.sliderTrack.scrollLeft = touchStartScrollLeft + diff;
                this.currentPosition = this.sliderTrack.scrollLeft;
                this.updateNavigationButtons();
                this.updateScrollbar();
            });
            
            this.sliderTrack.addEventListener('touchend', () => {
                touchStartX = 0;
                touchStartScrollLeft = 0;
            });
        }
        
        // Auto-hide buttons on mobile
        this.checkMobileView();
        window.addEventListener('resize', () => this.checkMobileView());
    }
    
    checkMobileView() {
        if (window.innerWidth <= 768) {
            if (this.btnPrev) this.btnPrev.style.display = 'none';
            if (this.btnNext) this.btnNext.style.display = 'none';
        } else {
            if (this.btnPrev) this.btnPrev.style.display = 'flex';
            if (this.btnNext) this.btnNext.style.display = 'flex';
        }
    }
    
    showLoading() {
        this.sliderTrack.innerHTML = `
            <div class="slider-loading">
                <div class="spinner"></div>
                <p class="mt-3 text-muted">Učitavam premium oglase...</p>
            </div>
        `;
    }
    
    showEmptyState(message = 'Trenutno nema premium oglasa') {
        this.sliderTrack.innerHTML = `
            <div class="slider-empty">
                <i class="fas fa-crown fa-3x text-warning mb-3"></i>
                <h4 class="text-warning">${message}</h4>
                <p class="text-muted mb-4">
                    Budite prvi koji će istaknuti svoj oglas!
                </p>
                <a href="?page=create-ad" class="btn btn-warning">
                    <i class="fas fa-plus me-2"></i> Postavi premium oglas
                </a>
            </div>
        `;
        
        // Sakrij navigation buttons
        if (this.btnPrev) this.btnPrev.style.display = 'none';
        if (this.btnNext) this.btnNext.style.display = 'none';
        if (this.scrollbarThumb) this.scrollbarThumb.style.display = 'none';
    }
    
    showError(message = 'Greška pri učitavanju') {
        this.sliderTrack.innerHTML = `
            <div class="slider-empty">
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                <h4 class="text-danger">${message}</h4>
                <p class="text-muted mb-4">
                    Pokušajte osvežiti stranicu ili kontaktirajte podršku.
                </p>
                <button class="btn btn-outline-primary" onclick="window.location.reload()">
                    <i class="fas fa-redo me-2"></i> Osveži stranicu
                </button>
            </div>
        `;
        
        // Sakrij navigation buttons
        if (this.btnPrev) this.btnPrev.style.display = 'none';
        if (this.btnNext) this.btnNext.style.display = 'none';
        if (this.scrollbarThumb) this.scrollbarThumb.style.display = 'none';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const sliderSection = document.querySelector('.premium-slider-section');
    if (sliderSection) {
        console.log('Initializing PremiumSlider...');
        window.premiumSlider = new PremiumSlider();
    } else {
        console.log('Premium slider section not found');
    }
});

// Fallback ako je DOM već učitan
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (document.querySelector('.premium-slider-section')) {
            window.premiumSlider = new PremiumSlider();
        }
    });
} else {
    // DOM je već učitan
    if (document.querySelector('.premium-slider-section')) {
        window.premiumSlider = new PremiumSlider();
    }
}