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
        progress: '15%',
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

        if (nextStatus === 'treating') {
            const vetId = btn.dataset.vetId;
            if (vetId) {
                formData.append('vet_id', vetId);
            } else {
                // Failsafe: If page was refreshed and somehow vetId is missing
                console.warn("Vet ID missing. Attempting to proceed without specific assignment.");
            }
        }

        const response = await fetch('../../api/update_mission_status.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            updateState(nextStatus);

            if (nextStatus === 'transporting') {
                console.log("Status changed to Transporting. calculating vet route...");
                
                // Get the Emergency Location (Where we are now)
                const mapEl = document.getElementById('live-tracking-map');
                if (mapEl) {
                    const currentLat = mapEl.dataset.destLat;
                    const currentLng = mapEl.dataset.destLng;

                    // Call the function we added to window scope earlier
                    if (typeof window.rerouteToVet === 'function') {
                        window.rerouteToVet(currentLat, currentLng);
                    } else {
                        console.warn("rerouteToVet function not found. Check rescuer_emergencies.js");
                    }
                }
            }
            
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

    const progressLine = document.getElementById('progress-line');
    if (progressLine) {
        progressLine.style.width = config.progress;
    }

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

// --- GOOGLE MAPS DYNAMIC LOADER ---

window.loadLiveMissionMap = function() {
    if (!document.getElementById('live-tracking-map')) return;

    // Check if Google Maps is already loaded
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initLiveMissionMap(); 
    } else {
        injectMapsScript(); 
    }
};

let isMapsScriptLoading = false;

function injectMapsScript() {
    if (isMapsScriptLoading) return; 
    isMapsScriptLoading = true;

    if (typeof GOOGLE_MAPS_KEY === 'undefined') {
        console.error("GOOGLE_MAPS_KEY is missing!");
        return;
    }

    const script = document.createElement('script');
    // loading 'marker' library is required for AdvancedMarkerElement
    script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_KEY}&libraries=places,marker&callback=initLiveMissionMap`;
    script.async = true;
    script.defer = true;
    document.body.appendChild(script);
}

// --- MAP LOGIC ---

let liveMap;
let directionsService;
let directionsRenderer;
let rescuerMarker;
let dbUpdateInterval;
let routeThrottleTimer = null; // Prevents API spamming

let currentRescuerPos = { lat: null, lng: null };

window.initLiveMissionMap = async function() {
    const mapEl = document.getElementById('live-tracking-map');
    if (!mapEl) return;

    // 1. Reset State (Fixes "Ghost Map" issues on re-entry)
    routeThrottleTimer = null; 
    rescuerMarker = null;

    // 2. Get Destination
    const destLat = parseFloat(mapEl.dataset.destLat);
    const destLng = parseFloat(mapEl.dataset.destLng);
    const emergencyId = mapEl.dataset.emergencyId;

    if (!destLat || !destLng) return;
    const destination = { lat: destLat, lng: destLng };

    // 3. Import Libraries (New Modern Way)
    const { Map } = await google.maps.importLibrary("maps");
    const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker"); 

    // 4. Initialize Map
    liveMap = new Map(mapEl, {
        zoom: 15,
        center: destination, 
        disableDefaultUI: false,
        mapId: "LIVE_MISSION_MAP" // REQUIRED for AdvancedMarkerElement
    });

    // 5. Setup Directions
    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: liveMap,
        suppressMarkers: true, 
        polylineOptions: {
            strokeColor: "#ea580c", 
            strokeWeight: 5
        }
    });

    // 6. Add Destination Marker (Using AdvancedMarkerElement)
    // Create a custom red pin
    const destPin = new PinElement({
        background: "#DC2626", // Red
        borderColor: "#ffffff",
        glyphColor: "#ffffff",
        scale: 1.2
    });

    new AdvancedMarkerElement({
        map: liveMap,
        position: destination,
        title: "Emergency Location",
        content: destPin.element // Use the custom pin
    });

    // 7. Start Tracking
    startTracking(destination, emergencyId, AdvancedMarkerElement);

    const btn = document.getElementById('main-action-btn');
    if (btn && btn.dataset.currentStatus === 'transporting') {
        // Use the emergency location (or rescuer location if available) to find the vet
        const destLat = mapEl.dataset.destLat;
        const destLng = mapEl.dataset.destLng;
        // Small delay to ensure everything is ready
        setTimeout(() => {
            if (typeof window.rerouteToVet === 'function') {
                window.rerouteToVet(destLat, destLng);
            }
        }, 1000);
    }
};

function startTracking(destination, emergencyId, AdvancedMarkerElement) {
    if (!navigator.geolocation) return;

    navigator.geolocation.watchPosition(
        (position) => {
            const pos = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            currentRescuerPos = pos; // Save for DB Loop

            // A. Update Marker (Instant)
            updateRescuerMarker(pos, AdvancedMarkerElement, position.coords.heading);

            // B. Update Route (Throttled to every 5 seconds)
            if (!routeThrottleTimer) {
                updateRoute(pos, destination); // Run now
                routeThrottleTimer = setTimeout(() => {
                    routeThrottleTimer = null; // Allow next run after 5s
                }, 5000);
            }
        },
        (error) => console.warn("GPS Error: ", error.message),
        { enableHighAccuracy: true, maximumAge: 0 }
    );

    // C. Database Sync (Every 10s)
    if (dbUpdateInterval) clearInterval(dbUpdateInterval);
    dbUpdateInterval = setInterval(() => {
        if (currentRescuerPos.lat) updateLocationInDB(emergencyId, currentRescuerPos);
    }, 10000); 
}

function updateRescuerMarker(pos, AdvancedMarkerElement, heading) {
    // Custom Blue Arrow Pin
    if (!rescuerMarker) {
        const rescuerPin = document.createElement('div');
        rescuerPin.innerHTML = '<i class="fa-solid fa-circle-arrow-up text-blue-600 text-3xl bg-white rounded-full shadow-md"></i>';
        
        rescuerMarker = new AdvancedMarkerElement({
            map: liveMap,
            position: pos,
            title: "You",
            content: rescuerPin
        });
    } else {
        rescuerMarker.position = pos;
    }

    // Rotate the arrow if heading exists
    if (heading && rescuerMarker.content) {
        rescuerMarker.content.style.transform = `rotate(${heading}deg)`;
    }
}

function updateRoute(origin, destination) {
    directionsService.route({
        origin: origin,
        destination: destination,
        travelMode: google.maps.TravelMode.DRIVING
    }, (response, status) => {
        if (status === "OK") {
            directionsRenderer.setDirections(response);
            
            // Update ETA Text
            const leg = response.routes[0].legs[0];
            const statusTextEl = document.getElementById('status-text'); // Ensure you have this ID in HTML if you want to use it
            if(statusTextEl) statusTextEl.innerText = `En Route (${leg.duration.text})`;
        } else {
            // Ignore small errors, only log if critical
            if(status !== 'ZERO_RESULTS' && status !== 'OVER_QUERY_LIMIT') {
                console.error("Route failed: " + status);
            }
        }
    });
}

window.rerouteToVet = async function(currentLat, currentLng) {
    console.log("Finding nearest vet...");

    try {
        const response = await fetch(`../../api/get_nearest_vet.php?lat=${currentLat}&lng=${currentLng}`);
        const data = await response.json();

        if (!data.success) {
            alert("No veterinarians found nearby!");
            return;
        }

        const vet = data.vet;
        const vetLocation = { lat: parseFloat(vet.latitude), lng: parseFloat(vet.longitude) };

        const btn = document.getElementById('main-action-btn');
        if (btn) {
            btn.dataset.vetId = vet.user_id; // Store ID
            console.log("Locked onto Vet ID:", vet.user_id);
        }

        console.log("Nearest Vet:", vet.full_name);

        // Import Marker Library
        const { PinElement, AdvancedMarkerElement } = await google.maps.importLibrary("marker");
        
        // Green Pin for Vet
        const vetPin = new PinElement({
            background: "#16A34A", 
            borderColor: "#ffffff",
            glyphColor: "#ffffff",
            scale: 1.2
        });

        // Add Vet Marker
        new AdvancedMarkerElement({
            map: liveMap,
            position: vetLocation,
            title: `Vet: ${vet.full_name}`,
            content: vetPin.element
        });

        // Update the Route
        if (currentRescuerPos.lat) {
            updateRoute(currentRescuerPos, vetLocation);
        } else {
             // If GPS hasn't locked yet, we can't draw route, but pin is there.
             console.log("Waiting for GPS lock to draw route to vet...");
        }

        // Update UI
        const statusTextEl = document.getElementById('status-text');
        if(statusTextEl) statusTextEl.innerHTML = `Transporting to <b>${vet.full_name}</b>`;

        alert(`Route updated! Proceed to ${vet.full_name} (${parseFloat(vet.distance).toFixed(1)}km away).`);

    } catch (error) {
        console.error("Error finding vet:", error);
    }
};

async function updateLocationInDB(emergencyId, pos) {
    const formData = new FormData();
    formData.append('emergency_id', emergencyId);
    formData.append('lat', pos.lat);
    formData.append('lng', pos.lng);

    try {
        await fetch('../api/update_rescuer_location.php', { method: 'POST', body: formData });
    } catch (err) { console.error(err); }
}