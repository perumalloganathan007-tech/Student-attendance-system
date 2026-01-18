<?php
require_once('../Includes/dbcon.php');

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!<br>";

// Define test functions
function testPasswordVerification($conn) {
    $email = "john.smith@school.com";
    $testPassword = "Password@123";
    
    // Get the stored password hash
    $query = "SELECT password FROM tblsubjectteachers WHERE emailAddress = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div style='color: red;'>User not found with email: $email</div><br>";
        return false;
    }
    
    $row = $result->fetch_assoc();
    $storedHash = $row['password'];
    
    echo "Testing password verification for: $email<br>";
    echo "Stored hash: " . htmlspecialchars($storedHash) . "<br>";
    echo "Test password: " . htmlspecialchars($testPassword) . "<br>";
    
    // Test verification
    if (password_verify($testPassword, $storedHash)) {
        echo "<div style='color: green;'>Password verification SUCCESSFUL!</div><br>";
        return true;
    } else {
        echo "<div style='color: red;'>Password verification FAILED!</div><br>";
        
        // Generate a new hash for comparison
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
        echo "New hash generated: " . htmlspecialchars($newHash) . "<br>";
        
        // Try to update the password if verification failed
        $updateQuery = "UPDATE tblsubjectteachers SET password = ? WHERE emailAddress = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ss", $newHash, $email);
        
        if ($updateStmt->execute()) {
            echo "<div style='color: green;'>Password hash updated successfully!</div><br>";
            return true;
        } else {
            echo "<div style='color: red;'>Failed to update password: " . $conn->error . "</div><br>";
            return false;
        }
    }
}

function displayAllTeachers($conn) {
    $query = "SELECT t.Id, t.firstName, t.lastName, t.emailAddress, t.password, t.subjectId, s.subjectName, s.subjectCode 
              FROM tblsubjectteachers t
              LEFT JOIN tblsubjects s ON t.subjectId = s.Id";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>All Subject Teachers in Database:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Password Hash</th><th>Subject ID</th><th>Subject</th><th>Subject Code</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Id'] . "</td>";
            echo "<td>" . $row['firstName'] . " " . $row['lastName'] . "</td>";
            echo "<td>" . $row['emailAddress'] . "</td>";
            echo "<td>" . substr($row['password'], 0, 15) . "...</td>";
            echo "<td>" . $row['subjectId'] . "</td>";
            echo "<td>" . $row['subjectName'] . "</td>";
            echo "<td>" . $row['subjectCode'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table><br>";
    } else {
        echo "<div style='color: red;'>No subject teachers found in database!</div><br>";
    }
}

// Create a new correct password hash for reference
$correctHash = password_hash("Password@123", PASSWORD_BCRYPT);
echo "Correct hash format for 'Password@123': " . htmlspecialchars($correctHash) . "<br><br>";

// Display all teachers
displayAllTeachers($conn);

// Test password verification
echo "<h3>Password Verification Test</h3>";
$result = testPasswordVerification($conn);

// Update all teachers with correct password if needed
if (!$result) {
    echo "<h3>Fixing all teacher passwords</h3>";
    $newHash = password_hash("Password@123", PASSWORD_BCRYPT);
    $updateAllQuery = "UPDATE tblsubjectteachers SET password = ?";
    $updateAllStmt = $conn->prepare($updateAllQuery);
    $updateAllStmt->bind_param("s", $newHash);
    
    if ($updateAllStmt->execute()) {
        echo "<div style='color: green;'>All teacher passwords updated successfully!</div><br>";
    } else {
        echo "<div style='color: red;'>Failed to update all passwords: " . $conn->error . "</div><br>";
    }
}

$conn->close();
?>
