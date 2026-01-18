<?php
require_once('../Includes/dbcon.php');

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Create a new password hash
$correctPassword = "Password@123";
$newHash = password_hash($correctPassword, PASSWORD_BCRYPT);
echo "Generated new hash for 'Password@123': $newHash\n\n";

// Update all subject teacher passwords with the new hash
$updateQuery = "UPDATE tblsubjectteachers SET password = '$newHash'";

if ($conn->query($updateQuery)) {
    echo "SUCCESS: Updated passwords for all subject teachers!\n";
    
    // Verify that the updates worked by checking one account
    $testEmail = "john.smith@school.com";
    $testQuery = "SELECT password FROM tblsubjectteachers WHERE emailAddress = '$testEmail'";
    $result = $conn->query($testQuery);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storedHash = $row['password'];
        echo "\nVerification for $testEmail:\n";
        echo "Stored hash: $storedHash\n";
        
        if (password_verify($correctPassword, $storedHash)) {
            echo "Password verification SUCCESSFUL!\n";
        } else {
            echo "WARNING: Password verification still failing!\n";
        }
    }
} else {
    echo "ERROR: Failed to update passwords: " . $conn->error . "\n";
}

echo "\nPlease try logging in with:\n";
echo "Email: john.smith@school.com\n";
echo "Password: Password@123\n";

$conn->close();
?>
