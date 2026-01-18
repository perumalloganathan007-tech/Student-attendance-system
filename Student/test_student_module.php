<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Student Module Test Page</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

// Check required tables
echo "<h3>2. Table Structure Check</h3>";
$requiredTables = [
    'tblstudents' => 'Student information',
    'tblclass' => 'Class information',
    'tblclassarms' => 'Class arms',
    'tblsubjects' => 'Subject information',
    'tblsubjectattendance' => 'Attendance records',
    'tblsubjectteacher_student' => 'Student-subject assignments'
];

foreach ($requiredTables as $table => $description) {
    $query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists ($description)</p>";
        
        // Check record count
        $countQuery = "SELECT COUNT(*) as count FROM $table";
        $countResult = $conn->query($countQuery);
        $count = $countResult->fetch_assoc()['count'];
        echo "<p style='margin-left: 20px;'>Records in table: $count</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing table: $table ($description)</p>";
    }
}

// Test sample student login
echo "<h3>3. Student Authentication Test</h3>";
$testQuery = "SELECT * FROM tblstudents ORDER BY Id LIMIT 1";
$testResult = $conn->query($testQuery);

if ($testResult && $testResult->num_rows > 0) {
    $student = $testResult->fetch_assoc();
    echo "<p style='color: green;'>✅ Found test student:</p>";
    echo "<ul>";
    echo "<li>ID: " . $student['Id'] . "</li>";
    echo "<li>Name: " . $student['firstName'] . " " . $student['lastName'] . "</li>";
    echo "<li>Admission Number: " . $student['admissionNumber'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ No test student found in database</p>";
}

// Check student module files
echo "<h3>4. File Structure Check</h3>";
$requiredFiles = [
    'index.php' => 'Dashboard',
    'viewAttendance.php' => 'View attendance records',
    'profile.php' => 'Student profile',
    'changePassword.php' => 'Change password',
    'downloadRecord.php' => 'Download attendance report',
    'Includes/header.php' => 'Common header',
    'Includes/footer.php' => 'Common footer',
    'Includes/sidebar.php' => 'Navigation menu'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✅ File '$file' exists ($description)</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing file: $file ($description)</p>";
    }
}

// Test student attendance records
echo "<h3>5. Attendance Records Check</h3>";
if (isset($student)) {
    $attendanceQuery = "SELECT COUNT(*) as count FROM tblsubjectattendance WHERE studentId = ?";
    $stmt = $conn->prepare($attendanceQuery);
    $stmt->bind_param("i", $student['Id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendanceCount = $result->fetch_assoc()['count'];
    
    if ($attendanceCount > 0) {
        echo "<p style='color: green;'>✅ Found $attendanceCount attendance records for test student</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No attendance records found for test student</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Cannot check attendance records (no test student)</p>";
}

echo "<h3>Quick Links</h3>";
echo "<p><a href='index.php' style='color: blue;'>Student Dashboard</a></p>";
echo "<p><a href='../index.php' style='color: blue;'>Main Login Page</a></p>";

echo "</div>";
?>
