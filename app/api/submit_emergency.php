<?php
// app/api/submit_emergency.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php'; // Ensure config is loaded if needed

header('Content-Type: application/json');

// --- 1. Authentication Check ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit;
}

// --- 2. Helper: Input Sanitization ---
function sanitizeInput($data) {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// --- 3. Request Method Check ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    // --- 4. Generate Emergency ID (EMGCyymmXXXXX) ---
    $prefix = "EMGC" . date("ym"); 
    
    // Lock table or use transaction to ensure ID uniqueness in high traffic
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT emergency_id FROM emergencies WHERE emergency_id LIKE ? ORDER BY emergency_id DESC LIMIT 1");
    $search = $prefix . "%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Extract the sequence number (last 5 digits)
        $last_num = intval(substr($row['emergency_id'], -5));
        $new_num = $last_num + 1;
    } else {
        $new_num = 1;
    }
    
    $emergency_id = $prefix . str_pad($new_num, 5, "0", STR_PAD_LEFT);
    $stmt->close();

    // --- 5. Process & Sanitize Text Inputs ---
    $user_id = $_SESSION['user_id'];
    
    // Whitelist validation for ENUMs
    $valid_animals = ['dog', 'cat', 'bird', 'other'];
    $valid_urgency = ['minor', 'serious', 'critical'];

    $animal_type = $_POST['animal_type'] ?? 'other';
    if (!in_array($animal_type, $valid_animals)) $animal_type = 'other';

    $urgency = $_POST['urgency'] ?? 'serious';
    if (!in_array($urgency, $valid_urgency)) $urgency = 'serious';

    $description = sanitizeInput($_POST['description'] ?? '');
    $location_address = sanitizeInput($_POST['location_address'] ?? 'Unknown Location');
    
    // Numeric validation for coordinates
    $latitude = filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT);
    
    // Convert false to NULL if validation fails
    if ($latitude === false) $latitude = null;
    if ($longitude === false) $longitude = null;

    // --- 6. Handle Image Uploads ---
    // Structure: app/images/uploads/emergencies/{emergency_id}/photo_1.jpg
    $upload_base_path = __DIR__ . '/../images/uploads/emergencies/';
    $target_dir = $upload_base_path . $emergency_id . '/';

    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            throw new Exception("Failed to create upload directory.");
        }
    }

    $photo_db_paths = [null, null, null]; // placeholders for photo_evidence_1, 2, 3
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    // Loop through expected file keys: photo_evidence_1, photo_evidence_2, photo_evidence_3
    for ($i = 1; $i <= 3; $i++) {
        $input_name = "photo_evidence_" . $i;

        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES[$input_name]['name'];
            $file_tmp = $_FILES[$input_name]['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_extensions)) {
                // Generate standardized filename: photo_1.jpg
                $new_filename = "photo_{$i}." . $file_ext;
                $destination = $target_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Store the relative path for the database
                    // e.g., emergencies/EMGC251200001/photo_1.jpg
                    $photo_db_paths[$i-1] = "emergencies/" . $emergency_id . "/" . $new_filename;
                } else {
                    throw new Exception("Failed to move uploaded file: " . $file_name);
                }
            } else {
                throw new Exception("Invalid file type for Photo $i. Allowed: JPG, PNG, WEBP, AVIF.");
            }
        }
    }

    // Require at least one photo
    if ($photo_db_paths[0] === null) {
        throw new Exception("At least one valid photo is required to submit a report.");
    }

    // --- 7. Insert into Database ---
    $sql = "INSERT INTO emergencies 
            (emergency_id, user_id, animal_type, urgency, description, location_address, latitude, longitude, photo_evidence_1, photo_evidence_2, photo_evidence_3, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $insert_stmt = $conn->prepare($sql);
    
    if (!$insert_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $insert_stmt->bind_param(
        "ssssssddsss", 
        $emergency_id, 
        $user_id, 
        $animal_type, 
        $urgency, 
        $description, 
        $location_address, 
        $latitude, 
        $longitude, 
        $photo_db_paths[0], 
        $photo_db_paths[1], 
        $photo_db_paths[2]
    );

    if ($insert_stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Emergency report submitted successfully.',
            'emergency_id' => $emergency_id
        ]);
    } else {
        throw new Exception("Database execution error: " . $insert_stmt->error);
    }

    $insert_stmt->close();
    $conn->close();

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    // Clean up uploaded files if DB insert failed (Optional but good practice)
    // ...
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>