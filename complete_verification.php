<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Complete Subject Teacher Fix Verification</h1>";

try {
    include 'Includes/dbcon.php';
    
    echo "<h2>1. Database Connection Test</h2>";
    if ($conn) {
        echo "<p style='color:green;'>✅ Database connected successfully</p>";
        echo "<p>Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<h2>2. Required Tables Check</h2>";
    $requiredTables = [
        'tblsubjectteachers' => 'Subject Teachers',
        'tblsubjects' => 'Subjects',
        'tblstudents' => 'Students',
        'tblsubjectteacher_student' => 'Subject Teacher-Student Relations',
        'tblsubjectattendance' => 'Subject Attendance Records'
    ];
    
    foreach ($requiredTables as $table => $description) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color:green;'>✅ $description ($table) - EXISTS</p>";
        } else {
            echo "<p style='color:red;'>❌ $description ($table) - MISSING</p>";
        }
    }
    
    echo "<h2>3. Critical Column Verification</h2>";
    
    // Check tblsubjectattendance structure specifically
    $attendanceTable = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
    if ($attendanceTable && $attendanceTable->num_rows > 0) {
        echo "<p style='color:green;'>✅ tblsubjectattendance table exists</p>";
        
        // Check for the 'date' column specifically
        $dateColumn = $conn->query("SHOW COLUMNS FROM tblsubjectattendance LIKE 'date'");
        if ($dateColumn && $dateColumn->num_rows > 0) {
            echo "<p style='color:green;'>✅ 'date' column exists in tblsubjectattendance</p>";
        } else {
            echo "<p style='color:red;'>❌ 'date' column MISSING in tblsubjectattendance</p>";
            echo "<p>Adding missing date column...</p>";
            $addColumn = "ALTER TABLE tblsubjectattendance ADD COLUMN `date` date NOT NULL AFTER `status`";
            if ($conn->query($addColumn)) {
                echo "<p style='color:green;'>✅ Date column added successfully</p>";
            } else {
                echo "<p style='color:red;'>❌ Failed to add date column: " . $conn->error . "</p>";
            }
        }
        
        // Show full table structure
        echo "<h3>tblsubjectattendance Table Structure:</h3>";
        $structure = $conn->query("DESCRIBE tblsubjectattendance");
        if ($structure) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($row = $structure->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red;'>❌ tblsubjectattendance table does not exist</p>";
        echo "<p>Creating table now...</p>";
        
        $createTable = "CREATE TABLE `tblsubjectattendance` (
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
        
        if ($conn->query($createTable)) {
            echo "<p style='color:green;'>✅ tblsubjectattendance created successfully</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to create tblsubjectattendance: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>4. Query Test</h2>";
    echo "<p>Testing the problematic query from SubjectTeacher/index.php line 62...</p>";
    
    // Test the exact query that was causing the error
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
        
        if ($testResult->num_rows > 0) {
            echo "<h3>Sample Result:</h3>";
            $row = $testResult->fetch_assoc();
            echo "<table border='1' style='border-collapse: collapse;'>";
            foreach ($row as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red;'>❌ Test query failed: " . $conn->error . "</p>";
        echo "<p><strong>This indicates the 'sa.date' column issue persists!</strong></p>";
    }
    
    echo "<h2>5. Subject Teacher Login Test</h2>";
    echo "<p>Check if the subject teacher user exists...</p>";
    
    $userCheck = $conn->query("SELECT Id, firstName, lastName, emailAddress FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'");
    if ($userCheck && $userCheck->num_rows > 0) {
        $user = $userCheck->fetch_assoc();
        echo "<p style='color:green;'>✅ Subject teacher user exists:</p>";
        echo "<ul>";
        echo "<li>ID: " . $user['Id'] . "</li>";
        echo "<li>Name: " . $user['firstName'] . " " . $user['lastName'] . "</li>";
        echo "<li>Email: " . $user['emailAddress'] . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ Subject teacher user (john.smith@school.com) not found</p>";
    }
    
    echo "<h2>6. Next Steps</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<h3>If all tests passed:</h3>";
    echo "<ol>";
    echo "<li><a href='subjectTeacherLogin.php' style='color: #007bff;'>Login as Subject Teacher</a> (john.smith@school.com / Password@123)</li>";
    echo "<li><a href='SubjectTeacher/index.php' style='color: #007bff;'>Access Subject Teacher Dashboard</a></li>";
    echo "<li><a href='module_comparison.html' style='color: #007bff;'>Compare with Class Teacher Module</a></li>";
    echo "</ol>";
    
    echo "<h3>If tests failed:</h3>";
    echo "<ol>";
    echo "<li>Run <a href='final_subject_teacher_fix.php' style='color: #dc3545;'>final_subject_teacher_fix.php</a> to recreate all tables</li>";
    echo "<li>Check database connection settings in Includes/dbcon.php</li>";
    echo "<li>Verify XAMPP MySQL service is running</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Critical Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
