<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';

echo "<h1>Fix Database Tables for Subject Teacher</h1>";

// 1. Check and create tblsubjectteacher_student if it doesn't exist
echo "<h2>Checking tblsubjectteacher_student table</h2>";
$checkTable = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
if($checkTable->num_rows == 0) {
    echo "Table does not exist. Creating...<br>";
    
    $createTable = "CREATE TABLE `tblsubjectteacher_student` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `studentId` (`studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if($conn->query($createTable)) {
        echo "✓ Table created successfully<br>";
    } else {
        echo "✗ Error creating table: " . $conn->error . "<br>";
    }
} else {
    echo "✓ tblsubjectteacher_student table exists<br>";
}

// 2. Check and create tblsubjectattendance if it doesn't exist
echo "<h2>Checking tblsubjectattendance table</h2>";
$checkTable = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
if($checkTable->num_rows == 0) {
    echo "Table does not exist. Creating...<br>";
    
    $createTable = "CREATE TABLE `tblsubjectattendance` (
        `Id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `status` tinyint(1) NOT NULL,
        `date` date NOT NULL,
        `sessionTermId` int(11) NOT NULL,
        `dateTimeTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if($conn->query($createTable)) {
        echo "✓ Table created successfully<br>";
    } else {
        echo "✗ Error creating table: " . $conn->error . "<br>";
    }
} else {
    echo "✓ tblsubjectattendance table exists<br>";
}

// 3. Assign students to subject teachers if none are assigned
echo "<h2>Checking student assignments</h2>";
$checkAssignments = $conn->query("SELECT COUNT(*) as count FROM tblsubjectteacher_student");
$result = $checkAssignments->fetch_assoc();

if($result['count'] == 0) {
    echo "No student assignments found. Adding sample assignments...<br>";
    
    // Get subject teachers
    $teacherQuery = "SELECT Id, subjectId FROM tblsubjectteachers";
    $teacherResult = $conn->query($teacherQuery);
    
    if($teacherResult->num_rows > 0) {
        // Get students
        $studentQuery = "SELECT Id FROM tblstudents";
        $studentResult = $conn->query($studentQuery);
        
        if($studentResult->num_rows > 0) {
            $students = [];
            while($student = $studentResult->fetch_assoc()) {
                $students[] = $student['Id'];
            }
            
            $inserted = 0;
            while($teacher = $teacherResult->fetch_assoc()) {
                // Assign 5-10 random students to each teacher
                $numToAssign = rand(5, 10);
                
                for($i = 0; $i < $numToAssign; $i++) {
                    if(count($students) > 0) {
                        $randomIndex = array_rand($students);
                        $studentId = $students[$randomIndex];
                        
                        $insertQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) 
                                      VALUES (?, ?)";
                        $insertStmt = $conn->prepare($insertQuery);
                        $insertStmt->bind_param("ii", $teacher['Id'], $studentId);
                        
                        if($insertStmt->execute()) {
                            $inserted++;
                            // Remove this student so they're not assigned again
                            array_splice($students, $randomIndex, 1);
                        }
                    }
                }
            }
            
            echo "✓ Assigned $inserted students to subject teachers<br>";
        } else {
            echo "No students found in database<br>";
        }
    } else {
        echo "No subject teachers found in database<br>";
    }
} else {
    echo "✓ Students are already assigned to subject teachers<br>";
}

// 4. Modify index.php to not fail if no attendance records exist
echo "<h2>Fixing index.php</h2>";

// Back up the original file
$indexPath = 'index.php';
$backupPath = 'index_backup_' . time() . '.php';

if(copy($indexPath, $backupPath)) {
    echo "✓ Created backup of index.php as $backupPath<br>";
    
    // Read the file content
    $content = file_get_contents($indexPath);
    
    // Add null coalescing operators to prevent errors with empty stats
    $content = str_replace('<?php echo $stats[\'totalStudents\']; ?>', 
                          '<?php echo $stats[\'totalStudents\'] ?? 0; ?>', $content);
                          
    $content = str_replace('<?php echo $stats[\'todayAttendance\'] ?: \'0\'; ?>', 
                          '<?php echo $stats[\'todayAttendance\'] ?? 0; ?>', $content);
                          
    $content = str_replace('<?php echo $stats[\'todayPresent\'] ?: \'0\'; ?>', 
                          '<?php echo $stats[\'todayPresent\'] ?? 0; ?>', $content);
                          
    $content = str_replace('<?php echo $stats[\'todayAbsent\'] ?: \'0\'; ?>', 
                          '<?php echo $stats[\'todayAbsent\'] ?? 0; ?>', $content);
    
    // Update session variables with default values if they're not set
    $sessionVarCheck = <<<'EOD'
$_SESSION['subjectName'] = $stats['subjectName'] ?? 'Unknown Subject';
$_SESSION['subjectId'] = $stats['subjectId'] ?? 0;
EOD;
    
    $content = str_replace('$_SESSION[\'subjectName\'] = $stats[\'subjectName\'];
$_SESSION[\'subjectId\'] = $stats[\'subjectId\'];', $sessionVarCheck, $content);
    
    if(file_put_contents($indexPath, $content)) {
        echo "✓ Updated index.php with null checks<br>";
    } else {
        echo "✗ Failed to update index.php<br>";
    }
} else {
    echo "✗ Failed to create backup of index.php<br>";
}

echo "<h2>Database Fix Complete</h2>";
echo "<p>Return to <a href='index.php'>Subject Teacher Dashboard</a></p>";

$conn->close();
?>
