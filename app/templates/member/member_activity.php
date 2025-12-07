<!-- member_activity.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';

requireRole(['user', 'volunteer', 'veterinarian']);
?>

<!-- VIEW: My Reports (Activity Tracking) -->
<div id="view-activity" class="hidden animate-fade-in max-w-4xl mx-auto space-y-6">
    <h3 class="text-lg font-bold text-gray-800">Your Reports History</h3>
    
    <!-- Report Item 1 (Active) -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 bg-blue-100 text-blue-700 px-3 py-1 rounded-bl-lg text-xs font-bold uppercase tracking-wider">
            In Progress
        </div>
        <div class="flex gap-6">
            <img src="https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=150&q=80" class="w-24 h-24 rounded-lg object-cover bg-gray-100 shrink-0">
            <div class="flex-1">
                <h4 class="font-bold text-gray-800 text-lg">Injured Stray Dog</h4>
                <p class="text-sm text-gray-500 mb-4"><i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i> Near Central Park Entrance • 2 hours ago</p>
                
                <!-- Timeline -->
                <div class="relative pl-2 space-y-6">
                    <!-- Step 1 -->
                    <div class="step-item relative pl-8">
                        <div class="step-connector"></div>
                        <div class="absolute left-0 top-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white shadow"></div>
                        <p class="text-sm font-medium text-gray-900">Report Received</p>
                        <p class="text-xs text-gray-500">10:30 AM</p>
                    </div>
                    <!-- Step 2 -->
                    <div class="step-item relative pl-8">
                        <div class="step-connector"></div>
                        <div class="absolute left-0 top-1 w-4 h-4 bg-blue-500 rounded-full border-2 border-white shadow animate-pulse"></div>
                        <p class="text-sm font-bold text-blue-600">Volunteer Dispatched</p>
                        <p class="text-xs text-gray-500">Mike R. is on the way (ETA 5 mins)</p>
                    </div>
                    <!-- Step 3 (Pending) -->
                    <div class="step-item relative pl-8 opacity-50">
                        <div class="absolute left-0 top-1 w-4 h-4 bg-gray-300 rounded-full border-2 border-white"></div>
                        <p class="text-sm font-medium text-gray-900">Rescued / At Vet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Item 2 (Completed) -->
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm opacity-75 hover:opacity-100 transition-opacity">
        <div class="flex gap-6 items-center">
            <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=150&q=80" class="w-20 h-20 rounded-lg object-cover bg-gray-100 grayscale">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <h4 class="font-bold text-gray-800">Tabby Cat</h4>
                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">Rescued</span>
                </div>
                <p class="text-sm text-gray-500">Reported on Oct 12, 2023</p>
                <p class="text-sm text-gray-600 mt-2">"Thanks for your help! The cat has been treated for dehydration and is now in foster care."</p>
            </div>
        </div>
    </div>
</div>