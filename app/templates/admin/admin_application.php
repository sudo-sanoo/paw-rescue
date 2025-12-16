<?php
// admin_application.php
require_once __DIR__ . '/../../includes/session_check.php'; // Adjust path as needed
require_once __DIR__ . '/../../includes/db.php';            // Adjust path as needed

// 1. Fetch Stats
// Pending Count
$stats = [
    'pending' => 0,
    'approved_today' => 0,
    'total_volunteers' => 0
];

// Pending Count
$stmt = $conn->query("SELECT COUNT(*) as count FROM rescuer_applications WHERE status = 'pending'");
$stats['pending'] = $stmt->fetch_assoc()['count'];

// Pending for more than 3 days
$stmt = $conn->query("SELECT COUNT(*) as count FROM rescuer_applications WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)");
$stats['overdue_pending'] = $stmt->fetch_assoc()['count'];

// Total Rescuers (Active rescuer applications)
$stmt = $conn->query("SELECT COUNT(*) as count FROM rescuer_applications WHERE status = 'approved'");
$stats['total_volunteers'] = $stmt->fetch_assoc()['count'];

// 2. Fetch All Applications with User Data
// Join with the users table to get contact info. 
$sql = "SELECT 
            ra.*, 
            u.full_name, 
            u.email, 
            u.phone, 
            u.profile_photo 
        FROM rescuer_applications ra 
        JOIN users u ON ra.user_id = u.user_id 
        ORDER BY ra.created_at DESC";

$result = $conn->query($sql);

$pending_apps = [];
$approved_apps = [];
$rejected_apps = [];

$upload_path = "../images/uploads"; 

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Prepare display data
        $row['formatted_date'] = date('M d, Y', strtotime($row['created_at']));
        
        // Image Paths
        $row['photo_url'] = $row['profile_photo'] 
            ? "$upload_path/avatars/{$row['profile_photo']}" 
            : "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']);
            
        $row['mykad_front_url'] = "$upload_path/mykad/{$row['user_id']}/{$row['mykad_front']}";
        $row['mykad_back_url']  = "$upload_path/mykad/{$row['user_id']}/{$row['mykad_back']}";
        $row['license_front_url'] = "$upload_path/driver_license/{$row['user_id']}/{$row['driver_license_front']}";
        $row['license_back_url']  = "$upload_path/driver_license/{$row['user_id']}/{$row['driver_license_back']}";
        $row['signature_url']   = "$upload_path/signatures/{$row['user_id']}/{$row['signature_image']}";

        // Sort into arrays
        if ($row['status'] === 'pending') {
            $pending_apps[] = $row;
        } elseif ($row['status'] === 'approved') {
            $approved_apps[] = $row;
        } else {
            $rejected_apps[] = $row;
        }
    }
}
?>

<!-- VIEW: Admin Applications Interface -->
<div id="view-applications" class="max-w-6xl mx-auto animate-fade-in">
    
    <!-- 1. Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Pending Review</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1" id="stat-pending-count"><?php echo htmlspecialchars($stats['pending']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Overdue Pending Review (3+ Days)</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($stats['overdue_pending']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Rescuers</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo htmlspecialchars($stats['total_volunteers']); ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
        </div>
    </div>

    <!-- 2. Filter Tabs -->
    <div class="bg-white rounded-t-xl border-b border-gray-200 px-6 pt-4">
        <div class="flex space-x-6 overflow-x-auto no-scrollbar">
            <button onclick="filterApplications('pending')" id="tab-pending" class="filter-tab pb-4 text-sm font-medium border-b-2 border-orange-500 text-orange-600 whitespace-nowrap transition-colors">
                Pending Reviews <span class="ml-2 bg-orange-100 text-orange-600 py-0.5 px-2 rounded-full text-xs" id="badge-pending"><?php echo htmlspecialchars($stats['pending']); ?></span>
            </button>
            <button onclick="filterApplications('approved')" id="tab-approved" class="filter-tab pb-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap transition-colors">
                Approved
            </button>
            <button onclick="filterApplications('rejected')" id="tab-rejected" class="filter-tab pb-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap transition-colors">
                Rejected
            </button>
        </div>
    </div>

    <!-- 3. List Container (Scrollable) -->
    <div class="bg-white rounded-b-xl border border-gray-200 shadow-sm h-[65vh] flex flex-col">
        <!-- Applications List Wrapper -->
        <div id="admin-apps-list" class="divide-y divide-gray-100 overflow-y-auto flex-1">
            
            <!-- ================= PENDING LIST ================= -->
            <div id="list-pending" class="divide-y divide-gray-100 animate-fade-in">
                <?php if (empty($pending_apps)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <p>No pending applications at the moment.</p>
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($pending_apps as $app): ?>
                        <div class="group p-4 hover:bg-orange-50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center cursor-pointer border-l-4 border-transparent hover:border-orange-400" 
                            onclick="openAdminModal(this)"
                            data-applicant='<?php echo htmlspecialchars(json_encode($app), ENT_QUOTES, 'UTF-8'); ?>'>
                            
                            <div class="flex items-center gap-4 flex-1">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($app['photo_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($app['full_name']); ?>" 
                                        class="w-12 h-12 rounded-full border border-gray-200 bg-white object-cover">
                                    
                                    <?php if ($app['has_prior_conviction'] == 1): ?>
                                        <div class="absolute -top-1 -right-1 bg-white rounded-full p-0.5 shadow-sm" title="Prior Conviction Declared">
                                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 fill-red-100"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-orange-600 transition-colors">
                                        <?php echo htmlspecialchars($app['full_name']); ?>
                                    </h4>
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($app['application_id']); ?></span> 
                                        • 
                                        <span><?php echo htmlspecialchars($app['formatted_date']); ?></span>
                                    </p>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto flex justify-end">
                                <button class="text-sm font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 px-4 py-2 rounded-lg transition-colors border border-orange-200 shadow-sm">
                                    Review
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>

            <!-- ================= APPROVED LIST ================= -->
            <div id="list-approved" class="divide-y divide-gray-100 hidden animate-fade-in">
                <?php if (empty($approved_apps)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <p>No approved volunteers yet.</p>
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($approved_apps as $app): ?>
                        <div class="group p-4 hover:bg-green-50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center border-l-4 border-transparent hover:border-green-500">
                            
                            <div class="flex items-center gap-4 flex-1">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($app['photo_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($app['full_name']); ?>" 
                                        class="w-12 h-12 rounded-full border border-gray-200 bg-white object-cover">
                                    
                                    <div class="absolute -bottom-1 -right-1 bg-green-100 rounded-full p-0.5 border border-white">
                                        <i data-lucide="check" class="w-3 h-3 text-green-600"></i>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-green-700 transition-colors">
                                        <?php echo htmlspecialchars($app['full_name']); ?>
                                    </h4>
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($app['application_id']); ?></span> 
                                        • 
                                        <span class="text-green-600">
                                            Approved <?php echo date('M d', strtotime($app['updated_at'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto flex justify-end">
                                <span class="text-xs font-bold text-green-700 bg-green-100 px-3 py-1.5 rounded-full border border-green-200">
                                    Approved
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>

            <!-- ================= REJECTED LIST ================= -->
            <div id="list-rejected" class="divide-y divide-gray-100 hidden animate-fade-in">
                <?php if (empty($rejected_apps)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <p>No rejected applications.</p>
                    </div>
                <?php else: ?>

                    <?php foreach ($rejected_apps as $app): ?>
                        <div class="group p-4 hover:bg-red-50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center border-l-4 border-transparent hover:border-red-500">
                            
                            <div class="flex items-center gap-4 flex-1">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($app['photo_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($app['full_name']); ?>" 
                                        class="w-12 h-12 rounded-full border border-gray-200 bg-white grayscale opacity-75 object-cover">
                                    
                                    <div class="absolute -bottom-1 -right-1 bg-red-100 rounded-full p-0.5 border border-white">
                                        <i data-lucide="x" class="w-3 h-3 text-red-600"></i>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 group-hover:text-red-700 transition-colors">
                                        <?php echo htmlspecialchars($app['full_name']); ?>
                                    </h4>
                                    <p class="text-xs text-gray-500 flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($app['application_id']); ?></span> 
                                        • 
                                        <span class="text-red-600">
                                            Rejected <?php echo date('M d', strtotime($app['updated_at'])); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <div class="w-full sm:w-auto flex justify-end">
                                <span class="text-xs font-bold text-red-700 bg-red-100 px-3 py-1.5 rounded-full border border-red-200">
                                    Rejected
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- 4. Review Modal (5 Pages - Orange Theme) -->
    <!-- This modal is hidden by default. JS toggles 'hidden' class. -->
    <div id="admin-review-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    
        <input type="hidden" id="modal-hidden-app-id" name="application_id" value="">

        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="window.closeAdminModal()"></div>
        
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-2xl flex flex-col max-h-[90vh] animate-fade-in">
                
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <div class="bg-white p-1.5 rounded-md border border-gray-200 shadow-sm">
                            <i data-lucide="file-text" class="w-5 h-5 text-orange-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Application Review</h3>
                            <p id="modal-step-indicator" class="text-xs text-gray-500">Step 1 of 5: General Info</p>
                        </div>
                    </div>
                    <button onclick="window.closeAdminModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 min-h-[400px]">
                    
                    <div id="modal-page-1" class="space-y-6">
                        <div class="flex flex-col sm:flex-row gap-6 items-center sm:items-start bg-orange-50 p-6 rounded-xl border border-orange-100">
                            <img id="modal-profile-photo" src="" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md bg-white">
                            <div class="flex-1 text-center sm:text-left">
                                <h2 id="modal-full-name" class="text-xl font-bold text-gray-900">...</h2>
                                <div class="flex flex-wrap justify-center sm:justify-start gap-3 mt-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-600 border border-gray-200 shadow-sm">
                                        <i data-lucide="user" class="w-3 h-3"></i> <span id="modal-user-id">...</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-600 border border-gray-200 shadow-sm">
                                        <i data-lucide="phone" class="w-3 h-3"></i> <span id="modal-phone">...</span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-gray-600 border border-gray-200 shadow-sm">
                                        <i data-lucide="mail" class="w-3 h-3"></i> <span id="modal-email">...</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Application ID</h5>
                                <p id="modal-app-id" class="text-sm font-mono font-medium text-gray-900">...</p>
                            </div>
                            <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Applying For</h5>
                                <p class="text-sm font-bold text-orange-600 flex items-center gap-2">
                                    <i data-lucide="shield" class="w-4 h-4"></i> Rescuer
                                </p>
                            </div>
                        </div>

                        <div>
                            <h5 class="text-sm font-bold text-gray-900 mb-2">Animal Handling Experience</h5>
                            <div id="modal-experience" class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 leading-relaxed min-h-[80px]">
                                ...
                            </div>
                        </div>

                        <div>
                            <h5 class="text-sm font-bold text-gray-900 mb-2">Training / Certification</h5>
                            <div id="modal-training" class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 leading-relaxed min-h-[80px]">
                                ...
                            </div>
                        </div>
                    </div>

                    <div id="modal-page-2" class="hidden space-y-6 text-center">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 inline-block mb-2">
                            <h4 class="text-lg font-bold text-blue-900 flex items-center gap-2 justify-center">
                                <i data-lucide="scan-face" class="w-5 h-5"></i> Identity Verification
                            </h4>
                            <p class="text-sm text-blue-700">Review submitted MyKad images for authenticity.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-3">
                                <h5 class="font-semibold text-gray-700">MyKad (Front)</h5>
                                <div class="aspect-[1.58/1] bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden group hover:bg-gray-200 transition-colors">
                                    <img id="modal-mykad-front" src="" class="absolute inset-0 w-full h-full object-contain">
                                    <div class="text-gray-500 flex flex-col items-center z-10" id="modal-mykad-front-placeholder">
                                        <i data-lucide="credit-card" class="w-10 h-10 mb-2 opacity-50"></i>
                                        <span class="text-sm font-medium">No Image</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <h5 class="font-semibold text-gray-700">MyKad (Back)</h5>
                                <div class="aspect-[1.58/1] bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden group hover:bg-gray-200 transition-colors">
                                    <img id="modal-mykad-back" src="" class="absolute inset-0 w-full h-full object-contain">
                                    <div class="text-gray-500 flex flex-col items-center z-10" id="modal-mykad-back-placeholder">
                                        <i data-lucide="credit-card" class="w-10 h-10 mb-2 opacity-50"></i>
                                        <span class="text-sm font-medium">No Image</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="modal-page-3" class="hidden space-y-6">
                        
                        <div class="p-4 rounded-lg border border-green-100 bg-green-50 flex items-start gap-4">
                            <div class="bg-white p-2 rounded-full shadow-sm text-green-600 mt-1 flex-shrink-0">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-green-900">Background Check Consent</h4>
                                <p class="text-sm text-green-700 mt-1">Applicant has <span class="font-bold underline">CONSENTED</span> to a police background check.</p>
                            </div>
                        </div>

                        <div class="space-y-3 pt-2">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2">Disclosure of Prior Conviction</h4>
                            
                            <div id="modal-conviction-container" class="p-6 bg-green-50/50 border border-green-100 rounded-lg flex items-center gap-4 text-green-800">
                                <div class="bg-green-100 p-2 rounded-full flex-shrink-0" id="modal-conviction-icon-bg">
                                    <i data-lucide="shield-check" class="w-6 h-6 text-green-600" id="modal-conviction-icon"></i>
                                </div>
                                <div>
                                    <span class="font-bold block text-green-900" id="modal-conviction-title">Clean Declaration</span>
                                    <span class="text-sm" id="modal-conviction-desc">Applicant has declared <strong>NO</strong> prior criminal convictions in the last 5 years.</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-lg text-xs text-gray-500 border border-gray-200">
                            <strong>Note to Admin:</strong> Please run the ID number through the external checking system before approval.
                        </div>
                    </div>

                    <div id="modal-page-4" class="hidden space-y-6">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <h4 class="text-lg font-bold text-gray-900">Driver's License & Vehicle</h4>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 uppercase font-bold mb-1">License Class</p>
                                <p id="modal-license-class" class="text-lg font-bold text-gray-900">...</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500 uppercase font-bold mb-1">Vehicle Availability</p>
                                <p id="modal-vehicle" class="text-lg font-bold text-gray-900">...</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                            <div class="space-y-2">
                                <h5 class="font-semibold text-gray-700 text-sm text-center">License (Front)</h5>
                                <div class="aspect-[1.58/1] bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden">
                                    <img id="modal-license-front" src="" class="absolute inset-0 w-full h-full object-contain">
                                    <div class="text-gray-500 flex flex-col items-center z-10" id="modal-license-front-placeholder">
                                        <i data-lucide="car-front" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <h5 class="font-semibold text-gray-700 text-sm text-center">License (Back)</h5>
                                <div class="aspect-[1.58/1] bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden">
                                    <img id="modal-license-back" src="" class="absolute inset-0 w-full h-full object-contain">
                                    <div class="text-gray-500 flex flex-col items-center z-10" id="modal-license-back-placeholder">
                                        <i data-lucide="car-front" class="w-8 h-8 text-gray-400"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="modal-page-5" class="hidden space-y-8 text-center pt-4">
                        
                        <div class="max-w-md mx-auto bg-gray-50 p-6 rounded-xl border border-gray-200">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Applicant Signature Declaration</h5>
                            <div class="h-32 bg-white border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center">
                                <img id="modal-signature-img" src="" class="max-h-full max-w-full object-contain hidden p-2" alt="Digital Signature">
                                <p id="modal-signature-name" class="font-handwriting text-3xl text-gray-700 italic select-none" style="font-family: cursive;">...</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Digitally Signed on <span id="modal-signed-date" class="text-gray-600 font-medium">...</span></p>
                        </div>

                        <div class="border-t border-gray-100 pt-6" id="modal-actions">
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                <button type="button" onclick="submitDecision('rejected')" class="flex-1 sm:flex-none px-8 py-3 bg-white text-red-600 border border-red-200 hover:bg-red-50 hover:border-red-300 font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                                    <i data-lucide="x-circle" class="w-5 h-5"></i> Reject Application
                                </button>
                                <button type="button" onclick="submitDecision('approved')" class="flex-1 sm:flex-none px-8 py-3 bg-green-600 text-white hover:bg-green-700 font-bold rounded-xl transition-all shadow-lg shadow-green-200 flex items-center justify-center gap-2">
                                    <i data-lucide="check-circle" class="w-5 h-5"></i> Approve Application
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl flex justify-between items-center">
                    <button onclick="window.closeAdminModal()" class="text-gray-500 hover:text-gray-800 font-medium px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    
                    <div class="flex gap-3">
                        <button id="btn-prev" onclick="window.changeAppPage(-1)" class="hidden px-5 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                            Back
                        </button>
                        <button id="btn-next" onclick="window.changeAppPage(1)" class="px-5 py-2 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 transition-colors shadow-sm flex items-center gap-2">
                            Next <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>