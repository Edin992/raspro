/**
 * assets/js/edit-ad.js - JavaScript za editovanje oglasa
 */

let editCurrentStep = 1;
let editUploadedFiles = [];
let editMaxFiles = 10;
let editAdId = null;

window.initEditAd = function() {
    console.log('🚀 Edit ad page initialized');
    
    const adIdInput = document.querySelector('input[name="ad_id"]');
    if (adIdInput) {
        editAdId = adIdInput.value;
        console.log('📝 Ad ID:', editAdId);
    }
    
    loadUserLimits().then(() => {
        initEditImageUpload();
        initEditFormSteps();
        initEditLivePreview();
        initEditFormSubmit();
        initEditCategorySelector();
        updateEditProgressBar();
        updateEditPreview();
        updateEditImageCount();
    }).catch(error => {
        console.error('Greška pri inicijalizaciji:', error);
    });
};

async function loadUserLimits() {
    try {
        console.log('📡 Učitavam korisničke limite...');
        const response = await fetch('/api/user/package-limits.php');
        const data = await response.json();
        
        if (data.success) {
            editMaxFiles = data.limits?.images || 10;
            
            const packageNameSpan = document.getElementById('package-name');
            const packageLimitText = document.getElementById('package-limit-text');
            
            if (packageNameSpan) {
                packageNameSpan.textContent = data.package || 'Free';
            }
            
            if (packageLimitText) {
                const isUnlimited = (data.limits?.ads === 999999 || data.is_unlimited);
                if (isUnlimited) {
                    packageLimitText.innerHTML = `Vaš paket dozvoljava <strong>neograničen</strong> broj oglasa i ${data.limits?.images || 10} slika po oglasu.`;
                } else {
                    packageLimitText.innerHTML = `Vaš paket dozvoljava ${data.limits?.ads || 10} oglasa (preostalo: ${data.remaining_ads || 0}) i ${data.limits?.images || 10} slika po oglasu.`;
                }
            }
            
            console.log('✅ User limits loaded:', { editMaxFiles, data });
        } else {
            console.error('Greška pri učitavanju limita:', data.message);
            editMaxFiles = 10;
        }
    } catch (error) {
        console.error('❌ Error loading user limits:', error);
        editMaxFiles = 10;
    }
}

function initEditCategorySelector() {
    const categorySelect = document.getElementById('category_id');
    const subcategoryContainer = document.getElementById('subcategory-container');
    const subcategorySelect = document.getElementById('subcategory_id');
    const originalSubcategoryId = document.getElementById('original-subcategory-id')?.value || '';
    const originalCategoryId = document.getElementById('original-category-id')?.value || '';
    
    if (!categorySelect) return;
    
    function loadSubcategories(categoryId, selectedId) {
        if (!categoryId || categoryId === '') {
            if (subcategoryContainer) subcategoryContainer.style.display = 'none';
            if (subcategorySelect) {
                subcategorySelect.innerHTML = '<option value="">Izaberite podkategoriju</option>';
            }
            return;
        }
        
        if (subcategorySelect) {
            subcategorySelect.innerHTML = '<option value="">Učitavanje...</option>';
            if (subcategoryContainer) subcategoryContainer.style.display = 'block';
        }
        
        fetch('/api/categories/children.php?parent_id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                if (!subcategorySelect) return;
                
                if (data.success && data.categories && data.categories.length > 0) {
                    subcategorySelect.innerHTML = '<option value="">Izaberite podkategoriju</option>';
                    data.categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.name;
                        if (selectedId && cat.id == selectedId) {
                            option.selected = true;
                        }
                        subcategorySelect.appendChild(option);
                    });
                    if (subcategoryContainer) subcategoryContainer.style.display = 'block';
                } else {
                    subcategorySelect.innerHTML = '<option value="">Nema podkategorija</option>';
                    if (subcategoryContainer) subcategoryContainer.style.display = 'none';
                }
                
                updateEditPreview();
            })
            .catch(error => {
                console.error('Error loading subcategories:', error);
                if (subcategorySelect) {
                    subcategorySelect.innerHTML = '<option value="">Greška pri učitavanju</option>';
                }
            });
    }
    
    if (originalCategoryId && originalCategoryId !== '') {
        loadSubcategories(originalCategoryId, originalSubcategoryId);
    }
    
    categorySelect.addEventListener('change', function() {
        if (subcategorySelect) {
            subcategorySelect.innerHTML = '<option value="">Izaberite podkategoriju</option>';
            subcategorySelect.value = '';
        }
        loadSubcategories(this.value, null);
        updateEditPreview();
    });
}

function initEditImageUpload() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('image-upload');
    
    if (!uploadArea || !fileInput) {
        console.warn('Upload elementi nisu pronađeni');
        return;
    }
    
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', handleEditFileSelect);
    
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.style.borderColor = '#0d6efd';
        uploadArea.style.backgroundColor = '#e7f1ff';
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.style.borderColor = '#ccc';
        uploadArea.style.backgroundColor = '#f9f9f9';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.style.borderColor = '#ccc';
        uploadArea.style.backgroundColor = '#f9f9f9';
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleEditFileSelect({ target: fileInput });
        }
    });
    
    document.querySelectorAll('.delete-image-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const card = this.closest('.existing-image-card');
            if (card) {
                card.style.opacity = this.checked ? '0.5' : '1';
            }
            updateEditImageCount();
            updateEditPreview();
        });
    });
    
    document.querySelectorAll('.main-image-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Glavna slika postavljena na:', this.value);
        });
    });
    
    updateEditImageCount();
}

function handleEditFileSelect(event) {
    const files = Array.from(event.target.files);
    
    const existingImages = document.querySelectorAll('.existing-image-card').length;
    const imagesToDelete = document.querySelectorAll('.delete-image-checkbox:checked').length;
    const remainingExisting = Math.max(0, existingImages - imagesToDelete);
    const currentTotal = remainingExisting + editUploadedFiles.length;
    const newTotal = currentTotal + files.length;
    
    console.log('🔍 Provera limita - trenutno:', currentTotal, 'novih fajlova:', files.length, 'ukupno bi bilo:', newTotal, '/', editMaxFiles);
    
    if (newTotal > editMaxFiles) {
        const maxNew = editMaxFiles - currentTotal;
        showEditAlert('warning', `Možete dodati još samo ${maxNew} slika. Ukupan broj slika ne sme preći ${editMaxFiles}.`);
        event.target.value = '';
        return;
    }
    
    const validFiles = [];
    
    files.forEach(file => {
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showEditAlert('danger', `Fajl "${file.name}" nije podržan. Dozvoljeni formati: JPG, PNG, WebP.`);
            return;
        }
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showEditAlert('danger', `Slika "${file.name}" je prevelika (max 5MB).`);
            return;
        }
        
        validFiles.push(file);
    });
    
    validFiles.forEach(file => {
        const reader = new FileReader();
        const previewId = 'new-preview-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        reader.onload = function(e) {
            addEditImagePreview(file, e.target.result, previewId);
        };
        
        reader.readAsDataURL(file);
        
        editUploadedFiles.push({
            file: file,
            previewId: previewId
        });
    });
    
    updateEditImageCount();
    event.target.value = '';
}

function addEditImagePreview(file, dataUrl, previewId) {
    const previewArea = document.getElementById('image-preview');
    const noImagesMessage = document.getElementById('no-images-message');
    
    if (!previewArea) return;
    
    if (noImagesMessage) {
        noImagesMessage.style.display = 'none';
    }
    
    const fileSize = formatFileSize(file.size);
    
    const preview = document.createElement('div');
    preview.className = 'image-preview-item d-inline-block m-2';
    preview.id = previewId;
    preview.style.verticalAlign = 'top';
    
    preview.innerHTML = `
        <div class="card" style="width: 150px;">
            <div class="position-relative">
                <img src="${dataUrl}" 
                     alt="${file.name}"
                     class="card-img-top" 
                     style="height: 120px; object-fit: cover;">
                <button type="button" 
                        class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 remove-edit-image-btn"
                        data-id="${previewId}"
                        style="width: 28px; height: 28px; border-radius: 50%; padding: 0;">
                    <i class="fas fa-times fa-sm"></i>
                </button>
            </div>
            <div class="card-body p-1 text-center">
                <small class="text-muted d-block text-truncate" style="max-width: 130px;" title="${file.name}">
                    ${truncateFileName(file.name, 15)}
                </small>
                <small class="text-muted">${fileSize}</small>
            </div>
        </div>
    `;
    
    previewArea.appendChild(preview);
    
    const removeBtn = preview.querySelector('.remove-edit-image-btn');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            removeEditImage(this.getAttribute('data-id'));
        });
    }
}

function removeEditImage(previewId) {
    const fileIndex = editUploadedFiles.findIndex(f => f.previewId === previewId);
    if (fileIndex !== -1) {
        editUploadedFiles.splice(fileIndex, 1);
    }
    
    const element = document.getElementById(previewId);
    if (element) {
        element.remove();
    }
    
    updateEditImageCount();
    
    const existingImages = document.querySelectorAll('.existing-image-card').length;
    const imagesToDelete = document.querySelectorAll('.delete-image-checkbox:checked').length;
    const remainingExisting = Math.max(0, existingImages - imagesToDelete);
    const totalImages = remainingExisting + editUploadedFiles.length;
    
    if (totalImages < editMaxFiles) {
        const fileInput = document.getElementById('image-upload');
        if (fileInput) fileInput.disabled = false;
    }
    
    if (editUploadedFiles.length === 0 && existingImages === 0) {
        const noImagesMessage = document.getElementById('no-images-message');
        if (noImagesMessage) {
            noImagesMessage.style.display = 'block';
        }
    }
}

function initEditFormSteps() {
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    
    nextButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStepEl = this.closest('.form-step');
            const nextStepId = this.getAttribute('data-next');
            const nextStep = document.getElementById(nextStepId);
            
            if (nextStep && validateEditStep()) {
                currentStepEl.classList.add('d-none');
                nextStep.classList.remove('d-none');
                editCurrentStep = parseInt(nextStepId.split('-')[1]);
                updateEditProgressBar();
                
                if (editCurrentStep === 4) {
                    updateEditPreview();
                    updateEditImageCount();
                }
                
                if (editCurrentStep === 3) {
                    updateEditImageCount();
                }
            }
        });
    });
    
    prevButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStepEl = this.closest('.form-step');
            const prevStepId = this.getAttribute('data-prev');
            const prevStep = document.getElementById(prevStepId);
            
            if (prevStep) {
                currentStepEl.classList.add('d-none');
                prevStep.classList.remove('d-none');
                editCurrentStep = parseInt(prevStepId.split('-')[1]);
                updateEditProgressBar();
            }
        });
    });
}

function validateEditStep() {
    switch(editCurrentStep) {
        case 1:
            const title = document.getElementById('title')?.value.trim();
            const category = document.getElementById('category_id')?.value;
            const price = document.getElementById('price')?.value;
            
            if (!title || title.length < 10 || title.length > 50) {
                showEditAlert('warning', 'Naslov mora imati najmanje 10 i najviše 50 karaktera');
                return false;
            }
            if (!category) {
                showEditAlert('warning', 'Izaberite kategoriju');
                return false;
            }
            if (!price || parseFloat(price) <= 0) {
                showEditAlert('warning', 'Unesite validnu cenu');
                return false;
            }
            return true;
            
        case 2:
            const description = document.getElementById('description')?.value.trim();
            if (!description || description.length < 20) {
                showEditAlert('warning', 'Opis mora imati najmanje 20 karaktera');
                return false;
            }
            return true;
            
        case 3:
            const existingImages = document.querySelectorAll('.existing-image-card').length;
            const imagesToDelete = document.querySelectorAll('.delete-image-checkbox:checked').length;
            const remainingImages = existingImages - imagesToDelete;
            
            if (remainingImages === 0 && editUploadedFiles.length === 0) {
                if (!confirm('Označili ste sve slike za brisanje i niste dodali nove. Oglasi bez slika imaju manje pregleda. Da li ste sigurni?')) {
                    return false;
                }
            }
            return true;
            
        default:
            return true;
    }
}

function updateEditProgressBar() {
    const progressBar = document.getElementById('form-progress');
    if (progressBar) {
        const progress = (editCurrentStep / 4) * 100;
        progressBar.style.width = `${progress}%`;
    }
    
    const stepIndicators = document.querySelectorAll('.form-step-indicator');
    stepIndicators.forEach((indicator, index) => {
        if (index + 1 < editCurrentStep) {
            indicator.classList.add('completed');
        } else {
            indicator.classList.remove('completed');
        }
    });
}

function initEditLivePreview() {
    const inputs = ['title', 'price', 'city', 'description', 'item_condition', 'price_negotiable'];
    
    const currencySelect = document.getElementById('currency');
    const currencySymbol = document.getElementById('currency-symbol');
    
    if (currencySelect && currencySymbol) {
        currencySelect.addEventListener('change', function() {
            const symbol = this.value === 'EUR' ? '€' : 'RSD';
            currencySymbol.textContent = symbol;
        });
    }
    
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => updateEditPreview());
            el.addEventListener('change', () => updateEditPreview());
        }
    });
}

function updateEditPreview() {
    const title = document.getElementById('title')?.value || '';
    const price = document.getElementById('price')?.value || '0';
    const city = document.getElementById('city')?.value || '';
    const description = document.getElementById('description')?.value || '';
    const condition = document.getElementById('item_condition')?.value || 'used';
    const isNegotiable = document.getElementById('price_negotiable')?.checked || false;
    
    const categorySelect = document.getElementById('category_id');
    const categoryText = categorySelect?.options[categorySelect.selectedIndex]?.text || 'Nije izabrana';
    
    const subcategorySelect = document.getElementById('subcategory_id');
    let subcategoryText = '';
    if (subcategorySelect && subcategorySelect.value) {
        subcategoryText = subcategorySelect.options[subcategorySelect.selectedIndex]?.text || '';
    }
    
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
    
    if (previewTitle) previewTitle.textContent = title || 'Naslov oglasa';
    if (previewPrice) previewPrice.textContent = new Intl.NumberFormat('sr-RS').format(price) + ' RSD';
    if (previewLocation) previewLocation.textContent = city || 'Nepoznato';
    if (previewDescription) previewDescription.textContent = description || 'Nema opisa';
    
    if (previewCondition) {
        const conditionMap = { 'new': 'Novo', 'used': 'Korišćeno', 'broken': 'Oštećeno' };
        previewCondition.textContent = conditionMap[condition] || 'Korišćeno';
    }
    
    if (previewNegotiable) {
        previewNegotiable.style.display = isNegotiable ? 'inline-block' : 'none';
    }
    
    if (summaryTitle) summaryTitle.textContent = title.substring(0, 30) || 'Nema naslova';
    if (summaryCategory) summaryCategory.textContent = categoryText;
    if (summarySubcategory) summarySubcategory.textContent = subcategoryText || 'Nema';
    if (summaryPrice) summaryPrice.textContent = new Intl.NumberFormat('sr-RS').format(price) + ' RSD';
}

// ============================================
// GLAVNA FUNKCIJA ZA PRIKAZ UKUPNOG BROJA SLIKA
// ============================================
function updateEditImageCount() {
    const existingImages = document.querySelectorAll('.existing-image-card').length;
    const imagesToDelete = document.querySelectorAll('.delete-image-checkbox:checked').length;
    const remainingExisting = Math.max(0, existingImages - imagesToDelete);
    const totalImages = remainingExisting + editUploadedFiles.length;
    
    console.log('📊 Update image count:');
    console.log('  - Postojeće slike:', existingImages);
    console.log('  - Za brisanje:', imagesToDelete);
    console.log('  - Preostale postojeće:', remainingExisting);
    console.log('  - Nove slike:', editUploadedFiles.length);
    console.log('  - UKUPNO:', totalImages, '/', editMaxFiles);
    
    // Ažuriraj summary (ukupno slika)
    const summaryImages = document.getElementById('summary-images');
    if (summaryImages) {
        summaryImages.innerHTML = `${remainingExisting} postojećih + ${editUploadedFiles.length} novih = ${totalImages} ukupno`;
    }
    
    // Ažuriraj tekst u upload area - PRIKAZ UKUPNOG BROJA
    const uploadArea = document.getElementById('upload-area');
    if (uploadArea) {
        const currentText = uploadArea.querySelector('h5');
        if (currentText) {
            currentText.textContent = `Fotografije (${totalImages}/${editMaxFiles})`;
        }
    }
    
    // Proveri limit - UKUPAN broj slika ne sme preći editMaxFiles
    if (totalImages > editMaxFiles) {
        showEditAlert('danger', `Ukupan broj slika (${totalImages}) premašuje maksimalni limit od ${editMaxFiles}.`);
        const fileInput = document.getElementById('image-upload');
        if (fileInput) fileInput.disabled = true;
        return;
    }
    
    // Onemogući upload ako je dostignut UKUPAN limit
    const fileInput = document.getElementById('image-upload');
    if (fileInput) {
        if (totalImages >= editMaxFiles) {
            fileInput.disabled = true;
            showEditAlert('info', `Dostigli ste maksimalan broj slika (${editMaxFiles}).`);
        } else {
            fileInput.disabled = false;
        }
    }
    
    if (editCurrentStep === 4) {
        updateEditPreview();
    }
}

function initEditFormSubmit() {
    const form = document.getElementById('edit-ad-form');
    
    if (!form) {
        console.error('❌ Forma sa ID "edit-ad-form" nije pronađena!');
        return;
    }
    
    console.log('✅ Forma pronađena, dodajem submit handler');
    console.log('📡 Form action:', form.action);
    console.log('📡 Form method:', form.method);
    
    form.removeEventListener('submit', handleEditSubmit);
    form.addEventListener('submit', handleEditSubmit);
}

async function handleEditSubmit(e) {
    e.preventDefault();
    console.log('Edit form submit pokrenut');
    
    for (let step = 1; step <= 3; step++) {
        editCurrentStep = step;
        if (!validateEditStep()) {
            const stepToShow = document.getElementById(`step-${step}`);
            if (stepToShow) {
                document.querySelectorAll('.form-step').forEach(el => el.classList.add('d-none'));
                stepToShow.classList.remove('d-none');
                updateEditProgressBar();
            }
            return;
        }
    }
    
    const form = document.getElementById('edit-ad-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Čuvanje...';
    
    try {
        const formData = new FormData(form);
        
        console.log('📷 Dodajem slike za upload:', editUploadedFiles.length);
        editUploadedFiles.forEach((fileObj, index) => {
            console.log(`  Slika ${index + 1}: ${fileObj.file.name}`);
            formData.append('new_images[]', fileObj.file);
        });
        
        const response = await fetch('/api/ads/update.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        console.log('Server response:', result);
        
        if (result.success) {
            showEditAlert('success', 'Oglas je uspešno ažuriran!');
            setTimeout(() => {
                window.location.href = result.redirect || `/ad/${result.ad_id}`;
            }, 2000);
        } else {
            showEditAlert('danger', result.message || 'Greška pri ažuriranju');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
        
    } catch (error) {
        console.error('Submit error:', error);
        showEditAlert('danger', 'Greška pri slanju podataka.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function truncateFileName(name, maxLength) {
    if (name.length <= maxLength) return name;
    const ext = name.split('.').pop();
    const nameWithoutExt = name.substring(0, name.length - ext.length - 1);
    const truncated = nameWithoutExt.substring(0, maxLength - 3 - ext.length);
    return truncated + '...' + ext;
}

function showEditAlert(type, message) {
    const alertDiv = document.createElement('div');
    const bootstrapType = type === 'error' ? 'danger' : type;
    
    alertDiv.className = `alert alert-${bootstrapType} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px; z-index: 10000;';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}