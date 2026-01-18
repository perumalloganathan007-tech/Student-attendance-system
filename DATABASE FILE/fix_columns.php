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

// Try to add columns one by one to handle errors gracefully
$columns = [
    ['subjectCode', 'varchar(50)', 'subjectName'],
    ['classId', 'varchar(10)', 'subjectCode']
];

foreach ($columns as $col) {
    list($colName, $colType, $afterCol) = $col;
    
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE '$colName'");
    if ($result->num_rows == 0) {
        // Column doesn't exist, add it
        $sql = "ALTER TABLE `tblsubjects` ADD COLUMN `$colName` $colType NOT NULL AFTER `$afterCol`";
        if ($conn->query($sql)) {
            echo "$colName column added successfully!\n";
        } else {
            echo "Error adding $colName column: " . $conn->error . "\n";
        }
    } else {
        echo "$colName column already exists.\n";
    }
}

// Add unique key for subjectCode if it doesn't exist
$result = $conn->query("SHOW INDEX FROM tblsubjects WHERE Key_name = 'uk_subject_code'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE `tblsubjects` ADD UNIQUE KEY `uk_subject_code` (`subjectCode`)")) {
        echo "Added unique key for subjectCode.\n";
    } else {
        echo "Error adding unique key: " . $conn->error . "\n";
    }
} else {
    echo "Unique key for subjectCode already exists.\n";
}

$conn->close();
echo "Done.\n";
?>
