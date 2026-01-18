<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';

// Start session
session_start();

echo "<h1>Take Attendance Debug</h1>";

echo "<h2>1. Session Status</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

$sessionVars = ['userId', 'firstName', 'lastName', 'user_type', 'userType', 'subjectId', 'subjectName', 'subjectCode'];
foreach ($sessionVars as $var) {
    $value = isset($_SESSION[$var]) ? $_SESSION[$var] : 'NOT SET';
    $color = $value === 'NOT SET' ? 'red' : 'green';
    echo "<tr><td>$var</td><td style='color: $color;'>$value</td></tr>";
}
echo "</table>";

echo "<h2>2. Database Connection Test</h2>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

echo "<h2>3. User Authentication Test</h2>";
if (!isset($_SESSION['userId'])) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    echo "<p><a href='../subjectTeacherLogin.php'>Go to Login</a></p>";
    exit;
} else {
    echo "<p style='color: green;'>✅ User logged in with ID: " . $_SESSION['userId'] . "</p>";
}

echo "<h2>4. Subject Teacher Information Test</h2>";
$userId = $_SESSION['userId'];

// Check if user exists in tblsubjectteacher
$checkUser = "SELECT * FROM tblsubjectteacher WHERE Id = ?";
$stmt = $conn->prepare($checkUser);
$stmt->bind_param("i", $userId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows == 0) {
    echo "<p style='color: red;'>❌ User ID $userId not found in tblsubjectteacher table</p>";
} else {
    $user = $userResult->fetch_assoc();
    echo "<p style='color: green;'>✅ User found: {$user['firstName']} {$user['lastName']}</p>";
    echo "<p>Subject ID: " . ($user['subjectId'] ?? 'NULL') . "</p>";
}

echo "<h2>5. Subject Information Test</h2>";
$subjectQuery = "SELECT 
    s.Id as subjectId,
    s.subjectName,
    s.subjectCode,
    st.Id as teacherId
FROM tblsubjectteacher st
INNER JOIN tblsubjects s ON s.Id = st.subjectId
WHERE st.Id = ?";

$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$subjectResult = $stmt->get_result();

if ($subjectResult->num_rows == 0) {
    echo "<p style='color: red;'>❌ No subject information found for teacher ID $userId</p>";
    echo "<p>This could be because:</p>";
    echo "<ul>";
    echo "<li>Teacher has no subject assigned (subjectId is NULL)</li>";
    echo "<li>Subject ID doesn't exist in tblsubjects table</li>";
    echo "</ul>";
    
    // Check what subjects are available
    $allSubjects = $conn->query("SELECT * FROM tblsubjects LIMIT 5");
    if ($allSubjects && $allSubjects->num_rows > 0) {
        echo "<h3>Available Subjects:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Subject Name</th><th>Subject Code</th></tr>";
        while ($subject = $allSubjects->fetch_assoc()) {
            echo "<tr><td>{$subject['Id']}</td><td>{$subject['subjectName']}</td><td>{$subject['subjectCode']}</td></tr>";
        }
        echo "</table>";
    }
    
} else {
    $subjectInfo = $subjectResult->fetch_assoc();
    echo "<p style='color: green;'>✅ Subject information found</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Subject ID</td><td>{$subjectInfo['subjectId']}</td></tr>";
    echo "<tr><td>Subject Name</td><td>{$subjectInfo['subjectName']}</td></tr>";
    echo "<tr><td>Subject Code</td><td>{$subjectInfo['subjectCode']}</td></tr>";
    echo "</table>";
}

echo "<h2>6. Student Assignment Test</h2>";
$studentQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();
$studentCount = $studentResult->fetch_assoc()['count'];

if ($studentCount == 0) {
    echo "<p style='color: orange;'>⚠️ No students assigned to this teacher</p>";
    echo "<p><a href='assignStudents.php'>Assign Students</a> or <a href='standalone_fix.php'>Run Database Fix</a></p>";
} else {
    echo "<p style='color: green;'>✅ $studentCount students assigned to this teacher</p>";
}

echo "<h2>7. Table Structure Check</h2>";
$tables = ['tblsubjectteacher', 'tblsubjectteacher_student', 'tblsubjectattendance', 'tblsessionterm'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
    }
}

echo "<h2>8. Fix Actions</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='fix_session.php' style='background: #007cba; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Fix Session</a> ";
echo "<a href='standalone_fix.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Fix Database</a> ";
echo "<a href='complete_test.php' style='background: #17a2b8; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Complete Test</a> ";
echo "</div>";

echo "<h2>9. Try Take Attendance</h2>";
echo "<p><a href='takeAttendance.php' style='background: #dc3545; color: white; padding: 10px; text-decoration: none;'>Take Attendance</a></p>";
?>
