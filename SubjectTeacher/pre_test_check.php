<?php
/**
 * Quick Pre-Test Verification
 * Run this before starting the main testing flow
 */

session_start();
require_once('../Includes/dbcon.php');

// Check if we're in a testing environment
$isTestMode = true;

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Pre-Test Verification</title>";
echo "<link href='../vendor/bootstrap/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>";
echo ".check-pass { color: #28a745; } .check-fail { color: #dc3545; } .check-warn { color: #ffc107; }";
echo "</style>";
echo "</head><body class='bg-light'>";
echo "<div class='container mt-4'>";
echo "<h2><i class='fas fa-check-circle'></i> Pre-Test Verification</h2>";

$issues = [];
$warnings = [];
$passes = [];

// 1. Check session
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>1. Session Check</h5></div>";
echo "<div class='card-body'>";

if (isset($_SESSION['userId'])) {
    echo "<span class='check-pass'>✓ Session active - User ID: " . $_SESSION['userId'] . "</span><br>";
    $passes[] = "Session active";
    
    if (isset($_SESSION['userType'])) {
        echo "<span class='check-pass'>✓ User type: " . $_SESSION['userType'] . "</span><br>";
        if ($_SESSION['userType'] !== 'SubjectTeacher') {
            echo "<span class='check-warn'>⚠ Warning: User type is not 'SubjectTeacher'</span><br>";
            $warnings[] = "User type mismatch";
        }
    } else {
        echo "<span class='check-fail'>✗ User type not set in session</span><br>";
        $issues[] = "Missing user type";
    }
    
    if (isset($_SESSION['subjectTeacherId'])) {
        echo "<span class='check-pass'>✓ Subject Teacher ID: " . $_SESSION['subjectTeacherId'] . "</span><br>";
    } else {
        echo "<span class='check-fail'>✗ Subject Teacher ID not set</span><br>";
        $issues[] = "Missing subject teacher ID";
    }
    
    if (isset($_SESSION['subjectId'])) {
        echo "<span class='check-pass'>✓ Subject ID: " . $_SESSION['subjectId'] . "</span><br>";
    } else {
        echo "<span class='check-warn'>⚠ Subject ID not set</span><br>";
        $warnings[] = "Missing subject ID";
    }
} else {
    echo "<span class='check-fail'>✗ No active session found</span><br>";
    $issues[] = "No session";
}

echo "</div></div>";

// 2. Database connection
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>2. Database Connection</h5></div>";
echo "<div class='card-body'>";

if ($conn && mysqli_ping($conn)) {
    echo "<span class='check-pass'>✓ Database connection active</span><br>";
    $passes[] = "Database connected";
} else {
    echo "<span class='check-fail'>✗ Database connection failed</span><br>";
    $issues[] = "Database connection failed";
}

echo "</div></div>";

// 3. Check critical tables
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>3. Critical Tables Check</h5></div>";
echo "<div class='card-body'>";

$critical_tables = [
    'tblsubjectattendance' => 'Attendance records',
    'tblsubjectteacher' => 'Subject teachers',
    'tblsubjectteacher_student' => 'Student assignments',
    'tblstudents' => 'Students',
    'tblsubjects' => 'Subjects'
];

foreach ($critical_tables as $table => $description) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "<span class='check-pass'>✓ Table '$table' exists ($description)</span><br>";
        
        // Check if table has data
        $count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$table`");
        if ($count_result) {
            $count = mysqli_fetch_assoc($count_result)['count'];
            if ($count > 0) {
                echo "<span class='check-pass'>  → Has $count records</span><br>";
            } else {
                echo "<span class='check-warn'>  → Table is empty</span><br>";
                if ($table === 'tblsubjectteacher_student') {
                    $warnings[] = "No student assignments";
                }
            }
        }
    } else {
        echo "<span class='check-fail'>✗ Table '$table' missing ($description)</span><br>";
        $issues[] = "Missing table: $table";
    }
}

echo "</div></div>";

// 4. Check attendance table structure
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>4. Attendance Table Structure</h5></div>";
echo "<div class='card-body'>";

$result = mysqli_query($conn, "DESCRIBE tblsubjectattendance");
if ($result) {
    $columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }
    
    $required_columns = ['Id', 'studentId', 'subjectTeacherId', 'subjectId', 'date', 'status'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (empty($missing_columns)) {
        echo "<span class='check-pass'>✓ All required columns present</span><br>";
        $passes[] = "Attendance table structure complete";
    } else {
        echo "<span class='check-fail'>✗ Missing columns: " . implode(', ', $missing_columns) . "</span><br>";
        $issues[] = "Incomplete attendance table structure";
    }
    
    echo "<small class='text-muted'>Columns found: " . implode(', ', $columns) . "</small><br>";
} else {
    echo "<span class='check-fail'>✗ Cannot read attendance table structure</span><br>";
    $issues[] = "Cannot read attendance table";
}

echo "</div></div>";

// 5. Check file permissions and existence
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>5. File System Check</h5></div>";
echo "<div class='card-body'>";

$critical_files = [
    'takeAttendance.php' => 'Main attendance page',
    'viewTodayAttendance.php' => 'Today\'s attendance view',
    'simple_take_attendance.php' => 'Simplified attendance',
    'index.php' => 'Dashboard',
    'Includes/sidebar.php' => 'Navigation sidebar'
];

foreach ($critical_files as $file => $description) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "<span class='check-pass'>✓ File '$file' exists and readable ($description)</span><br>";
        } else {
            echo "<span class='check-warn'>⚠ File '$file' exists but not readable</span><br>";
            $warnings[] = "File permission issue: $file";
        }
    } else {
        echo "<span class='check-fail'>✗ File '$file' missing ($description)</span><br>";
        $issues[] = "Missing file: $file";
    }
}

echo "</div></div>";

// Summary and recommendations
echo "<div class='card mb-3'>";
echo "<div class='card-header'><h5>6. Summary & Recommendations</h5></div>";
echo "<div class='card-body'>";

echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<h6 class='text-success'>Passed (" . count($passes) . ")</h6>";
foreach ($passes as $pass) {
    echo "<small class='check-pass'>✓ $pass</small><br>";
}
echo "</div>";

echo "<div class='col-md-4'>";
echo "<h6 class='text-warning'>Warnings (" . count($warnings) . ")</h6>";
foreach ($warnings as $warning) {
    echo "<small class='check-warn'>⚠ $warning</small><br>";
}
echo "</div>";

echo "<div class='col-md-4'>";
echo "<h6 class='text-danger'>Critical Issues (" . count($issues) . ")</h6>";
foreach ($issues as $issue) {
    echo "<small class='check-fail'>✗ $issue</small><br>";
}
echo "</div>";
echo "</div>";

echo "<hr>";

if (empty($issues)) {
    if (empty($warnings)) {
        echo "<div class='alert alert-success'>";
        echo "<h6><i class='fas fa-check-circle'></i> System Ready!</h6>";
        echo "<p>All checks passed. You can proceed with testing the Take Attendance functionality.</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h6><i class='fas fa-exclamation-triangle'></i> System Mostly Ready</h6>";
        echo "<p>No critical issues found, but there are some warnings. You can proceed with testing, but may encounter limited functionality.</p>";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h6><i class='fas fa-times-circle'></i> Critical Issues Found</h6>";
    echo "<p>Please fix the critical issues before testing. Use the quick fixes below:</p>";
    echo "</div>";
}

echo "</div></div>";

// Quick action buttons
echo "<div class='card'>";
echo "<div class='card-header'><h5>Quick Actions</h5></div>";
echo "<div class='card-body text-center'>";
echo "<div class='row'>";

if (!empty($issues)) {
    echo "<div class='col-md-3'>";
    echo "<a href='fix_attendance_table.php' class='btn btn-danger btn-block'>";
    echo "<i class='fas fa-wrench'></i> Fix Database Issues";
    echo "</a>";
    echo "</div>";
}

echo "<div class='col-md-3'>";
echo "<a href='test_attendance_dashboard.html' class='btn btn-primary btn-block'>";
echo "<i class='fas fa-play'></i> Start Testing";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<a href='system_status_check.php' class='btn btn-info btn-block'>";
echo "<i class='fas fa-chart-line'></i> Detailed Status";
echo "</a>";
echo "</div>";

echo "<div class='col-md-3'>";
echo "<a href='debug_attendance.php' class='btn btn-warning btn-block'>";
echo "<i class='fas fa-bug'></i> Debug Mode";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</div></div>";

echo "</div>";
echo "</body></html>";
?>
