<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$user_id = intval($data["user_id"] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user_id"
    ]);
    exit;
}

$sql = "
SELECT 
    sh.id,
    sh.label,
    sh.confidence,
    sh.image_url,
    sh.created_at,
    p.name AS patient_name,
    p.age,
    p.gender,
    p.case_id
FROM scan_history sh
JOIN patients p ON p.id = sh.patient_id
WHERE p.user_id = ?
ORDER BY sh.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$scans = [];
while ($row = $result->fetch_assoc()) {
    $scans[] = $row;
}

echo json_encode([
    "status" => "success",
    "scans" => $scans
]);
