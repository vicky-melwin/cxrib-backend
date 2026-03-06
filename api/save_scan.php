<?php

//--------------------------------------------------
// PREVENT EXTRA OUTPUT
//--------------------------------------------------
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

/*
|--------------------------------------------------------------------------
| 1. VALIDATE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {

    ob_clean();
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

    ob_clean();
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
$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0777, true)) {

        ob_clean();
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create upload directory"
        ]);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 4. VALIDATE IMAGE TYPE
|--------------------------------------------------------------------------
*/
$allowed = ["jpg","jpeg","png","webp"];

$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid image format"
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 5. SAVE IMAGE FILE
|--------------------------------------------------------------------------
*/
$filename = "scan_" . time() . "_" . rand(1000,9999) . "." . $ext;

$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save image"
    ]);
    exit;
}

// ensure readable
chmod($targetPath, 0644);

/*
|--------------------------------------------------------------------------
| 6. BUILD IMAGE URL (AUTO DETECT SERVER)
|--------------------------------------------------------------------------
*/
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

$image_url = $protocol . "://" . $host . "/april_2025_batch/cxrib/api/uploads/" . $filename;

/*
|--------------------------------------------------------------------------
| 7. INSERT INTO DATABASE
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "INSERT INTO scan_history (patient_id, label, confidence, image_url)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database prepare failed"
    ]);
    exit;
}

$stmt->bind_param(
    "isds",
    $patient_id,
    $label,
    $confidence,
    $image_url
);

if (!$stmt->execute()) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed",
        "mysql_error" => $stmt->error
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| 8. SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/
$scan_id = $stmt->insert_id;

ob_clean();

echo json_encode([
    "status"    => "success",
    "scan_id"   => $scan_id,
    "image_url" => $image_url
]);

$stmt->close();
$conn->close();

exit;