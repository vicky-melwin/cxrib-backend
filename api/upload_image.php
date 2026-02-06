<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

// ===============================
// CONFIG
// ===============================
$uploadDir = __DIR__ . "/../uploads/";
$allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
$maxSize = 5 * 1024 * 1024; // 5MB

// ===============================
// VALIDATION
// ===============================
if (!isset($_FILES["image"])) {
    echo json_encode([
        "status" => "error",
        "message" => "No image uploaded"
    ]);
    exit;
}

$file = $_FILES["image"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode([
        "status" => "error",
        "message" => "Upload error"
    ]);
    exit;
}

if (!in_array($file["type"], $allowedTypes)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid image type"
    ]);
    exit;
}

if ($file["size"] > $maxSize) {
    echo json_encode([
        "status" => "error",
        "message" => "Image too large (max 5MB)"
    ]);
    exit;
}

// ===============================
// SAVE IMAGE
// ===============================
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 755, true);
}

$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = "img_" . time() . "_" . rand(1000, 9999) . "." . $ext;
$savePath = $uploadDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $savePath)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save image"
    ]);
    exit;
}

// ===============================
// SUCCESS RESPONSE
// ===============================
echo json_encode([
    "status" => "success",
    "image_name" => $filename,
    "image_url" => "http://localhost/cerviscan-backend/uploads/" . $filename
]);
