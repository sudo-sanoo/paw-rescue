<?php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';
require_once __DIR__ . '/../includes/config.php';

requireRole(['user', 'volunteer', 'veterinarian']);

// debugging purpose during development
// echo "you are member";

// this is a shared layout

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$full_name = $user['full_name'];

$role_map = [
    'user'       => 'Community Volunteer',
    'volunteer'    => 'Community Rescuer',
    'veterinarian' => 'Veterinarian System Administrator',
];
$role_identifier = $user['role'] ?? 'user';
$user_role = $role_map[$role_identifier] ?? ucfirst($role_identifier);

$phone = $user['phone'];

$profile_photo = $user['profile_photo'] ?? ''; // stored as relative path like "images/uploads/avatars/abc.png"

$initials = getInitials($user['full_name']);

$impact_query = "SELECT * FROM emergencies 
                 WHERE status = 'treated' 
                 ORDER BY updated_at DESC 
                 LIMIT 6"; // Limiting to 6 for layout nicety
$impact_stmt = $conn->prepare($impact_query);
$impact_stmt->execute();
$impact_stories = $impact_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <!-- Favicons -->
    <link rel="icon" href="../favicon.svg">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/member_template.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- GOOGLE MAPS API (GOOGLE MAPS JAVASCRIPT & GEOCODING)-->
    <script>
        const GOOGLE_MAPS_KEY = "<?php echo GOOGLE_MAPS_API_KEY; ?>";
    </script>
    <!-- JS -->
    <script defer src="../js/member_templates.js"></script>
    <script defer src="../js/mykad_service.js"></script>
    <script defer src="../js/member_report.js"></script>
    <script defer src="../js/member_activity.js"></script>
    <script defer src="../js/rescuer_emergencies.js"></script>
    <script defer src="../js/vet_treatment.js"></script>
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased">

    <div class="min-h-screen flex">
        
        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-overlay" onclick="toggleMobileMenu()" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden lg:hidden glass-effect"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-all duration-300 ease-in-out flex flex-col overflow-hidden whitespace-nowrap">
            <!-- Logo Area -->
            <div class="p-6 flex items-center gap-3 border-b border-gray-100 min-w-[16rem]">
                <div class="bg-[#F59E0B] p-2 rounded-lg text-white shrink-0">
                    <img src="../favicon.svg" class="h-6 w-6" alt="Icon">
                </div>
                <span class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-orange-400">
                    PawRescue
                </span>
                <button onclick="toggleMobileMenu()" class="ml-auto lg:hidden text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1 min-w-[16rem]">
                <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors bg-orange-50 text-orange-600 font-medium">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 shrink-0"></i>
                    <span>Home</span>
                </button>
                <!-- (Legacy), displayed as 'volunteer' and 'rescuer' respectively in the system -->
                <!-- 2. Report Emergency (Highlighted) -->
                <?php if ($role_identifier === 'user' || $role_identifier === 'volunteer'): ?>
                <button onclick="switchTab('report')" id="nav-report" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900 group">
                    <div class="bg-red-100 text-red-500 p-1.4 rounded-md group-hover:bg-red-500 group-hover:text-white transition-colors">
                        <i data-lucide="siren" class="w-4 h-4 shrink-0"></i>
                    </div>
                    <span class="font-semibold text-gray-700 group-hover:text-red-600">Report Emergency</span>
                </button>
                <?php endif; ?>
                <!-- 3. Emergencies Now -->
                <?php if ($role_identifier === 'volunteer' || $role_identifier === 'veterinarian'): ?>
                <button onclick="switchTab('rescuer_emergencies')" id="nav-rescuer_emergencies" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900 group">
                    <div class="relative">
                        <div class="bg-red-100 text-red-500 p-1.4 rounded-md group-hover:bg-red-500 group-hover:text-white transition-colors">
                            <i data-lucide="radio" class="w-5 h-5 shrink-0 animate-pulse"></i>
                        </div>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                    </div>
                    <span>Emergencies Now</span>
                </button>
                <?php endif; ?>
                <!-- 4. My Reports (Activity) -->
                <?php if ($role_identifier === 'user' || $role_identifier === 'volunteer'): ?>
                <button onclick="switchTab('activity')" id="nav-activity" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="activity" class="w-5 h-5 shrink-0"></i>
                    <span>My Reports</span>
                </button>
                <?php endif; ?>
                <!-- 5. Adopt -->
                <?php if ($role_identifier === 'user' || $role_identifier === 'volunteer'): ?>
                <button onclick="switchTab('adopt')" id="nav-adopt" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="heart-handshake" class="w-5 h-5 shrink-0"></i>
                    <span>Adopt</span>
                </button>
                <?php endif; ?>
                <!-- 6. Become Volunteer -->
                <?php if ($role_identifier === 'user'): ?>
                <button onclick="switchTab('join')" id="nav-join" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="medal" class="w-5 h-5 shrink-0"></i>
                    <span>Become a Hero</span>
                </button>
                <?php endif; ?>
                <!-- 7. Veterinarian Treatment Report -->
                <?php if ($role_identifier === 'veterinarian'): ?>
                <button onclick="switchTab('vet_treatment_report')" id="nav-vet_treatment_report" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="square-activity" class="w-5 h-5 shrink-0"></i>
                    <span>Treatment</span>
                </button>
                <?php endif; ?>
            </nav>

            <!-- Bottom Actions -->
            <div class="p-4 border-t border-gray-100 min-w-[16rem]">
                <button onclick="switchTab('settings')" id="nav-settings" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="settings" class="w-5 h-5 shrink-0"></i>
                    <span>Settings</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
            
            <!-- Navbar -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-10 flex-shrink-0">
                <div class="flex items-center justify-between px-6 py-4">
                    
                    <!-- Mobile Toggle & Search -->
                    <div class="flex items-center gap-4 flex-1">
                        <button onclick="toggleMobileMenu()" class="lg:hidden text-gray-500 hover:text-gray-700">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <button onclick="toggleDesktopSidebar()" class="hidden lg:block text-gray-400 hover:text-gray-600 hover:bg-gray-50 p-1.5 rounded-lg transition-colors" title="Toggle Sidebar">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <h2 id="page-title" class="text-lg font-bold text-gray-800 ml-2">Home</h2>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                            <div class="text-right hidden md:block">
                                <p class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($full_name);?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user_role);?></p>
                            </div>
                            <!-- Added ID for avatar to update it later -->
                            <div id="header-avatar" class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold border-2 border-orange-50 overflow-hidden">
                                <span class="text-sm <?php echo $profile_photo ? 'hidden' : ''; ?>">
                                    <?php echo htmlspecialchars($initials);?>
                                </span>
                                <img id="header-avatar-img" 
                                    src="<?php echo $profile_photo ? '../' . htmlspecialchars($profile_photo) : ''; ?>" 
                                    class="w-full h-full object-cover <?php echo $profile_photo ? '' : 'hidden'; ?>" 
                                    alt="Profile">
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6 relative">
                <div id="page-content">
                    <!-- VIEW: Home (Feed & Impact) -->
                    <div id="view-home" class="animate-fade-in space-y-8">
                        <!-- Hero Section -->
                        <?php if ($role_identifier === 'user' || $role_identifier === 'volunteer'): ?>
                        <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                            <div class="relative z-10 max-w-lg">
                                <h1 class="text-3xl font-bold mb-2">Every Second Counts</h1>
                                <p class="text-orange-50 mb-6">If you see an animal in distress, report it immediately. You are the eyes and ears of the rescue team.</p>
                                <button onclick="switchTab('report')" class="bg-white text-orange-600 px-6 py-3 rounded-xl font-bold shadow-md hover:bg-gray-100 transition-transform hover:scale-105 flex items-center gap-2">
                                    <i data-lucide="siren" class="w-5 h-5 text-red-500"></i>
                                    Report Emergency
                                </button>
                            </div>
                            <i class="fa-solid fa-dog absolute -bottom-4 -right-4 text-9xl text-white opacity-10 rotate-12"></i>
                        </div>
                        <?php endif; ?>

                        <!-- Recent Success Stories -->
                        <div class="mt-8">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    Community Impact
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if ($impact_stories->num_rows > 0): ?>
                                    <?php while($story = $impact_stories->fetch_assoc()): ?>
                                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300 flex flex-col h-full">
                                            
                                            <div class="p-5 flex-1 flex flex-col">
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-orange-50 text-orange-600 capitalize">
                                                        <i class="fa-solid fa-<?php echo ($story['animal_type'] === 'dog' ? 'dog' : ($story['animal_type'] === 'cat' ? 'cat' : ($story['animal_type'] === 'bird' ? 'dove' : 'paw'))); ?>"></i>
                                                        <?php echo htmlspecialchars($story['animal_type']); ?>
                                                    </span>
                                                </div>

                                                <div class="mb-4 flex-1">
                                                    <h4 class="font-bold text-gray-800 text-sm mb-2">
                                                        Case #<?php echo substr($story['emergency_id'], -5); ?>
                                                    </h4>
                                                    <p class="text-gray-600 text-sm leading-relaxed line-clamp-3">
                                                        <?php 
                                                        // PRIORITIZE OUTCOME, FALLBACK TO DESCRIPTION
                                                        $story_text = !empty($story['outcome']) ? $story['outcome'] : $story['description'];
                                                        echo htmlspecialchars($story_text); 
                                                        ?>
                                                    </p>
                                                </div>

                                                <div class="pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                                    <span><i class="fa-regular fa-calendar mr-1"></i> <?php echo date('M d, Y', strtotime($story['updated_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="col-span-full py-12 text-center bg-white rounded-xl border border-dashed border-gray-200">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 text-gray-400 mb-3">
                                            <i class="fa-solid fa-heart-crack"></i>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">No success stories yet</h3>
                                        <p class="text-sm text-gray-500 mt-1">Once animals are treated, their stories will appear here.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ================= MODALS ================= -->

    <!-- Profile Photo Crop Modal (Updated for Cropper.js) -->
    <div id="crop-modal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center shrink-0">
                <h3 class="font-bold text-lg text-gray-800">Crop Photo</h3>
                <button onclick="closeCropModal()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <div class="p-6 flex-1 overflow-hidden flex flex-col items-center justify-center bg-gray-100 relative">
                 <!-- Image Wrapper for Cropper.js -->
                 <div class="w-full h-64 sm:h-80">
                    <img id="crop-image-target" src="" class="block max-w-full" style="display: block; max-width: 100%;">
                 </div>
                 <p class="text-xs text-gray-500 mt-2">Drag to move/resize. Square aspect ratio enforced.</p>
            </div>
            
            <div class="p-4 border-t border-gray-100 flex gap-3 shrink-0">
                <button onclick="closeCropModal()" class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Cancel</button>
                <button onclick="confirmCrop()" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Apply Crop</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Change Name -->
    <div id="name-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800">Change Name</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="modal-input-name" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300">
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button onclick="closeModalById('name-modal')" class="px-4 py-2 text-gray-600 font-medium hover:text-gray-800">Cancel</button>
                <button onclick="saveNameDraft()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-bold hover:bg-orange-600 shadow-sm">Update</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Change Phone -->
    <div id="phone-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800">Change Phone Number</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">+60</span>
                        </div>
                        <input type="tel" id="modal-input-phone" class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300" placeholder="123456789">
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button onclick="closeModalById('phone-modal')" class="px-4 py-2 text-gray-600 font-medium hover:text-gray-800">Cancel</button>
                <button onclick="savePhoneDraft()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-bold hover:bg-orange-600 shadow-sm">Update</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Change Email -->
    <div id="email-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800" id="email-modal-title">Add Email Address</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="modal-input-email" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300" placeholder="you@example.com">
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button onclick="closeModalById('email-modal')" class="px-4 py-2 text-gray-600 font-medium hover:text-gray-800">Cancel</button>
                <button onclick="saveEmailDraft()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-bold hover:bg-orange-600 shadow-sm">Update</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Change Password -->
    <div id="password-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800">Change Password</h3>
                <p class="text-sm text-gray-500 mt-1">Ensure your account is using a long, random password to stay secure.</p>
            </div>
            <div class="p-6 space-y-4">
                <!-- New Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <!-- Default type is password (hidden) with eye-off icon -->
                        <input type="password" id="new-password" class="w-full pl-3 pr-10 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300">
                        <button type="button" onclick="togglePasswordVisibility('new-password', 'eye-icon-1')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye-off" id="eye-icon-1" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm-password" class="w-full pl-3 pr-10 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300">
                        <button type="button" onclick="togglePasswordVisibility('confirm-password', 'eye-icon-2')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i data-lucide="eye-off" id="eye-icon-2" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 bg-gray-50 rounded-b-xl flex justify-end gap-3">
                <button onclick="closeModalById('password-modal')" class="px-4 py-2 text-gray-600 font-medium hover:text-gray-800">Cancel</button>
                <button onclick="savePassword()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-bold hover:bg-orange-600 shadow-sm">Update Password</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Logout Confirmation -->
    <div id="logout-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full">
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="log-out" class="w-6 h-6"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Log Out</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to log out of your account?</p>
                <div class="flex gap-3 justify-center">
                    <button onclick="closeModalById('logout-modal')" class="px-4 py-2 border border-gray-200 rounded-lg text-gray-600 font-medium hover:bg-gray-50">Cancel</button>
                    <button onclick="confirmLogout()" class="px-6 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 shadow-sm">Yes, Log Out</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Success Notification Modal (And Error Modal) -->
    <div id="custom-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[100] p-4" onclick="closeSuccessModal()">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full transform transition-all duration-300" onclick="event.stopPropagation()">
            <div class="text-center">
                <i id="modal-icon" class="fa-solid fa-check-circle text-orange-500 text-5xl mb-4 animate-pulse-slow"></i>
                <h3 id="modal-title" class="text-2xl font-bold text-gray-800 mb-2">Success!</h3>
                <p id="modal-message" class="text-gray-600 mb-6">Action completed successfully.</p>
                <button onclick="closeSuccessModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition shadow-md">
                    Got it
                </button>
            </div>
        </div>
    </div>

    <!-- LEGAL MODAL (Volunteer Application) -->
    <div id="legal-modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden flex items-center justify-center z-[100] p-4 backdrop-blur-sm transition-opacity duration-300 opacity-0">
        <div class="bg-white rounded-none md:rounded-xl shadow-2xl max-w-lg w-full transform scale-95 transition-all duration-300 flex flex-col" onclick="event.stopPropagation()">
            <div class="bg-orange-50 px-8 py-6 border-b border-orange-100 flex items-center justify-between rounded-t-xl">
                <div class="flex items-center gap-3">
                    <div class="bg-white p-2.5 rounded-full border border-orange-200 shadow-sm">
                        <i class="fa-solid fa-shield-dog text-orange-500 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 tracking-tight">Rescuer Application Affidavit</h3>
                        <p class="text-xs text-orange-600 font-medium">PawRescue Official Protocol</p>
                    </div>
                </div>
                <button onclick="closeLegalModal()" class="text-gray-400 hover:text-orange-500 transition">
                    <i class="fa-solid fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-8">
                <p class="text-sm text-gray-600 mb-4 leading-relaxed">
                    You are applying to become a <strong>Volunteer Rescuer</strong>. This role grants you the authority to assist vulnerable animals in emergency situations.
                </p>
                <p class="text-sm text-gray-600 mb-6 leading-relaxed">
                    By proceeding, you explicitly consent to the necessary <strong>verification checks and background screenings</strong> required to ensure the safety and integrity of our rescue operations.
                </p>

                <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-6 rounded-r-lg shadow-sm">
                    <h4 class="text-orange-800 font-bold text-xs uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info"></i> Information Attestation
                    </h4>
                    <p class="text-xs text-gray-700 mb-2 font-medium">
                        PawRescue relies on the honesty of our volunteers. Please ensure all submitted details are current and accurate. Fraudulent submissions may result in:
                    </p>
                    <ul class="list-disc pl-4 space-y-1 text-xs text-gray-600">
                        <li>Denial of the rescuer application.</li>
                        <li>Temporary suspension of account privileges.</li>
                        <li>Removal from the rescuer registry.</li>
                        <li>Potential legal action and treated as attempted endangerment of animals.</li>
                    </ul>
                </div>
                
                <div class="flex items-start gap-3 mt-2 group p-3 rounded-lg hover:bg-orange-50 transition-colors">
                    <div class="flex items-center h-5 mt-0.5">
                        <input id="confirm-checkbox" type="checkbox" onchange="toggleSubmitButton()" class="w-5 h-5 text-orange-500 border-gray-300 rounded focus:ring-orange-500 cursor-pointer accent-orange-500">
                    </div>
                    <div class="ml-1 text-sm">
                        <label for="confirm-checkbox" class="font-bold text-gray-800 cursor-pointer select-none">
                            I certify that all submitted proofs are authentic.
                        </label>
                        <p class="text-xs text-gray-500 mt-1">
                            I confirm the validity of my documents and agree to the Terms of Service.
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-8 py-5 border-t border-gray-200 flex justify-end gap-3 rounded-b-xl">
                <button onclick="closeLegalModal()" class="px-5 py-2.5 bg-white text-gray-600 text-sm font-bold border border-gray-300 rounded-lg hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors">
                    Cancel
                </button>
                <button id="submit-btn" type="button" onclick="submitApplication()" disabled class="px-5 py-2.5 bg-orange-500 text-white text-sm font-bold rounded-lg shadow-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2">
                    <i class="fa-solid fa-file-signature"></i> Confirm & Submit
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // cache the default dashboard so returning to it restores the exact markup/state snapshot
        if (typeof _adminPageCache !== 'undefined' && document.getElementById('page-content')) {
            _adminPageCache.set('dashboard', document.getElementById('page-content').innerHTML);
        }
        // do NOT call initSettings() here because settings markup is not present on initial load.
    });
    </script>

</body>
</html>