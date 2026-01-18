<?php
// Direct Access Test for viewTodayAttendance.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Include necessary files
include '../Includes/dbcon.php';

echo "<h1>Direct Access Test</h1>";
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if the viewTodayAttendance.php file exists
$file_path = __DIR__ . '/viewTodayAttendance.php';
echo "<h2>File Check:</h2>";
echo "Looking for file: " . $file_path . "<br>";
echo "File exists: " . (file_exists($file_path) ? 'Yes' : 'No') . "<br>";

if (file_exists($file_path)) {
    echo "<h2>Attempting to include file:</h2>";
    echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0; background-color: #f9f9f9;'>";
    
    // Set a basic session if needed
    if (!isset($_SESSION['user_type'])) {
        $_SESSION['user_type'] = 'SubjectTeacher';
        echo "<p>Setting user_type session variable to 'SubjectTeacher'</p>";
    }
    
    if (!isset($_SESSION['userId'])) {
        // Try to get a valid user ID from the database
        $query = "SELECT Id FROM tblsubjectteachers LIMIT 1";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['userId'] = $row['Id'];
            echo "<p>Setting userId session variable to " . $row['Id'] . "</p>";
        } else {
            $_SESSION['userId'] = 1;
            echo "<p>Setting userId session variable to 1 (default)</p>";
        }
    }
    
    // Add basic required session variables
    if (!isset($_SESSION['subjectId'])) {
        // Try to get subject ID from database
        $query = "SELECT subjectId FROM tblsubjectteachers WHERE Id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['subjectId'] = $row['subjectId'];
            echo "<p>Setting subjectId session variable to " . $row['subjectId'] . "</p>";
        } else {
            $_SESSION['subjectId'] = 1;
            echo "<p>Setting subjectId session variable to 1 (default)</p>";
        }
    }
    
    try {
        include($file_path);
    } catch (Exception $e) {
        echo "<h3>Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<h2>Alternative Files:</h2>";
    echo "<ul>";
    $files = scandir(__DIR__);
    foreach ($files as $file) {
        if (strpos(strtolower($file), 'today') !== false || 
            strpos(strtolower($file), 'attendance') !== false) {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
    
    echo "<h2>Try a Manual Redirect:</h2>";
    echo "<a href='viewTodayAttendance.php' style='padding: 10px 15px; background: #4e73df; color: white; text-decoration: none; border-radius: 4px;'>Access viewTodayAttendance.php</a>";
}
?>
