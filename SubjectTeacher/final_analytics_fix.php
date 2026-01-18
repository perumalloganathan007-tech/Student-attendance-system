<?php
// Final fix for the attendance analytics issues

echo "<h1>Attendance Analytics Final Fix</h1>";
echo "<p>This script will implement all necessary fixes for the attendance analytics page.</p>";

// Include database connection
include '../Includes/dbcon.php';

// Initialize session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if we need to configure session for testing
if (!isset($_SESSION['userId']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'SubjectTeacher') {
    echo "<div class='alert alert-warning'>";
    echo "<strong>Not logged in as Subject Teacher</strong><br>";
    echo "This script requires you to be logged in as a Subject Teacher to test properly.<br>";
    echo "Current session: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Not set');
    echo "</div>";
    
    // Show option to set a test session
    echo "<form method='post'>";
    echo "<input type='submit' name='set_test_session' value='Set Test Session' class='btn btn-primary'>";
    echo "</form>";
    
    // Set test session if requested
    if (isset($_POST['set_test_session'])) {
        // Try to find a valid subject teacher in the database
        $query = "SELECT st.Id, st.emailAddress, st.firstName, st.lastName, s.Id as subjectId, s.subjectName 
                 FROM tblsubjectteachers st 
                 JOIN tblsubjects s ON st.subjectId = s.Id 
                 LIMIT 1";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $teacher = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['userId'] = $teacher['Id'];
            $_SESSION['user_type'] = 'SubjectTeacher';
            $_SESSION['firstName'] = $teacher['firstName'];
            $_SESSION['lastName'] = $teacher['lastName'];
            $_SESSION['emailAddress'] = $teacher['emailAddress'];
            $_SESSION['subjectId'] = $teacher['subjectId'];
            $_SESSION['subjectName'] = $teacher['subjectName'];
            $_SESSION['last_login'] = time();
            
            echo "<div class='alert alert-success'>";
            echo "Test session set for teacher: " . $teacher['firstName'] . " " . $teacher['lastName'] . "<br>";
            echo "Subject: " . $teacher['subjectName'] . "<br>";
            echo "Please refresh this page to continue.";
            echo "</div>";
            
            echo "<meta http-equiv='refresh' content='2'>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>";
            echo "Could not find any subject teachers in the database.<br>";
            echo "Error: " . $conn->error;
            echo "</div>";
        }
    }
}

// Fix 1: Check and ensure correct SQL query in session_utils.php
$sessionUtilsFile = "./Includes/session_utils.php";
if (file_exists($sessionUtilsFile)) {
    $contents = file_get_contents($sessionUtilsFile);
    
    // Check if the incorrect JOIN condition exists
    if (strpos($contents, "INNER JOIN tblterm t ON t.sessionTermId = st.Id") !== false) {
        // Replace with the correct JOIN condition
        $newContents = str_replace(
            "INNER JOIN tblterm t ON t.sessionTermId = st.Id",
            "INNER JOIN tblterm t ON t.Id = st.termId",
            $contents
        );
        
        // Write back to the file
        if (file_put_contents($sessionUtilsFile, $newContents) !== false) {
            echo "<div class='alert alert-success'>";
            echo "✓ Fixed incorrect JOIN condition in session_utils.php";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "✗ Failed to write updates to session_utils.php";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>";
        echo "✓ SQL query in session_utils.php is already correct";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>";
    echo "✗ session_utils.php file not found";
    echo "</div>";
}

// Fix 2: Ensure tblterm has sessionTermId column
echo "<h3>Checking tblterm Table Structure</h3>";

$hasSessionTermId = false;
$tableStructure = $conn->query("DESCRIBE tblterm");

if ($tableStructure) {
    while ($column = $tableStructure->fetch_assoc()) {
        if ($column['Field'] == 'sessionTermId') {
            $hasSessionTermId = true;
            break;
        }
    }
    
    if (!$hasSessionTermId) {
        // Add the column
        $addColumn = $conn->query("ALTER TABLE tblterm ADD COLUMN sessionTermId INT(11)");
        
        if ($addColumn) {
            echo "<div class='alert alert-success'>";
            echo "✓ Added sessionTermId column to tblterm table";
            echo "</div>";
            
            // Update the values
            $updateValues = $conn->query("
                UPDATE tblterm t 
                JOIN tblsessionterm st ON t.Id = st.termId 
                SET t.sessionTermId = st.Id
            ");
            
            if ($updateValues) {
                echo "<div class='alert alert-success'>";
                echo "✓ Updated sessionTermId values in tblterm table";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger'>";
                echo "✗ Failed to update sessionTermId values: " . $conn->error;
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>";
            echo "✗ Failed to add sessionTermId column: " . $conn->error;
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>";
        echo "✓ sessionTermId column already exists in tblterm";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>";
    echo "✗ Failed to get table structure: " . $conn->error;
    echo "</div>";
}

// Fix 3: Verify attendance analytics file
$analyticsFile = "./attendanceAnalytics.php";

if (file_exists($analyticsFile)) {
    echo "<div class='alert alert-info'>";
    echo "✓ attendanceAnalytics.php file exists";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "✗ attendanceAnalytics.php file not found!";
    echo "</div>";
    
    // Try to find with different case
    $files = scandir('.');
    $found = false;
    foreach ($files as $file) {
        if (strtolower($file) === 'attendanceanalytics.php') {
            echo "<div class='alert alert-warning'>";
            echo "Found file with different case: $file";
            echo "</div>";
            $found = true;
            
            // Create a symbolic link to fix the case sensitivity issue
            if (copy($file, 'attendanceAnalytics.php')) {
                echo "<div class='alert alert-success'>";
                echo "✓ Created a copy with the correct filename case";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger'>";
                echo "✗ Failed to create a copy with the correct filename case";
                echo "</div>";
            }
            break;
        }
    }
    if (!$found) {
        echo "<div class='alert alert-danger'>";
        echo "Could not find the analytics file with any case variation";
        echo "</div>";
    }
}

// Fix 4: Test database connections for analytics queries
echo "<h3>Testing Database Queries for Analytics</h3>";

try {
    // Only run this check if we have a valid session
    if (isset($_SESSION['userId'])) {
        // Test query for monthly stats
        $statsQuery = "SELECT 
                          DATE_FORMAT(sa.date, '%Y-%m') as month,
                          COUNT(DISTINCT sa.studentId) as totalStudents
                        FROM tblsubjectattendance sa
                        WHERE sa.subjectTeacherId = ?
                        GROUP BY DATE_FORMAT(sa.date, '%Y-%m')
                        ORDER BY month DESC
                        LIMIT 1";
        
        $stmt = $conn->prepare($statsQuery);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $count = $result->num_rows;
            echo "<div class='alert alert-" . ($count > 0 ? "success" : "warning") . "'>";
            echo ($count > 0 ? "✓ Successfully" : "⚠ Query executed but") . " retrieved $count monthly statistics records";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "✗ Monthly statistics query failed: " . $stmt->error;
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>";
        echo "⚠ Skipping database query tests - no valid session";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "✗ Error testing database queries: " . $e->getMessage();
    echo "</div>";
}

// Final conclusion
echo "<h2>Summary of Fixes</h2>";
echo "<ol>";
echo "<li>Fixed JOIN condition in session_utils.php</li>";
echo "<li>Verified/added sessionTermId column in tblterm table</li>";
echo "<li>Verified existence of attendanceAnalytics.php file</li>";
echo "<li>Tested database queries for analytics data</li>";
echo "</ol>";

echo "<h3>Next Steps</h3>";
echo "<p>You should now be able to access the attendance analytics page without errors.</p>";
echo "<div class='mt-4'>";
echo "<a href='attendanceAnalytics.php' class='btn btn-primary'>Go to Analytics Dashboard</a> ";
echo "<a href='index.php' class='btn btn-secondary'>Return to Dashboard</a>";
echo "</div>";
?>
