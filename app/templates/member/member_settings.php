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

// Fetch Rescuer Application History
$stmt_rescuer_application = $conn->prepare("
    SELECT application_id, status, created_at
    FROM rescuer_applications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt_rescuer_application->bind_param("s", $user_id);
$stmt_rescuer_application->execute();
$history_result = $stmt_rescuer_application->get_result();
$applications = $history_result->fetch_all(MYSQLI_ASSOC);

// Fallbacks
$user_id = $user['user_id'] ?? $user_id;

$full_name = $user['full_name'] ?? '';

$role_map = [
    'user'       => 'Community Volunteer',
    'volunteer'    => 'Community Rescuer',
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

        <!-- History Section -->
        <div class="p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5 text-orange-500"></i>
                History
            </h3>
            <?php if ($role_identifier === 'user' || $role_identifier === 'volunteer'): ?>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <div><p class="font-medium text-gray-800">Rescuer Application</p><p class="text-sm text-gray-500">View your past applications to be a rescuer.</p></div>
                    <button onclick="openModal('application-history-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">&nbsp;View&nbsp;</button>
                </div>
            </div>
            <?php endif; ?>
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
                        <?php echo $has_email ? 'Change' : '&nbsp;&nbsp;&nbsp;Add&nbsp;&nbsp;&nbsp;'; ?>
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

<!-- Application History Modal (Level 1) -->
<div id="application-history-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModalById('application-history-modal')"></div>
    
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-2xl shadow-2xl w-[95%] max-w-2xl overflow-hidden modal-enter flex flex-col max-h-[85vh]">
        
        <div class="p-5 md:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-20">
            <div>
                <h3 class="text-lg md:text-xl font-bold text-gray-800">Application History</h3>
                <p class="text-xs md:text-sm text-gray-500">View details of your past applications.</p>
            </div>
            <button onclick="closeModalById('application-history-modal')" class="text-gray-400 hover:text-gray-600 bg-white p-2 rounded-lg border border-gray-200 shadow-sm hover:shadow transition-all active:bg-gray-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-0 scrollbar-thin">
            <table class="w-full text-left border-collapse">
                
                <thead class="hidden md:table-header-group bg-gray-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">No.</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Application ID</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Submitted On</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">Status</th>
                    </tr>
                </thead>

                <tbody id="application-history-list" class="divide-y divide-gray-100">
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 whitespace-nowrap text-center text-sm text-gray-500 italic col-span-full">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-3 bg-gray-50 rounded-full">
                                        <i data-lucide="inbox" class="w-8 h-8 text-gray-300"></i>
                                    </div>
                                    <span>You have not submitted any applications yet.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $total_apps = count($applications);
                        foreach ($applications as $index => $app): 
                            $app_id = htmlspecialchars($app['application_id']);
                            $date = date("d M Y", strtotime($app['created_at']));
                            $status = $app['status'];
                            $display_no = $total_apps - $index;

                            // Badge Logic (No changes needed here)
                            if ($status === 'pending') {
                                $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"><span class="w-1.5 h-1.5 bg-yellow-500 rounded-full mr-1.5"></span>Pending</span>';
                            } elseif ($status === 'approved') {
                                $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span>Approved</span>';
                            } else {
                                $badge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"><span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>Rejected</span>';
                            }
                        ?>
                        
                        <tr class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-1 p-4 border-b border-gray-100 items-center hover:bg-gray-50 cursor-pointer transition-colors md:table-row" 
                            onclick="viewApplicationDetails(this)" 
                            data-id="<?php echo $app_id; ?>"
                            data-date="<?php echo $date; ?>"
                            data-status="<?php echo $status; ?>"
                            data-reason="<?php echo isset($app['rejection_reason']) ? htmlspecialchars($app['rejection_reason']) : ''; ?>">
                            
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                <?php echo $display_no; ?>
                            </td>

                            <td class="text-gray-900 font-semibold md:font-medium md:px-6 md:py-4 whitespace-nowrap text-sm">
                                <?php echo $app_id; ?>
                            </td>

                            <td class="text-xs text-gray-500 md:text-sm md:text-gray-500 md:px-6 md:py-4 whitespace-nowrap col-start-1">
                                <?php echo $date; ?>
                            </td>

                            <td class="text-right row-span-2 md:row-span-1 md:text-left md:px-6 md:py-4 whitespace-nowrap text-sm col-start-2 row-start-1">
                                <?php echo $badge; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100 text-center text-xs text-gray-400">
            Tap on a card to view full details
        </div>
    </div>
</div>

<!-- Application History Modal (Level 2) -->
<div id="application-details-modal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-md" onclick="closeModalById('application-details-modal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg modal-enter p-4">
        <div id="application-details-content">
            </div>
    </div>
</div>