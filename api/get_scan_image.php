<?php
require_once __DIR__ . "/db.php";

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Missing scan id");
}

$scan_id = intval($_GET['id']);

$stmt = $conn->prepare(
    "SELECT image_data FROM scan_history WHERE id = ?"
);
$stmt->bind_param("i", $scan_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    http_response_code(404);
    exit("Scan not found");
}

$stmt->bind_result($image);
$stmt->fetch();

header("Content-Type: image/jpeg");
echo $image;
