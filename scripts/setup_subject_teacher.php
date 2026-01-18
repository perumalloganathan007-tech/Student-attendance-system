<?php
require_once('../Includes/dbcon.php');

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Create tables
$sqlFile = file_get_contents(__DIR__ . '/../DATABASE FILE/subject_teacher_update.sql');
$queries = explode(';', $sqlFile);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (stripos($query, 'INSERT INTO `tblsubjectteachers`') !== false) {
            // Skip the original insert, we'll do it with proper password hashing
            continue;
        }
        if (!$conn->query($query)) {
            echo "Error executing query: " . $conn->error . "\n";
            echo "Query was: " . $query . "\n\n";
        } else {
            echo "Successfully executed: " . substr($query, 0, 50) . "...\n";
        }
    }
}

// Now insert the subject teacher with properly hashed password
$firstName = 'Math';
$lastName = 'Teacher';
$email = 'math.teacher@school.com';
$password = 'Math@123';
$phoneNo = '1234567890';
$subjectId = '1';

// Hash the password using the function from dbcon.php
$hashedPassword = hash_password($password);

// Prepare the insert statement
$query = "INSERT INTO tblsubjectteachers (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
          VALUES (?, ?, ?, ?, ?, ?)
          ON DUPLICATE KEY UPDATE 
          firstName = VALUES(firstName),
          lastName = VALUES(lastName),
          password = VALUES(password),
          phoneNo = VALUES(phoneNo),
          subjectId = VALUES(subjectId)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssss", $firstName, $lastName, $email, $hashedPassword, $phoneNo, $subjectId);

if ($stmt->execute()) {
    echo "\nSubject teacher account created/updated successfully!\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
} else {
    echo "\nError creating subject teacher account: " . $stmt->error . "\n";
}

// Verify the account
$query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "\nTest subject teacher account verified:\n";
    echo "Email: " . $row['emailAddress'] . "\n";
    echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "\n";
    
    // Test password verification
    if (verify_password($password, $row['password'])) {
        echo "Password verification successful!\n";
    } else {
        echo "Password verification failed!\n";
    }
} else {
    echo "\nTest subject teacher account not found!\n";
}

$conn->close();
?>
