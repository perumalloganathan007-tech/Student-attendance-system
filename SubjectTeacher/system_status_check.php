<?php
// Status checker to verify all fixes have been applied correctly
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../Includes/dbcon.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a file exists (case-insensitive)
function file_exists_ci($filepath) {
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    $dirname = dirname($filepath);
    $filename = basename($filepath);
    
    if (!is_dir($dirname)) {
        return false;
    }
    
    $filenames = scandir($dirname);
    foreach ($filenames as $fn) {
        if (strtolower($fn) === strtolower($filename)) {
            return $dirname . DIRECTORY_SEPARATOR . $fn;
        }
    }
    
    return false;
}

// Function to check if a table has a specific column
function has_column($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Function to read a file and check for a pattern
function file_contains($filepath, $pattern) {
    if (!file_exists($filepath)) {
        return false;
    }
    
    $content = file_get_contents($filepath);
    return strpos($content, $pattern) !== false;
}

// Create tabular output for status
function status_badge($condition, $success_text = "OK", $failure_text = "ISSUE") {
    if ($condition) {
        return "<span class='badge bg-success'>$success_text</span>";
    } else {
        return "<span class='badge bg-danger'>$failure_text</span>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Status Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 20px;
        }
        .check-card {
            margin-bottom: 20px;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        .check-card.success {
            border-left-color: #198754;
        }
        .check-card.warning {
            border-left-color: #ffc107;
        }
        .check-card.danger {
            border-left-color: #dc3545;
        }
        .check-card:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        .bg-success {
            background-color: #198754!important;
            color: white;
        }
        .bg-danger {
            background-color: #dc3545!important;
            color: white;
        }
        .bg-warning {
            background-color: #ffc107!important;
            color: black;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Student Attendance System Status Check</h1>
        <p class="lead">This tool verifies that all fixes have been properly applied.</p>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>1. File System Checks</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Check</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Check attendance analytics file
                                $analytics_file = __DIR__ . '/attendanceAnalytics.php';
                                $analytics_exists = file_exists($analytics_file);
                                ?>
                                <tr>
                                    <td>attendanceAnalytics.php</td>
                                    <td><?php echo status_badge($analytics_exists); ?></td>
                                    <td>
                                        <?php 
                                        if (!$analytics_exists) {
                                            $ci_file = file_exists_ci($analytics_file);
                                            if ($ci_file) {
                                                echo "Found with different case: " . basename($ci_file);
                                            } else {
                                                echo "File not found";
                                            }
                                        } else {
                                            echo "File exists at correct path";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check viewTodayAttendance.php file
                                $today_file = __DIR__ . '/viewTodayAttendance.php';
                                $today_exists = file_exists($today_file);
                                ?>
                                <tr>
                                    <td>viewTodayAttendance.php</td>
                                    <td><?php echo status_badge($today_exists); ?></td>
                                    <td>
                                        <?php 
                                        if (!$today_exists) {
                                            $ci_file = file_exists_ci($today_file);
                                            if ($ci_file) {
                                                echo "Found with different case: " . basename($ci_file);
                                            } else {
                                                echo "File not found";
                                            }
                                        } else {
                                            echo "File exists at correct path";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check 404.php file
                                $error_file = __DIR__ . '/404.php';
                                $error_exists = file_exists($error_file);
                                ?>
                                <tr>
                                    <td>404.php</td>
                                    <td><?php echo status_badge($error_exists); ?></td>
                                    <td>
                                        <?php 
                                        echo $error_exists ? "Custom error page exists" : "Custom error page missing";
                                        ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check .htaccess file
                                $htaccess_file = __DIR__ . '/.htaccess';
                                $htaccess_exists = file_exists($htaccess_file);
                                $htaccess_correct = $htaccess_exists && 
                                    file_contains($htaccess_file, 'ErrorDocument 404') && 
                                    !file_contains($htaccess_file, 'RewriteMap');
                                ?>
                                <tr>
                                    <td>.htaccess configuration</td>
                                    <td><?php echo status_badge($htaccess_correct); ?></td>
                                    <td>
                                        <?php 
                                        if (!$htaccess_exists) {
                                            echo "File missing";
                                        } else if (!file_contains($htaccess_file, 'ErrorDocument 404')) {
                                            echo "Missing ErrorDocument directive";
                                        } else if (file_contains($htaccess_file, 'RewriteMap')) {
                                            echo "Contains problematic RewriteMap directive";
                                        } else {
                                            echo "Configuration is correct";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check session_utils.php file
                                $utils_file = __DIR__ . '/Includes/session_utils.php';
                                $utils_exists = file_exists($utils_file);
                                $utils_correct = $utils_exists && 
                                    file_contains($utils_file, "INNER JOIN tblterm t ON t.Id = st.termId") && 
                                    !file_contains($utils_file, "INNER JOIN tblterm t ON t.sessionTermId = st.Id");
                                ?>
                                <tr>
                                    <td>session_utils.php JOIN query</td>
                                    <td><?php echo status_badge($utils_correct); ?></td>
                                    <td>
                                        <?php 
                                        if (!$utils_exists) {
                                            echo "File missing";
                                        } else if (file_contains($utils_file, "INNER JOIN tblterm t ON t.sessionTermId = st.Id")) {
                                            echo "Contains incorrect JOIN condition";
                                        } else if (!file_contains($utils_file, "INNER JOIN tblterm t ON t.Id = st.termId")) {
                                            echo "Missing correct JOIN condition";
                                        } else {
                                            echo "JOIN query is correct";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>2. Database Checks</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Check</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Check tblterm table structure
                                $term_table_exists = false;
                                $has_session_term_id = false;
                                
                                try {
                                    $result = $conn->query("SHOW TABLES LIKE 'tblterm'");
                                    $term_table_exists = $result && $result->num_rows > 0;
                                    
                                    if ($term_table_exists) {
                                        $has_session_term_id = has_column($conn, 'tblterm', 'sessionTermId');
                                    }
                                } catch (Exception $e) {
                                    // Handle error
                                }
                                ?>
                                <tr>
                                    <td>tblterm table exists</td>
                                    <td><?php echo status_badge($term_table_exists); ?></td>
                                    <td>
                                        <?php echo $term_table_exists ? "Table exists" : "Table missing"; ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td>sessionTermId column in tblterm</td>
                                    <td><?php echo status_badge($has_session_term_id); ?></td>
                                    <td>
                                        <?php 
                                        if (!$term_table_exists) {
                                            echo "Table missing";
                                        } else {
                                            echo $has_session_term_id ? "Column exists" : "Column missing";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check session tables
                                $session_term_table_exists = false;
                                
                                try {
                                    $result = $conn->query("SHOW TABLES LIKE 'tblsessionterm'");
                                    $session_term_table_exists = $result && $result->num_rows > 0;
                                } catch (Exception $e) {
                                    // Handle error
                                }
                                ?>
                                <tr>
                                    <td>tblsessionterm table exists</td>
                                    <td><?php echo status_badge($session_term_table_exists); ?></td>
                                    <td>
                                        <?php echo $session_term_table_exists ? "Table exists" : "Table missing"; ?>
                                    </td>
                                </tr>
                                
                                <?php
                                // Check attendance tables
                                $attendance_table_exists = false;
                                
                                try {
                                    $result = $conn->query("SHOW TABLES LIKE 'tblsubjectattendance'");
                                    $attendance_table_exists = $result && $result->num_rows > 0;
                                } catch (Exception $e) {
                                    // Handle error
                                }
                                ?>
                                <tr>
                                    <td>tblsubjectattendance table exists</td>
                                    <td><?php echo status_badge($attendance_table_exists); ?></td>
                                    <td>
                                        <?php echo $attendance_table_exists ? "Table exists" : "Table missing"; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>3. Session Checks</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Check</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Check if session variables are set
                                $has_user_id = isset($_SESSION['userId']);
                                $has_user_type = isset($_SESSION['user_type']);
                                $is_subject_teacher = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'SubjectTeacher';
                                $has_subject_id = isset($_SESSION['subjectId']);
                                ?>
                                <tr>
                                    <td>Session status</td>
                                    <td><?php echo status_badge($has_user_id && $has_user_type); ?></td>
                                    <td>
                                        <?php 
                                        if ($has_user_id && $has_user_type) {
                                            echo "Session active for: " . $_SESSION['user_type'];
                                        } else {
                                            echo "Session not initialized";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td>Subject Teacher session</td>
                                    <td><?php echo status_badge($is_subject_teacher); ?></td>
                                    <td>
                                        <?php 
                                        if ($has_user_type) {
                                            echo $is_subject_teacher ? 
                                                "Logged in as Subject Teacher" : 
                                                "Logged in as " . $_SESSION['user_type'] . " (not Subject Teacher)";
                                        } else {
                                            echo "User type not set";
                                        }
                                        ?>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td>Subject information</td>
                                    <td><?php echo status_badge($has_subject_id); ?></td>
                                    <td>
                                        <?php 
                                        if ($has_subject_id) {
                                            echo "Subject ID: " . $_SESSION['subjectId'];
                                            if (isset($_SESSION['subjectName'])) {
                                                echo ", Name: " . $_SESSION['subjectName'];
                                            }
                                        } else {
                                            echo "Subject information not set";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>4. Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calculate overall status
                        $file_system_ok = $analytics_exists && $today_exists && $error_exists && $htaccess_correct && $utils_correct;
                        $database_ok = $term_table_exists && $has_session_term_id && $session_term_table_exists && $attendance_table_exists;
                        $session_ok = $has_user_id && $is_subject_teacher && $has_subject_id;
                        
                        $all_ok = $file_system_ok && $database_ok && $session_ok;
                        ?>
                        
                        <div class="alert <?php echo $all_ok ? 'alert-success' : 'alert-warning'; ?>">
                            <h4><?php echo $all_ok ? '✓ All systems operational' : '⚠ Some issues detected'; ?></h4>
                            <p><?php echo $all_ok ? 
                                'All fixes have been successfully applied and the system should be working correctly.' : 
                                'Some issues were detected that may affect system functionality.'; ?></p>
                        </div>
                        
                        <h5>Status by Category:</h5>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                File System
                                <?php echo status_badge($file_system_ok); ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Database
                                <?php echo status_badge($database_ok); ?>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Session
                                <?php echo status_badge($session_ok); ?>
                            </li>
                        </ul>
                        
                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
                            <div>
                                <a href="attendanceAnalytics.php" class="btn btn-success me-2">Test Analytics Page</a>
                                <a href="viewTodayAttendance.php" class="btn btn-success">Test Today's Attendance</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
