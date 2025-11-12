/* --- Keyframes Animasi (Tetap Sama) --- */
if (!document.querySelector('#material-keyframes')) {
    const style = document.createElement('style');
    style.id = 'material-keyframes';
    style.textContent = `
        @keyframes materialShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-4px); }
            75% { transform: translateX(4px); }
        }
        @keyframes materialPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
}

/*
=========================================
 KELAS UNTUK HALAMAN LOGIN (login.php)
=========================================
*/
class MaterialLoginForm {
    constructor() {
        this.form = document.getElementById('loginForm');
        // DIUBAH: dari email ke username
        this.usernameInput = document.getElementById('username');
        this.passwordInput = document.getElementById('password');
        this.passwordToggle = document.getElementById('passwordToggle');
        this.submitButton = this.form.querySelector('.material-btn');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPasswordToggle();
        this.setupRippleEffects();
    }
    
    bindEvents() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        // DIUBAH: validasi username, bukan email
        this.usernameInput.addEventListener('blur', () => this.validateUsername());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());
        this.usernameInput.addEventListener('input', () => this.clearError('username'));
        this.passwordInput.addEventListener('input', () => this.clearError('password'));
        
        [this.usernameInput, this.passwordInput].forEach(input => {
            input.addEventListener('focus', (e) => this.handleInputFocus(e));
            input.addEventListener('blur', (e) => this.handleInputBlur(e));
        });
    }
    
    setupPasswordToggle() {
        this.passwordToggle.addEventListener('click', (e) => {
            // DIPERBAIKI: Menggunakan this.passwordInput, bukan 'confirmPassword'
            const type = this.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            this.passwordInput.setAttribute('type', type);
        });
    }
    
    setupRippleEffects() {
        // (Fungsi ini tidak perlu diubah, biarkan apa adanya)
        [this.usernameInput, this.passwordInput].forEach(input => {
            input.addEventListener('focus', (e) => {
                const rippleContainer = input.parentNode.querySelector('.ripple-container');
                this.createRipple(e, rippleContainer);
            });
        });
        this.submitButton.addEventListener('click', (e) => {
            this.createRipple(e, this.submitButton.querySelector('.btn-ripple'));
        });
        const checkbox = this.form.querySelector('.checkbox-wrapper');
        if(checkbox) {
            checkbox.addEventListener('click', (e) => {
                const rippleContainer = checkbox.querySelector('.checkbox-ripple');
                this.createRipple(e, rippleContainer);
            });
        }
    }
    
    createRipple(event, container) {
        // (Fungsi ini tidak perlu diubah, biarkan apa adanya)
        const rect = container.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('div');
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        container.appendChild(ripple);
        setTimeout(() => { ripple.remove(); }, 600);
    }
    
    handleInputFocus(e) { e.target.closest('.input-wrapper').classList.add('focused'); }
    handleInputBlur(e) { e.target.closest('.input-wrapper').classList.remove('focused'); }

    // DIUBAH: Menjadi validateUsername
    validateUsername() {
        const username = this.usernameInput.value.trim();
        if (!username) {
            this.showError('username', 'Masukkan username');
            return false;
        }
        this.clearError('username');
        return true;
    }
    
    validatePassword() {
        const password = this.passwordInput.value;
        if (!password) {
            this.showError('password', 'Masukkan password');
            return false;
        }
        if (password.length < 8) {
            this.showError('password', 'Password minimal 8 karakter');
            return false;
        }
        this.clearError('password');
        return true;
    }
    
    showError(field, message) {
        const formGroup = document.getElementById(field).closest('.form-group');
        const errorElement = document.getElementById(`${field}Error`); // cth: usernameError
        
        formGroup.classList.add('error');
        errorElement.textContent = message;
        errorElement.classList.add('show');
        
        const input = document.getElementById(field);
        input.style.animation = 'materialShake 0.4s ease-in-out';
        setTimeout(() => { input.style.animation = ''; }, 400);
    }
    
    clearError(field) {
        const formGroup = document.getElementById(field).closest('.form-group');
        const errorElement = document.getElementById(`${field}Error`);
        
        formGroup.classList.remove('error');
        errorElement.classList.remove('show');
        setTimeout(() => { errorElement.textContent = ''; }, 200);
    }
    
    handleSubmit(e) {
        // Validasi dulu
        const isUsernameValid = this.validateUsername();
        const isPasswordValid = this.validatePassword();
        
        // Jika salah satu tidak valid, hentikan submit
        if (!isUsernameValid || !isPasswordValid) {
            e.preventDefault(); // HENTIKAN SUBMIT
            this.submitButton.style.animation = 'materialPulse 0.3s ease';
            setTimeout(() => { this.submitButton.style.animation = ''; }, 300);
            return;
        }
        
        // --- INI PERUBAHAN PENTING ---
        // Jika valid, jangan `preventDefault`. Biarkan form dikirim ke PHP.
        // Cukup tampilkan loading spinner.
        this.setLoading(true);
        
        // Kita TIDAK lagi menjalankan `e.preventDefault()`.
        // Form akan otomatis ter-submit ke PHP.
        // Logika `showMaterialSuccess` dihapus karena halaman akan di-reload oleh PHP.
    }
    
    setLoading(loading) {
        this.submitButton.classList.toggle('loading', loading);
        this.submitButton.disabled = loading;
    }
}


/*
=========================================
 KELAS UNTUK HALAMAN DAFTAR (daftar.php)
=========================================
*/
class MaterialRegisterForm {
    constructor() {
        this.form = document.getElementById('registerForm');
        this.namaInput = document.getElementById('nama');
        this.usernameInput = document.getElementById('username');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.confirmPasswordInput = document.getElementById('confirm_password');
        
        this.passwordToggle = document.getElementById('passwordToggle');
        this.confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        
        this.submitButton = this.form.querySelector('.material-btn');
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPasswordToggles();
        this.setupRippleEffects();
    }
    
    bindEvents() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        this.namaInput.addEventListener('blur', () => this.validateNama());
        this.usernameInput.addEventListener('blur', () => this.validateUsername());
        this.emailInput.addEventListener('blur', () => this.validateEmail());
        this.passwordInput.addEventListener('blur', () => this.validatePassword());
        this.confirmPasswordInput.addEventListener('blur', () => this.validateConfirmPassword());

        this.namaInput.addEventListener('input', () => this.clearError('nama'));
        this.usernameInput.addEventListener('input', () => this.clearError('username'));
        this.emailInput.addEventListener('input', () => this.clearError('email'));
        this.passwordInput.addEventListener('input', () => this.clearError('password'));
        this.confirmPasswordInput.addEventListener('input', () => this.clearError('confirm_password'));
        
        [this.namaInput, this.usernameInput, this.emailInput, this.passwordInput, this.confirmPasswordInput].forEach(input => {
            input.addEventListener('focus', (e) => this.handleInputFocus(e));
            input.addEventListener('blur', (e) => this.handleInputBlur(e));
        });
    }
    
    setupPasswordToggles() {
        this.passwordToggle.addEventListener('click', (e) => {
            const type = this.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            this.passwordInput.setAttribute('type', type);
        });
        
        this.confirmPasswordToggle.addEventListener('click', (e) => {
            const type = this.confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            this.confirmPasswordInput.setAttribute('type', type);
        });
    }
    
    setupRippleEffects() {
        // (Sama seperti login)
        [this.namaInput, this.usernameInput, this.emailInput, this.passwordInput, this.confirmPasswordInput].forEach(input => {
            input.addEventListener('focus', (e) => {
                const rippleContainer = input.parentNode.querySelector('.ripple-container');
                this.createRipple(e, rippleContainer);
            });
        });
        this.submitButton.addEventListener('click', (e) => {
            this.createRipple(e, this.submitButton.querySelector('.btn-ripple'));
        });
    }

    createRipple(event, container) {
        // (Sama seperti login)
        const rect = container.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('div');
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        container.appendChild(ripple);
        setTimeout(() => { ripple.remove(); }, 600);
    }

    handleInputFocus(e) { e.target.closest('.input-wrapper').classList.add('focused'); }
    handleInputBlur(e) { e.target.closest('.input-wrapper').classList.remove('focused'); }

    // Validasi untuk setiap field
    validateNama() {
        if (!this.namaInput.value.trim()) {
            this.showError('nama', 'Masukkan nama');
            return false;
        }
        this.clearError('nama');
        return true;
    }

    validateUsername() {
        if (!this.usernameInput.value.trim()) {
            this.showError('username', 'Masukkan username');
            return false;
        }
        this.clearError('username');
        return true;
    }

    validateEmail() {
        const email = this.emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) {
            this.showError('email', 'Masukkan email');
            return false;
        }
        if (!emailRegex.test(email)) {
            this.showError('email', 'Masukkan email yang valid');
            return false;
        }
        this.clearError('email');
        return true;
    }
    
    validatePassword() {
        const password = this.passwordInput.value;
        if (!password) {
            this.showError('password', 'Masukkan password');
            return false;
        }
        if (password.length < 8) {
            this.showError('password', 'Password minimal 8 karakter');
            return false;
        }
        this.clearError('password');
        return true;
    }

    validateConfirmPassword() {
        const password = this.passwordInput.value;
        const confirmPassword = this.confirmPasswordInput.value;
        if (!confirmPassword) {
            this.showError('confirm_password', 'Konfirmasi password anda');
            return false;
        }
        if (password !== confirmPassword) {
            this.showError('confirm_password', 'Password tidak cocok');
            return false;
        }
        this.clearError('confirm_password');
        return true;
    }
    
    showError(field, message) {
        const formGroup = document.getElementById(field).closest('.form-group');
        const errorElement = document.getElementById(`${field}Error`);
        
        formGroup.classList.add('error');
        errorElement.textContent = message;
        errorElement.classList.add('show');
        
        const input = document.getElementById(field);
        input.style.animation = 'materialShake 0.4s ease-in-out';
        setTimeout(() => { input.style.animation = ''; }, 400);
    }
    
    clearError(field) {
        const formGroup = document.getElementById(field).closest('.form-group');
        const errorElement = document.getElementById(`${field}Error`);
        
        formGroup.classList.remove('error');
        errorElement.classList.remove('show');
        setTimeout(() => { errorElement.textContent = ''; }, 200);
    }
    
    handleSubmit(e) {
        // Validasi semua field
        const isNamaValid = this.validateNama();
        const isUsernameValid = this.validateUsername();
        const isEmailValid = this.validateEmail();
        const isPasswordValid = this.validatePassword();
        const isConfirmPasswordValid = this.validateConfirmPassword();
        
        // Jika ada satu saja yang tidak valid, hentikan submit
        if (!isNamaValid || !isUsernameValid || !isEmailValid || !isPasswordValid || !isConfirmPasswordValid) {
            e.preventDefault(); // HENTIKAN SUBMIT
            this.submitButton.style.animation = 'materialPulse 0.3s ease';
            setTimeout(() => { this.submitButton.style.animation = ''; }, 300);
            return;
        }
        
        // Jika semua valid, tampilkan loading dan biarkan form terkirim ke PHP
        this.setLoading(true);
    }
    
    setLoading(loading) {
        this.submitButton.classList.toggle('loading', loading);
        this.submitButton.disabled = loading;
    }
}


/*
=========================================
 INISIALISASI (PENTING)
=========================================
*/
document.addEventListener('DOMContentLoaded', () => {
    // Cek halaman mana yang sedang aktif, lalu jalankan script yang sesuai
    
    if (document.getElementById('loginForm')) {
        new MaterialLoginForm();
    }
    
    if (document.getElementById('registerForm')) {
        new MaterialRegisterForm();
    }

    // TAMBAHKAN INI
    if (document.getElementById('resetForm')) {
        new MaterialResetForm();
    }
});

/*
=========================================
 KELAS UNTUK HALAMAN RESET (reset-password.php)
=========================================
*/
class MaterialResetForm {
    constructor() {
        this.form = document.getElementById('resetForm');
        this.passwordInput = document.getElementById('password');
        this.confirmPasswordInput = document.getElementById('confirm_password');
        
        this.passwordToggle = document.getElementById('passwordToggle');
        this.confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        
        this.submitButton = this.form.querySelector('.material-btn');
        
        // Cek jika elemen ada sebelum lanjut
        if (!this.form || !this.passwordInput || !this.confirmPasswordInput) {
            console.error("Form reset password tidak ditemukan atau field hilang.");
            return;
        }

        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPasswordToggles();
        this.setupRippleEffects();
    }
    
    bindEvents() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        this.passwordInput.addEventListener('blur', () => this.validatePassword());
        this.confirmPasswordInput.addEventListener('blur', () => this.validateConfirmPassword());

        this.passwordInput.addEventListener('input', () => this.clearError('password'));
        this.confirmPasswordInput.addEventListener('input', () => this.clearError('confirm_password'));
        
        [this.passwordInput, this.confirmPasswordInput].forEach(input => {
            input.addEventListener('focus', (e) => this.handleInputFocus(e));
            input.addEventListener('blur', (e) => this.handleInputBlur(e));
        });
    }
    
    setupPasswordToggles() {
        if (this.passwordToggle) {
            this.passwordToggle.addEventListener('click', (e) => {
                const type = this.passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                this.passwordInput.setAttribute('type', type);
            });
        }
        
        if (this.confirmPasswordToggle) {
            this.confirmPasswordToggle.addEventListener('click', (e) => {
                const type = this.confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                this.confirmPasswordInput.setAttribute('type', type);
            });
        }
    }
    
    setupRippleEffects() {
        [this.passwordInput, this.confirmPasswordInput].forEach(input => {
            input.addEventListener('focus', (e) => {
                const rippleContainer = input.parentNode.querySelector('.ripple-container');
                if(rippleContainer) this.createRipple(e, rippleContainer);
            });
        });
        if (this.submitButton) {
            this.submitButton.addEventListener('click', (e) => {
                const rippleContainer = this.submitButton.querySelector('.btn-ripple');
                if(rippleContainer) this.createRipple(e, rippleContainer);
            });
        }
    }

    createRipple(event, container) {
        const rect = container.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('div');
        ripple.className = 'ripple';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        
        container.appendChild(ripple);
        setTimeout(() => { ripple.remove(); }, 600);
    }

    handleInputFocus(e) { e.target.closest('.input-wrapper').classList.add('focused'); }
    handleInputBlur(e) { e.target.closest('.input-wrapper').classList.remove('focused'); }

    validatePassword() {
        const password = this.passwordInput.value;
        if (!password) {
            this.showError('password', 'Password is required');
            return false;
        }
        if (password.length < 6) {
            this.showError('password', 'Password must be at least 6 characters');
            return false;
        }
        this.clearError('password');
        return true;
    }

    validateConfirmPassword() {
        const password = this.passwordInput.value;
        const confirmPassword = this.confirmPasswordInput.value;
        if (!confirmPassword) {
            this.showError('confirm_password', 'Please confirm your password');
            return false;
        }
        if (password !== confirmPassword) {
            this.showError('confirm_password', 'Passwords do not match');
            return false;
        }
        this.clearError('confirm_password');
        return true;
    }
    
    showError(field, message) {
        const el = document.getElementById(field);
        if (!el) return;
        const formGroup = el.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message'); // Diperbarui
        
        formGroup.classList.add('error');
        if(errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
        
        el.style.animation = 'materialShake 0.4s ease-in-out';
        setTimeout(() => { el.style.animation = ''; }, 400);
    }
    
    clearError(field) {
        const el = document.getElementById(field);
        if (!el) return;
        const formGroup = el.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message'); // Diperbarui
        
        formGroup.classList.remove('error');
        if(errorElement) {
            errorElement.classList.remove('show');
            setTimeout(() => { errorElement.textContent = ''; }, 200);
        }
    }
    
    handleSubmit(e) {
        const isPasswordValid = this.validatePassword();
        const isConfirmPasswordValid = this.validateConfirmPassword();
        
        if (!isPasswordValid || !isConfirmPasswordValid) {
            e.preventDefault(); 
            this.submitButton.style.animation = 'materialPulse 0.3s ease';
            setTimeout(() => { this.submitButton.style.animation = ''; }, 300);
            return;
        }
        this.setLoading(true);
    }
    
    setLoading(loading) {
        this.submitButton.classList.toggle('loading', loading);
        this.submitButton.disabled = loading;
    }
}