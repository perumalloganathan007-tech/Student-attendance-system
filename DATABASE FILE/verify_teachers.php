<?php
require_once('../Includes/dbcon.php');

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Check if subject teachers table exists
$result = $conn->query("SHOW TABLES LIKE 'tblsubjectteachers'");
if ($result->num_rows == 0) {
    die("tblsubjectteachers table does not exist!");
}
echo "Subject teachers table exists.\n\n";

// Display sample login credentials
$query = "SELECT firstName, lastName, emailAddress, subjectName, subjectCode 
          FROM tblsubjectteachers st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "Available subject teacher accounts:\n";
    echo "----------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "\n";
        echo "Email: " . $row['emailAddress'] . "\n";
        echo "Subject: " . $row['subjectName'] . " (" . $row['subjectCode'] . ")\n";
        echo "Password: Password@123\n";
        echo "----------------------------------------\n";
    }
} else {
    echo "No subject teachers found in database.\n";
    echo "Running insert script...\n";
    
    // Run the insert script if no teachers found
    $sql = file_get_contents('insert_subject_teachers.sql');
    if ($conn->multi_query($sql)) {
        echo "Subject teachers added successfully!\n";
    } else {
        echo "Error adding subject teachers: " . $conn->error . "\n";
    }
}

$conn->close();
?>
