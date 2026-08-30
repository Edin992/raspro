/**
 * upload.js - Upload fotografija za oglase
 */

let uploadedFiles = [];
let maxFiles = 10; // Default dok ne učita sa servera
let maxFileSize = 5 * 1024 * 1024; // Default 5MB
let currentStep = 1;
let isInitialized = false;
let packageLimits = null;

// Učitavanje limita sa servera
async function loadPackageLimits() {
    try {
        console.log('Učitavam limite paketa...');
        const response = await fetch('/api/user/package-limits.php');
        const data = await response.json();
        
        if (data.success) {
            packageLimits = data;
            maxFiles = data.limits.images;
            maxFileSize = data.limits.max_image_size;
            
            console.log('Limiti učitani:', {
                package: data.package,
                maxFiles: maxFiles,
                maxFileSize: formatFileSize(maxFileSize),
                remainingAds: data.remaining_ads,
                features: data.features
            });
            
            // Ažuriraj UI sa limitima
            updateLimitsDisplay();
            
            // Opciono: prikaži feature-e paketa
            displayPackageFeatures(data.features);
            
            return true;
        } else {
            console.error('Greška pri učitavanju limita:', data.message);
            return false;
        }
    } catch (error) {
        console.error('Greška pri učitavanju limita:', error);
        return false;
    }
}

// Opciono: prikaži feature-e paketa
function displayPackageFeatures(features) {
    if (!features || !Array.isArray(features)) return;
    
    const featureContainer = document.getElementById('package-features');
    if (featureContainer) {
        let html = '<ul class="list-unstyled small">';
        features.forEach(feature => {
            html += `<li><i class="fas fa-check-circle text-success me-2"></i>${feature}</li>`;
        });
        html += '</ul>';
        featureContainer.innerHTML = html;
    }
}

// Ažuriranje prikaza limita
function updateLimitsDisplay() {
    if (!packageLimits) return;
    
    // Ažuriraj tekst u alertu
    const limitText = document.getElementById('package-limit-text');
    if (limitText) {
        limitText.textContent = `Vaš paket "${packageLimits.package}" dozvoljava ${packageLimits.limits.ads} oglasa (preostalo: ${packageLimits.remaining_ads}) i ${maxFiles} slika po oglasu.`;
        
        if (packageLimits.has_reached_limit) {
            limitText.innerHTML += ' <span class="text-danger fw-bold">Dostigli ste maksimalan broj oglasa!</span>';
        }
    }
    
    // Ažuriraj upload area tekst
    const uploadArea = document.getElementById('upload-area');
    if (uploadArea) {
        const currentText = uploadArea.querySelector('h5');
        if (currentText) {
            currentText.textContent = `Dodajte fotografije (0/${maxFiles})`;
        }
    }
    
    // Postavi limit na file input
    const fileInput = document.getElementById('image-upload');
    if (fileInput) {
        fileInput.setAttribute('data-max-files', maxFiles);
        fileInput.setAttribute('data-max-size', maxFileSize);
    }
}

// Inicijalizacija - SAMO JEDNOM!
async function initImageUpload() {
    // Ako je već inicijalizirano, izađi
    if (isInitialized) {
        console.log('upload.js već inicijaliziran');
        return;
    }
    
    console.log('Inicijaliziram upload.js...');
    
    // Prvo učitaj limite sa servera
    await loadPackageLimits();
    
    isInitialized = true;
    
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('image-upload');
    
    if (!uploadArea || !fileInput) {
        console.warn('Upload elementi nisu pronađeni');
        return;
    }
    
    // Klik na upload area
    uploadArea.addEventListener('click', () => {
        console.log('Klik na upload area');
        fileInput.click();
    });
    
    // Promena fajla
    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#0d6efd';
        uploadArea.style.backgroundColor = '#e7f1ff';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = '#ccc';
        uploadArea.style.backgroundColor = '#f9f9f9';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = '#ccc';
        uploadArea.style.backgroundColor = '#f9f9f9';
        
        if (e.dataTransfer.files.length) {
            console.log('Drop fajlova:', e.dataTransfer.files.length);
            fileInput.files = e.dataTransfer.files;
            handleFileSelect({ target: fileInput });
        }
    });
    
    // Inicijalizuj dugmad za korake - ali SAMO JEDNOM!
    initFormSteps();
    
    // Inicijalizuj live preview
    initLivePreview();
    
    // Inicijalizuj form submit
    initFormSubmit();
    
    console.log('upload.js inicijalizacija završena');
}

function handleFileSelect(event) {
    console.log('handleFileSelect pozvan');
    const files = Array.from(event.target.files);
    console.log('Odabrane datoteke:', files.length);
    
    const totalFiles = uploadedFiles.length + files.length;
    
    // Proveri limit
    if (totalFiles > maxFiles) {
        showAlert('warning', `Možete dodati najviše ${maxFiles} slika. Već imate ${uploadedFiles.length}.`);
        return;
    }
    
    // Validacija svakog fajla
    const validFiles = [];
    
    files.forEach(file => {
        console.log('Proveravam fajl:', file.name, file.type, file.size);
        
        // Proveri tip
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showAlert('danger', `Fajl "${file.name}" nije podržan. Dozvoljeni formati: JPG, PNG, WebP, GIF.`);
            return;
        }
        
        // Proveri veličinu (koristi maxFileSize iz limita)
        if (file.size > maxFileSize) {
            showAlert('danger', `Slika "${file.name}" je prevelika (max ${formatFileSize(maxFileSize)}).`);
            return;
        }
        
        validFiles.push(file);
    });
    
    // Dodaj validne fajlove
    validFiles.forEach(file => {
        // Kreiraj preview
        const reader = new FileReader();
        reader.onload = (e) => {
            console.log('FileReader uspešan za:', file.name);
            addImagePreview(file, e.target.result);
        };
        
        reader.onerror = (e) => {
            console.error('FileReader greška:', e);
            showAlert('danger', `Greška pri čitanju fajla ${file.name}`);
        };
        
        reader.readAsDataURL(file);
        
        // Dodaj u niz
        uploadedFiles.push({
            file: file,
            previewId: 'preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
            isPrimary: uploadedFiles.length === 0 // Prva slika je glavna
        });
    });
    
    // Ažuriraj prikaz
    updateImageCount();
    
    // Resetuj input za više upload-a
    event.target.value = '';
}



function addImagePreview(file, dataUrl) {
    const previewArea = document.getElementById('image-preview');
    const noImagesMessage = document.getElementById('no-images-message');
    
    if (!previewArea) {
        console.error('previewArea nije pronađen');
        return;
    }
    
    // Sakrij "nema slika" poruku
    if (noImagesMessage) {
        noImagesMessage.style.display = 'none';
    }
    
    // Kreiraj ID za ovaj preview
    const previewId = 'preview-' + Date.now();
    const fileIndex = uploadedFiles.findIndex(f => f.file === file);
    
    if (fileIndex === -1) {
        console.error('Fajl nije pronađen u uploadedFiles');
        return;
    }
    
    uploadedFiles[fileIndex].previewId = previewId;
    const isPrimary = uploadedFiles[fileIndex].isPrimary;
    
    // Kreiraj preview element
    const preview = document.createElement('div');
    preview.className = 'image-preview-item mb-3';
    preview.id = previewId;
    preview.style.position = 'relative';
    preview.style.display = 'inline-block';
    preview.style.marginRight = '15px';
    preview.style.verticalAlign = 'top';
    
    // Formatiraj veličinu fajla
    const fileSize = formatFileSize(file.size);
    
    preview.innerHTML = `
        <div class="card" style="width: 180px;">
            <div class="card-img-top position-relative">
                <img src="${dataUrl}" 
                     alt="${file.name}"
                     class="img-fluid" 
                     style="height: 150px; object-fit: cover; width: 100%; border-radius: 8px 8px 0 0;">
                <button type="button" 
                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 remove-image-btn"
                        data-id="${previewId}"
                        style="width: 30px; height: 30px; border-radius: 50%; padding: 0; z-index: 10;">
                    <i class="fas fa-times"></i>
                </button>
                <div class="position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white p-1 w-100">
                    <small>${fileSize}</small>
                </div>
            </div>
            <div class="card-body p-2">
                <small class="text-truncate d-block" title="${file.name}" style="max-width: 150px;">
                    ${truncateFileName(file.name, 20)}
                </small>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <button type="button" 
                            class="btn btn-sm ${isPrimary ? 'btn-primary' : 'btn-outline-primary'} set-primary-btn"
                            data-id="${previewId}"
                            style="font-size: 0.75rem;">
                        ${isPrimary ? '✓ Glavna' : 'Glavna'}
                    </button>
                    <div class="progress" style="height: 3px; width: 40px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Dodaj na početak
    previewArea.insertBefore(preview, previewArea.firstChild);
    
    // Dodaj event listener za uklanjanje
    const removeBtn = preview.querySelector('.remove-image-btn');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            removeImage(this.getAttribute('data-id'));
        });
    }
    
    // Dodaj event listener za postavljanje glavne slike
    const primaryBtn = preview.querySelector('.set-primary-btn');
    if (primaryBtn) {
        primaryBtn.addEventListener('click', function() {
            setPrimaryImage(this.getAttribute('data-id'));
        });
    }
}

function removeImage(previewId) {
    console.log('Uklanjam sliku:', previewId);
    
    // Ukloni iz niza
    const fileIndex = uploadedFiles.findIndex(f => f.previewId === previewId);
    if (fileIndex !== -1) {
        // Ako uklanjamo glavnu sliku, postavi prvu dostupnu za glavnu
        const wasPrimary = uploadedFiles[fileIndex].isPrimary;
        uploadedFiles.splice(fileIndex, 1);
        
        if (wasPrimary && uploadedFiles.length > 0) {
            uploadedFiles[0].isPrimary = true;
            updatePrimaryButtons();
        }
    }
    
    // Ukloni iz DOM-a
    const element = document.getElementById(previewId);
    if (element) {
        element.remove();
    }
    
    // Ažuriraj broj
    updateImageCount();
    
    // Pokaži "nema slika" poruku ako je prazno
    if (uploadedFiles.length === 0) {
        const noImagesMessage = document.getElementById('no-images-message');
        if (noImagesMessage) {
            noImagesMessage.style.display = 'block';
        }
    }
}

function setPrimaryImage(previewId) {
    console.log('Postavljam glavnu sliku:', previewId);
    
    // Resetuj sve na ne-primary
    uploadedFiles.forEach(img => {
        img.isPrimary = false;
    });
    
    // Postavi novu primarnu
    const image = uploadedFiles.find(img => img.previewId === previewId);
    if (image) {
        image.isPrimary = true;
    }
    
    // Ažuriraj dugmad
    updatePrimaryButtons();
}

function updatePrimaryButtons() {
    document.querySelectorAll('.set-primary-btn').forEach(btn => {
        const previewId = btn.getAttribute('data-id');
        const image = uploadedFiles.find(img => img.previewId === previewId);
        
        if (image && image.isPrimary) {
            btn.className = 'btn btn-sm btn-primary set-primary-btn';
            btn.textContent = '✓ Glavna';
            btn.style.fontSize = '0.75rem';
        } else {
            btn.className = 'btn btn-sm btn-outline-primary set-primary-btn';
            btn.textContent = 'Glavna';
            btn.style.fontSize = '0.75rem';
        }
    });
}

function updateImageCount() {
    console.log('Ažuriram broj slika:', uploadedFiles.length);
    
    // Ažuriraj broj slika u preview-u
    const summaryImages = document.getElementById('summary-images');
    if (summaryImages) {
        summaryImages.textContent = uploadedFiles.length;
    }
    
    // Ažuriraj tekst u upload area
    const uploadArea = document.getElementById('upload-area');
    if (uploadArea) {
        const currentText = uploadArea.querySelector('h5');
        if (currentText) {
            currentText.textContent = `Dodajte fotografije (${uploadedFiles.length}/${maxFiles})`;
        }
    }
    
    // Onemogući upload ako je dostignut limit
    const fileInput = document.getElementById('image-upload');
    if (fileInput) {
        if (uploadedFiles.length >= maxFiles) {
            fileInput.disabled = true;
            showAlert('info', `Dostigli ste maksimalan broj slika (${maxFiles}).`);
        } else {
            fileInput.disabled = false;
        }
    }
    
    // Ažuriraj preview (korak 4)
    if (currentStep === 4) {
        updatePreview();
    }
}

// FORMA KORACI
function initFormSteps() {
    console.log('Inicijalizujem korake forme');
    
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    // Ukloni postojeće event listenere (da ne bude duplih)
    nextButtons.forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
    });
    
    prevButtons.forEach(btn => {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
    });
    
    // Ponovo dohvati nakon zamene
    const newNextButtons = document.querySelectorAll('.next-step');
    const newPrevButtons = document.querySelectorAll('.prev-step');
    
    newNextButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStepEl = this.closest('.form-step');
            const nextStepId = this.getAttribute('data-next');
            const nextStep = document.getElementById(nextStepId);
            
            console.log('Next step:', currentStep, '->', nextStepId);
            
            if (nextStep && validateCurrentStep()) {
                currentStepEl.classList.add('d-none');
                nextStep.classList.remove('d-none');
                currentStep = parseInt(nextStepId.split('-')[1]);
                
                // Ažuriraj progres bar
                updateProgressBar();
                
                // Ako je korak 3, ažuriraj upload area tekst
                if (currentStep === 3) {
                    updateImageCount();
                }
                
                // Ako je korak 4, ažuriraj preview
                if (currentStep === 4) {
                    updatePreview();
                }
            }
        });
    });
    
    newPrevButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStepEl = this.closest('.form-step');
            const prevStepId = this.getAttribute('data-prev');
            const prevStep = document.getElementById(prevStepId);
            
            console.log('Prev step:', currentStep, '->', prevStepId);
            
            if (prevStep) {
                currentStepEl.classList.add('d-none');
                prevStep.classList.remove('d-none');
                currentStep = parseInt(prevStepId.split('-')[1]);
                
                // Ažuriraj progres bar
                updateProgressBar();
            }
        });
    });
}

function validateCurrentStep() {
    console.log('Validacija koraka:', currentStep);
    
    switch(currentStep) {
        case 1:
            const title = document.getElementById('title')?.value.trim();
            const category = document.getElementById('category_id')?.value;
            const subcategory = document.getElementById('subcategory_id')?.value;
            const price = document.getElementById('price')?.value;
            
            console.log('Validacija korak 1:', { title, category, subcategory, price });
            
            if (!title || title.length < 10 || title.length > 50) {
                showAlert('warning', 'Naslov mora imati najmanje 10 i najviše 50 karaktera');
                return false;
            }
            
            if (!category || category === '') {
                showAlert('warning', 'Izaberite kategoriju');
                return false;
            }
            
           
            if (!subcategory || subcategory === '') {
                 showAlert('warning', 'Izaberite podkategoriju');
                 return false;
            }
            
            if (!price || isNaN(price) || parseFloat(price) <= 0) {
                showAlert('warning', 'Unesite validnu cenu');
                return false;
            }
            
            if (parseFloat(price) > 99999999) {
                showAlert('warning', 'Cena ne može biti veća od 99,999,999 RSD ili EUR');
                return false;
            }
            
            return true;
            
        case 2:
            const description = document.getElementById('description')?.value.trim();
            const city = document.getElementById('city')?.value.trim();
            
            console.log('Validacija korak 2:', { description, city });
            
            if (!description || description.length < 20) {
                showAlert('warning', 'Opis mora imati najmanje 20 karaktera');
                return false;
            }
            
            if (!city) {
                showAlert('warning', 'Unesite grad/mesto');
                return false;
            }
            
            return true;
            
        case 3:
            console.log('Validacija korak 3 - slike:', uploadedFiles.length);
            
            if (uploadedFiles.length === 0) {
                const confirm = window.confirm(
                    'Niste dodali nijednu fotografiju. Oglasi sa slikama imaju 5x više pregleda.\n' +
                    'Da li ste sigurni da želite da nastavite bez slika?'
                );
                return confirm;
            }
            return true;
            
        default:
            return true;
    }
}

function updateProgressBar() {
    const progressBar = document.getElementById('form-progress');
    if (progressBar) {
        const progress = (currentStep / 4) * 100;
        progressBar.style.width = `${progress}%`;
        
        // Ažuriraj indikatore
        document.querySelectorAll('.form-step-indicator').forEach((indicator, index) => {
            if (index + 1 < currentStep) {
                indicator.classList.add('completed');
            } else {
                indicator.classList.remove('completed');
            }
        });
    }
}

// LIVE PREVIEW
function initLivePreview() {
    console.log('Inicijalizujem live preview');
    
    // Pratite promene u formi za live preview
    const formInputs = ['title', 'price', 'city', 'description', 'category_id', 'condition', 'price_negotiable'];
    
    formInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            // Ukloni postojeće listener pre dodavanja novog
            input.removeEventListener('input', updatePreview);
            input.removeEventListener('change', updatePreview);
            
            input.addEventListener('input', updatePreview);
            input.addEventListener('change', updatePreview);
        }
    });
}

function updatePreview() {
    console.log('Ažuriram preview');
    
    // Osnovni podaci
    const title = document.getElementById('title')?.value || 'Naslov oglasa';
    const price = document.getElementById('price')?.value || '0';
    const city = document.getElementById('city')?.value || 'Nepoznato';
    const description = document.getElementById('description')?.value || 'Nema opisa';
    const condition = document.getElementById('condition')?.value || 'used';
    const isNegotiable = document.getElementById('price_negotiable')?.checked || false;
    const categorySelect = document.getElementById('category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const categoryText = categorySelect?.options[categorySelect.selectedIndex]?.text || 'Nije izabrana';
    const subcategoryText = subcategorySelect?.options[subcategorySelect.selectedIndex]?.text || 'Nije izabrana';
    
    // Ukloni broj oglasa iz teksta kategorije (ako postoji)
    const cleanCategoryText = categoryText.split('(')[0].trim();
    const cleanSubcategoryText = subcategoryText.split('(')[0].trim();
    
    // Ažuriraj preview elemente
    const previewTitle = document.getElementById('preview-title');
    const previewPrice = document.getElementById('preview-price');
    const previewLocation = document.getElementById('preview-location');
    const previewDescription = document.getElementById('preview-description');
    const previewCondition = document.getElementById('preview-condition');
    const previewNegotiable = document.getElementById('preview-negotiable');
    const summaryTitle = document.getElementById('summary-title-short');
    const summaryCategory = document.getElementById('summary-category');
    const summarySubcategory = document.getElementById('summary-subcategory');
    const summaryPrice = document.getElementById('summary-price');
    
    if (previewTitle) previewTitle.textContent = title;
    if (previewPrice) previewPrice.textContent = formatPrice(price) + ' RSD';
    if (previewLocation) previewLocation.textContent = city;
    if (previewDescription) previewDescription.textContent = description;
    
    // Condition badge
    if (previewCondition) {
        const conditionText = getConditionText(condition);
        const conditionClass = getConditionClass(condition);
        previewCondition.textContent = conditionText;
        previewCondition.className = 'badge ' + conditionClass;
    }
    
    // Negotiable badge
    if (previewNegotiable) {
        previewNegotiable.style.display = isNegotiable ? 'inline-block' : 'none';
    }
    
    // Summary
    if (summaryTitle) summaryTitle.textContent = truncateText(title, 30);
    if (summaryCategory) summaryCategory.textContent = cleanCategoryText;
    if (summarySubcategory) summarySubcategory.textContent = cleanSubcategoryText;
    if (summaryPrice) summaryPrice.textContent = formatPrice(price) + ' RSD';
}

// FORM SUBMIT HANDLING
function initFormSubmit() {
    const form = document.getElementById('create-ad-form');
    if (!form) return;
    
    // Ukloni postojeći listener pre dodavanja novog
    form.removeEventListener('submit', handleFormSubmit);
    form.addEventListener('submit', handleFormSubmit);
}

async function handleFormSubmit(e) {
    e.preventDefault();
    console.log('Form submit pokrenut');
    
    // Proveri sve korake
    for (let step = 1; step <= 3; step++) {
        currentStep = step;
        if (!validateCurrentStep()) {
            // Vrati se na korak sa greškom
            document.querySelectorAll('.form-step').forEach(el => el.classList.add('d-none'));
            const errorStep = document.getElementById(`step-${step}`);
            if (errorStep) errorStep.classList.remove('d-none');
            updateProgressBar();
            return;
        }
    }
    
    const form = e.target;
    
    // Prikaži loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Objavljujem...';
    
    try {
        // Kreiraj FormData
        const formData = new FormData(form);
        
        // Dodaj slike
        uploadedFiles.forEach((fileObj, index) => {
            formData.append('images[]', fileObj.file);
            if (fileObj.isPrimary) {
                formData.append('primary_image_index', index);
            }
        });
        
        // Pošalji na server
        const response = await fetch('/api/ads/create.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            showAlert('success', 'Oglas je uspešno objavljen!');
            
            // Preusmeri nakon 2 sekunde
            setTimeout(() => {
                window.location.href = result.redirect || `/ad/${result.ad_id}`;
            }, 2000);
        } else {
            showAlert('danger', result.message || 'Greška pri objavljivanju oglasa');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
        
    } catch (error) {
        console.error('Submit error:', error);
        showAlert('danger', 'Greška pri slanju podataka. Proverite internet konekciju.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// FORMATTER FUNKCIJE
function formatPrice(price) {
    const num = parseFloat(price) || 0;
    return num.toLocaleString('sr-RS', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

function getConditionText(condition) {
    const conditions = {
        'new': 'Novo',
        'used': 'Korišćeno',
        'broken': 'Oštećeno'
    };
    return conditions[condition] || 'Korišćeno';
}

function getConditionClass(condition) {
    const classes = {
        'new': 'bg-success',
        'used': 'bg-info',
        'broken': 'bg-warning'
    };
    return classes[condition] || 'bg-info';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function truncateFileName(name, maxLength) {
    if (name.length <= maxLength) return name;
    
    const extension = name.split('.').pop();
    const nameWithoutExt = name.substring(0, name.length - extension.length - 1);
    const truncated = nameWithoutExt.substring(0, maxLength - 3 - extension.length);
    
    return truncated + '...' + extension;
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function showAlert(type, message) {
    // Konvertuj 'error' u 'danger' za Bootstrap
    const bootstrapType = type === 'error' ? 'danger' : type;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${bootstrapType} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${bootstrapType === 'danger' ? 'exclamation-triangle' : 
                              bootstrapType === 'warning' ? 'exclamation-circle' : 
                              'info-circle'} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Automatski ukloni nakon 5 sekundi
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// INICIJALIZACIJA - SAMO JEDNOM!
document.addEventListener('DOMContentLoaded', function() {
    console.log('upload.js učitan - DOMContentLoaded');
    
    // Inicijalizuj upload SAMO AKO NIJE VEĆ INICIJALIZIRANO
    if (!isInitialized) {
        initImageUpload();
    }
    
    // Globalna funkcija za removeImage (za inline onclick)
    window.removeImage = removeImage;
    
    // Ako pageInit postoji, pozovi ga - ALI PROVERI DA LI JE VEĆ POZVAN
    if (typeof window.pageInit === 'function' && !window.pageInitCalled) {
        window.pageInitCalled = true;
        window.pageInit();
    }
});