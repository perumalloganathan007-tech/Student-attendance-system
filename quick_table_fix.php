<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Quick Database Table Check</h1>";

try {
    include 'Includes/dbcon.php';
    
    echo "<h2>Database Connection Status</h2>";
    if ($conn) {
        echo "<p style='color:green;'>✅ Connected to database successfully</p>";
        echo "<p>Database: " . $conn->query("SELECT DATABASE()")->fetch_row()[0] . "</p>";
    } else {
        echo "<p style='color:red;'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<h2>Required Tables Check</h2>";
    $requiredTables = [
        'tblsubjectteacher_student',
        'tblsubjectattendance',
        'tblsubjects',
        'tblstudents',
        'tblsubjectteacher'
    ];
    
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color:green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color:red;'>❌ Table '$table' missing</p>";
        }
    }
    
    echo "<h2>Quick Fix - Create Missing Tables</h2>";
    
    // Create tblsubjectteacher_student if missing
    $createStudentTable = "CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `subjectTeacherId` int(11) NOT NULL,
      `studentId` int(11) NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`),
      KEY `subjectTeacherId` (`subjectTeacherId`),
      KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createStudentTable)) {
        echo "<p style='color:green;'>✅ tblsubjectteacher_student table created/verified</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating tblsubjectteacher_student: " . $conn->error . "</p>";
    }
    
    // Create tblsubjectattendance if missing
    $createAttendanceTable = "CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
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
      KEY `date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createAttendanceTable)) {
        echo "<p style='color:green;'>✅ tblsubjectattendance table created/verified</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating tblsubjectattendance: " . $conn->error . "</p>";
    }
    
    echo "<h2>Final Verification</h2>";
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color:green;'>✅ Table '$table' now exists</p>";
        } else {
            echo "<p style='color:red;'>❌ Table '$table' still missing</p>";
        }
    }
    
    echo "<h2>Next Steps</h2>";
    echo "<p>1. <a href='subjectTeacherLogin.php'>Test Subject Teacher Login</a></p>";
    echo "<p>2. <a href='SubjectTeacher/index.php'>Access Subject Teacher Dashboard</a></p>";
    echo "<p>3. <a href='module_comparison.html'>Compare with Class Teacher Module</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
