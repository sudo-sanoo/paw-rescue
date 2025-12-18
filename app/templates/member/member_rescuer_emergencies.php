<!-- member_rescuer_emergencies.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helper_func.php';

requireRole(['volunteer', 'veterinarian']);

$user_id = $_SESSION['user_id'];
$upload_path = "../../images/uploads";

// 1. CHECK FOR ACTIVE MISSION  
$activeSql = "SELECT 
                e.*, 
                u.full_name AS reporter_name, 
                u.phone AS reporter_phone, 
                u.profile_photo AS reporter_photo
              FROM emergencies e
              JOIN users u ON e.user_id = u.user_id
              WHERE e.rescuer_transport = ? 
              AND e.status IN ('otw', 'transporting', 'treating') 
              LIMIT 1";

$activeStmt = $conn->prepare($activeSql);
$activeStmt->bind_param("s", $user_id);
$activeStmt->execute();
$activeMission = $activeStmt->get_result()->fetch_assoc();

// 2. FETCH LIST ONLY IF NO ACTIVE MISSION
$emergencies = [];
if (!$activeMission) {
    $sql = "SELECT 
                e.*,
                e.created_at AS emergency_timestamp,
                u.full_name,
                u.phone, 
                u.profile_photo 
            FROM emergencies e 
            JOIN users u ON e.user_id = u.user_id 
            WHERE e.status = 'pending' 
            ORDER BY 
                FIELD(e.urgency, 'critical', 'serious', 'minor') ASC, 
                e.created_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $emergencies = $result->fetch_all(MYSQLI_ASSOC);
}

// 3. Calculate Counts
$stats = [
    'all' => count($emergencies),
    'critical' => 0,
    'serious' => 0,
    'minor' => 0
];

foreach ($emergencies as $e) {
    if (isset($stats[$e['urgency']])) {
        $stats[$e['urgency']]++;
    }
}

// 4. Styling Maps
$urgencyStyles = [
    'critical' => [
        'bg' => 'bg-red-600', 
        'badge_bg' => 'bg-red-100', 
        'badge_text' => 'text-red-700', 
        'badge_border' => 'border-red-200',
        'icon' => 'siren'
    ],
    'serious' => [
        'bg' => 'bg-orange-500', 
        'badge_bg' => 'bg-orange-100', 
        'badge_text' => 'text-orange-700', 
        'badge_border' => 'border-orange-200',
        'icon' => 'alert-triangle'
    ],
    'minor' => [
        'bg' => 'bg-blue-500', 
        'badge_bg' => 'bg-blue-100', 
        'badge_text' => 'text-blue-700', 
        'badge_border' => 'border-blue-200',
        'icon' => 'info'
    ]
];

// 1. Get Data & Parse JSON
$aiStatus = $activeMission['ai_status']; 
$aiScore = $activeMission['ai_severity_score'];

// Attempt to decode the JSON we stored in the DB
$aiData = json_decode($activeMission['ai_insight'], true);
// Check if it's valid structured data
$isStructured = json_last_error() === JSON_ERROR_NONE && is_array($aiData);

// 2. Dynamic Colors based on Score
$scoreColor = 'text-green-600 bg-green-50 border-green-200';
$progressColor = 'bg-green-500';

if($aiScore > 50) { 
    $scoreColor = 'text-orange-600 bg-orange-50 border-orange-200'; 
    $progressColor = 'bg-orange-500'; 
}
if($aiScore > 80) { 
    $scoreColor = 'text-red-600 bg-red-50 border-red-200'; 
    $progressColor = 'bg-red-500'; 
}

?>

<!-- VIEW: Rescuer Emergencies -->
<div id="view-rescuer_emergency" class="animate-fade-in max-w-7xl mx-auto space-y-6">
    
    <!-- ACTIVE MISSION PAGE -->
    <?php if ($activeMission):

        $reporterAvatar = $activeMission['reporter_photo'] 
            ? "$upload_path/avatars/{$activeMission['reporter_photo']}" 
            : "https://ui-avatars.com/api/?name=" . urlencode($activeMission['reporter_name']);

        $status = $activeMission['status'];
        
        // Default State (OTW)
        $ui = [
            'progress' => '0%',
            'status_text' => 'En Route',
            'label' => 'Picking up at',
            'location' => htmlspecialchars($activeMission['location_address']),
            'btn_text' => 'Transport to Vet',
            'btn_color' => 'bg-gray-900',
            'icon' => 'fa-location-dot'
        ];

        if ($status === 'transporting') {
            $ui = [
                'progress' => '50%',
                'status_text' => 'Transporting',
                'label' => 'Dropping off at',
                'location' => 'Partner Vet Clinic', 
                'btn_text' => 'Arrived at Vet',
                'btn_color' => 'bg-orange-600',
                'icon' => 'fa-truck-medical'
            ];
        } elseif ($status === 'treating') {
            $ui = [
                'progress' => '100%',
                'status_text' => 'Vet Handoff',
                'label' => 'Mission Status',
                'location' => 'Handover Completed',
                'btn_text' => 'Submit Report',
                'btn_color' => 'bg-green-600',
                'icon' => 'fa-check'
            ];
        }
    ?>
    
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- LEFT COLUMN: MAP (Takes 2 columns) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Map Card -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden h-[500px] lg:h-[600px] relative group">
                    <!-- Floating Map Label (Top Left) -->
                    <div class="absolute top-4 left-4 z-10 bg-white/90 backdrop-blur px-3 py-1.5 rounded-lg shadow-sm border border-gray-200">
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-xs font-bold text-gray-700">Live GPS</span>
                        </div>
                    </div>

                    <div class="absolute top-4 right-4 z-10 w-full max-w-[60%] lg:max-w-sm bg-white/95 backdrop-blur rounded-2xl p-4 shadow-lg border border-gray-200/50">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Reporter</h3>
                        </div>
                        <div class="flex items-center gap-3">
                            <img src="<?= $reporterAvatar ?>"
                                    class="w-10 h-10 rounded-full object-cover bg-orange-100 border-2 border-white shadow-sm">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($activeMission['reporter_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($activeMission['reporter_phone']) ?></p>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <a href="https://wa.me/<?= str_replace(['+', '-', ' '], '', $activeMission['reporter_phone']) ?>" target="_blank" class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center hover:bg-green-100 transition-colors">
                                    <i class="fa-brands fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div id="live-tracking-map" class="w-full h-full bg-gray-100 relative" 
                        data-dest-lat="<?= htmlspecialchars($activeMission['latitude']) ?>" 
                        data-dest-lng="<?= htmlspecialchars($activeMission['longitude']) ?>"
                        data-emergency-id="<?= htmlspecialchars($activeMission['emergency_id']) ?>">
                        
                        <div class="absolute inset-0 flex items-center justify-center text-gray-500">
                            <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Loading Map...
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: DETAILS & AI (Takes 1 column) -->
            <div class="space-y-6">
                
                <!-- Emergency Details Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                    <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Current Status</h2>
                    
                    <!-- Destination -->
                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center shrink-0 text-orange-500 shadow-sm">
                            <i class="fa-solid fa-location-dot text-xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span id="destination-label" class="text-xs font-bold text-gray-500 uppercase">Picking up at</span>
                            </div>
                            <h3 id="destination-text" class="text-sm text-gray-900 mt-1 font-bold"><?= $ui['location'] ?></h3>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative mb-6">
                        <div class="flex justify-between mb-2 text-xs font-medium text-gray-500">
                            <span>Mission Progress</span>
                            <span id="status-text"><?= $ui['status_text'] ?></span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div id="progress-line" class="h-full bg-orange-500 transition-all duration-500" style="width: <?= $ui['progress'] ?>"></div>
                        </div>
                    </div>

                    <!-- Main Action Button -->
                    <button id="main-action-btn" 
                            onclick="advanceMissionStatus()"
                            data-emergency-id="<?= $activeMission['emergency_id'] ?>"
                            data-current-status="<?= $status ?>"
                            class="group w-full <?= $ui['btn_color'] ?> hover:bg-black text-white rounded-xl py-4 shadow-lg shadow-gray-200 flex items-center justify-center gap-3 transition-all transform active:scale-[0.98]">
                        <span id="action-text" class="font-bold"><?= $ui['btn_text'] ?></span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>

                <!-- AI Insights Section (Inline) -->
                <div class="bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-5 shadow-sm border border-indigo-100 relative overflow-hidden">
                    <!-- Decorative blur -->
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-indigo-100 rounded-full blur-2xl opacity-60"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="bg-indigo-600 text-white p-1.5 rounded-lg shadow-sm shadow-indigo-200">
                                <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 text-sm">AI Analysis</h3>
                        </div>

                        <!-- Initial State -->
                        <div id="ai-initial" class="<?= $aiStatus === 'pending' ? '' : 'hidden' ?> text-center py-4">
                            <p class="text-xs text-gray-500 mb-3">Scan uploaded images for injuries and behavioral traits.</p>
                            <button onclick="generateAIInsights()" class="w-full bg-white border border-indigo-200 text-indigo-600 text-sm font-bold py-2 rounded-xl hover:bg-indigo-50 transition-colors shadow-sm">
                                Run AI Scan
                            </button>
                        </div>

                        <!-- Loading State -->
                        <div id="ai-content-loading" class="hidden py-6 text-center">
                            <i class="fa-solid fa-circle-notch fa-spin text-2xl text-indigo-500 mb-2"></i>
                            <p class="text-xs font-medium text-indigo-600">Analyzing injuries...</p>
                        </div>

                        <!-- Results State -->
                        <div id="ai-content-results" class="<?= $aiStatus === 'completed' ? '' : 'hidden' ?> space-y-3 animate-fade-in">
                            <!-- Severity -->
                            <div class="flex items-center justify-between bg-white/60 p-2 rounded-lg border border-indigo-50">
                                <span class="text-xs font-bold text-gray-600">Severity</span>
                                <span id="ai-score-text"
                                        class="<?= $scoreColor ?> px-2 py-0.5 rounded text-[10px] font-bold">
                                    <?= $aiScore !== null ? $aiScore . '/100' : '' ?>
                                </span>
                            </div>
                            
                            <div id="ai-structured-data">
                                <?php if ($aiStatus === 'completed' && $isStructured): ?>
                                    
                                    <?php if (!empty($aiData['risks'])): ?>
                                    <div class="mb-3">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">Potential Risks</label>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach($aiData['risks'] as $risk): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-red-50 text-red-700 text-xs font-bold border border-red-100">
                                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> <?= htmlspecialchars($risk) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($aiData['equipment'])): ?>
                                    <div class="mb-3">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 block">Recommended Gear</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php foreach($aiData['equipment'] as $item): ?>
                                                <div class="flex items-center gap-2 bg-white px-2 py-1.5 rounded-lg border border-gray-100 shadow-sm text-xs text-gray-700 font-medium">
                                                    <i class="fa-solid fa-check text-green-500"></i> <?= htmlspecialchars($item) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($aiData['handling'])): ?>
                                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                                        <div class="flex items-start gap-2">
                                            <i class="fa-solid fa-hands-holding-circle text-blue-500 mt-0.5"></i>
                                            <div>
                                                <p class="text-[10px] font-bold text-blue-400 uppercase mb-0.5">Handling Advice</p>
                                                <p class="text-xs text-blue-900 leading-relaxed font-medium">
                                                    "<?= htmlspecialchars($aiData['handling']) ?>"
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <?php elseif ($aiStatus === 'completed'): ?>
                                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs text-gray-600 italic">
                                        <?= nl2br(htmlspecialchars($activeMission['ai_insight'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End Right Column -->

        </div> 

    <?php else: ?>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
            <div class="flex flex-wrap gap-2" id="emergency-filters">
                <button data-filter="all" class="filter-btn active px-4 py-1.5 bg-gray-800 text-white text-sm rounded-full font-medium shadow-sm transition hover:bg-gray-700">
                    All (<?= $stats['all'] ?>)
                </button>
                <button data-filter="critical" class="filter-btn px-4 py-1.5 bg-red-300 text-red-700 text-sm rounded-full font-medium border border-red-200 transition hover:bg-red-200">
                    Critical (<?= $stats['critical'] ?>)
                </button>
                <button data-filter="serious" class="filter-btn px-4 py-1.5 bg-orange-300 text-orange-700 text-sm rounded-full font-medium border border-orange-200 transition hover:bg-orange-200">
                    Serious (<?= $stats['serious'] ?>)
                </button>
                <button data-filter="minor" class="filter-btn px-4 py-1.5 bg-blue-300 text-blue-700 text-sm rounded-full font-medium border border-blue-200 transition hover:bg-blue-200">
                    Minor (<?= $stats['minor'] ?>)
                </button>
            </div>
        </div>

        <div id="emergencies-list-container" class="space-y-4">

            <?php if (empty($emergencies)): ?>
                <div class="text-center py-12 text-gray-500 bg-white rounded-xl border border-gray-200">
                    <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-3 text-green-500"></i>
                    <p>No pending emergencies at the moment.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($emergencies as $row): 
                    $style = $urgencyStyles[$row['urgency']] ?? $urgencyStyles['minor'];
                    $timeAgo = time_elapsed_string($row['emergency_timestamp']);

                    $row['photo_url'] = $row['profile_photo'] 
                        ? "$upload_path/avatars/{$row['profile_photo']}"
                        : "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']);

                    $evidenceBase = "$upload_path/emergencies/" . $row['emergency_id'] . "/";
                    
                    $photos = [];
                    if ($row['photo_evidence_1']) $photos[] = $evidenceBase . basename($row['photo_evidence_1']);
                    if ($row['photo_evidence_2']) $photos[] = $evidenceBase . basename($row['photo_evidence_2']);
                    if ($row['photo_evidence_3']) $photos[] = $evidenceBase . basename($row['photo_evidence_3']);
                    
                    $photosJson = htmlspecialchars(json_encode($photos), ENT_QUOTES, 'UTF-8');
                ?>

                <div 
                    onclick="openDynamicModal('modal-<?= $row['emergency_id'] ?>', <?= $photosJson ?>, <?= $row['latitude'] ?? 0 ?>, <?= $row['longitude'] ?? 0 ?>)" 
                    class="emergency-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative transition hover:shadow-md cursor-pointer group"
                    data-urgency="<?= strtolower($row['urgency']) ?>"
                    data-timestamp="<?= strtotime($row['created_at']) ?>">
                    <div class="absolute left-0 top-0 bottom-0 w-2 <?= $style['bg'] ?>"></div>
                    
                    <div class="p-5 pl-7">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono text-gray-400">#<?= htmlspecialchars($row['emergency_id']) ?></span>
                                    <span class="pulse-red inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold <?= $style['bg'] ?> text-white">
                                        <i data-lucide="<?= $style['icon'] ?>" class="w-3 h-3"></i> <?= strtoupper($row['urgency']) ?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                    <?= htmlspecialchars($row['full_name']) ?>
                                    <span class="text-sm font-normal text-gray-500 flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded-md">
                                        <i class="fa-solid fa-phone text-xs"></i> <?= htmlspecialchars($row['phone']) ?>
                                    </span>
                                </h3>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-2xl font-bold <?= str_replace('bg-', 'text-', $style['bg']) ?>"><?= strtoupper($timeAgo) ?></div>
                                <div class="text-xs text-gray-500 uppercase tracking-wide">Ago</div>
                            </div>
                        </div>

                        <p class="mt-3 text-gray-700 leading-relaxed">
                            "<?= htmlspecialchars($row['description']) ?>"
                        </p>

                        <div class="mt-4 flex gap-3">

                            <?php foreach ($photos as $photo): ?>
                                <div class="relative rounded-lg bg-gray-200 overflow-hidden cursor-pointer group w-24 h-24 shrink-0">
                                    <img src="<?= htmlspecialchars($photo) ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($photos)): ?>
                                <div class="h-24 flex items-center text-gray-400 text-xs italic">No photos provided</div>
                            <?php endif; ?>

                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-600">
                                <i data-lucide="map-pin" class="w-4 h-4 text-red-500"></i>
                                <span class="truncate max-w-md"><?= htmlspecialchars($row['location_address']) ?></span>
                            </div>
                            <div class="flex items-center gap-1 text-blue-600 font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                <span>View Details</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($emergencies as $row): 
    $style = $urgencyStyles[$row['urgency']] ?? $urgencyStyles['minor'];
    
    $row['photo_url'] = $row['profile_photo'] 
        ? "$upload_path/avatars/{$row['profile_photo']}"
        : "https://ui-avatars.com/api/?name=" . urlencode($row['full_name']);

    $evidenceBase = "$upload_path/emergencies/" . $row['emergency_id'] . "/";
    
    $photos = [];
    if ($row['photo_evidence_1']) $photos[] = $evidenceBase . basename($row['photo_evidence_1']);
    if ($row['photo_evidence_2']) $photos[] = $evidenceBase . basename($row['photo_evidence_2']);
    if ($row['photo_evidence_3']) $photos[] = $evidenceBase . basename($row['photo_evidence_3']);
    
    $photosJson = htmlspecialchars(json_encode($photos), ENT_QUOTES, 'UTF-8');
?>

<!-- EMERGENCY DETAIL MODAL -->
<div id="modal-<?= $row['emergency_id'] ?>" class="fixed inset-0 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <!-- Backdrop -->
    <div onclick="closeEmergencyModal('modal-<?= $row['emergency_id'] ?>')" class="absolute inset-0 bg-black bg-opacity-70 backdrop-blur-sm"></div>
    
    <!-- Modal Content Panel (Width increased to max-w-7xl) -->
    <div class="relative w-full max-w-7xl h-[95vh] md:h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden transform scale-95 transition-transform duration-300 mx-4" id="modal-panel-<?= $row['emergency_id'] ?>">
        
        <!-- Modal Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10 shrink-0">
            <div class="flex items-center gap-4">
                <span class="<?= $style['bg'] ?> text-white px-3 py-1 rounded-full text-sm font-bold shadow-sm animate-pulse">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= strtoupper($row['urgency']) ?>
                </span>
                <span class="text-gray-400 font-mono text-lg">#<?= $row['emergency_id'] ?></span>
            </div>
            <button onclick="closeEmergencyModal('modal-<?= $row['emergency_id'] ?>')" class="p-2 hover:bg-gray-100 rounded-full text-gray-500 hover:text-gray-800 transition">
                <i data-lucide="x" class="w-8 h-8"></i>
            </button>
        </div>

        <!-- Modal Body (Split Layout) -->
        <div class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-50">
            
            <div class="flex flex-col lg:flex-row gap-6 h-full">
                
                <!-- LEFT COLUMN: MAP ONLY -->
                <div class="lg:w-6/12 flex flex-col gap-4">
                    
                    <!-- Map Card (Full Height) -->
                    <div class="flex-1 bg-gray-200 rounded-3xl relative overflow-hidden group min-h-[400px] border-4 border-white shadow-lg shadow-blue-100/50">
                        <!-- GOOGLE MAP -->
                        <div id="google-map" class="absolute inset-0 w-full h-full bg-cover bg-center">
                            <div id="map-view-<?= $row['emergency_id'] ?>" class="w-full h-full bg-gray-300 opacity-50 flex items-center justify-center">
                            </div>
                        </div>

                        <!-- Center Pin Overlay -->
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center pointer-events-none z-10 pb-9">
                            <div class="w-4 h-4 bg-orange-500 rounded-full animate-ping absolute top-[28px]"></div>
                            <i class="fa-solid fa-location-dot text-6xl text-orange-600 drop-shadow-xl z-10 relative"></i>
                            <div class="w-3 h-3 bg-black opacity-20 rounded-full absolute bottom-[5px] blur-[2px]"></div>
                        </div>

                        <!-- Location Address Overlay (Read Only) -->
                        <div class="absolute top-4 left-4 right-4 z-20 pointer-events-none">
                            <div class="bg-white/95 backdrop-blur-md p-3 rounded-2xl shadow-lg flex items-center gap-3 border border-white/20 pointer-events-auto max-w-full">
                                <div class="p-2 bg-orange-100 text-orange-600 rounded-xl">
                                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                                </div>
                                <div class="pr-2 min-w-0 flex-1">
                                    <p class="text-xs text-gray-500 font-bold uppercase">Incident Location</p>
                                    <p class="text-sm font-bold text-gray-800 truncate w-full"><?= htmlspecialchars($row['location_address']) ?></p>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($row['location_address']) ?>" target="_blank" class="ml-2 bg-blue-50 text-blue-600 p-2 rounded-xl hover:bg-blue-100 transition-colors" title="Open in Maps">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Details + Photos + AI -->
                <div class="lg:w-6/12 flex flex-col gap-4">
                    <div class="bg-white rounded-3xl p-6 lg:p-8 shadow-xl border border-white flex flex-col h-full overflow-y-auto">
                        
                        <!-- Reporter Section -->
                        <div class="flex items-center gap-4 mb-4 pb-4 border-b border-gray-100">
                            <?php if ($row['profile_photo']): ?>
                                <img src="<?= htmlspecialchars($row['profile_photo']) ?>" class="w-14 h-14 rounded-full object-cover shadow-lg">
                            <?php else: ?>
                                <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 text-xl font-bold border-4 border-white shadow-sm overflow-hidden relative">
                                    <?= strtoupper(getInitials($row['full_name'])) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Reported By</p>
                                <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($row['full_name']) ?></h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-bold flex items-center gap-1">
                                        <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($row['phone']) ?>
                                    </span>
                                    <a href="https://wa.me/<?= str_replace(['-',' '], '', $row['phone']) ?>" target="_blank" class="text-gray-400 hover:text-green-500 transition-colors">
                                        <i class="fa-brands fa-whatsapp text-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            
                            <!-- Multi-Photo Evidence Area -->
                            <div>
                                <div class="flex items-center justify-between mb-3 px-1">
                                    <label class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                        <i data-lucide="camera" class="w-4 h-4"></i> Evidence Photos
                                    </label>
                                    <span class="text-xs font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded-md"><?= count($photos) ?> Files</span>
                                </div>
                                
                                <div class="grid grid-cols-4 gap-3 h-28">
                                    <?php foreach ($photos as $index => $photoUrl): ?>
                                    <div onclick="updateLightboxAndOpen(<?= $index ?>, <?= $photosJson ?>)" class="relative rounded-xl overflow-hidden cursor-pointer group h-full bg-gray-200">
                                        <img src="<?= htmlspecialchars($photoUrl) ?>" class="w-full h-full object-cover transition transform group-hover:scale-110">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                            <i data-lucide="maximize-2" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">Situation Description</label>
                                <div class="bg-gray-50 rounded-2xl p-4 text-gray-700 leading-relaxed text-sm border border-gray-100 italic">
                                    "<?= nl2br(htmlspecialchars($row['description'])) ?>"
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 border-t border-gray-200 bg-white flex justify-end gap-3 shrink-0">
            <button onclick="closeEmergencyModal('modal-<?= $row['emergency_id'] ?>')" class="py-3 px-6 rounded-xl text-gray-500 font-bold hover:bg-gray-100 transition">
                Close
            </button>
            
            <form onsubmit="handleAcceptMission(event, '<?= $row['emergency_id'] ?>')">
                <button type="submit" class="py-3 px-8 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 shadow-lg shadow-red-200 flex items-center gap-2 transform active:scale-95 transition-all">
                    <i data-lucide="ambulance" class="w-5 h-5"></i>
                    RESPOND TO EMERGENCY
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- LIGHTBOX MODAL (For Photos) -->
<div id="lightbox-modal" class="fixed inset-0 z-[60] bg-black/95 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
    <!-- Close Button -->
    <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors z-[70]">
        <i data-lucide="x" class="w-10 h-10"></i>
    </button>

    <!-- Nav Left -->
    <button onclick="changePhoto(-1)" class="absolute left-4 lg:left-10 text-white/50 hover:text-white p-4 transition-colors z-[70] hover:bg-white/10 rounded-full">
        <i data-lucide="chevron-left" class="w-12 h-12"></i>
    </button>

    <!-- Image Container -->
    <div class="relative max-w-[90vw] max-h-[85vh]">
        <img id="lightbox-img" src="" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
        <div class="absolute bottom-4 left-0 right-0 text-center pointer-events-none">
            <span id="lightbox-counter" class="bg-black/50 text-white px-3 py-1 rounded-full text-sm font-medium backdrop-blur-sm">1 / 3</span>
        </div>
    </div>

    <!-- Nav Right -->
    <button onclick="changePhoto(1)" class="absolute right-4 lg:right-10 text-white/50 hover:text-white p-4 transition-colors z-[70] hover:bg-white/10 rounded-full">
        <i data-lucide="chevron-right" class="w-12 h-12"></i>
    </button>
</div>