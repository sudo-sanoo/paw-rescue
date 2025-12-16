<!-- member_report.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';

requireRole(['user', 'volunteer']);
?>

<!-- VIEW: Report Emergency -->
<div id="view-report" class="animate-fade-in flex flex-col lg:flex-row gap-6 h-auto lg:h-[calc(100vh-140px)]">
    
    <!-- 1. Visual Context (Map & Photo) -->
    <div class="lg:w-7/12 flex flex-col gap-4 h-[500px] lg:h-full">
        <!-- Map Card -->
        <div class="flex-1 bg-gray-100 rounded-3xl relative overflow-hidden group min-h-[300px] border-4 border-white shadow-lg shadow-blue-100/50">
                                
            <!-- THE GOOGLE MAP -->
            <div id="google-map" class="absolute inset-0 w-full h-full bg-gray-200"></div>

            <!-- Center Pin Overlay (Stays fixed while map moves) -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center pointer-events-none z-10 pb-9">
                <div class="w-4 h-4 bg-orange-500 rounded-full animate-ping absolute top-[28px]"></div>
                <i class="fa-solid fa-location-dot text-5xl text-orange-600 drop-shadow-xl z-10 relative"></i>
                <div class="w-2 h-2 bg-black opacity-20 rounded-full absolute bottom-[5px] blur-[1px]"></div>
            </div>

            <!-- Location Input Overlay -->
            <div class="absolute top-4 left-4 right-4 z-20">
                <div class="bg-white/95 backdrop-blur-md p-1.5 pr-2 rounded-2xl shadow-lg flex items-center gap-2 border border-white/20">
                    <div class="p-2 bg-gray-100 rounded-xl text-gray-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input type="text" id="location-input" class="bg-transparent border-none text-sm w-full focus:ring-0 font-medium text-gray-700" placeholder="Searching location..." readonly>
                    <button onclick="panToCurrentLocation()" class="bg-blue-50 text-blue-600 p-2 rounded-xl hover:bg-blue-100 transition-colors" title="My Location">
                        <i data-lucide="crosshair" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Map Loading State -->
            <div id="map-loader" class="absolute inset-0 bg-gray-100 flex items-center justify-center z-0">
                <div class="flex flex-col items-center gap-2">
                    <div class="loader !border-gray-300 !border-t-orange-500"></div>
                    <span class="text-xs text-gray-500">Loading Map...</span>
                </div>
            </div>
        </div>

        <!-- Multi-Photo Evidence Area -->
        <div class="bg-white rounded-3xl border-4 border-white shadow-lg p-4 shrink-0 flex flex-col justify-center h-48">
            <div class="flex items-center justify-between mb-2 px-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Evidence Photos</label>
                <span id="photo-counter" class="text-[10px] font-bold text-gray-400">0/3</span>
            </div>
            
            <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide h-full items-center" id="photo-gallery">
                
                <!-- Add Button -->
                <div id="add-photo-btn" class="relative w-28 h-28 shrink-0 rounded-2xl bg-gray-50 border-2 border-dashed border-gray-300 hover:border-orange-400 hover:bg-orange-50 transition-all flex flex-col items-center justify-center cursor-pointer group">
                    <input type="file" id="file-upload" accept="image/*" multiple onchange="handlePhotoUpload(this)" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="bg-white p-2 rounded-full shadow-sm mb-2 group-hover:scale-110 transition-transform">
                        <i data-lucide="camera" class="w-5 h-5 text-gray-400 group-hover:text-orange-500"></i>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 group-hover:text-orange-600">Add Photo</span>
                </div>

                <!-- Thumbnails are injected here -->
            </div>
        </div>
    </div>

    <!-- 2. Details Form -->
    <div class="lg:w-5/12 bg-white rounded-3xl p-6 lg:p-8 shadow-xl border border-white flex flex-col h-auto lg:h-full overflow-y-auto">
        <form onsubmit="handleEmergencySubmit(event)" id="emergency-form" class="flex flex-col h-full gap-6">
            
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <input type="hidden" id="full-address" name="location_address">

            <div class="space-y-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-extrabold text-gray-800">Rescue Details</h3>
                </div>
                
                <!-- Animal Selector (Pills) -->
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 block">Animal</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer flex-1 min-w-[80px]">
                            <input type="radio" name="animal_type" value="dog" class="peer sr-only" checked>
                            <div class="py-3 rounded-2xl border border-gray-100 bg-gray-50 text-gray-400 font-medium peer-checked:bg-gray-800 peer-checked:text-white peer-checked:border-gray-800 peer-checked:shadow-lg transition-all flex flex-col items-center gap-1 hover:bg-gray-100">
                                <i class="fa-solid fa-dog text-lg"></i> 
                                <span class="text-xs">Dog</span>
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1 min-w-[80px]">
                            <input type="radio" name="animal_type" value="cat" class="peer sr-only">
                            <div class="py-3 rounded-2xl border border-gray-100 bg-gray-50 text-gray-400 font-medium peer-checked:bg-gray-800 peer-checked:text-white peer-checked:border-gray-800 peer-checked:shadow-lg transition-all flex flex-col items-center gap-1 hover:bg-gray-100">
                                <i class="fa-solid fa-cat text-lg"></i> 
                                <span class="text-xs">Cat</span>
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1 min-w-[80px]">
                            <input type="radio" name="animal_type" value="bird" class="peer sr-only">
                            <div class="py-3 rounded-2xl border border-gray-100 bg-gray-50 text-gray-400 font-medium peer-checked:bg-gray-800 peer-checked:text-white peer-checked:border-gray-800 peer-checked:shadow-lg transition-all flex flex-col items-center gap-1 hover:bg-gray-100">
                                <i class="fa-solid fa-dove text-lg"></i> 
                                <span class="text-xs">Bird</span>
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1 min-w-[80px]">
                            <input type="radio" name="animal_type" value="other" class="peer sr-only">
                            <div class="py-3 rounded-2xl border border-gray-100 bg-gray-50 text-gray-400 font-medium peer-checked:bg-gray-800 peer-checked:text-white peer-checked:border-gray-800 peer-checked:shadow-lg transition-all flex flex-col items-center gap-1 hover:bg-gray-100">
                                <i class="fa-solid fa-question text-lg"></i> 
                                <span class="text-xs">Other</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Severity Slider/Segments -->
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 block">Severity</label>
                    <div class="bg-gray-100 p-1.5 rounded-2xl flex relative">
                        <label class="flex-1 cursor-pointer z-10">
                            <input type="radio" name="urgency" value="minor" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-xl text-xs font-bold text-gray-500 peer-checked:bg-white peer-checked:text-yellow-600 peer-checked:shadow-sm transition-all flex items-center justify-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-yellow-400"></div> Minor
                            </div>
                        </label>
                            <label class="flex-1 cursor-pointer z-10">
                            <input type="radio" name="urgency" value="serious" class="peer sr-only" checked>
                            <div class="text-center py-2.5 rounded-xl text-xs font-bold text-gray-500 peer-checked:bg-white peer-checked:text-orange-600 peer-checked:shadow-sm transition-all flex items-center justify-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-orange-500"></div> Serious
                            </div>
                        </label>
                            <label class="flex-1 cursor-pointer z-10">
                            <input type="radio" name="urgency" value="critical" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-xl text-xs font-bold text-gray-500 peer-checked:bg-white peer-checked:text-red-600 peer-checked:shadow-sm transition-all flex items-center justify-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></div> Critical
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Minimal Textarea -->
                <div class="flex-1">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 block">Notes
                        <span id="desc-char-count" class="text-gray-400 font-normal ml-2 lowercase">300 out of 300 characters remaining</span>
                    </label>
                    <textarea 
                        name="description"
                        id="description-input"
                        maxlength="300"
                        oninput="updateCharCount(this, 'desc-char-count', 300)"
                        class="w-full bg-gray-50 border-0 rounded-2xl p-4 text-sm focus:ring-2 focus:ring-orange-500 min-h-[100px]" 
                        placeholder="Briefly describe the injury or condition..."></textarea>
                </div>
            </div>

            <!-- Big Action Button -->
            <button type="submit" id="submit-emergency-btn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-5 rounded-2xl shadow-xl shadow-orange-200 transform active:scale-[0.98] transition-all flex justify-between items-center px-6 mt-auto group">
                <div class="flex flex-col items-start">
                    <span class="text-lg leading-tight">Request Rescue</span>
                    <span class="text-[10px] text-orange-100 font-medium uppercase tracking-wider">Tap to Submit Report</span>
                </div>
                <div class="bg-white/20 p-2.5 rounded-full group-hover:bg-white/30 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </div>
            </button>
        </form>
    </div>
</div>