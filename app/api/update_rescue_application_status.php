<?php
// app/api/update_rescue_application_status.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 2. Get Input
$input = json_decode(file_get_contents('php://input'), true);
$app_id = $input['application_id'] ?? null;
$status = $input['status'] ?? null;

if (!$app_id || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// 3. Process Transaction
$conn->autocommit(false); // Start Transaction

try {
    // Determine is_resolved based on your requirement:
    // Approved -> is_resolved = 'yes'
    // Rejected -> is_resolved = 'no' (stays no)
    $is_resolved = ($status === 'approved') ? 'yes' : 'no';

    // A. Update Application Status
    $stmt = $conn->prepare("UPDATE rescuer_applications SET status = ?, is_resolved = ?, updated_at = NOW() WHERE application_id = ?");
    $stmt->bind_param("sss", $status, $is_resolved, $app_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update application status");
    }
    $stmt->close();

    // B. If Approved, Upgrade User Role
    if ($status === 'approved') {
        // Get user_id associated with this application
        $u_stmt = $conn->prepare("SELECT user_id FROM rescuer_applications WHERE application_id = ?");
        $u_stmt->bind_param("s", $app_id);
        $u_stmt->execute();
        $res = $u_stmt->get_result();
        $user_data = $res->fetch_assoc();
        $u_stmt->close();

        if ($user_data) {
            $user_id = $user_data['user_id'];
            
            // Update user role to 'volunteer'
            $role_stmt = $conn->prepare("UPDATE users SET role = 'volunteer' WHERE user_id = ?");
            $role_stmt->bind_param("s", $user_id);
            
            if (!$role_stmt->execute()) {
                throw new Exception("Failed to update user role");
            }
            $role_stmt->close();
        } else {
             throw new Exception("Application user not found");
        }
    }

    $conn->commit(); // Commit Transaction
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    error_log("Approval Error: " . $e->getMessage()); // Good practice to log internal errors
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>