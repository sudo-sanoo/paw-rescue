/** * LIVE EMERGENCY MODAL LOGIC
 * Elements are fetched inside the functions to support AJAX loading 
 */
function openLiveEmergencyModal() {
    const backdrop = document.getElementById('live-modal-backdrop');
    const content = document.getElementById('live-modal-content');

    if (backdrop && content) {
        backdrop.classList.remove('hidden');
        content.classList.remove('hidden');
        content.classList.add('modal-enter');
        
        setTimeout(() => {
            const car = document.getElementById('rescuer-marker');
            if(car) car.style.transform = 'translate(50px, 50px)';
        }, 500);
    }
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