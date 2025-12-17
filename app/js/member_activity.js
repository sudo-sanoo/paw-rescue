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
    safeSetText('live-status-text', status.replace('_', ' ').toUpperCase());
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
    // 1. Map exact Database ENUM values to your HTML Step IDs
    const statusMap = {
        'pending': 'submitted',
        'otw': 'on_the_way',
        'transporting': 'transporting',
        'treating': 'treating',
        'treated': 'resolved'
    };

    // 2. Normalize the status to a valid HTML step ID
    const currentStepId = statusMap[dbStatus.toLowerCase()] || 'submitted';
    
    // The sequence must match your HTML IDs exactly
    const steps = ['submitted', 'on_the_way', 'arrived', 'transporting', 'treating', 'resolved'];
    const currentIdx = steps.indexOf(currentStepId);

    steps.forEach((stepId, index) => {
        const container = document.getElementById(`step-${stepId}`);
        if (!container) return;

        // Target the dot (the first div inside the step container)
        const dot = container.querySelector('div:first-child');
        const label = container.querySelector('.status-label');
        const text = container.querySelector('p:last-child');

        // Reset all states
        container.classList.remove('opacity-40');
        dot.className = "absolute -left-[31px] top-1 w-4 h-4 rounded-full border-2 border-white transition-all";
        if (label) label.classList.add('hidden');
        if (text) text.className = "text-sm font-medium text-gray-400";

        if (index < currentIdx) {
            // STATE: COMPLETED (Blue)
            dot.classList.add('bg-blue-500', 'ring-1', 'ring-blue-100');
            if (text) {
                text.classList.remove('text-gray-400');
                text.classList.add('text-gray-600');
            }
        } else if (index === currentIdx) {
            // STATE: ACTIVE (Orange & Pulsing)
            dot.classList.add('bg-orange-500', 'animate-pulse', 'ring-4', 'ring-orange-100');
            if (label) label.classList.remove('hidden');
            if (text) {
                text.className = "text-sm font-bold text-gray-900";
            }
        } else {
            // STATE: PENDING (Gray & Faded)
            container.classList.add('opacity-40');
            dot.classList.add('bg-gray-200');
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