<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

$user_id = intval($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    echo json_encode(["deleted_ids" => []]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id FROM patients WHERE user_id = ? AND is_deleted = 1"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$deleted = [];
while ($row = $res->fetch_assoc()) {
    $deleted[] = (int)$row['id'];
}

echo json_encode([
    "deleted_ids" => $deleted
]);
