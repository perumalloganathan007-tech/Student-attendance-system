<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';

echo "<h2>Subject Teacher Login Debug Tool</h2>";

// Test login credentials
$testEmail = "john.smith@school.com";
$testPassword = "Password@123";

echo "<h3>Testing Login for: $testEmail</h3>";

// Check if user exists
$query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<p style='color:green'>✓ User found in database</p>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$row['Id']}</td></tr>";
    echo "<tr><td>First Name</td><td>{$row['firstName']}</td></tr>";
    echo "<tr><td>Last Name</td><td>{$row['lastName']}</td></tr>";
    echo "<tr><td>Email</td><td>{$row['emailAddress']}</td></tr>";
    echo "<tr><td>Subject ID</td><td>{$row['subjectId']}</td></tr>";
    echo "<tr><td>Password Hash</td><td>" . substr($row['password'], 0, 50) . "...</td></tr>";
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Verification Test</h3>";
    if (password_verify($testPassword, $row['password'])) {
        echo "<p style='color:green'>✓ Password verification SUCCESSFUL</p>";
    } else {
        echo "<p style='color:red'>✗ Password verification FAILED</p>";
        
        // Try to fix the password
        echo "<p>Attempting to fix password...</p>";
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE tblsubjectteachers SET password = ? WHERE emailAddress = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newHash, $testEmail);
        
        if ($updateStmt->execute()) {
            echo "<p style='color:green'>✓ Password updated successfully</p>";
            echo "<p>New hash: " . substr($newHash, 0, 50) . "...</p>";
            
            // Test again
            if (password_verify($testPassword, $newHash)) {
                echo "<p style='color:green'>✓ Password verification now SUCCESSFUL</p>";
            } else {
                echo "<p style='color:red'>✗ Password verification still FAILED</p>";
            }
        } else {
            echo "<p style='color:red'>✗ Failed to update password</p>";
        }
    }
    
} else {
    echo "<p style='color:red'>✗ User NOT found in database</p>";
    
    // Show all available users
    echo "<h3>Available Subject Teachers:</h3>";
    $allQuery = "SELECT Id, firstName, lastName, emailAddress FROM tblsubjectteachers LIMIT 10";
    $allResult = $conn->query($allQuery);
    
    if ($allResult->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        while ($userRow = $allResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$userRow['Id']}</td>";
            echo "<td>{$userRow['firstName']} {$userRow['lastName']}</td>";
            echo "<td>{$userRow['emailAddress']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No subject teachers found in database!</p>";
    }
}

// Direct login test form
echo "<hr>";
echo "<h3>Manual Login Test</h3>";
echo "<form method='POST'>";
echo "<p>Email: <input type='email' name='test_email' value='$testEmail'></p>";
echo "<p>Password: <input type='password' name='test_password' value='$testPassword'></p>";
echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    echo "<h4>Login Test Results:</h4>";
    
    $query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            echo "<p style='color:green; font-weight:bold'>✓ LOGIN SUCCESSFUL!</p>";
            echo "<p>You should be able to login with these credentials.</p>";
        } else {
            echo "<p style='color:red; font-weight:bold'>✗ LOGIN FAILED - Wrong Password</p>";
        }
    } else {
        echo "<p style='color:red; font-weight:bold'>✗ LOGIN FAILED - User Not Found</p>";
    }
}
?>
