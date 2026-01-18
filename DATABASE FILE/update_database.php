<?php
require_once('../Includes/dbcon.php');

// Execute the ALTER TABLE statement
$sql = file_get_contents('add_subject_code.sql');

if($conn->multi_query($sql)) {
    echo "Subject Code column added successfully!";
    
    // Wait for the multi_query to finish
    while($conn->next_result()) {
        if($res = $conn->store_result()) {
            $res->free();
        }
    }
    
    // Verify the column exists
    $result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'subjectCode'");
    if($result->num_rows > 0) {
        echo "\nColumn verification successful!";
    } else {
        echo "\nError: Column was not created properly!";
    }
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>
