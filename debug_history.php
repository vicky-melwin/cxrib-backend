<?php
require_once "api/db.php";

echo "<h1>Debug History Query</h1>";

$user_id = 1; // Assuming user_id 1 for testing. Change if needed.
if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
}
echo "User ID: $user_id<br>";

// 0. Check Users
$res = $conn->query("SELECT * FROM users");
echo "<h2>Users</h2>";
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}, Email: {$row['email']}, Name: {$row['name']}<br>";
}

// 1. Check Patients (ALL)
$res = $conn->query("SELECT * FROM patients");
echo "<h2>All Patients (Count: {$res->num_rows})</h2>";
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}, UserID: {$row['user_id']}, Name: {$row['name']}<br>";
}

// 2. Check Scans (ALL)
$res = $conn->query("SELECT * FROM scan_history");
echo "<h2>All Scans (Count: {$res->num_rows})</h2>";
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']}, PatientID: {$row['patient_id']}, Label: {$row['label']}<br>";
}

// 3. Check Join Query
$sql = "
SELECT
    sh.id,
    p.id AS patient_id,
    p.name AS patient_name
FROM scan_history sh
JOIN patients p ON p.id = sh.patient_id
WHERE p.user_id = $user_id
ORDER BY sh.id DESC
";

$res = $conn->query($sql);
if (!$res) {
    echo "<h2>Query Error</h2>";
    echo $conn->error;
} else {
    echo "<h2>Joined Results (Count: {$res->num_rows})</h2>";
    while ($row = $res->fetch_assoc()) {
        echo "Scan ID: {$row['id']}, Patient ID: {$row['patient_id']}, Name: {$row['patient_name']}<br>";
    }
}
?>
