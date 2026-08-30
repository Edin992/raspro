// ==================== GLOBALNE FUNKCIJE (dostupne svuda) ====================

/**
 * Prikaz notifikacije korisniku
 */
function showNotification(type, message) {
    console.log('showNotification called:', type, message);
    
    // Proveri da li Bootstrap alert već postoji
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '1060';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    alertDiv.style.animation = 'slideDown 0.3s ease-out';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (document.body.contains(alertDiv)) {
            alertDiv.remove();
        }
    }, 5000);
}

// Dodaj CSS animaciju ako ne postoji
if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideDown {
            from {
                transform: translate(-50%, -100%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Inicijalizacija sistema za slanje poruka
 */
function initMessageSystem() {
    console.log('initMessageSystem called');
    
    const sendMessageBtn = document.getElementById('send-message-btn');
    const sendMessageSubmit = document.getElementById('send-message-submit');
    
    console.log('sendMessageBtn:', sendMessageBtn);
    console.log('sendMessageSubmit:', sendMessageSubmit);
    
    if (sendMessageBtn) {
        sendMessageBtn.addEventListener('click', function() {
            console.log('Send message button clicked');
            const modalElement = document.getElementById('messageModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                console.error('Modal element not found!');
                showNotification('danger', 'Greška: Modal nije pronađen');
            }
        });
    } else {
        console.warn('Send message button not found - možda korisnik nije prijavljen ili je svoj oglas');
    }
    
    if (sendMessageSubmit) {
        sendMessageSubmit.addEventListener('click', async function() {
            console.log('Send message submit clicked');
            
            const form = document.getElementById('send-message-form');
            console.log('Form:', form);
            
            if (!form) {
                console.error('Form not found!');
                showNotification('danger', 'Greška: Forma nije pronađena');
                return;
            }
            
            const formData = new FormData(form);
            
            // Ispiši sve podatke iz forme za debug
            console.log('Form data:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            const message = formData.get('message')?.trim();
            console.log('Message:', message);
            
            // Validacija
            if (!message) {
                showNotification('warning', 'Unesite poruku');
                return;
            }
            
            if (message.length > 1000) {
                showNotification('warning', 'Poruka je predugačka (max 1000 karaktera)');
                return;
            }
            
            // Prikaži loading
            const originalHtml = sendMessageSubmit.innerHTML;
            sendMessageSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Slanje...';
            sendMessageSubmit.disabled = true;
            
            try {
                console.log('📤 Slanje POST zahteva na /api/chat/send.php');
                console.log('FormData entries:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                const response = await fetch('/api/chat/send.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Prvo proveri da li je response OK
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server returned error:', response.status, text);
                    showNotification('danger', `Server greška: ${response.status} - ${text.substring(0, 100)}`);
                    return;
                }
                
                // Pokušaj da parsiraš JSON
                const text = await response.text();
                console.log('Raw response text:', text);
                
                if (!text) {
                    console.error('Empty response from server');
                    showNotification('danger', 'Server je vratio prazan odgovor');
                    return;
                }
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Response was:', text);
                    showNotification('danger', 'Server je vratio neispravan odgovor');
                    return;
                }
                
                console.log('Parsed response:', result);
                
                if (result.success) {
                    showNotification('success', 'Poruka je uspešno poslata!');
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    form.reset();
                    
                    // Opciono: pitaj korisnika da li želi da vidi poruke
                    setTimeout(() => {
                        if (confirm('Poruka je poslata. Želite li da vidite sve poruke?')) {
                            window.location.href = '?page=messages';
                        }
                    }, 1000);
                    
                } else {
                    showNotification('danger', result.message || 'Greška pri slanju poruke');
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showNotification('danger', 'Greška pri slanju poruke: ' + error.message);
            } finally {
                // Vrati dugme u normalno stanje
                sendMessageSubmit.innerHTML = originalHtml;
                sendMessageSubmit.disabled = false;
            }
        });
    } else {
        console.warn('Send message submit button not found - možda korisnik nije prijavljen');
    }
}

/**
 * Inicijalizacija brisanja oglasa
 */


// ==================== GLAVNI KOD - Čeka da se DOM učita ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing gallery');
    
    const images = document.querySelectorAll('.thumbnail-wrapper');
    if (images.length === 0) {
        console.log('No images found, skipping gallery initialization');
        return;
    }
    
    // Elementi galerije
    const imagesWrapper = document.getElementById('images-wrapper');
    const imageSlides = document.querySelectorAll('.image-slide');
    const indicators = document.querySelectorAll('.image-indicator');
    const currentImageSpan = document.getElementById('current-image');
    const totalImagesSpan = document.getElementById('total-images');
    
    let currentImageIndex = 0;
    let isSwiping = false;
    let swipeStartX = 0;
    let swipeCurrentX = 0;
    let swipeThreshold = 50;
    
    // Inicijalizacija
    if (totalImagesSpan) {
        totalImagesSpan.textContent = images.length;
    }
    
    // Pozovi funkcije za ostale funkcionalnosti
    initMessageSystem();    // Inicijalizacija slanja poruka
    
    
    // ==================== FUNKCIJE ZA GALERIJU ====================
    
    /**
     * Postavlja trenutnu sliku
     */
    function setCurrentImage(index, animate = true) {
        // Koriguj index (loop)
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        
        // Ažuriraj globalni indeks
        currentImageIndex = index;
        
        // Pozovi animaciju
        goToImage(index, animate);
    }
    
    /**
     * Animira prelaz na sliku
     */
    function goToImage(index, animate = true) {
        // Animacija
        if (animate && imageSlides[index]) {
            imageSlides[index].classList.add('slide-animation');
            setTimeout(() => {
                imageSlides[index].classList.remove('slide-animation');
            }, 400);
        }
        
        // Ažuriraj poziciju
        if (imagesWrapper) {
            imagesWrapper.style.transform = `translateX(-${index * 100}%)`;
            imagesWrapper.style.transition = animate ? 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)' : 'none';
        }
        
        updateUI();
    }
    
    /**
     * Ažurira UI elemente (thumbnails, indikatori, brojač)
     */
    function updateUI() {
        // Ažuriraj thumbnails
        images.forEach((thumb, index) => {
            const isActive = index === currentImageIndex;
            thumb.classList.toggle('active', isActive);
            
            const indicator = thumb.querySelector('.thumbnail-active-indicator');
            if (indicator) {
                indicator.style.display = isActive ? 'block' : 'none';
            }
        });
        
        // Ažuriraj indikatore
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentImageIndex);
        });
        
        // Ažuriraj brojač
        if (currentImageSpan) {
            currentImageSpan.textContent = currentImageIndex + 1;
        }
    }
    
    /**
     * Otvara fullscreen popup za slike
     */
    function openFullscreenPopup(e) {
        // Sprečimo event bubbling ako postoji
        if (e) e.stopPropagation();
        
        // Koristimo globalni currentImageIndex 
        const clickedImageIndex = currentImageIndex;
        
        console.log("Opening popup for image index:", clickedImageIndex);
        
        // Generišemo popup HTML
        const popupHtml = `
            <div class="modal fade image-popup-modal fullscreen-popup" id="imagePopupModal" tabindex="-1">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content">
                        <div class="modal-body position-relative p-0" style="overflow: hidden;">
                            <!-- Close button -->
                            <button type="button" class="popup-close-btn" data-bs-dismiss="modal"
                                    style="position: absolute; top: 20px; right: 20px; z-index: 1000;
                                           background: rgba(0,0,0,0.5); border: none; width: 50px; 
                                           height: 50px; border-radius: 50%; color: white; font-size: 1.5rem;">
                                <i class="fas fa-times"></i>
                            </button>
                            
                            <!-- Navigation -->
                            <button class="popup-nav-btn popup-nav-prev"
                                    style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
                                           z-index: 1000; background: rgba(0,0,0,0.5); border: none; 
                                           width: 60px; height: 60px; border-radius: 50%; color: white; 
                                           font-size: 1.8rem;">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            
                            <!-- Images container za swipe -->
                            <div class="popup-images-wrapper" 
                                style="width: ${images.length * 100}%; height: 100%; display: flex; transition: transform 0.3s ease;">
                                ${Array.from(images).map((img, index) => {
                                    // Uzmi full image URL iz data atributa
                                    const fullImageUrl = img.getAttribute('data-full-image') || 
                                                         img.querySelector('img').src;
                                    return `
                                        <div class="popup-image-slide" data-index="${index}"
                                             style="flex: 0 0 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                            <img src="${fullImageUrl}" 
                                                 alt="Slika ${index + 1}"
                                                 class="popup-main-image"
                                                 style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                            
                            <button class="popup-nav-btn popup-nav-next"
                                    style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
                                           z-index: 1000; background: rgba(0,0,0,0.5); border: none; 
                                           width: 60px; height: 60px; border-radius: 50%; color: white; 
                                           font-size: 1.8rem;">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                            
                            <!-- Image counter -->
                            <div class="image-counter"
                                 style="position: absolute; bottom: 30px; left: 0; right: 0; 
                                        text-align: center; color: white; font-size: 1.2rem; z-index: 1000;">
                                <span class="popup-current">${clickedImageIndex + 1}</span> / 
                                <span class="popup-total">${images.length}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Dodaj modal
        document.body.insertAdjacentHTML('beforeend', popupHtml);
        
        // Prikaži modal
        const modalElement = document.getElementById('imagePopupModal');
        const modal = new bootstrap.Modal(modalElement);
        
        // Setup popup events
        setupPopupEvents(modal, clickedImageIndex);
        
        modal.show();
    }
    
    /**
     * Postavlja event listenere za popup
     */
    function setupPopupEvents(modal, startIndex) {
        const popupModal = document.getElementById('imagePopupModal');
        if (!popupModal) return;
        
        let popupCurrentIndex = startIndex;
        const popupWrapper = popupModal.querySelector('.popup-images-wrapper');
        const popupCurrentSpan = popupModal.querySelector('.popup-current');
        const popupTotalSpan = popupModal.querySelector('.popup-total');
        
        // Postavi početnu poziciju
        if (popupWrapper) {
            popupWrapper.style.transform = `translateX(-${popupCurrentIndex * 100}%)`;
        }
        
        if (popupTotalSpan) {
            popupTotalSpan.textContent = images.length;
        }
        
        // Navigacija u popup-u
        const prevBtn = popupModal.querySelector('.popup-nav-prev');
        const nextBtn = popupModal.querySelector('.popup-nav-next');
        const closeBtn = popupModal.querySelector('.popup-close-btn');
        
        // Funkcija za navigaciju u popup-u
        function goToPopupImage(index) {
            // Koriguj index (loop)
            if (index < 0) index = images.length - 1;
            if (index >= images.length) index = 0;
            
            popupCurrentIndex = index;
            
            if (popupWrapper) {
                popupWrapper.style.transform = `translateX(-${index * 100}%)`;
            }
            
            // Ažuriraj brojač
            if (popupCurrentSpan) {
                popupCurrentSpan.textContent = index + 1;
            }
        }
        
        // Event listener-i
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                goToPopupImage(popupCurrentIndex - 1);
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                goToPopupImage(popupCurrentIndex + 1);
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                modal.hide();
            });
        }
        
        // Swipe u popup-u
        let isPopupSwiping = false;
        let popupStartX = 0;
        let popupCurrentX = 0;
        
        popupModal.addEventListener('touchstart', function(e) {
            isPopupSwiping = true;
            popupStartX = e.touches[0].clientX;
            if (popupWrapper) {
                popupWrapper.style.transition = 'none';
            }
        });
        
        popupModal.addEventListener('touchmove', function(e) {
            if (!isPopupSwiping || !popupWrapper) return;
            
            popupCurrentX = e.touches[0].clientX;
            const diff = popupCurrentX - popupStartX;
            
            // Računamo pomeraj u procentima
            const translateX = -popupCurrentIndex * 100 + (diff / window.innerWidth * 100);
            
            popupWrapper.style.transform = `translateX(${translateX}%)`;
        });
        
        popupModal.addEventListener('touchend', function(e) {
            if (!isPopupSwiping || !popupWrapper) return;
            
            isPopupSwiping = false;
            popupWrapper.style.transition = 'transform 0.3s ease';
            
            const diff = popupCurrentX - popupStartX;
            const swipeDistance = Math.abs(diff);
            const threshold = window.innerWidth * 0.15;
            
            if (swipeDistance > threshold) {
                if (diff > 0) {
                    goToPopupImage(popupCurrentIndex - 1);
                } else {
                    goToPopupImage(popupCurrentIndex + 1);
                }
            } else {
                goToPopupImage(popupCurrentIndex);
            }
        });
        
        // Keyboard navigation u popup-u
        function handlePopupKeydown(e) {
            if (popupModal.classList.contains('show')) {
                if (e.key === 'ArrowLeft') {
                    goToPopupImage(popupCurrentIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    goToPopupImage(popupCurrentIndex + 1);
                } else if (e.key === 'Escape') {
                    modal.hide();
                }
            }
        }
        
        // Dodaj keyboard listener
        document.addEventListener('keydown', handlePopupKeydown);
        
        // Zatvaranje modala
        popupModal.addEventListener('hidden.bs.modal', function() {
            // Ažuriraj glavnu galeriju sa trenutnim indexom iz popup-a
            currentImageIndex = popupCurrentIndex;
            setCurrentImage(popupCurrentIndex, false);
            
            // Ukloni keyboard listener
            document.removeEventListener('keydown', handlePopupKeydown);
            
            // Ukloni modal iz DOM-a
            setTimeout(() => {
                if (document.body.contains(this)) {
                    this.remove();
                }
            }, 300);
        });
    }
    
    /**
     * Zatvara popup
     */
    function closePopup() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('imagePopupModal'));
        if (modal) {
            modal.hide();
        }
    }
    
    // Auto-hide controls on mobile after inactivity
    let controlsTimeout;
    function showControlsTemporarily() {
        const controls = document.querySelectorAll('.image-nav-btn, .image-indicators, .image-counter');
        controls.forEach(control => {
            control.style.opacity = '1';
        });
        
        clearTimeout(controlsTimeout);
        controlsTimeout = setTimeout(() => {
            controls.forEach(control => {
                if (!control.matches(':hover')) {
                    control.style.opacity = '0.5';
                }
            });
        }, 3000);
    }
    
    // ==================== EVENT LISTENERI ZA GALERIJU ====================
    
    // Klik na thumbnail
    images.forEach((thumb, index) => {
        thumb.addEventListener('click', function() {
            setCurrentImage(index);
        });
    });
    
    // Klik na indikator
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            setCurrentImage(index);
        });
    });
    
    // Klik na glavnu sliku
    document.querySelectorAll('.main-image-display').forEach(img => {
        img.addEventListener('click', function(e) {
            // Sprečavamo default ponašanje
            e.stopPropagation();
            
            // Pronađemo kojoj slici pripada
            const slide = this.closest('.image-slide');
            if (slide) {
                const index = parseInt(slide.getAttribute('data-image-index'), 10);
                setCurrentImage(index);
            }
            
            // Otvorimo popup
            openFullscreenPopup(e);
        });
    });
    
    // Navigacione strelice
    const prevBtn = document.querySelector('.image-nav-prev');
    const nextBtn = document.querySelector('.image-nav-next');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => setCurrentImage(currentImageIndex - 1));
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => setCurrentImage(currentImageIndex + 1));
    }
    
    // Swipe za mobile - Touch events
    const swipeContainer = document.querySelector('.swipe-container');
    
    if (swipeContainer) {
        // Touch start
        swipeContainer.addEventListener('touchstart', function(e) {
            isSwiping = true;
            swipeStartX = e.touches[0].clientX;
            if (imagesWrapper) {
                imagesWrapper.classList.add('swiping');
            }
        });
        
        // Touch move
        swipeContainer.addEventListener('touchmove', function(e) {
            if (!isSwiping || !imagesWrapper) return;
            
            swipeCurrentX = e.touches[0].clientX;
            const diff = swipeCurrentX - swipeStartX;
            
            // Pomeraj slike za swipe efekat
            const translateX = -currentImageIndex * 100 + (diff / swipeContainer.offsetWidth * 100);
            imagesWrapper.style.transform = `translateX(${translateX}%)`;
        });
        
        // Touch end
        swipeContainer.addEventListener('touchend', function(e) {
            if (!isSwiping) return;
            
            isSwiping = false;
            if (imagesWrapper) {
                imagesWrapper.classList.remove('swiping');
            }
            
            const diff = swipeCurrentX - swipeStartX;
            const swipeDistance = Math.abs(diff);
            
            if (swipeDistance > swipeThreshold) {
                if (diff > 0) {
                    // Swipe desno - prethodna slika
                    setCurrentImage(currentImageIndex - 1, true);
                } else {
                    // Swipe levo - sledeća slika
                    setCurrentImage(currentImageIndex + 1, true);
                }
            } else {
                // Vrati na trenutnu sliku
                goToImage(currentImageIndex, false);
            }
        });
    }
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') setCurrentImage(currentImageIndex - 1);
        if (e.key === 'ArrowRight') setCurrentImage(currentImageIndex + 1);
        if (e.key === 'Escape') closePopup();
    });
    
    // Reset controls visibility on interaction
    document.addEventListener('mousemove', showControlsTemporarily);
    document.addEventListener('touchstart', showControlsTemporarily);
});