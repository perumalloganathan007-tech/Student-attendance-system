<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Take Attendance Functionality Test</h2>";

// Start session
session_start();

// Include database connection
include '../Includes/dbcon.php';

// Test database connection
if ($conn->connect_error) {
    echo "<div style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</div>";
    exit;
} else {
    echo "<div style='color: green;'>✅ Database connection successful</div>";
}

// Test session variables
echo "<h3>Session Variables Check:</h3>";
echo "<ul>";
echo "<li>User ID: " . ($_SESSION['userId'] ?? '<span style="color:red;">Not set</span>') . "</li>";
echo "<li>User Type: " . ($_SESSION['userType'] ?? '<span style="color:red;">Not set</span>') . "</li>";
echo "<li>Subject ID: " . ($_SESSION['subjectId'] ?? '<span style="color:red;">Not set</span>') . "</li>";
echo "<li>Subject Name: " . ($_SESSION['subjectName'] ?? '<span style="color:red;">Not set</span>') . "</li>";
echo "<li>Subject Code: " . ($_SESSION['subjectCode'] ?? '<span style="color:red;">Not set</span>') . "</li>";
echo "</ul>";

// Test if we have a valid subject teacher logged in
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'SubjectTeacher') {
    echo "<div style='color: orange;'>⚠️ You need to log in as a Subject Teacher first</div>";
    echo "<p><a href='../subjectTeacherLogin.php'>Login here</a></p>";
    exit;
}

// Test subject teacher table
echo "<h3>Subject Teacher Information:</h3>";
$query = "SELECT st.Id, st.emailAddress, s.subjectName, s.subjectCode 
          FROM tblsubjectteacher st 
          INNER JOIN tblsubjects s ON s.Id = st.subjectId 
          WHERE st.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    echo "<div style='color: green;'>✅ Subject Teacher found:</div>";
    echo "<ul>";
    echo "<li>Teacher ID: " . $teacher['Id'] . "</li>";
    echo "<li>Email: " . $teacher['emailAddress'] . "</li>";
    echo "<li>Subject: " . $teacher['subjectName'] . " (" . $teacher['subjectCode'] . ")</li>";
    echo "</ul>";
} else {
    echo "<div style='color: red;'>❌ Subject Teacher not found in database</div>";
}

// Test students assigned to this teacher
echo "<h3>Students Assigned:</h3>";
$studentQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$studentCount = $result->fetch_assoc()['count'];

echo "<div>Number of students assigned: <strong>$studentCount</strong></div>";

if ($studentCount > 0) {
    echo "<div style='color: green;'>✅ Students are assigned to this teacher</div>";
} else {
    echo "<div style='color: orange;'>⚠️ No students assigned to this teacher</div>";
}

// Test attendance table structure
echo "<h3>Attendance Table Structure:</h3>";
$columnsQuery = "SHOW COLUMNS FROM tblsubjectattendance";
$result = $conn->query($columnsQuery);

if ($result) {
    echo "<div style='color: green;'>✅ Attendance table accessible</div>";
    echo "<table border='1' style='margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($column = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: red;'>❌ Cannot access attendance table: " . $conn->error . "</div>";
}

// Test today's attendance status
echo "<h3>Today's Attendance Status:</h3>";
$today = date("Y-m-d");
$checkQuery = "SELECT COUNT(*) as count FROM tblsubjectattendance WHERE subjectTeacherId = ? AND date = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $_SESSION['userId'], $today);
$stmt->execute();
$result = $stmt->get_result();
$todayCount = $result->fetch_assoc()['count'];

if ($todayCount > 0) {
    echo "<div style='color: blue;'>ℹ️ Attendance already taken today ($todayCount records)</div>";
} else {
    echo "<div style='color: orange;'>⚠️ Attendance not yet taken today</div>";
}

// Test session term
echo "<h3>Session Term Check:</h3>";
$sessionQuery = "SELECT Id, sessionName, termName, isActive FROM tblsessionterm WHERE isActive = 1";
$result = $conn->query($sessionQuery);

if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    echo "<div style='color: green;'>✅ Active session found:</div>";
    echo "<ul>";
    echo "<li>Session: " . $session['sessionName'] . "</li>";
    echo "<li>Term: " . $session['termName'] . "</li>";
    echo "<li>ID: " . $session['Id'] . "</li>";
    echo "</ul>";
} else {
    echo "<div style='color: red;'>❌ No active session term found</div>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='takeAttendance.php' target='_blank'>Test Take Attendance Page</a></li>";
echo "<li><a href='index.php'>Return to Dashboard</a></li>";
echo "<li><a href='viewStudents.php'>View All Students</a></li>";
echo "</ul>";
?>
