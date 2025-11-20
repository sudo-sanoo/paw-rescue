// --- Navbar Logic ---
function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
}

function closeMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    if (!menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }
}

// --- Slider Logic ---
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function updateSlides() {
    slides.forEach((slide, index) => {
        if (index === currentSlide) {
            slide.classList.add('active');
        } else {
            slide.classList.remove('active');
        }
    });
    
    dots.forEach((dot, index) => {
        if (index === currentSlide) {
            dot.classList.remove('bg-gray-800');
            dot.classList.add('bg-brand');
        } else {
            dot.classList.add('bg-gray-800');
            dot.classList.remove('bg-brand');
        }
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateSlides();
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateSlides();
}

function goToSlide(n) {
    currentSlide = n;
    updateSlides();
}

// Auto advance slider
let slideInterval = setInterval(nextSlide, 5000);

// Pause interval on user interaction
slides.forEach(slide => {
    slide.addEventListener('mouseenter', () => clearInterval(slideInterval));
    slide.addEventListener('mouseleave', () => slideInterval = setInterval(nextSlide, 5000));
});

// --- Auth Toggle Logic ---
function toggleAuth(type) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const switchToRegBtn = document.getElementById('switch-to-register');
    const switchToLoginBtn = document.getElementById('switch-to-login');
    const mobLoginTab = document.getElementById('mob-login-tab');
    const mobRegisterTab = document.getElementById('mob-register-tab');

    if (type === 'register') {
        // Show Register
        loginForm.style.opacity = '0';
        loginForm.style.pointerEvents = 'none';
        setTimeout(() => { 
            loginForm.style.visibility = 'hidden'; 
            registerForm.style.visibility = 'visible';
            registerForm.style.opacity = '1';
            registerForm.style.pointerEvents = 'all';
        }, 300);
        
        // Button visibility
        if(switchToRegBtn) switchToRegBtn.style.display = 'none';
        if(switchToLoginBtn) {
            switchToLoginBtn.style.display = 'block';
            switchToLoginBtn.classList.remove('opacity-50', 'hidden');
        }

        // Mobile tabs
        if(mobRegisterTab) {
            mobRegisterTab.classList.add('text-brand', 'border-b-2', 'border-brand');
            mobRegisterTab.classList.remove('text-gray-400');
            mobLoginTab.classList.remove('text-brand', 'border-b-2', 'border-brand');
            mobLoginTab.classList.add('text-gray-400');
        }

    } else {
        // Show Login
        registerForm.style.opacity = '0';
        registerForm.style.pointerEvents = 'none';
        setTimeout(() => { 
            registerForm.style.visibility = 'hidden';
            loginForm.style.visibility = 'visible';
            loginForm.style.opacity = '1';
            loginForm.style.pointerEvents = 'all';
        }, 300);

        // Button visibility
        if(switchToLoginBtn) switchToLoginBtn.style.display = 'none';
        if(switchToRegBtn) {
            switchToRegBtn.style.display = 'block';
        }

        // Mobile tabs
        if(mobLoginTab) {
            mobLoginTab.classList.add('text-brand', 'border-b-2', 'border-brand');
            mobLoginTab.classList.remove('text-gray-400');
            mobRegisterTab.classList.remove('text-brand', 'border-b-2', 'border-brand');
            mobRegisterTab.classList.add('text-gray-400');
        }
    }
}

    // --- Custom Modal Logic (Replacing alert()) ---
const modal = document.getElementById('custom-modal');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalIcon = document.getElementById('modal-icon');

/**
 * Displays the custom modal.
 * @param {string} title The title of the message.
 * @param {string} message The body text of the message.
 * @param {string} iconClass FontAwesome class for the icon (e.g., 'fa-solid fa-check-circle text-brand').
 */
function showModal(title, message, iconClass = 'fa-solid fa-check-circle text-brand') {
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Reset and apply new icon class
    modalIcon.className = ''; 
    modalIcon.className = iconClass + ' text-5xl mb-4 animate-pulse-slow';
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// --- Form Submission Handler (Demo) ---
function handleAuthSubmission(type) {
    if (type === 'login') {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        
        if (email && password) {
            showModal('Login Successful (Demo)', `Logged in as ${email}. Welcome to PawRescue!`, 'fa-solid fa-heart text-brand');
        } else {
            showModal('Input Error', 'Please enter both email and password.', 'fa-solid fa-circle-exclamation text-red-500');
        }
    } else if (type === 'register') {
        const name = document.getElementById('register-name').value;
        const email = document.getElementById('register-email').value;
        const password = document.getElementById('register-password').value;
        
        if (name && email && password) {
            showModal('Registration Successful (Demo)', `Thank you, ${name}! Your account is created.`, 'fa-solid fa-hand-holding-heart text-brand-accent');
        } else {
            showModal('Input Error', 'Please fill out all required fields.', 'fa-solid fa-circle-exclamation text-red-500');
        }
    }
}

// Initialize the app state
window.onload = () => {
    updateSlides(); // Ensure first slide is active on load
    // Set up initial mobile tab state (Login active)
    toggleAuth('login');
};