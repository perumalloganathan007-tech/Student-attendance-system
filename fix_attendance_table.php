<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Subject Teacher Table Structure Fix</h1>";

try {
    include 'Includes/dbcon.php';
    
    echo "<h2>Current Table Structure Check</h2>";
    
    // Check if tblsubjectattendance exists and show its structure
    $result = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color:green;'>✅ Table 'tblsubjectattendance' exists</p>";
        
        // Show current columns
        echo "<h3>Current Columns in tblsubjectattendance:</h3>";
        $columns = $conn->query("DESCRIBE tblsubjectattendance");
        if ($columns) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $columns->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "<td>" . $row['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red;'>❌ Table 'tblsubjectattendance' does not exist</p>";
        echo "<p>Creating the table now...</p>";
    }
    
    echo "<h2>Recreating/Fixing tblsubjectattendance Table</h2>";
    
    // Drop and recreate the table to ensure proper structure
    $conn->query("DROP TABLE IF EXISTS tblsubjectattendance");
    
    $createAttendanceTable = "CREATE TABLE `tblsubjectattendance` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `studentId` int(11) NOT NULL,
      `subjectTeacherId` int(11) NOT NULL,
      `subjectId` int(11) NOT NULL,
      `classId` int(11) DEFAULT 1,
      `classArmId` int(11) DEFAULT 1,
      `sessionTermId` int(11) DEFAULT 1,
      `status` tinyint(1) NOT NULL COMMENT '1=Present, 0=Absent',
      `date` date NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      KEY `studentId` (`studentId`),
      KEY `subjectTeacherId` (`subjectTeacherId`),
      KEY `subjectId` (`subjectId`),
      KEY `date` (`date`),
      KEY `idx_attendance_lookup` (`subjectTeacherId`, `date`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createAttendanceTable)) {
        echo "<p style='color:green;'>✅ tblsubjectattendance table recreated successfully</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating tblsubjectattendance: " . $conn->error . "</p>";
    }
    
    echo "<h2>Verify New Table Structure</h2>";
    $columns = $conn->query("DESCRIBE tblsubjectattendance");
    if ($columns) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $columns->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Test Query</h2>";
    echo "<p>Testing the problematic query from SubjectTeacher/index.php...</p>";
    
    // Test the query that was causing the error
    $testQuery = "SELECT 
            s.subjectName,
            s.Id as subjectId,
            COALESCE(s.subjectCode, CONCAT('SUB', s.Id)) as subjectCode,
            COUNT(DISTINCT sts.studentId) as totalStudents,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() THEN sa.studentId END) as todayAttendance,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 1 THEN sa.studentId END) as todayPresent,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 0 THEN sa.studentId END) as todayAbsent
          FROM tblsubjectteachers st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          LEFT JOIN tblsubjectteacher_student sts ON sts.subjectTeacherId = st.Id
          LEFT JOIN tblsubjectattendance sa ON sa.subjectTeacherId = st.Id
          WHERE st.Id = 1
          GROUP BY s.Id";
    
    $testResult = $conn->query($testQuery);
    if ($testResult) {
        echo "<p style='color:green;'>✅ Test query executed successfully!</p>";
        echo "<p>Query returned " . $testResult->num_rows . " rows</p>";
    } else {
        echo "<p style='color:red;'>❌ Test query failed: " . $conn->error . "</p>";
    }
    
    echo "<h2>Next Steps</h2>";
    echo "<p>1. <a href='SubjectTeacher/index.php'>Test Subject Teacher Dashboard</a></p>";
    echo "<p>2. <a href='subjectTeacherLogin.php'>Login as Subject Teacher</a></p>";
    echo "<p>3. The 'sa.date' column error should now be resolved</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
