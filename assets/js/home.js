/**
 * assets/js/home.js - JavaScript za početnu stranu
 * 🔥 ISPRAVLJENO - DODATI CREDENTIALS U FETCH POZIVE
 */

console.log('=== HOME.JS LOADED ===');

// ============================================
// FALLBACK ZA SITE_CONFIG
// ============================================
if (typeof window.SITE_CONFIG === 'undefined') {
    window.SITE_CONFIG = {
        url: window.location.origin,
        userId: null,
        csrfToken: null
    };
    console.log('⚠️ SITE_CONFIG was not defined, using fallback');
}

console.log('SITE_CONFIG:', window.SITE_CONFIG);

// Helper funkcije koje će koristiti createAdCard
const HomePageHelpers = {
    formatPrice: function(price) {
        if (!price) return '0 RSD';
        return new Intl.NumberFormat('sr-RS', {
            style: 'currency',
            currency: 'RSD',
            minimumFractionDigits: 0
        }).format(price).replace('RSD', 'din');
    },
    
    timeAgo: function(dateString) {
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
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// GLOBALNA FUNKCIJA za kreiranje ad kartice
if (typeof window.createAdCard === 'undefined') {
    window.createAdCard = function(ad, isPremium = false) {
        const price = ad.price ? 
            new Intl.NumberFormat('sr-RS').format(ad.price) : 
            'Po dogovoru';
        
        const timeAgo = ad.time_ago || 
            (ad.created_at ? new Date(ad.created_at).toLocaleDateString('sr-RS') : '');
        
        const adUrl = `/ad/${ad.id}${ad.slug ? '/' + ad.slug : ''}`;
        
        return `
            <div class="col-md-3 col-sm-6 mb-4">
                <a href="${adUrl}" class="text-decoration-none card-link-wrapper">
                    <div class="card h-100 shadow-sm border-0 position-relative overflow-hidden">
                        ${isPremium ? 
                            '<div class="premium-badge"><span class="badge bg-warning"><i class="fas fa-crown"></i> Premium</span></div>' : 
                            ''}
                        
                        <div class="card-img-top" style="height: 180px; overflow: hidden;">
                            <img src="${ad.image || 'assets/images/no-image.png'}" 
                                 class="img-fluid w-100 h-100" 
                                 style="object-fit: cover; transition: transform 0.3s;"
                                 alt="${ad.title}"
                                 onerror="this.src='assets/images/no-image.png'"
                                 onmouseover="this.style.transform='scale(1.05)'"
                                 onmouseout="this.style.transform='scale(1)'">
                        </div>
                        
                        <div class="card-body d-flex flex-column p-3">
                            <h6 class="card-title fw-bold mb-2 text-dark" style="min-height: 48px;">
                                ${ad.title}
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="h5 text-primary mb-0 fw-bold">${price} ${ad.currency}</span>
                                ${ad.price_negotiable ? 
                                    '<small class="text-muted">(po dogovoru)</small>' : 
                                    ''}
                            </div>
                            
                            <div class="d-flex justify-content-between text-muted small mb-3">
                                <span><i class="fas fa-map-marker-alt me-1"></i>${ad.city || 'Nije naveden'}</span>
                                <span><i class="far fa-clock me-1"></i>${timeAgo}</span>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2" style="border-top: 1px solid #eee;">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>${ad.seller || 'Korisnik'}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>${ad.views || 0}
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-hover-overlay"></div>
                    </div>
                </a>
            </div>
        `;
    };
}

class HomePage {
    constructor() {
        console.log('HomePage constructor called');
        this.SITE_CONFIG = window.SITE_CONFIG || { url: window.location.origin };
        this.init();
    }
    
    init() {
        console.log('Home page initialized');
        this.loadData();
        this.initSearchForm();
        this.initEventListeners();
    }
    
    // ============================================
    // 🔥 SVI FETCH POZIVI SADA IMAJU credentials: 'include'
    // ============================================
    
    async loadData() {
        console.log('Loading home page data...');
        try {
            await Promise.all([
                this.loadPremiumAds(),
                this.loadNewAds(),
                this.loadStats(),
                this.loadCategories(),
                this.loadCities()
            ]);
            
            console.log('✅ All home page data loaded');
        } catch (error) {
            console.error('❌ Error loading home page data:', error);
        }
    }
    
    async loadPremiumAds() {
        console.log('Loading premium ads...');
        try {
            const apiUrl = `${this.SITE_CONFIG.url}/api/ads/premium.php?limit=12`;
            console.log('API URL:', apiUrl);
            
            // 🔥 DODATO credentials: 'include'
            const response = await fetch(apiUrl, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('Premium API response:', data);
            
            const container = document.getElementById('premium-ads');
            if (!container) {
                console.warn('Premium ads container not found');
                return;
            }
            
            container.innerHTML = '';
            
            if (data.success && data.ads && data.ads.length > 0) {
                console.log(`Found ${data.ads.length} premium ads`);
                data.ads.forEach(ad => {
                    container.innerHTML += window.createAdCard(ad, true);
                });
            } else {
                console.log('No premium ads found');
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Trenutno nema premium oglasa.
                            <a href="/create-ad" class="alert-link ms-1">
                                Postavite svoj oglas i istaknite ga!
                            </a>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('❌ Greška pri učitavanju premium oglasa:', error);
            const container = document.getElementById('premium-ads');
            if (container) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Nije moguće učitati premium oglase. Pokušajte kasnije.
                        </div>
                    </div>
                `;
            }
        }
    }
    
    async loadNewAds() {
        console.log('Loading all ads...');
        try {
            const apiUrl = `${this.SITE_CONFIG.url}/api/ads/new.php?limit=12`;
            console.log('API URL:', apiUrl);
            
            // 🔥 DODATO credentials: 'include'
            const response = await fetch(apiUrl, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            console.log('Response status:', response.status);
            
            const data = await response.json();
            console.log('All ads API response:', data);
            
            const container = document.getElementById('new-ads');
            if (!container) {
                console.warn('New ads container not found');
                return;
            }
            
            container.innerHTML = '';
            
            if (data.success && data.ads && data.ads.length > 0) {
                console.log(`Found ${data.ads.length} ads`);
                data.ads.forEach(ad => {
                    container.innerHTML += window.createAdCard(ad, false);
                });
            } else {
                console.log('No ads found');
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Trenutno nema oglasa na sajtu.
                            <a href="/create-ad" class="alert-link ms-1">
                                Postavite prvi oglas!
                            </a>
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('❌ Greška pri učitavanju oglasa:', error);
            this.showError('new-ads', 'Greška pri učitavanju oglasa');
        }
    }
    
    async loadStats() {
        console.log('Loading stats...');
        try {
            // 🔥 DODATO credentials: 'include'
            const response = await fetch(`${this.SITE_CONFIG.url}/api/stats/home.php`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            console.log('Stats response:', data);
            
            if (data.success) {
                const totalAds = document.getElementById('total-ads');
                const dailyAds = document.getElementById('daily-ads');
                const activeUsers = document.getElementById('active-users');
                
                if (totalAds && data.stats?.total_ads) {
                    totalAds.textContent = this.formatNumber(data.stats.total_ads) + '+';
                }
                if (dailyAds && data.stats?.daily_ads) {
                    dailyAds.textContent = this.formatNumber(data.stats.daily_ads) + '+';
                }
                if (activeUsers && data.stats?.active_users) {
                    activeUsers.textContent = this.formatNumber(data.stats.active_users) + '+';
                }
            }
        } catch (error) {
            console.error('❌ Error loading stats:', error);
        }
    }
    
    async loadCategories() {
        console.log('Loading categories...');
        try {
            // 🔥 DODATO credentials: 'include'
            const response = await fetch(`${this.SITE_CONFIG.url}/api/categories/list.php`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            console.log('Categories response:', data);
            
            const select = document.querySelector('select[name="category"]');
            if (select && data.success && data.categories) {
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                data.categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('❌ Error loading categories:', error);
        }
    }
    
    async loadCities() {
        console.log('Loading cities...');
        try {
            // 🔥 DODATO credentials: 'include'
            const response = await fetch(`${this.SITE_CONFIG.url}/api/cities/popular.php`, {
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            console.log('Cities response:', data);
            
            const select = document.querySelector('select[name="city"]');
            if (select && data.success && data.cities) {
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                data.cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.name;
                    option.textContent = `${city.name} (${city.count})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('❌ Error loading cities:', error);
        }
    }
    
    initSearchForm() {
        const searchForm = document.querySelector('.hero-section form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                const submitBtn = searchForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Pretražujem...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 1000);
            });
        }
    }
    
    initEventListeners() {
        document.querySelectorAll('.badge.bg-light').forEach(badge => {
            badge.addEventListener('click', (e) => {
                e.preventDefault();
                const searchText = badge.textContent.trim();
                const searchInput = document.querySelector('input[name="q"]');
                
                if (searchInput) {
                    searchInput.value = searchText;
                    searchInput.focus();
                }
            });
        });
    }
    
    formatPrice(price) {
        return HomePageHelpers.formatPrice(price);
    }
    
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }
    
    timeAgo(dateString) {
        return HomePageHelpers.timeAgo(dateString);
    }
    
    escapeHtml(text) {
        return HomePageHelpers.escapeHtml(text);
    }
    
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <p class="text-muted">${message}</p>
                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                        Pokušaj ponovo
                    </button>
                </div>
            `;
        }
    }
}

// Inicijalizacija
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - initializing HomePage...');
    try {
        window.homePage = new HomePage();
        console.log('✅ HomePage initialized successfully');
    } catch (error) {
        console.error('❌ Error initializing HomePage:', error);
    }
});