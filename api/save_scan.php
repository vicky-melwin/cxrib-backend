<?php
header("Content-Type: application/json");
require_once __DIR__ . "/db.php";

/*
|--------------------------------------------------------------------------
| 1. VALIDATE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "No image uploaded"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. VALIDATE FORM FIELDS
|--------------------------------------------------------------------------
*/
$patient_id = intval($_POST['patient_id'] ?? 0);
$label      = trim($_POST['label'] ?? '');
$confidence = floatval($_POST['confidence'] ?? 0);

if ($patient_id <= 0 || $label === '' || $confidence <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing fields"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. PREPARE UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/
$uploadDir = __DIR__ . "/../uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| 4. SAVE IMAGE AS FILE (.jpg)
|--------------------------------------------------------------------------
*/
$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$filename = "scan_" . time() . "_" . rand(1000,9999) . "." . $ext;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save image"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. BUILD IMAGE URL
|--------------------------------------------------------------------------
*/
$image_url = "http://localhost/cerviscan-backend/uploads/" . $filename;

/*
|--------------------------------------------------------------------------
| 6. INSERT INTO DATABASE
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "INSERT INTO scan_history (patient_id, label, confidence, image_url)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param(
    "isds",
    $patient_id,
    $label,
    $confidence,
    $image_url
);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed",
        "mysql_error" => $stmt->error
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 7. SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/
echo json_encode([
    "status"    => "success",
    "scan_id"   => $stmt->insert_id,
    "image_url" => $image_url
]);
