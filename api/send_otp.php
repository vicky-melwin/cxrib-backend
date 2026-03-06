<?php
// --------------------------------------------------
// START OUTPUT BUFFER (IMPORTANT)
// --------------------------------------------------
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/../php_mailer/PHPMailer.php";
require_once __DIR__ . "/../php_mailer/SMTP.php";
require_once __DIR__ . "/../php_mailer/Exception.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

date_default_timezone_set("Asia/Kolkata");

// --------------------------------------------------
// READ JSON INPUT
// --------------------------------------------------
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$email = trim($data["email"] ?? "");

// --------------------------------------------------
// VALIDATE EMAIL
// --------------------------------------------------
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email"
    ]);
    exit;
}

// --------------------------------------------------
// GENERATE OTP
// --------------------------------------------------
$otp = (string) random_int(100000, 999999);
$expires = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// --------------------------------------------------
// DELETE OLD OTP
// --------------------------------------------------
$stmt = $conn->prepare("DELETE FROM email_otps WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// --------------------------------------------------
// SAVE NEW OTP
// --------------------------------------------------
$stmt = $conn->prepare(
    "INSERT INTO email_otps (email, otp, expires_at)
     VALUES (?, ?, ?)"
);

if (!$stmt) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Database prepare failed"
    ]);
    exit;
}

$stmt->bind_param("sss", $email, $otp, $expires);

if (!$stmt->execute()) {
    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save OTP"
    ]);
    exit;
}

// --------------------------------------------------
// SEND EMAIL USING PHPMailer
// --------------------------------------------------
try {

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;

    $mail->Username = "cxribiosapp@gmail.com";
    $mail->Password = "xxxdgmppynfklako";

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom("cxribiosapp@gmail.com", "CXRib");
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "CXRib OTP Verification";

$mail->Body = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CXRib OTP</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center" style="padding:30px">

<table width="420" style="background:#ffffff;border-radius:12px;
box-shadow:0 6px 18px rgba(0,0,0,0.08)">

<tr>
<td align="center"
style="padding:24px;background:linear-gradient(135deg,#4a3aff,#a855f7);
border-radius:12px 12px 0 0">

<h1 style="margin:0;color:#ffffff;font-size:24px">
CXRib
</h1>

<p style="margin:6px 0 0;color:#e0e7ff;font-size:14px">
Secure OTP Verification
</p>

</td>
</tr>

<tr>
<td style="padding:28px;text-align:center">

<p style="font-size:15px;color:#333">
Use the following One-Time Password to verify your email
</p>

<div style="
font-size:36px;
letter-spacing:10px;
font-weight:bold;
color:#4a3aff;
background:#f3f4f6;
padding:14px 0;
border-radius:8px;
margin:20px 0">

'.$otp.'

</div>

<p style="font-size:14px;color:#555">
This OTP is valid for <b>5 minutes</b>.
</p>

<p style="font-size:13px;color:#888;margin-top:20px">
If you didn’t request this email, please ignore it.
</p>

</td>
</tr>

<tr>
<td align="center"
style="padding:16px;font-size:12px;color:#999">

© '.date("Y").' CXRib. All rights reserved.

</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>
';

    $mail->send();

} catch (Exception $e) {

    ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => "Email sending failed"
    ]);
    exit;
}

// --------------------------------------------------
// SUCCESS RESPONSE
// --------------------------------------------------
ob_clean();

echo json_encode([
    "status" => "success",
    "message" => "OTP sent successfully"
]);

$stmt->close();
$conn->close();

exit;