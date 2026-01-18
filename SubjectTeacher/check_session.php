<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Diagnostic Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .session-var { margin: 5px 0; padding: 5px; border: 1px solid #ddd; }
    .missing { color: red; }
    .present { color: green; }
    .debug-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
</style>";

session_start();

echo "<div class='debug-box'>";
echo "<h3>Current Session Variables:</h3>";
$required_vars = ['userId', 'userType', 'subjectId', 'subjectName', 'subjectCode'];

foreach ($required_vars as $var) {
    echo "<div class='session-var'>";
    if (isset($_SESSION[$var])) {
        echo "<span class='present'>✓ {$var}: " . htmlspecialchars($_SESSION[$var]) . "</span>";
    } else {
        echo "<span class='missing'>✗ {$var}: Not Set</span>";
    }
    echo "</div>";
}
echo "</div>";

echo "<div class='debug-box'>";
echo "<h3>Complete Session Dump:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Check database connection and subject teacher record
include '../Includes/dbcon.php';

if (isset($_SESSION['userId'])) {
    echo "<div class='debug-box'>";
    echo "<h3>Database Verification:</h3>";
    
    $query = "SELECT st.*, s.subjectName, s.subjectCode 
              FROM tblsubjectteacher st
              INNER JOIN tblsubjects s ON s.Id = st.subjectId
              WHERE st.Id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        echo "<div class='present'>✓ Found teacher record in database:</div>";
        echo "<pre>";
        print_r($teacher);
        echo "</pre>";
    } else {
        echo "<div class='missing'>✗ Teacher record not found in database!</div>";
    }
    echo "</div>";
    
    // If session variables are missing but we have userId, offer to fix
    if (!isset($_SESSION['subjectId']) || !isset($_SESSION['subjectName']) || !isset($_SESSION['subjectCode'])) {
        echo "<div class='debug-box'>";
        echo "<h3>Quick Fix Available:</h3>";
        echo "<form method='post'>";
        echo "<input type='submit' name='fix_session' value='Fix Missing Session Variables' style='padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
        echo "</form>";
        echo "</div>";
        
        if (isset($_POST['fix_session'])) {
            $_SESSION['subjectId'] = $teacher['subjectId'];
            $_SESSION['subjectName'] = $teacher['subjectName'];
            $_SESSION['subjectCode'] = $teacher['subjectCode'];
            $_SESSION['userType'] = 'SubjectTeacher';
            
            echo "<div class='debug-box' style='background: #d4edda; color: #155724;'>";
            echo "Session variables have been updated! <a href='takeAttendance.php'>Try Take Attendance now</a>";
            echo "</div>";
        }
    }
}

echo "<div class='debug-box'>";
echo "<h3>Navigation:</h3>";
echo "<ul>";
echo "<li><a href='takeAttendance.php'>Try Take Attendance</a></li>";
echo "<li><a href='index.php'>Go to Dashboard</a></li>";
echo "<li><a href='../subjectTeacherLogin.php'>Go to Login</a></li>";
echo "</ul>";
echo "</div>";
?>
