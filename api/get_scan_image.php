<?php

require_once __DIR__ . "/db.php";

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Missing scan id");
}

$scan_id = intval($_GET['id']);

$stmt = $conn->prepare(
    "SELECT image_url FROM scan_history WHERE id = ?"
);

$stmt->bind_param("i", $scan_id);
$stmt->execute();
$stmt->bind_result($image_url);
$stmt->fetch();

if (!$image_url) {
    http_response_code(404);
    exit("Scan not found");
}

$filename = basename($image_url);

$filePath = __DIR__ . "/uploads/" . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    exit("Image file not found");
}

header("Content-Type: image/jpeg");
readfile($filePath);
exit;