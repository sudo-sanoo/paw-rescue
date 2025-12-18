<?php
// api/generate_ai_insight.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$emergency_id = $_POST['emergency_id'] ?? null;

if (!$emergency_id) {
    echo json_encode(['success' => false, 'message' => 'Missing Emergency ID']);
    exit;
}

// 1. Fetch Emergency Data (Description & Photos)
$sql = "SELECT description, photo_evidence_1, photo_evidence_2, photo_evidence_3, ai_status, ai_insight, ai_severity_score 
        FROM emergencies WHERE emergency_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $emergency_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// 2. Check Cache
if ($data['ai_status'] === 'completed') {
    $decodedInsight = json_decode($data['ai_insight'], true);
    echo json_encode([
        'success' => true, 
        'cached' => true,
        'score' => $data['ai_severity_score'], 
        'insight' => $decodedInsight ? $decodedInsight : $data['ai_insight']
    ]);
    exit;
}

// 3. Prepare Images for Gemini (Base64 Encoding)
$basePath = __DIR__ . '/../images/uploads/emergencies/' . $emergency_id . '/';
$inlineDataParts = [];

$photos = ['photo_evidence_1', 'photo_evidence_2', 'photo_evidence_3'];
foreach ($photos as $field) {
    if (!empty($data[$field])) {
        $filePath = $basePath . basename($data[$field]);
        if (file_exists($filePath)) {
            $mimeType = mime_content_type($filePath);
            $base64Data = base64_encode(file_get_contents($filePath));
            
            $inlineDataParts[] = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data' => $base64Data
                ]
            ];
        }
    }
}

// 4. Construct the Prompt
$promptText = "You are a veterinary AI assistant. Analyze the images and description: '{$data['description']}'.
Return a valid JSON object (no markdown) with these keys:
1. 'score': Integer (0-100) based on urgency.
2. 'risks': Array of strings (e.g. ['Aggression', 'Rabies Risk']). Max 3 items.
3. 'equipment': Array of strings (e.g. ['Muzzle', 'Thick Gloves']). Max 4 items.
4. 'handling': A short string (max 30 words) on how to safely approach/lift.

Example JSON:
{
  \"score\": 85,
  \"risks\": [\"Biting\", \"Shock\"],
  \"equipment\": [\"Muzzle\", \"Stretcher\"],
  \"handling\": \"Approach slowly from side. Do not leash neck. Use blanket to slide onto stretcher.\"
}";

// 5. Call Gemini API
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;
$payload = ['contents' => [['parts' => array_merge([['text' => $promptText]], $inlineDataParts)]]];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => 'AI Service Unavailable']);
    exit;
}

// 6. Parse & Save
$jsonResponse = json_decode($response, true);
try {
    $rawText = $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
    $rawText = str_replace(['```json', '```'], '', $rawText); // Clean Markdown
    $aiData = json_decode($rawText, true);

    if (!isset($aiData['score'])) throw new Exception("Invalid AI format");

    $score = (int)$aiData['score'];
    // Store the WHOLE JSON object as a string in the 'ai_insight' text column
    $insightJson = json_encode([
        'risks' => $aiData['risks'] ?? [],
        'equipment' => $aiData['equipment'] ?? [],
        'handling' => $aiData['handling'] ?? 'Proceed with caution.'
    ]);

    $updateSql = "UPDATE emergencies SET ai_status = 'completed', ai_severity_score = ?, ai_insight = ? WHERE emergency_id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("iss", $score, $insightJson, $emergency_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'cached' => false,
        'score' => $score,
        'insight' => json_decode($insightJson) // Return object to JS
    ]);

} catch (Exception $e) {
    $conn->query("UPDATE emergencies SET ai_status = 'failed' WHERE emergency_id = '$emergency_id'");
    echo json_encode(['success' => false, 'message' => 'AI Parse Error']);
}
?>