<?php
ob_clean();
require_once __DIR__ . "/db.php";

header("Content-Type: application/json");
date_default_timezone_set("Asia/Kolkata");

$data = json_decode(file_get_contents("php://input"), true);

$email = strtolower(trim($data["email"] ?? ""));
$otp   = trim($data["otp"] ?? "");

if (empty($email) || empty($otp)) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and OTP required"
    ]);
    exit;
}

// 🔹 Fetch OTP from DB
$stmt = $conn->prepare(
    "SELECT otp, expires_at 
     FROM email_otps 
     WHERE email = ? 
     ORDER BY id DESC 
     LIMIT 1"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP not found. Please resend OTP."
    ]);
    exit;
}

$row = $result->fetch_assoc();

// 🔹 Check expiry
if (strtotime($row["expires_at"]) < time()) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP expired. Please resend."
    ]);
    exit;
}

// 🔹 Compare OTP (STRING SAFE)
if ((string)$row["otp"] !== (string)$otp) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid OTP"
    ]);
    exit;
}

// 🔹 OTP verified → delete it
$stmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "message" => "OTP verified successfully"
]);

if (strtotime($row["expires_at"]) < time()) {
    echo json_encode([
        "status" => "error",
        "message" => "OTP expired. Please resend."
    ]);
    exit;
}

exit;
