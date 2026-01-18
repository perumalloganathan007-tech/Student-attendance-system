<?php
// Debug file to check and fix session variables for Subject Teacher
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Subject Teacher Session Debug</h2>";

// Check session status
if (!isset($_SESSION['userId'])) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "⚠️ Error: User is not logged in. <a href='../subjectTeacherLogin.php'>Click here to login</a>";
    echo "</div>";
    die();
}

// Display critical session variables
echo "<h3>Session Variables Status:</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
$criticalVars = [
    'userId' => 'User ID',
    'firstName' => 'First Name',
    'lastName' => 'Last Name',
    'emailAddress' => 'Email',
    'subjectId' => 'Subject ID',
    'subjectName' => 'Subject Name',
    'subjectCode' => 'Subject Code',
    'userType' => 'User Type'
];

$missingVars = [];
foreach ($criticalVars as $var => $label) {
    $status = isset($_SESSION[$var]) && !empty($_SESSION[$var]);
    $color = $status ? 'green' : 'red';
    $icon = $status ? '✅' : '❌';
    echo "<div style='color: {$color}; margin: 5px 0;'>";
    echo "{$icon} {$label}: " . (isset($_SESSION[$var]) ? $_SESSION[$var] : 'Not set');
    echo "</div>";
    
    if (!$status) {
        $missingVars[] = $var;
    }
}
echo "</div>";

// Get Subject Teacher Information and Subject ID
$query = "SELECT 
            st.Id as teacherId,
            s.Id as subjectId,
            s.subjectName,
            s.subjectCode
          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          WHERE st.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    $teacherInfo = $result->fetch_assoc();
    
    echo "<h3>Database Information:</h3>";
    echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    
    if ($teacherInfo) {
        // Update session with correct values
        $_SESSION['subjectId'] = $teacherInfo['subjectId'];
        $_SESSION['subjectName'] = $teacherInfo['subjectName'];
        $_SESSION['subjectCode'] = $teacherInfo['subjectCode'];
        
        echo "<div style='color:green;'>";
        echo "✅ Subject information retrieved successfully:<br>";
        echo "Subject ID: " . $_SESSION['subjectId'] . "<br>";
        echo "Subject Name: " . $_SESSION['subjectName'] . "<br>";
        echo "Subject Code: " . $_SESSION['subjectCode'] . "</div>";
    } else {
        echo "<div style='color:red;'>";
        echo "❌ No subject information found for this teacher in the database<br>";
        echo "Please contact the administrator to assign a subject to your account.</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "❌ Database Error: " . $e->getMessage();
    echo "</div>";
}

// Navigation options
echo "<h3>Actions:</h3>";
echo "<div style='margin: 20px 0;'>";
if (empty($missingVars) && $teacherInfo) {
    echo "<a href='takeAttendance.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Take Attendance</a>";
}
echo "<a href='fix_attendance.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Fix Database Issues</a>";
echo "<a href='index.php' style='background: #607D8B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a>";
echo "</div>";

echo "</div>";

// Check database tables
echo "<h3>Database Table Check:</h3>";

// Check if tblsubjectattendance table has the required columns
echo "<h4>Checking tblsubjectattendance table structure:</h4>";
$checkColumnsQuery = "SHOW COLUMNS FROM tblsubjectattendance";
$columnsResult = $conn->query($checkColumnsQuery);

if ($columnsResult) {
    echo "<ul>";
    $columns = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
    }
    echo "</ul>";
    
    // Check for missing columns
    $requiredColumns = ['Id', 'studentId', 'teacherId', 'subjectId', 'subjectTeacherId', 'status', 'date', 'sessionTermId', 'remarks'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        echo "<div style='color:red;'><h4>Missing columns:</h4>";
        echo implode(", ", $missingColumns);
        echo "</div>";
        
        foreach ($missingColumns as $column) {
            if ($column === 'subjectTeacherId') {
                $alterQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN subjectTeacherId INT NULL AFTER subjectId";
                if ($conn->query($alterQuery)) {
                    echo "<div style='color:green;'>Added missing column: subjectTeacherId</div>";
                } else {
                    echo "<div style='color:red;'>Failed to add column: subjectTeacherId</div>";
                }
            } elseif ($column === 'remarks') {
                $alterQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN remarks VARCHAR(255) NULL AFTER status";
                if ($conn->query($alterQuery)) {
                    echo "<div style='color:green;'>Added missing column: remarks</div>";
                } else {
                    echo "<div style='color:red;'>Failed to add column: remarks</div>";
                }
            }
        }
    } else {
        echo "<div style='color:green;'><h4>All required columns are present!</h4></div>";
    }
} else {
    echo "<div style='color:red;'><h4>Error checking table structure:</h4>";
    echo $conn->error . "</div>";
}

// Check students assigned to this teacher
echo "<h3>Students assigned to this teacher:</h3>";
$studentQuery = "SELECT 
                    s.Id, 
                    s.firstName, 
                    s.lastName, 
                    s.admissionNumber
                 FROM tblstudents s
                 INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                 WHERE sts.subjectTeacherId = ?";

$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $_SESSION['userId']);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Admission Number</th></tr>";
        while ($student = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $student['Id'] . "</td>";
            echo "<td>" . $student['firstName'] . " " . $student['lastName'] . "</td>";
            echo "<td>" . $student['admissionNumber'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='color:red;'><h4>No students assigned to this teacher!</h4>";
        echo "You need to assign students to this subject teacher before taking attendance.</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'><h3>Error retrieving students:</h3>";
    echo $e->getMessage() . "</div>";
}

echo "<p><a href='index.php'>Return to Dashboard</a></p>";
?>
