<?php
include '../Includes/dbcon.php';

// Delete existing test data first
$conn->query("DELETE FROM tblsubjectteachers WHERE emailAddress = 'math.teacher@school.com'");
$conn->query("DELETE FROM tblsubjects WHERE subjectCode = 'MATH101'");

// First create a subject
$subjectQuery = "INSERT INTO tblsubjects (subjectName, subjectCode) 
                VALUES ('Mathematics', 'MATH101')";
$conn->query($subjectQuery);

// Get the subject ID
$subjectId = $conn->insert_id;
if (!$subjectId) {
    die("Error creating subject: " . $conn->error);
}

// Create a subject teacher with proper password hashing
$password = "Math@123"; // This will be the login password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify the hash was created properly
if (!$hashedPassword || !password_verify($password, $hashedPassword)) {
    die("Error creating password hash");
}

$teacherQuery = "INSERT INTO tblsubjectteachers (
                    firstName, 
                    lastName, 
                    emailAddress, 
                    password,
                    phoneNo,
                    subjectId
                ) VALUES (
                    'John',
                    'Smith',
                    'math.teacher@school.com',
                    '$hashedPassword',
                    '1234567890',
                    $subjectId
                )";

if($conn->query($teacherQuery)) {
    echo "Subject teacher created successfully!\n";
    echo "Login credentials:\n";
    echo "Email: math.teacher@school.com\n";
    echo "Password: Math@123\n";
} else {
    echo "Error creating subject teacher: " . $conn->error;
}
?>
