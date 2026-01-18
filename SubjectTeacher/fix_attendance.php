<?php
// Update attendance table to add missing columns and fix issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
session_start();

echo "<div style='font-family: Arial, sans-serif; padding: 20px;'>";
echo "<h2>Subject Teacher System Fix</h2>";

// Step 1: Verify subject teacher data
if (isset($_SESSION['userId'])) {
    $teacherId = $_SESSION['userId'];
    
    // Check subject teacher record
    $query = "SELECT st.*, s.subjectName, s.subjectCode 
              FROM tblsubjectteacher st
              LEFT JOIN tblsubjects s ON s.Id = st.subjectId
              WHERE st.Id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<h3>Subject Teacher Information</h3>";
        echo "<p>Teacher ID: " . $row['Id'] . "</p>";
        echo "<p>Name: " . $row['firstName'] . " " . $row['lastName'] . "</p>";
        echo "<p>Subject ID: " . ($row['subjectId'] ?? 'Not set') . "</p>";
        echo "<p>Subject: " . ($row['subjectName'] ?? 'Not found') . "</p>";
        echo "<p>Subject Code: " . ($row['subjectCode'] ?? 'Not found') . "</p>";
        
        // Update session with subject information
        $_SESSION['subjectId'] = $row['subjectId'];
        $_SESSION['subjectName'] = $row['subjectName'];
        $_SESSION['subjectCode'] = $row['subjectCode'];
        
        if ($row['subjectId'] && $row['subjectName']) {
            echo "<p style='color: green;'>✅ Subject information is valid and has been updated in your session</p>";
        } else {
            echo "<p style='color: red;'>❌ Subject information is incomplete</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Teacher record not found!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No user ID in session. Please log in again.</p>";
}

// Original table structure checks continue below
echo "<h3>Checking Database Structure:</h3>";

// Check tblsubjectattendance structure
echo "<h3>Table Structure:</h3>";
$checkStructureQuery = "DESCRIBE tblsubjectattendance";
$structureResult = $conn->query($checkStructureQuery);

if ($structureResult) {
    echo "<pre>";
    echo "Column Name | Type | Null | Key | Default | Extra\n";
    echo "--------------------------------------------------------\n";
    while ($column = $structureResult->fetch_assoc()) {
        echo $column['Field'] . " | " . $column['Type'] . " | " . $column['Null'] . " | " . 
             $column['Key'] . " | " . $column['Default'] . " | " . $column['Extra'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red'>Error checking table structure: " . $conn->error . "</p>";
}

// Check if remarks column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'remarks'";
$result = $conn->query($checkColumnQuery);

if (!$result) {
    echo "<p style='color:red'>Error checking for remarks column: " . $conn->error . "</p>";
} elseif ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN remarks VARCHAR(255) NULL AFTER status";
    if ($conn->query($addColumnQuery)) {
        echo "<p style='color:green'>Successfully added 'remarks' column to tblsubjectattendance table.</p>";
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>'remarks' column already exists in tblsubjectattendance table.</p>";
}

// Check if subjectId column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'subjectId'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN subjectId INT NULL AFTER studentId";
    if ($conn->query($addColumnQuery)) {
        echo "<p style='color:green'>Successfully added 'subjectId' column to tblsubjectattendance table.</p>";
        
        // Update existing records with subject ID from subject teachers table        $updateQuery = "UPDATE tblsubjectattendance sa 
                       JOIN tblsubjectteacher st ON sa.teacherId = st.Id 
                       SET sa.subjectId = st.subjectId";
        if ($conn->query($updateQuery)) {
            echo "<p style='color:green'>Successfully updated subjectId values in existing records.</p>";
        } else {
            echo "<p style='color:red'>Error updating records: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>'subjectId' column already exists in tblsubjectattendance table.</p>";
}

// Check if subjectTeacherId column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'subjectTeacherId'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN subjectTeacherId INT NULL AFTER subjectId";
    if ($conn->query($addColumnQuery)) {
        echo "<p style='color:green'>Successfully added 'subjectTeacherId' column to tblsubjectattendance table.</p>";
        
        // Update existing records with teacher ID based on subject        $updateQuery = "UPDATE tblsubjectattendance sa 
                       JOIN tblsubjectteacher st ON sa.subjectId = st.subjectId 
                       SET sa.subjectTeacherId = st.Id, sa.teacherId = st.Id";
        if ($conn->query($updateQuery)) {
            echo "<p style='color:green'>Successfully updated subjectTeacherId values in existing records.</p>";
        } else {
            echo "<p style='color:red'>Error updating records: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>'subjectTeacherId' column already exists in tblsubjectattendance table.</p>";
}

// Check if teacherId column exists
$checkColumnQuery = "SHOW COLUMNS FROM tblsubjectattendance LIKE 'teacherId'";
$result = $conn->query($checkColumnQuery);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $addColumnQuery = "ALTER TABLE tblsubjectattendance ADD COLUMN teacherId INT NULL AFTER studentId";
    if ($conn->query($addColumnQuery)) {
        echo "<p style='color:green'>Successfully added 'teacherId' column to tblsubjectattendance table.</p>";
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>'teacherId' column already exists in tblsubjectattendance table.</p>";
}

// Check if tblsubjectteacher_student table exists
$checkTableQuery = "SHOW TABLES LIKE 'tblsubjectteacher_student'";
$result = $conn->query($checkTableQuery);

if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $createTableQuery = "CREATE TABLE `tblsubjectteacher_student` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subjectTeacherId` int(11) NOT NULL,
        `studentId` int(11) NOT NULL,
        `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `subjectTeacherId` (`subjectTeacherId`),
        KEY `studentId` (`studentId`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($createTableQuery)) {
        echo "<p style='color:green'>Successfully created tblsubjectteacher_student table.</p>";
    } else {
        echo "<p style='color:red'>Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>tblsubjectteacher_student table already exists.</p>";
}

echo "<p><a href='takeAttendance.php'>Go to Take Attendance page</a></p>";
echo "<p><a href='index.php'>Return to Dashboard</a></p>";
?>
