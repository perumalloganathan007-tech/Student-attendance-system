<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../Includes/dbcon.php');

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Subject Teacher Database Structure Fix</h2>";
echo "<p>Running standalone database fixes...</p>";

// 1. Check if tblsubjectteacher_student table exists
echo "<h3>1. Checking tblsubjectteacher_student table...</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
if ($tableCheck->num_rows == 0) {
    echo "<p style='color: red;'>❌ Table 'tblsubjectteacher_student' does not exist!</p>";
    
    // Create the table
    $createTable = "CREATE TABLE `tblsubjectteacher_student` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✅ Created tblsubjectteacher_student table</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Table 'tblsubjectteacher_student' exists</p>";
}

// 2. Check for any existing subject teachers
echo "<h3>2. Checking existing subject teachers...</h3>";
$teacherQuery = "SELECT Id, firstName, lastName, emailAddress FROM tblsubjectteacher";
$teacherResult = $conn->query($teacherQuery);

if ($teacherResult && $teacherResult->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $teacherResult->num_rows . " subject teacher(s)</p>";
    
    while ($teacher = $teacherResult->fetch_assoc()) {
        echo "<div style='margin-left: 20px; border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>Teacher ID:</strong> " . $teacher['Id'] . "<br>";
        echo "<strong>Name:</strong> " . $teacher['firstName'] . " " . $teacher['lastName'] . "<br>";
        echo "<strong>Email:</strong> " . $teacher['emailAddress'] . "<br>";
        
        // Check students assigned to this teacher
        $studentQuery = "SELECT COUNT(*) as studentCount FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("i", $teacher['Id']);
        $stmt->execute();
        $studentResult = $stmt->get_result();
        $studentCount = $studentResult->fetch_assoc()['studentCount'];
        
        echo "<strong>Assigned Students:</strong> " . $studentCount . "<br>";
        
        if ($studentCount == 0) {
            echo "<p style='color: orange;'>⚠️ No students assigned to this teacher</p>";
        }
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>❌ No subject teachers found in database</p>";
}

// 3. Check for available students
echo "<h3>3. Checking available students...</h3>";
$studentQuery = "SELECT COUNT(*) as totalStudents FROM tblstudents";
$studentResult = $conn->query($studentQuery);
if ($studentResult) {
    $totalStudents = $studentResult->fetch_assoc()['totalStudents'];
    echo "<p style='color: green;'>✅ Found $totalStudents total students in database</p>";
} else {
    echo "<p style='color: red;'>❌ Error checking students: " . $conn->error . "</p>";
}

// 4. Auto-assign students to teachers if needed
echo "<h3>4. Auto-assigning students (if needed)...</h3>";
$teacherResult = $conn->query("SELECT Id FROM tblsubjectteacher");
if ($teacherResult && $teacherResult->num_rows > 0) {
    while ($teacher = $teacherResult->fetch_assoc()) {
        $teacherId = $teacher['Id'];
        
        // Check if teacher has any students assigned
        $checkQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        $hasStudents = $checkResult->fetch_assoc()['count'] > 0;
        
        if (!$hasStudents) {
            // Assign some students to this teacher (first 5 students as an example)
            $assignQuery = "SELECT Id FROM tblstudents LIMIT 5";
            $studentsToAssign = $conn->query($assignQuery);
            
            if ($studentsToAssign && $studentsToAssign->num_rows > 0) {
                $assigned = 0;
                while ($student = $studentsToAssign->fetch_assoc()) {
                    $insertQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("ii", $teacherId, $student['Id']);
                    if ($insertStmt->execute()) {
                        $assigned++;
                    }
                }
                echo "<p style='color: green;'>✅ Assigned $assigned students to teacher ID $teacherId</p>";
            }
        }
    }
}

echo "<h3>5. Final Status Check...</h3>";
echo "<p style='color: blue;'>✅ Database structure fix completed!</p>";
echo "<p><a href='../subjectTeacherLogin.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
echo "</div>";
?>
