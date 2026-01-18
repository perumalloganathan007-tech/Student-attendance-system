<?php
// This script adds the remarks column to the tblsubjectattendance table if it doesn't exist

// Include database connection
include '../Includes/dbcon.php';

// Check if remarks column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'remarks'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN remarks VARCHAR(255) NULL AFTER status";
    if ($conn->query($addColumnQuery)) {
        echo "Successfully added 'remarks' column to tblsubjectattendance table.<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'remarks' column already exists in tblsubjectattendance table.<br>";
}

// Check if subjectTeacherId column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'subjectTeacherId'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN subjectTeacherId INT NULL AFTER subjectId";
    if ($conn->query($addColumnQuery)) {
        echo "Successfully added 'subjectTeacherId' column to tblsubjectattendance table.<br>";
        
        // Update existing records with teacher ID based on subject
        $updateQuery = "UPDATE tblsubjectattendance sa 
                       JOIN tblsubjectteachers st ON sa.subjectId = st.subjectId 
                       SET sa.subjectTeacherId = st.Id";
        if ($conn->query($updateQuery)) {
            echo "Successfully updated subjectTeacherId values in existing records.<br>";
        } else {
            echo "Error updating records: " . $conn->error . "<br>";
        }
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "'subjectTeacherId' column already exists in tblsubjectattendance table.<br>";
}

echo "<br>Database structure check complete.";
?>
