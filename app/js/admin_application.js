function filterApplications(status) {
    // 1. Hide all lists
    ['pending', 'approved', 'rejected'].forEach(type => {
        const listEl = document.getElementById(`list-${type}`);
        if (listEl) listEl.classList.add('hidden');
        
        // Reset tabs styles
        const tabEl = document.getElementById(`tab-${type}`);
        if (tabEl) {
            tabEl.classList.remove('border-orange-500', 'text-orange-600');
            tabEl.classList.add('border-transparent', 'text-gray-500');
        }
    });

    // 2. Show active list
    const activeList = document.getElementById(`list-${status}`);
    if (activeList) activeList.classList.remove('hidden');

    // 3. Highlight active tab
    const activeTab = document.getElementById(`tab-${status}`);
    if (activeTab) {
        activeTab.classList.remove('border-transparent', 'text-gray-500');
        activeTab.classList.add('border-orange-500', 'text-orange-600');
    }
}


let currentAppPage = 1;

// 1. OPEN MODAL & POPULATE DATA
window.openAdminModal = function(element) {
    // A. Parse the data from the clicked element
    // Note: The element must have data-applicant='{...}' attribute
    const data = JSON.parse(element.getAttribute('data-applicant'));
    
    // Debug: Check data in console
    console.log("Opening application for:", data.full_name, data);

    // B. Populate Page 1: General Info
    document.getElementById('modal-profile-photo').src = data.photo_url;
    document.getElementById('modal-full-name').innerText = data.full_name;
    document.getElementById('modal-user-id').innerText = data.user_id;
    document.getElementById('modal-phone').innerText = data.phone;
    document.getElementById('modal-email').innerText = data.email || 'N/A';
    document.getElementById('modal-app-id').innerText = data.application_id;
    document.getElementById('modal-experience').innerText = data.animal_handling_experience || 'No experience listed.';
    document.getElementById('modal-training').innerText = data.training_certifications || 'No certification listed.';

    // Store App ID for form submission
    document.getElementById('modal-hidden-app-id').value = data.application_id;

    // C. Populate Page 2: Identity (Images)
    setImage('modal-mykad-front', 'modal-mykad-front-placeholder', data.mykad_front_url);
    setImage('modal-mykad-back', 'modal-mykad-back-placeholder', data.mykad_back_url);

    // D. Populate Page 3: Conviction Logic (Styling change)
    const convContainer = document.getElementById('modal-conviction-container');
    const convIconBg = document.getElementById('modal-conviction-icon-bg');
    const convIcon = document.getElementById('modal-conviction-icon');
    const convTitle = document.getElementById('modal-conviction-title');
    const convDesc = document.getElementById('modal-conviction-desc');

    if (data.has_prior_conviction == 1) {
        // WARNING STATE
        convContainer.className = "p-6 bg-red-50/50 border border-red-100 rounded-lg flex items-center gap-4 text-red-800";
        convIconBg.className = "bg-red-100 p-2 rounded-full flex-shrink-0";
        convIcon.className = "w-6 h-6 text-red-600";
        convTitle.innerText = "Prior Conviction Declared";
        convTitle.className = "font-bold block text-red-900";
        convDesc.innerHTML = `<strong>Details:</strong> ${data.conviction_details || 'No details provided.'}`;
        // Update icon to alert
        convIcon.setAttribute('data-lucide', 'alert-triangle');
    } else {
        // CLEAN STATE (Default)
        convContainer.className = "p-6 bg-green-50/50 border border-green-100 rounded-lg flex items-center gap-4 text-green-800";
        convIconBg.className = "bg-green-100 p-2 rounded-full flex-shrink-0";
        convIcon.className = "w-6 h-6 text-green-600";
        convTitle.innerText = "Clean Declaration";
        convTitle.className = "font-bold block text-green-900";
        convDesc.innerHTML = "Applicant has declared <strong>NO</strong> prior criminal convictions in the last 5 years.";
        // Update icon to shield
        convIcon.setAttribute('data-lucide', 'shield-check');
    }
    // Refresh icons because we changed data-lucide attributes
    lucide.createIcons(); 

    // E. Populate Page 4: Qualifications
    // Helper to capitalize first letter
    const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '-';
    
    document.getElementById('modal-license-class').innerText = capitalize(data.license_status);
    document.getElementById('modal-vehicle').innerText = capitalize(data.vehicle_availability);
    
    setImage('modal-license-front', 'modal-license-front-placeholder', data.license_front_url);
    setImage('modal-license-back', 'modal-license-back-placeholder', data.license_back_url);

    // F. Populate Page 5: Signature
    const sigImg = document.getElementById('modal-signature-img');
    const sigName = document.getElementById('modal-signature-name');
    
    // Logic: If signature URL exists (and isn't just the folder path), show image
    if (data.signature_url && !data.signature_url.endsWith('/')) {
        sigImg.src = data.signature_url;
        sigImg.classList.remove('hidden');
        sigName.classList.add('hidden');
    } else {
        // Fallback to text name if image fails
        sigName.innerText = data.full_name;
        sigName.classList.remove('hidden');
        sigImg.classList.add('hidden');
    }
    
    document.getElementById('modal-signed-date').innerText = data.formatted_date;

    // G. Reset UI State and Show
    currentAppPage = 1;
    updateModalUI();
    document.getElementById('admin-review-modal').classList.remove('hidden');
};

// Helper: Toggles between showing the Image or the Placeholder Icon
function setImage(imgId, placeholderId, url) {
    const imgEl = document.getElementById(imgId);
    const placeEl = document.getElementById(placeholderId);
    
    if (url && url.trim() !== '') {
        imgEl.src = url;
        imgEl.classList.remove('hidden');
        placeEl.classList.add('hidden');
    } else {
        imgEl.src = '';
        imgEl.classList.add('hidden');
        placeEl.classList.remove('hidden');
    }
}

// 2. CLOSE MODAL
window.closeAdminModal = function() {
    document.getElementById('admin-review-modal').classList.add('hidden');
};

// 3. PAGINATION LOGIC
window.changeAppPage = function(delta) {
    const newPage = currentAppPage + delta;
    if(newPage >= 1 && newPage <= 5) {
        currentAppPage = newPage;
        updateModalUI();
    }
};

// 4. UPDATE UI (Headers, Buttons, Page Visibility)
function updateModalUI() {
    // Hide all pages
    for(let i=1; i<=5; i++) {
        const page = document.getElementById(`modal-page-${i}`);
        if(page) page.classList.add('hidden');
    }
    // Show current page
    document.getElementById(`modal-page-${currentAppPage}`).classList.remove('hidden');
    
    // Update Header Text
    const titles = ["General Info", "Identity", "Background", "Qualifications", "Decision"];
    document.getElementById('modal-step-indicator').innerText = `Step ${currentAppPage} of 5: ${titles[currentAppPage-1]}`;
    
    // Toggle Buttons
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    
    if(currentAppPage === 1) btnPrev.classList.add('hidden');
    else btnPrev.classList.remove('hidden');
    
    if(currentAppPage === 5) btnNext.classList.add('hidden');
    else btnNext.classList.remove('hidden');
}

// 5. SUBMIT DECISION
window.submitDecision = function(status) {
    const appId = document.getElementById('modal-hidden-app-id').value;
    
    // Optional: Confirm action before proceeding
    const actionText = status === 'approved' ? 'Approve' : 'Reject';
    if(!confirm(`Are you sure you want to ${actionText} this application?`)) return;

    // Show loading state (optional, adds polish)
    const btnContainer = document.getElementById('modal-actions');
    const originalContent = btnContainer.innerHTML;
    btnContainer.innerHTML = `<div class="text-center py-4"><span class="text-gray-500">Processing...</span></div>`;

    fetch('../api/update_rescue_application_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            application_id: appId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Application successfully ${status}!`);
            window.closeAdminModal();
            // Reload the page to show updated table/statuses
            window.location.reload(); 
        } else {
            alert('Error: ' + (data.error || 'Unknown error occurred'));
            // Restore buttons if error
            btnContainer.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('A network error occurred.');
        btnContainer.innerHTML = originalContent;
    });
}