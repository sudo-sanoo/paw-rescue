// --- MAP STATE VARIABLES ---
let activityMap;
let activityDirectionsService;
let activityDirectionsRenderer;
let getRescuerMarker;
let emergencyMarker;
let pollInterval;

// --- GOOGLE MAPS DYNAMIC LOADER (Reusable Pattern) ---
function loadActivityMap(emergencyId, destLat, destLng) {
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initActivityMap(emergencyId, destLat, destLng);
    } else {
        // If map script isn't loaded yet, load it now
        if (typeof GOOGLE_MAPS_KEY === 'undefined') {
            console.error("GOOGLE_MAPS_KEY missing");
            return;
        }
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_KEY}&libraries=places,marker&callback=onMapsApiLoaded`;
        script.async = true;
        script.defer = true;
        document.body.appendChild(script);

        // Global callback for when the script finishes
        window.onMapsApiLoaded = () => {
            initActivityMap(emergencyId, destLat, destLng);
        };
    }
}

// --- MAIN MAP LOGIC ---
async function initActivityMap(emergencyId, destLat, destLng) {
    const mapEl = document.getElementById('activity-live-map');
    if (!mapEl || !destLat || !destLng) return;

    const destination = { lat: parseFloat(destLat), lng: parseFloat(destLng) };

    // 1. Initialize Map
    const { Map } = await google.maps.importLibrary("maps");
    activityMap = new Map(mapEl, {
        zoom: 13,
        center: destination,
        disableDefaultUI: true, // Keep it clean for the modal
        zoomControl: true,      // Allow user to zoom
        mapId: "ACTIVITY_MAP_ID"
    });

    // 2. Add Emergency Marker (Red)
    emergencyMarker = new google.maps.Marker({
        position: destination,
        map: activityMap,
        title: "Emergency Scene",
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 8,
            fillColor: "#DC2626", // Red
            fillOpacity: 1,
            strokeWeight: 2,
            strokeColor: "white",
        }
    });

    // 3. Setup Directions (Routing)
    activityDirectionsService = new google.maps.DirectionsService();
    activityDirectionsRenderer = new google.maps.DirectionsRenderer({
        map: activityMap,
        suppressMarkers: true,
        polylineOptions: { strokeColor: "#2563EB", strokeWeight: 4 } // Blue line
    });

    // 4. Start Polling for Rescuer Location
    startPollingRescuer(emergencyId, destination);
}

function startPollingRescuer(emergencyId, destination) {
    // Run immediately once
    fetchRescuerLocation(emergencyId, destination);

    // Then run every 10 seconds
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(() => {
        fetchRescuerLocation(emergencyId, destination);
    }, 10000);
}

async function fetchRescuerLocation(emergencyId, destination) {
    try {
        const response = await fetch(`../api/get_rescuer_location.php?emergency_id=${emergencyId}`);
        const data = await response.json();

        if (data.success) {
            const rescuerPos = { lat: data.lat, lng: data.lng };
            updateRescuerOnMap(rescuerPos, destination);
        } else {
            console.log("Rescuer location not yet available.");
        }
    } catch (err) {
        console.error("Polling Error:", err);
    }
}

function updateRescuerOnMap(rescuerPos, destination) {
    // 1. Add/Move Rescuer Marker
    if (!getRescuerMarker) {
        getRescuerMarker = new google.maps.Marker({
            position: rescuerPos,
            map: activityMap,
            title: "Rescuer",
            icon: {
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 5,
                fillColor: "#ea580c", // Orange (distinct from route)
                fillOpacity: 1,
                strokeWeight: 2,
                strokeColor: "white",
            }
        });
    } else {
        getRescuerMarker.setPosition(rescuerPos);
    }

    // 2. Draw Route (Rescuer -> Emergency)
    activityDirectionsService.route({
        origin: rescuerPos,
        destination: destination,
        travelMode: google.maps.TravelMode.DRIVING
    }, (response, status) => {
        if (status === "OK") {
            activityDirectionsRenderer.setDirections(response);
        }
    });
}

/** * OPEN LIVE EMERGENCY MODAL TRIGGER 
 * Updated to accept lat/lng and trigger the map
 */
function openLiveEmergencyModal(id, urgency, status, rescuerName, rescuerPhone, initials, lat, lng) {
    const backdrop = document.getElementById('live-modal-backdrop');
    const content = document.getElementById('live-modal-content');

    // Helper to safely set text
    const safeSetText = (id, text) => {
        const el = document.getElementById(id);
        if (el) el.innerText = text;
    };

    safeSetText('live-meta-text', `Emergency ID: ${id} • ${urgency} Priority`);
    safeSetText('live-rescuer-name', rescuerName || 'Assigning...');
    safeSetText('live-rescuer-phone', rescuerPhone || '---');
    safeSetText('live-rescuer-initials', initials || '?');

    // Handle WhatsApp Link
    const waLink = document.getElementById('live-rescuer-whatsapp');
    if (waLink) {
        if (rescuerPhone && rescuerPhone !== '---') {
            const cleanPhone = rescuerPhone.replace(/\D/g, '');
            waLink.href = `https://wa.me/${cleanPhone}`;
            waLink.style.display = 'flex';
        } else {
            waLink.style.display = 'none';
        }
    }

    if (lat && lng && lat !== 'null' && lat !== null) {
        loadActivityMap(id, lat, lng);
    } else {
        // Optional: Handle case where location is missing (e.g. hide map)
        const mapEl = document.getElementById('activity-live-map');
        if(mapEl) mapEl.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400">Location not available</div>';
    }
    // ----------------------------------------

    if (backdrop && content) {
        backdrop.classList.remove('hidden');
        content.classList.remove('hidden');
        // Small delay for animation
        setTimeout(() => content.classList.add('translate-y-0', 'opacity-100'), 10);
    }
}

function closeLiveEmergencyModal() {
    const backdrop = document.getElementById('live-modal-backdrop');
    const content = document.getElementById('live-modal-content');
    
    // STOP POLLING to save resources when modal is closed
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }

    if (backdrop && content) {
        content.classList.remove('translate-y-0', 'opacity-100');
        setTimeout(() => {
            backdrop.classList.add('hidden');
            content.classList.add('hidden');
        }, 300);
    }
}

/** * RESOLVED CASE MODAL LOGIC 
 */
function openResolvedModal(id, report, date, loc) {
    const backdrop = document.getElementById('resolved-modal-backdrop');
    const content = document.getElementById('resolved-modal-content');

    // Update text content
    const idEl = document.getElementById('res-modal-id');
    const reportEl = document.getElementById('res-modal-report');
    const dateEl = document.getElementById('res-modal-date');
    const locEl = document.getElementById('res-modal-loc');

    if (idEl) idEl.innerText = `Emergency ID: #${id}`;
    if (reportEl) reportEl.innerText = `"${report}"`;
    if (dateEl) dateEl.innerText = date;
    if (locEl) locEl.innerText = loc;

    if (backdrop && content) {
        backdrop.classList.remove('hidden');
        content.classList.remove('hidden');
        content.classList.add('modal-enter');
    }
}

function closeResolvedModal() {
    const backdrop = document.getElementById('resolved-modal-backdrop');
    const content = document.getElementById('resolved-modal-content');

    if (backdrop && content) {
        backdrop.classList.add('hidden');
        content.classList.add('hidden');
    }
}

/** * GLOBAL EVENT HANDLERS
 */
window.onclick = function(event) {
    const liveBackdrop = document.getElementById('live-modal-backdrop');
    const resBackdrop = document.getElementById('resolved-modal-backdrop');

    if (event.target === liveBackdrop) closeLiveEmergencyModal();
    if (event.target === resBackdrop) closeResolvedModal();
}