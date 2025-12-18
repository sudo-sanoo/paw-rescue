<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helper_func.php';

requireRole(['veterinarian']);

$user_id = $_SESSION['user_id'];
$upload_path = "../../images/uploads";

$sql = "SELECT e.*, 
               u.full_name as rescuer_name,
               u.phone as rescuer_phone
        FROM emergencies e
        LEFT JOIN users u ON e.rescuer_transport = u.user_id
        WHERE e.vet_treatment = ? 
        AND e.status = 'treating'
        ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cases = $result->fetch_all(MYSQLI_ASSOC);

?>
<!-- VIEW: Veterinarian Treatment & Treatment Report -->
<div id="view-vet_treatment_report" class="hidden animate-fade-in max-w-6xl mx-auto">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Incoming Cases</h3>
            <p class="text-gray-500 text-sm">Animals requiring immediate medical attention.</p>
        </div>
        <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">
            <?= count($cases) ?> Active Cases
        </span>
    </div>

    <?php if (empty($cases)): ?>
        <div class="text-center py-20 bg-white rounded-xl border border-dashed border-gray-300">
            <i data-lucide="stethoscope" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
            <p class="text-gray-500 font-medium">No active cases assigned to you.</p>
        </div>
    <?php else: ?>
    
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <?php foreach ($cases as $case): 
                // --- FIX: IMAGE PATH LOGIC ---
                // Pattern: ../../images/uploads/emergencies/{EMERGENCY_ID}/{FILENAME}
                
                $img1 = $case['photo_evidence_1'] ? $upload_path . '/' . $case['photo_evidence_1'] : '';
                $img2 = $case['photo_evidence_2'] ? $upload_path . '/' . $case['photo_evidence_2'] : '';
                $img3 = $case['photo_evidence_3'] ? $upload_path . '/' . $case['photo_evidence_3'] : '';

                // Urgency Badge Colors
                $badgeColor = match($case['urgency']) {
                    'critical' => 'bg-red-100 text-red-700 border-red-200',
                    'serious'  => 'bg-orange-100 text-orange-700 border-orange-200',
                    'minor'    => 'bg-blue-100 text-blue-700 border-blue-200',
                    default    => 'bg-gray-100 text-gray-700'
                };
            ?>

            <div onclick="openTreatmentModal(this)" 
                    class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer group"
                    data-id="<?= $case['emergency_id'] ?>"
                    data-location="<?= ucfirst($case['animal_type']) ?>"
                    data-condition="<?= htmlspecialchars($case['description']) ?>"
                    data-severity="<?= ucfirst($case['urgency']) ?>"
                    data-rescuer="<?= htmlspecialchars($case['rescuer_name'] ?? 'Unknown') ?>"
                    data-img1="<?= $img1 ?>"
                    data-img2="<?= $img2 ?>"
                    data-img3="<?= $img3 ?>">

                <div class="h-48 overflow-hidden relative">
                    <div class="absolute top-3 right-3 <?= $badgeColor ?> text-xs font-bold px-2 py-1 rounded shadow-sm z-10 animate-pulse"><?= strtoupper($case['urgency']) ?></div>
                    <img src="<?= $img1 ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                    <div class="absolute bottom-3 left-3 text-white">
                        <p class="text-xs opacity-90"><i class="far fa-clock mr-1"></i> Arrived <?= time_elapsed_string($case['updated_at']) // Using updated_at as arrival time ?></p>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="bg-red-50 text-red-600 p-2 rounded-lg shrink-0">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Reported Condition</p>
                            <p class="text-gray-800 text-sm line-clamp-2"><?= htmlspecialchars($case['description']) ?></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded">#<?= $case['emergency_id'] ?></span>
                        <span class="text-sm font-semibold text-orange-600 group-hover:underline">Start Treatment &rarr;</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Treatment Modal Overlay -->
<div id="treatment-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeTreatmentModal()"></div>

    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6">
        
        <div class="relative w-full max-w-3xl bg-white rounded-xl shadow-xl flex flex-col max-h-[90vh] modal-enter overflow-hidden">
            
            <div class="flex-none bg-orange-600 px-4 py-4 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-semibold leading-6 text-white flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                    Treatment Report
                </h3>
                <button onclick="closeTreatmentModal()" class="text-orange-100 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form onsubmit="submitTreatmentReport(event)" class="flex flex-col flex-1 overflow-hidden">
                
                <input type="hidden" id="modal-emergency-id" name="emergency_id">

                <div class="flex-1 overflow-y-auto px-4 py-5 sm:p-6">
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200 flex flex-col sm:flex-row gap-4 items-start">
                        <div class="flex-1 w-full">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p id="modal-id" class="text-xs text-gray-500 font-mono">CASE-ID: #---</p>
                                </div>
                                <span id="modal-severity" class="px-2 py-1 text-xs font-bold rounded bg-gray-100 text-gray-700">---</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold block mb-1">Reporter's Note:</span> 
                                <p id="modal-condition" class="line-clamp-4 break-words leading-relaxed italic">Loading...</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label for="modal-diagnosis" class="block text-sm font-medium text-gray-700">Official Diagnosis</label>
                                <textarea id="modal-diagnosis" name="diagnosis_text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-3 border bg-white" placeholder="e.g. Tibial fracture, severe dehydration..." required></textarea>
                            </div>

                            <div>
                                <label for="modal-treatment" class="block text-sm font-medium text-gray-700">Treatment Administered</label>
                                <textarea id="modal-treatment" name="treatment_text" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-3 border bg-white" placeholder="e.g. Administered 5mg painkiller, splinted leg..." required></textarea>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                                    <input type="number" step="0.1" id="modal-weight" name="weight" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-2 border" placeholder="0.0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Temp (°C)</label>
                                    <input type="number" step="0.1" id="modal-temp" name="temp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-2 border" placeholder="38.0">
                                </div>
                            </div>

                            <div>
                                <label for="modal-post-status" class="block text-sm font-medium text-gray-700">Post-Treatment Status</label>
                                <select id="modal-post-status" name="post_treatment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-2 border bg-white">
                                    <option value="recovered">Recovered</option>
                                    <option value="stable">Stable</option>
                                    <option value="critical">Critical</option>
                                    <option value="deceased">Deceased</option>
                                </select>
                            </div>

                            <div>
                                <label for="modal-outcome-note" class="block text-sm font-medium text-gray-700">Outcome Note</label>
                                <input type="text" id="modal-outcome-note" name="outcome" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm p-2 border" placeholder="Final outcome description...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-gray-100 pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Evidence Photos</label>
                        <div class="bg-gray-100 rounded-lg border border-gray-200 p-2">
                            <div class="grid grid-cols-3 gap-2">
                                <div class="aspect-square rounded-md overflow-hidden bg-gray-200 relative group">
                                    <img id="modal-img-1" src="" class="w-full h-full object-cover hidden">
                                    <span class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs empty-label">No Image</span>
                                </div>
                                <div class="aspect-square rounded-md overflow-hidden bg-gray-200 relative group">
                                    <img id="modal-img-2" src="" class="w-full h-full object-cover hidden">
                                    <span class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs empty-label">No Image</span>
                                </div>
                                <div class="aspect-square rounded-md overflow-hidden bg-gray-200 relative group">
                                    <img id="modal-img-3" src="" class="w-full h-full object-cover hidden">
                                    <span class="absolute inset-0 flex items-center justify-center text-gray-400 text-xs empty-label">No Image</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex-none bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 sm:ml-3 sm:w-auto transition-colors">
                        Submit Report
                    </button>
                    <button type="button" onclick="closeTreatmentModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>