<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';

echo "<h2>Subject Teacher Database Fix</h2>";

// 1. Check existing tables
echo "<h3>1. Checking Database Tables</h3>";
$result = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "<p>Existing tables:</p><ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// 2. Create missing tables
echo "<h3>2. Creating Missing Tables</h3>";

// Create tblsubjectteacher_student table
$createSubjectTeacherStudent = "
CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `subjectTeacherId` int(11) NOT NULL,
  `studentId` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`),
  KEY `subjectTeacherId` (`subjectTeacherId`),
  KEY `studentId` (`studentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($createSubjectTeacherStudent)) {
    echo "<p style='color:green'>✅ Created tblsubjectteacher_student table</p>";
} else {
    echo "<p style='color:red'>❌ Error creating tblsubjectteacher_student: " . $conn->error . "</p>";
}

// Create tblsubjectattendance table if it doesn't exist
$createSubjectAttendance = "
CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
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

if ($conn->query($createSubjectAttendance)) {
    echo "<p style='color:green'>✅ Created/Verified tblsubjectattendance table</p>";
} else {
    echo "<p style='color:red'>❌ Error creating tblsubjectattendance: " . $conn->error . "</p>";
}

// 3. Check if we need to add a subjectCode column to tblsubjects
echo "<h3>3. Checking tblsubjects Structure</h3>";
$checkSubjectCode = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'subjectCode'");
if ($checkSubjectCode->num_rows == 0) {
    $addSubjectCode = "ALTER TABLE tblsubjects ADD COLUMN subjectCode VARCHAR(10) AFTER subjectName";
    if ($conn->query($addSubjectCode)) {
        echo "<p style='color:green'>✅ Added subjectCode column to tblsubjects</p>";
        
        // Update existing subjects with codes
        $updateCodes = [
            "UPDATE tblsubjects SET subjectCode = 'MATH' WHERE subjectName LIKE '%math%'",
            "UPDATE tblsubjects SET subjectCode = 'ENG' WHERE subjectName LIKE '%english%'",
            "UPDATE tblsubjects SET subjectCode = 'SCI' WHERE subjectName LIKE '%science%'",
            "UPDATE tblsubjects SET subjectCode = 'HIST' WHERE subjectName LIKE '%history%'",
            "UPDATE tblsubjects SET subjectCode = CONCAT('SUB', Id) WHERE subjectCode IS NULL"
        ];
        
        foreach ($updateCodes as $update) {
            $conn->query($update);
        }
        echo "<p style='color:green'>✅ Updated subject codes</p>";
    } else {
        echo "<p style='color:red'>❌ Error adding subjectCode: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ subjectCode column already exists</p>";
}

// 4. Populate tblsubjectteacher_student with sample data
echo "<h3>4. Populating Subject Teacher-Student Relationships</h3>";

// First check if we have students and the subject teacher
$checkStudents = $conn->query("SELECT COUNT(*) as count FROM tblstudents");
$studentCount = $checkStudents->fetch_assoc()['count'];

$checkSubjectTeacher = $conn->query("SELECT Id, subjectId FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'");
$subjectTeacher = $checkSubjectTeacher->fetch_assoc();

if ($studentCount > 0 && $subjectTeacher) {
    echo "<p>Found $studentCount students and subject teacher (ID: {$subjectTeacher['Id']})</p>";
    
    // Assign first 10 students to this subject teacher
    $assignStudents = "
    INSERT IGNORE INTO tblsubjectteacher_student (subjectTeacherId, studentId)
    SELECT {$subjectTeacher['Id']}, Id 
    FROM tblstudents 
    LIMIT 10";
    
    if ($conn->query($assignStudents)) {
        $assignedCount = $conn->affected_rows;
        echo "<p style='color:green'>✅ Assigned $assignedCount students to subject teacher</p>";
    } else {
        echo "<p style='color:red'>❌ Error assigning students: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ No students found or subject teacher not found. Creating sample data...</p>";
    
    // Create sample students if none exist
    if ($studentCount == 0) {
        $createSampleStudents = "
        INSERT INTO tblstudents (firstName, lastName, admissionNumber, classId, classArmId, dateCreated) VALUES
        ('Alice', 'Johnson', 'STU001', 1, 1, NOW()),
        ('Bob', 'Smith', 'STU002', 1, 1, NOW()),
        ('Carol', 'Davis', 'STU003', 1, 1, NOW()),
        ('David', 'Wilson', 'STU004', 1, 1, NOW()),
        ('Emma', 'Brown', 'STU005', 1, 1, NOW())";
        
        if ($conn->query($createSampleStudents)) {
            echo "<p style='color:green'>✅ Created sample students</p>";
        } else {
            echo "<p style='color:red'>❌ Error creating sample students: " . $conn->error . "</p>";
        }
    }
}

// 5. Test the fixed query
echo "<h3>5. Testing Fixed Query</h3>";
if ($subjectTeacher) {
    $testQuery = "SELECT 
            s.subjectName,
            s.Id as subjectId,
            s.subjectCode,
            COUNT(DISTINCT sts.studentId) as totalStudents,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() THEN sa.studentId END) as todayAttendance,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 1 THEN sa.studentId END) as todayPresent,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 0 THEN sa.studentId END) as todayAbsent
          FROM tblsubjectteachers st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          LEFT JOIN tblsubjectteacher_student sts ON sts.subjectTeacherId = st.Id
          LEFT JOIN tblsubjectattendance sa ON sa.subjectTeacherId = st.Id
          WHERE st.Id = {$subjectTeacher['Id']}
          GROUP BY s.Id";
    
    $testResult = $conn->query($testQuery);
    if ($testResult) {
        $testData = $testResult->fetch_assoc();
        echo "<p style='color:green'>✅ Query executed successfully!</p>";
        echo "<p>Results:</p>";
        echo "<ul>";
        echo "<li>Subject: " . ($testData['subjectName'] ?? 'Not found') . "</li>";
        echo "<li>Subject Code: " . ($testData['subjectCode'] ?? 'Not set') . "</li>";
        echo "<li>Total Students: " . ($testData['totalStudents'] ?? 0) . "</li>";
        echo "<li>Today's Attendance: " . ($testData['todayAttendance'] ?? 0) . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:red'>❌ Query failed: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<h3>6. Ready to Access Subject Teacher Dashboard</h3>";
echo "<div style='padding:15px; background:#e6f7e6; border:1px solid #4CAF50; border-radius:5px;'>";
echo "<p><strong>Database setup complete!</strong></p>";
echo "<p><a href='SubjectTeacher/index.php' target='_blank' style='background:#4CAF50; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>➤ Access Subject Teacher Dashboard</a></p>";
echo "</div>";

echo "<h3>7. Reference - Class Teacher Structure</h3>";
echo "<p>You can now compare the Subject Teacher module with the Class Teacher module for reference.</p>";
echo "<p><a href='ClassTeacher/index.php' target='_blank' style='background:#2196F3; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>➤ View Class Teacher Dashboard (Reference)</a></p>";
?>
