<?php
/**
 * Migration Script: Update Grading Scheme to Bangladesh UGC Standard
 * ACADEMIX - Academic Management System
 */

require_once __DIR__ . '/../config/database.php';

echo "Starting Grading Scheme Migration...\n";

// 1. Clear existing default grading scheme (department_id IS NULL)
echo "Clearing existing default grading scheme...\n";
$stmt = $db->prepare("DELETE FROM grading_scheme WHERE department_id IS NULL");
if (!$stmt->execute()) {
    die("Error clearing older data: " . $db->error . "\n");
}
echo "Cleared " . $stmt->affected_rows . " rows.\n";

// 2. Define Bangladesh UGC Grading System
// Structure: [Grade, Min%, Max%, Point, Description]
$grades = [
    ['A+', 80, 100, 4.00, 'Outstanding'],
    ['A',  75, 79,  3.75, 'Excellent'],
    ['A-', 70, 74,  3.50, 'Very Good'],
    ['B+', 65, 69,  3.25, 'Good'],
    ['B',  60, 64,  3.00, 'Satisfactory'],
    ['B-', 55, 59,  2.75, 'Above Average'],
    ['C+', 50, 54,  2.50, 'Average'],
    ['C',  45, 49,  2.25, 'Below Average'],
    ['D',  40, 44,  2.00, 'Pass'],
    ['F',  0,  39,  0.00, 'Fail'],
];

// 3. Insert new data
echo "Inserting new grading scheme...\n";
$stmt = $db->prepare("INSERT INTO grading_scheme (department_id, grade, min_marks, max_marks, grade_point, description) VALUES (NULL, ?, ?, ?, ?, ?)");

foreach ($grades as $g) {
    // $g[0] = Grade, $g[1] = Min, $g[2] = Max, $g[3] = Point, $g[4] = Desc
    $stmt->bind_param("sddds", $g[0], $g[1], $g[2], $g[3], $g[4]);
    
    if ($stmt->execute()) {
        echo "Inserted Grade: {$g[0]} ({$g[1]}-{$g[2]}%) -> {$g[3]}\n";
    } else {
        echo "Failed to insert {$g[0]}: " . $stmt->error . "\n";
    }
}

echo "\nMigration completed successfully!\n";

// 4. Verify
echo "\nVerifying current data in DB:\n";
$result = $db->query("SELECT * FROM grading_scheme WHERE department_id IS NULL ORDER BY min_marks DESC");
echo str_pad("Grade", 10) . str_pad("Range", 15) . str_pad("Point", 10) . "Description\n";
echo str_repeat("-", 50) . "\n";
while ($row = $result->fetch_assoc()) {
    $range = $row['min_marks'] . '-' . $row['max_marks'];
    echo str_pad($row['grade'], 10) . str_pad($range, 15) . str_pad($row['grade_point'], 10) . $row['description'] . "\n";
}
?>
