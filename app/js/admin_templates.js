// Initialize Lucide Icons
if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

// --- STATE MANAGEMENT ---
// Store original values to detect changes
let originalProfile = {
    name: "",
    phone: "",
    email: "",
    avatarSrc: "",      // current img.src (may be data: or full URL)
    serverAvatarPath: "", // server-side path stored in DB, e.g. "uploads/avatars/xxx.png"
    hasEmail: false
};

// --- Helpers ---
function safeEl(id) { return document.getElementById(id); }

// Phone normalization + validation (same logic as server register.php)
function normalizePhoneToPlus60(phone) {
    if (!phone) return null;
    // Remove everything except digits and plus
    let p = phone.replace(/[^\d\+]/g, '');
    let digits = p.replace(/^\+/, '');
    if (digits.startsWith('60')) {
        return '+' + digits;
    }
    if (digits.startsWith('0')) {
        return '+60' + digits.substring(1);
    }
    if (/^1[0-9]{7,8}$/.test(digits)) {
        return '+60' + digits;
    }
    return null;
}

function isValidMYPhonePlus60(phonePlus) {
    if (!phonePlus) return false;
    const digits = phonePlus.replace(/^\+/, '');
    return /^60(1[0-9]{7,8})$/.test(digits);
}

function normalizeServerAvatarPath(path) {
    if (!path) return "";
    // Strip leading ../ or / or full origin
    return path.replace(/^(\.\.\/|\/|https?:\/\/[^\/]+\/)/, "");
}

function ensureInitSettingsElements() {
    // returns false if settings DOM not present
    return !!(safeEl('display-fullname') && safeEl('display-phone') && safeEl('settings-avatar-img') && safeEl('display-email-filled') && safeEl('display-email-value'));
}

// helper to compute correct URL for server-returned asset (assumes file under app/)
function computeRelativeUrlToAppServer(serverPath) {
    // If serverPath already looks like an absolute URL, return as-is
    if (!serverPath) return "";
    if (/^https?:\/\//i.test(serverPath)) return serverPath;
    // Normalize to path from current file; admin pages live in app/templates/admin/ so prepend ../../ if needed
    // Our previous convention was to reference images as "../" from admin_dashboard.php -> we want a working URL.
    // Simpler: rely on a root-relative path if server returns without leading slash: prefix with '/app/' if you serve app at /app/
    // But to be robust, try two common options:
    if (serverPath.startsWith('uploads/') || serverPath.startsWith('images/') || serverPath.startsWith('app/')) {
        // prefer relative path from admin_dashboard.php: '../../' to go up from app/templates/admin to app/
        return `../../${serverPath}`.replace(/\/{2,}/g,'/');
    }
    // fallback
    return `../../${serverPath}`.replace(/\/{2,}/g,'/');
}

// --- Initialization (settings) ---
function initSettings(){
    if (!ensureInitSettingsElements()) return;

    const nameEl = safeEl('display-fullname');
    const phoneEl = safeEl('display-phone');
    const emailValueEl = safeEl('display-email-value');
    const emailFilledEl = safeEl('display-email-filled');
    const imgEl = safeEl('settings-avatar-img');

    originalProfile.name = nameEl.value || "";
    // normalize displayed phone into +60 form for consistent comparison
    const rawPhone = phoneEl.value || "";
    const normalized = normalizePhoneToPlus60(rawPhone) || rawPhone;
    originalProfile.phone = normalized;
    // Update the DOM phone input to normalized short form (without +60 displayed separately in UI you have +60 prefix)
    // NOTE: your displayed phone value is without +60 in your markup; keep it that way for inputs, but we store normalized
    originalProfile.email = (emailValueEl && emailValueEl.value) ? emailValueEl.value : "";
    originalProfile.hasEmail = !emailFilledEl.classList.contains('hidden');

    // server-side avatar path: read from data attribute if author rendered it (recommended)
    // <img id="settings-avatar-img" data-profile="uploads/avatars/xxx.jpg" ...>
    const serverPath = (imgEl && imgEl.dataset && imgEl.dataset.profile) ? imgEl.dataset.profile : "";
    originalProfile.serverAvatarPath = normalizeServerAvatarPath(serverPath);

    // avatarSrc: the actual img.src if visible and not empty
    let src = "";
    if (imgEl && !imgEl.classList.contains('hidden') && imgEl.src) {
        src = imgEl.src;
    }
    originalProfile.avatarSrc = src || "";
}

// --- Change Detection ---
function checkForChanges(){
    // Always try to show/hide action bar even if settings elements missing
    const actionContainer = safeEl('settings-actions');
    if (!actionContainer) return;

    if (!ensureInitSettingsElements()) {
        // If settings DOM not present, hide action bar
        actionContainer.classList.add('hidden');
        actionContainer.classList.remove('flex');
        return;
    }

    const currentName = safeEl('display-fullname').value || "";
    const rawPhone = safeEl('display-phone').value || "";
    const currentPhoneNormalized = normalizePhoneToPlus60(rawPhone) || rawPhone;
    const currentEmail = safeEl('display-email-value').value || "";
    const currentHasEmail = !safeEl('display-email-filled').classList.contains('hidden');
    const imgEl = safeEl('settings-avatar-img');
    const currentImgSrc = (imgEl && imgEl.src) ? imgEl.src : "";

    let hasChanges = false;
    if (currentName !== originalProfile.name) hasChanges = true;
    if (currentPhoneNormalized !== originalProfile.phone) hasChanges = true;

    if (currentHasEmail !== originalProfile.hasEmail) hasChanges = true;
    else if (currentHasEmail && currentEmail !== originalProfile.email) hasChanges = true;

    // Avatar detection:
    // - changed if currentImgSrc startsWith('data:') (new cropped image)
    // - OR if originalProfile.serverAvatarPath is empty but currentImgSrc is not empty (new upload)
    // - OR if originalProfile.serverAvatarPath exists but currentImgSrc doesn't include it (i.e. user selected a different file)
    if (currentImgSrc !== originalProfile.avatarSrc) {
        if (currentImgSrc.startsWith('data:')) {
            hasChanges = true;
        } else {
            const normalizedServer = originalProfile.serverAvatarPath;
            if (!normalizedServer && currentImgSrc) {
                hasChanges = true;
            } else if (normalizedServer) {
                // If server path present, check whether currentImgSrc includes the server path
                try {
                    // currentImgSrc might be absolute URL; check substring
                    if (currentImgSrc.indexOf(normalizedServer) === -1) {
                        hasChanges = true;
                    }
                } catch(e){ hasChanges = true; }
            }
        }
    }

    if (hasChanges) {
        actionContainer.classList.remove('hidden');
        actionContainer.classList.add('flex');
    } else {
        actionContainer.classList.add('hidden');
        actionContainer.classList.remove('flex');
    }
}

// --- Global Save / Cancel ---
// --- Save (POST to API) ---
function saveSettings(){
    if (!ensureInitSettingsElements()) {
        showSuccessModal('Error','Settings UI not found.');
        return;
    }

    const full_name = (safeEl('display-fullname').value || "").trim();
    const rawPhone = (safeEl('display-phone').value || "").trim();
    const phone_normalized = normalizePhoneToPlus60(rawPhone) || rawPhone;
    const emailFilled = !safeEl('display-email-filled').classList.contains('hidden');
    const email = emailFilled ? (safeEl('display-email-value').value || "").trim() : "";

    // Basic client-side validation
    if (!full_name) {
        showSuccessModal('Error','Full name cannot be empty.','fa-solid fa-circle-exclamation text-red-500'); return;
    }
    if (!phone_normalized || !isValidMYPhonePlus60(phone_normalized)) {
        showSuccessModal('Error','Please enter a valid Malaysian phone number.','fa-solid fa-circle-exclamation text-red-500'); return;
    }

    const imgEl = safeEl('settings-avatar-img');
    const avatarChanged = imgEl && imgEl.src && imgEl.src.startsWith('data:');
    const removeAvatar = (!imgEl || imgEl.classList.contains('hidden')) && !!originalProfile.serverAvatarPath; // user removed avatar

    const form = new FormData();
    form.append('full_name', full_name);
    form.append('phone', phone_normalized);
    form.append('email', email);
    form.append('avatar_changed', avatarChanged ? '1' : '0');
    form.append('remove_avatar', removeAvatar ? '1' : '0');
    // include original server-side avatar path (so server can delete it if replaced)
    form.append('original_avatar', originalProfile.serverAvatarPath || '');

    if (avatarChanged) {
        // send dataURL; server should handle dataURL decoding and saving
        form.append('avatar', imgEl.src);
    }

    // Disable buttons while saving
    const actionContainer = safeEl('settings-actions');
    const saveBtn = actionContainer ? actionContainer.querySelector('button[onclick*="saveSettings"]') : null;
    const cancelBtn = actionContainer ? actionContainer.querySelector('button[onclick*="cancelSettings"]') : null;
    if (saveBtn) saveBtn.disabled = true;
    if (cancelBtn) cancelBtn.disabled = true;

    fetch('../api/update_profile.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: form
    })
    .then(async (r) => {
        // Try parse JSON; if parse fails return helpful error
        let json;
        try { json = await r.json(); } catch(e) {
            throw new Error('Invalid JSON response from server.');
        }
        if (!json || !json.success) {
            const err = (json && (json.error || (json.errors && json.errors.join(', ')))) || 'Unknown error';
            throw new Error(err);
        }
        // SUCCESS: update originalProfile and UI
        originalProfile.name = full_name;
        originalProfile.phone = phone_normalized;
        originalProfile.email = email;
        originalProfile.hasEmail = !!email;

        // server may return profile_photo path (relative) — normalize it
        if (json.user && json.user.profile_photo) {
            const serverPath = normalizeServerAvatarPath(json.user.profile_photo);
            originalProfile.serverAvatarPath = serverPath;
            // set avatarSrc to full URL for display consistency
            const avatarFull = (serverPath) ? computeRelativeUrlToAppServer(serverPath) : "";
            originalProfile.avatarSrc = avatarFull || "";
            // update DOM img and data-profile attribute
            if (imgEl) {
                if (avatarFull) {
                    imgEl.src = avatarFull;
                    imgEl.dataset.profile = serverPath;
                    imgEl.classList.remove('hidden');
                    const initialsEl = safeEl('settings-avatar-initials'); if (initialsEl) initialsEl.classList.add('hidden');
                } else {
                    imgEl.src = '';
                    imgEl.classList.add('hidden');
                    const initialsEl = safeEl('settings-avatar-initials'); if (initialsEl) initialsEl.classList.remove('hidden');
                }
            }
            // update header avatar (if present)
            const headerAvatar = safeEl('header-avatar');
            if (headerAvatar) {
                if (avatarFull) headerAvatar.innerHTML = `<img src="${avatarFull}" class="w-full h-full object-cover">`;
                else headerAvatar.innerHTML = `<span class="text-sm">${(safeEl('settings-avatar-initials') || {textContent:''}).textContent}</span>`;
            }
        } else {
            // no profile_photo returned: keep previous or clear if removeAvatar
            if (removeAvatar) {
                originalProfile.serverAvatarPath = "";
                originalProfile.avatarSrc = "";
                if (imgEl) { imgEl.src = ''; imgEl.classList.add('hidden'); }
                const initialsEl = safeEl('settings-avatar-initials'); if (initialsEl) initialsEl.classList.remove('hidden');
                const headerAvatar = safeEl('header-avatar'); if (headerAvatar) headerAvatar.innerHTML = `<span class="text-sm">${(safeEl('settings-avatar-initials') || {textContent:''}).textContent}</span>`;
            } else {
                // If server didn't return anything, try to preserve what we already had
                originalProfile.avatarSrc = imgEl && imgEl.src ? imgEl.src : originalProfile.avatarSrc;
            }
        }

        // hide action bar explicitly to fix "sticky" bug
        if (actionContainer) {
            actionContainer.classList.add('hidden');
            actionContainer.classList.remove('flex');
        }

        // Re-enable buttons
        if (saveBtn) saveBtn.disabled = false;
        if (cancelBtn) cancelBtn.disabled = false;

        showSuccessModal('Profile Updated','Your profile changes have been saved.');
    })
    .catch(err => {
        console.error('Save failed', err);
        if (actionContainer) {
            if (saveBtn) saveBtn.disabled = false;
            if (cancelBtn) cancelBtn.disabled = false;
        }
        showSuccessModal('Error', String(err.message || err), 'fa-solid fa-circle-exclamation text-red-500');
    });
}

// --- Cancel (revert) ---
function cancelSettings(){
    if (!ensureInitSettingsElements()) return;
    safeEl('display-fullname').value = originalProfile.name || "";
    // When showing phone input, strip +60 prefix for UI if you already display +60 separately; keep raw
    const displayedPhone = (originalProfile.phone || "").replace(/^\+60/, '');
    safeEl('display-phone').value = displayedPhone;

    if (originalProfile.hasEmail) {
        safeEl('display-email-value').value = originalProfile.email || "";
        safeEl('display-email-empty').classList.add('hidden');
        safeEl('display-email-filled').classList.remove('hidden');
        const secBtn = safeEl('security-email-btn'); if (secBtn) secBtn.textContent = 'Change';
    } else {
        safeEl('display-email-value').value = '';
        safeEl('display-email-empty').classList.remove('hidden');
        safeEl('display-email-filled').classList.add('hidden');
        const secBtn = safeEl('security-email-btn'); if (secBtn) secBtn.textContent = 'Add';
    }

    const img = safeEl('settings-avatar-img');
    const initials = safeEl('settings-avatar-initials');
    if (originalProfile.serverAvatarPath) {
        const avatarFull = computeRelativeUrlToAppServer(originalProfile.serverAvatarPath);
        if (img) { img.src = avatarFull; img.dataset.profile = originalProfile.serverAvatarPath; img.classList.remove('hidden'); }
        if (initials) initials.classList.add('hidden');
        const headerAvatar = safeEl('header-avatar'); if (headerAvatar) headerAvatar.innerHTML = `<img src="${avatarFull}" class="w-full h-full object-cover">`;
    } else {
        if (img) { img.src = ''; img.classList.add('hidden'); }
        if (initials) initials.classList.remove('hidden');
        const headerAvatar = safeEl('header-avatar'); if (headerAvatar) headerAvatar.innerHTML = `<span class="text-sm">${initials ? initials.textContent : ''}</span>`;
    }

    // hide action bar explicitly
    const actionContainer = safeEl('settings-actions');
    if (actionContainer) {
        actionContainer.classList.add('hidden'); actionContainer.classList.remove('flex');
    }
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
    const input = safeEl('profile-upload');
    if (input) input.click();
}

function handleFileSelect(input) {
    if (!input || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = safeEl('crop-image-target');
        img.src = e.target.result;
        safeEl('crop-modal').classList.remove('hidden');
        safeEl('crop-modal').classList.add('flex');

        if (cropper) { cropper.destroy(); cropper = null; }
        cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 0.8
        });
    };
    reader.readAsDataURL(input.files[0]);
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

function closeCropModal() {
    if (cropper) { cropper.destroy(); cropper = null; }
    const cm = safeEl('crop-modal');
    if (cm) { cm.classList.add('hidden'); cm.classList.remove('flex'); }
    const input = safeEl('profile-upload');
    if (input) input.value = '';
}

function confirmCrop() {
    if (!cropper) return;

    // 1. Get the cropped image data
    const canvas = cropper.getCroppedCanvas({ width: 300, height: 300 });
    const dataUrl = canvas.toDataURL('image/png');

    // 2. Select BOTH elements by their UNIQUE IDs
    const settingsImg = document.getElementById('settings-avatar-img');
    const settingsInitials = document.getElementById('settings-avatar-initials');
    
    const headerImg = document.getElementById('header-avatar-img'); // New ID
    const headerContainer = document.getElementById('header-avatar');

    // 3. Update the Settings Page Image
    if (settingsImg) {
        settingsImg.src = dataUrl;
        settingsImg.classList.remove('hidden');
    }
    if (settingsInitials) {
        settingsInitials.classList.add('hidden');
    }

    // 4. Update the Header Image
    if (headerImg) {
        headerImg.src = dataUrl;
        headerImg.classList.remove('hidden');
        
        // Ensure header text/initials are hidden if we are showing an image
        // (Assuming the header structure usually has a span next to the img)
        if (headerContainer) {
            const span = headerContainer.querySelector('span');
            if (span) span.classList.add('hidden');
        }
    }

    // 5. Cleanup
    closeCropModal();
    checkForChanges(); // This shows the "Save" bar
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

// --- Password change (client-side strength check + POST) ---
function isStrongPassword(pw){
    if (!pw) return false;
    // same pattern as register.php: min 8 chars, 1 uppercase, 1 number, 1 special
    return /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(pw);
}
function savePassword(){
    const newPass = (safeEl('new-password') && safeEl('new-password').value) || '';
    const confirmPass = (safeEl('confirm-password') && safeEl('confirm-password').value) || '';
    if (!newPass || !confirmPass) { showSuccessModal('Error','Please fill in both fields.','fa-solid fa-circle-exclamation text-red-500'); return; }
    if (newPass !== confirmPass) { showSuccessModal('Error','Passwords do not match.','fa-solid fa-circle-exclamation text-red-500'); return; }
    if (!isStrongPassword(newPass)) { showSuccessModal('Error','Password must be at least 8 characters, include an uppercase letter, a number and a special character.','fa-solid fa-circle-exclamation text-red-500'); return; }

    // POST to API endpoint change_password.php (server must implement)
    const form = new FormData();
    form.append('new_password', newPass);

    fetch('../api/change_password.php', { method:'POST', credentials:'same-origin', body: form })
    .then(r => r.json())
    .then(json => {
        if (!json || !json.success) throw new Error((json && (json.error || json.message)) || 'Failed to change password');
        // clear inputs and close modal
        if (safeEl('new-password')) safeEl('new-password').value = '';
        if (safeEl('confirm-password')) safeEl('confirm-password').value = '';
        closeModalById('password-modal');
        showSuccessModal('Password Changed','Your password has been updated securely.');
    })
    .catch(err => {
        console.error('Password change failed',err);
        showSuccessModal('Error', String(err.message || err),'fa-solid fa-circle-exclamation text-red-500');
    });
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

// --- Expose to global for inline handlers ---
window.switchTab = switchTab;
window.initSettings = initSettings;
window.checkForChanges = checkForChanges;
window.saveSettings = saveSettings;
window.cancelSettings = cancelSettings;
window.openModal = openModal;
window.closeModalById = closeModalById;
window.triggerPhotoUpload = triggerPhotoUpload;
window.handleFileSelect = handleFileSelect;
window.closeCropModal = closeCropModal;
window.confirmCrop = confirmCrop;
window.confirmLogout = confirmLogout;
window.saveNameDraft = saveNameDraft;
window.savePhoneDraft = savePhoneDraft;
window.saveEmailDraft = saveEmailDraft;
window.savePassword = savePassword;
window.showSuccessModal = showSuccessModal;
window.closeSuccessModal = closeSuccessModal;

// --- Auto-bind simple input listeners on DOMContentLoaded to detect changes ---
document.addEventListener('DOMContentLoaded', () => {
    // cache dashboard snapshot if exists (original behavior)
    const pageContent = safeEl('page-content');
    if (pageContent && typeof _adminPageCache !== 'undefined') _adminPageCache.set('dashboard', pageContent.innerHTML);

    // bind inputs if settings are present
    if (ensureInitSettingsElements()) {
        // normalize displayed phone value so checkForChanges compares normalized values
        const phoneInput = safeEl('display-phone');
        if (phoneInput) {
            // don't overwrite user's typed format in UI; we normalize internally on save/check only
            phoneInput.addEventListener('input', () => { checkForChanges(); });
        }
        const nameInput = safeEl('display-fullname'); if (nameInput) nameInput.addEventListener('input', checkForChanges);
        const emailInput = safeEl('display-email-value'); if (emailInput) emailInput.addEventListener('input', checkForChanges);
        // settings-avatar-img not an input; confirmCrop triggers checkForChanges after change
        initSettings();
        // initial check to ensure action bar hidden/shown correctly
        checkForChanges();
    }
});