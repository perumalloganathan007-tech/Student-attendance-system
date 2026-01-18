<?php
// Test script to check if viewTodayAttendance.php is accessible

echo "<h1>Testing View Today's Attendance Page</h1>";
echo "<p>This script will test if the viewTodayAttendance.php page is accessible.</p>";

// Include necessary files for testing
include_once('../Includes/dbcon.php');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if session is active
echo "<h2>Session Status</h2>";
if (isset($_SESSION['userId']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'SubjectTeacher') {
    echo "<p class='text-success'>✓ Active session found for Subject Teacher: " . $_SESSION['firstName'] . " " . $_SESSION['lastName'] . "</p>";
} else {
    echo "<p class='text-danger'>✗ No active Subject Teacher session found</p>";
    echo "<p>Please log in as a Subject Teacher first.</p>";
}

// Check file existence
echo "<h2>File Existence</h2>";
$viewTodayFile = __DIR__ . '/viewTodayAttendance.php';
if (file_exists($viewTodayFile)) {
    echo "<p class='text-success'>✓ viewTodayAttendance.php exists at the expected path</p>";
} else {
    echo "<p class='text-danger'>✗ viewTodayAttendance.php does not exist at the expected path</p>";
    
    // Look for similar files
    $files = scandir(__DIR__);
    $similar_files = [];
    foreach ($files as $file) {
        if (stripos($file, 'attendance') !== false) {
            $similar_files[] = $file;
        }
    }
    
    if (!empty($similar_files)) {
        echo "<p>Found similar files:</p><ul>";
        foreach ($similar_files as $file) {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
        echo "</ul>";
    }
}

// Test .htaccess configuration
echo "<h2>URL Rewriting</h2>";
echo "<p>Testing if .htaccess is properly configured...</p>";

$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "<p class='text-success'>✓ .htaccess file exists</p>";
    $content = file_get_contents($htaccess);
    echo "<p>Content:</p><pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p class='text-danger'>✗ No .htaccess file found</p>";
}

// Check for 404.php
echo "<h2>Error Page</h2>";
$error_page = __DIR__ . '/404.php';
if (file_exists($error_page)) {
    echo "<p class='text-success'>✓ Custom 404 error page exists</p>";
} else {
    echo "<p class='text-danger'>✗ No custom 404 error page found</p>";
}

// Add a direct link
echo "<h2>Try Direct Access</h2>";
echo "<p>Click the link below to try accessing the page directly:</p>";
echo "<p><a href='viewTodayAttendance.php' target='_blank'>Open viewTodayAttendance.php</a></p>";

// Also try including the file
echo "<h2>Try Including File</h2>";
echo "<p>Attempting to include the file to see if there are any PHP errors:</p>";

try {
    ob_start();
    @include('viewTodayAttendance.php');
    $output = ob_get_clean();
    
    echo "<p class='text-success'>✓ File included without errors</p>";
    echo "<p>First 200 characters of output:</p>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 200)) . "...</pre>";
} catch (Exception $e) {
    $output = ob_get_clean();
    echo "<p class='text-danger'>✗ Error including file: " . htmlspecialchars($e->getMessage()) . "</p>";
    if (!empty($output)) {
        echo "<p>Output before error:</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
}

// Summary
echo "<h2>Summary</h2>";
echo "<p>If you see any errors above, they may indicate issues that need to be fixed.</p>";
echo "<p>If everything looks good, both the viewTodayAttendance.php and attendanceAnalytics.php pages should now be working correctly.</p>";
?>
