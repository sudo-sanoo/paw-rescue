<!-- VIEW: Settings -->
<div id="view-settings" class="hidden max-w-4xl mx-auto space-y-8 animate-fade-in">
    <h2 class="text-2xl font-bold text-gray-800">Account Settings</h2>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-100">
        <!-- Profile Section -->
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5 text-orange-500"></i>
                Profile Information
            </h3>
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <div class="shrink-0 flex flex-col items-center gap-3">
                    <!-- Profile Image Container -->
                    <div id="settings-avatar-container" class="w-24 h-24 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-2xl font-bold border-4 border-white shadow-sm overflow-hidden relative">
                        <span id="settings-avatar-initials">SJ</span>
                        <img id="settings-avatar-img" src="" class="w-full h-full object-cover hidden" alt="Profile">
                    </div>
                    <!-- Change Photo Button -->
                    <button onclick="triggerPhotoUpload()" class="text-sm text-orange-600 font-medium hover:text-orange-700">Change Photo</button>
                    <!-- Hidden File Input -->
                    <input type="file" id="profile-upload" class="hidden" accept=".jpg,.png,.jpeg" onchange="handleFileSelect(this)">
                </div>
                
                <div class="flex-1 w-full space-y-4">
                    <!-- 1. Full Name (Disabled, display only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="display-fullname" value="Sarah Jenkins" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>

                    <!-- Phone Number (Disabled, display only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">+60</span>
                            </div>
                            <input type="tel" id="display-phone" value="123456789" disabled class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <!-- Email Address (Display Logic: "No Email" text or Disabled Input) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <!-- State 1: No Email -->
                        <div id="display-email-empty" class="text-gray-500 py-2 text-sm italic">
                            You do not have an email address.
                        </div>
                        <!-- State 2: Has Email (Hidden by default for demo start) -->
                        <div id="display-email-filled" class="hidden">
                            <input type="email" id="display-email-value" value="" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <input type="text" value="Adoption Coordinator" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Section -->
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="bell" class="w-5 h-5 text-orange-500"></i>
                Notifications
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div><p class="font-medium text-gray-800">Email Notifications</p><p class="text-sm text-gray-500">Receive daily summaries.</p></div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked onchange="saveNotificationPreference()" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Security Section (Editable Fields) -->
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="shield" class="w-5 h-5 text-orange-500"></i>
                Security
            </h3>
            <div class="space-y-4">
                <!-- Change Name -->
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Full Name</p><p class="text-sm text-gray-500">Update your display name.</p></div>
                    <button onclick="openModal('name-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change Name</button>
                </div>
                <!-- Change Phone -->
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Phone Number</p><p class="text-sm text-gray-500">Update your primary login number.</p></div>
                    <button onclick="openModal('phone-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change Phone Number</button>
                </div>
                <!-- Change/Add Email -->
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Email Address</p><p class="text-sm text-gray-500">Manage your connected email.</p></div>
                    <button id="security-email-btn" onclick="openModal('email-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Add Email</button>
                </div>
                <!-- Change Password -->
                <div class="flex items-center justify-between py-2">
                    <div><p class="font-medium text-gray-800">Password</p><p class="text-sm text-gray-500">Last changed 3 months ago.</p></div>
                    <button onclick="openModal('password-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change Password</button>
                </div>
            </div>
        </div>

        <!-- Account Actions Section -->
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="power" class="w-5 h-5 text-orange-500"></i>
                Account Actions
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Log Out</p><p class="text-sm text-gray-500">Sign out of your current session.</p></div>
                    <button onclick="openModal('logout-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Log Out</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Save/Cancel Actions (Hidden by default) -->
    <div id="settings-actions" class="hidden flex justify-end gap-4 animate-fade-in sticky bottom-6 z-40 bg-white/80 p-4 rounded-xl shadow-lg border border-gray-100 backdrop-blur-sm">
            <div class="flex-1 flex items-center text-orange-600 font-medium">
            <i data-lucide="info" class="w-4 h-4 mr-2"></i> Unsaved changes
        </div>
        <button onclick="cancelSettings()" class="px-6 py-2 border border-gray-200 rounded-lg text-gray-600 font-medium hover:bg-gray-50 transition-colors">Cancel</button>
        <button onclick="saveSettings()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 transition-colors shadow-sm">Save Changes</button>
    </div>
</div>