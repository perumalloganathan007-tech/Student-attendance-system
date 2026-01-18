<?php
/**
 * Emergency Database Table Fix for Subject Teacher Module
 * This script will check for and create missing tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once('../Includes/dbcon.php');

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Emergency Table Fix - Subject Teacher</title>";
echo "<link href='../vendor/bootstrap/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo ".check-pass { color: #28a745; } .check-fail { color: #dc3545; } .check-warn { color: #ffc107; }";
echo "</style>";
echo "</head><body class='bg-light'>";
echo "<div class='container mt-4'>";
echo "<h2><i class='fas fa-database'></i> Emergency Database Table Fix</h2>";

$issues_fixed = [];
$errors = [];

// 1. Check existing tables
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-info text-white'><h5>Current Database Tables</h5></div>";
echo "<div class='card-body'>";

$tables_query = "SHOW TABLES";
$result = mysqli_query($conn, $tables_query);
$existing_tables = [];

if ($result) {
    echo "<p>Existing tables in the database:</p><ul>";
    while ($row = mysqli_fetch_array($result)) {
        $table_name = $row[0];
        $existing_tables[] = $table_name;
        echo "<li><code>$table_name</code></li>";
    }
    echo "</ul>";
} else {
    echo "<span class='check-fail'>✗ Could not list database tables: " . mysqli_error($conn) . "</span>";
}

echo "</div></div>";

// 2. Check for specific tables we need
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-warning text-dark'><h5>Required Table Check</h5></div>";
echo "<div class='card-body'>";

$required_tables = [
    'tblsubjectteacher',
    'tblsubjectteacher_student', 
    'tblsubjectattendance',
    'tblsubjects',
    'tblstudents',
    'tblsessionterm'
];

$missing_tables = [];
foreach ($required_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "<span class='check-pass'>✓ Table '$table' exists</span><br>";
    } else {
        echo "<span class='check-fail'>✗ Table '$table' MISSING</span><br>";
        $missing_tables[] = $table;
    }
}

echo "</div></div>";

// 3. Create missing tables
if (!empty($missing_tables)) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-danger text-white'><h5>Creating Missing Tables</h5></div>";
    echo "<div class='card-body'>";
    
    // Create tblsubjectteacher
    if (in_array('tblsubjectteacher', $missing_tables)) {
        echo "<h6>Creating tblsubjectteacher...</h6>";
        $create_subject_teacher = "
        CREATE TABLE `tblsubjectteacher` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `firstName` varchar(255) NOT NULL,
            `lastName` varchar(255) NOT NULL,
            `emailAddress` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `phoneNo` varchar(255) NOT NULL,
            `subjectId` int(11) NOT NULL,
            `dateCreated` varchar(255) NOT NULL,
            PRIMARY KEY (`Id`),
            UNIQUE KEY `emailAddress` (`emailAddress`),
            KEY `subjectId` (`subjectId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        if (mysqli_query($conn, $create_subject_teacher)) {
            echo "<span class='check-pass'>✓ Created tblsubjectteacher table</span><br>";
            $issues_fixed[] = "Created tblsubjectteacher table";
        } else {
            echo "<span class='check-fail'>✗ Failed to create tblsubjectteacher: " . mysqli_error($conn) . "</span><br>";
            $errors[] = "Failed to create tblsubjectteacher";
        }
    }
    
    // Create tblsubjectteacher_student 
    if (in_array('tblsubjectteacher_student', $missing_tables)) {
        echo "<h6>Creating tblsubjectteacher_student...</h6>";
        $create_teacher_student = "
        CREATE TABLE `tblsubjectteacher_student` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `subjectTeacherId` int(11) NOT NULL,
            `studentId` int(11) NOT NULL,
            `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`Id`),
            UNIQUE KEY `unique_assignment` (`subjectTeacherId`, `studentId`),
            KEY `subjectTeacherId` (`subjectTeacherId`),
            KEY `studentId` (`studentId`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        if (mysqli_query($conn, $create_teacher_student)) {
            echo "<span class='check-pass'>✓ Created tblsubjectteacher_student table</span><br>";
            $issues_fixed[] = "Created tblsubjectteacher_student table";
        } else {
            echo "<span class='check-fail'>✗ Failed to create tblsubjectteacher_student: " . mysqli_error($conn) . "</span><br>";
            $errors[] = "Failed to create tblsubjectteacher_student";
        }
    }
    
    // Create tblsubjectattendance
    if (in_array('tblsubjectattendance', $missing_tables)) {
        echo "<h6>Creating tblsubjectattendance...</h6>";
        $create_attendance = "
        CREATE TABLE `tblsubjectattendance` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `studentId` int(11) NOT NULL,
            `subjectTeacherId` int(11) NOT NULL,
            `subjectId` int(11) NOT NULL,
            `date` date NOT NULL,
            `status` tinyint(1) NOT NULL,
            `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`Id`),
            UNIQUE KEY `unique_attendance` (`studentId`, `subjectTeacherId`, `date`),
            KEY `idx_attendance_lookup` (`subjectTeacherId`, `date`, `status`),
            KEY `idx_student_date` (`studentId`, `date`),
            KEY `idx_subject_date` (`subjectId`, `date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        if (mysqli_query($conn, $create_attendance)) {
            echo "<span class='check-pass'>✓ Created tblsubjectattendance table</span><br>";
            $issues_fixed[] = "Created tblsubjectattendance table";
        } else {
            echo "<span class='check-fail'>✗ Failed to create tblsubjectattendance: " . mysqli_error($conn) . "</span><br>";
            $errors[] = "Failed to create tblsubjectattendance";
        }
    }
    
    // Create tblsubjects if missing
    if (in_array('tblsubjects', $missing_tables)) {
        echo "<h6>Creating tblsubjects...</h6>";
        $create_subjects = "
        CREATE TABLE `tblsubjects` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `subjectName` varchar(255) NOT NULL,
            `subjectCode` varchar(50) NOT NULL,
            `dateCreated` varchar(255) NOT NULL,
            PRIMARY KEY (`Id`),
            UNIQUE KEY `subjectCode` (`subjectCode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        if (mysqli_query($conn, $create_subjects)) {
            echo "<span class='check-pass'>✓ Created tblsubjects table</span><br>";
            $issues_fixed[] = "Created tblsubjects table";
        } else {
            echo "<span class='check-fail'>✗ Failed to create tblsubjects: " . mysqli_error($conn) . "</span><br>";
            $errors[] = "Failed to create tblsubjects";
        }
    }
    
    // Create tblsessionterm if missing
    if (in_array('tblsessionterm', $missing_tables)) {
        echo "<h6>Creating tblsessionterm...</h6>";
        $create_session = "
        CREATE TABLE `tblsessionterm` (
            `Id` int(11) NOT NULL AUTO_INCREMENT,
            `sessionName` varchar(255) NOT NULL,
            `termName` varchar(255) NOT NULL,
            `isActive` tinyint(1) NOT NULL DEFAULT '0',
            `dateCreated` varchar(255) NOT NULL,
            PRIMARY KEY (`Id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
        
        if (mysqli_query($conn, $create_session)) {
            echo "<span class='check-pass'>✓ Created tblsessionterm table</span><br>";
            $issues_fixed[] = "Created tblsessionterm table";
        } else {
            echo "<span class='check-fail'>✗ Failed to create tblsessionterm: " . mysqli_error($conn) . "</span><br>";
            $errors[] = "Failed to create tblsessionterm";
        }
    }
    
    echo "</div></div>";
}

// 4. Add sample data if tables were created
if (!empty($issues_fixed)) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-success text-white'><h5>Adding Sample Data</h5></div>";
    echo "<div class='card-body'>";
    
    // Add sample session/term
    $check_session = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblsessionterm");
    if ($check_session) {
        $session_count = mysqli_fetch_assoc($check_session)['count'];
        if ($session_count == 0) {
            $insert_session = "INSERT INTO tblsessionterm (sessionName, termName, isActive, dateCreated) VALUES ('2023/2024', 'First Term', 1, NOW())";
            if (mysqli_query($conn, $insert_session)) {
                echo "<span class='check-pass'>✓ Added default session/term</span><br>";
                $issues_fixed[] = "Added default session/term";
            }
        }
    }
    
    // Add sample subject
    $check_subjects = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblsubjects");
    if ($check_subjects) {
        $subject_count = mysqli_fetch_assoc($check_subjects)['count'];
        if ($subject_count == 0) {
            $insert_subject = "INSERT INTO tblsubjects (subjectName, subjectCode, dateCreated) VALUES ('Mathematics', 'MATH101', NOW())";
            if (mysqli_query($conn, $insert_subject)) {
                echo "<span class='check-pass'>✓ Added sample subject (Mathematics)</span><br>";
                $issues_fixed[] = "Added sample subject";
            }
        }
    }
    
    // Add sample subject teacher
    $check_teachers = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblsubjectteacher");
    if ($check_teachers) {
        $teacher_count = mysqli_fetch_assoc($check_teachers)['count'];
        if ($teacher_count == 0) {
            $password_hash = password_hash('123456', PASSWORD_DEFAULT);
            $insert_teacher = "INSERT INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId, dateCreated) 
                              VALUES ('John', 'Smith', 'john.smith@example.com', '$password_hash', '1234567890', 1, NOW())";
            if (mysqli_query($conn, $insert_teacher)) {
                echo "<span class='check-pass'>✓ Added sample subject teacher (john.smith@example.com / password: 123456)</span><br>";
                $issues_fixed[] = "Added sample subject teacher";
            }
        }
    }
    
    echo "</div></div>";
}

// 5. Summary
echo "<div class='card mb-3'>";
echo "<div class='card-header bg-primary text-white'><h5>Summary</h5></div>";
echo "<div class='card-body'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h6 class='text-success'>Issues Fixed (" . count($issues_fixed) . ")</h6>";
foreach ($issues_fixed as $fix) {
    echo "<small class='text-success'>✓ $fix</small><br>";
}
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h6 class='text-danger'>Errors (" . count($errors) . ")</h6>";
foreach ($errors as $error) {
    echo "<small class='text-danger'>✗ $error</small><br>";
}
echo "</div>";
echo "</div>";

if (empty($errors)) {
    echo "<div class='alert alert-success mt-3'>";
    echo "<h6><i class='fas fa-check-circle'></i> Database Tables Fixed!</h6>";
    echo "<p>All required tables have been created successfully. You can now:</p>";
    echo "<ul>";
    echo "<li>Test the viewTodayAttendance.php page</li>";
    echo "<li>Login as Subject Teacher using: john.smith@example.com / 123456</li>";
    echo "<li>Add more subjects and teachers as needed</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger mt-3'>";
    echo "<h6><i class='fas fa-times-circle'></i> Some Issues Remain</h6>";
    echo "<p>There were errors creating some tables. Please check the database permissions and try again.</p>";
    echo "</div>";
}

echo "</div></div>";

// Action buttons
echo "<div class='card'>";
echo "<div class='card-header bg-secondary text-white'><h5>Next Steps</h5></div>";
echo "<div class='card-body text-center'>";

echo "<div class='row'>";
echo "<div class='col-md-3'>";
echo "<a href='viewTodayAttendance.php' class='btn btn-primary btn-block'>";
echo "<i class='fas fa-eye'></i> Test Today's Attendance";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<a href='../subjectTeacherLogin.php' class='btn btn-success btn-block'>";
echo "<i class='fas fa-sign-in-alt'></i> Login as Subject Teacher";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<a href='test_attendance_dashboard.html' class='btn btn-info btn-block'>";
echo "<i class='fas fa-tachometer-alt'></i> Testing Dashboard";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<button onclick='location.reload()' class='btn btn-warning btn-block'>";
echo "<i class='fas fa-redo'></i> Refresh Status";
echo "</button>";
echo "</div>";

echo "</div>";

if (!empty($issues_fixed)) {
    echo "<div class='mt-3'>";
    echo "<div class='alert alert-info'>";
    echo "<h6>Sample Login Credentials Created:</h6>";
    echo "<p><strong>Email:</strong> john.smith@example.com<br>";
    echo "<strong>Password:</strong> 123456</p>";
    echo "<p>Use these credentials to login and test the Subject Teacher module.</p>";
    echo "</div>";
    echo "</div>";
}

echo "</div></div>";

echo "</div>";
echo "</body></html>";
?>
