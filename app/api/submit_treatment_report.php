<?php
// api/submit_treatment_report.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';

header('Content-Type: application/json');
ini_set('display_errors', 0); // Prevent HTML error output breaking JSON

// 1. Method & Auth Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if ($_SESSION['role'] !== 'veterinarian') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$vet_id = $_SESSION['user_id'];
$emergency_id = $_POST['emergency_id'] ?? null;

// 2. Data Retrieval & Sanitization
// ---------------------------------------------------------
$diagnosis = $_POST['diagnosis'] ?? '';
$treatment = $_POST['treatment_administered'] ?? '';

// Numeric fields: Convert empty strings to NULL for DB safety
$weight = (isset($_POST['weight']) && $_POST['weight'] !== '') ? $_POST['weight'] : null;
$temp   = (isset($_POST['temperature']) && $_POST['temperature'] !== '') ? $_POST['temperature'] : null;

// ENUM Status Logic:
// The JS might send 'status' or 'post_treatment_status'. We check both.
$post_status_enum = $_POST['post_treatment_status'] ?? $_POST['status'] ?? 'recovered';

// Outcome Note Logic:
// This specific text goes to the 'emergencies' table
$outcome_note = $_POST['outcome_note'] ?? '';

// Validation
if (!$emergency_id || !$diagnosis) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields (ID or Diagnosis)']);
    exit;
}

// 3. Generate Report ID (Format: TRMTyymmXXXXX)
// ---------------------------------------------------------
$prefix = "TRMT" . date("ym");
$sql = "SELECT report_id FROM treatment_reports WHERE report_id LIKE ? ORDER BY report_id DESC LIMIT 1";
$likePrefix = $prefix . '%';

$stmtId = $conn->prepare($sql);
$stmtId->bind_param("s", $likePrefix);
$stmtId->execute();
$resultId = $stmtId->get_result();
$last_id = $resultId->fetch_assoc();

if ($last_id) {
    // Extract sequence number
    $last_seq = intval(substr($last_id['report_id'], 8));
    $new_seq = str_pad($last_seq + 1, 5, "0", STR_PAD_LEFT);
} else {
    $new_seq = "00001";
}
$report_id = $prefix . $new_seq;

// 4. Insert into treatment_reports
// ---------------------------------------------------------
// Based on your Schema:
// Columns: report_id, emergency_id, vet_id, diagnosis, treatment_administered, weight, temperature, post_treatment_status
$sqlInsert = "INSERT INTO treatment_reports 
    (report_id, emergency_id, vet_id, diagnosis, treatment_administered, weight, temperature, post_treatment_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sqlInsert);

if(!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

// Bind Parameters:
// s = string, d = double (decimal)
// report_id(s), emergency_id(s), vet_id(s), diagnosis(s), treatment(s), weight(d), temp(d), post_status_enum(s)
$stmt->bind_param("sssssdds", 
    $report_id, 
    $emergency_id, 
    $vet_id, 
    $diagnosis, 
    $treatment,
    $weight,
    $temp,
    $post_status_enum
);

if ($stmt->execute()) {
    
    // 5. Update the Main Emergency Table
    // ---------------------------------------------------------
    // Requirement: Update status to 'treated' and save the outcome note in 'outcome' column
    
    $updateSql = "UPDATE emergencies 
                  SET status = 'treated', 
                      outcome = ?, 
                      updated_at = NOW() 
                  WHERE emergency_id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    
    // Bind: outcome_note(s), emergency_id(s)
    $updateStmt->bind_param("ss", $outcome_note, $emergency_id);
    
    if($updateStmt->execute()) {
        echo json_encode(['success' => true, 'report_id' => $report_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Report saved, but emergency status update failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Insert Error: ' . $stmt->error]);
}
?>