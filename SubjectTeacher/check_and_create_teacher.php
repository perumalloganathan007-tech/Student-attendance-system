<?php
require_once('../Includes/dbcon.php');

echo "<h2>Subject Teacher Database Check</h2>";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher'");
if ($tableCheck->num_rows == 0) {
    echo "<p style='color: red;'>ERROR: tblsubjectteacher table does not exist!</p>";
    exit;
}

// Check existing teachers
$teacherQuery = "SELECT * FROM tblsubjectteacher";
$result = $conn->query($teacherQuery);

if ($result && $result->num_rows > 0) {
    echo "<p>Found " . $result->num_rows . " teacher(s):</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject ID</th><th>Password Hash</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Id'] . "</td>";
        echo "<td>" . $row['firstName'] . " " . $row['lastName'] . "</td>";
        echo "<td>" . $row['emailAddress'] . "</td>";
        echo "<td>" . $row['subjectId'] . "</td>";
        echo "<td>" . substr($row['password'], 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No teachers found. Creating a test teacher...</p>";
    
    // Create a test teacher
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                    VALUES ('John', 'Smith', 'john.smith@school.com', ?, '1234567890', 1)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("s", $password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Created test teacher: john.smith@school.com / password123</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating teacher: " . $conn->error . "</p>";
    }
}

// Check subjects table
echo "<h3>Subjects Available:</h3>";
$subjectQuery = "SELECT * FROM tblsubjects LIMIT 5";
$subjectResult = $conn->query($subjectQuery);

if ($subjectResult && $subjectResult->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Subject Name</th><th>Subject Code</th></tr>";
    while ($subject = $subjectResult->fetch_assoc()) {
        echo "<tr><td>" . $subject['Id'] . "</td><td>" . $subject['subjectName'] . "</td><td>" . $subject['subjectCode'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No subjects found. You may need to add some subjects first.</p>";
}

echo "<p><a href='../subjectTeacherLogin.php'>Go to Login Page</a></p>";
?>
