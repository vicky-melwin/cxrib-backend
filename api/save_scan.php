<?php
// --------------------------------------------------
// HARD STOP ALL UNWANTED OUTPUT (VERY IMPORTANT)
// --------------------------------------------------
ob_start();
error_reporting(E_ALL); 
ini_set('display_errors', 0); // Don't display errors in output

header("Content-Type: application/json");
require_once __DIR__ . "/db.php";

// LOGGING START
$logFile = __DIR__ . "/debug.log";
// Ensure log file is writable
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0777);
}

$logMsg = "\n[" . date("Y-m-d H:i:s") . "] save_scan.php CALLED\n";
$logMsg .= "POST: " . print_r($_POST, true) . "\n";
file_put_contents($logFile, $logMsg, FILE_APPEND);

/*
|--------------------------------------------------------------------------
| 1. VALIDATE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $msg = "No image uploaded or upload error: " . ($_FILES['image']['error'] ?? 'Unknown');
    file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $msg
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

if ($patient_id <= 0 || $label === '') {
    $msg = "Invalid inputs: patient_id=$patient_id, label='$label', confidence=$confidence";
    file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
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
// USE LOCAL API UPLOADS FOLDER TO AVOID PERMISSION ISSUES
$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {
    // Try to create directory with full permissions
    if (!mkdir($uploadDir, 0777, true)) {
        $msg = "Failed to create directory $uploadDir. Check permissions.";
        file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
        echo json_encode(["status" => "error", "message" => $msg]);
        exit;
    }
    chmod($uploadDir, 0777); // Ensure it's writable
}

/*
|--------------------------------------------------------------------------
| 4. SAVE IMAGE AS FILE (.jpg)
|--------------------------------------------------------------------------
*/
$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
if (!$ext) $ext = "jpg"; // Default extension if missing
$filename = "scan_" . time() . "_" . rand(1000,9999) . "." . $ext;
$targetPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
    // Attempting to debug permission error
    $error = error_get_last();
    $phpError = isset($error['message']) ? $error['message'] : 'Unknown PHP error';
    
    $msg = "Failed to move uploaded file to $targetPath. PHP Error: $phpError";
    file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save image. Check server permissions."
    ]);
    exit;
}

// Ensure the new file has read permissions for web server
chmod($targetPath, 0644);

/*
|--------------------------------------------------------------------------
| 5. BUILD IMAGE URL
|--------------------------------------------------------------------------
*/
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST']; // e.g., 10.220.4.98 or localhost
$image_url = "$protocol://$host/cerviscan-backend/api/uploads/" . $filename;


/*
|--------------------------------------------------------------------------
| 6. INSERT INTO DATABASE
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "INSERT INTO scan_history (patient_id, label, confidence, image_url)
     VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    $msg = "Prepare failed: " . $conn->error;
    file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
    ob_clean();
    echo json_encode(["status" => "error", "message" => $msg]);
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
    $msg = "Database Error: " . $stmt->error;
    file_put_contents($logFile, "ERROR: $msg\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed",
        "mysql_error" => $stmt->error
    ]);
    exit; // Important
}

$scan_id = $stmt->insert_id;
file_put_contents($logFile, "SUCCESS: Scan saved with ID $scan_id\n", FILE_APPEND);

/*
|--------------------------------------------------------------------------
| 7. SUCCESS RESPONSE
|--------------------------------------------------------------------------
*/
ob_clean();
echo json_encode([
    "status"    => "success",
    "scan_id"   => $scan_id,
    "image_url" => $image_url
]);

$stmt->close();
$conn->close();
