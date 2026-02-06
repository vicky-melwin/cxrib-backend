<?php
header("Content-Type: text/plain; charset=UTF-8");
require_once __DIR__ . "/db.php";

// Fix Scan 139 -> Patient 210
$sql1 = "UPDATE scan_history SET patient_id = 210 WHERE id = 139";
if ($conn->query($sql1) === TRUE) {
    echo "✅ Fixed Scan 139 -> Patient 210\n";
} else {
    echo "❌ Error fixing Scan 139: " . $conn->error . "\n";
}

// Fix Scan 141 -> Patient 212
$sql2 = "UPDATE scan_history SET patient_id = 212 WHERE id = 141";
if ($conn->query($sql2) === TRUE) {
    echo "✅ Fixed Scan 141 -> Patient 212\n";
} else {
    echo "❌ Error fixing Scan 141: " . $conn->error . "\n";
}

$conn->close();
?>
