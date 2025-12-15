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

window.openAdminModal = function() {
    // Simply opens the pre-filled modal
    currentAppPage = 1;
    updateModalUI();
    document.getElementById('admin-review-modal').classList.remove('hidden');
};

window.closeAdminModal = function() {
    document.getElementById('admin-review-modal').classList.add('hidden');
};

window.changeAppPage = function(delta) {
    const newPage = currentAppPage + delta;
    if(newPage >= 1 && newPage <= 5) {
        currentAppPage = newPage;
        updateModalUI();
    }
};

function updateModalUI() {
    // Toggle Page Visibility
    for(let i=1; i<=5; i++) {
        const page = document.getElementById(`modal-page-${i}`);
        if(page) page.classList.add('hidden');
    }
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