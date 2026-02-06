<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

$uploadDir = __DIR__ . "/../uploads/";
$host = $_SERVER['HTTP_HOST'];   // ✅ FIX

file_put_contents(
    __DIR__ . "/db_check.log",
    "DB=" . $conn->query("SELECT DATABASE()")->fetch_row()[0] . PHP_EOL,
    FILE_APPEND
);

// ==========================
// DEBUG LOG (KEEP FOR NOW)
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
    echo json_encode(["status" => "error", "message" => "Invalid scan data"]);
    exit;
}

if (!isset($_FILES["xray"])) {
    echo json_encode(["status" => "error", "message" => "No image uploaded"]);
    exit;
}

// ==========================
// IMAGE UPLOAD
// ==========================
$file = $_FILES["xray"];
$allowed = ["image/jpeg", "image/png", "image/jpg"];

if (!in_array($file["type"], $allowed)) {
    echo json_encode(["status" => "error", "message" => "Invalid image type"]);
    exit;
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($file["name"], PATHINFO_EXTENSION);
$newName = "xray_" . time() . "_" . rand(1000,9999) . "." . $ext;
$savePath = $uploadDir . $newName;

if (!move_uploaded_file($file["tmp_name"], $savePath)) {
    echo json_encode(["status" => "error", "message" => "Image upload failed"]);
    exit;
}

// ✅ CORRECT IMAGE URL (WORKS FOR NGROK + LOCAL)
$imageURL = "https://$host/cerviscan-backend/uploads/$newName";

// ==========================
// SAVE TO DB
// ==========================
$stmt = $conn->prepare("
    INSERT INTO scan_history (patient_id, label, confidence, image_url)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("isds", $patient_id, $label, $confidence, $imageURL);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "scan_id" => $stmt->insert_id,
        "image_url" => $imageURL
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}
