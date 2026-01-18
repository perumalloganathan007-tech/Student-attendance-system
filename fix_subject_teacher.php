<?php
require_once 'Includes/dbcon.php';

// Create john.smith@school.com if it doesn't exist
$checkQuery = "SELECT * FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'";
$result = $conn->query($checkQuery);

if ($result->num_rows == 0) {
    // Insert the user if not exists
    $insertQuery = "INSERT INTO tblsubjectteachers (firstName, lastName, emailAddress, password, subjectId) 
                   VALUES ('John', 'Smith', 'john.smith@school.com', ?, 1)";
    $stmt = $conn->prepare($insertQuery);
    $password = password_hash('Password@123', PASSWORD_BCRYPT);
    $stmt->bind_param('s', $password);
    
    if ($stmt->execute()) {
        echo "Created new subject teacher account for john.smith@school.com<br>";
    } else {
        echo "Error creating account: " . $conn->error . "<br>";
    }
} else {
    // Update existing user's password
    $updateQuery = "UPDATE tblsubjectteachers SET password = ? WHERE emailAddress = 'john.smith@school.com'";
    $stmt = $conn->prepare($updateQuery);
    $password = password_hash('Password@123', PASSWORD_BCRYPT);
    $stmt->bind_param('s', $password);
    
    if ($stmt->execute()) {
        echo "Reset password for john.smith@school.com<br>";
    } else {
        echo "Error resetting password: " . $conn->error . "<br>";
    }
}

// Verify the account exists and password is set
$verifyQuery = "SELECT * FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'";
$result = $conn->query($verifyQuery);
if ($row = $result->fetch_assoc()) {
    echo "Account Status:<br>";
    echo "ID: " . $row['Id'] . "<br>";
    echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "<br>";
    echo "Email: " . $row['emailAddress'] . "<br>";
    echo "Subject ID: " . $row['subjectId'] . "<br>";
    echo "Password is set: " . (!empty($row['password']) ? "Yes" : "No") . "<br>";
} else {
    echo "Failed to verify account<br>";
}
?>
