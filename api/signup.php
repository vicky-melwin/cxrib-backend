<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$name     = trim($data["name"] ?? "");
$email    = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid input"
    ]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password)
     VALUES (?, ?, ?)"
);
$stmt->bind_param("sss", $name, $email, $password);
$stmt->execute();

echo json_encode([
    "status"  => "success",
    "user_id" => $stmt->insert_id
]);
exit;
