<?php
require_once __DIR__ . "/db.php";
header("Content-Type: application/json");

$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid user"
    ]);
    exit;
}

// ✅ Fetch only scan IDs (important for sync)
$stmt = $conn->prepare("
    SELECT id 
    FROM scan_history
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $data
]);
