<?php
require_once __DIR__ . "/db.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$email    = strtolower(trim($data["email"] ?? ""));
$password = trim($data["password"] ?? "");

if ($email === "" || $password === "") {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password required"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, name, email, password
     FROM users
     WHERE LOWER(email) = ?
     LIMIT 1"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    // 🔴 PLAIN TEXT CHECK (AS YOU REQUESTED)
    if ($password === $row["password"]) {
        echo json_encode([
            "status"  => "success",
            "user_id" => (int)$row["id"],
            "name"    => $row["name"],
            "email"   => $row["email"]
        ]);
        exit;
    }
}

echo json_encode([
    "status" => "error",
    "message" => "Invalid email or password"
]);
exit;
