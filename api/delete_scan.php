<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

$scan_id = intval($_POST['scan_id'] ?? 0);
if ($scan_id <= 0) {
    echo json_encode(["status" => "error", "reason" => "invalid_scan_id"]);
    exit;
}

/* 1️⃣ Find patient_id */
$stmt = $conn->prepare(
    "SELECT patient_id FROM scan_history WHERE id = ?"
);
$stmt->bind_param("i", $scan_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(["status" => "not_found"]);
    exit;
}

$patient_id = intval($row['patient_id']);
$stmt->close();

/* 2️⃣ Soft Delete scan */
$stmt = $conn->prepare(
    "UPDATE scan_history SET is_deleted = 1 WHERE id = ?"
);
$stmt->bind_param("i", $scan_id);
$stmt->execute();
$stmt->close();

/* 3️⃣ Check remaining scans */
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS cnt FROM scan_history WHERE patient_id = ? AND is_deleted = 0"
);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();
$countRow = $res->fetch_assoc();
$stmt->close();

/* 4️⃣ SOFT DELETE patient if no scans left */
if ((int)$countRow['cnt'] === 0) {
    $stmt = $conn->prepare(
        "UPDATE patients SET is_deleted = 1 WHERE id = ?"
    );
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "deleted_scan_ids" => [$scan_id]
]);
