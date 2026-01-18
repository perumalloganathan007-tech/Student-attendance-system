<?php
include '../Includes/dbcon.php';

// Alter tables to support longer password hashes
$alterQueries = [
    "ALTER TABLE tbladmin MODIFY password VARCHAR(255) NOT NULL",
    "ALTER TABLE tblclassteacher MODIFY password VARCHAR(255) NOT NULL",
    "ALTER TABLE tblstudents MODIFY password VARCHAR(255) NOT NULL"
];

foreach ($alterQueries as $query) {
    if (!$conn->query($query)) {
        echo "Error updating table structure: " . $conn->error . "\n";
    }
}

// Set default passwords
$adminPass = hash_password('admin@123');
$teacherPass = hash_password('teacher@123');
$studentPass = hash_password('student@123');

// Update admin password
$updateAdmin = $conn->prepare("UPDATE tbladmin SET password = ? WHERE emailAddress = 'admin@mail.com'");
$updateAdmin->bind_param("s", $adminPass);
if ($updateAdmin->execute()) {
    echo "Admin password updated successfully\n";
} else {
    echo "Error updating admin password: " . $conn->error . "\n";
}

// Update all class teacher passwords
$updateTeachers = $conn->prepare("UPDATE tblclassteacher SET password = ?");
$updateTeachers->bind_param("s", $teacherPass);
if ($updateTeachers->execute()) {
    echo "Class teacher passwords updated successfully\n";
} else {
    echo "Error updating teacher passwords: " . $conn->error . "\n";
}

// Update all student passwords
$updateStudents = $conn->prepare("UPDATE tblstudents SET password = ?");
$updateStudents->bind_param("s", $studentPass);
if ($updateStudents->execute()) {
    echo "Student passwords updated successfully\n";
} else {
    echo "Error updating student passwords: " . $conn->error . "\n";
}

echo "\nDefault passwords have been set:\n";
echo "Administrator (admin@mail.com): admin@123\n";
echo "Class Teachers: teacher@123\n";
echo "Students: student@123\n";
?>
