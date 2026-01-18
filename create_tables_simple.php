<?php
require_once('Includes/dbcon.php');

echo "<h2>🔧 Database Table Creation Script</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { color: blue; }
    hr { margin: 20px 0; }
</style>";

// Check if tblsubjectteacher table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteacher'");
if (mysqli_num_rows($result) > 0) {
    echo "<p class='success'>✓ Table 'tblsubjectteacher' already exists!</p>";
} else {
    echo "<p class='warning'>⚠ Table 'tblsubjectteacher' does not exist. Creating it now...</p>";
    
    // Create tblsubjectteacher table
    $createTable = "CREATE TABLE `tblsubjectteacher` (
        `Id` int(10) NOT NULL AUTO_INCREMENT,
        `firstName` varchar(255) NOT NULL,
        `lastName` varchar(255) NOT NULL,
        `emailAddress` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `phoneNo` varchar(20) DEFAULT NULL,
        `subjectId` int(10) NOT NULL,
        `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`),
        UNIQUE KEY `emailAddress` (`emailAddress`),
        KEY `subjectId` (`subjectId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    if (mysqli_query($conn, $createTable)) {
        echo "<p class='success'>✓ Table 'tblsubjectteacher' created successfully!</p>";
        
        // Insert sample data
        echo "<p class='info'>Creating sample Subject Teacher...</p>";
        
        // First, get a subject ID (or create one if none exist)
        $subjectResult = mysqli_query($conn, "SELECT Id FROM tblsubjects LIMIT 1");
        if (mysqli_num_rows($subjectResult) > 0) {
            $subject = mysqli_fetch_assoc($subjectResult);
            $subjectId = $subject['Id'];
        } else {
            // Create a sample subject first
            $createSubject = "INSERT INTO tblsubjects (subjectName, subjectCode) VALUES ('Mathematics', 'MATH101')";
            if (mysqli_query($conn, $createSubject)) {
                $subjectId = mysqli_insert_id($conn);
                echo "<p class='success'>✓ Sample subject 'Mathematics' created!</p>";
            } else {
                echo "<p class='error'>✗ Failed to create sample subject</p>";
                $subjectId = 1; // fallback
            }
        }
        
        // Hash the password
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        // Insert sample teacher
        $insertTeacher = "INSERT INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                         VALUES ('John', 'Smith', 'john.smith@email.com', ?, '1234567890', ?)";
        $stmt = $conn->prepare($insertTeacher);
        $stmt->bind_param('si', $hashedPassword, $subjectId);
        
        if ($stmt->execute()) {
            echo "<p class='success'>✓ Sample Subject Teacher created!</p>";
            echo "<p class='info'>Login credentials: john.smith@email.com / password123</p>";
        } else {
            echo "<p class='error'>✗ Failed to create sample teacher: " . $stmt->error . "</p>";
        }
        
    } else {
        echo "<p class='error'>✗ Failed to create table: " . mysqli_error($conn) . "</p>";
    }
}

echo "<hr>";

// Create other necessary tables if they don't exist
$tables = [
    'tblsubjectattendance' => "CREATE TABLE `tblsubjectattendance` (
        `Id` int(10) NOT NULL AUTO_INCREMENT,
        `studentId` int(10) NOT NULL,
        `subjectTeacherId` int(10) NOT NULL,
        `subjectId` int(10) NOT NULL,
        `classId` int(10) NOT NULL,
        `classArmId` int(10) NOT NULL,
        `status` tinyint(1) NOT NULL DEFAULT '0',
        `date` date NOT NULL,
        `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`),
        KEY `studentId` (`studentId`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1",
    
    'tblsubjectteacher_student' => "CREATE TABLE `tblsubjectteacher_student` (
        `Id` int(10) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(10) NOT NULL,
        `studentId` int(10) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`),
        UNIQUE KEY `unique_assignment` (`subjectTeacherId`, `studentId`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1"
];

foreach ($tables as $tableName => $createSQL) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p class='success'>✓ Table '$tableName' already exists</p>";
    } else {
        if (mysqli_query($conn, $createSQL)) {
            echo "<p class='success'>✓ Table '$tableName' created successfully!</p>";
        } else {
            echo "<p class='error'>✗ Failed to create table '$tableName': " . mysqli_error($conn) . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Try logging in with: <strong>john.smith@email.com</strong> / <strong>password123</strong></li>";
echo "<li>Go to <a href='index.php'>Main Login Page</a></li>";
echo "<li>Select 'Subject Teacher' and use the credentials above</li>";
echo "<li>Test the Take Attendance functionality</li>";
echo "</ol>";

echo "<p><a href='SubjectTeacher/navigation_test_fixed.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>→ Go to Navigation Test</a></p>";
?>
