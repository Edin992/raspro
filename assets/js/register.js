/**
 * assets/js/register.js - ISPRAVLJENA VERZIJA
 */

class RegistrationForm {
    constructor() {
        this.form = document.getElementById('register-form');
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        // Event listener za submit
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubmit();
        });
        
        this.initAccountTypeToggle();
        
        // Provera username u real-time
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.addEventListener('blur', () => this.checkUsername());
        }
        
        // Provera password strength
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', () => this.checkPasswordStrength());
        }
        
        // Toggle password visibility
        const toggleBtn = document.getElementById('toggle-password');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.togglePassword());
        }
    }
    
    async checkUsername() {
        const usernameInput = document.getElementById('username');
        const feedback = document.getElementById('username-feedback');
        
        if (!usernameInput || !feedback) return;
        
        const username = usernameInput.value.trim();
        
        if (username.length < 3) {
            feedback.textContent = 'Korisničko ime mora imati najmanje 3 karaktera';
            feedback.className = 'invalid-feedback d-block';
            usernameInput.classList.add('is-invalid');
            return;
        }
        
        // Proveri da li username postoji
        try {
            const response = await fetch(`${SITE_CONFIG.url}/api/user/check-username.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username })
            });
            
            const data = await response.json();
            
            if (data.available) {
                feedback.textContent = 'Korisničko ime je dostupno';
                feedback.className = 'valid-feedback d-block';
                usernameInput.classList.remove('is-invalid');
                usernameInput.classList.add('is-valid');
            } else {
                feedback.textContent = 'Korisničko ime je već zauzeto';
                feedback.className = 'invalid-feedback d-block';
                usernameInput.classList.remove('is-valid');
                usernameInput.classList.add('is-invalid');
            }
        } catch (error) {
            console.error('Error checking username:', error);
        }
    }
    
    checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        const requirements = document.querySelectorAll('.password-requirement');
        
        if (!password) return;
        
        let score = 0;
        const requirementsMet = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };
        
        // Ažuriraj ikone zahteva
        requirements.forEach(req => {
            const rule = req.dataset.rule;
            const icon = req.querySelector('i');
            
            if (requirementsMet[rule]) {
                icon.className = 'fas fa-check text-success me-1';
                score++;
            } else {
                icon.className = 'fas fa-times text-danger me-1';
            }
        });
        
        // Ažuriraj progress bar
        const percentage = (score / 4) * 100;
        strengthBar.style.width = `${percentage}%`;
        
        // Ažuriraj tekst
        if (score === 0) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Veoma slaba';
        } else if (score <= 2) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Slaba';
        } else if (score === 3) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.textContent = 'Dobra';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Odlična';
        }
    }
    
    togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.getElementById('toggle-password');
        const icon = toggleBtn.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.className = 'fas fa-eye-slash';
            toggleBtn.title = 'Sakrij lozinku';
        } else {
            passwordInput.type = 'password';
            icon.className = 'fas fa-eye';
            toggleBtn.title = 'Prikaži lozinku';
        }
    }
    
        initAccountTypeToggle() {
        const privateRadio = document.getElementById('account_private');
        const companyRadio = document.getElementById('account_company');
        const companyNameField = document.getElementById('company-name-field');
        const companyNameInput = document.getElementById('company_name');
        const firstNameLabel = document.getElementById('first_name_label');
        const lastNameLabel = document.getElementById('last_name_label');
        const firstNameInput = document.getElementById('first_name');
        const lastNameInput = document.getElementById('last_name');
        
        if (!privateRadio || !companyRadio) return;
        
        const toggleFields = () => {
            if (companyRadio.checked) {
                // Prikaži polje za naziv firme
                companyNameField.style.display = 'block';
                companyNameInput.required = true;
                
                // Promeni labele za ime/prezime u "Kontakt osoba"
                if (firstNameLabel) firstNameLabel.innerHTML = 'Kontakt osoba - Ime (opciono)';
                if (lastNameLabel) lastNameLabel.innerHTML = 'Kontakt osoba - Prezime (opciono)';
                
            } else {
                // Sakrij polje za naziv firme
                companyNameField.style.display = 'none';
                companyNameInput.required = false;
                
                // Vrati originalne labele
                if (firstNameLabel) firstNameLabel.innerHTML = 'Ime *';
                if (lastNameLabel) lastNameLabel.innerHTML = 'Prezime *';
            }
        };
        
        privateRadio.addEventListener('change', toggleFields);
        companyRadio.addEventListener('change', toggleFields);
        
        // Inicijalno stanje
        toggleFields();
    }
    
    async handleSubmit() {
        // Sakupi podatke iz forme
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData.entries());
        
        // Validacija pre slanja
        const errors = this.validateForm(data);
        
        if (errors.length > 0) {
            this.showErrors(errors);
            return;
        }
        
        // Prikaži loading
        this.showLoading(true);
        
        try {
            // Pošalji podatke na server
            const response = await fetch(`${SITE_CONFIG.url}/api/user/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
                            if (result.success) {
                    // Uspešna registracija
                    this.showSuccess(result.message || 'Uspešno ste se registrovali!');
                    
                    // ✅ DODAJEMO: Ako treba verifikacija, ne preusmeravaj na dashboard!
                    if (result.needs_verification || result.email_sent === true) {
                        // Preusmeri na verify-email stranicu
                        setTimeout(() => {
                            window.location.href = result.redirect || '?page=verify-email';
                        }, 2000);
                    } else {
                        // Normalan redirect
                        setTimeout(() => {
                            window.location.href = result.redirect || '?page=dashboard';
                        }, 1500);
                    }
                } else {
                    // Greška pri registraciji
                    this.showErrors(result.errors || [result.message || 'Došlo je do greške']);
                }
            
        } catch (error) {
            console.error('Registration error:', error);
            this.showErrors(['Došlo je do greške pri povezivanju sa serverom']);
        } finally {
            this.showLoading(false);
        }
    }
    
    validateForm(data) {
        const errors = [];
        
        // Proveri da li se lozinke poklapaju
        if (data.password !== data.confirm_password) {
            errors.push('Lozinke se ne poklapaju');
        }
        
        // Validacija za firmu
        if (data.account_type === 'company') {
            if (!data.company_name || data.company_name.trim() === '') {
                errors.push('Naziv firme je obavezan');
            }
        }
        
        // Proveri dužinu lozinke
        if (data.password.length < 8) {
            errors.push('Lozinka mora imati najmanje 8 karaktera');
        }
        
        // Proveri email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(data.email)) {
            errors.push('Email adresa nije validna');
        }
        
        // Proveri da li su uslovi prihvaćeni
        if (!data.terms) {
            errors.push('Morate prihvatiti uslove korišćenja');
        }
        
        return errors;
    }
    
    showErrors(errors) {
        // Sakrij sve prethodne greške
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.classList.remove('d-block');
        });
        
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        // Prikaži nove greške
        if (Array.isArray(errors)) {
            errors.forEach(error => {
                this.showNotification('danger', error);
            });
        } else if (typeof errors === 'string') {
            this.showNotification('danger', errors);
        }
    }
    
    showSuccess(message) {
        this.showNotification('success', message);
    }
    
    showNotification(type, message) {
        // Koristi postojeći sistem notifikacija ili kreiraj jednostavan
        if (typeof Rasprodaja !== 'undefined' && Rasprodaja.showNotification) {
            Rasprodaja.showNotification(type, message);
        } else {
            // Jednostavna alert alternativa
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.card-body').prepend(alert);
            
            // Automatski ukloni nakon 5 sekundi
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    }
    
    showLoading(show) {
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const loadingOverlay = document.getElementById('register-loading');
        
        if (submitBtn) {
            if (show) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registrujem...';
                submitBtn.disabled = true;
            } else {
                submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i> Kreiraj nalog';
                submitBtn.disabled = false;
            }
        }
        
        if (loadingOverlay) {
            loadingOverlay.classList.toggle('d-none', !show);
        }
    }
}

// Inicijalizacija kada se stranica učita
document.addEventListener('DOMContentLoaded', function() {
    new RegistrationForm();
});