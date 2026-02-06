<?php
header("Content-Type: text/html; charset=UTF-8");
require_once __DIR__ . "/db.php";

echo "<h2>Users</h2>";
$result = $conn->query("SELECT * FROM users");
if ($result) {
    while($row = $result->fetch_assoc()) { print_r($row); echo "<br>"; }
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Patients</h2>";
$result = $conn->query("SELECT * FROM patients");
if ($result) {
    while($row = $result->fetch_assoc()) { print_r($row); echo "<br>"; }
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Scan History</h2>";
$result = $conn->query("SELECT * FROM scan_history");
if ($result) {
    while($row = $result->fetch_assoc()) { print_r($row); echo "<br>"; }
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>JOIN CHECK</h2>";
$sql = "
SELECT
    sh.id as scan_id,
    sh.patient_id as scan_patient_id,
    p.id as patient_table_id,
    p.name
FROM scan_history sh
LEFT JOIN patients p ON p.id = sh.patient_id
";
$result = $conn->query($sql);
if ($result) {
    echo "<table border='1'><tr><th>Scan ID</th><th>Scan Patient ID</th><th>Patient Table ID</th><th>Match?</th></tr>";
    while($row = $result->fetch_assoc()) {
        $match = $row['patient_table_id'] ? "✅ YES" : "❌ NO MATCH";
        echo "<tr>";
        echo "<td>{$row['scan_id']}</td>";
        echo "<td>{$row['scan_patient_id']}</td>";
        echo "<td>{$row['patient_table_id']}</td>";
        echo "<td>{$match}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}
?>
