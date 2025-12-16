// --- Emergency Form Logic ---

// Mock Geolocation
function getLocation() {
    const input = document.getElementById('location-input');
    
    // Simulate loading
    input.setAttribute('placeholder', 'Locating...');
    
    setTimeout(() => {
        input.value = "Central Park, Near West Entrance";
        // Visual feedback
        input.focus();
    }, 800);
}

// Photo Gallery Logic
let selectedPhotos = [];
const MAX_PHOTOS = 3;

function handlePhotoUpload(input) {
    const gallery = document.getElementById('photo-gallery');
    const addBtn = document.getElementById('add-photo-btn');
    const counter = document.getElementById('photo-counter');

    if (input.files) {
        const files = Array.from(input.files);
        
        // Limit check
        if (selectedPhotos.length + files.length > MAX_PHOTOS) {
            alert(`You can only upload a maximum of ${MAX_PHOTOS} photos.`);
            return;
        }

        files.forEach(file => {
            selectedPhotos.push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'photo-thumbnail relative w-28 h-28 shrink-0 rounded-2xl overflow-hidden shadow-md group border border-gray-100';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                    <button type="button" onclick="removePhoto(this, '${file.name}')" class="remove-btn absolute top-1 right-1 bg-black/50 hover:bg-red-500 text-white p-1 rounded-full opacity-0 transition-all z-10 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                `;
                // Insert before the add button
                gallery.insertBefore(div, addBtn);
            }
            reader.readAsDataURL(file);
        });

        // Update UI State
        counter.innerText = `${selectedPhotos.length}/${MAX_PHOTOS}`;
        
        if (selectedPhotos.length >= MAX_PHOTOS) {
            addBtn.classList.add('hidden');
        }
        
        // Clear input so same file can be selected again if removed
        input.value = '';
    }
}

function removePhoto(btn, fileName) {
    // Remove from DOM
    const container = btn.closest('.photo-thumbnail');
    container.remove();
    
    // Remove from Array (Mock logic based on index or name)
    // In a real app we'd track IDs, here we just pop or filter
    selectedPhotos.pop(); 

    // Update UI State
    document.getElementById('photo-counter').innerText = `${selectedPhotos.length}/${MAX_PHOTOS}`;
    document.getElementById('add-photo-btn').classList.remove('hidden');
}