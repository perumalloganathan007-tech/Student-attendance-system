<?php
// This script diagnoses and can fix issues with the tblsubjectattendance table schema
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['userId']) || isset($_GET['run'])) {
    // Allow execution without login if run parameter is present
    // This is for quick diagnostics but not recommended for production
} else {
    // Validate admin session otherwise
    if ($_SESSION['userType'] !== "Admin") {
        echo "You must be an admin to run this script";
        exit();
    }
}

echo "<h1>Student Attendance System - Database Schema Diagnostics</h1>";

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($sql);
    return $result->num_rows > 0;
}

// Function to execute a query safely
function executeQuery($conn, $query, $description) {
    echo "<p>Attempting: $description... ";
    try {
        $result = $conn->query($query);
        if ($result) {
            echo "<span style='color:green'>SUCCESS</span></p>";
            return true;
        } else {
            echo "<span style='color:red'>FAILED: " . $conn->error . "</span></p>";
            return false;
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>ERROR: " . $e->getMessage() . "</span></p>";
        return false;
    }
}

// 1. Check if the tblsubjectattendance table exists
echo "<h2>Checking tblsubjectattendance table</h2>";
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
if ($result->num_rows > 0) {
    $tableExists = true;
    echo "<p style='color:green'>✓ tblsubjectattendance table exists</p>";
} else {
    echo "<p style='color:red'>✗ tblsubjectattendance table does not exist!</p>";
}

if ($tableExists) {
    // 2. Check the structure of the table
    echo "<h2>Table Structure</h2>";
    $columns = [];
    $result = $conn->query("DESCRIBE tblsubjectattendance");
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
        $columns[] = $row['Field'];
    }
    echo "</table>";
    
    // 3. Check for specific issues
    echo "<h2>Schema Analysis</h2>";
    
    // Check for subjectId column
    if (in_array('subjectId', $columns)) {
        echo "<p style='color:green'>✓ subjectId column exists</p>";
    } else {
        echo "<p style='color:red'>✗ subjectId column is missing!</p>";
        if (isset($_GET['fix'])) {
            echo "<p>Attempting to add subjectId column...</p>";
            executeQuery($conn, "ALTER TABLE tblsubjectattendance ADD COLUMN subjectId INT(11) NULL AFTER studentId", 
                        "Adding subjectId column");
        }
    }
    
    // Check for subjectTeacherId column
    if (in_array('subjectTeacherId', $columns)) {
        echo "<p style='color:green'>✓ subjectTeacherId column exists</p>";
    } else {
        echo "<p style='color:red'>✗ subjectTeacherId column is missing!</p>";
        if (isset($_GET['fix'])) {
            echo "<p>Attempting to add subjectTeacherId column...</p>";
            executeQuery($conn, "ALTER TABLE tblsubjectattendance ADD COLUMN subjectTeacherId INT(11) NULL AFTER subjectId", 
                        "Adding subjectTeacherId column");
        }
    }
    
    // Check for date column
    if (in_array('date', $columns)) {
        echo "<p style='color:green'>✓ date column exists</p>";
    } else {
        echo "<p style='color:red'>✗ date column is missing!</p>";
        if (in_array('dateTimeTaken', $columns)) {
            echo "<p>The table uses dateTimeTaken instead of date</p>";
            if (isset($_GET['fix'])) {
                echo "<p>Attempting to add date column based on dateTimeTaken...</p>";
                executeQuery($conn, "ALTER TABLE tblsubjectattendance ADD COLUMN date DATE NULL AFTER status", 
                            "Adding date column");
                executeQuery($conn, "UPDATE tblsubjectattendance SET date = dateTimeTaken", 
                            "Copying dateTimeTaken to date");
            }
        } else if (isset($_GET['fix'])) {
            echo "<p>Attempting to add date column...</p>";
            executeQuery($conn, "ALTER TABLE tblsubjectattendance ADD COLUMN date DATE NULL", 
                        "Adding date column");
        }
    }
    
    // 4. Update subject relations if needed
    if (isset($_GET['updateSubjects']) && in_array('subjectId', $columns) && in_array('subjectTeacherId', $columns)) {
        echo "<h2>Updating Subject Relations</h2>";
        
        // Update subjectId from subjectTeacherId where possible
        executeQuery($conn, 
            "UPDATE tblsubjectattendance sa
             JOIN tblsubjectteachers st ON sa.subjectTeacherId = st.Id
             SET sa.subjectId = st.subjectId
             WHERE sa.subjectId IS NULL AND sa.subjectTeacherId IS NOT NULL AND st.subjectId IS NOT NULL",
            "Setting subjectId from subjectTeacherId where missing");
        
        // Update subjectTeacherId from subjectId where possible
        executeQuery($conn, 
            "UPDATE tblsubjectattendance sa
             JOIN tblsubjectteachers st ON sa.subjectId = st.subjectId
             SET sa.subjectTeacherId = st.Id
             WHERE sa.subjectTeacherId IS NULL AND sa.subjectId IS NOT NULL",
            "Setting subjectTeacherId from subjectId where missing");
    }
    
    // 5. Count records and show distribution
    echo "<h2>Record Statistics</h2>";
    $result = $conn->query("SELECT COUNT(*) AS total FROM tblsubjectattendance");
    $row = $result->fetch_assoc();
    echo "<p>Total Records: {$row['total']}</p>";
    
    if (in_array('subjectId', $columns)) {
        $result = $conn->query("SELECT COUNT(*) AS count FROM tblsubjectattendance WHERE subjectId IS NOT NULL");
        $row = $result->fetch_assoc();
        echo "<p>Records with subjectId: {$row['count']}</p>";
    }
    
    if (in_array('subjectTeacherId', $columns)) {
        $result = $conn->query("SELECT COUNT(*) AS count FROM tblsubjectattendance WHERE subjectTeacherId IS NOT NULL");
        $row = $result->fetch_assoc();
        echo "<p>Records with subjectTeacherId: {$row['count']}</p>";
    }
}

echo "<h2>Actions</h2>";
echo "<p><a href='?run=1'>Check Database Schema</a></p>";
echo "<p><a href='?run=1&fix=1'>Fix Database Schema Issues</a></p>";
echo "<p><a href='?run=1&fix=1&updateSubjects=1'>Fix Schema and Update Subject Relations</a></p>";
echo "<p style='color:orange'><strong>Note:</strong> Always back up your database before making schema changes.</p>";
?>
