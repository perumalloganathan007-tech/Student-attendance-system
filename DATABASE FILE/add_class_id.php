<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'attendancesystem';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected successfully to database.\n";

// First add classId column
$sql1 = "ALTER TABLE `tblsubjects` 
         ADD COLUMN IF NOT EXISTS `classId` varchar(10) NOT NULL AFTER `subjectCode`";

// Execute the first SQL
if ($conn->query($sql1)) {
    echo "ClassId column added successfully!\n";
    
    // Verify the column exists
    $result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'classId'");
    if ($result->num_rows > 0) {
        echo "Column verification successful!\n";
    } else {
        echo "Error: Column was not created properly!\n";
    }
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
echo "Done.\n";
?>
