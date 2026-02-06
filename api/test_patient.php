<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/db.php";

// ================= TEST QUERY =================

$sql = "
INSERT INTO patients (user_id, name, age, gender, case_id)
VALUES (1, 'TEST PATIENT', 35, 'Male', 1234)
";

if ($conn->query($sql)) {
    echo json_encode([
        "status" => "success",
        "patient_id" => $conn->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "mysql_error" => $conn->error
    ]);
}
