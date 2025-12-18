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
    // Normalize to path from current file; member pages live in app/templates/member/ so prepend ../../ if needed
    // Our previous convention was to reference images as "../" from member_dashboard.php -> we want a working URL.
    // Simpler: rely on a root-relative path if server returns without leading slash: prefix with '/app/' if you serve app at /app/
    // But to be robust, try two common options:
    if (serverPath.startsWith('uploads/') || serverPath.startsWith('images/') || serverPath.startsWith('app/')) {
        // prefer relative path from member_dashboard.php: '../../' to go up from app/templates/member to app/
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

const _memberPageCache = new Map(); // caches fetched HTML by tabId
function afterPageLoad(tabId) {
    // Recreate lucide icons inside newly inserted HTML
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        try { lucide.createIcons(); } catch(e){ console.warn(e); }
    }

    // Show the content if hidden
    const view = document.getElementById('view-' + tabId);
    if(view) view.classList.remove('hidden');

    if (tabId === 'join') {
        // use setTimeout to ensure the DOM render is complete
        setTimeout(() => initSignaturePad(), 0);
    }

    if (tabId === 'report') {
        // use setTimeout to ensure the #google-map div is rendered and has height
        setTimeout(() => {
            if (typeof window.initReportPage === 'function') {
                window.initReportPage();
            } else {
                console.error("initReportPage() not found. Make sure member_report.js is included in member_dashboard.php");
            }
        }, 200);
    }

    // This triggers the Live Map whenever the user navigates to "Emergencies Now"
    if (tabId === 'rescuer_emergencies') {
        // We use a small timeout to ensure the DOM elements (<div id="live-tracking-map">) are ready
        setTimeout(() => {
            // Check if the loader function exists (it's in rescuer_emergencies.js)
            if (typeof window.loadLiveMissionMap === 'function') {
                window.loadLiveMissionMap(); 
            } else {
                console.warn("Live Map Loader not found. Is rescuer_emergencies.js loaded?");
            }
        }, 100);
    }

    // Page-specific initializers
    if (tabId === 'settings' && typeof initSettings === 'function') {
        setTimeout(() => initSettings(), 0);
    }
}

const tabTitles = {
    'dashboard': 'Home',
    'report':    'Report Emergency',
    'rescuer_emergencies': 'Emergencies Now',
    'activity':  'My Reports',
    'adopt':     'Adopt an Animal',
    'join':      'Rescuer Application',
    'settings':  'Settings',
    'vet_treatment_report': 'Treatment',
};

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

    const pageTitleEl = document.getElementById('page-title');
    if (pageTitleEl) {
        // Use the friendly title from the map, defaulting to 'Dashboard' if not found
        pageTitleEl.textContent = tabTitles[tabId] || 'Dashboard';
    } else {
        console.error('page-title container not found!');
    }

    const pageContent = document.getElementById('page-content');
    if (!pageContent) {
        console.error('page-content container not found!');
        return;
    }

    // Home is default — do not AJAX-fetch it unless we cached and intentionally replaced it
    if (tabId === 'dashboard') {
        if (_memberPageCache.has('dashboard')) {
            pageContent.innerHTML = _memberPageCache.get('dashboard');
            afterPageLoad('dashboard');
        } else {
            // if we didn't cache dashboard earlier, do nothing (it's already shown)
        }
    } else {
        // If page cached, use it
        if (_memberPageCache.has(tabId)) {
            pageContent.innerHTML = _memberPageCache.get(tabId);
            afterPageLoad(tabId);
        } else {
            // Show loading indicator (keeps layout)
            pageContent.innerHTML = '<div class="py-20 text-center text-gray-500">Loading...</div>';

            // fetch path relative to current file: app/templates/member/member_<tab>.php
            const fetchPath = `../templates/member/member_${tabId}.php`;

            fetch(fetchPath, { credentials: 'same-origin' })
                .then(resp => {
                    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                    return resp.text();
                })
                .then(html => {
                    // insert HTML and cache it
                    pageContent.innerHTML = html;
                    _memberPageCache.set(tabId, html);
                    afterPageLoad(tabId);
                })
                .catch(err => {
                    console.error('Failed to load member page:', err);
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
    const newName = document.getElementById('modal-input-name').value.trim();
    if (!newName) {
        showSuccessModal('Error', 'Name cannot be empty.', 'fa-solid fa-circle-exclamation text-red-500');
        return;
    }
    if (newName.length > 30) {
        showSuccessModal('Error', 'Full name cannot exceed 30 characters.', 'fa-solid fa-circle-exclamation text-red-500');
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
let onModalClose = null;

function ensureModalRefs() {
    if (!modal) modal = document.getElementById('custom-modal');
    if (!modalTitle) modalTitle = document.getElementById('modal-title');
    if (!modalMessage) modalMessage = document.getElementById('modal-message');
    if (!modalIcon) modalIcon = document.getElementById('modal-icon');
}

// updated showSuccessModal uses lazy refs
function showSuccessModal(title, message, iconClass = 'fa-solid fa-check-circle text-orange-500', callback = null) {
    ensureModalRefs();
    if (!modal || !modalTitle || !modalMessage || !modalIcon) return; // fail-safe

    modalTitle.textContent = title;
    modalMessage.textContent = message;

    modalIcon.className = '';
    modalIcon.className = iconClass + ' text-5xl mb-4 animate-pulse-slow';
    onModalClose = callback;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// showsucessmodal wrapper for errors
function showErrorModal(title, message) {
    showSuccessModal(title, message, 'fa-solid fa-circle-xmark text-red-500');
}

function closeSuccessModal() {
    ensureModalRefs();
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if (typeof onModalClose === 'function') {
        onModalClose();
        onModalClose = null; // Reset it so it doesn't run for other modals
    }
}

// --- Custom Dropdown Logic ---
        
// 1. Toggle Visibility
function toggleCustomDropdown(id) {
    const menu = document.getElementById(id + '-menu');
    
    // Close all other dropdowns first
    document.querySelectorAll('.custom-dropdown-menu').forEach(el => {
        if (el.id !== id + '-menu') el.classList.add('hidden');
    });
    
    menu.classList.toggle('hidden');
}

// 2. Select Option
function selectCustomOption(dropdownId, value, label) {
    // Update Label
    const labelEl = document.getElementById(dropdownId + '-label');
    if(labelEl) labelEl.innerText = label;

    // Update the Hidden Input Value (Data)
    const inputEl = document.getElementById(dropdownId + '-value');
    if(inputEl) inputEl.value = value;

    // Close Menu
    const menu = document.getElementById(dropdownId + '-menu');
    menu.classList.add('hidden');
}

// 3. Close when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-dropdown-container')) {
        document.querySelectorAll('.custom-dropdown-menu').forEach(el => {
            el.classList.add('hidden');
        });
    }
});

// --- Volunteer Wizard Logic ---
let currentStep = 1;

function goToStep(step) {
    // Validate before jumping (simplified for prototype)
    // In real app, check if previous steps are valid
    showStep(step);
}

function validateStep(step) {
    // --- STEP 1 VALIDATION: MyKad ---
    if (step === 1) {
        const frontVal = document.getElementById('mykad_front_base64').value;
        const backVal = document.getElementById('mykad_back_base64').value;

        if (!frontVal || !backVal) {
            showErrorModal("Step 1 Incomplete", "Please capture both Front and Back photos of your MyKad before proceeding.");
            return false;
        }
    }

    // --- STEP 2 VALIDATION: Legal ---
    if (step === 2) {
        // 1. Check Background Consent
        const consent = document.querySelector('input[name="has_background_check_consent"]:checked');
        if (!consent) {
            showErrorModal("Step 2 Incomplete", "Please indicate if you consent to a background check.");
            return false;
        }

        // 2. Check Prior Conviction
        const conviction = document.querySelector('input[name="has_prior_conviction"]:checked');
        if (!conviction) {
            showErrorModal("Step 2 Incomplete", "Please indicate if you have any prior convictions.");
            return false;
        }

        // 3. Conditional: If "Yes" is selected, details are mandatory
        if (conviction.value === "1") {
            const details = document.getElementById('conviction-text').value.trim();
            if (!details) {
                showErrorModal("Details Required", "Since you selected 'Yes' for prior convictions, you must provide details.");
                return false;
            }
        }
    }

    // --- STEP 3 VALIDATION: Qualifications ---
    if (step === 3) {
        // Check File Inputs (checking .files.length)
        const frontFile = document.getElementById('license-front').files.length;
        const backFile = document.getElementById('license-back').files.length;

        if (frontFile === 0 || backFile === 0) {
            showErrorModal("Step 3 Incomplete", "Please upload both the Front and Back of your Driver's License.");
            return false;
        }
    }

    return true;
}

function changeStep(direction) {
    if (direction === 1) {
        // Run validation on the CURRENT step before allowing move
        if (!validateStep(currentStep)) {
            return; // Stop here. Do not proceed.
        }
    }

    const newStep = currentStep + direction;
    
    if (newStep >= 1 && newStep <= 4) {
        showStep(newStep);
    }
}

function showStep(step) {
    // Hide all steps
    for(let i=1; i<=4; i++) {
        document.getElementById(`step-content-${i}`).classList.add('hidden');
        const indicator = document.getElementById(`step-indicator-${i}`);
        const label = indicator.nextElementSibling;
        
        // Reset styles
        if (i < step) {
            // Completed
            indicator.className = "w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold border-4 border-white shadow transition-colors";
            indicator.innerHTML = '<i data-lucide="check" class="w-5 h-5"></i>';
            label.classList.add('text-green-600');
        } else if (i === step) {
            // Current
            indicator.className = "w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold border-4 border-white shadow transition-colors";
            indicator.innerHTML = i;
            label.classList.add('text-gray-900', 'font-bold');
            label.classList.remove('text-gray-500');
        } else {
            // Future
            indicator.className = "w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold border-4 border-white shadow transition-colors";
            indicator.innerHTML = i;
            label.classList.remove('text-green-600', 'text-gray-900', 'font-bold');
            label.classList.add('text-gray-500');
        }
    }

    // Update Progress Lines
    const line1 = document.getElementById('line-1');
    const line2 = document.getElementById('line-2');
    const line3 = document.getElementById('line-3');

    // Line 1 (between 1 and 2) active if we are at step 2 or more
    line1.className = step >= 2 ? 
        'flex-1 h-0.5 border-t-4 border-dotted border-orange-500 mx-2 transition-all duration-300' : 
        'flex-1 h-0.5 border-t-4 border-dotted border-transparent mx-2 transition-all duration-300';

    // Line 2 (between 2 and 3) active if we are at step 3 or more
    line2.className = step >= 3 ? 
        'flex-1 h-0.5 border-t-4 border-dotted border-orange-500 mx-2 transition-all duration-300' : 
        'flex-1 h-0.5 border-t-4 border-dotted border-transparent mx-2 transition-all duration-300';
    
    // Line 3 (between 3 and 4) active if we are at step 4
    line3.className = step >= 4 ? 
        'flex-1 h-0.5 border-t-4 border-dotted border-orange-500 mx-2 transition-all duration-300' : 
        'flex-1 h-0.5 border-t-4 border-dotted border-transparent mx-2 transition-all duration-300';

    // Show current step
    document.getElementById(`step-content-${step}`).classList.remove('hidden');
    
    // Buttons state
    const btnBack = document.getElementById('btn-back');
    const btnNext = document.getElementById('btn-next');
    const btnSubmit = document.getElementById('btn-submit');

    btnBack.classList.toggle('hidden', step === 1);
    
    if (step === 4) {
        btnNext.classList.add('hidden');
        btnSubmit.classList.remove('hidden');
        // Initialize canvas when showing step 4
        // The global window.resizeCanvas function will be called here
        if (typeof window.resizeCanvas === 'function') {
            setTimeout(window.resizeCanvas, 100); 
        }
    } else {
        btnNext.classList.remove('hidden');
        btnSubmit.classList.add('hidden');
    }

    currentStep = step;
    lucide.createIcons(); // Re-render icons for indicators
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
window.toggleCustomDropdown = toggleCustomDropdown;
window.selectCustomOption = selectCustomOption;
window.goToStep = goToStep;
window.changeStep = changeStep;
window.showStep = showStep;

window.clearSignature = function() { console.log("Clear signature placeholder executed."); };
window.resizeCanvas = function() { console.log("Resize canvas placeholder executed."); };

// --- Auto-bind simple input listeners on DOMContentLoaded to detect changes ---
document.addEventListener('DOMContentLoaded', () => {
    // cache dashboard snapshot if exists (original behavior)
    const pageContent = safeEl('page-content');
    if (pageContent && typeof _memberPageCache !== 'undefined') _memberPageCache.set('dashboard', pageContent.innerHTML);

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

    window.addEventListener('resize', () => {
        if(typeof window.resizeCanvas === 'function') window.resizeCanvas();
    });
});

function initSignaturePad() {
    const canvas = document.getElementById('signature-pad');
    const hiddenInput = document.getElementById('signature_base64');
    
    // If canvas doesn't exist (e.g., tab loaded but some error occurred), stop.
    if (!canvas) return; 

    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let hasSigned = false;
    let lastX = 0;
    let lastY = 0;

    // Define the resize function globally so showStep() can call it
    window.resizeCanvas = function() {
        // Only resize if the canvas is actually visible to avoid 0 height issues
        if (canvas.offsetParent === null) return; 
        
        const rect = canvas.getBoundingClientRect();
        // Set actual size in memory (scaled for high DPI screens)
        canvas.width = rect.width * 2; 
        canvas.height = rect.height * 2;
        ctx.scale(2, 2);
        
        // Reset context properties after resize
        ctx.lineCap = "round";
        ctx.lineJoin = "round";
        ctx.strokeStyle = "#000";
        ctx.lineWidth = 2;
    };

    function saveSignature() {
        if (hasSigned) {
            hiddenInput.value = canvas.toDataURL('image/png');
        } else {
            hiddenInput.value = "";
        }
    }

    function draw(e) {
        if (!isDrawing) return;

        hasSigned = true;
        
        const rect = canvas.getBoundingClientRect();
        let clientX, clientY;
        
        if (e.type.includes('touch')) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }
        
        const x = clientX - rect.left;
        const y = clientY - rect.top;

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.stroke();
        [lastX, lastY] = [x, y];
    }

    // Remove old listeners to prevent duplicates if tab is reloaded
    // (Cloning the node is a quick hack to wipe listeners, or just leave as is if simple)
    // For simplicity, we just add them. 

    canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        const rect = canvas.getBoundingClientRect();
        [lastX, lastY] = [e.clientX - rect.left, e.clientY - rect.top];
    });

    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        isDrawing = true;
        const rect = canvas.getBoundingClientRect();
        [lastX, lastY] = [e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top];
    });

    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('touchmove', (e) => { e.preventDefault(); draw(e); });

    // On end of drawing, save data to hidden input
    canvas.addEventListener('mouseup', () => { isDrawing = false; saveSignature(); });
    canvas.addEventListener('mouseout', () => { isDrawing = false; saveSignature(); });
    canvas.addEventListener('touchend', () => {
        isDrawing = false; 
        saveSignature();
        // console.log("saved") // for debuging 
    });

    // Global clear function
    window.clearSignature = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hiddenInput.value = '';
        hasSigned = false;
        // console.log("cleared") // just for debugging
    }
}

function updateFileName(input, displayId) {
    const displayElement = document.getElementById(displayId);
    if (input.files && input.files.length > 0) {
        displayElement.textContent = input.files[0].name;
        displayElement.classList.remove('italic', 'text-gray-500');
        displayElement.classList.add('text-gray-800');
    } else {
        displayElement.textContent = "No file chosen";
        displayElement.classList.add('italic', 'text-gray-500');
        displayElement.classList.remove('text-gray-800');
    }
}

function updateCharCount(input, counterId, limit) {
    // 1. Strict Input Sanitization (SQL Injection Protection)
    // Instantly remove characters that are commonly used in SQL injection attacks:
    // ' (Single Quote), " (Double Quote), ; (Semicolon)
    const unsafeChars = /['";]/g;
    
    if (unsafeChars.test(input.value)) {
        // Remove the dangerous characters immediately
        input.value = input.value.replace(unsafeChars, '');
    }

    // 2. Update Character Counter
    const currentLength = input.value.length;
    const remaining = limit - currentLength;
    const counterElement = document.getElementById(counterId);
    
    counterElement.textContent = `${remaining} out of ${limit} characters remaining`;

    // 3. Visual Feedback for limit
    if (remaining <= 0) {
        counterElement.classList.add('text-red-600');
    } else {
        counterElement.classList.remove('text-red-600');
    }
}

function openLegalModal() {
    // --- VALIDATION CHECKS (Step 4) ---
    
    // Check Signature (Hidden Input populated by canvas)
    const signatureB64 = document.getElementById('signature_base64').value;
    if (!signatureB64) {
        showErrorModal("Missing Signature", "Please sign the application in the box provided before submitting.");
        return; // STOP: Do not open the legal modal
    }

    const agree1 = document.getElementById('agreement-check-1');
    const agree2 = document.getElementById('agreement-check-2');

    if (!agree1 || !agree1.checked || !agree2 || !agree2.checked) {
        showErrorModal("Agreement Required", "You must agree to the Code of Conduct and Reporting Clause to proceed.");
        return;
    }

    const legalModal = document.getElementById('legal-modal');
    if (!legalModal) return;
    
    legalModal.classList.remove('hidden');
    // Prevent body background from scrolling
    document.body.style.overflow = 'hidden';
    
    // Small delay to allow display:block to apply before opacity transition
    setTimeout(() => {
        legalModal.classList.remove('opacity-0');
        const innerDiv = legalModal.querySelector('div');
        if(innerDiv) {
            innerDiv.classList.remove('scale-95');
            innerDiv.classList.add('scale-100');
        }
    }, 10);
}

function closeLegalModal() {
    const legalModal = document.getElementById('legal-modal');
    if (!legalModal) return;

    legalModal.classList.add('opacity-0');
    const innerDiv = legalModal.querySelector('div');
    if(innerDiv) {
        innerDiv.classList.remove('scale-100');
        innerDiv.classList.add('scale-95');
    }
    
    setTimeout(() => {
        legalModal.classList.add('hidden');
        // Re-enable body scrolling
        document.body.style.overflow = '';
        resetLegalForm();
    }, 300);
}

function toggleSubmitButton() {
    const checkbox = document.getElementById('confirm-checkbox');
    const submitBtn = document.getElementById('submit-btn');
    if (!checkbox || !submitBtn) return;

    if (checkbox.checked) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
    } else {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

function resetLegalForm() {
    const checkbox = document.getElementById('confirm-checkbox');
    if (checkbox) {
        checkbox.checked = false;
        toggleSubmitButton();
    }
}

async function submitApplication() {
    try {
        // 1. Close Modal
        if(typeof closeLegalModal === 'function') closeLegalModal();

        // 2. Retrieve Data from DOM
        const frontB64 = document.getElementById('mykad_front_base64').value;
        const backB64 = document.getElementById('mykad_back_base64').value;
        const signatureB64 = document.getElementById('signature_base64').value;
        
        const licenseStatus = document.getElementById('license-status-value').value;
        const vehicleAvail = document.getElementById('vehicle-availability-value').value;
        const experience = document.getElementById('experience-text').value;
        const certifications = document.getElementById('certifications-text').value;
        const convictionDetails = document.getElementById('conviction-text').value;

        // Get Radio Values
        const consentEl = document.querySelector('input[name="has_background_check_consent"]:checked');
        const consent = consentEl ? consentEl.value : "0";
        
        const convictionEl = document.querySelector('input[name="has_prior_conviction"]:checked');
        const conviction = convictionEl ? convictionEl.value : "0";

        // Get Standard Files
        const licenseFrontFile = document.getElementById('license-front').files[0];
        const licenseBackFile = document.getElementById('license-back').files[0];

        // 3. Validation
        if (!frontB64 || !backB64) {
            showErrorModal("Missing Identity", "Please capture both front and back MyKad images.");
            return;
        }
        if (!signatureB64) {
            showErrorModal("Missing Signature", "Please sign the application before submitting.");
            return;
        }
        if (!licenseFrontFile || !licenseBackFile) {
             showErrorModal("Missing License", "Please upload both front and back of your driver's license.");
             return;
        }

        // 4. Prepare Form Data
        const formData = new FormData();
        
        // Match these keys exactly to your PHP $_POST/$_FILES lookups
        formData.append("mykad_front_base64", frontB64);
        formData.append("mykad_back_base64", backB64);
        formData.append("signature_base64", signatureB64);
        
        formData.append("license_status", licenseStatus);
        formData.append("vehicle_availability", vehicleAvail);
        formData.append("animal_handling_experience", experience);
        formData.append("training_certifications", certifications);
        formData.append("conviction_details", convictionDetails);
        
        formData.append("has_background_check_consent", consent);
        formData.append("has_prior_conviction", conviction);
        
        formData.append("driver_license_front", licenseFrontFile);
        formData.append("driver_license_back", licenseBackFile);

        // 5. Send to Backend
        const response = await fetch("../api/process_rescue_application.php", {
            method: "POST",
            body: formData
        });

        // 7. Handle Response
        // Check if response is ok (status 200-299)
        if (!response.ok) {
            // Try to get error text if JSON parse fails
            const text = await response.text();
            console.error("Server Error:", text);
            throw new Error("Server responded with " + response.status);
        }

        const result = await response.json();

        if (!result.success) {
            // Show PHP Error using our red modal
            showErrorModal("Submission Failed", result.error || "Unknown error occurred.");
            return;
        }

        // 8. Success!
        showSuccessModal(
            "Application Submitted",
            "Your application has been received and is pending review.",
            undefined, // use default icon
            () => window.location.reload() // Reload page on close
        );

    } catch (error) {
        console.error("Submit error:", error);
        showErrorModal("System Error", "A network error occurred. Please check the console.");
    }
}

function viewApplicationDetails(row) {
    // Retrieve data from data-attributes
    const app = {
        appId: row.dataset.id,
        date: row.dataset.date,
        status: row.dataset.status,
        reason: row.dataset.reason
    };

    let styles = {
        border: 'border-gray-100',
        gradient: 'from-green-100 via-gray-100 to-amber-100',
        headerBorder: 'border-gray-200',
        iconBg: 'bg-gray-100',
        iconColor: 'text-gray-600',
        icon: 'file-question',
        title: 'Application Update',
        desc: 'Status unknown.',
        statusBadge: ''
    };

    if (app.status === 'pending') {
        styles.border = 'border-amber-200';
        styles.gradient = 'from-green-50 via-amber-100 to-amber-300';
        styles.headerBorder = 'border-amber-300';
        styles.iconBg = 'bg-amber-100';
        styles.iconColor = 'text-amber-600';
        styles.icon = 'hourglass';
        styles.title = 'Application Under Review';
        styles.desc = 'Thank you for stepping up to become a PawRescue Hero.<br>Our team is currently reviewing your verification documents.';
        styles.statusBadge = `<span class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs font-bold border border-yellow-100"><span class="w-2 h-2 bg-yellow-400 rounded-full animate-pulse"></span> Pending</span>`;
    } else if (app.status === 'approved') {
        styles.border = 'border-green-200';
        styles.gradient = 'from-amber-50 via-green-100 to-green-300';
        styles.headerBorder = 'border-green-300';
        styles.iconBg = 'bg-green-100';
        styles.iconColor = 'text-green-600';
        styles.icon = 'medal';
        styles.title = 'You are a Hero!';
        styles.desc = 'Your application has been approved. You are now an official rescuer.';
        styles.statusBadge = `<span class="inline-flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-bold border border-green-100"><span class="w-2 h-2 bg-green-500 rounded-full"></span> Approved</span>`;
    } else {
        styles.border = 'border-red-200';
        styles.gradient = 'from-gray-50 via-red-100 to-red-300';
        styles.headerBorder = 'border-red-300';
        styles.iconBg = 'bg-red-100';
        styles.iconColor = 'text-red-600';
        styles.icon = 'x-circle';
        styles.title = 'Application Rejected';
        
        const reasonText = app.reason 
            ? `<strong>Reason:</strong> ${app.reason}` 
            : 'Unfortunately, your application for the Community Rescuer role has not been successful at this time. We encourage you to gain relevant experience and reapply whenever you feel ready.';
        
        styles.desc = reasonText;
        styles.statusBadge = `<span class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold border border-red-100">Rejected</span>`;
    }

    const htmlContent = `
        <div class="max-w-full mx-auto bg-white rounded-2xl shadow-sm border ${styles.border} overflow-hidden">
            <div class="bg-gradient-to-r ${styles.gradient} p-8 text-center border-b ${styles.headerBorder}">
                <div class="w-20 h-20 ${styles.iconBg} ${styles.iconColor} rounded-full flex items-center justify-center mx-auto mb-6 shadow-md">
                    <i data-lucide="${styles.icon}" class="w-10 h-10"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">${styles.title}</h2>
                <p class="text-gray-600 text-sm leading-relaxed">${styles.desc}</p>
            </div>

            <div class="p-8">
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-4">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Application ID</span>
                        <span class="font-mono text-gray-700 font-bold tracking-tight">${app.appId}</span>
                    </div>
                    <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-4">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Submitted On</span>
                        <span class="text-gray-700 font-medium">${app.date}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Current Status</span>
                        ${styles.statusBadge}
                    </div>
                </div>
                <div class="mt-6 text-center">
                        <button onclick="closeModalById('application-details-modal')" class="text-gray-400 hover:text-gray-600 text-sm font-medium transition-colors">Close</button>
                </div>
            </div>
        </div>
    `;

    const contentEl = document.getElementById('application-details-content');
    if(contentEl) {
        contentEl.innerHTML = htmlContent;
        if(typeof lucide !== 'undefined') lucide.createIcons();
        openModal('application-details-modal');
    }
}

function handleReapply(appId) {
    // 1. Optimistic UI: Hide overlay immediately
    const overlay = document.getElementById('rejection-overlay');
    const form = document.getElementById('rescuer-form-container');
    
    // 2. Send Request to DB to mark as resolved
    fetch('../api/resolve_rescuer_application.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ application_id: appId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animation for smooth transition
            overlay.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => {
                overlay.style.display = 'none';
                form.classList.remove('hidden');
            }, 300);
        } else {
            alert('Something went wrong. Please try again.');
        }
    })
    .catch(error => console.error('Error:', error));
}