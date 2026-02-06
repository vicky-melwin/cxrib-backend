<?php
require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "User ID missing"
    ]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Account deleted permanently"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete account"
    ]);
}
