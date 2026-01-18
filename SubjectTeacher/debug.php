<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Subject Teacher Debug</h1>";

// Check session variables
echo "<h2>Session Variables</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Include database connection
echo "<h2>Database Connection</h2>";
include '../Includes/dbcon.php';
echo "Database connection established.<br>";

// Check if userId exists
echo "<h2>User ID Check</h2>";
if(isset($_SESSION['userId'])) {
    echo "User ID: " . $_SESSION['userId'] . "<br>";
    
    // Query teacher information
    $query = "SELECT * FROM tblsubjectteachers WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "<h3>Teacher Information</h3>";
        echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "<br>";
        echo "Email: " . $row['emailAddress'] . "<br>";
        echo "Subject ID: " . $row['subjectId'] . "<br>";
        
        // Check if subject exists
        $subjectQuery = "SELECT * FROM tblsubjects WHERE Id = ?";
        $subjectStmt = $conn->prepare($subjectQuery);
        $subjectStmt->bind_param("i", $row['subjectId']);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result();
        
        if($subjectResult->num_rows > 0) {
            $subject = $subjectResult->fetch_assoc();
            echo "<h3>Subject Information</h3>";
            echo "Subject Name: " . $subject['subjectName'] . "<br>";
            echo "Subject Code: " . $subject['subjectCode'] . "<br>";
            
            // Trying the main query from index.php
            echo "<h3>Main Query Test</h3>";
            
            $mainQuery = "SELECT 
                s.subjectName,
                s.Id as subjectId,
                s.subjectCode,
                COUNT(DISTINCT sts.studentId) as totalStudents,
                COUNT(DISTINCT CASE WHEN sa.date = CURDATE() THEN sa.studentId END) as todayAttendance,
                COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 1 THEN sa.studentId END) as todayPresent,
                COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 0 THEN sa.studentId END) as todayAbsent
            FROM tblsubjectteachers st
            INNER JOIN tblsubjects s ON s.Id = st.subjectId
            LEFT JOIN tblsubjectteacher_student sts ON sts.subjectTeacherId = st.Id
            LEFT JOIN tblsubjectattendance sa ON sa.subjectTeacherId = st.Id
            WHERE st.Id = ?
            GROUP BY s.Id";
            
            try {
                $mainStmt = $conn->prepare($mainQuery);
                $mainStmt->bind_param("i", $_SESSION['userId']);
                $mainStmt->execute();
                $mainResult = $mainStmt->get_result();
                
                if($mainResult->num_rows > 0) {
                    $stats = $mainResult->fetch_assoc();
                    echo "<pre>";
                    print_r($stats);
                    echo "</pre>";
                } else {
                    echo "No results from main query.<br>";
                }
            } catch (Exception $e) {
                echo "Error executing main query: " . $e->getMessage() . "<br>";
            }
            
            // Check for missing tables
            echo "<h3>Table Check</h3>";
            $tables = ["tblsubjectteacher_student", "tblsubjectattendance"];
            
            foreach($tables as $table) {
                $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
                if($checkTable->num_rows > 0) {
                    echo "✓ $table exists<br>";
                } else {
                    echo "✗ $table does not exist!<br>";
                }
            }
        } else {
            echo "Subject not found for ID: " . $row['subjectId'] . "<br>";
        }
    } else {
        echo "No teacher found with ID: " . $_SESSION['userId'] . "<br>";
    }
} else {
    echo "User ID not set in session. Please log in again.<br>";
}
?>
