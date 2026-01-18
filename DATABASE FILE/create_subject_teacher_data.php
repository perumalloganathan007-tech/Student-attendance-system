<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

echo "<h1>Create Sample Subject Teacher Data</h1>";

// 1. Create sample subject if not exists
$subjectSQL = "INSERT IGNORE INTO tblsubjects (subjectName, subjectCode) VALUES ('Mathematics', 'MATH101')";
if($conn->query($subjectSQL)) {
    $subjectId = $conn->insert_id ?: 1;
    echo "<p>✓ Subject created/found (ID: $subjectId)</p>";
    
    // 2. Create subject teacher if not exists
    $password = password_hash("Password@123", PASSWORD_DEFAULT);
    $teacherSQL = "INSERT IGNORE INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                   VALUES ('John', 'Smith', 'john.smith@school.com', '$password', '1234567890', $subjectId)";
    
    if($conn->query($teacherSQL)) {
        $teacherId = $conn->insert_id;
        echo "<p>✓ Subject teacher created/found (ID: $teacherId)</p>";
        
        // 3. Create sample students if not exist
        $students = [
            ['Alice', 'Johnson', 'STU001'],
            ['Bob', 'Smith', 'STU002'],
            ['Carol', 'Davis', 'STU003']
        ];
        
        foreach($students as $student) {
            $studentSQL = "INSERT IGNORE INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId) 
                          VALUES ('{$student[0]}', '{$student[1]}', '{$student[2]}', 1, 1)";
            if($conn->query($studentSQL)) {
                $studentId = $conn->insert_id;
                
                // 4. Link student to subject teacher
                if($studentId) {
                    $linkSQL = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId)
                               VALUES ($teacherId, $studentId)";
                    $conn->query($linkSQL);
                }
            }
        }
        echo "<p>✓ Sample students created and linked</p>";
        
        echo "<hr><h2>Login Credentials:</h2>";
        echo "<p>Email: john.smith@school.com<br>Password: Password@123</p>";
    } else {
        echo "<p>✗ Error creating subject teacher: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✗ Error creating subject: " . $conn->error . "</p>";
}

?>
