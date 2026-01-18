<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../Includes/dbcon.php');

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<h1>Subject Teacher Login Test</h1>";
echo "<p style='color:green;'>Database connection successful!</p>";

// Test credentials
$testEmail = "john.smith@school.com";
$testPassword = "Password@123";

echo "<h2>Testing Login for: $testEmail</h2>";

// Step 1: Check if user exists
echo "<h3>Step 1: Checking if user exists</h3>";
$query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>ERROR: User not found with email: $testEmail</p>";
    echo "<p>Creating user...</p>";
    
    // Try to create the user
    $createQuery = "INSERT INTO tblsubjectteachers (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                    VALUES ('John', 'Smith', ?, ?, '1234567890', 
                    (SELECT Id FROM tblsubjects WHERE subjectCode = 'MATH101' LIMIT 1))";
    $createStmt = $conn->prepare($createQuery);
    $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
    $createStmt->bind_param("ss", $testEmail, $newHash);
    
    if ($createStmt->execute()) {
        echo "<p style='color:green;'>User created successfully!</p>";
    } else {
        echo "<p style='color:red;'>Failed to create user: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>User found!</p>";
    $row = $result->fetch_assoc();
    echo "<pre>";
    echo "User ID: " . $row['Id'] . "\n";
    echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "\n";
    echo "Email: " . $row['emailAddress'] . "\n";
    echo "SubjectId: " . $row['subjectId'] . "\n";
    echo "Password Hash: " . htmlspecialchars($row['password']) . "\n";
    echo "</pre>";
    
    // Step 2: Verify password
    echo "<h3>Step 2: Verifying password</h3>";
    if (password_verify($testPassword, $row['password'])) {
        echo "<p style='color:green;'>Password verification SUCCESSFUL!</p>";
    } else {
        echo "<p style='color:red;'>Password verification FAILED!</p>";
        echo "<p>Updating password...</p>";
        
        // Update password
        $updateQuery = "UPDATE tblsubjectteachers SET password = ? WHERE emailAddress = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
        $updateStmt->bind_param("ss", $newHash, $testEmail);
        
        if ($updateStmt->execute()) {
            echo "<p style='color:green;'>Password updated successfully!</p>";
            echo "<p>New hash: " . htmlspecialchars($newHash) . "</p>";
            
            // Verify again
            if (password_verify($testPassword, $newHash)) {
                echo "<p style='color:green;'>Password verification now SUCCESSFUL!</p>";
            } else {
                echo "<p style='color:red;'>Password still failing verification! PHP password_verify function may be broken.</p>";
            }
        } else {
            echo "<p style='color:red;'>Failed to update password: " . $conn->error . "</p>";
        }
    }
}

// Step 3: Check subjects table
echo "<h3>Step 3: Checking subjects table</h3>";
$subjectQuery = "SELECT * FROM tblsubjects";
$subjectResult = $conn->query($subjectQuery);

if ($subjectResult->num_rows > 0) {
    echo "<p style='color:green;'>Subjects found: " . $subjectResult->num_rows . "</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Subject Name</th><th>Subject Code</th><th>Class ID</th></tr>";
    
    while ($subjectRow = $subjectResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $subjectRow['Id'] . "</td>";
        echo "<td>" . $subjectRow['subjectName'] . "</td>";
        echo "<td>" . ($subjectRow['subjectCode'] ?? 'NULL') . "</td>";
        echo "<td>" . ($subjectRow['classId'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red;'>No subjects found!</p>";
}

// Step 4: Check subject teacher relationship
echo "<h3>Step 4: Checking subject teacher relationship</h3>";
$joinQuery = "SELECT t.Id, t.firstName, t.lastName, t.emailAddress, t.subjectId, s.subjectName, s.subjectCode 
              FROM tblsubjectteachers t
              LEFT JOIN tblsubjects s ON t.subjectId = s.Id";
$joinResult = $conn->query($joinQuery);

if ($joinResult->num_rows > 0) {
    echo "<p style='color:green;'>Found teacher-subject relationships: " . $joinResult->num_rows . "</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Teacher</th><th>Email</th><th>Subject ID</th><th>Subject</th><th>Subject Code</th></tr>";
    
    while ($joinRow = $joinResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $joinRow['firstName'] . " " . $joinRow['lastName'] . "</td>";
        echo "<td>" . $joinRow['emailAddress'] . "</td>";
        echo "<td>" . $joinRow['subjectId'] . "</td>";
        echo "<td>" . ($joinRow['subjectName'] ?? 'NULL') . "</td>";
        echo "<td>" . ($joinRow['subjectCode'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p style='color:red;'>No teacher-subject relationships found!</p>";
}

// Provide login instructions
echo "<h3>Login Instructions</h3>";
echo "<p>Please try logging in with:</p>";
echo "<ul>";
echo "<li>Email: $testEmail</li>";
echo "<li>Password: $testPassword</li>";
echo "</ul>";
echo "<p><a href='../subjectTeacherLogin.php'>Go to Login Page</a></p>";

$conn->close();
?>
