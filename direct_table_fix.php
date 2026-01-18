<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';

echo "<h2>Direct Database Table Fix</h2>";

// Create the missing table directly
$sql = "CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectTeacherId` int(11) NOT NULL,
  `studentId` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`),
  KEY `subjectTeacherId` (`subjectTeacherId`),
  KEY `studentId` (`studentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

echo "<h3>Creating tblsubjectteacher_student table...</h3>";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✅ Table tblsubjectteacher_student created successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating table: " . $conn->error . "</p>";
}

// Verify the table exists
$check = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
if ($check->num_rows > 0) {
    echo "<p style='color:green;'>✅ Table verification: tblsubjectteacher_student exists</p>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE tblsubjectteacher_student");
    echo "<h4>Table Structure:</h4>";
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Table still doesn't exist after creation attempt</p>";
}

// Create tblsubjectattendance if it doesn't exist
$sqlAttendance = "CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `studentId` int(11) NOT NULL,
  `subjectTeacherId` int(11) NOT NULL,
  `subjectId` int(11) NOT NULL,
  `classId` int(11) NOT NULL,
  `classArmId` int(11) NOT NULL,
  `sessionTermId` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '1=Present, 0=Absent',
  `date` date NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_attendance` (`studentId`, `subjectTeacherId`, `date`),
  KEY `studentId` (`studentId`),
  KEY `subjectTeacherId` (`subjectTeacherId`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

echo "<h3>Creating tblsubjectattendance table...</h3>";
if ($conn->query($sqlAttendance) === TRUE) {
    echo "<p style='color:green;'>✅ Table tblsubjectattendance created successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating table: " . $conn->error . "</p>";
}

// Add sample data to link subject teacher with students
echo "<h3>Adding Sample Data...</h3>";

// Get the subject teacher ID
$getTeacher = $conn->query("SELECT Id FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'");
if ($getTeacher->num_rows > 0) {
    $teacher = $getTeacher->fetch_assoc();
    $teacherId = $teacher['Id'];
    echo "<p>Found subject teacher ID: $teacherId</p>";
    
    // Get some students
    $getStudents = $conn->query("SELECT Id FROM tblstudents LIMIT 5");
    if ($getStudents->num_rows > 0) {
        $insertCount = 0;
        while ($student = $getStudents->fetch_assoc()) {
            $studentId = $student['Id'];
            $insertSql = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES ($teacherId, $studentId)";
            if ($conn->query($insertSql)) {
                $insertCount++;
            }
        }
        echo "<p style='color:green;'>✅ Assigned $insertCount students to subject teacher</p>";
    } else {
        // Create sample students if none exist
        $createStudents = "INSERT IGNORE INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId, dateCreated) VALUES
        ('Alice', 'Johnson', 'STU001', 1, 1, NOW()),
        ('Bob', 'Smith', 'STU002', 1, 1, NOW()),
        ('Carol', 'Davis', 'STU003', 1, 1, NOW()),
        ('David', 'Wilson', 'STU004', 1, 1, NOW()),
        ('Emma', 'Brown', 'STU005', 1, 1, NOW())";
        
        if ($conn->query($createStudents)) {
            echo "<p style='color:green;'>✅ Created sample students</p>";
            
            // Now assign them
            $assignSql = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId) 
                         SELECT $teacherId, Id FROM tblstudents LIMIT 5";
            if ($conn->query($assignSql)) {
                echo "<p style='color:green;'>✅ Assigned sample students to subject teacher</p>";
            }
        }
    }
} else {
    echo "<p style='color:red;'>❌ Subject teacher not found. Please run the login fix first.</p>";
}

// Test the problematic query from index.php
echo "<h3>Testing the Index Query...</h3>";
if (isset($teacherId)) {
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
          WHERE st.Id = $teacherId
          GROUP BY s.Id";
    
    $result = $conn->query($testQuery);
    if ($result) {
        echo "<p style='color:green;'>✅ Test query executed successfully!</p>";
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo "<table border='1' style='border-collapse:collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($data as $key => $value) {
                echo "<tr><td>$key</td><td>$value</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Query returned no results, but no error.</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Test query failed: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>Database Status Summary</h3>";
$tables = ['tblsubjectteachers', 'tblsubjectteacher_student', 'tblsubjectattendance', 'tblsubjects', 'tblstudents'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "<p style='color:green;'>✅ $table exists ($count records)</p>";
    } else {
        echo "<p style='color:red;'>❌ $table missing</p>";
    }
}

echo "<p><strong>Ready to test:</strong> <a href='SubjectTeacher/index.php' target='_blank' style='background:green;color:white;padding:10px;text-decoration:none;border-radius:5px;'>Access Subject Teacher Dashboard</a></p>";
?>
