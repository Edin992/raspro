// profile-instagram.js - Instagram-style funkcionalnosti

document.addEventListener('DOMContentLoaded', function() {
    // 1. FOLLOW/UNFOLLOW FUNKCIONALNOST
    const followBtn = document.getElementById('follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', handleFollow);
    }
    
    // 2. PORUKA FUNKCIONALNOST
    const messageBtn = document.getElementById('message-btn');
    if (messageBtn) {
        messageBtn.addEventListener('click', handleMessage);
    }
    
    // 3. SHARE FUNKCIONALNOST
    const shareBtn = document.getElementById('share-btn');
    if (shareBtn) {
        shareBtn.addEventListener('click', handleShare);
    }
    
    // 4. HOVER EFEKTI ZA OGLASE
    initAdCardEffects();
    
    // 5. STATS CLICK HANDLER
    initStatsClickHandlers();
});

// FOLLOW/UNFOLLOW HANDLER
async function handleFollow(event) {
    const btn = event.currentTarget;
    const userId = btn.getAttribute('data-user-id');
    const isFollowing = btn.getAttribute('data-is-following') === '1';
    
    // Prevent double click
    if (btn.classList.contains('loading')) return;
    
    btn.classList.add('loading');
    
    try {
        const response = await fetch(`${SITE_URL}/api/user/${isFollowing ? 'unfollow' : 'follow'}.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId,
                csrf_token: getCSRFToken()
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update button state
            const followersStat = document.getElementById('followers-stat');
            const followersCount = parseInt(followersStat.querySelector('.stat-number').textContent);
            
            if (isFollowing) {
                // Unfollow
                btn.classList.remove('following');
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Zaprati';
                btn.setAttribute('data-is-following', '0');
                
                // Update counter
                followersStat.querySelector('.stat-number').textContent = followersCount - 1;
                
                // Show notification
                showNotification('Više ne pratite ovog korisnika', 'info');
            } else {
                // Follow
                btn.classList.add('following');
                btn.innerHTML = '<i class="fas fa-check"></i> Prati se';
                btn.setAttribute('data-is-following', '1');
                
                // Update counter
                followersStat.querySelector('.stat-number').textContent = followersCount + 1;
                
                // Show notification
                showNotification('Sada pratite ovog korisnika', 'success');
            }
        } else {
            showNotification(result.error || 'Došlo je do greške', 'error');
        }
    } catch (error) {
        console.error('Follow error:', error);
        showNotification('Došlo je do greške pri komunikaciji sa serverom', 'error');
    } finally {
        btn.classList.remove('loading');
    }
}

// MESSAGE HANDLER
function handleMessage(event) {
    const btn = event.currentTarget;
    const userId = btn.getAttribute('data-user-id');
    
    // Redirect to messages with this user
    window.location.href = `${SITE_URL}/?page=user/messages&to=${userId}`;
}

// SHARE HANDLER
function handleShare() {
    const profileUrl = window.location.href;
    
    // Create share modal or use Web Share API if available
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: profileUrl
        });
    } else {
        // Fallback to custom modal
        showShareModal(profileUrl);
    }
}

// SHARE MODAL
function showShareModal(url) {
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade" id="shareModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Podeli profil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="${url}" id="shareUrl" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyShareUrl()">
                                Kopiraj
                            </button>
                        </div>
                        <div class="social-share d-flex justify-content-center gap-3">
                            <button class="btn btn-outline-primary" onclick="shareToFacebook('${url}')">
                                <i class="fab fa-facebook"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="shareToTwitter('${url}')">
                                <i class="fab fa-twitter"></i>
                            </button>
                            <button class="btn btn-outline-success" onclick="shareToWhatsApp('${url}')">
                                <i class="fab fa-whatsapp"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHTML;
    document.body.appendChild(modalContainer);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('shareModal'));
    modal.show();
    
    // Remove modal on hide
    document.getElementById('shareModal').addEventListener('hidden.bs.modal', function() {
        modalContainer.remove();
    });
}

// COPY URL FUNCTION
function copyShareUrl() {
    const urlInput = document.getElementById('shareUrl');
    urlInput.select();
    document.execCommand('copy');
    
    // Show feedback
    showNotification('URL kopiran u clipboard', 'success');
}

// SOCIAL SHARE FUNCTIONS
function shareToFacebook(url) {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
}

function shareToTwitter(url) {
    const text = `Pogledaj profil na Rasprodaja.rs`;
    window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`, '_blank');
}

function shareToWhatsApp(url) {
    const text = `Pogledaj profil na Rasprodaja.rs: ${url}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
}

// AD CARD EFFECTS
function initAdCardEffects() {
    const adCards = document.querySelectorAll('.ad-card');
    
    adCards.forEach(card => {
        // Hover effect
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
            this.style.transform = '';
        });
        
        // Click animation
        card.addEventListener('click', function(e) {
            if (!this.href) return;
            
            // Add click effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
        });
    });
}

// STATS CLICK HANDLERS
function initStatsClickHandlers() {
    const postsStat = document.getElementById('posts-stat');
    const followersStat = document.getElementById('followers-stat');
    const followingStat = document.getElementById('following-stat');
    
    if (postsStat) {
        postsStat.addEventListener('click', () => {
            // Scroll to ads grid
            document.querySelector('.ads-grid-container').scrollIntoView({ 
                behavior: 'smooth' 
            });
        });
    }
    
    if (followersStat) {
        followersStat.addEventListener('click', () => {
            showFollowersModal();
        });
    }
    
    if (followingStat) {
        followingStat.addEventListener('click', () => {
            showFollowingModal();
        });
    }
}

// FOLLOWERS/FOLLOWING MODALS
async function showFollowersModal() {
    const userId = document.querySelector('[data-user-id]')?.getAttribute('data-user-id');
    if (!userId) return;
    
    try {
        const response = await fetch(`${SITE_URL}/api/user/followers.php?user_id=${userId}`);
        const followers = await response.json();
        
        // Create and show modal with followers list
        createUsersModal('Pratioci', followers);
    } catch (error) {
        console.error('Error loading followers:', error);
    }
}

async function showFollowingModal() {
    const userId = document.querySelector('[data-user-id]')?.getAttribute('data-user-id');
    if (!userId) return;
    
    try {
        const response = await fetch(`${SITE_URL}/api/user/following.php?user_id=${userId}`);
        const following = await response.json();
        
        // Create and show modal with following list
        createUsersModal('Prati', following);
    } catch (error) {
        console.error('Error loading following:', error);
    }
}

// UTILITY FUNCTIONS
function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// CREATE USERS MODAL
function createUsersModal(title, users) {
    // Implementation for showing list of users in modal
    // This would create a Bootstrap modal with the list
    console.log(`Show ${title}:`, users);
    // You would implement this based on your modal system
}