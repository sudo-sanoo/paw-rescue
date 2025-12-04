// Initialize Lucide Icons
lucide.createIcons();

// --- STATE MANAGEMENT ---
// Store original values to detect changes
let originalProfile = {
    name: "Demo Name",
    phone: "+60123456789",
    email: "", // Empty initially
    avatarSrc: "",
    hasEmail: false
};


// --- Initialization ---
function initSettings() {
    // defensive: only run if the settings DOM exists
    const nameEl = document.getElementById('display-fullname');
    const phoneEl = document.getElementById('display-phone');
    const emailFilledEl = document.getElementById('display-email-filled');
    const emailValueEl = document.getElementById('display-email-value');
    const imgEl = document.getElementById('settings-avatar-img');

    // If any essential element is missing, bail out silently (we're likely not on settings page)
    if (!nameEl || !phoneEl || !emailFilledEl || !imgEl) {
        return;
    }

    originalProfile.name = nameEl.value || "";
    originalProfile.phone = phoneEl.value || "";

    const emailVal = (emailValueEl && emailValueEl.value) ? emailValueEl.value : "";
    if (emailVal && emailVal.trim() !== "") {
        originalProfile.email = emailVal;
        originalProfile.hasEmail = true;
    } else {
        originalProfile.email = "";
        originalProfile.hasEmail = false;
    }

    // Capture avatar source (may be empty)
    originalProfile.avatarSrc = imgEl.src || "";
}

// --- Change Detection ---
function checkForChanges() {
    const currentName = document.getElementById('display-fullname').value;
    const currentPhone = document.getElementById('display-phone').value;
    const currentEmail = document.getElementById('display-email-value').value;
    const currentImg = document.getElementById('settings-avatar-img').src;
    
    // Determine current email "existence" state
    const currentHasEmail = !document.getElementById('display-email-filled').classList.contains('hidden');

    let hasChanges = false;

    if (currentName !== originalProfile.name) hasChanges = true;
    if (currentPhone !== originalProfile.phone) hasChanges = true;
    
    // Email Logic: 
    // 1. If original had email but now hidden (removed) -> change
    // 2. If original didn't have email but now has (added) -> change
    // 3. If both have email, but values differ -> change
    if (currentHasEmail !== originalProfile.hasEmail) {
        hasChanges = true;
    } else if (currentHasEmail && currentEmail !== originalProfile.email) {
        hasChanges = true;
    }

    // Image Logic
    // Compare src strings. Note: empty src might differ from "window.location..." in some browsers
    // but for dataURLs vs empty strings this works well enough for demo.
    if (currentImg !== originalProfile.avatarSrc && !(currentImg === window.location.href && originalProfile.avatarSrc === "")) {
            // Check if actually changed (Crop generates DataURL)
            if (currentImg.startsWith('data:')) {
                hasChanges = true;
            }
    }

    const actionContainer = document.getElementById('settings-actions');
    if (hasChanges) {
        actionContainer.classList.remove('hidden');
        actionContainer.classList.add('flex');
    } else {
        actionContainer.classList.add('hidden');
        actionContainer.classList.remove('flex');
    }
}

// --- Global Save / Cancel ---
function saveSettings() {
    // Commit changes: Update "Original" state to match "Current" DOM
    originalProfile.name = document.getElementById('display-fullname').value;
    originalProfile.phone = document.getElementById('display-phone').value;
    
    const emailContainerHidden = document.getElementById('display-email-filled').classList.contains('hidden');
    if (!emailContainerHidden) {
        originalProfile.email = document.getElementById('display-email-value').value;
        originalProfile.hasEmail = true;
    } else {
        originalProfile.email = "";
        originalProfile.hasEmail = false;
    }
    
    originalProfile.avatarSrc = document.getElementById('settings-avatar-img').src;

    // Hide Action Bar
    checkForChanges(); 
    
    showSuccessModal('Profile Updated', 'All changes have been saved successfully.');
}

function cancelSettings() {
    // Revert DOM to Original State
    document.getElementById('display-fullname').value = originalProfile.name;
    document.getElementById('display-phone').value = originalProfile.phone;

    // Revert Email State
    if (originalProfile.hasEmail) {
        document.getElementById('display-email-value').value = originalProfile.email;
        document.getElementById('display-email-empty').classList.add('hidden');
        document.getElementById('display-email-filled').classList.remove('hidden');
        document.getElementById('security-email-btn').textContent = "Change Email";
    } else {
        document.getElementById('display-email-value').value = "";
        document.getElementById('display-email-empty').classList.remove('hidden');
        document.getElementById('display-email-filled').classList.add('hidden');
        document.getElementById('security-email-btn').textContent = "Add Email";
    }

    // Revert Image
    const img = document.getElementById('settings-avatar-img');
    const initials = document.getElementById('settings-avatar-initials');
    
    // Check if we have a valid image in original profile (e.g. not empty string)
    // For demo, if src is empty or matches page URL (default empty src behavior), show initials
    if (!originalProfile.avatarSrc || originalProfile.avatarSrc === window.location.href) {
        img.src = "";
        img.classList.add('hidden');
        initials.classList.remove('hidden');
    } else {
        img.src = originalProfile.avatarSrc;
        img.classList.remove('hidden');
        initials.classList.add('hidden');
        
        // Also update header
        document.getElementById('header-avatar').innerHTML = `<img src="${originalProfile.avatarSrc}" class="w-full h-full object-cover">`;
    }

    checkForChanges();
}

// UI Logic
const sidebar = document.getElementById('sidebar');
const mobileOverlay = document.getElementById('mobile-overlay');

function toggleMobileMenu() {
    sidebar.classList.toggle('-translate-x-full');
    mobileOverlay.classList.toggle('hidden');
}

function toggleDesktopSidebar() {
    sidebar.classList.toggle('sidebar-collapsed');
}

const _adminPageCache = new Map(); // caches fetched HTML by tabId
function afterPageLoad(tabId) {
    // Recreate lucide icons inside newly inserted HTML
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        try { lucide.createIcons(); } catch(e){ console.warn(e); }
    }

    // Show the content if hidden
    const view = document.getElementById('view-' + tabId);
    if(view) view.classList.remove('hidden');

    // Page-specific initializers
    if (tabId === 'settings' && typeof initSettings === 'function') {
        setTimeout(() => initSettings(), 0);
    }
}

function switchTab(tabId) {
    // 1) Update sidebar nav styling
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('bg-orange-50', 'text-orange-600', 'font-medium');
        el.classList.add('text-gray-600', 'hover:bg-gray-50', 'hover:text-gray-900');
    });

    const activeNav = document.getElementById('nav-' + tabId);
    if (activeNav) {
        activeNav.classList.remove('text-gray-600', 'hover:bg-gray-50', 'hover:text-gray-900');
        activeNav.classList.add('bg-orange-50', 'text-orange-600', 'font-medium');
    }

    const pageContent = document.getElementById('page-content');
    if (!pageContent) {
        console.error('page-content container not found!');
        return;
    }

    // Dashboard is default — do not AJAX-fetch it unless we cached and intentionally replaced it
    if (tabId === 'dashboard') {
        if (_adminPageCache.has('dashboard')) {
            pageContent.innerHTML = _adminPageCache.get('dashboard');
            afterPageLoad('dashboard');
        } else {
            // if we didn't cache dashboard earlier, do nothing (it's already shown)
        }
    } else {
        // If page cached, use it
        if (_adminPageCache.has(tabId)) {
            pageContent.innerHTML = _adminPageCache.get(tabId);
            afterPageLoad(tabId);
        } else {
            // Show loading indicator (keeps layout)
            pageContent.innerHTML = '<div class="py-20 text-center text-gray-500">Loading...</div>';

            // fetch path relative to current file: app/templates/admin/admin_<tab>.php
            const fetchPath = `../templates/admin/admin_${tabId}.php`;

            fetch(fetchPath, { credentials: 'same-origin' })
                .then(resp => {
                    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                    return resp.text();
                })
                .then(html => {
                    // insert HTML and cache it
                    pageContent.innerHTML = html;
                    _adminPageCache.set(tabId, html);
                    afterPageLoad(tabId);
                })
                .catch(err => {
                    console.error('Failed to load admin page:', err);
                    pageContent.innerHTML = `<div class="py-20 text-center text-red-500">Failed to load page (${tabId}).</div>`;
                });
        }
    }

    // Close mobile sidebar if open (mobile UX)
    if (sidebar && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
        toggleMobileMenu();
    }

    // Scroll to top of container for nicer UX
    if (pageContent) pageContent.scrollTop = 0;
}

function saveNotificationPreference() {
    console.log("Preference saved");
}

// --- Logout Logic ---
function confirmLogout() {
    window.location.href = '../auth/logout.php';
}

// --- 2. Profile Photo Logic (Updated for Cropper.js) ---
let cropper = null;

function triggerPhotoUpload() {
    document.getElementById('profile-upload').click();
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('crop-image-target');
            img.src = e.target.result;
            
            document.getElementById('crop-modal').classList.remove('hidden');
            document.getElementById('crop-modal').classList.add('flex');
            
            // Initialize Cropper
            if (cropper) {
                cropper.destroy();
            }
            cropper = new Cropper(img, {
                aspectRatio: 1, // Enforce Square
                viewMode: 1,    // Restrict crop box to image bounds
                autoCropArea: 0.8,
                dragMode: 'move',
                guides: true,
                center: true,
                highlight: false,
                background: false
            });
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function closeCropModal() {
    document.getElementById('crop-modal').classList.add('hidden');
    document.getElementById('crop-modal').classList.remove('flex');
    
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    document.getElementById('profile-upload').value = ''; // Reset input
}

function confirmCrop() {
    if (!cropper) return;
    
    // Get cropped result
    const src = cropper.getCroppedCanvas({
        width: 300, // Reasonable avatar size
        height: 300
    }).toDataURL();

    // Update UI (Display changes, but not saved yet)
    document.getElementById('settings-avatar-initials').classList.add('hidden');
    const settingsImg = document.getElementById('settings-avatar-img');
    settingsImg.src = src;
    settingsImg.classList.remove('hidden');
    
    // Note: Header avatar usually updates on final save in real apps, 
    // but prompt says "display still changes" so we update UI immediately for feedback.
    const headerAvatar = document.getElementById('header-avatar');
    headerAvatar.innerHTML = `<img src="${src}" class="w-full h-full object-cover">`;
    
    closeCropModal();
    checkForChanges(); // Trigger "Save Changes" button
}

// --- Generic Modal Logic ---
function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.getElementById(modalId).classList.add('flex');

    if (modalId === 'name-modal') {
        document.getElementById('modal-input-name').value = document.getElementById('display-fullname').value;
    } else if (modalId === 'phone-modal') {
        document.getElementById('modal-input-phone').value = document.getElementById('display-phone').value;
    } else if (modalId === 'email-modal') {
        const currentEmail = document.getElementById('display-email-value').value;
        document.getElementById('modal-input-email').value = currentEmail;
        if (currentEmail) {
            document.getElementById('email-modal-title').textContent = "Change Email Address";
        } else {
            document.getElementById('email-modal-title').textContent = "Add Email Address";
        }
    }
}

function closeModalById(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

// --- Saving Field Drafts (Updates UI only, shows Save Bar) ---
function saveNameDraft() {
    const newName = document.getElementById('modal-input-name').value;
    if (!newName.trim()) {
        showSuccessModal('Error', 'Name cannot be empty.', 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }
    document.getElementById('display-fullname').value = newName;
    closeModalById('name-modal');
    checkForChanges();
}

function savePhoneDraft() {
    const newPhone = document.getElementById('modal-input-phone').value;
    if (!newPhone.trim()) {
        showSuccessModal('Error', 'Phone number cannot be empty.', 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }
    document.getElementById('display-phone').value = newPhone;
    closeModalById('phone-modal');
    checkForChanges();
}

function saveEmailDraft() {
    const newEmail = document.getElementById('modal-input-email').value;
    if (!newEmail.trim()) {
        showSuccessModal('Error', 'Email cannot be empty.', 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }
    
    // Update UI
    document.getElementById('display-email-value').value = newEmail;
    
    // Switch display state
    document.getElementById('display-email-empty').classList.add('hidden');
    document.getElementById('display-email-filled').classList.remove('hidden');
    document.getElementById('security-email-btn').textContent = "Change";

    closeModalById('email-modal');
    checkForChanges();
}

// --- 4. Password Logic (Remains immediate as it usually requires backend auth) ---
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text'; 
        icon.setAttribute('data-lucide', 'eye'); 
    } else {
        input.type = 'password'; 
        icon.setAttribute('data-lucide', 'eye-off'); 
    }
    lucide.createIcons();
}

function savePassword() {
    const newPass = document.getElementById('new-password').value;
    const confirmPass = document.getElementById('confirm-password').value;

    if(!newPass || !confirmPass) {
        showSuccessModal("Error", "Please fill in both fields.", 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }

    if(newPass !== confirmPass) {
        showSuccessModal("Error", "Passwords do not match.", 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }

    document.getElementById('new-password').value = '';
    document.getElementById('confirm-password').value = '';

    closeModalById('password-modal');
    showSuccessModal('Password Changed', 'Your password has been updated securely.');
}

// --- lazy modal refs (so they don't throw when page doesn't have them yet) ---
let modal = null;
let modalTitle = null;
let modalMessage = null;
let modalIcon = null;

function ensureModalRefs() {
    if (!modal) modal = document.getElementById('custom-modal');
    if (!modalTitle) modalTitle = document.getElementById('modal-title');
    if (!modalMessage) modalMessage = document.getElementById('modal-message');
    if (!modalIcon) modalIcon = document.getElementById('modal-icon');
}

// updated showSuccessModal uses lazy refs
function showSuccessModal(title, message, iconClass = 'fa-solid fa-check-circle text-orange-500') {
    ensureModalRefs();
    if (!modal || !modalTitle || !modalMessage || !modalIcon) return; // fail-safe

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    modalIcon.className = '';
    modalIcon.className = iconClass + ' text-5xl mb-4 animate-pulse-slow';

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSuccessModal() {
    ensureModalRefs();
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}