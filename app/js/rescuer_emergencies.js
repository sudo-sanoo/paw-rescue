// Initialize Lucide Icons
if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();

// --- LIGHTBOX LOGIC ---

// 1. Change: Use a dynamic array instead of static const
let activeEmergencyPhotos = []; 
let currentPhotoIndex = 0;

// Helper function to get elements dynamically
function getLightboxElements() {
    return {
        modal: document.getElementById('lightbox-modal'),
        img: document.getElementById('lightbox-img'),
        counter: document.getElementById('lightbox-counter')
    };
}

// 2. New Function: Called by the thumbnails in the PHP loop
// It receives the index and the specific array of photos for that emergency
function updateLightboxAndOpen(index, photos) {
    activeEmergencyPhotos = photos || [];
    currentPhotoIndex = index;
    
    if (activeEmergencyPhotos.length === 0) {
        console.error("No photos available for this emergency");
        return;
    }

    updateLightboxImage();
    
    const { modal } = getLightboxElements();
    if (modal) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
        });
    }
}

function closeLightbox() {
    const { modal } = getLightboxElements();
    if (!modal) return;

    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function changePhoto(direction) {
    if (activeEmergencyPhotos.length === 0) return;

    // 3. Change: Calculate index using the dynamic array length
    currentPhotoIndex = (currentPhotoIndex + direction + activeEmergencyPhotos.length) % activeEmergencyPhotos.length;
    updateLightboxImage();
}

function updateLightboxImage() {
    const { img, counter } = getLightboxElements();
    
    if (!img) return; 

    img.style.opacity = '0.5';
    
    // 4. Change: Set src from the dynamic array
    if (activeEmergencyPhotos[currentPhotoIndex]) {
        img.src = activeEmergencyPhotos[currentPhotoIndex];
    }
    
    if (counter) {
        counter.innerText = `${currentPhotoIndex + 1} / ${activeEmergencyPhotos.length}`;
    }
    
    setTimeout(() => {
        if(img) img.style.opacity = '1';
    }, 50);
}

// --- KEYBOARD SUPPORT ---
document.addEventListener('keydown', (e) => {
    const { modal } = getLightboxElements();
    if (!modal || modal.classList.contains('hidden')) return;
    
    if (e.key === "Escape") closeLightbox();
    if (e.key === "ArrowRight") changePhoto(1);
    if (e.key === "ArrowLeft") changePhoto(-1);
});

// -- GOOGLE MAPS INSTANCE INIT --

const mapInstances = {};


// --- EMERGENCY MODAL LOGIC ---

// Called when clicking the Main Card
function openDynamicModal(modalId, photos, lat, lng) {
    // We update the context here so if they use keyboard nav it works immediately
    activeEmergencyPhotos = photos || []; 
    openEmergencyModal(modalId);

    const emergencyId = modalId.replace('modal-', '');
    const mapElementId = `map-view-${emergencyId}`;

    setTimeout(() => {
        initViewMap(mapElementId, lat, lng);
    }, 300);
}

function openEmergencyModal(modalId) {
    const el = document.getElementById(modalId);
    if (!el) return;
    
    el.classList.remove('hidden');
    el.classList.add('flex');
    
    setTimeout(() => {
        el.classList.remove('opacity-0');
    }, 10);
}

function closeEmergencyModal(modalId) {
    const el = document.getElementById(modalId);
    if (!el) return;
    
    el.classList.add('opacity-0');
    
    setTimeout(() => {
        el.classList.add('hidden');
        el.classList.remove('flex');
    }, 300); 
}

// -- GOOGLE MAPS LOGIC (READ-ONLY) --

async function initViewMap(elementId, lat, lng) {
    const mapEl = document.getElementById(elementId);
    
    // Safety Checks
    if (!mapEl) return console.error("Map element not found:", elementId);
    if (!lat || !lng || lat === 0) return mapEl.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">Location not available</div>';

    // Prevent re-initializing if already loaded
    if (mapInstances[elementId]) return;

    try {
        // Ensure API is loaded
        await loadGoogleMapsAPI();

        const { Map } = await google.maps.importLibrary("maps");
        const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

        const position = { lat: parseFloat(lat), lng: parseFloat(lng) };

        // Create the Map
        const map = new Map(mapEl, {
            center: position,
            zoom: 14,
            disableDefaultUI: true,       // Hides StreetView, MapType, etc.
            gestureHandling: "auto",
            draggable: false,              // Allows panning
            zoomControl: true,            // Allows zooming
            mapId: "DEMO_MAP_ID"          // Required for Advanced Markers
        });

        // Add the Pin
        new AdvancedMarkerElement({
            position: position,
            map: map,
            title: "Emergency Location"
        });

        // Save instance to prevent reloading
        mapInstances[elementId] = map;

    } catch (error) {
        console.error("Map Load Error:", error);
        mapEl.innerHTML = `<div class="p-4 text-center text-red-500">Map failed to load.</div>`;
    }
}

/**
 * Handles the "Respond to Emergency" button click
 */
// --- Accept Mission Logic ---
window.handleAcceptMission = async function(event, emergencyId) {
    event.preventDefault(); // Stop default form submission
    
    const submitBtn = event.target.querySelector('button[type="submit"]');

    // 1. Validation / Confirmation
    if (!confirm("Are you sure you want to accept this rescue mission?")) {
        return;
    }

    // 2. Prepare Data
    const formData = new FormData();
    formData.append('emergency_id', emergencyId);

    // 3. UI Feedback
    const originalBtnContent = submitBtn.innerHTML;
    submitBtn.innerHTML = `<span class="animate-pulse">Accepting...</span>`;
    submitBtn.disabled = true;
    
    // Optional: Visual change to indicate processing
    submitBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
    submitBtn.classList.add('bg-gray-400');

    try {
        const response = await fetch('../../api/accept_emergency_rescue_mission.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccessModal("Mission Accepted", "You are now tasked with rescuing an animal, please be safe, prepared, and prioritize the animal's well-being while ensuring your own safety.", 'fa-solid fa-check-circle text-orange-500', () => {
                window.location.reload(); 
            }); 
        } else {
            throw new Error(result.message || "Failed to accept mission");
        }
    } catch (error) {
        console.error('Error:', error);
        
        showSuccessModal("Mission Accepted", "You are now tasked with rescuing an animal, please be safe, prepared, and prioritize the animal's well-being while ensuring your own safety.", 'fa-solid fa-check-circle text-orange-500', () => {
            window.location.reload(); 
        }); 
        
        // Reset Button State
        submitBtn.innerHTML = originalBtnContent;
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-400');
        submitBtn.classList.add('bg-red-600', 'hover:bg-red-700');
    }
};

// -- EMERGENCIES FILTERING -- 

document.addEventListener('click', (e) => {
    // 1. Check if the clicked element (or its parent) is a filter button
    const btn = e.target.closest('.filter-btn');
    if (!btn) return;

    // 2. Locate the container (If it's not on screen, stop)
    const container = document.getElementById('emergencies-list-container');
    if (!container) return;

    const filterType = btn.getAttribute('data-filter');
    const noResultsMsg = document.getElementById('no-results-message'); // Optional UI polish
    
    // Define weights: Lower = Higher Priority
    const urgencyWeight = { 'critical': 1, 'serious': 2, 'minor': 3 };

    // --- UI UPDATES ---
    
    // Reset all buttons in the same group
    const allButtons = btn.parentElement.querySelectorAll('.filter-btn');
    allButtons.forEach(b => {
        b.style.opacity = '0.6';
        b.style.transform = 'scale(0.95)';
    });

    // Highlight active button
    btn.style.opacity = '1';
    btn.style.transform = 'scale(1)';

    // --- FILTERING LOGIC ---

    const cards = Array.from(container.querySelectorAll('.emergency-card'));
    let visibleCount = 0;

    cards.forEach(card => {
        const urgency = card.getAttribute('data-urgency');
        
        // Show if 'all' OR matches specific urgency
        if (filterType === 'all' || urgency === filterType) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });

    // Toggle "No Results" message if you added it to PHP
    if (noResultsMsg) {
        noResultsMsg.classList.toggle('hidden', visibleCount > 0);
    }

    // --- SORTING LOGIC ---
    
    // Get only the visible cards to sort
    const visibleCards = cards.filter(c => !c.classList.contains('hidden'));

    visibleCards.sort((a, b) => {
        const urgencyA = urgencyWeight[a.getAttribute('data-urgency')] || 99;
        const urgencyB = urgencyWeight[b.getAttribute('data-urgency')] || 99;
        
        // Get timestamps (ensure they are integers)
        const timeA = parseInt(a.getAttribute('data-timestamp')) || 0;
        const timeB = parseInt(b.getAttribute('data-timestamp')) || 0;

        if (filterType === 'all') {
            // IF 'All': Primary Sort = Severity (Urgency)
            if (urgencyA !== urgencyB) {
                return urgencyA - urgencyB; // ASC (1 comes before 3)
            }
            // Secondary Sort = Hours Ago (Newest First)
            return timeB - timeA; 
        } else {
            // IF Specific Filter: Sort ONLY by Hours Ago (Newest First)
            return timeB - timeA;
        }
    });

    // --- RE-RENDER ---
    // Append sorted cards back to the container
    visibleCards.forEach(card => container.appendChild(card));
});

// --- MISSION LOGIC ---
let currentStatus = 'otw'; 

// Configuration for different states
const missionStates = {
    'otw': {
        progress: '0%',
        destLabel: 'Picking up at',
        destText: '123 Jalan Ampang',
        btnText: 'Confirm Pickup',
        btnColor: 'bg-gray-900',
        statusText: 'En Route'
    },
    'transporting': {
        progress: '50%',
        destLabel: 'Dropping off at',
        destText: 'PetCare Center KL',
        btnText: 'Arrived at Vet',
        btnColor: 'bg-orange-600',
        statusText: 'Transporting'
    },
    'treating': {
        progress: '100%',
        destLabel: 'Mission',
        destText: 'Completed',
        btnText: 'Submit Report',
        btnColor: 'bg-green-600',
        statusText: 'Vet Handoff'
    }
};

// 2. Main Logic Function
async function advanceMissionStatus() {
    const btn = document.getElementById('main-action-btn');
    const btnText = document.getElementById('action-text');

    if (!btn) return;

    // READ FROM DATA ATTRIBUTES (Fixes AJAX issue)
    const emergencyId = btn.dataset.emergencyId;
    const currentStatus = btn.dataset.currentStatus;

    if (!emergencyId || !currentStatus) {
        console.error("Missing emergency context", btn.dataset);
        alert("Error: Emergency data missing. Please refresh.");
        return;
    }

    // Determine Next Status
    let nextStatus = '';
    if (currentStatus === 'otw') {
        nextStatus = 'transporting';
    } else if (currentStatus === 'transporting') {
        nextStatus = 'treating'; 
    } else {
        return; 
    }

    // UI Loading State
    btn.classList.add('opacity-75', 'cursor-wait');
    const originalText = btnText.innerText;
    btnText.innerText = "Updating...";
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('emergency_id', emergencyId);
        formData.append('status', nextStatus);

        const response = await fetch('../../api/update_mission_status.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            updateState(nextStatus);
            
            // Special handling for final state
            if (nextStatus === 'treating') {
                btn.className = "w-full bg-green-600 text-white rounded-xl py-4 shadow-lg flex items-center justify-center gap-2";
                btn.innerHTML = `<i class="fa-solid fa-check"></i> <span class="font-bold">Good Job!</span>`;
                
                setTimeout(() => {
                    alert("Mission successfully handed over to Vet!");
                    window.location.reload(); 
                }, 1500);
            }
        } else {
            alert("Error: " + result.message);
            btnText.innerText = originalText;
        }
    } catch (error) {
        console.error('API Error:', error);
        alert("Failed to connect to server.");
        btnText.innerText = originalText;
    } finally {
        btn.classList.remove('opacity-75', 'cursor-wait');
        btn.disabled = false;
    }
}

function updateState(newState) {
    const config = missionStates[newState];
    if (!config) return;

    // Update Text
    document.getElementById('destination-label').innerText = config.destLabel;
    document.getElementById('destination-text').innerText = config.destText;
    document.getElementById('action-text').innerText = config.btnText;
    document.getElementById('status-text').innerText = config.statusText;

    // Update Button Style
    const btn = document.getElementById('main-action-btn');
    if (btn) {
        btn.className = `group w-full ${config.btnColor} hover:opacity-90 text-white rounded-xl py-4 shadow-lg shadow-gray-200 flex items-center justify-center gap-3 transition-all transform active:scale-[0.98]`;
        
        // IMPORTANT: Update the data attribute so the next click works
        btn.dataset.currentStatus = newState;
    }
}


// --- AI ANALYSIS LOGIC ---

async function generateAIInsights() {
    // 1. Get Elements
    const containerInit = document.getElementById('ai-initial');
    const containerLoading = document.getElementById('ai-content-loading');
    const containerResults = document.getElementById('ai-content-results');
    
    // Elements to update
    const scoreText = document.getElementById('ai-score-text');
    const structuredContainer = document.getElementById('ai-structured-data');

    // 2. Get Context
    const btn = document.getElementById('main-action-btn');
    if (!btn || !btn.dataset.emergencyId) {
        return alert("Error: Emergency context missing. Please refresh.");
    }
    const emergencyId = btn.dataset.emergencyId;

    // 3. Show Loading
    containerInit.classList.add('hidden');
    containerLoading.classList.remove('hidden');

    try {
        const formData = new FormData();
        formData.append('emergency_id', emergencyId);

        // 4. API Call
        const response = await fetch('../api/generate_ai_insight.php', {
            method: 'POST', body: formData
        });
        const result = await response.json();

        if (result.success) {
            // --- A. Update Score ---
            scoreText.innerText = result.score + '/100';
            
            // Dynamic Colors
            let colorClass = 'bg-green-500';
            let textClass = 'text-green-600';
            if(result.score > 50) { colorClass = 'bg-orange-500'; textClass = 'text-orange-600'; }
            if(result.score > 80) { colorClass = 'bg-red-500'; textClass = 'text-red-600'; }
            
            scoreText.className = `text-sm font-bold ${textClass}`;

            // --- B. Build Structured HTML ---
            const data = result.insight; // This is an Object: { risks:[], equipment:[], handling:"" }
            let html = '';

            // 1. RISKS
            if (data.risks && data.risks.length > 0) {
                html += `
                <div class="mb-3">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">Potential Risks</label>
                    <div class="flex flex-wrap gap-2">
                        ${data.risks.map(risk => `
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-red-50 text-red-700 text-xs font-bold border border-red-100">
                                <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> ${risk}
                            </span>
                        `).join('')}
                    </div>
                </div>`;
            }

            // 2. EQUIPMENT
            if (data.equipment && data.equipment.length > 0) {
                html += `
                <div class="mb-3">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">Recommended Gear</label>
                    <div class="grid grid-cols-2 gap-2">
                        ${data.equipment.map(item => `
                            <div class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-lg border border-gray-100 shadow-sm text-xs text-gray-700 font-medium">
                                <i class="fa-solid fa-check text-green-500"></i> ${item}
                            </div>
                        `).join('')}
                    </div>
                </div>`;
            }

            // 3. HANDLING
            if (data.handling) {
                html += `
                <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-hands-holding-circle text-blue-500 mt-0.5"></i>
                        <div>
                            <p class="text-[10px] font-bold text-blue-400 uppercase mb-0.5">Handling Advice</p>
                            <p class="text-xs text-blue-900 leading-relaxed font-medium">"${data.handling}"</p>
                        </div>
                    </div>
                </div>`;
            }

            // Inject HTML
            structuredContainer.innerHTML = html;

            // Show Results
            containerLoading.classList.add('hidden');
            containerResults.classList.remove('hidden');

        } else {
            alert("AI Error: " + result.message);
            containerLoading.classList.add('hidden');
            containerInit.classList.remove('hidden');
        }
    } catch (error) {
        console.error(error);
        alert("Connection Failed");
        containerLoading.classList.add('hidden');
        containerInit.classList.remove('hidden');
    }
}