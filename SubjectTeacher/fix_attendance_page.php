<?php
// Auto-Redirect Script - Try all possible versions of viewTodayAttendance.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Attendance Page Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 5px;
            padding: 20px;
        }
        h1 {
            color: #4e73df;
            margin-top: 0;
        }
        .options {
            margin: 20px 0;
        }
        .option {
            margin: 15px 0;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .path {
            font-family: monospace;
            background: #f8f9fa;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .button:hover {
            background: #2e59d9;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Today's Attendance Page Fix</h1>
        <p>This script will help you access the Today's Attendance page by trying different approaches.</p>";

// Check if we have a valid session
session_start();
$has_valid_session = isset($_SESSION['userId']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'SubjectTeacher';

if (!$has_valid_session) {
    echo "<div class='warning'>
            <strong>Warning:</strong> You don't appear to have a valid session. This might cause authentication issues.
            <br><br>
            <a href='fix_session.php' class='button'>Fix Session Issues</a>
          </div>";
}

// Define possible paths to check
$possible_paths = [
    // Current directory with exact case
    'viewTodayAttendance.php',
    // Current directory with lowercase
    'viewtodayattendance.php',
    // With capital first letters
    'ViewTodayAttendance.php',
    // Without the "s" in attendance
    'viewTodayAttendance.php',
    // Absolute URL
    '/tax/Student-Attendance-System/SubjectTeacher/viewTodayAttendance.php',
    // URL with lowercase
    '/tax/Student-Attendance-System/SubjectTeacher/viewtodayattendance.php',
];

// Check if we should try a path
if (isset($_GET['try_path'])) {
    $path_index = (int)$_GET['try_path'];
    if (isset($possible_paths[$path_index])) {
        $path = $possible_paths[$path_index];
        header("Location: $path");
        exit;
    }
}

// Display options
echo "<div class='options'>";
echo "<h2>Options to Try:</h2>";

foreach ($possible_paths as $index => $path) {
    echo "<div class='option'>
            <h3>Option " . ($index + 1) . ":</h3>
            <p>Try accessing: <span class='path'>" . htmlspecialchars($path) . "</span></p>
            <a href='?try_path=$index' class='button'>Try This Path</a>
          </div>";
}

// Add manual file check option
echo "<div class='option'>
        <h3>File System Check:</h3>
        <p>Check if the file exists in the file system with the correct case.</p>
        <a href='file_check.php' class='button'>Check Files</a>
      </div>";

// Add direct access test option
echo "<div class='option'>
        <h3>Direct Access Test:</h3>
        <p>Try to directly include the viewTodayAttendance.php file.</p>
        <a href='direct_access_test.php' class='button'>Direct Access Test</a>
      </div>";

echo "</div>
    </div>
</body>
</html>";
