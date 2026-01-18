<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once('../Includes/dbcon.php');

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Subject Teacher Module - Current Status Check</h2>";

// Check table existence
echo "<h3>1. Table Structure Check</h3>";
$tables = ['tblsubjectteacher', 'tblsubjectteacher_student', 'tblsubjectattendance', 'tblstudents', 'tblsubjects'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        
        // Count records
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `$table`");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
            echo "<p style='margin-left: 20px;'>Records: $count</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table '$table' does not exist</p>";
    }
}

// Check for old incorrect table
$oldTableResult = $conn->query("SHOW TABLES LIKE 'tblsubjectteachers'");
if ($oldTableResult->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ Old table 'tblsubjectteachers' (plural) still exists - this should be renamed</p>";
} else {
    echo "<p style='color: green;'>✅ Old incorrect table 'tblsubjectteachers' not found (good)</p>";
}

// Check sample data
echo "<h3>2. Sample Data Check</h3>";

// Check for subject teachers
$teacherQuery = "SELECT Id, firstName, lastName, emailAddress, subjectId FROM tblsubjectteacher LIMIT 3";
$teacherResult = $conn->query($teacherQuery);

if ($teacherResult && $teacherResult->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found subject teachers:</p>";
    echo "<ul>";
    while ($teacher = $teacherResult->fetch_assoc()) {
        echo "<li>ID: {$teacher['Id']}, Name: {$teacher['firstName']} {$teacher['lastName']}, Email: {$teacher['emailAddress']}, Subject ID: {$teacher['subjectId']}</li>";
    }
    echo "</ul>";
    
    // Check password format for first teacher
    $passwordCheck = $conn->query("SELECT password FROM tblsubjectteacher LIMIT 1");
    if ($passwordCheck && $passwordCheck->num_rows > 0) {
        $passwordData = $passwordCheck->fetch_assoc();
        $password = $passwordData['password'];
        if (strlen($password) >= 60 && (strpos($password, '$2y$') === 0 || strpos($password, '$2b$') === 0)) {
            echo "<p style='color: green;'>✅ Passwords are properly hashed</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Passwords may not be properly hashed (length: " . strlen($password) . ")</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ No subject teachers found</p>";
}

// Check for students
$studentQuery = "SELECT COUNT(*) as count FROM tblstudents";
$studentResult = $conn->query($studentQuery);
if ($studentResult) {
    $studentCount = $studentResult->fetch_assoc()['count'];
    echo "<p style='color: green;'>✅ Found $studentCount students in database</p>";
}

// Check for subjects
$subjectQuery = "SELECT COUNT(*) as count FROM tblsubjects";
$subjectResult = $conn->query($subjectQuery);
if ($subjectResult) {
    $subjectCount = $subjectResult->fetch_assoc()['count'];
    echo "<p style='color: green;'>✅ Found $subjectCount subjects in database</p>";
}

echo "<h3>3. Key File Status</h3>";
$keyFiles = [
    'subjectTeacherLogin.php' => '../subjectTeacherLogin.php',
    'takeAttendance.php' => 'takeAttendance.php',
    'viewStudents.php' => 'viewStudents.php',
    'init_session.php' => 'Includes/init_session.php'
];

foreach ($keyFiles as $fileName => $filePath) {
    if (file_exists($filePath)) {
        echo "<p style='color: green;'>✅ File '$fileName' exists</p>";
        
        // Check for incorrect table references
        $content = file_get_contents($filePath);
        $incorrectRefs = substr_count($content, 'tblsubjectteachers');
        if ($incorrectRefs > 0) {
            echo "<p style='color: orange; margin-left: 20px;'>⚠️ Still contains $incorrectRefs references to 'tblsubjectteachers'</p>";
        } else {
            echo "<p style='color: green; margin-left: 20px;'>✅ No incorrect table references found</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ File '$fileName' not found at '$filePath'</p>";
    }
}

echo "<h3>4. Recommended Actions</h3>";
echo "<ol>";
echo "<li><a href='../subjectTeacherLogin.php' target='_blank'>Test Login Page</a></li>";
echo "<li><a href='debug_session.php' target='_blank'>Check Session Debug Tool</a></li>";
echo "<li>After logging in, test 'Take Attendance' and 'View Students' features</li>";
echo "</ol>";

echo "<p><strong>Status Summary:</strong> Database structure appears to be ready for testing. Please proceed with functional testing.</p>";
echo "</div>";
?>
