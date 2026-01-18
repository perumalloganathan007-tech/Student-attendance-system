<?php
include 'Includes/dbcon.php';
echo 'Checking for john.smith@school.com in the database...' . PHP_EOL;
$query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Found user: {$row['firstName']} {$row['lastName']} (ID: {$row['Id']})" . PHP_EOL;
    echo "Password hash: {$row['password']}" . PHP_EOL;
} else {
    echo "User john.smith@school.com NOT found in the database!" . PHP_EOL;
    
    // Check if the table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteachers'");
    if ($tableCheck->num_rows == 0) {
        echo "The tblsubjectteachers table does not exist!" . PHP_EOL;
    } else {
        // Show sample records
        $sample = $conn->query("SELECT emailAddress FROM tblsubjectteachers LIMIT 5");
        if ($sample->num_rows > 0) {
            echo "Sample emails in the table:" . PHP_EOL;
            while($emailRow = $sample->fetch_assoc()) {
                echo "- {$emailRow['emailAddress']}" . PHP_EOL;
            }
        } else {
            echo "The tblsubjectteachers table is empty!" . PHP_EOL;
        }
    }
}
?>
