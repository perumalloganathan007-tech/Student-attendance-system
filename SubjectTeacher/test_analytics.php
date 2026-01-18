<?php
// Test script to check if attendanceAnalytics.php is accessible
echo "<h2>Testing Analytics Page</h2>";

// Start output buffering to catch any errors
ob_start();

// Include necessary files
include '../Includes/dbcon.php';

// Include session initialization
include 'Includes/init_session.php';

// Check if we can access the analytics page directly
echo "<h3>Testing Direct Access to attendanceAnalytics.php</h3>";
echo "<p>This will attempt to directly include the analytics file to see if there are any errors.</p>";

// Try to include the file
try {
    // Check if file exists first
    if (file_exists('attendanceAnalytics.php')) {
        echo "<p style='color:green'>✓ File exists at the expected path</p>";
        
        // Check if it's readable
        if (is_readable('attendanceAnalytics.php')) {
            echo "<p style='color:green'>✓ File is readable</p>";
            
            // Include the file to test it
            echo "<p>Attempting to include the file...</p>";
            
            // Start another output buffer to catch any errors or output from the included file
            ob_start();
            
            // Try to include the file
            $error = false;
            try {
                include 'attendanceAnalytics.php';
            } catch (Exception $e) {
                $error = true;
                echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
            }
            
            // Get any output from the included file
            $output = ob_get_contents();
            ob_end_clean();
            
            if (!$error) {
                echo "<p style='color:green'>✓ File included successfully</p>";
            }
            
            // Show a truncated version of the output
            if (strlen($output) > 500) {
                echo "<p>First 500 characters of output: <pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre></p>";
            } else {
                echo "<p>Output: <pre>" . htmlspecialchars($output) . "</pre></p>";
            }
        } else {
            echo "<p style='color:red'>✗ File exists but is not readable</p>";
        }
    } else {
        echo "<p style='color:red'>✗ File does not exist at the expected path</p>";
        
        // Check if it's in a different case
        $dir = opendir('.');
        $found = false;
        while (($file = readdir($dir)) !== false) {
            if (strtolower($file) == strtolower('attendanceAnalytics.php')) {
                $found = true;
                echo "<p>Found file with different case: $file</p>";
                break;
            }
        }
        closedir($dir);
        
        if (!$found) {
            echo "<p>Could not find file with any case variations</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check database connection for attendance data
echo "<h3>Testing Database Connection and Data</h3>";
echo "<p>Checking if we can query attendance data from the database...</p>";

try {
    // Check if we have an active session
    if (isset($_SESSION['userId']) && !empty($_SESSION['userId'])) {
        echo "<p style='color:green'>✓ User ID found in session: " . $_SESSION['userId'] . "</p>";
        
        // Try to query attendance data
        $query = "SELECT COUNT(*) as count FROM tblsubjectattendance WHERE subjectTeacherId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo "<p>Found " . $row['count'] . " attendance records for this teacher</p>";
    } else {
        echo "<p style='color:red'>✗ No user ID found in session</p>";
        echo "<p>To test this page, you need to log in as a subject teacher first and then run this test.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error querying database: " . $e->getMessage() . "</p>";
}

// Test if we can get the current URL
echo "<h3>Testing URL Access</h3>";
echo "<p>Current URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Analytics URL would be: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace('test_analytics.php', 'attendanceAnalytics.php', $_SERVER['REQUEST_URI']) . "</p>";

// Get the contents of the output buffer
$content = ob_get_contents();
ob_end_clean();

// Display the content
echo $content;

// Provide a link to try accessing the page directly
echo "<p><a href='attendanceAnalytics.php' target='_blank'>Try accessing attendanceAnalytics.php directly</a></p>";
?>
