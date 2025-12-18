// vet_treatment.js

if (typeof lucide !== 'undefined') lucide.createIcons();

let currentEditingCard = null;

function openTreatmentModal(cardElement) {
    const modal = document.getElementById('treatment-modal');
    if (!modal) return;

    currentEditingCard = cardElement;
    
    // 1. Extract Data
    const id = cardElement.getAttribute('data-id');
    const species = cardElement.getAttribute('data-species');
    const condition = cardElement.getAttribute('data-condition');
    // Evidence images (optional)
    const imgs = [
        cardElement.getAttribute('data-img1'),
        cardElement.getAttribute('data-img2'),
        cardElement.getAttribute('data-img3')
    ];

    // 2. Populate Text
    const setText = (eid, val) => {
        const el = document.getElementById(eid);
        if(el) el.innerText = val || '---';
    };

    setText('modal-id', `CASE-ID: #${id}`);
    setText('modal-species', species);
    setText('modal-condition', condition);

    // 3. Set Hidden Input
    const idInput = document.getElementById('modal-emergency-id');
    if(idInput) idInput.value = id;

    // 4. Populate Images (SAFE MODE: Checks if element exists first)
    for(let i=0; i<3; i++) {
        const imgEl = document.getElementById(`modal-img-${i+1}`);
        if (imgEl) {
            // Only try to access nextElementSibling if imgEl exists
            const label = imgEl.nextElementSibling; 
            
            if(imgs[i] && !imgs[i].includes('placehold.co') && imgs[i] !== '') {
                imgEl.src = imgs[i];
                imgEl.classList.remove('hidden');
                if(label) label.classList.add('hidden');
            } else {
                imgEl.classList.add('hidden');
                if(label) label.classList.remove('hidden');
            }
        }
    }

    // 5. Clear Previous Inputs
    const inputs = ['modal-diagnosis', 'modal-treatment', 'modal-weight', 'modal-temp', 'modal-post-status', 'modal-outcome-note'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = '';
    });

    modal.classList.remove('hidden');
}

function closeTreatmentModal() {
    const modal = document.getElementById('treatment-modal');
    if (modal) modal.classList.add('hidden');
    currentEditingCard = null;
}

// SUBMIT LOGIC
async function submitTreatmentReport(e) {
    e.preventDefault();

    // Gather basic data
    const emergencyId = document.getElementById('modal-emergency-id').value;
    const diagnosis = document.getElementById('modal-diagnosis').value;
    const treatment = document.getElementById('modal-treatment').value;
    
    // Gather new columns
    const weight = document.getElementById('modal-weight').value;
    const temp = document.getElementById('modal-temp').value;
    const postStatus = document.getElementById('modal-post-status').value;
    
    // Gather Outcome Note (Targeting emergencies table)
    const outcomeNote = document.getElementById('modal-outcome-note').value;

    // Create Payload
    const apiData = new FormData();
    apiData.append('emergency_id', emergencyId);
    apiData.append('diagnosis', diagnosis);
    apiData.append('treatment_administered', treatment);
    apiData.append('status', status); // outcome_status enum
    apiData.append('weight', weight);
    apiData.append('temperature', temp);
    apiData.append('post_treatment_status', postStatus);
    apiData.append('outcome_note', outcomeNote); // To be stored in emergencies.outcome

    try {
        const response = await fetch('../../api/submit_treatment_report.php', {
            method: 'POST',
            body: apiData
        });
        
        // Handle non-JSON responses gracefully
        const textResponse = await response.text();
        let result;
        try {
            result = JSON.parse(textResponse);
        } catch (e) {
            console.error("Server Response:", textResponse);
            throw new Error("Invalid server response.");
        }

        if (result.success) {
            closeTreatmentModal();
            if(currentEditingCard) {
                currentEditingCard.style.transition = 'all 0.5s ease';
                currentEditingCard.style.opacity = '0';
                currentEditingCard.style.transform = 'scale(0.9)';
                setTimeout(() => currentEditingCard.remove(), 500);
            }
            alert("Report saved successfully!");
            setTimeout(() => window.location.reload(), 1000);
        } else {
            alert("Error: " + result.message);
        }
    } catch (err) {
        console.error(err);
        alert("Failed to submit. Check console for details.");
    }
}

window.submitTreatmentReport = submitTreatmentReport;
window.openTreatmentModal = openTreatmentModal;
window.closeTreatmentModal = closeTreatmentModal;