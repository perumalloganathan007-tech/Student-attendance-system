<?php
require_once('../Includes/dbcon.php');

echo "<h1>Complete Subject Teacher Test</h1>";

// Step 1: Ensure test user exists
echo "<h2>Step 1: Ensuring Test User Exists</h2>";

$checkUser = $conn->prepare("SELECT * FROM tblsubjectteacher WHERE emailAddress = ?");
$testEmail = 'john.smith@school.com';
$checkUser->bind_param("s", $testEmail);
$checkUser->execute();
$userResult = $checkUser->get_result();

if ($userResult->num_rows == 0) {
    // Create test user
    $password = password_hash('password123', PASSWORD_DEFAULT);
    $insertUser = $conn->prepare("INSERT INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId) VALUES (?, ?, ?, ?, ?, ?)");
    $firstName = 'John';
    $lastName = 'Smith';
    $phoneNo = '1234567890';
    $subjectId = 1;
    
    $insertUser->bind_param("sssssi", $firstName, $lastName, $testEmail, $password, $phoneNo, $subjectId);
    
    if ($insertUser->execute()) {
        echo "<p style='color: green;'>✅ Created test user: $testEmail / password123</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating user: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Test user exists: $testEmail</p>";
    $user = $userResult->fetch_assoc();
    echo "<p>User ID: " . $user['Id'] . ", Name: " . $user['firstName'] . " " . $user['lastName'] . "</p>";
}

// Step 2: Test login process
echo "<h2>Step 2: Testing Login Process</h2>";

$loginQuery = "SELECT * FROM tblsubjectteacher WHERE emailAddress = ?";
$stmt = $conn->prepare($loginQuery);
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✅ User found in database</p>";
    
    // Test password verification
    if (password_verify('password123', $user['password'])) {
        echo "<p style='color: green;'>✅ Password verification successful</p>";
        
        // Simulate session setup
        session_start();
        $_SESSION['userId'] = $user['Id'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['emailAddress'] = $user['emailAddress'];
        $_SESSION['subjectId'] = $user['subjectId'];
        $_SESSION['user_type'] = 'SubjectTeacher';
        $_SESSION['userType'] = 'SubjectTeacher';
        
        echo "<p style='color: green;'>✅ Session variables set</p>";
    } else {
        echo "<p style='color: red;'>❌ Password verification failed</p>";
    }
} else {
    echo "<p style='color: red;'>❌ User not found</p>";
}

// Step 3: Test subject teacher-student relationship
echo "<h2>Step 3: Testing Student Assignment</h2>";

if (isset($_SESSION['userId'])) {
    $teacherId = $_SESSION['userId'];
    
    // Check if students are assigned
    $studentQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentCount = $result->fetch_assoc()['count'];
    
    if ($studentCount > 0) {
        echo "<p style='color: green;'>✅ $studentCount students assigned to teacher</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No students assigned. Assigning first 3 students...</p>";
        
        // Assign first 3 students
        $getStudents = $conn->query("SELECT Id FROM tblstudents LIMIT 3");
        $assigned = 0;
        
        while ($student = $getStudents->fetch_assoc()) {
            $assignQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
            $assignStmt = $conn->prepare($assignQuery);
            $assignStmt->bind_param("ii", $teacherId, $student['Id']);
            
            if ($assignStmt->execute()) {
                $assigned++;
            }
        }
        
        echo "<p style='color: green;'>✅ Assigned $assigned students to teacher</p>";
    }
}

// Step 4: Test attendance functionality access
echo "<h2>Step 4: Testing Functionality Access</h2>";

echo "<div style='margin: 20px 0;'>";
echo "<h3>Test Links (use these to test the actual functionality):</h3>";
echo "<p><a href='../subjectTeacherLogin.php' target='_blank' style='background: #007cba; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Login Page</a></p>";
echo "<p><a href='takeAttendance.php' target='_blank' style='background: #28a745; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Take Attendance</a></p>";
echo "<p><a href='viewStudents.php' target='_blank' style='background: #17a2b8; color: white; padding: 10px; text-decoration: none; margin: 5px;'>View Students</a></p>";
echo "<p><a href='index.php' target='_blank' style='background: #6c757d; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Dashboard</a></p>";
echo "</div>";

// Step 5: Display current session
echo "<h2>Step 5: Current Session Status</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";
$sessionVars = ['userId', 'firstName', 'lastName', 'emailAddress', 'subjectId', 'user_type', 'userType'];

foreach ($sessionVars as $var) {
    $value = isset($_SESSION[$var]) ? $_SESSION[$var] : 'Not set';
    echo "<tr><td>$var</td><td>$value</td></tr>";
}
echo "</table>";

echo "<h2>Test Summary</h2>";
echo "<p style='color: green;'><strong>Setup Complete!</strong> You can now test the Subject Teacher functionality using the links above.</p>";
echo "<p><strong>Login Credentials:</strong> john.smith@school.com / password123</p>";
?>
