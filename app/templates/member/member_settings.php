<!-- member_settings.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helper_func.php';

requireRole(['user', 'volunteer', 'veterinarian']);

// Fetch current user info
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fallbacks
$user_id = $user['user_id'] ?? $user_id;

$full_name = $user['full_name'] ?? '';

$role_map = [
    'user'       => 'Member',
    'volunteer'    => 'Volunteer',
    'veterinarian' => 'Veterinarian System Administrator',
];
$role_identifier = $user['role'] ?? 'user';
$user_role = $role_map[$role_identifier] ?? ucfirst($role_identifier);

$phone_number = isset($user['phone']) ? str_replace("+60", "", $user['phone']) : '';

$email = $user['email'] ?? '';
$has_email = !empty($email);

$profile_photo = $user['profile_photo'] ?? ''; // stored as relative path like "images/uploads/avatars/abc.png"
$profile_photo_attr = $profile_photo ? $profile_photo : '';
$profile_photo_src = $profile_photo ? ('../../' . $profile_photo) : ''; // member template path -> adjust if needed

$initials = getInitials($full_name);
?>

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
                        <span id="settings-avatar-initials" class="<?php echo $profile_photo ? 'hidden' : ''; ?>"><?php echo htmlspecialchars(getInitials($full_name)); ?></span>
                        <img id="settings-avatar-img"
                            src="<?php echo htmlspecialchars($profile_photo_src); ?>"
                            data-profile="<?php echo htmlspecialchars($profile_photo_attr); ?>"
                            class="w-full h-full object-cover <?php echo $profile_photo ? '' : 'hidden'; ?>"
                            alt="Profile">
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
                        <input type="text" id="display-fullname" value="<?php echo htmlspecialchars($full_name);?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>

                    <!-- Phone Number (Disabled, display only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">+60</span>
                            </div>
                            <input type="tel" id="display-phone" value="<?php echo htmlspecialchars($phone_number);?>" disabled class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <!-- Email Address (Display Logic: "No Email" text or Disabled Input) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <!-- State 1: No Email -->
                        <div id="display-email-empty" class="text-gray-500 py-2 text-sm italic <?php echo $email ? 'hidden' : ''; ?>">
                            You do not have an email address.
                        </div>
                        <!-- State 2: Has Email (Hidden by default) -->
                        <div id="display-email-filled" class="<?php echo $email ? '' : 'hidden'; ?>">
                            <input type="email" id="display-email-value" value="<?php echo htmlspecialchars($email); ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                        </div>
                    </div>

                    <!-- User ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_id);?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                    </div>
                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_role);?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
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
                    <button onclick="openModal('name-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change</button>
                </div>
                <!-- Change Phone -->
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Phone Number</p><p class="text-sm text-gray-500">Update your primary login number.</p></div>
                    <button onclick="openModal('phone-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change</button>
                </div>
                <!-- Change/Add Email -->
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Email Address</p><p class="text-sm text-gray-500">Manage your connected email.</p></div>
                    <button id="security-email-btn" onclick="openModal('email-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <?php echo $has_email ? 'Change' : 'Add'; ?>
                    </button>
                </div>
                <!-- Change Password -->
                <div class="flex items-center justify-between py-2">
                    <div><p class="font-medium text-gray-800">Password</p><p class="text-sm text-gray-500">Keep your password confidential.</p></div>
                    <button onclick="openModal('password-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">Change</button>
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