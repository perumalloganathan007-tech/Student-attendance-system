<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

$dateTaken = date("Y-m-d");

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

// Get teacher's subject info
$subjectQuery = "SELECT s.subjectName, s.subjectCode 
                 FROM tblsubjectteacher st 
                 INNER JOIN tblsubjects s ON s.Id = st.subjectId 
                 WHERE st.Id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$subjectInfo = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Today's Attendance Report">
    <title>Today's Attendance Report - <?php echo $subjectInfo['subjectName']; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .report-options {
            background: #f8f9fc;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .report-options .btn {
            margin: 0 10px;
        }
        .report-summary {
            margin-bottom: 30px;
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

                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Today's Attendance Report</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Today's Report</li>
                        </ol>
                    </div>

                    <!-- Report Options -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Report Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="report-summary">
                                        <h5>Subject: <?php echo $subjectInfo['subjectName'] . ' (' . $subjectInfo['subjectCode'] . ')'; ?></h5>
                                        <p>Date: <?php echo date('F j, Y'); ?></p>
                                        <div class="row mt-4">
                                            <div class="col-md-3">
                                                <div class="card bg-primary text-white shadow">
                                                    <div class="card-body">
                                                        Total Students
                                                        <div class="h4"><?php echo $stats['totalAttendance']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-success text-white shadow">
                                                    <div class="card-body">
                                                        Present
                                                        <div class="h4"><?php echo $stats['presentCount']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-danger text-white shadow">
                                                    <div class="card-body">
                                                        Absent
                                                        <div class="h4"><?php echo $stats['absentCount']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-info text-white shadow">
                                                    <div class="card-body">
                                                        Attendance Rate
                                                        <div class="h4"><?php echo $stats['attendanceRate']; ?>%</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="report-options text-center">
                                        <p class="mb-4">Choose how you would like to view today's attendance report:</p>
                                        <a href="viewTodayAttendance.php" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Report Online
                                        </a>
                                        <a href="viewTodayAttendance.php?format=csv" class="btn btn-success">
                                            <i class="fas fa-file-csv"></i> Download CSV Report
                                        </a>
                                        <a href="#" onclick="window.print(); return false;" class="btn btn-info">
                                            <i class="fas fa-print"></i> Print Report
                                        </a>
                                    </div>

                                    <?php if ($stats['totalAttendance'] == 0): ?>
                                    <div class="alert alert-info text-center mt-4">
                                        <i class="fas fa-info-circle"></i> No attendance has been taken for today.
                                        <br>
                                        <a href="takeAttendance.php" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-plus-circle"></i> Take Attendance Now
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
            <!-- Footer -->
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
</body>
</html>
