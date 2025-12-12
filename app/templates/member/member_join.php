<!-- member_join.php -->
<?php
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/db.php';

requireRole(['user']);
?>

<!-- VIEW: Join (Volunteer Application) -->
<div id="view-join" class="hidden animate-fade-in max-w-4xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Become a PawRescue Hero</h2>
        <p class="text-gray-500 max-w-2xl mx-auto mt-2">Complete this comprehensive verification to join our rescue team.</p>
    </div>

    <!-- Wizard Progress -->
    <div class="flex items-center justify-between max-w-2xl mx-auto mb-10 relative">
        <!-- Step 1 -->
        <div class="flex-none flex flex-col items-center gap-2 z-10">
            <div id="step-indicator-1" class="w-10 h-10 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold border-4 border-white shadow transition-colors">1</div>
            <span class="text-xs font-bold text-gray-600">ID Verification</span>
        </div>
        
        <!-- Connector 1-2 -->
        <div id="line-1" class="flex-1 h-0.5 border-t-4 border-dotted border-gray-200 mx-2 transition-all duration-300"></div>

        <!-- Step 2 -->
        <div class="flex-none flex flex-col items-center gap-2 z-10">
            <div id="step-indicator-2" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold border-4 border-white shadow transition-colors">2</div>
            <span class="text-xs font-medium text-gray-500">Legal</span>
        </div>

        <!-- Connector 2-3 -->
        <div id="line-2" class="flex-1 h-0.5 border-t-4 border-dotted border-gray-200 mx-2 transition-all duration-300"></div>

        <!-- Step 3 -->
        <div class="flex-none flex flex-col items-center gap-2 z-10">
            <div id="step-indicator-3" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold border-4 border-white shadow transition-colors">3</div>
            <span class="text-xs font-medium text-gray-500">Quals</span>
        </div>

        <!-- Connector 3-4 -->
        <div id="line-3" class="flex-1 h-0.5 border-t-4 border-dotted border-gray-200 mx-2 transition-all duration-300"></div>

        <!-- Step 4 -->
        <div class="flex-none flex flex-col items-center gap-2 z-10">
            <div id="step-indicator-4" class="w-10 h-10 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center font-bold border-4 border-white shadow transition-colors">4</div>
            <span class="text-xs font-medium text-gray-500">Agreement</span>
        </div>
    </div>

    <form id="volunteer-form" onsubmit="event.preventDefault();" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- STEP 1: Identification -->
        <div id="step-content-1" class="p-8 space-y-6">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                <div class="bg-blue-100 p-2 rounded-lg text-blue-600"><i data-lucide="id-card" class="w-6 h-6"></i></div>
                <h3 class="text-xl font-bold text-gray-800">I. Applicant Identification & Verification</h3>
            </div>
            
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 flex gap-2">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    Please provide a clear photo of your Malaysian Identity Card (MyKad). This information is encrypted and used solely for identity verification.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Front IC -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">MyKad (Front)</label>
                    <!-- Box for MyKad click to activate the camera -->
                    <div id="mykad-box-front" class="w-full aspect-[1.58/1] border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center hover:bg-gray-50 transition-colors cursor-pointer group"
                         onclick="openMyKadCamera('front')">
                        <div class="w-16 h-12 bg-gray-200 rounded mx-auto mb-3 flex items-center justify-center text-gray-400 group-hover:text-blue-500">
                            <i data-lucide="image" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm font-medium text-blue-600">MyKad Front</p>
                        <p class="text-xs text-gray-400 mt-1">Place the front of your MyKad in the box.</p>
                    </div>
                    <!-- Preview image (hidden until capture) -->
                    <img id="mykad-preview-front" src="" alt="MyKad front preview" class="hidden w-full aspect-[1.58/1] object-cover rounded-xl border cursor-pointer hover:opacity-80 transition-opacity" />
                </div>
                <!-- Back IC -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">MyKad (Back)</label>
                    <!-- Box for MyKad click to activate the camera -->
                    <div id="mykad-box-back" class="w-full aspect-[1.58/1] border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-center hover:bg-gray-50 transition-colors cursor-pointer group"
                         onclick="openMyKadCamera('back')">
                        <div class="w-16 h-12 bg-gray-200 rounded mx-auto mb-3 flex items-center justify-center text-gray-400 group-hover:text-blue-500">
                            <i data-lucide="image" class="w-6 h-6"></i>
                        </div>
                        <p class="text-sm font-medium text-blue-600">MyKad Back</p>
                        <p class="text-xs text-gray-400 mt-1">Place the back of your MyKad in the box.</p>
                    </div>
                    <!-- Preview image (hidden until capture) -->
                    <img id="mykad-preview-back" src="" alt="MyKad back preview" class="hidden w-full aspect-[1.58/1] object-cover rounded-xl border cursor-pointer hover:opacity-80 transition-opacity" />
                </div>
            </div>
        </div>

        <!-- STEP 2: Legal -->
        <div id="step-content-2" class="hidden p-8 space-y-6">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                <div class="bg-red-100 p-2 rounded-lg text-red-600"><i data-lucide="shield-alert" class="w-6 h-6"></i></div>
                <h3 class="text-xl font-bold text-gray-800">II. Legal & Background Checks</h3>
            </div>

            <!-- Consent 1 -->
            <div class="border border-red-100 bg-red-50 rounded-lg p-5">
                <h4 class="font-bold text-red-800 mb-2 flex items-center gap-2">
                    <i data-lucide="file-search" class="w-4 h-4"></i>
                    1. Consent for Criminal Background Check
                </h4>
                <p class="text-sm text-red-700 mb-4 leading-relaxed">
                    A mandatory field requiring agreement to a comprehensive background check (which may involve local law enforcement records and animal abuse registries).
                </p>
                <label class="flex items-start gap-3 cursor-pointer group p-3 bg-white rounded border border-red-100 hover:border-red-300 transition-all shadow-sm">
                    <input type="checkbox" id="legal-consent-check" class="mt-1 w-5 h-5 rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer">
                    <span class="text-sm font-bold text-gray-800 group-hover:text-red-700">I voluntarily consent to a comprehensive criminal background check.</span>
                </label>
            </div>

            <!-- Consent 2 -->
            <div class="border border-gray-200 rounded-lg p-5">
                <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-2">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-orange-500"></i>
                    2. Disclosure of Prior Convictions
                </h4>
                <p class="text-sm text-gray-600 mb-4">
                    Applicants must explicitly disclose any past convictions related to animal abuse, neglect, violence, or fraud. Falsifying this information will result in disqualification.
                </p>
                
                <div class="space-y-3">
                    <p class="text-sm font-bold text-gray-700">Have you ever been convicted of a crime involving animals or violence?</p>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg w-full hover:bg-gray-50">
                            <input type="radio" name="conviction" value="no" class="text-orange-600 focus:ring-orange-500" onclick="document.getElementById('conviction-details').classList.add('hidden')">
                            <span class="text-sm font-medium">No, I have not.</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-3 border rounded-lg w-full hover:bg-gray-50">
                            <input type="radio" name="conviction" value="yes" class="text-orange-600 focus:ring-orange-500" onclick="document.getElementById('conviction-details').classList.remove('hidden')">
                            <span class="text-sm font-medium">Yes, I have.</span>
                        </label>
                    </div>
                    
                    <div id="conviction-details" class="hidden mt-3 animate-fade-in">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Please explain the nature of the conviction:</label>
                        <textarea 
                            id="conviction-text"
                            class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-orange-500 outline-none h-32 resize-none scrollbar-thin" 
                            maxlength="300" 
                            placeholder="Provide details..."
                            oninput="updateCharCount(this, 'conviction-counter', 300)"></textarea>
                        <div id="conviction-counter" class="text-right text-xs text-gray-400 mt-1 font-medium">300 out of 300 characters remaining</div>
                        <div id="conviction-warning" class="hidden text-xs text-red-500 mt-1">Special characters forbidden.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: Qualifications -->
        <div id="step-content-3" class="hidden p-8 space-y-6">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                <div class="bg-green-100 p-2 rounded-lg text-green-600"><i data-lucide="award" class="w-6 h-6"></i></div>
                <h3 class="text-xl font-bold text-gray-800">III. Qualifications & Experience</h3>
            </div>

            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg mb-2 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-500"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-amber-800">Mandatory Prerequisite</h3>
                        <div class="mt-1 text-sm text-amber-700">
                            <p>Please Note: To ensure the safety and efficiency of our rescue operations, possession of a valid driver's license and immediate access to a reliable vehicle are <strong>mandatory prerequisites</strong> for this role.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 relative z-30">
                <!-- Driver's License Status Dropdown -->
                <div class="relative custom-dropdown-container">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Driver's License Status</label>
                    <button onclick="toggleCustomDropdown('license-status')" class="w-full flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3 text-sm outline-none focus:ring-2 focus:ring-green-500 hover:border-gray-400 transition-colors">
                        <span id="license-status-label" class="text-gray-700">Full License (D/DA)</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>
                    
                    <div id="license-status-menu" class="custom-dropdown-menu hidden absolute left-0 top-full mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden dropdown-enter z-50">
                        <div class="p-1">
                            <button onclick="selectCustomOption('license-status', 'full', 'Full License (D/DA)')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Full License (D/DA)</button>
                            <button onclick="selectCustomOption('license-status', 'probation', 'Probationary (P)')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Probationary (P)</button>
                            <button onclick="selectCustomOption('license-status', 'motorcycle', 'Motorcycle Only')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Motorcycle Only</button>
                        </div>
                    </div>
                </div>
                
                <!-- Vehicle Availability Dropdown -->
                <div class="relative custom-dropdown-container">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Availability</label>
                    <button onclick="toggleCustomDropdown('vehicle-availability')" class="w-full flex items-center justify-between bg-white border border-gray-300 rounded-lg p-3 text-sm outline-none focus:ring-2 focus:ring-green-500 hover:border-gray-400 transition-colors">
                        <span id="vehicle-availability-label" class="text-gray-700">Car (Own)</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>
                    
                    <div id="vehicle-availability-menu" class="custom-dropdown-menu hidden absolute left-0 top-full mt-2 w-full bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden dropdown-enter z-50">
                        <div class="p-1">
                            <button onclick="selectCustomOption('vehicle-availability', 'car', 'Car (Own)')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Car (Own)</button>
                            <button onclick="selectCustomOption('vehicle-availability', 'van', 'Van/Truck (Own)')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Van/Truck (Own)</button>
                            <button onclick="selectCustomOption('vehicle-availability', 'motorcycle', 'Motorcycle (Own)')" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded-lg transition-colors">Motorcycle (Own)</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload License -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Driver's License / Digital Copy (Required)</label>
                <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg bg-gray-50">
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded text-sm font-medium hover:bg-gray-100">Choose File</button>
                    <span class="text-sm text-gray-500 italic">No file chosen</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Accepts screenshots from MyJPJ app or PDF exports.</p>
            </div>

            <!-- Experience -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Animal Handling Experience</label>
                <textarea 
                    id="experience-text"
                    class="w-full border border-gray-300 rounded-lg p-3 text-sm outline-none focus:border-green-500 h-32 resize-none scrollbar-thin" 
                    maxlength="100" 
                    placeholder="e.g., Worked at shelter for 2 years, own 3 dogs..."
                    oninput="updateCharCount(this, 'experience-counter', 100)"></textarea>
                <div id="experience-counter" class="text-right text-xs text-gray-400 mt-1 font-medium">100 out of 100 characters remaining</div>
            </div>

            <!-- Certifications -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Training / Certifications (Optional)</label>
                <textarea 
                    id="certifications-text"
                    class="w-full border border-gray-300 rounded-lg p-3 text-sm outline-none focus:border-green-500 h-32 resize-none scrollbar-thin" 
                    maxlength="100" 
                    placeholder="e.g., Pet First Aid Level 1"
                    oninput="updateCharCount(this, 'cert-counter', 100)"></textarea>
                <div id="cert-counter" class="text-right text-xs text-gray-400 mt-1 font-medium">100 out of 100 characters remaining</div>
            </div>
        </div>

        <!-- STEP 4: Agreement -->
        <div id="step-content-4" class="hidden p-8 space-y-6">
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                <div class="bg-gray-100 p-2 rounded-lg text-gray-600"><i data-lucide="pen-tool" class="w-6 h-6"></i></div>
                <h3 class="text-xl font-bold text-gray-800">IV. Agreement & Signature</h3>
            </div>

            <!-- Scrollable Terms Box -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 h-48 overflow-y-auto text-sm text-gray-600 leading-relaxed mb-4 text-justify pr-2">
                <strong class="text-gray-900 block mb-2">1. Code of Conduct Agreement</strong>
                I agree to treat all animals with respect and compassion. I will follow all PawRescue protocols regarding safety and animal welfare.<br><br>
                <strong class="text-gray-900 block mb-2">2. Waiver of Liability</strong>
                I understand the risks involved in animal rescue and hereby waive PawRescue from liability for injuries or damages sustained during volunteer activities.<br><br>
                <strong class="text-gray-900 block mb-2">3. Mandatory Reporting Clause</strong>
                I agree to immediately report any signs of animal abuse or neglect I encounter during my duties.<br><br>
                <strong class="text-gray-900 block mb-2">4. Commitment to Mission</strong>
                I pledge to uphold the mission of PawRescue to save, rehabilitate, and rehome animals in need.
            </div>

            <!-- Checkboxes -->
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" class="w-4 h-4 text-orange-600 rounded focus:ring-orange-500 cursor-pointer">
                    <span class="text-sm text-gray-700">I have read and agree to the <span class="font-bold">Code of Conduct</span> & <span class="font-bold">Waiver of Liability</span>.</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" class="w-4 h-4 text-orange-600 rounded focus:ring-orange-500 cursor-pointer">
                    <span class="text-sm text-gray-700">I acknowledge the <span class="font-bold">Mandatory Reporting Clause</span>.</span>
                </label>
            </div>

            <!-- Signature Pad -->
            <div class="mt-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Signature</label>
                <div class="border border-gray-300 rounded-lg bg-white relative overflow-hidden" style="height: 160px;">
                    <canvas id="signature-pad" class="w-full h-full cursor-crosshair"></canvas>
                    <div class="absolute bottom-2 right-2 flex gap-2">
                        <button type="button" onclick="clearSignature()" class="text-xs text-red-500 hover:text-red-700 bg-white border border-red-200 px-2 py-1 rounded">Clear</button>
                    </div>
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-200 pointer-events-none text-4xl font-cursive opacity-50">Sign Here</div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Draw your signature above using your mouse or finger.</p>
            </div>
        </div>

        <!-- Hidden fields to carry base64 images to backend when the user submits -->
        <input type="hidden" id="mykad_front_base64" name="mykad_front_base64" value="">
        <input type="hidden" id="mykad_back_base64" name="mykad_back_base64" value="">

        <!-- Navigation Buttons -->
        <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
            <button id="btn-back" type="button" onclick="changeStep(-1)" class="hidden px-6 py-2 text-gray-600 hover:text-gray-800 font-medium">Back</button>
            <div class="flex-1"></div>
            <button id="btn-next" type="button" onclick="changeStep(1)" class="px-8 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-black font-bold shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                <span>Next Step</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
            <button id="btn-submit" type="button" onclick="openLegalModal()" class="hidden px-8 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-bold shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                <span>Submit Application</span>
                <i data-lucide="check" class="w-4 h-4"></i>
            </button>
        </div>
    </form>
</div>

<script>
    const CURRENT_USER_ID = "<?php echo $_SESSION['user_id']; ?>";
</script>