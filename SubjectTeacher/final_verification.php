<?php
// Final verification and testing script
session_start();
require_once('../Includes/dbcon.php');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Subject Teacher Module - Final Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .container { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin: 5px; display: inline-block; }
        .btn-success { background: #28a745; }
        .btn-info { background: #17a2b8; }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>";

echo "<h1>Subject Teacher Module - Final Verification</h1>";

// Check 1: Database Tables
echo "<div class='container'>";
echo "<h2>1. Database Table Structure</h2>";

$tables = [
    'tblsubjectteacher' => 'Subject Teachers',
    'tblsubjectteacher_student' => 'Teacher-Student Assignments',
    'tblsubjectattendance' => 'Attendance Records',
    'tblstudents' => 'Students',
    'tblsubjects' => 'Subjects',
    'tblclass' => 'Classes'
];

$allTablesExist = true;
foreach ($tables as $tableName => $description) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows > 0) {
        $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
        $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
        echo "<p class='success'>✅ $description ($tableName): $count records</p>";
    } else {
        echo "<p class='error'>❌ $description ($tableName): Table missing</p>";
        $allTablesExist = false;
    }
}

// Check for old incorrect table
$oldTable = $conn->query("SHOW TABLES LIKE 'tblsubjectteachers'");
if ($oldTable->num_rows > 0) {
    echo "<p class='warning'>⚠️ Old table 'tblsubjectteachers' still exists (should be renamed)</p>";
}

echo "</div>";

// Check 2: Test User
echo "<div class='container'>";
echo "<h2>2. Test User Verification</h2>";

$testEmail = 'john.smith@school.com';
$userQuery = "SELECT * FROM tblsubjectteacher WHERE emailAddress = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    echo "<p class='success'>✅ Test user exists: {$user['firstName']} {$user['lastName']} (ID: {$user['Id']})</p>";
    
    // Check subject assignment
    if (!empty($user['subjectId'])) {
        $subjectQuery = "SELECT subjectName FROM tblsubjects WHERE Id = ?";
        $subjectStmt = $conn->prepare($subjectQuery);
        $subjectStmt->bind_param("i", $user['subjectId']);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result();
        
        if ($subjectResult->num_rows > 0) {
            $subject = $subjectResult->fetch_assoc();
            echo "<p class='success'>✅ Subject assigned: {$subject['subjectName']}</p>";
        } else {
            echo "<p class='warning'>⚠️ Subject ID {$user['subjectId']} not found in subjects table</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ No subject assigned to test user</p>";
    }
    
    // Check student assignments
    $studentCountQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
    $studentStmt = $conn->prepare($studentCountQuery);
    $studentStmt->bind_param("i", $user['Id']);
    $studentStmt->execute();
    $studentCountResult = $studentStmt->get_result();
    $studentCount = $studentCountResult->fetch_assoc()['count'];
    
    echo "<p class='success'>✅ Students assigned to teacher: $studentCount</p>";
    
} else {
    echo "<p class='error'>❌ Test user not found</p>";
}

echo "</div>";

// Check 3: File Status
echo "<div class='container'>";
echo "<h2>3. Critical Files Status</h2>";

$criticalFiles = [
    '../subjectTeacherLogin.php' => 'Login Page',
    'index.php' => 'Dashboard',
    'takeAttendance.php' => 'Take Attendance',
    'viewStudents.php' => 'View Students',
    'Includes/init_session.php' => 'Session Initialization'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $description ($file)</p>";
        
        // Check for table name issues
        $content = file_get_contents($file);
        $badRefs = substr_count($content, 'tblsubjectteachers');
        if ($badRefs > 0) {
            echo "<p class='warning' style='margin-left: 20px;'>⚠️ Contains $badRefs references to incorrect table name</p>";
        }
    } else {
        echo "<p class='error'>❌ $description ($file) - File not found</p>";
    }
}

echo "</div>";

// Check 4: Functionality Tests
echo "<div class='container'>";
echo "<h2>4. Ready for Testing</h2>";

if ($allTablesExist && $userResult->num_rows > 0) {
    echo "<p class='success'>✅ System is ready for testing!</p>";
    
    echo "<h3>Test the following functionality:</h3>";
    echo "<p><a href='../subjectTeacherLogin.php' class='btn' target='_blank'>1. Login Test</a> Use: john.smith@school.com / password123</p>";
    echo "<p><a href='index.php' class='btn btn-secondary' target='_blank'>2. Dashboard Access</a> (Login first)</p>";
    echo "<p><a href='takeAttendance.php' class='btn btn-success' target='_blank'>3. Take Attendance</a> (Login first)</p>";
    echo "<p><a href='viewStudents.php' class='btn btn-info' target='_blank'>4. View Students</a> (Login first)</p>";
    
    echo "<h3>If you encounter issues:</h3>";
    echo "<p><a href='debug_session.php' class='btn btn-secondary' target='_blank'>Session Debug Tool</a></p>";
    echo "<p><a href='fix_session.php' class='btn btn-secondary' target='_blank'>Session Repair Tool</a></p>";
    
} else {
    echo "<p class='error'>❌ System not ready - please fix the issues above first</p>";
}

echo "</div>";

// Check 5: Recent File Changes Summary
echo "<div class='container'>";
echo "<h2>5. Summary of Fixes Applied</h2>";
echo "<ul>";
echo "<li>✅ Fixed table name 'tblsubjectteachers' → 'tblsubjectteacher' in critical files</li>";
echo "<li>✅ Enhanced session initialization with automatic subject information fetching</li>";
echo "<li>✅ Improved error handling in takeAttendance.php and viewStudents.php</li>";
echo "<li>✅ Created comprehensive debugging and repair tools</li>";
echo "<li>✅ Ensured test user exists with proper credentials</li>";
echo "<li>✅ Set up student-teacher assignments for testing</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
?>
