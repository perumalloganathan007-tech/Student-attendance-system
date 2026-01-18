<?php
include '../Includes/dbcon.php';

// 1. Check if the tables exist
$tables = array('tblsubjects', 'tblsubjectteachers');
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    echo "Table $table exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "\n";
}

// 2. Create subject if not exists
$conn->query("INSERT INTO tblsubjects (subjectName, subjectCode) 
              SELECT 'Mathematics', 'MATH101' 
              WHERE NOT EXISTS (
                  SELECT 1 FROM tblsubjects WHERE subjectCode = 'MATH101'
              )");

// Get subject ID
$subjectResult = $conn->query("SELECT Id FROM tblsubjects WHERE subjectCode = 'MATH101'");
$subject = $subjectResult->fetch_assoc();
$subjectId = $subject['Id'];

echo "\nSubject ID for MATH101: " . $subjectId . "\n";

// 3. Create or update subject teacher
$password = "Math@123";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$conn->query("DELETE FROM tblsubjectteachers WHERE emailAddress = 'math.teacher@school.com'");

$query = "INSERT INTO tblsubjectteachers (
            firstName, 
            lastName, 
            emailAddress, 
            password, 
            phoneNo, 
            subjectId,
            dateCreated
          ) VALUES (
            'John',
            'Smith',
            'math.teacher@school.com',
            '$hashedPassword',
            '1234567890',
            $subjectId,
            NOW()
          )";

if($conn->query($query)) {
    echo "Subject teacher created successfully!\n";
} else {
    echo "Error creating subject teacher: " . $conn->error . "\n";
}

// 4. Verify login data
$verifyQuery = "SELECT st.*, s.subjectName, s.Id as subjectId 
                FROM tblsubjectteachers st
                INNER JOIN tblsubjects s ON s.Id = st.subjectId
                WHERE st.emailAddress = 'math.teacher@school.com'";
$result = $conn->query($verifyQuery);

if($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    echo "\nTeacher found in database:\n";
    echo "Name: " . $teacher['firstName'] . " " . $teacher['lastName'] . "\n";
    echo "Email: " . $teacher['emailAddress'] . "\n";
    echo "Subject: " . $teacher['subjectName'] . "\n";
    echo "Password verification test: " . (password_verify("Math@123", $teacher['password']) ? "PASS" : "FAIL") . "\n";
} else {
    echo "\nTeacher not found in database!\n";
}

// 5. Show table structure
echo "\nTable structure for tblsubjectteachers:\n";
$structure = $conn->query("DESCRIBE tblsubjectteachers");
while($row = $structure->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
