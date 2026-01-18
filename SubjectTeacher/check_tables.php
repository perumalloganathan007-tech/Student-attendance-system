<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../Includes/dbcon.php');

echo "<h1>Subject Teacher Table Check</h1>";

// Check if tables exist
$requiredTables = [
    'tblsubjects',
    'tblsubjectteachers',
    'tblsubjectteacher_student',
    'tblsubjectattendance'
];

echo "<h2>Checking Required Tables</h2>";
foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color:green'>✓ Table '$table' exists</p>";
        
        // Show table structure
        echo "<h3>Structure of $table</h3>";
        $columns = $conn->query("DESCRIBE $table");
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($column = $columns->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count records
        $count = $conn->query("SELECT COUNT(*) as total FROM $table")->fetch_assoc()['total'];
        echo "<p>Total records: $count</p>";
    } else {
        echo "<p style='color:red'>✗ Table '$table' does not exist!</p>";
    }
}

echo "<h2>Checking Subject Teacher Relationships</h2>";
$teacherId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;

if ($teacherId) {
    echo "<p>Current teacher ID: $teacherId</p>";
    
    // Check teacher record
    $teacherQuery = "SELECT * FROM tblsubjectteachers WHERE Id = $teacherId";
    $teacherResult = $conn->query($teacherQuery);
    
    if ($teacherResult->num_rows > 0) {
        $teacher = $teacherResult->fetch_assoc();
        echo "<p>Teacher found: " . $teacher['firstName'] . " " . $teacher['lastName'] . "</p>";
        echo "<p>Subject ID: " . $teacher['subjectId'] . "</p>";
        
        // Check if subject exists
        $subjectQuery = "SELECT * FROM tblsubjects WHERE Id = " . $teacher['subjectId'];
        $subjectResult = $conn->query($subjectQuery);
        
        if ($subjectResult->num_rows > 0) {
            $subject = $subjectResult->fetch_assoc();
            echo "<p style='color:green'>✓ Subject found: " . $subject['subjectName'] . " (" . $subject['subjectCode'] . ")</p>";
        } else {
            echo "<p style='color:red'>✗ Subject not found for this teacher!</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Teacher record not found!</p>";
    }
} else {
    echo "<p style='color:red'>✗ No teacher ID provided!</p>";
}

echo "<h2>Creating Missing Tables</h2>";

// Create tblsubjectteacher_student if it doesn't exist
$checkTable = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
if ($checkTable->num_rows == 0) {
    echo "<p>Creating tblsubjectteacher_student table...</p>";
    
    $createTable = "CREATE TABLE `tblsubjectteacher_student` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($createTable)) {
        echo "<p style='color:green'>✓ Table created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating table: " . $conn->error . "</p>";
    }
}

// Create tblsubjectattendance if it doesn't exist
$checkTable = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
if ($checkTable->num_rows == 0) {
    echo "<p>Creating tblsubjectattendance table...</p>";
    
    $createTable = "CREATE TABLE `tblsubjectattendance` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `status` tinyint(1) NOT NULL,
        `date` date NOT NULL,
        `sessionTermId` int(11) NOT NULL,
        `dateTimeTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($createTable)) {
        echo "<p style='color:green'>✓ Table created successfully</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating table: " . $conn->error . "</p>";
    }
}

echo "<h2>Solution</h2>";
echo "<p>After verifying the database tables, please go back to the <a href='index.php'>Subject Teacher Dashboard</a> to see if it's fixed.</p>";

$conn->close();
?>
