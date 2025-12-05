<?php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: member_dashboard.php");
    exit;
}

// debugging purpose during development
// echo "you are admin";

// this is a shared layout

// Fetch current user info
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$full_name = $user['full_name'];
$user_role = ($user['role'] == 'admin') ? "System Admin" : $user['role'];
$phone = $user['phone'];
$profile_photo = $user['profile_photo'] ?? ''; // stored as relative path like "images/uploads/avatars/abc.png"

$initials = getInitials($user['full_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Favicons -->
    <link rel="icon" href="../favicon.svg">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/admin_template.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- JS -->
    <script defer src="../js/admin_templates.js"></script>
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
                    <span>Dashboard</span>
                </button>
                <button onclick="switchTab('animals')" id="nav-animals" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="dog" class="w-5 h-5 shrink-0"></i>
                    <span>Animals</span>
                </button>
                <button onclick="switchTab('report')" id="nav-report" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="file-text" class="w-5 h-5 shrink-0"></i>
                    <span>Reports</span>
                </button>
                <button onclick="switchTab('volunteers')" id="nav-volunteers" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="users" class="w-5 h-5 shrink-0"></i>
                    <span>Volunteers</span>
                </button>
                <button onclick="switchTab('schedule')" id="nav-schedule" class="nav-item w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    <i data-lucide="calendar" class="w-5 h-5 shrink-0"></i>
                    <span>Schedule</span>
                </button>
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
                        <div class="relative w-full max-w-md hidden md:block">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
                            <input type="text" placeholder="Search for animals, volunteers..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-100 focus:border-orange-300 text-sm transition-all" />
                        </div>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-4">
                        <button class="relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-full transition-colors">
                            <i data-lucide="bell" class="w-5 h-5"></i>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                        </button>
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
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div id="page-content">
                    <!-- VIEW: Dashboard -->
                    <div id="view-dashboard" class="space-y-8 animate-fade-in">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h1>
                                <p class="text-gray-500">Here's what's happening at PawRescue today.</p>
                            </div>
                            <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-colors flex items-center gap-2">
                                <i data-lucide="heart" class="w-4 h-4"></i>
                                <span>New Intake</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                                <div><p class="text-sm text-gray-500 font-medium">Animals in Care</p><h3 class="text-2xl font-bold text-gray-800 mt-1">42</h3></div>
                                <div class="p-3 rounded-full bg-orange-100 text-orange-600"><i data-lucide="dog" class="w-6 h-6"></i></div>
                            </div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                                <div><p class="text-sm text-gray-500 font-medium">Pending Adoptions</p><h3 class="text-2xl font-bold text-gray-800 mt-1">15</h3></div>
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600"><i data-lucide="file-text" class="w-6 h-6"></i></div>
                            </div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                                <div><p class="text-sm text-gray-500 font-medium">Volunteers Active</p><h3 class="text-2xl font-bold text-gray-800 mt-1">28</h3></div>
                                <div class="p-3 rounded-full bg-green-100 text-green-600"><i data-lucide="users" class="w-6 h-6"></i></div>
                            </div>
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                                <div><p class="text-sm text-gray-500 font-medium">Urgent Cases</p><h3 class="text-2xl font-bold text-gray-800 mt-1">3</h3></div>
                                <div class="p-3 rounded-full bg-red-100 text-red-600"><i data-lucide="clock" class="w-6 h-6"></i></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div class="lg:col-span-2 space-y-8">
                                <!-- Recent Animals Section -->
                                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                    <div class="flex items-center justify-between mb-6">
                                        <h2 class="text-lg font-bold text-gray-800">New Arrivals</h2>
                                        <a href="#" onclick="switchTab('animals')" class="text-orange-600 text-sm font-medium hover:text-orange-700 flex items-center gap-1">
                                            View all <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                    <div class="grid gap-4">
                                        <!-- Animal Item 1 -->
                                        <div class="flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group">
                                            <img src="https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=150&q=80" alt="Barnaby" class="w-16 h-16 rounded-lg object-cover group-hover:scale-105 transition-transform">
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-bold text-gray-800">Barnaby</h4>
                                                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-700">Available</span>
                                                </div>
                                                <p class="text-sm text-gray-500">Golden Retriever • Dog</p>
                                            </div>
                                        </div>
                                        <!-- Animal Item 2 -->
                                        <div class="flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group">
                                            <img src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=150&q=80" alt="Mittens" class="w-16 h-16 rounded-lg object-cover group-hover:scale-105 transition-transform">
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-bold text-gray-800">Mittens</h4>
                                                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-blue-100 text-blue-700">Pending</span>
                                                </div>
                                                <p class="text-sm text-gray-500">Tabby • Cat</p>
                                            </div>
                                        </div>
                                        <!-- Animal Item 3 -->
                                        <div class="flex items-center gap-4 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all cursor-pointer group">
                                            <img src="https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=150&q=80" alt="Rocky" class="w-16 h-16 rounded-lg object-cover group-hover:scale-105 transition-transform">
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-bold text-gray-800">Rocky</h4>
                                                    <span class="text-xs px-2 py-1 rounded-full font-medium bg-orange-100 text-orange-700">Medical Hold</span>
                                                </div>
                                                <p class="text-sm text-gray-500">Bulldog Mix • Dog</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Adoption Applications Table -->
                                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                    <div class="flex items-center justify-between mb-6">
                                        <h2 class="text-lg font-bold text-gray-800">Recent Applications</h2>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left">
                                            <thead>
                                                <tr class="border-b border-gray-100 text-gray-400 text-sm">
                                                    <th class="pb-3 font-medium">Applicant</th>
                                                    <th class="pb-3 font-medium">Animal</th>
                                                    <th class="pb-3 font-medium">Status</th>
                                                    <th class="pb-3 font-medium text-right">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-sm">
                                                <!-- Row 1 -->
                                                <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                                                    <td class="py-4 font-medium text-gray-800">Sarah Jenkins</td>
                                                    <td class="py-4 text-gray-600">Barnaby</td>
                                                    <td class="py-4">
                                                        <span class="px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Under Review</span>
                                                    </td>
                                                    <td class="py-4 text-right text-gray-500">2 hrs ago</td>
                                                </tr>
                                                <!-- Row 2 -->
                                                <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors">
                                                    <td class="py-4 font-medium text-gray-800">Mike Ross</td>
                                                    <td class="py-4 text-gray-600">Bella</td>
                                                    <td class="py-4">
                                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Approved</span>
                                                    </td>
                                                    <td class="py-4 text-right text-gray-500">1 day ago</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-8">
                                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                    <div class="flex items-center justify-between mb-6">
                                        <h2 class="text-lg font-bold text-gray-800">Today's Tasks</h2>
                                        <button class="text-gray-400 hover:text-gray-600"><i data-lucide="calendar" class="w-5 h-5"></i></button>
                                    </div>
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <button class="mt-1 flex-shrink-0 w-5 h-5 rounded border border-gray-300"></button>
                                            <div><p class="text-sm font-medium text-gray-800">Vet Visit: Luna</p><span class="text-xs text-gray-400">10:00 AM</span></div>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <button class="mt-1 flex-shrink-0 w-5 h-5 rounded border border-gray-300"></button>
                                            <div><p class="text-sm font-medium text-gray-800">Foster Home Check</p><span class="text-xs text-gray-400">2:00 PM</span></div>
                                        </div>
                                    </div>
                                    <button class="w-full mt-6 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Add New Task</button>
                                </div>

                                <!-- Quick Volunteer Status -->
                                <div class="bg-orange-500 p-6 rounded-xl text-white shadow-lg relative overflow-hidden group">
                                    <div class="relative z-10">
                                        <h3 class="font-bold text-lg mb-1">Need Help?</h3>
                                        <p class="text-orange-100 text-sm mb-4">You have 5 volunteers available for transport right now.</p>
                                        <button class="bg-white text-orange-600 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-orange-50 transition-colors">
                                            Alert Team
                                        </button>
                                    </div>
                                    <div class="absolute -bottom-4 -right-4 opacity-20 transform rotate-12 group-hover:scale-110 transition-transform duration-500">
                                        <i data-lucide="users" class="w-24 h-24"></i>
                                    </div>
                                </div>
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