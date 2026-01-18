<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';

echo "<h1>Complete Subject Teacher Database Fix</h1>";

// 1. Check and create all required tables
$tables = [
    'tblsubjectteacher_student' => "CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `subjectTeacherId` int(11) NOT NULL,
      `studentId` int(11) NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`),
      KEY `subjectTeacherId` (`subjectTeacherId`),
      KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    'tblsubjectattendance' => "CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

echo "<h2>Creating Required Tables</h2>";
foreach ($tables as $tableName => $sql) {
    if ($conn->query($sql)) {
        echo "<p style='color:green;'>✅ Table $tableName created/verified</p>";
    } else {
        echo "<p style='color:red;'>❌ Error with $tableName: " . $conn->error . "</p>";
    }
}

// 2. Ensure required columns exist
echo "<h2>Checking Table Columns</h2>";

// Check if subjectCode exists in tblsubjects
$checkSubjectCode = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'subjectCode'");
if ($checkSubjectCode->num_rows == 0) {
    $addColumn = "ALTER TABLE tblsubjects ADD COLUMN subjectCode VARCHAR(10) AFTER subjectName";
    if ($conn->query($addColumn)) {
        echo "<p style='color:green;'>✅ Added subjectCode column to tblsubjects</p>";
    } else {
        echo "<p style='color:red;'>❌ Error adding subjectCode: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green;'>✅ subjectCode column exists</p>";
}

// 3. Ensure sample data exists
echo "<h2>Setting Up Sample Data</h2>";

// Get subject teacher
$getTeacher = $conn->query("SELECT Id, subjectId FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'");
if ($getTeacher->num_rows > 0) {
    $teacher = $getTeacher->fetch_assoc();
    echo "<p>✅ Subject teacher found (ID: {$teacher['Id']}, Subject: {$teacher['subjectId']})</p>";
    
    // Ensure subject exists
    $checkSubject = $conn->query("SELECT * FROM tblsubjects WHERE Id = {$teacher['subjectId']}");
    if ($checkSubject->num_rows == 0) {
        // Create the subject
        $createSubject = "INSERT INTO tblsubjects (Id, subjectName, subjectCode) VALUES ({$teacher['subjectId']}, 'Mathematics', 'MATH')";
        if ($conn->query($createSubject)) {
            echo "<p style='color:green;'>✅ Created Mathematics subject</p>";
        }
    }
    
    // Ensure students exist
    $studentCount = $conn->query("SELECT COUNT(*) as count FROM tblstudents")->fetch_assoc()['count'];
    if ($studentCount == 0) {
        $createStudents = "INSERT INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId, dateCreated) VALUES
        ('Alice', 'Johnson', 'STU001', 1, 1, NOW()),
        ('Bob', 'Smith', 'STU002', 1, 1, NOW()),
        ('Carol', 'Davis', 'STU003', 1, 1, NOW()),
        ('David', 'Wilson', 'STU004', 1, 1, NOW()),
        ('Emma', 'Brown', 'STU005', 1, 1, NOW())";
        
        if ($conn->query($createStudents)) {
            echo "<p style='color:green;'>✅ Created 5 sample students</p>";
            $studentCount = 5;
        }
    }
    
    // Link students to subject teacher
    if ($studentCount > 0) {
        $linkStudents = "INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId)
                        SELECT {$teacher['Id']}, Id FROM tblstudents LIMIT 5";
        if ($conn->query($linkStudents)) {
            $linked = $conn->affected_rows;
            echo "<p style='color:green;'>✅ Linked $linked students to subject teacher</p>";
        }
    }
    
} else {
    echo "<p style='color:red;'>❌ Subject teacher john.smith@school.com not found!</p>";
    echo "<p>Please run the login fix first: <a href='complete_login_fix.php'>complete_login_fix.php</a></p>";
}

// 4. Test the exact query from index.php
echo "<h2>Testing Index.php Query</h2>";
if (isset($teacher)) {
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
          WHERE st.Id = {$teacher['Id']}
          GROUP BY s.Id";
    
    $result = $conn->query($testQuery);
    if ($result) {
        echo "<p style='color:green;'>✅ Index query works perfectly!</p>";
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo "<h4>Query Results:</h4>";
            echo "<table border='1' style='border-collapse:collapse; margin:10px 0;'>";
            foreach ($data as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color:red;'>❌ Query failed: " . $conn->error . "</p>";
    }
}

// 5. Final verification
echo "<h2>Final Database Status</h2>";
$requiredTables = [
    'tblsubjectteachers' => 'Subject Teachers',
    'tblsubjectteacher_student' => 'Teacher-Student Links', 
    'tblsubjectattendance' => 'Subject Attendance',
    'tblsubjects' => 'Subjects',
    'tblstudents' => 'Students'
];

echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
echo "<tr><th>Table</th><th>Description</th><th>Records</th><th>Status</th></tr>";

foreach ($requiredTables as $table => $desc) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check->num_rows > 0) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        $status = $count > 0 ? "✅ Ready" : "⚠️ Empty";
        $statusColor = $count > 0 ? "green" : "orange";
        echo "<tr><td>$table</td><td>$desc</td><td>$count</td><td style='color:$statusColor;'>$status</td></tr>";
    } else {
        echo "<tr><td>$table</td><td>$desc</td><td>-</td><td style='color:red;'>❌ Missing</td></tr>";
    }
}
echo "</table>";

// 6. Access links
echo "<h2>Ready to Access</h2>";
echo "<div style='background:#f0f8f0; padding:15px; border:1px solid #4CAF50; border-radius:5px; margin:20px 0;'>";
echo "<h3>Subject Teacher Dashboard is Ready!</h3>";
echo "<p><strong>Login Credentials:</strong><br>";
echo "Email: john.smith@school.com<br>";
echo "Password: Password@123</p>";
echo "<p><a href='SubjectTeacher/index.php' target='_blank' style='background:#4CAF50;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;font-weight:bold;'>Access Subject Teacher Dashboard</a></p>";
echo "</div>";

echo "<p><strong>For comparison:</strong> <a href='ClassTeacher/index.php' target='_blank' style='background:#2196F3;color:white;padding:8px 15px;text-decoration:none;border-radius:3px;'>View Class Teacher Module</a></p>";
?>
