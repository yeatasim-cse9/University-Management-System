<?php
$conn = new mysqli('localhost', 'root', '', 'academix');
if ($conn->connect_error) die("Conn failed");

$tables = ['semesters'];

foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $res = $conn->query("SHOW COLUMNS FROM $t");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Table not found or error: " . $conn->error . "\n";
    }
    echo "\n";
}
