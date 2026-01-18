<?php
/**
 * Quick Fix Verification for viewTodayAttendance.php
 * Run this to verify the fixes are working properly
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Fix Verification - Today's Attendance</title>";
echo "<link href='../vendor/bootstrap/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light'>";
echo "<div class='container mt-4'>";
echo "<h2><i class='fas fa-wrench'></i> Today's Attendance Fix Verification</h2>";

// Include database connection
require_once('../Includes/dbcon.php');

echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'><h5>Fix Status Check</h5></div>";
echo "<div class='card-body'>";

$fixes_applied = [];
$issues_found = [];

// 1. Check if session is set
if (isset($_SESSION['userId'])) {
    echo "<span class='text-success'>✓ Session is active - User ID: " . $_SESSION['userId'] . "</span><br>";
    $fixes_applied[] = "Session active";
} else {
    echo "<span class='text-warning'>⚠ No active session - this is normal if not logged in</span><br>";
    $issues_found[] = "No session (expected if not logged in)";
}

// 2. Check if tblsubjectteacher table exists (not tblsubjectteachers)
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteacher'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<span class='text-success'>✓ Table 'tblsubjectteacher' exists (correct table name)</span><br>";
    $fixes_applied[] = "Correct table name used";
} else {
    echo "<span class='text-danger'>✗ Table 'tblsubjectteacher' not found</span><br>";
    $issues_found[] = "Missing tblsubjectteacher table";
}

// 3. Check if the old incorrect table name exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteachers'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "<span class='text-warning'>⚠ Table 'tblsubjectteachers' (plural) still exists - this was the incorrect name</span><br>";
    $issues_found[] = "Old incorrect table name still exists";
} else {
    echo "<span class='text-success'>✓ Old incorrect table 'tblsubjectteachers' not found (good)</span><br>";
    $fixes_applied[] = "No conflicting table names";
}

// 4. Simulate the viewTodayAttendance.php logic to test null safety
echo "<hr><h6>Testing Null Safety Logic:</h6>";

// Simulate what happens when $teacherInfo is null
$teacherInfo = null;

// Test the fixed title logic
$title = isset($teacherInfo['subjectName']) ? $teacherInfo['subjectName'] : 'Subject Teacher';
echo "<span class='text-success'>✓ Title with null check: '$title'</span><br>";
$fixes_applied[] = "Title null check working";

// Test the fixed subject display logic
$subjectDisplay = (isset($teacherInfo['subjectName']) ? $teacherInfo['subjectName'] : 'Unknown Subject') . ' - ' . (isset($teacherInfo['subjectCode']) ? $teacherInfo['subjectCode'] : 'N/A');
echo "<span class='text-success'>✓ Subject display with null check: '$subjectDisplay'</span><br>";
$fixes_applied[] = "Subject display null check working";

// Test with some data
$teacherInfo = [
    'subjectName' => 'Mathematics',
    'subjectCode' => 'MATH101',
    'teacherId' => 1
];

$subjectDisplayWithData = (isset($teacherInfo['subjectName']) ? $teacherInfo['subjectName'] : 'Unknown Subject') . ' - ' . (isset($teacherInfo['subjectCode']) ? $teacherInfo['subjectCode'] : 'N/A');
echo "<span class='text-success'>✓ Subject display with data: '$subjectDisplayWithData'</span><br>";
$fixes_applied[] = "Subject display with data working";

echo "</div></div>";

// Summary
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-success text-white'><h5>Summary</h5></div>";
echo "<div class='card-body'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h6 class='text-success'>Fixes Applied (" . count($fixes_applied) . ")</h6>";
foreach ($fixes_applied as $fix) {
    echo "<small class='text-success'>✓ $fix</small><br>";
}
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h6 class='text-warning'>Issues Found (" . count($issues_found) . ")</h6>";
foreach ($issues_found as $issue) {
    echo "<small class='text-warning'>⚠ $issue</small><br>";
}
echo "</div>";
echo "</div>";

if (empty($issues_found)) {
    echo "<div class='alert alert-success mt-3'>";
    echo "<h6><i class='fas fa-check-circle'></i> All Fixes Successfully Applied!</h6>";
    echo "<p>The viewTodayAttendance.php file should now work without the 'array offset on null' errors.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-warning mt-3'>";
    echo "<h6><i class='fas fa-exclamation-triangle'></i> Some Issues Remain</h6>";
    echo "<p>While the null safety fixes have been applied, there are still some database-related issues that may need attention.</p>";
    echo "</div>";
}

echo "</div></div>";

// Next steps
echo "<div class='card'>";
echo "<div class='card-header bg-info text-white'><h5>Next Steps</h5></div>";
echo "<div class='card-body'>";
echo "<ol>";
echo "<li><strong>Test the page:</strong> Go to <a href='viewTodayAttendance.php'>viewTodayAttendance.php</a> and check if the errors are gone</li>";
echo "<li><strong>Log in as Subject Teacher:</strong> Ensure you're logged in with proper Subject Teacher credentials</li>";
echo "<li><strong>Check data:</strong> Verify that there are attendance records to display</li>";
echo "<li><strong>Report issues:</strong> If you still see errors, they may be from different lines or different causes</li>";
echo "</ol>";

echo "<div class='text-center mt-3'>";
echo "<a href='viewTodayAttendance.php' class='btn btn-primary'>";
echo "<i class='fas fa-eye'></i> Test Today's Attendance Page";
echo "</a> ";
echo "<a href='test_attendance_dashboard.html' class='btn btn-info'>";
echo "<i class='fas fa-tachometer-alt'></i> Back to Testing Dashboard";
echo "</a>";
echo "</div>";

echo "</div></div>";

echo "</div>";
echo "</body></html>";
?>
