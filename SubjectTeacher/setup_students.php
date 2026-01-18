<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Setup Students for Take Attendance Test</h2>";

// Include database connection
include '../Includes/dbcon.php';

// Test database connection
if ($conn->connect_error) {
    echo "<div style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</div>";
    exit;
}

// Check if we have our test teacher
$teacherQuery = "SELECT Id, emailAddress FROM tblsubjectteacher WHERE emailAddress = 'john.smith@school.com'";
$result = $conn->query($teacherQuery);

if ($result->num_rows === 0) {
    echo "<div style='color: red;'>❌ Test teacher (john.smith@school.com) not found</div>";
    echo "<p>Please run the complete_test.php script first to create the test teacher.</p>";
    exit;
}

$teacher = $result->fetch_assoc();
$teacherId = $teacher['Id'];
echo "<div style='color: green;'>✅ Found test teacher (ID: $teacherId)</div>";

// Check if we have students in the system
$studentQuery = "SELECT COUNT(*) as count FROM tblstudents";
$result = $conn->query($studentQuery);
$studentCount = $result->fetch_assoc()['count'];

echo "<p>Total students in system: <strong>$studentCount</strong></p>";

if ($studentCount === 0) {
    echo "<h3>Creating Test Students...</h3>";
    
    // Get a class and class arm
    $classQuery = "SELECT Id FROM tblclass LIMIT 1";
    $result = $conn->query($classQuery);
    if ($result->num_rows === 0) {
        echo "<div style='color: red;'>❌ No classes found. Please create classes first.</div>";
        exit;
    }
    $classId = $result->fetch_assoc()['Id'];
    
    $classArmQuery = "SELECT Id FROM tblclassarms WHERE classId = ? LIMIT 1";
    $stmt = $conn->prepare($classArmQuery);
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<div style='color: red;'>❌ No class arms found. Please create class arms first.</div>";
        exit;
    }
    $classArmId = $result->fetch_assoc()['Id'];
    
    // Create test students
    $students = [
        ['Alice', 'Johnson', '001'],
        ['Bob', 'Smith', '002'],
        ['Carol', 'Brown', '003'],
        ['David', 'Wilson', '004'],
        ['Emma', 'Davis', '005']
    ];
    
    $insertStudentQuery = "INSERT INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId, dateCreated) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertStudentQuery);
    
    foreach ($students as $student) {
        $stmt->bind_param("sssii", $student[0], $student[1], $student[2], $classId, $classArmId);
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✅ Created student: {$student[0]} {$student[1]} ({$student[2]})</div>";
        } else {
            echo "<div style='color: red;'>❌ Failed to create student: {$student[0]} {$student[1]}</div>";
        }
    }
}

// Get available students
$availableStudentsQuery = "SELECT Id, firstName, lastName, admissionNumber FROM tblstudents LIMIT 10";
$result = $conn->query($availableStudentsQuery);

echo "<h3>Available Students:</h3>";
$availableStudents = [];
if ($result->num_rows > 0) {
    echo "<ul>";
    while ($student = $result->fetch_assoc()) {
        $availableStudents[] = $student['Id'];
        echo "<li>ID: {$student['Id']} - {$student['firstName']} {$student['lastName']} ({$student['admissionNumber']})</li>";
    }
    echo "</ul>";
} else {
    echo "<div style='color: red;'>❌ No students available</div>";
    exit;
}

// Check current assignments for this teacher
$assignedQuery = "SELECT studentId FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
$stmt = $conn->prepare($assignedQuery);
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$result = $stmt->get_result();

$currentAssignments = [];
while ($row = $result->fetch_assoc()) {
    $currentAssignments[] = $row['studentId'];
}

echo "<h3>Currently Assigned Students:</h3>";
if (count($currentAssignments) > 0) {
    echo "<p>Teacher has " . count($currentAssignments) . " students assigned</p>";
    foreach ($currentAssignments as $studentId) {
        echo "<div>Student ID: $studentId</div>";
    }
} else {
    echo "<p>No students currently assigned</p>";
    
    // Assign first 5 available students to the teacher
    echo "<h3>Assigning Students to Teacher...</h3>";
    $assignQuery = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
    $stmt = $conn->prepare($assignQuery);
    
    $studentsToAssign = array_slice($availableStudents, 0, 5);
    foreach ($studentsToAssign as $studentId) {
        $stmt->bind_param("ii", $teacherId, $studentId);
        if ($stmt->execute()) {
            echo "<div style='color: green;'>✅ Assigned student ID: $studentId to teacher</div>";
        } else {
            echo "<div style='color: red;'>❌ Failed to assign student ID: $studentId</div>";
        }
    }
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='test_take_attendance.php'>Run Take Attendance Test</a></li>";
echo "<li><a href='takeAttendance.php'>Go to Take Attendance Page</a></li>";
echo "<li><a href='../subjectTeacherLogin.php'>Login as john.smith@school.com / password123</a></li>";
echo "</ul>";
?>
