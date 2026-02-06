<?php
// --------------------------------------------------
// HARD STOP ALL UNWANTED OUTPUT (VERY IMPORTANT)
// --------------------------------------------------
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// --------------------------------------------------
// HEADERS
// --------------------------------------------------
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// --------------------------------------------------
// HANDLE PREFLIGHT (iOS NEEDS THIS)
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --------------------------------------------------
// DB
// --------------------------------------------------
require_once __DIR__ . "/db.php";

// --------------------------------------------------
// READ RAW JSON
// --------------------------------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
    ]);
    exit;
}

// --------------------------------------------------
// SANITIZE INPUT
// --------------------------------------------------
$user_id = intval($data["user_id"] ?? 0);
$name    = trim($data["name"] ?? "");
$age     = intval($data["age"] ?? 0);
$gender  = trim($data["gender"] ?? "");
$case_id = intval($data["case_id"] ?? 0);

// --------------------------------------------------
// VALIDATION
// --------------------------------------------------
if (
    $user_id <= 0 ||
    $name === "" ||
    $age <= 0 ||
    $gender === "" ||
    $case_id <= 0
) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input"
    ]);
    exit;
}

// --------------------------------------------------
// CHECK IF PATIENT ALREADY EXISTS
// --------------------------------------------------
$stmt = $conn->prepare(
    "SELECT id FROM patients WHERE user_id = ? AND case_id = ? LIMIT 1"
);
$stmt->bind_param("ii", $user_id, $case_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    ob_clean();
    echo json_encode([
        "status" => "success",
        "patient_id" => (int)$row["id"]
    ]);
    exit;
}
$stmt->close();

// --------------------------------------------------
// INSERT NEW PATIENT
// --------------------------------------------------
$stmt = $conn->prepare(
    "INSERT INTO patients (user_id, name, age, gender, case_id, created_at)
     VALUES (?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("isisi", $user_id, $name, $age, $gender, $case_id);

if (!$stmt->execute()) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Insert failed"
    ]);
    exit;
}

// --------------------------------------------------
// SUCCESS RESPONSE (ONLY JSON — NOTHING ELSE)
// --------------------------------------------------
ob_clean();
echo json_encode([
    "status" => "success",
    "patient_id" => (int)$stmt->insert_id
]);

$stmt->close();
$conn->close();
exit;
