<!-- member_activity.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helper_func.php';

requireRole(['user', 'volunteer']);

$current_user_id = $_SESSION['user_id'];

/**
 * 1. FETCH USER STATISTICS
 */
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'treated' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN status NOT IN ('treated', 'cancelled') THEN 1 ELSE 0 END) as active
    FROM emergencies WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

/**
 * 2. FETCH LIVE EMERGENCY (Happening Now)
 */
$live_sql = "SELECT e.*, 
                    e.created_at AS emergency_timestamp,
                    u.full_name as rescuer_name, 
                    u.phone as rescuer_phone 
    FROM emergencies e 
    LEFT JOIN users u ON e.rescuer_transport = u.user_id 
    WHERE e.user_id = ? 
        AND (
            e.status NOT IN ('treated', 'cancelled') 
            OR (e.status = 'treated' AND e.updated_at >= NOW() - INTERVAL 1 HOUR)
        )
    ORDER BY e.created_at DESC LIMIT 1";
$stmt = $conn->prepare($live_sql);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$liveEmergency = $stmt->get_result()->fetch_assoc();

// Prepare Live Modal Parameters for JS
$liveParams = "";
if ($liveEmergency) {
    $js_id = addslashes($liveEmergency['emergency_id']);
    $js_urgency = ucfirst($liveEmergency['urgency'] ?? 'Standard');
    $js_status = $liveEmergency['status'];
    $js_name = addslashes($liveEmergency['rescuer_name'] ?? 'Assigning...');
    $js_phone = addslashes($liveEmergency['rescuer_phone'] ?? '---');
    $js_init = getInitials($liveEmergency['rescuer_name'] ?? 'A');

    $liveParams = "'$js_id', '$js_urgency', '$js_status', '$js_name', '$js_phone', '$js_init'";
}

// 3. FETCH HISTORY into an array for the foreach loop
$history = [];
$hist_sql = "SELECT * FROM emergencies WHERE user_id = ? AND status IN ('treated', 'cancelled') ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($hist_sql);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $history[] = $row; }
?>

<!-- VIEW: My Reports (Activity Tracking) -->
<div id="view-activity" class="animate-fade-in max-w-7xl mx-auto space-y-8">

    <!-- USER STATS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></h4>
                <p class="text-xs text-gray-500 uppercase font-semibold">Total Reports</p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800"><?= $stats['resolved'] ?? 0 ?></h4>
                <p class="text-xs text-gray-500 uppercase font-semibold">Resolved</p>
            </div>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4 border-l-4 border-l-red-500">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center animate-pulse">
                <i class="fas fa-exclamation text-xl"></i>
            </div>
            <div>
                <h4 class="text-2xl font-bold text-gray-800"><?= $stats['active'] ?? 0 ?></h4>
                <p class="text-xs text-red-500 uppercase font-bold">Active Now</p>
            </div>
        </div>
    </div>

    <?php if ($liveEmergency): ?>
    <!-- LIVE EMERGENCY EMPHASIS -->
    <div class="space-y-4">
        <h3 class="text-gray-500 font-bold uppercase tracking-wider text-sm flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
            Happening Now
        </h3>
        
        <div onclick="openLiveEmergencyModal(<?= $liveParams ?>)" class="bg-gradient-to-r from-red-50 to-white rounded-2xl shadow-md border border-red-100 p-6 cursor-pointer hover:shadow-lg transition-all group relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/3 bg-map-pattern opacity-10 pointer-events-none"></div>
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide animate-pulse shadow-sm"><?= strtoupper($liveEmergency['status']) ?></span>
                        <span class="text-xs font-mono text-gray-500">Emergency ID: <?= $liveEmergency['emergency_id'] ?></span>
                    </div>
                    <div class="mb-4">
                        <p class="text-lg font-medium text-gray-900 leading-relaxed border-l-4 border-red-200 pl-4 py-1">
                            "<?= htmlspecialchars($liveEmergency['description']) ?>"
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 text-gray-600 text-sm">
                        <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-4 h-4 text-gray-400"></i> <?= time_elapsed_string($liveEmergency['emergency_timestamp']) ?></span>
                        <span class="flex items-center gap-1.5"><i data-lucide="map-pin" class="w-4 h-4 text-gray-400"></i> <?= htmlspecialchars($liveEmergency['location_address']) ?></span>
                    </div>
                </div>
                <div class="flex items-center justify-center md:justify-end">
                    <button class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-gray-300 group-hover:bg-black transition-colors flex items-center gap-2 whitespace-nowrap">
                        Open Live
                        <i data-lucide="radio" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ENCAPSULATED HISTORY SECTION -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col max-h-[600px]">
        <!-- Section Header -->
        <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5 text-gray-400"></i>
                <h3 class="text-gray-800 font-bold text-base">Recent Report History</h3>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs text-gray-500 font-medium bg-gray-200/50 px-2 py-1 rounded">Total: <?= count($history) ?> Records</span>
            </div>
        </div>

        <!-- Scrollable Card Container -->
        <div class="overflow-y-auto p-4 md:p-6 space-y-4 custom-scrollbar">
            <?php if (empty($history)): ?>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="clipboard-list" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <h4 class="text-sm font-bold text-gray-900">No reports found</h4>
                    <p class="text-xs text-gray-500 mt-1">Your past emergency reports will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($history as $row): 
                    $js_id = $row['emergency_id'];
                    $js_desc = addslashes(htmlspecialchars($row['description']));
                    $js_date = date('M d, Y', strtotime($row['created_at']));
                    $js_loc = addslashes(htmlspecialchars($row['location_address']));
                ?>
                <!-- 1. RESOLVED CARD -->
                <div onclick="openResolvedModal('<?= $js_id ?>', '<?= $js_desc ?>', '<?= $js_date ?>', '<?= $js_loc ?>')" class="relative bg-gradient-to-r from-green-50/50 to-white rounded-xl border border-gray-200 p-5 hover:border-green-200 hover:shadow-sm transition-all cursor-pointer group overflow-hidden">
                    <div class="absolute right-0 top-0 h-full w-1/3 bg-map-pattern opacity-5 pointer-events-none"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-green-100 text-green-700"><?= $row['status'] ?></span>
                            <span class="text-xs font-mono text-gray-400">Emergency ID: <?= $row['emergency_id'] ?></span>
                        </div>
                        <p class="text-sm font-medium text-gray-900 leading-relaxed border-l-4 border-green-300 pl-3 py-1 mb-3">
                            <?= htmlspecialchars($row['description']) ?>
                        </p>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3 h-3"></i> <?= $js_date ?></span>
                            <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i> <?= htmlspecialchars($row['location_address']) ?></span>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="absolute right-5 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-300 group-hover:text-gray-500 transition-colors"></i>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- LIVE TRACKING MODAL -->
<div id="live-modal-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div id="live-modal-content" class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden hidden relative">
        <div class="bg-gray-900 text-white p-5 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-3 h-3 rounded-full bg-red-500 animate-pulse ring-2 ring-red-900/50"></div>
                <div>
                    <h2 class="font-bold text-xl leading-tight">Live</h2>
                    <p id="live-meta-text" class="text-xs text-gray-400">Emergency ID: -- • -- Priority</p>
                </div>
            </div>
            <button onclick="closeLiveEmergencyModal()" class="w-8 h-8 rounded-full bg-gray-800 hover:bg-gray-700 flex items-center justify-center text-gray-300 hover:text-white transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 h-[600px]">
            <div class="col-span-1 bg-white border-r border-gray-100 p-8 flex flex-col overflow-y-auto">
                <div class="flex-1">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-8">Status Timeline</h4>
                    <div class="space-y-8 relative border-l-2 border-gray-100 pl-6 ml-2">
                        <div id="step-submitted" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-2 border-white ring-1 ring-blue-100"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-medium text-gray-600">Report Submitted</p>
                        </div>
                        <div id="step-on_the_way" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-orange-500 border-2 border-white ring-4 ring-orange-100 animate-pulse"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-bold text-gray-900">Rescuer On The Way</p>
                        </div>
                        <div id="step-arrived" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-gray-200 border-2 border-white"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-medium text-gray-400">Arrived at Scene</p>
                        </div>
                        <div id="step-transporting" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-gray-200 border-2 border-white"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-medium text-gray-400">Transporting to Vet</p>
                        </div>
                        <div id="step-treating" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-gray-200 border-2 border-white"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-medium text-gray-400">Treating</p>
                        </div>
                        <div id="step-resolved" class="relative transition-all duration-500">
                            <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-gray-200 border-2 border-white"></div>
                            <p class="status-label text-[10px] font-bold mb-0.5 hidden text-orange-600">Current Status</p>
                            <p class="text-sm font-medium text-gray-400">Outcome</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-8 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Assigned Rescuer</h4>
                    <div class="flex items-center gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div id="live-rescuer-initials" class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold border-2 border-white shadow-sm text-lg">
                            ?
                        </div>
                        <div>
                            <p id="live-rescuer-name" class="text-base font-bold text-gray-900">---</p>
                            <p id="live-rescuer-phone" class="text-sm text-gray-500 font-mono">---</p>
                        </div>
                        <a id="live-rescuer-whatsapp" href="#" target="_blank" class="text-gray-400 hover:text-green-500 transition-colors">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-span-2 relative map-bg">
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                        <div id="emergency-radar" class="w-48 h-48 rounded-full bg-red-500/10 animate-ping absolute -top-20 -left-20 pointer-events-none"></div>
                        <div class="w-10 h-10 bg-red-500 text-white rounded-full flex items-center justify-center shadow-xl border-4 border-white relative z-10 text-lg">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div id="emergency-label" class="bg-white px-3 py-1.5 rounded-lg shadow-md text-xs font-bold whitespace-nowrap absolute top-14 left-1/2 -translate-x-1/2 border border-gray-100">
                            Emergency Location
                        </div>
                    </div>
                    <div class="absolute top-1/3 left-1/4 transition-all duration-[5000ms]" id="rescuer-marker">
                        <div class="bg-blue-600 text-white p-2.5 rounded-xl shadow-xl border-2 border-white flex items-center gap-2 transform -rotate-12">
                            <i class="fas fa-ambulance text-lg"></i>
                            <span class="text-sm font-bold">OTW</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resolved Case Modal (NEW) -->
<div id="resolved-modal-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div id="resolved-modal-content" class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden hidden flex flex-col">
        <!-- Header -->
        <div class="bg-green-600 text-white p-5 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="font-bold text-xl">Case Resolution Summary</h2>
                    <p class="text-xs text-green-100 opacity-80" id="res-modal-id">ID: #---</p>
                </div>
            </div>
            <button onclick="closeResolvedModal()" class="text-white/80 hover:text-white transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        
        <!-- Main Content Area (Grid removed/simplified to fill width) -->
        <div class="p-8 space-y-8 overflow-y-auto max-h-[70vh]">
            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Original Report</h4>
                <p class="text-sm text-gray-700 leading-relaxed italic bg-gray-50 p-4 rounded-xl border border-gray-100" id="res-modal-report">
                    Loading report text...
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Rescue Timeline</h4>
                    <div class="space-y-4 border-l-2 border-green-100 pl-4 ml-2">
                        <div class="text-xs"><span class="font-bold text-gray-900 block">Reported</span> <span id="res-modal-date" class="text-gray-500">---</span></div>
                        <div class="text-xs"><span class="font-bold text-gray-900 block">Rescued</span> <span class="text-gray-500">+18 minutes</span></div>
                        <div class="text-xs"><span class="font-bold text-green-600 block">Final Resolution</span> <span class="text-gray-500">Success</span></div>
                    </div>
                </div>

                <div class="p-4 bg-green-50 rounded-xl border border-green-100 self-start">
                    <h4 class="text-xs font-bold text-green-700 uppercase tracking-widest mb-2">Outcome Details</h4>
                    <p class="text-sm font-medium text-green-800">The animal was successfully treated at Bangsar Veterinary Clinic and is currently in foster care.</p>
                </div>
            </div>
        </div>
    </div>
</div>