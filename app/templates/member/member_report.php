<!-- member_report.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';

requireRole(['user', 'volunteer', 'veterinarian']);
?>

<!-- VIEW: Report Emergency (Form) -->
<div id="view-report" class="hidden animate-fade-in max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg border border-red-100 overflow-hidden">
        <div class="bg-red-50 p-6 border-b border-red-100 flex items-center gap-4">
            <div class="bg-red-100 p-3 rounded-full text-red-600">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Report an Injured Animal</h2>
                <p class="text-red-600 text-sm font-medium">Please only use this for genuine emergencies.</p>
            </div>
        </div>
        
        <div class="p-8 space-y-6">
            <!-- Location -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <div class="flex gap-2">
                    <button class="flex-1 border border-gray-300 rounded-lg py-3 px-4 text-gray-600 hover:bg-gray-50 flex items-center justify-center gap-2 transition-colors">
                        <i data-lucide="map-pin" class="w-5 h-5 text-orange-500"></i>
                        Use Current Location
                    </button>
                    <button class="flex-1 border border-gray-300 rounded-lg py-3 px-4 text-gray-600 hover:bg-gray-50 flex items-center justify-center gap-2 transition-colors">
                        <i data-lucide="map" class="w-5 h-5 text-blue-500"></i>
                        Select on Map
                    </button>
                </div>
                <p class="text-xs text-green-600 mt-2 flex items-center gap-1 hidden">
                    <i data-lucide="check" class="w-3 h-3"></i> Location acquired: 123 Main St, Springfield
                </p>
            </div>

            <!-- Photo Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Photo Evidence</label>
                <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-orange-400 hover:bg-orange-50 transition-colors cursor-pointer">
                    <i data-lucide="camera" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600 font-medium">Click to upload or take a photo</p>
                    <p class="text-xs text-gray-400 mt-1">Helps volunteers identify the animal</p>
                </div>
            </div>

            <!-- Details -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description of Situation</label>
                <textarea rows="3" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-200 focus:border-orange-500 outline-none transition-all" placeholder="e.g., Dog with injured leg, lying near the sidewalk..."></textarea>
            </div>

            <!-- Animal Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Animal Type (Best Guess)</label>
                <div class="grid grid-cols-4 gap-3">
                    <button class="border border-orange-500 bg-orange-50 text-orange-700 py-2 rounded-lg text-sm font-medium">Dog</button>
                    <button class="border border-gray-200 hover:border-gray-300 py-2 rounded-lg text-sm text-gray-600">Cat</button>
                    <button class="border border-gray-200 hover:border-gray-300 py-2 rounded-lg text-sm text-gray-600">Bird</button>
                    <button class="border border-gray-200 hover:border-gray-300 py-2 rounded-lg text-sm text-gray-600">Other</button>
                </div>
            </div>

            <button class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-red-200 transition-transform hover:scale-[1.01] active:scale-[0.99] flex items-center justify-center gap-2">
                <span>Submit Emergency Report</span>
                <i data-lucide="send" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
</div>