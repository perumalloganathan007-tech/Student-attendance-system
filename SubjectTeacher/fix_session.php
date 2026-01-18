<?php
/**
 * Session Repair Tool for Subject Teacher Module
 * This file fixes session issues by ensuring default values are set when missing
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include_once('../Includes/dbcon.php');

// Function to check if user exists in the database
function checkTeacherExists($conn, $teacherId) {
    $query = "SELECT COUNT(*) as count FROM tblsubjectteacher WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return ($result['count'] > 0);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Session Repair Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .fixed { background-color: #d4edda; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Session Repair Tool</h1>
        <h3>Current Session Status:</h3>";

// Check if userId is set and valid
$userIdValid = isset($_SESSION['userId']) && !empty($_SESSION['userId']) && is_numeric($_SESSION['userId']);
$userExists = $userIdValid ? checkTeacherExists($conn, $_SESSION['userId']) : false;

echo "<table>
        <tr>
            <th>Session Variable</th>
            <th>Current Value</th>
            <th>Status</th>
        </tr>
        <tr>
            <td>userId</td>
            <td>" . (isset($_SESSION['userId']) ? $_SESSION['userId'] : '<em>Not set</em>') . "</td>
            <td>" . ($userIdValid ? ($userExists ? '<span class="success">Valid</span>' : '<span class="error">Invalid (User not found)</span>') : '<span class="error">Missing</span>') . "</td>
        </tr>
        <tr>
            <td>user_type</td>
            <td>" . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '<em>Not set</em>') . "</td>
            <td>" . (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'SubjectTeacher' ? '<span class="success">Valid</span>' : '<span class="error">Invalid or Missing</span>') . "</td>
        </tr>
        <tr>
            <td>userType</td>
            <td>" . (isset($_SESSION['userType']) ? $_SESSION['userType'] : '<em>Not set</em>') . "</td>
            <td>" . (isset($_SESSION['userType']) && $_SESSION['userType'] == 'SubjectTeacher' ? '<span class="success">Valid</span>' : '<span class="warning">Missing (but not critical)</span>') . "</td>
        </tr>
        <tr>
            <td>subjectId</td>
            <td>" . (isset($_SESSION['subjectId']) ? $_SESSION['subjectId'] : '<em>Not set</em>') . "</td>
            <td>" . (isset($_SESSION['subjectId']) ? '<span class="success">Set</span>' : '<span class="warning">Missing</span>') . "</td>
        </tr>
        <tr>
            <td>firstName</td>
            <td>" . (isset($_SESSION['firstName']) ? $_SESSION['firstName'] : '<em>Not set</em>') . "</td>
            <td>" . (isset($_SESSION['firstName']) ? '<span class="success">Set</span>' : '<span class="warning">Missing</span>') . "</td>
        </tr>
        <tr>
            <td>lastName</td>
            <td>" . (isset($_SESSION['lastName']) ? $_SESSION['lastName'] : '<em>Not set</em>') . "</td>
            <td>" . (isset($_SESSION['lastName']) ? '<span class="success">Set</span>' : '<span class="warning">Missing</span>') . "</td>
        </tr>
      </table>";

// Options for fixing
if ($userIdValid && $userExists) {
    echo "<h3>Auto-Fix Session</h3>";
    
    if (isset($_GET['fix']) && $_GET['fix'] == 'true') {
        // Perform the fixes
        if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'SubjectTeacher') {
            $_SESSION['user_type'] = 'SubjectTeacher';
            echo "<p class='fixed'>✓ Fixed: Set user_type to 'SubjectTeacher'</p>";
        }
        
        if (!isset($_SESSION['userType']) || $_SESSION['userType'] != 'SubjectTeacher') {
            $_SESSION['userType'] = 'SubjectTeacher';
            echo "<p class='fixed'>✓ Fixed: Set userType to 'SubjectTeacher'</p>";
        }
        
        // If firstName or lastName is missing, get them from the database        if (!isset($_SESSION['firstName']) || !isset($_SESSION['lastName'])) {
            $query = "SELECT firstName, lastName, subjectId FROM tblsubjectteacher WHERE Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['userId']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                if (!isset($_SESSION['firstName'])) {
                    $_SESSION['firstName'] = $row['firstName'];
                    echo "<p class='fixed'>✓ Fixed: Set firstName to '{$row['firstName']}'</p>";
                }
                
                if (!isset($_SESSION['lastName'])) {
                    $_SESSION['lastName'] = $row['lastName'];
                    echo "<p class='fixed'>✓ Fixed: Set lastName to '{$row['lastName']}'</p>";
                }
                
                if (!isset($_SESSION['subjectId'])) {
                    $_SESSION['subjectId'] = $row['subjectId'];
                    echo "<p class='fixed'>✓ Fixed: Set subjectId to '{$row['subjectId']}'</p>";
                }
            }
        }
        
        echo "<p><strong>Session repair completed!</strong> You can now <a href='index.php'>go to the dashboard</a>.</p>";
    } else {
        echo "<p>Click the button below to automatically fix session issues:</p>
              <a href='?fix=true' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Fix Session Issues</a>";
    }
} else if (!$userIdValid) {
    echo "<h3 class='error'>Critical Error: Missing or Invalid User ID</h3>
          <p>You need to log in properly to use this system. Please <a href='../subjectTeacherLogin.php'>log in here</a>.</p>";
} else if (!$userExists) {
    echo "<h3 class='error'>Critical Error: User Not Found</h3>
          <p>The user ID in your session doesn't exist in the database. Please <a href='../subjectTeacherLogin.php'>log in again</a>.</p>";
}

echo "</div>
</body>
</html>";
