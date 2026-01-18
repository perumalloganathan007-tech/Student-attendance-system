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

// SQL to add column
$sql = "ALTER TABLE `tblsubjects` 
        ADD COLUMN IF NOT EXISTS `subjectCode` varchar(50) NOT NULL AFTER `subjectName`,
        ADD UNIQUE KEY IF NOT EXISTS `uk_subject_code` (`subjectCode`);";

// Execute the SQL
if ($conn->query($sql)) {
    echo "Subject Code column added successfully!\n";
    
    // Verify the column exists
    $result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'subjectCode'");
    if ($result->num_rows > 0) {
        echo "Column verification successful!\n";
        
        // Update existing rows with default values if needed
        $conn->query("UPDATE tblsubjects SET subjectCode = CONCAT('SUB', Id) WHERE subjectCode = ''");
        echo "Updated existing rows with default subject codes.\n";
    } else {
        echo "Error: Column was not created properly!\n";
    }
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
echo "Done.\n";
?>
