/**
 * profile.js - Kompletan JavaScript za profil stranicu
 * Spaja profile.js i profile-public.js u jedan fajl
 */

// ============================================
// GLAVNA INICIJALIZACIJA
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initProfile();
});

function initProfile() {
    // Inicijalizacija formi (iz profile.js)
    initEditProfileForm();
    initChangePasswordForm();
    initAvatarUpload();
    initSettingsModals();
    
    // Inicijalizacija public profil funkcionalnosti (iz profile-public.js)
    initProfileTabs();
    initFollowSystem();
    initMessageSystem();
    initShareFeatures();
    initUIInteractions();
    initLoadMore();
    
    // Inicijalizacija tooltip-ova
    initTooltips();
    
    // Inicijalizacija keyboard shortcuts
    initEventListeners();
}

// ============================================
// EDIT PROFILE FORM (iz profile.js)
// ============================================
function initEditProfileForm() {
    const form = document.getElementById('editProfileForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) {
            console.error('Submit dugme nije pronađeno');
            return;
        }
        
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Čuvanje...';
            
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('success', 'Uspešno sačuvano!', 'Profil je uspešno ažuriran.');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('error', 'Greška!', result.message || 'Došlo je do greške.');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Greška!', 'Došlo je do greške prilikom slanja.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}


// ============================================
// CHANGE PASSWORD FORM (iz profile.js)
// ============================================
function initChangePasswordForm() {
    const form = document.getElementById('changePasswordForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Dohvati CSRF token IZ FORME (ne iz globalnog)
        const csrfTokenInput = form.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';
        
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        console.log('CSRF Token present:', csrfToken ? 'YES' : 'NO');
        
        // Validacije
        if (!currentPassword) {
            showToast('error', 'Greška!', 'Unesite trenutnu lozinku.');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showToast('error', 'Greška!', 'Lozinke se ne poklapaju.');
            return;
        }
        
        if (newPassword.length < 8) {
            showToast('error', 'Greška!', 'Lozinka mora imati najmanje 8 karaktera.');
            return;
        }
        
        // Pronađi dugme
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) {
            console.error('Submit dugme nije pronađeno');
            showToast('error', 'Greška!', 'Tehnička greška. Pokušajte ponovo.');
            return;
        }
        
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Menjam...';
            
            // PRAVILAN NAČIN - šalji FormData (ne JSON)
            const formData = new FormData(form);
            
            const response = await fetch('/api/user/change-password.php', {
                method: 'POST',
                body: formData
                // NEMA headers - browser sam postavlja
            });
            
            console.log('Response status:', response.status);
            
            const result = await response.json();
            console.log('Server response:', result);
            
            if (result.success) {
                showToast('success', 'Uspešno!', 'Lozinka je uspešno promenjena.');
                form.reset();
                const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                if (modal) modal.hide();
            } else {
                showToast('error', 'Greška!', result.message || 'Došlo je do greške.');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Greška!', error.message || 'Došlo je do greške prilikom slanja.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// ============================================
// AVATAR UPLOAD (iz profile.js)
// ============================================
function initAvatarUpload() {
    const avatarBtn = document.querySelector('[data-bs-target="#avatarModal"]');
    if (!avatarBtn) return;
    
    if (!document.getElementById('avatarModal')) {
        let csrfToken = '';
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) csrfToken = csrfInput.value;
        
        const modalHTML = `
        <div class="modal fade" id="avatarModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-camera me-2"></i> Promeni sliku profila</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <img src="${document.getElementById('profile-avatar').src}" 
                                 id="avatar-preview" class="rounded-circle mb-3"
                                 width="150" height="150" style="object-fit: cover;">
                        </div>
                        <form id="avatarUploadForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="${csrfToken}">
                            <div class="mb-3">
                                <input type="file" class="form-control" id="avatar_file" 
                                       name="avatar" accept="image/*">
                                <div class="form-text">Max 5MB. Dozvoljeni formati: JPG, PNG, GIF.</div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-danger" id="removeAvatarBtn">
                                    <i class="fas fa-trash me-2"></i> Ukloni sliku
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i> Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>`;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        initAvatarUploadForm();
    }
}

function initAvatarUploadForm() {
    const form = document.getElementById('avatarUploadForm');
    if (!form) return;
    
    const fileInput = document.getElementById('avatar_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const preview = document.getElementById('avatar-preview');
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    const removeBtn = document.getElementById('removeAvatarBtn');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => removeAvatar());
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('avatar_file');
        if (!fileInput || !fileInput.files.length) {
            showToast('error', 'Greška!', 'Izaberite sliku.');
            return;
        }
        
        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showToast('error', 'Greška!', 'Slika je prevelika. Maksimalna veličina je 5MB.');
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            showToast('error', 'Greška!', 'Dozvoljeni formati su: JPG, PNG, GIF.');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Upload...';
            
            const formData = new FormData(form);
            const response = await fetch('/api/user/upload-avatar.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                showToast('success', 'Uspešno!', result.message || 'Slika profila je ažurirana.');
                
                const avatarImg = document.getElementById('profile-avatar');
                if (avatarImg && result.avatar_url) {
                    avatarImg.src = result.avatar_url + '?t=' + new Date().getTime();
                }
                
                const previewImg = document.getElementById('avatar-preview');
                if (previewImg && result.avatar_url) {
                    previewImg.src = result.avatar_url + '?t=' + new Date().getTime();
                }
                
                const modal = bootstrap.Modal.getInstance(document.getElementById('avatarModal'));
                if (modal) modal.hide();
                form.reset();
            } else {
                showToast('error', 'Greška!', result.message || 'Došlo je do greške.');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Mrežna greška!', 'Došlo je do greške u komunikaciji sa serverom.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

async function removeAvatar() {
    if (!confirm('Da li ste sigurni da želite da uklonite sliku profila?')) return;
    
    try {
        let csrfToken = '';
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) csrfToken = csrfInput.value;
        
        const response = await fetch('/api/user/remove-avatar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrfToken })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Uspešno!', 'Slika profila je uklonjena.');
            
            const avatarImg = document.getElementById('profile-avatar');
            if (avatarImg && result.default_avatar) {
                avatarImg.src = result.default_avatar + '?t=' + new Date().getTime();
            }
            
            const previewImg = document.getElementById('avatar-preview');
            if (previewImg && result.default_avatar) {
                previewImg.src = result.default_avatar + '?t=' + new Date().getTime();
            }
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('avatarModal'));
            if (modal) modal.hide();
        } else {
            showToast('error', 'Greška!', result.message || 'Došlo je do greške.');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Greška!', 'Došlo je do greške.');
    }
}

// ============================================
// PROFILE TABS (iz profile-public.js)
// ============================================
function initProfileTabs() {
    const tabButtons = document.querySelectorAll('.profile-tab');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(tab => tab.classList.remove('active'));
            this.classList.add('active');
            
            const tabId = this.getAttribute('data-bs-target')?.replace('#', '');
            if (tabId) {
                document.dispatchEvent(new CustomEvent('profileTabChanged', { detail: { tabId } }));
            }
        });
    });
    
    const hash = window.location.hash.substring(1);
    if (hash) {
        setTimeout(() => {
            const tabButton = document.querySelector(`[data-bs-target="#${hash}"]`);
            if (tabButton) tabButton.click();
        }, 100);
    }
}

// ============================================
// FOLLOW SYSTEM (iz profile-public.js)
// ============================================
function initFollowSystem() {
    const followButton = document.getElementById('followButton');
    if (!followButton) return;
    
    followButton.addEventListener('click', handleFollowAction);
}

async function handleFollowAction() {
    const button = this;
    const userId = button.dataset.userId;
    const isFollowing = button.dataset.isFollowing === '1';
    const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
    
    button.disabled = true;
    const originalHtml = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    
    try {
        const response = await fetch(`/api/user/${isFollowing ? 'unfollow' : 'follow'}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ csrf_token: csrfToken, user_id: parseInt(userId) })
        });
        
        const result = await response.json();
        
        if (result.success) {
            updateFollowUI(!isFollowing, button);
            showToast('success', 'Uspešno!', `Sada ${!isFollowing ? 'pratite' : 'ne pratite'} ovog korisnika.`);
        } else {
            showToast('error', 'Greška!', result.message || 'Došlo je do greške.');
        }
    } catch (error) {
        console.error('Follow error:', error);
        showToast('error', 'Greška!', 'Došlo je do greške u komunikaciji sa serverom.');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

function updateFollowUI(newIsFollowing, button) {
    button.dataset.isFollowing = newIsFollowing ? '1' : '0';
    button.classList.remove('btn-primary', 'btn-secondary');
    button.classList.add(newIsFollowing ? 'btn-secondary' : 'btn-primary');
    
    const icon = newIsFollowing ? 'user-check' : 'user-plus';
    const text = newIsFollowing ? 'Pratim' : 'Prati';
    button.innerHTML = `<i class="fas fa-${icon} me-1"></i>${text}`;
    
    const change = newIsFollowing ? 1 : -1;
    document.querySelectorAll('.follower-count').forEach(el => {
        const currentCount = parseInt(el.textContent) || 0;
        el.textContent = Math.max(0, currentCount + change);
        el.classList.add('follower-updated');
        setTimeout(() => el.classList.remove('follower-updated'), 1000);
    });
}

// ============================================
// MESSAGE SYSTEM (iz profile-public.js)
// ============================================
function initMessageSystem() {
    const messageForm = document.getElementById('messageForm');
    if (!messageForm) return;
    
    messageForm.addEventListener('submit', handleMessageSubmit);
    
    const messageTextarea = messageForm.querySelector('textarea[name="message"]');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            const remaining = 1000 - this.value.length;
            const counter = document.getElementById('messageCounter') || createMessageCounter(this);
            counter.textContent = `${remaining} karaktera preostalo`;
            counter.className = `form-text ${remaining < 100 ? 'text-warning' : 'text-muted'}`;
        });
    }
}

function createMessageCounter(textarea) {
    const counter = document.createElement('div');
    counter.id = 'messageCounter';
    counter.className = 'form-text text-muted';
    textarea.parentNode.appendChild(counter);
    return counter;
}

async function handleMessageSubmit(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    const message = formData.get('message');
    if (!message || message.trim().length < 5) {
        showToast('error', 'Greška!', 'Poruka mora imati najmanje 5 karaktera.');
        return;
    }
    
    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Slanje...';
        
        const response = await fetch('/api/chat/send.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            showToast('success', 'Uspešno!', 'Poruka je poslata.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
            if (modal) modal.hide();
            form.reset();
        } else {
            showToast('error', 'Greška!', result.message || 'Došlo je do greške pri slanju poruke.');
        }
    } catch (error) {
        console.error('Message send error:', error);
        showToast('error', 'Greška!', 'Došlo je do greške u komunikaciji sa serverom.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// ============================================
// SHARE FEATURES (iz profile-public.js)
// ============================================
function initShareFeatures() {
    const copyBtn = document.querySelector('[onclick*="copyProfileLink"]');
    if (copyBtn) {
        copyBtn.removeAttribute('onclick');
        copyBtn.addEventListener('click', copyProfileLink);
    }
    
    const shareButtons = document.querySelectorAll('[onclick*="shareProfile"]');
    shareButtons.forEach(btn => {
        const match = btn.getAttribute('onclick')?.match(/shareProfile\('(\w+)'\)/);
        if (match) {
            const platform = match[1];
            btn.removeAttribute('onclick');
            btn.addEventListener('click', () => shareProfile(platform));
        }
    });
}

function copyProfileLink() {
    const linkInput = document.getElementById('profileLink');
    if (!linkInput) return;
    
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    try {
        navigator.clipboard.writeText(linkInput.value);
        showToast('success', 'Uspešno!', 'Link profila je kopiran.');
    } catch (err) {
        document.execCommand('copy');
        showToast('success', 'Uspešno!', 'Link profila je kopiran.');
    }
}

function shareProfile(platform) {
    const url = encodeURIComponent(window.location.href);
    const title = encodeURIComponent(document.title);
    const text = encodeURIComponent(`Pogledaj profil na Rasprodaja.rs: ${document.title}`);
    
    const shareUrls = {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`,
        twitter: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
        pinterest: `https://pinterest.com/pin/create/button/?url=${url}&description=${text}`,
        whatsapp: `https://wa.me/?text=${text}%20${url}`,
        telegram: `https://t.me/share/url?url=${url}&text=${text}`
    };
    
    if (shareUrls[platform]) {
        window.open(shareUrls[platform], '_blank', 'width=600,height=400');
    }
}

// ============================================
// UI INTERACTIONS (iz profile-public.js)
// ============================================
function initUIInteractions() {
    document.querySelectorAll('.ad-grid-link').forEach(link => {
        link.addEventListener('mouseenter', function() {
            const card = this.querySelector('.card');
            if (card) {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            const card = this.querySelector('.card');
            if (card) {
                card.style.transform = '';
                card.style.boxShadow = '';
            }
        });
    });
    
    const avatar = document.getElementById('profile-avatar');
    if (avatar) {
        avatar.addEventListener('mouseenter', () => avatar.style.transform = 'scale(1.05)');
        avatar.addEventListener('mouseleave', () => avatar.style.transform = '');
    }
    
    const reportBtn = document.getElementById('reportButton');
    if (reportBtn) {
        reportBtn.addEventListener('click', () => showToast('info', 'Prijava profila', 'Ova funkcionalnost će biti dostupna uskoro.'));
    }
}

// ============================================
// LOAD MORE (iz profile-public.js)
// ============================================
function initLoadMore() {
    const loadMoreBtn = document.getElementById('loadMoreAds');
    if (!loadMoreBtn) return;
    
    let currentPage = 1;
    const userId = loadMoreBtn.dataset.userId;
    const adsContainer = document.querySelector('#adsTab .row');
    
    loadMoreBtn.addEventListener('click', async function() {
        currentPage++;
        
        const originalText = this.innerHTML;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Učitavam...';
        
        try {
            const response = await fetch(`/api/ads/list.php?user_id=${userId}&page=${currentPage}&limit=12`);
            const result = await response.json();
            
            if (result.success && result.ads && result.ads.length > 0) {
                result.ads.forEach(ad => {
                    adsContainer.insertAdjacentHTML('beforeend', createAdCardHTML(ad));
                });
                
                if (result.ads.length < 12) this.style.display = 'none';
                showToast('success', 'Uspešno!', 'Učitano još oglasa.');
            } else {
                this.style.display = 'none';
                showToast('info', 'Info', 'Nema više oglasa za učitavanje.');
            }
        } catch (error) {
            console.error('Load more error:', error);
            showToast('error', 'Greška!', 'Došlo je do greške pri učitavanju.');
        } finally {
            this.disabled = false;
            this.innerHTML = originalText;
        }
    });
}

function createAdCardHTML(ad) {
    return `
    <div class="col">
        <a href="?page=ad-detail&id=${ad.id}" class="text-decoration-none text-dark ad-grid-link">
            <div class="card ad-grid-item h-100 ${ad.is_premium ? 'premium' : ''}">
                ${ad.thumbnail ? 
                    `<img src="${ad.thumbnail}" class="card-img-top" alt="${ad.title}" style="height: 200px; object-fit: cover;">` :
                    `<div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>`
                }
                <div class="card-body">
                    <h6 class="card-title text-truncate" title="${ad.title}">${ad.title}</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="text-primary">${new Intl.NumberFormat('sr-RS').format(ad.price)} RSD</strong>
                        <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${ad.city}</small>
                    </div>
                    ${ad.category_name ? `<div class="mt-2"><small class="badge bg-light text-dark">${ad.category_name}</small></div>` : ''}
                </div>
            </div>
        </a>
    </div>`;
}

// ============================================
// SETTINGS MODALS (iz profile.js)
// ============================================
function initSettingsModals() {
    const privacyBtn = document.querySelector('[data-bs-target="#privacySettingsModal"]');
    if (privacyBtn && !document.getElementById('privacySettingsModal')) createPrivacySettingsModal();
    
    const notificationBtn = document.querySelector('[data-bs-target="#notificationSettingsModal"]');
    if (notificationBtn && !document.getElementById('notificationSettingsModal')) createNotificationSettingsModal();
}

function createPrivacySettingsModal() {
    let csrfToken = '';
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) csrfToken = csrfInput.value;
    
    const modalHTML = `
    <div class="modal fade" id="privacySettingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i> Podešavanja privatnosti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="privacySettingsForm">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <div class="mb-3">
                            <label class="form-label">Ko može da vidi vaš profil?</label>
                            <select class="form-select" name="profile_visibility">
                                <option value="public">Svi korisnici</option>
                                <option value="registered">Samo registrovani korisnici</option>
                                <option value="none">Samo ja</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="show_email_setting" name="show_email" value="1" checked>
                            <label class="form-check-label" for="show_email_setting">Prikaži moj email u profilu</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="show_phone_setting" name="show_phone" value="1" checked>
                            <label class="form-check-label" for="show_phone_setting">Prikaži moj telefon u profilu</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ko može da vam šalje poruke?</label>
                            <select class="form-select" name="message_privacy">
                                <option value="everyone">Svi korisnici</option>
                                <option value="registered">Samo registrovani korisnici</option>
                                <option value="none">Niko</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="button" class="btn btn-primary" onclick="savePrivacySettings()"><i class="fas fa-save me-2"></i> Sačuvaj</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function createNotificationSettingsModal() {
    let csrfToken = '';
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) csrfToken = csrfInput.value;
    
    const modalHTML = `
    <div class="modal fade" id="notificationSettingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bell me-2"></i> Podešavanja notifikacija</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="notificationSettingsForm">
                        <input type="hidden" name="csrf_token" value="${csrfToken}">
                        <div class="mb-3">
                            <h6>Email notifikacije</h6>
                            <div class="form-check mb-2"><input type="checkbox" class="form-check-input" id="email_messages" name="email_messages" value="1" checked><label class="form-check-label" for="email_messages">Novi poruke</label></div>
                            <div class="form-check mb-2"><input type="checkbox" class="form-check-input" id="email_replies" name="email_replies" value="1" checked><label class="form-check-label" for="email_replies">Odgovori na oglase</label></div>
                            <div class="form-check"><input type="checkbox" class="form-check-input" id="email_newsletter" name="email_newsletter" value="1"><label class="form-check-label" for="email_newsletter">Newsletter i ponude</label></div>
                        </div>
                        <div class="mb-3">
                            <h6>Push notifikacije</h6>
                            <div class="form-check mb-2"><input type="checkbox" class="form-check-input" id="push_messages" name="push_messages" value="1" checked><label class="form-check-label" for="push_messages">Novi poruke</label></div>
                            <div class="form-check"><input type="checkbox" class="form-check-input" id="push_sales" name="push_sales" value="1"><label class="form-check-label" for="push_sales">Promocije i sniženja</label></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Odustani</button>
                    <button type="button" class="btn btn-primary" onclick="saveNotificationSettings()"><i class="fas fa-save me-2"></i> Sačuvaj</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

window.savePrivacySettings = function() {
    showToast('info', 'Info', 'Funkcija će biti implementirana u narednom update-u.');
};

window.saveNotificationSettings = function() {
    showToast('info', 'Info', 'Funkcija će biti implementirana u narednom update-u.');
};

// ============================================
// TOOLTIPS & KEYBOARD (iz profile.js)
// ============================================
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

function initEventListeners() {
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's' && document.activeElement.tagName !== 'INPUT') {
            e.preventDefault();
            const editBtn = document.querySelector('#editProfileForm button[type="submit"]');
            if (editBtn) editBtn.click();
        }
    });
}

// ============================================
// UTILITY FUNCTIONS
// ============================================
function showInputError(input, message) {
    clearInputError(input);
    input.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    input.parentNode.appendChild(errorDiv);
}

function clearInputError(input) {
    input.classList.remove('is-invalid');
    const errorDiv = input.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) errorDiv.remove();
}

function showToast(type, title, message) {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
    <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${type} text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${message}</div>
    </div>`;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

window.showToast = showToast;
window.copyProfileLink = copyProfileLink;
window.shareProfile = shareProfile;
window.removeAvatar = removeAvatar;