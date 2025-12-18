<?php
// api/get_nearest_vet.php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// We need the "From" location (The Emergency Scene)
$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

// SQL Logic: 
// 1. Select users with role 'veterinarian'
// 2. Calculate distance (6371 is Earth radius in km)
// 3. Order by distance ASC
// 4. Limit 1
$sql = "SELECT 
            user_id, full_name, phone, latitude, longitude,
            ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) 
            * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) 
            * sin( radians( latitude ) ) ) ) AS distance 
        FROM users 
        WHERE role = 'veterinarian' 
          AND status = 'active'
          AND latitude IS NOT NULL 
        HAVING distance < 50 
        ORDER BY distance ASC 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ddd", $lat, $lng, $lat);
$stmt->execute();
$result = $stmt->get_result();
$vet = $result->fetch_assoc();

if ($vet) {
    echo json_encode(['success' => true, 'vet' => $vet]);
} else {
    echo json_encode(['success' => false, 'message' => 'No nearby vets found']);
}
?>