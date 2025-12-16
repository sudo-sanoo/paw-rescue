<!-- member_adopt.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';

requireRole(['user', 'volunteer']);
?>

<!-- VIEW: Adopt (Animals) -->
<div id="view-adopt" class="hidden animate-fade-in space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <h3 class="text-lg font-bold text-gray-800">Find a Companion</h3>
        <!-- Custom Dropdowns -->
        <div class="flex gap-3 relative z-30">
            <!-- Filter: Type -->
            <div class="relative custom-dropdown-container">
                <button onclick="toggleCustomDropdown('adopt-type')" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg px-4 py-2.5 hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm focus:ring-2 focus:ring-orange-100 min-w-[140px] justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="paw-print" class="w-4 h-4 text-gray-400"></i>
                        <span id="adopt-type-label">All Types</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                </button>
                <!-- Menu -->
                <div id="adopt-type-menu" class="custom-dropdown-menu hidden absolute left-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden dropdown-enter z-50">
                    <div class="p-1">
                        <button onclick="selectCustomOption('adopt-type', 'all', 'All Types')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">All Types</button>
                        <button onclick="selectCustomOption('adopt-type', 'dog', 'Dogs')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Dogs</button>
                        <button onclick="selectCustomOption('adopt-type', 'cat', 'Cats')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Cats</button>
                        <button onclick="selectCustomOption('adopt-type', 'bird', 'Birds')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Birds</button>
                    </div>
                </div>
            </div>
            <!-- Filter: Age -->
            <div class="relative custom-dropdown-container">
                <button onclick="toggleCustomDropdown('adopt-age')" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg px-4 py-2.5 hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm focus:ring-2 focus:ring-orange-100 min-w-[140px] justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="hourglass" class="w-4 h-4 text-gray-400"></i>
                        <span id="adopt-age-label">Any Age</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                </button>
                <!-- Menu -->
                <div id="adopt-age-menu" class="custom-dropdown-menu hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden dropdown-enter z-50">
                    <div class="p-1">
                        <button onclick="selectCustomOption('adopt-age', 'any', 'Any Age')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Any Age</button>
                        <button onclick="selectCustomOption('adopt-age', 'baby', 'Puppy/Kitten')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Puppy/Kitten</button>
                        <button onclick="selectCustomOption('adopt-age', 'young', 'Young')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Young</button>
                        <button onclick="selectCustomOption('adopt-age', 'adult', 'Adult')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Adult</button>
                        <button onclick="selectCustomOption('adopt-age', 'senior', 'Senior')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-600 rounded-lg transition-colors">Senior</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition-all">
            <div class="h-48 overflow-hidden relative">
                <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=500&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <span class="absolute top-2 right-2 bg-white/90 backdrop-blur text-gray-800 text-xs font-bold px-2 py-1 rounded">2 years</span>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-gray-800 text-lg">Barnaby</h4>
                    <i data-lucide="dog" class="w-4 h-4 text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-4">Golden Retriever Mix • Male</p>
                <button class="w-full border border-orange-500 text-orange-600 hover:bg-orange-50 font-medium py-2 rounded-lg transition-colors">View Profile</button>
            </div>
        </div>
        <!-- Card 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition-all">
            <div class="h-48 overflow-hidden relative">
                <img src="https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=500&q=80" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <span class="absolute top-2 right-2 bg-white/90 backdrop-blur text-gray-800 text-xs font-bold px-2 py-1 rounded">6 months</span>
            </div>
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-bold text-gray-800 text-lg">Milo</h4>
                    <i data-lucide="cat" class="w-4 h-4 text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-4">Tabby • Male</p>
                <button class="w-full border border-orange-500 text-orange-600 hover:bg-orange-50 font-medium py-2 rounded-lg transition-colors">View Profile</button>
            </div>
        </div>
    </div>
</div>