<?php
include '../Includes/dbcon.php';

// Check if subject exists
echo "Checking subjects table...\n";
$subjectResult = $conn->query("SELECT * FROM tblsubjects WHERE subjectCode = 'MATH101'");
if($subjectResult->num_rows > 0) {
    $subject = $subjectResult->fetch_assoc();
    echo "Found subject: " . $subject['subjectName'] . " (ID: " . $subject['Id'] . ")\n";
} else {
    echo "Subject not found!\n";
}

// Check if subject teacher exists
echo "\nChecking subject teachers table...\n";
$teacherResult = $conn->query("SELECT * FROM tblsubjectteachers WHERE emailAddress = 'math.teacher@school.com'");
if($teacherResult->num_rows > 0) {
    $teacher = $teacherResult->fetch_assoc();
    echo "Found teacher: " . $teacher['firstName'] . " " . $teacher['lastName'] . "\n";
    echo "Email: " . $teacher['emailAddress'] . "\n";
    echo "Subject ID: " . $teacher['subjectId'] . "\n";
    // Verify password hash format
    echo "Password hash exists: " . (!empty($teacher['password']) ? "Yes" : "No") . "\n";
    echo "Password hash format correct: " . (strpos($teacher['password'], '$2y$') === 0 ? "Yes" : "No") . "\n";
} else {
    echo "Teacher not found!\n";
}

// Show table structure
echo "\nTable structure for tblsubjectteachers:\n";
$result = $conn->query("SHOW COLUMNS FROM tblsubjectteachers");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
