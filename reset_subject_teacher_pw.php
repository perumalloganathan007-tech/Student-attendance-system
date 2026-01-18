<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'Includes/dbcon.php';

echo "<h2>Subject Teacher Password Reset Tool</h2>";

// Create a new password hash for "Password@123"
$defaultPassword = "Password@123";
$newHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

echo "<p>Generated new hash for 'Password@123': " . htmlspecialchars($newHash) . "</p>";

// Update all subject teacher passwords with the new hash
$updateQuery = "UPDATE tblsubjectteachers SET password = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("s", $newHash);

if ($stmt->execute()) {
    echo "<p style='color: green;'>SUCCESS: Updated passwords for all subject teachers!</p>";
    
    // List all subject teachers for login testing
    $listQuery = "SELECT Id, firstName, lastName, emailAddress, subjectId FROM tblsubjectteachers";
    $result = $conn->query($listQuery);
    
    if ($result->num_rows > 0) {
        echo "<h3>Available Subject Teacher Accounts:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject ID</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Id'] . "</td>";
            echo "<td>" . $row['firstName'] . " " . $row['lastName'] . "</td>";
            echo "<td>" . $row['emailAddress'] . "</td>";
            echo "<td>" . $row['subjectId'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No subject teachers found in database.</p>";
    }
    
    // Check if standard account exists
    $checkQuery = "SELECT * FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows == 0) {
        // Create standard test account
        echo "<p>Creating standard test account...</p>";
        
        // First check if we have subjects
        $subjectQuery = "SELECT Id FROM tblsubjects LIMIT 1";
        $subjectResult = $conn->query($subjectQuery);
        
        if ($subjectResult->num_rows > 0) {
            $subjectRow = $subjectResult->fetch_assoc();
            $subjectId = $subjectRow['Id'];
            
            $insertQuery = "INSERT INTO tblsubjectteachers (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                            VALUES ('John', 'Smith', 'john.smith@school.com', ?, '1234567890', ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("si", $newHash, $subjectId);
            
            if ($insertStmt->execute()) {
                echo "<p style='color: green;'>Created standard test account successfully!</p>";
            } else {
                echo "<p style='color: red;'>Failed to create test account: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>No subjects found in database. Cannot create test account.</p>";
        }
    }
    
} else {
    echo "<p style='color: red;'>ERROR: Failed to update passwords: " . $conn->error . "</p>";
}

echo "<h3>Login Instructions:</h3>";
echo "<p>Use any of the email addresses above with password: <strong>Password@123</strong></p>";
echo "<p><a href='subjectTeacherLogin.php'>Go to Subject Teacher Login Page</a></p>";
?>
