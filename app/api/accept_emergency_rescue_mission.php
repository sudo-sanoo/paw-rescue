<?php
// accept_emergency_rescue_mission.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $rescuer_id = $_SESSION['user_id'];
    $emergency_id = $_POST['emergency_id'] ?? '';

    // Basic Validation
    if (empty($emergency_id)) {
        throw new Exception("Emergency ID is missing.");
    }

    $conn->begin_transaction();

    $sql = "UPDATE emergencies 
            SET status = 'otw', 
                rescuer_transport = ?, 
                updated_at = NOW() 
            WHERE emergency_id = ? AND status = 'pending'";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $rescuer_id, $emergency_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Success
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Mission accepted! Drive safely.'
        ]);
    } else {
        // Failure: No row updated (likely already taken)
        $conn->rollback();
        
        // Check specific reason
        $checkSql = "SELECT status FROM emergencies WHERE emergency_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $emergency_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($row['status'] !== 'pending') {
                throw new Exception("This emergency has already been taken by another rescuer.");
            } else {
                throw new Exception("Failed to update mission status.");
            }
        } else {
            throw new Exception("Emergency ID not found.");
        }
        $checkStmt->close();
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    error_log("Accept Mission Error: " . $e->getMessage()); 
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
?>