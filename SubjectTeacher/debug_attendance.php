<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Take Attendance Page Debug</h1>";

try {
    include '../Includes/dbcon.php';
    session_start();
    
    echo "<h2>1. Session Information</h2>";
    if (isset($_SESSION['userId'])) {
        echo "<p style='color:green;'>✅ User ID: " . $_SESSION['userId'] . "</p>";
        echo "<p>User Type: " . ($_SESSION['userType'] ?? $_SESSION['user_type'] ?? 'Not set') . "</p>";
        echo "<p>Subject ID: " . ($_SESSION['subjectId'] ?? 'Not set') . "</p>";
        echo "<p>Subject Name: " . ($_SESSION['subjectName'] ?? 'Not set') . "</p>";
    } else {
        echo "<p style='color:red;'>❌ No active session found</p>";
        echo "<p><a href='../subjectTeacherLogin.php'>Login Here</a></p>";
    }
    
    if (isset($_SESSION['userId'])) {
        echo "<h2>2. Subject Teacher Information</h2>";
          // Get subject teacher information
        $teacherQuery = "SELECT st.Id, st.firstName, st.lastName, st.emailAddress, s.subjectName, s.Id as subjectId
                        FROM tblsubjectteacher st
                        INNER JOIN tblsubjects s ON s.Id = st.subjectId
                        WHERE st.Id = ?";
        $stmt = $conn->prepare($teacherQuery);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $teacherResult = $stmt->get_result();
        
        if ($teacherResult->num_rows > 0) {
            $teacher = $teacherResult->fetch_assoc();
            echo "<p style='color:green;'>✅ Teacher: " . $teacher['firstName'] . " " . $teacher['lastName'] . "</p>";
            echo "<p>Email: " . $teacher['emailAddress'] . "</p>";
            echo "<p>Subject: " . $teacher['subjectName'] . " (ID: " . $teacher['subjectId'] . ")</p>";
            
            // Update session with correct information
            $_SESSION['subjectId'] = $teacher['subjectId'];
            $_SESSION['subjectName'] = $teacher['subjectName'];
        } else {
            echo "<p style='color:red;'>❌ No subject teacher record found for user ID: " . $_SESSION['userId'] . "</p>";
        }
        
        echo "<h2>3. Students Assigned to This Teacher</h2>";
        
        // Check if students are assigned to this subject teacher
        $studentQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $result = $stmt->get_result();
        $studentCount = $result->fetch_assoc()['count'];
        
        echo "<p>Students assigned: <strong>$studentCount</strong></p>";
        
        if ($studentCount == 0) {
            echo "<div style='background:#fff3cd; padding:15px; border-radius:5px; margin:10px 0;'>";
            echo "<h3>⚠️ No Students Assigned</h3>";
            echo "<p>This might be why the Take Attendance page doesn't show any students.</p>";
            echo "<p><strong>Solution:</strong> We need to assign students to this subject teacher.</p>";
            echo "</div>";
            
            // Let's assign some sample students
            echo "<h3>Assigning Sample Students</h3>";
            
            // Get some students from the database
            $allStudents = "SELECT Id, firstName, lastName FROM tblstudents LIMIT 5";
            $studentsResult = $conn->query($allStudents);
            
            if ($studentsResult && $studentsResult->num_rows > 0) {
                echo "<p>Assigning students to subject teacher...</p>";
                while ($student = $studentsResult->fetch_assoc()) {
                    // Insert student-teacher relationship
                    $insertQuery = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bind_param("ii", $_SESSION['userId'], $student['Id']);
                    
                    if ($insertStmt->execute()) {
                        echo "<p style='color:green;'>✅ Assigned: " . $student['firstName'] . " " . $student['lastName'] . "</p>";
                    } else {
                        echo "<p style='color:red;'>❌ Failed to assign: " . $student['firstName'] . " " . $student['lastName'] . "</p>";
                    }
                }
            } else {
                echo "<p style='color:red;'>❌ No students found in database</p>";
                echo "<p>You may need to add students through the Admin panel first.</p>";
            }
        } else {
            echo "<h3>✅ Students Currently Assigned:</h3>";
            $assignedQuery = "SELECT s.Id, s.firstName, s.lastName, s.admissionNumber 
                             FROM tblstudents s 
                             INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id 
                             WHERE sts.subjectTeacherId = ?";
            $stmt = $conn->prepare($assignedQuery);
            $stmt->bind_param("i", $_SESSION['userId']);
            $stmt->execute();
            $assignedResult = $stmt->get_result();
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background-color: #f0f0f0;'><th>ID</th><th>Admission No.</th><th>Name</th></tr>";
            while ($student = $assignedResult->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $student['Id'] . "</td>";
                echo "<td>" . $student['admissionNumber'] . "</td>";
                echo "<td>" . $student['firstName'] . " " . $student['lastName'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h2>4. Today's Attendance Check</h2>";
        $today = date('Y-m-d');
        $attendanceQuery = "SELECT COUNT(*) as count FROM tblsubjectattendance 
                           WHERE subjectTeacherId = ? AND date = ?";
        $stmt = $conn->prepare($attendanceQuery);
        $stmt->bind_param("is", $_SESSION['userId'], $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendanceCount = $result->fetch_assoc()['count'];
        
        if ($attendanceCount > 0) {
            echo "<p style='color:orange;'>⚠️ Attendance already taken today ($attendanceCount records)</p>";
            echo "<p>You may need to modify existing attendance or the system may prevent new entries.</p>";
        } else {
            echo "<p style='color:green;'>✅ No attendance taken today - ready to take attendance</p>";
        }
    }
    
    echo "<h2>5. Navigation Links</h2>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='index.php' style='display:inline-block; padding:10px 20px; margin:5px; background:#007bff; color:white; text-decoration:none; border-radius:3px;'>Dashboard</a>";
    echo "<a href='takeAttendance.php' style='display:inline-block; padding:10px 20px; margin:5px; background:#28a745; color:white; text-decoration:none; border-radius:3px;'>Take Attendance</a>";
    echo "<a href='viewTodayAttendance.php' style='display:inline-block; padding:10px 20px; margin:5px; background:#17a2b8; color:white; text-decoration:none; border-radius:3px;'>View Today's Attendance</a>";
    echo "<a href='viewStudents.php' style='display:inline-block; padding:10px 20px; margin:5px; background:#6c757d; color:white; text-decoration:none; border-radius:3px;'>View Students</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
