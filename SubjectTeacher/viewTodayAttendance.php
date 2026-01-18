<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Initialize session variables
include 'Includes/init_session.php';

/**
 * Check if a column exists in a table
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $column Column name
 * @return bool True if column exists, false otherwise
 */
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0);
}

// Debug information (can be commented out in production)
/*
echo "<div style='position:fixed; bottom:0; right:0; background:white; border:1px solid black; padding:10px; z-index:9999; max-width:300px; font-size:12px;'>";
echo "<strong>Debug Info:</strong><br>";
echo "Date: " . $dateTaken = date("Y-m-d") . "<br>";
echo "User ID: " . ($_SESSION['userId'] ?? 'Not set') . "<br>";
echo "Subject ID: " . ($_SESSION['subjectId'] ?? 'Not set') . "<br>";
echo "Subject Name: " . ($_SESSION['subjectName'] ?? 'Not set') . "<br>";
echo "</div>";
*/

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Ensure subject info is available
if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    echo "<div class='alert alert-danger' style='margin: 20px;'>
            <h4>Error: Teacher information is missing</h4>
            <p>Please go to the <a href='fix_session.php'>Session Repair Tool</a> page to check and fix your session.</p>
            <p>Then return to the <a href='index.php'>Dashboard</a> and try again.</p>
         </div>";
    include "Includes/footer.php";
    exit();
}

// Get current date
$dateTaken = date("Y-m-d");

// Get Subject Teacher Information
$query = "SELECT 
    s.Id as subjectId,
    s.subjectName,
    s.subjectCode,
    st.Id as teacherId
FROM tblsubjectteacher st
INNER JOIN tblsubjects s ON s.Id = st.subjectId
WHERE st.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

// Set session variables with the retrieved information
if ($teacherInfo) {
    $_SESSION['subjectId'] = $teacherInfo['subjectId'];
    $_SESSION['subjectName'] = $teacherInfo['subjectName'];
    $_SESSION['subjectCode'] = $teacherInfo['subjectCode'];
} else {
    // If teacher info not found, set default values to prevent errors
    $teacherInfo = [
        'subjectId' => $_SESSION['subjectId'] ?? 0,
        'subjectName' => $_SESSION['subjectName'] ?? 'Unknown Subject',
        'subjectCode' => $_SESSION['subjectCode'] ?? 'N/A',
        'teacherId' => $_SESSION['userId'] ?? 0
    ];
    
    // Try to get subject info from session if available
    if (isset($_SESSION['subjectId']) && $_SESSION['subjectId']) {
        $subjectQuery = "SELECT subjectName, subjectCode FROM tblsubjects WHERE Id = ?";
        $subjectStmt = $conn->prepare($subjectQuery);
        $subjectStmt->bind_param("i", $_SESSION['subjectId']);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->get_result()->fetch_assoc();
        
        if ($subjectResult) {
            $teacherInfo['subjectName'] = $subjectResult['subjectName'];
            $teacherInfo['subjectCode'] = $subjectResult['subjectCode'];
        }
    }
}

// Get attendance statistics for today
$statsQuery = "SELECT 
                COUNT(DISTINCT sa.studentId) as totalAttendance,
                SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as presentCount,
                SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as absentCount,
                ROUND(SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / GREATEST(COUNT(DISTINCT sa.studentId), 1) * 100, 1) as attendanceRate
              FROM tblsubjectattendance sa
              WHERE sa.subjectTeacherId = ? 
              AND sa.date = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("is", $_SESSION['userId'], $dateTaken);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// If no stats, initialize default values
if (!$stats || $stats['totalAttendance'] == 0) {
    $stats = [
        'totalAttendance' => 0,
        'presentCount' => 0,
        'absentCount' => 0,
        'attendanceRate' => 0
    ];
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Check if attendance has been taken today
$attendanceTaken = ($stats['totalAttendance'] > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Subject Teacher Attendance Management System">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Today's Attendance - <?php echo isset($teacherInfo['subjectName']) ? $teacherInfo['subjectName'] : 'Subject Teacher'; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
      <!-- Custom styles -->
    <style>
        .attendance-table th, .attendance-table td {
            vertical-align: middle !important;
        }
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }        .attendance-summary {
            font-size: 0.9rem;
            margin-bottom: 1rem;
            border: 1px solid #e3e6f0;
        }
        .attendance-date {
            font-weight: bold;
            color: #4e73df;
        }
        .stats-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .no-attendance {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fc;
            border-radius: 5px;
        }
        .filter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-group .btn {
            box-shadow: none;
        }
        .filter-status {
            font-size: 0.85rem;
        }
        .card-header.bg-light {
            border-top: 1px solid #e3e6f0;
            border-bottom: 1px solid #e3e6f0;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->
                
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            Today's Attendance                            <small class="text-muted">
                                (<?php echo (isset($teacherInfo['subjectName']) ? $teacherInfo['subjectName'] : 'Unknown Subject') . ' - ' . (isset($teacherInfo['subjectCode']) ? $teacherInfo['subjectCode'] : 'N/A'); ?>)
                            </small>
                        </h1>                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item"><a href="viewTodayAttendance.php">Today's Attendance</a></li>
                            <?php if ($filter !== 'all' && !empty($filter)): ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo ucfirst($filter); ?> Students
                            </li>
                            <?php endif; ?>
                        </ol>
                    </div>

                    <div class="row mb-3">                        <!-- Total Students Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="viewTodayAttendance.php?filter=all" class="text-decoration-none">
                                <div class="card h-100 stats-card" style="border-left-color: #4e73df;">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Total Attendance</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['totalAttendance']; ?></div>
                                                <div class="mt-2 mb-0 text-muted text-xs">
                                                    <span>Students</span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-users fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                        <?php if ($attendanceTaken): ?>
                                        <div class="text-xs text-primary mt-2">
                                            <i class="fas fa-mouse-pointer fa-sm"></i> Click to view all
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Present Students Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="viewTodayAttendance.php?filter=present" class="text-decoration-none">
                                <div class="card h-100 stats-card" style="border-left-color: #1cc88a;">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Present</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['presentCount']; ?></div>
                                                <div class="mt-2 mb-0 text-muted text-xs">
                                                    <span>Students</span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-success"></i>
                                            </div>
                                        </div>
                                        <?php if ($attendanceTaken && $stats['presentCount'] > 0): ?>
                                        <div class="text-xs text-success mt-2">
                                            <i class="fas fa-mouse-pointer fa-sm"></i> Click to view present
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Absent Students Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <a href="viewTodayAttendance.php?filter=absent" class="text-decoration-none">
                                <div class="card h-100 stats-card" style="border-left-color: #e74a3b;">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Absent</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['absentCount']; ?></div>
                                                <div class="mt-2 mb-0 text-muted text-xs">
                                                    <span>Students</span>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                                            </div>
                                        </div>
                                        <?php if ($attendanceTaken && $stats['absentCount'] > 0): ?>
                                        <div class="text-xs text-danger mt-2">
                                            <i class="fas fa-mouse-pointer fa-sm"></i> Click to view absent
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!-- Attendance Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card" style="border-left-color: #f6c23e;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Attendance Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['attendanceRate']; ?>%</div>
                                            <div class="mt-2 mb-0 text-muted text-xs">
                                                <span>For today</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percentage fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row for attendance data -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Student Attendance for <span class="attendance-date"><?php echo date('l, F j, Y', strtotime($dateTaken)); ?></span>
                                    </h6>
                                      <?php if ($attendanceTaken): ?>
                                    <div>
                                        <a href="takeAttendance.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit fa-sm"></i> Edit Attendance
                                        </a>                                        <a href="javascript:void(0);" onclick="printTable(); return false;" class="btn btn-info btn-sm mr-2 print-btn" title="Print Attendance Report (Alt+P)">
                                            <i class="fas fa-print fa-sm"></i> Print
                                        </a>
                                        <a href="javascript:void(0);" onclick="exportTableToCSV(); return false;" class="btn btn-success btn-sm export-btn" title="Export to CSV (Alt+E)">
                                            <i class="fas fa-file-csv fa-sm"></i> Export CSV
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                  <?php if ($attendanceTaken): ?>
                                <!-- Filter Buttons -->
                                <div class="card-header py-2 bg-light">
                                    <div class="filter-container">                                        <div class="btn-group btn-group-sm" role="group" aria-label="Attendance Filter">                                            <a href="viewTodayAttendance.php?filter=all" class="btn btn-<?php echo ($filter === 'all' || empty($filter)) ? 'primary' : 'outline-primary'; ?>" 
                                               data-toggle="tooltip" title="Show all students (Alt+1)">
                                                <i class="fas fa-list"></i> All Students (<?php echo $stats['totalAttendance']; ?>)
                                            </a>
                                            <a href="viewTodayAttendance.php?filter=present" class="btn btn-<?php echo ($filter === 'present') ? 'success' : 'outline-success'; ?>"
                                               data-toggle="tooltip" title="Show present students (Alt+2)">
                                                <i class="fas fa-check-circle"></i> Present (<?php echo $stats['presentCount']; ?>)
                                            </a>
                                            <a href="viewTodayAttendance.php?filter=absent" class="btn btn-<?php echo ($filter === 'absent') ? 'danger' : 'outline-danger'; ?>"
                                               data-toggle="tooltip" title="Show absent students (Alt+3)">
                                                <i class="fas fa-times-circle"></i> Absent (<?php echo $stats['absentCount']; ?>)
                                            </a>
                                        </div>
                                        <div class="filter-status">
                                            <span class="text-muted">Currently showing: 
                                                <strong>
                                                <?php 
                                                    if ($filter === 'present') echo 'Present Students';
                                                    else if ($filter === 'absent') echo 'Absent Students';
                                                    else echo 'All Students'; 
                                                ?>
                                                </strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <?php if ($attendanceTaken): ?>
                                        <?php if ($filter === 'all'): ?>
                                        <!-- Statistics Summary (visible only on "all" view) -->
                                        <div class="attendance-summary mb-3 p-3 bg-light rounded">
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <div class="text-center">
                                                        <div class="small font-weight-bold text-primary mb-1">Attendance Rate</div>
                                                        <div class="h4 mb-0 font-weight-bold">
                                                            <?php echo $stats['attendanceRate']; ?>%
                                                        </div>
                                                        <div class="progress progress-sm mt-2 mb-1">
                                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $stats['attendanceRate']; ?>%" 
                                                                aria-valuenow="<?php echo $stats['attendanceRate']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted">Today's Overall Rate</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <div class="text-center">
                                                        <div class="small font-weight-bold text-success mb-1">Present</div>
                                                        <div class="h4 mb-0 font-weight-bold">
                                                            <?php echo $stats['presentCount']; ?> 
                                                            <small class="text-muted">of <?php echo $stats['totalAttendance']; ?></small>
                                                        </div>
                                                        <div class="progress progress-sm mt-2 mb-1">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: <?php echo ($stats['totalAttendance'] > 0) ? ($stats['presentCount']/$stats['totalAttendance']*100) : 0; ?>%" 
                                                                aria-valuenow="<?php echo ($stats['totalAttendance'] > 0) ? ($stats['presentCount']/$stats['totalAttendance']*100) : 0; ?>" 
                                                                aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted">Students Present</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <div class="text-center">
                                                        <div class="small font-weight-bold text-danger mb-1">Absent</div>
                                                        <div class="h4 mb-0 font-weight-bold">
                                                            <?php echo $stats['absentCount']; ?> 
                                                            <small class="text-muted">of <?php echo $stats['totalAttendance']; ?></small>
                                                        </div>
                                                        <div class="progress progress-sm mt-2 mb-1">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                style="width: <?php echo ($stats['totalAttendance'] > 0) ? ($stats['absentCount']/$stats['totalAttendance']*100) : 0; ?>%" 
                                                                aria-valuenow="<?php echo ($stats['totalAttendance'] > 0) ? ($stats['absentCount']/$stats['totalAttendance']*100) : 0; ?>" 
                                                                aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small text-muted">Students Absent</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered attendance-table" id="attendanceTable">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Student Name</th>
                                                        <th>Admission No.</th>
                                                        <th>Class</th>
                                                        <th>Status</th>
                                                        <?php if (columnExists($conn, 'tblsubjectattendance', 'remarks')): ?>
                                                        <th>Remarks</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Check if remarks column exists
                                                    $hasRemarksColumn = columnExists($conn, 'tblsubjectattendance', 'remarks');
                                                      // Build appropriate query based on columns and filter
                                                    $statusCondition = "";
                                                    if ($filter === 'present') {
                                                        $statusCondition = " AND sa.status = 1";
                                                    } elseif ($filter === 'absent') {
                                                        $statusCondition = " AND sa.status = 0";
                                                    }
                                                    
                                                    if ($hasRemarksColumn) {
                                                        $query = "SELECT 
                                                                    s.Id,
                                                                    s.firstName,
                                                                    s.lastName,
                                                                    s.admissionNumber,
                                                                    c.className,
                                                                    sa.status,
                                                                    sa.remarks
                                                                 FROM tblstudents s
                                                                 INNER JOIN tblclass c ON s.classId = c.Id
                                                                 INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId
                                                                 WHERE sa.subjectTeacherId = ?
                                                                 AND sa.date = ?
                                                                 $statusCondition
                                                                 ORDER BY s.lastName ASC, s.firstName ASC";
                                                    } else {
                                                        $query = "SELECT 
                                                                    s.Id,
                                                                    s.firstName,
                                                                    s.lastName,
                                                                    s.admissionNumber,
                                                                    c.className,
                                                                    sa.status
                                                                 FROM tblstudents s
                                                                 INNER JOIN tblclass c ON s.classId = c.Id
                                                                 INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId
                                                                 WHERE sa.subjectTeacherId = ?
                                                                 AND sa.date = ?
                                                                 $statusCondition
                                                                 ORDER BY s.lastName ASC, s.firstName ASC";
                                                    }
                                                    
                                                    $stmt = $conn->prepare($query);
                                                    $stmt->bind_param("is", $_SESSION['userId'], $dateTaken);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                      if ($result->num_rows == 0) {
                                                        if ($filter === 'present') {
                                                            echo '<tr><td colspan="' . ($hasRemarksColumn ? 6 : 5) . '" class="text-center">No present students found for today. <a href="viewTodayAttendance.php?filter=all">View all students</a></td></tr>';
                                                        } else if ($filter === 'absent') {
                                                            echo '<tr><td colspan="' . ($hasRemarksColumn ? 6 : 5) . '" class="text-center">No absent students found for today. <a href="viewTodayAttendance.php?filter=all">View all students</a></td></tr>';
                                                        } else {
                                                            echo '<tr><td colspan="' . ($hasRemarksColumn ? 6 : 5) . '" class="text-center">No attendance records found for today.</td></tr>';
                                                        }
                                                    } else {
                                                        $cnt = 1;
                                                        while ($row = $result->fetch_assoc()) {
                                                            $statusClass = $row['status'] == 1 ? 'status-present' : 'status-absent';
                                                            $statusText = $row['status'] == 1 ? 'Present' : 'Absent';
                                                            $statusIcon = $row['status'] == 1 ? 'check-circle' : 'times-circle';
                                                            $statusColor = $row['status'] == 1 ? 'success' : 'danger';
                                                    ?>
                                                            <tr>
                                                                <td><?php echo $cnt; ?></td>
                                                                <td><?php echo str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']); ?></td>
                                                                <td><?php echo $row['admissionNumber']; ?></td>
                                                                <td><?php echo $row['className']; ?></td>
                                                                <td class="<?php echo $statusClass; ?>">
                                                                    <i class="fas fa-<?php echo $statusIcon; ?> text-<?php echo $statusColor; ?> mr-2"></i>
                                                                    <?php echo $statusText; ?>
                                                                </td>
                                                                <?php if ($hasRemarksColumn): ?>
                                                                <td><?php echo $row['remarks'] ?? ''; ?></td>
                                                                <?php endif; ?>
                                                            </tr>
                                                    <?php
                                                            $cnt++;
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-attendance">
                                            <div class="mb-3">
                                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                                <h5 class="text-muted">No attendance has been taken for today.</h5>
                                            </div>
                                            <a href="takeAttendance.php" class="btn btn-primary">
                                                <i class="fas fa-plus-circle fa-sm mr-2"></i>Take Attendance Now
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!---Container Fluid-->
            </div>
            <!-- Content ends -->
            
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
      <!-- Print and CSV functionality -->
    <script>    function printTable() {
        try {
            // Get table content
            var printContents = document.getElementById('attendanceTable');
            if (!printContents) {
                alert('Attendance table not found!');
                return;
            }            var tableHTML = printContents.outerHTML;
            var printHeader = '<div style="text-align:center; margin-bottom:20px;"><h3>Subject: <?php echo addslashes((isset($teacherInfo["subjectName"]) ? $teacherInfo["subjectName"] : "Unknown Subject") . " (" . (isset($teacherInfo["subjectCode"]) ? $teacherInfo["subjectCode"] : "N/A") . ")"); ?></h3>' +
                             '<h4>Attendance for <?php echo addslashes(date("l, F j, Y", strtotime($dateTaken))); ?></h4></div>';
            
            // Create print window
            var printWindow = window.open('', '_blank', 'width=800,height=600');
            
            if (printWindow) {
                printWindow.document.write('<html><head><title>Print Attendance Report</title>');
                printWindow.document.write('<style>');
                printWindow.document.write('body { font-family: Arial, sans-serif; }');
                printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
                printWindow.document.write('th { background-color: #f2f2f2; font-weight: bold; }');
                printWindow.document.write('.status-present { background-color: #d4edda; color: #155724; }');
                printWindow.document.write('.status-absent { background-color: #f8d7da; color: #721c24; }');
                printWindow.document.write('@media print { .no-print { display: none; } }');
                printWindow.document.write('</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write('<div style="padding:20px;">');
                printWindow.document.write(printHeader);
                printWindow.document.write(tableHTML);
                
                // Add summary information
                var total = <?php echo $stats['totalAttendance']; ?>;
                var present = <?php echo $stats['presentCount']; ?>;
                var absent = <?php echo $stats['absentCount']; ?>;
                var rate = total > 0 ? Math.round((present / total) * 100) : 0;
                
                printWindow.document.write('<div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">');
                printWindow.document.write('<h5>Attendance Summary:</h5>');
                printWindow.document.write('<p>Total Students: ' + total + '<br>');
                printWindow.document.write('Present: ' + present + '<br>');
                printWindow.document.write('Absent: ' + absent + '<br>');
                printWindow.document.write('Attendance Rate: ' + rate + '%</p>');
                printWindow.document.write('</div>');
                
                printWindow.document.write('<div class="no-print" style="margin-top: 20px; text-align: center;">');
                printWindow.document.write('<button onclick="window.print();" style="padding: 8px 16px; background: #4e73df; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Print Report</button> ');
                printWindow.document.write('<button onclick="window.close();" style="padding: 8px 16px; background: #858796; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Close</button>');
                printWindow.document.write('</div></div>');
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                
                // Wait for content to load before focusing the window
                setTimeout(function() {
                    printWindow.focus();
                }, 500);
            } else {
                alert('Please allow pop-ups for this website to use the print function.');
            }
        } catch (e) {
            console.error('Print error:', e);
            alert('There was a problem printing the attendance report. Please try again.');
        }
    }
    function exportTableToCSV() {
        try {
            // Get the table
            var table = document.getElementById('attendanceTable');
            if (!table) {
                alert('Table data not found!');
                return;
            }            // Add report title and date to CSV
            var csvHeader = '"Subject: <?php echo addslashes((isset($teacherInfo["subjectName"]) ? $teacherInfo["subjectName"] : "Unknown Subject") . " (" . (isset($teacherInfo["subjectCode"]) ? $teacherInfo["subjectCode"] : "N/A") . ")"); ?>"\n';
            csvHeader += '"Attendance Report for <?php echo addslashes(date("l, F j, Y", strtotime($dateTaken))); ?>"\n';
            csvHeader += '"Class: <?php echo addslashes((isset($teacherInfo["yearName"]) ? $teacherInfo["yearName"] : "Unknown") . " (" . (isset($teacherInfo["programName"]) ? $teacherInfo["programName"] : "Unknown") . ")"); ?>"\n\n';
            
            // Prepare CSV content
            var csv = [];
            var rows = table.querySelectorAll('tr');
            
            // Get header row
            var headerRow = [];
            var headers = rows[0].querySelectorAll('th');
            for (var i = 0; i < headers.length; i++) {
                headerRow.push('"' + headers[i].innerText.trim() + '"');
            }
            csv.push(headerRow.join(','));
            
            var totalStudents = 0;
            var presentStudents = 0;
            var absentStudents = 0;
            
            // Get data rows
            for (var i = 1; i < rows.length; i++) {
                var row = [];
                var cols = rows[i].querySelectorAll('td');
                
                // Skip empty rows or "No students found" messages
                if (cols.length <= 1) continue;
                
                totalStudents++;
                var isPresent = false;
                
                for (var j = 0; j < cols.length; j++) {
                    // Get text content and clean it
                    var text = cols[j].innerText.trim().replace(/"/g, '""');
                    
                    // Count attendance status
                    if (j === 4) { // Status column (5th column, zero-indexed)
                        if (text.includes('Present')) {
                            presentStudents++;
                            isPresent = true;
                        } else if (text.includes('Absent')) {
                            absentStudents++;
                        }
                    }
                    
                    row.push('"' + text + '"');
                }
                
                csv.push(row.join(','));
            }
            
            // Add summary at the end
            var csvFooter = '\n\n"Attendance Summary"\n';
            csvFooter += '"Total Students","' + totalStudents + '"\n';
            csvFooter += '"Present","' + presentStudents + '"\n';
            csvFooter += '"Absent","' + absentStudents + '"\n';
            csvFooter += '"Attendance Rate","' + (totalStudents > 0 ? Math.round((presentStudents / totalStudents) * 100) : 0) + '%"\n';
            
            // Combine all parts
            var csvContent = csvHeader + csv.join('\n') + csvFooter;
            
            // Create Blob
            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            
            // Create and trigger download
            var fileName = 'attendance_report_<?php echo date('Y-m-d', strtotime($dateTaken)); ?>.csv';
            
            // Try to use the download attribute first
            if (navigator.msSaveBlob) {
                // For IE/Edge
                navigator.msSaveBlob(blob, fileName);
            } else {
                // For modern browsers
                var link = document.createElement('a');
                
                if (link.download !== undefined) {
                    // Create a link to the file
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', fileName);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function() {
                        URL.revokeObjectURL(url);
                    }, 100);
                } else {
                    // Fallback
                    alert('Your browser does not support downloading CSV files directly. Please try using Chrome, Firefox, Edge, or another modern browser.');
                }
            }
            
        } catch (e) {
            console.error('CSV Export error:', e);
            alert('There was a problem exporting the attendance report. Please try again.');
        }
    }    // Document Ready Handler
    $(document).ready(function() {
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Highlight the current filter button
        $('.btn-group .btn').on('click', function() {
            $('.btn-group .btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // Add explicit event handlers for the buttons
        document.querySelectorAll('.print-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                printTable();
                return false;
            });
        });
        
        document.querySelectorAll('.export-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                exportTableToCSV();
                return false;
            });
        });
        
        // Add keyboard shortcuts for filter navigation
        $(document).keydown(function(e) {
            // Only when not typing in an input
            if (!$('input:focus, textarea:focus').length) {
                // Alt+1 = All Students
                if (e.altKey && e.which === 49) {
                    window.location.href = 'viewTodayAttendance.php?filter=all';
                }
                // Alt+2 = Present Students
                else if (e.altKey && e.which === 50) {
                    window.location.href = 'viewTodayAttendance.php?filter=present';
                }
                // Alt+3 = Absent Students
                else if (e.altKey && e.which === 51) {
                    window.location.href = 'viewTodayAttendance.php?filter=absent';
                }
                // Alt+P = Print
                else if (e.altKey && e.which === 80) {
                    printTable();
                    return false;
                }
                // Alt+E = Export CSV
                else if (e.altKey && e.which === 69) {
                    exportTableToCSV();
                    return false;
                }
            }
        });
          // Make sure print/export buttons work properly
        $('.print-btn').on('click', function(e) {
            e.preventDefault();
            printTable();
            return false;
        });
        
        $('.export-btn').on('click', function(e) {
            e.preventDefault();
            exportTableToCSV();
            return false;
        });
    });
    </script>
</body>
</html>
