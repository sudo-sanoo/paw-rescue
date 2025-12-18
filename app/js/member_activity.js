/** * LIVE EMERGENCY MODAL LOGIC - Photos Removed
 */
function openLiveEmergencyModal(id, urgency, status, rescuerName, rescuerPhone, initials) {
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

    const waLink = document.getElementById('live-rescuer-whatsapp');
    if (waLink) {
        if (rescuerPhone && rescuerPhone !== '---') {
            // Remove any non-numeric characters (dashes, spaces, plus signs)
            const cleanPhone = rescuerPhone.replace(/\D/g, '');
            waLink.href = `https://wa.me/${cleanPhone}`;
            waLink.style.display = 'flex'; // Show if phone exists
        } else {
            waLink.href = '#';
            waLink.style.display = 'none'; // Hide link if no rescuer assigned yet
        }
    }

    // Update the visual timeline steps
    updateTimelineUI(status);

    if (backdrop && content) {
        backdrop.classList.remove('hidden');
        content.classList.remove('hidden');
        
        // Re-init Lucide for AJAX-loaded icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Optional: Trigger the car animation
        setTimeout(() => {
            const car = document.getElementById('rescuer-marker');
            if(car) car.style.transform = 'translate(50px, 50px)';
        }, 300);
    }
}

/**
 * Manages the color and pulsing state of the timeline
 * Based on DB ENUM: pending, otw, transporting, treating, treated
 */
function updateTimelineUI(dbStatus) {
    const status = dbStatus.toLowerCase();
    
    // 1. Map for Timeline Steps
    const statusMap = {
        'pending': 'submitted',
        'otw': 'on_the_way',
        'transporting': 'transporting',
        'treating': 'treating',
        'treated': 'resolved'
    };

    const currentStepId = statusMap[status] || 'submitted';
    const steps = ['submitted', 'on_the_way', 'arrived', 'transporting', 'treating', 'resolved'];
    const currentIdx = steps.indexOf(currentStepId);

    // 2. MAP ELEMENTS (Radar & Labels)
    const emergencyRadar = document.getElementById('emergency-radar');
    const emergencyLabel = document.getElementById('emergency-label');
    const rescuerMarker = document.getElementById('rescuer-marker');

    // Reset Map to default state first
    if (emergencyRadar) emergencyRadar.classList.add('animate-ping', 'bg-red-500/10');
    if (emergencyLabel) emergencyLabel.innerText = "Emergency Location";

    if (rescuerMarker) {
        const markerText = rescuerMarker.querySelector('span');
        const markerIcon = rescuerMarker.querySelector('i');
        const markerContainer = rescuerMarker.querySelector('div');

        switch(status) {
            case 'pending':
                rescuerMarker.style.opacity = '0';
                break;
            case 'otw':
                rescuerMarker.style.opacity = '1';
                rescuerMarker.style.top = '20%'; 
                rescuerMarker.style.left = '15%';
                markerText.innerText = "RESCUER"; // Simple identifier
                break;
            case 'transporting':
                rescuerMarker.style.top = '45%'; 
                rescuerMarker.style.left = '40%';
                markerText.innerText = "TRANSPORTING";
                markerIcon.className = "fas fa-truck-medical animate-bounce";
                markerContainer.classList.replace('bg-blue-600', 'bg-orange-500');
                break;
            case 'treating':
                // CHANGE 1: Move to Vet Location
                rescuerMarker.style.top = '35%'; 
                rescuerMarker.style.left = '25%';
                markerText.innerText = "AT VET";
                
                // CHANGE 2: Update Emergency Point to Vet Name & Stop Radar
                if (emergencyLabel) emergencyLabel.innerText = "Paws & Claws Vet Clinic";
                if (emergencyRadar) emergencyRadar.classList.add('opacity-0');
                
                markerContainer.className = "bg-purple-600 text-white p-2.5 rounded-xl shadow-xl border-2 border-white flex items-center gap-2";
                break;
            case 'treated':
                rescuerMarker.style.top = '40%';
                rescuerMarker.style.left = '50%';
                markerText.innerText = "RESOLVED";
                
                // CHANGE 3: Stay at Vet Name & Stop Radar
                if (emergencyLabel) emergencyLabel.innerText = "Paws & Claws Vet Clinic";
                if (emergencyRadar) emergencyRadar.classList.add('opacity-0');
                
                markerContainer.className = "bg-green-600 text-white p-2.5 rounded-xl shadow-xl border-2 border-white flex items-center gap-2 scale-110";
                break;
        }
    }

    // 3. Update Timeline CSS (Fixed text issue)
    steps.forEach((stepId, index) => {
        const container = document.getElementById(`step-${stepId}`);
        if (!container) return;

        const dot = container.querySelector('div:first-child');
        const textElement = container.querySelector('p:last-child'); // The label like "Rescuer On The Way"

        // Reset
        container.classList.remove('opacity-40');
        dot.className = "absolute -left-[31px] top-1 w-4 h-4 rounded-full border-2 border-white transition-all";

        if (index < currentIdx) {
            // Completed Steps (Blue)
            dot.classList.add('bg-blue-500');
            textElement.className = "text-sm font-medium text-gray-600";
        } else if (index === currentIdx) {
            // Active Step (Orange Pulse)
            dot.classList.add('bg-orange-500', 'animate-pulse', 'ring-4', 'ring-orange-100');
            textElement.className = "text-sm font-bold text-gray-900"; 
            // We NO LONGER change the innerText here, so "Rescuer On The Way" stays put.
        } else {
            // Future Steps (Gray)
            container.classList.add('opacity-40');
            dot.classList.add('bg-gray-200');
            textElement.className = "text-sm font-medium text-gray-400";
        }
    });
}

function closeLiveEmergencyModal() {
    const backdrop = document.getElementById('live-modal-backdrop');
    const content = document.getElementById('live-modal-content');

    if (backdrop && content) {
        backdrop.classList.add('hidden');
        content.classList.add('hidden');
        content.classList.remove('modal-enter');
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