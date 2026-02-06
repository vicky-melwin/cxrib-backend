<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/db.php";

$user_id = intval($_GET["user_id"] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user_id"
    ]);
    exit;
}

$sql = "
SELECT
    sh.id, -- ✅ RESTORED
    p.id AS patient_id, -- ✅ ADDED
    p.name AS patient_name,
    p.age,
    p.gender,
    p.case_id,

    -- ✅ CORRECT COLUMN
    sh.label AS prediction,

    sh.confidence,
    sh.image_url
FROM scan_history sh
JOIN patients p ON p.id = sh.patient_id
WHERE p.user_id = ? 
  AND sh.is_deleted = 0
  AND p.is_deleted = 0
ORDER BY sh.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$scans = [];

while ($row = $result->fetch_assoc()) {
    $scans[] = [
        "id" => (int)$row["id"],
        "patient_id" => (int)$row["patient_id"], // ✅ ADDED
        "patient_name" => $row["patient_name"],
        "age" => (int)$row["age"],
        "gender" => $row["gender"],
        "case_id" => (int)$row["case_id"],
        "prediction" => $row["prediction"],
        "confidence" => (float)$row["confidence"],
        "image_url" => $row["image_url"]
    ];
}

echo json_encode([
    "status" => "success",
    "scans" => $scans
]);

$stmt->close();
$conn->close();
