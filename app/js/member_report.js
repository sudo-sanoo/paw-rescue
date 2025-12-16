// --- Emergency Form Logic ---

// --- GOOGLE MAPS LOGIC ---
// Global variable to track the map instance
let mapInstance;
let geocoderInstance;

/**
 * 1. The Entry Point
 * Call this function immediately after AJAX .innerHTML insertion finishes.
 */
window.initReportPage = function() {
    loadGoogleMapsAPI()
        .then(() => {
            renderMap();
        })
        .catch(err => console.error("Maps API Load Error:", err));
};

/**
 * 2. Dynamic Script Loader
 * Checks if Google Maps is already loaded. If not, injects the script tag.
 */
let googleMapsLoadingPromise = null;

function loadGoogleMapsAPI() {
    // 1. If API is already fully loaded and ready
    if (window.google && window.google.maps && window.google.maps.importLibrary) {
        return Promise.resolve();
    }

    // 2. If a request is already in progress, return that existing promise
    if (googleMapsLoadingPromise) {
        return googleMapsLoadingPromise;
    }

    // 3. Initialize the loading process
    googleMapsLoadingPromise = new Promise((resolve, reject) => {
        // Create a unique global callback name
        const callbackName = 'googleMapsInitCallback';

        // Define the global callback that Google Maps will trigger
        window[callbackName] = () => {
            resolve();
            delete window[callbackName]; // Clean up
        };

        const script = document.createElement('script');
        // KEY FIX: Add &callback=googleMapsInitCallback
        script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_KEY}&libraries=places&loading=async&v=weekly&callback=${callbackName}`;
        script.id = 'gmaps-script';
        script.async = true;
        script.defer = true;
        
        script.onerror = (err) => {
            googleMapsLoadingPromise = null; // Reset promise on error so we can try again
            reject(err);
        };
        
        document.head.appendChild(script);
    });

    return googleMapsLoadingPromise;
}

/**
 * 3. The Actual Map Logic (Universal Version)
 * Handles both Modern (importLibrary) and Legacy (global google.maps) loading modes.
 */
async function renderMap() {
    const mapEl = document.getElementById("google-map");
    if (!mapEl) return;

    // Define variables for the classes we need
    let MapClass, GeocoderClass;

    // CHECK: Does this version support dynamic import? (Modern Mode)
    if (google.maps.importLibrary) {
        const { Map } = await google.maps.importLibrary("maps");
        const { Geocoder } = await google.maps.importLibrary("geocoding");
        MapClass = Map;
        GeocoderClass = Geocoder;
    } 
    // FALLBACK: Use global objects (Legacy Mode)
    else {
        MapClass = google.maps.Map;
        GeocoderClass = google.maps.Geocoder;
    }

    // Remove loading indicator
    const loader = document.getElementById('map-loader');
    if(loader) loader.classList.add('hidden');
    
    // Initialize Map using the resolved class
    const defaultCenter = { lat: 6.028609836669974, lng: 116.12939120708417 }; // TARUMT Sabah Branch
    
    mapInstance = new MapClass(mapEl, {
        center: defaultCenter,
        zoom: 15,
        disableDefaultUI: true,
        zoomControl: false,
        gestureHandling: "greedy",
        styles: [{ "featureType": "poi", "elementType": "labels", "stylers": [{ "visibility": "off" }] }]
    });

    geocoderInstance = new GeocoderClass();

    mapInstance.addListener("idle", () => {
        const center = mapInstance.getCenter();
        geocodePosition(center);
    });

    // Trigger location search
    panToCurrentLocation();
}

window.panToCurrentLocation = function() {
    const input = document.getElementById('location-input');
    
    // Check if mapInstance exists, not isMapInitialized
    if (navigator.geolocation && mapInstance) {
        input.value = "Locating...";
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };

                document.getElementById('latitude').value = pos.lat;
                document.getElementById('longitude').value = pos.lng;

                mapInstance.setCenter(pos);
                mapInstance.setZoom(17);
            },
            (error) => {
                console.error("Geolocation failed:", error);
                input.value = "Location access denied. Please drag map.";
            }
        );
    } else {
        console.log("Map not ready or Geolocation not supported");
    }
}

function geocodePosition(pos) {
    const input = document.getElementById('location-input');
    const hiddenAddress = document.getElementById('full-address');
    const hiddenLat = document.getElementById('latitude');
    const hiddenLng = document.getElementById('longitude');

    hiddenLat.value = pos.lat();
    hiddenLng.value = pos.lng();
    
    geocoderInstance.geocode({ location: pos }, (results, status) => {
        if (status === "OK") {
            if (results[0]) {
                // Clean up address for display (remove country if too long)
                let address = results[0].formatted_address;

                hiddenAddress.value = address;

                // Simplification for UI
                input.value = address.split(',').slice(0, 3).join(', ');
            } else {
                input.value = "Unknown location";
            }
        } else {
            input.value = "Cannot determine address";
        }
    });
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

window.removePhoto = function(btn, fileName) {
    // Remove from DOM
    const container = btn.closest('.photo-thumbnail');
    container.remove();
    
    // Remove from Array
    selectedPhotos = selectedPhotos.filter(f => f.name !== fileName);

    // Update UI State
    document.getElementById('photo-counter').innerText = `${selectedPhotos.length}/${MAX_PHOTOS}`;
    document.getElementById('add-photo-btn').classList.remove('hidden');
}

// --- Submission Logic ---
window.handleEmergencySubmit = async function(event) {
    event.preventDefault();
    const submitBtn = document.getElementById('submit-emergency-btn');
    const descriptionInput = document.getElementById('description-input');
    
    // 1. Validation
    if (selectedPhotos.length < 1) {
        showErrorModal("Missing Evidence", "Please upload at least 1 photo of the animal/situation.");
        return;
    }

    if (!document.getElementById('latitude').value || !document.getElementById('full-address').value) {
        showErrorModal("Location Required", "Please allow the map to finish locating the position.");
        return;
    }

    if (!descriptionInput.value.trim()) {
        showErrorModal("Notes Required", "Please provide a brief description in the notes.");
        return;
    }

    // 2. Prepare Data
    const formData = new FormData(event.target);
    
    // Append photos manually (since manipulated them in the JS array `selectedPhotos`)
    selectedPhotos.forEach((file, index) => {
        formData.append(`photo_evidence_${index + 1}`, file);
    });

    // 3. UI Feedback
    const originalBtnContent = submitBtn.innerHTML;
    submitBtn.innerHTML = `<span class="animate-pulse">Submitting...</span>`;
    submitBtn.disabled = true;

    try {
        const response = await fetch('../api/submit_emergency.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showSuccessModal("Report Submitted", "Help is on the way! The rescue team has been notified.", 'fa-solid fa-check-circle text-orange-500', () => {
                window.location.reload(); 
            });
        } else {
            throw new Error(result.message || "Submission failed");
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorModal("Submission Error", error.message);
        submitBtn.innerHTML = originalBtnContent;
        submitBtn.disabled = false;
    }
};