<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎯 Final Take Attendance Test</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>";

// Include required files
include '../Includes/dbcon.php';
session_start();

// Auto-login for testing
if (!isset($_SESSION['userId'])) {
    $email = 'john.smith@school.com';
    $query = "SELECT st.Id, st.emailAddress, s.Id as subjectId, s.subjectName, s.subjectCode
              FROM tblsubjectteacher st 
              INNER JOIN tblsubjects s ON s.Id = st.subjectId 
              WHERE st.emailAddress = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['userId'] = $user['Id'];
        $_SESSION['userType'] = 'SubjectTeacher';
        $_SESSION['subjectId'] = $user['subjectId'];
        $_SESSION['subjectName'] = $user['subjectName'];
        $_SESSION['subjectCode'] = $user['subjectCode'];
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>✅ Auto-logged in as: $email</div>";
    }
}

$tests = [];
$allPassed = true;

// Test 1: Database Connection
if ($conn->connect_error) {
    $tests[] = ['❌ Database Connection', 'Failed: ' . $conn->connect_error, false];
    $allPassed = false;
} else {
    $tests[] = ['✅ Database Connection', 'Connected successfully', true];
}

// Test 2: Session Variables
$sessionComplete = isset($_SESSION['userId']) && isset($_SESSION['userType']) && isset($_SESSION['subjectId']);
if ($sessionComplete) {
    $tests[] = ['✅ Session Variables', "User ID: {$_SESSION['userId']}, Subject: {$_SESSION['subjectName']}", true];
} else {
    $tests[] = ['❌ Session Variables', 'Missing session data', false];
    $allPassed = false;
}

// Test 3: Table Existence
$tables = ['tblsubjectteacher', 'tblsubjects', 'tblstudents', 'tblsubjectteacher_student', 'tblsubjectattendance'];
$missingTables = [];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$result || $result->num_rows === 0) {
        $missingTables[] = $table;
    }
}

if (empty($missingTables)) {
    $tests[] = ['✅ Required Tables', 'All tables exist', true];
} else {
    $tests[] = ['❌ Required Tables', 'Missing: ' . implode(', ', $missingTables), false];
    $allPassed = false;
}

// Test 4: Teacher Record
if ($sessionComplete) {
    $query = "SELECT st.Id, s.subjectName FROM tblsubjectteacher st 
              INNER JOIN tblsubjects s ON s.Id = st.subjectId 
              WHERE st.Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        $tests[] = ['✅ Teacher Record', "Found: {$teacher['subjectName']}", true];
    } else {
        $tests[] = ['❌ Teacher Record', 'Teacher not found in database', false];
        $allPassed = false;
    }
}

// Test 5: Students Assigned
if ($sessionComplete) {
    $query = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentCount = $result->fetch_assoc()['count'];
    
    if ($studentCount > 0) {
        $tests[] = ['✅ Student Assignments', "$studentCount students assigned", true];
    } else {
        $tests[] = ['⚠️ Student Assignments', 'No students assigned (will auto-assign)', true];
        
        // Auto-assign students for testing
        $studentsQuery = "SELECT Id FROM tblstudents LIMIT 5";
        $studentsResult = $conn->query($studentsQuery);
        if ($studentsResult && $studentsResult->num_rows > 0) {
            $assigned = 0;
            while ($student = $studentsResult->fetch_assoc()) {
                $assignQuery = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
                $assignStmt = $conn->prepare($assignQuery);
                $assignStmt->bind_param("ii", $_SESSION['userId'], $student['Id']);
                if ($assignStmt->execute()) {
                    $assigned++;
                }
            }
            $tests[] = ['✅ Auto-Assignment', "$assigned students assigned automatically", true];
        }
    }
}

// Test 6: Active Session Term
$sessionQuery = "SELECT Id, sessionName, termName FROM tblsessionterm WHERE isActive = 1";
$result = $conn->query($sessionQuery);
if ($result && $result->num_rows > 0) {
    $session = $result->fetch_assoc();
    $tests[] = ['✅ Active Session', "{$session['sessionName']} - {$session['termName']}", true];
} else {
    $tests[] = ['❌ Active Session', 'No active session term found', false];
    $allPassed = false;
}

// Test 7: Attendance Table Structure
$columnsQuery = "SHOW COLUMNS FROM tblsubjectattendance";
$result = $conn->query($columnsQuery);
if ($result) {
    $columns = [];
    while ($column = $result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    $requiredColumns = ['Id', 'studentId', 'subjectTeacherId', 'date', 'status'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        $tests[] = ['✅ Attendance Table', 'All required columns present', true];
    } else {
        $tests[] = ['❌ Attendance Table', 'Missing columns: ' . implode(', ', $missingColumns), false];
        $allPassed = false;
    }
} else {
    $tests[] = ['❌ Attendance Table', 'Cannot access table structure', false];
    $allPassed = false;
}

// Test 8: Take Attendance File
$takeAttendanceFile = __DIR__ . '/takeAttendance.php';
if (file_exists($takeAttendanceFile)) {
    $tests[] = ['✅ TakeAttendance File', 'File exists and accessible', true];
} else {
    $tests[] = ['❌ TakeAttendance File', 'File not found', false];
    $allPassed = false;
}

// Display Results
echo "<h2>🧪 Test Results</h2>";
echo "<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<thead style='background: #f8f9fa;'>";
echo "<tr><th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Test</th>";
echo "<th style='border: 1px solid #ddd; padding: 12px; text-align: left;'>Result</th></tr>";
echo "</thead><tbody>";

foreach ($tests as $test) {
    $rowColor = $test[2] ? '#d4edda' : '#f8d7da';
    echo "<tr style='background: $rowColor;'>";
    echo "<td style='border: 1px solid #ddd; padding: 12px; font-weight: bold;'>{$test[0]}</td>";
    echo "<td style='border: 1px solid #ddd; padding: 12px;'>{$test[1]}</td>";
    echo "</tr>";
}

echo "</tbody></table>";

// Overall Status
if ($allPassed) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #155724; margin: 0 0 10px 0;'>🎉 All Tests Passed!</h2>";
    echo "<p style='margin: 0;'>The Take Attendance system is ready to use.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: #721c24; margin: 0 0 10px 0;'>⚠️ Issues Found</h2>";
    echo "<p style='margin: 0;'>Please resolve the failed tests before using Take Attendance.</p>";
    echo "</div>";
}

// Action Buttons
echo "<div style='margin: 30px 0; text-align: center;'>";
echo "<h2>🚀 Next Steps</h2>";

if ($allPassed) {
    echo "<a href='takeAttendance.php' style='display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold;'>📝 Take Attendance Now</a>";
}

echo "<a href='quick_login.php' style='display: inline-block; background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔑 Login Again</a>";
echo "<a href='setup_students.php' style='display: inline-block; background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 5px;'>👥 Setup Students</a>";
echo "<a href='test_take_attendance.php' style='display: inline-block; background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔧 Diagnostic Test</a>";
echo "<a href='index.php' style='display: inline-block; background: #6f42c1; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Dashboard</a>";
echo "</div>";

echo "</div>";
?>
