<?php

ob_start();
header("Content-Type: application/json");

require_once __DIR__ . "/db.php";

$uploadDir = __DIR__ . "/uploads/";

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];


// ==========================
// DEBUG LOG
// ==========================
file_put_contents(
    __DIR__ . "/debug.log",
    "\nREQUEST:\n" . print_r($_POST, true) .
    "\nFILES:\n" . print_r($_FILES, true),
    FILE_APPEND
);


// ==========================
// VALIDATE INPUT
// ==========================
$patient_id = intval($_POST["patient_id"] ?? 0);
$label      = trim($_POST["label"] ?? "");
$confidence = floatval($_POST["confidence"] ?? 0);

if ($patient_id <= 0 || $label === "" || $confidence <= 0) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid scan data"
    ]);
    exit;
}

if (!isset($_FILES["xray"])) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "No image uploaded"
    ]);
    exit;
}


// ==========================
// IMAGE VALIDATION
// ==========================
$file = $_FILES["xray"];

$allowed = ["image/jpeg", "image/png", "image/jpg"];

if (!in_array($file["type"], $allowed)) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid image type"
    ]);
    exit;
}


// ==========================
// CREATE UPLOAD FOLDER
// ==========================
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


// ==========================
// SAVE IMAGE
// ==========================
$ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

$newName = "scan_" . time() . "_" . rand(1000,9999) . "." . $ext;

$savePath = $uploadDir . $newName;

if (!move_uploaded_file($file["tmp_name"], $savePath)) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Image upload failed"
    ]);
    exit;
}


// ==========================
// BUILD IMAGE URL
// ==========================
$imageURL =
    $protocol . "://" .
    $host .
    "/april_2025_batch/cxrib/api/uploads/" .
    $newName;


// ==========================
// SAVE TO DATABASE
// ==========================
$stmt = $conn->prepare(
    "INSERT INTO scan_history (patient_id, label, confidence, image_url)
     VALUES (?, ?, ?, ?)"
);

$stmt->bind_param("isds", $patient_id, $label, $confidence, $imageURL);


if ($stmt->execute()) {

    ob_clean();
    echo json_encode([
        "status" => "success",
        "scan_id" => $stmt->insert_id,
        "image_url" => $imageURL
    ]);

} else {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
exit;