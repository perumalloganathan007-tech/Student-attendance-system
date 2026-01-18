<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Quick Login for Testing</h2>";

// Start session
session_start();

// Include database connection
include '../Includes/dbcon.php';

// Auto-login as john.smith@school.com for testing
$email = 'john.smith@school.com';
$password = 'password123';

echo "<p>Attempting to log in as: <strong>$email</strong></p>";

// Check if user exists and get their info
$query = "SELECT st.Id, st.emailAddress, st.password, s.Id as subjectId, s.subjectName, s.subjectCode
          FROM tblsubjectteacher st 
          INNER JOIN tblsubjects s ON s.Id = st.subjectId 
          WHERE st.emailAddress = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Verify password (assuming it might be hashed or plain text)
    $passwordValid = false;
    if (password_verify($password, $user['password'])) {
        $passwordValid = true;
        echo "<div style='color: green;'>✅ Password verified (hashed)</div>";
    } elseif ($user['password'] === $password) {
        $passwordValid = true;
        echo "<div style='color: green;'>✅ Password verified (plain text)</div>";
    } else {
        echo "<div style='color: red;'>❌ Password verification failed</div>";
        echo "<p>Stored password: " . htmlspecialchars($user['password']) . "</p>";
        echo "<p>Trying to match: " . htmlspecialchars($password) . "</p>";
    }
    
    if ($passwordValid) {
        // Set session variables
        $_SESSION['userId'] = $user['Id'];
        $_SESSION['userType'] = 'SubjectTeacher';
        $_SESSION['subjectId'] = $user['subjectId'];
        $_SESSION['subjectName'] = $user['subjectName'];
        $_SESSION['subjectCode'] = $user['subjectCode'];
        
        echo "<div style='color: green;'>✅ Successfully logged in!</div>";
        echo "<h3>Session Variables Set:</h3>";
        echo "<ul>";
        echo "<li>User ID: " . $_SESSION['userId'] . "</li>";
        echo "<li>User Type: " . $_SESSION['userType'] . "</li>";
        echo "<li>Subject ID: " . $_SESSION['subjectId'] . "</li>";
        echo "<li>Subject Name: " . $_SESSION['subjectName'] . "</li>";
        echo "<li>Subject Code: " . $_SESSION['subjectCode'] . "</li>";
        echo "</ul>";
        
        echo "<h3>Ready to Test:</h3>";
        echo "<ul>";
        echo "<li><a href='takeAttendance.php' target='_blank' style='color: blue; font-weight: bold;'>🎯 Test Take Attendance Now</a></li>";
        echo "<li><a href='viewStudents.php' target='_blank'>👥 View Students</a></li>";
        echo "<li><a href='index.php' target='_blank'>🏠 Dashboard</a></li>";
        echo "<li><a href='test_take_attendance.php' target='_blank'>🔧 Diagnostic Test</a></li>";
        echo "</ul>";
        
    } else {
        echo "<div style='color: red;'>❌ Login failed due to password mismatch</div>";
    }
} else {
    echo "<div style='color: red;'>❌ User not found: $email</div>";
    echo "<p>Please run the complete_test.php script to create the test user first.</p>";
}
?>
