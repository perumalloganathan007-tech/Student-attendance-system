<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../Includes/dbcon.php');
session_start();

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Subject Teacher - Student Assignment Check</h2>";

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo "<p style='color: red;'>❌ Not logged in. <a href='../subjectTeacherLogin.php'>Login here</a></p>";
    echo "</div>";
    exit();
}

$teacherId = $_SESSION['userId'];
echo "<p>Current Teacher ID: $teacherId</p>";

// Check if tblsubjectteacher_student table exists
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

// Check students assigned to this teacher
$query = "SELECT COUNT(*) as studentCount FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['studentCount'];

echo "<p>Students assigned to this teacher: $count</p>";

if ($count == 0) {
    echo "<p style='color: orange;'>⚠️ No students assigned to this teacher!</p>";
    
    // Let's assign some sample students
    $studentsQuery = "SELECT Id, firstName, lastName FROM tblstudents LIMIT 5";
    $studentsResult = $conn->query($studentsQuery);
    
    if ($studentsResult && $studentsResult->num_rows > 0) {
        echo "<p>Assigning sample students...</p>";
        $insertQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        while ($student = $studentsResult->fetch_assoc()) {
            $insertStmt->bind_param("ii", $teacherId, $student['Id']);
            if ($insertStmt->execute()) {
                echo "<p style='color: green;'>✅ Assigned student: " . $student['firstName'] . " " . $student['lastName'] . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Error assigning student: " . $conn->error . "</p>";
            }
        }
        
        echo "<p><strong>Students have been assigned! Now try viewing students again.</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ No students found in the database!</p>";
    }
} else {
    // Show assigned students
    $query = "SELECT s.Id, s.firstName, s.lastName, s.admissionNumber 
              FROM tblstudents s
              INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
              WHERE sts.subjectTeacherId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h3>Assigned Students:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Admission Number</th></tr>";
    
    while ($student = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $student['Id'] . "</td>";
        echo "<td>" . $student['firstName'] . " " . $student['lastName'] . "</td>";
        echo "<td>" . $student['admissionNumber'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Actions:</h3>";
echo "<p><a href='viewStudents.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Students Page</a></p>";
echo "<p><a href='index.php' style='background: #607D8B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a></p>";

echo "</div>";
?>
