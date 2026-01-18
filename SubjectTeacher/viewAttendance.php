<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Initialize session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate that user is a Subject Teacher
if (!isset($_SESSION['userId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'SubjectTeacher') {
    header("Location: ../subjectTeacherLogin.php");
    exit();
}

// Get date range from request or default to current month
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

// Get Subject Teacher Information and Statistics
$query = "SELECT 
            s.subjectName,
            s.subjectCode,
            (SELECT COUNT(*) FROM tblsubjectteacher_student WHERE subjectTeacherId = st.Id) as totalStudents,            (
                SELECT COUNT(DISTINCT date) 
                FROM tblsubjectattendance 
                WHERE (subjectId = s.Id OR subjectTeacherId = st.Id)
                AND date BETWEEN ? AND ?
            ) as totalDays,
            (
                SELECT COUNT(*) 
                FROM tblsubjectattendance 
                WHERE (subjectId = s.Id OR subjectTeacherId = st.Id)
                AND status = 1 
                AND date BETWEEN ? AND ?
            ) as totalPresent,
            (
                SELECT COUNT(*) 
                FROM tblsubjectattendance 
                WHERE (subjectId = s.Id OR subjectTeacherId = st.Id)
                AND status = 0 
                AND date BETWEEN ? AND ?
            ) as totalAbsent          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          WHERE st.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssi", 
    $startDate, $endDate,
    $startDate, $endDate,
    $startDate, $endDate,
    $_SESSION['userId']
);

try {
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $conn->error);
    }
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    if (!$stats) {
        // Set default values if no data found
        $stats = [
            'subjectName' => 'No Data',
            'subjectCode' => 'N/A',
            'totalStudents' => 0,
            'totalDays' => 0,
            'totalPresent' => 0,
            'totalAbsent' => 0
        ];
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
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
    <title>View Attendance - <?php echo $stats['subjectName']; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        .stats-card {
            border-left: 4px solid;
        }
        .stats-card.total-students { border-color: #4e73df; }
        .stats-card.total-days { border-color: #1cc88a; }
        .stats-card.attendance-rate { border-color: #36b9cc; }
        .stats-card.absent-rate { border-color: #e74a3b; }
        .date-range-form {
            background: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
        }
        .attendance-percentage {
            font-size: 2rem;
            font-weight: bold;
        }
        .trend-indicator {
            font-size: 0.875rem;
        }
        .trend-up { color: #1cc88a; }
        .trend-down { color: #e74a3b; }
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
                <?php 
                if (file_exists("Includes/topbar.php")) {
                    include "Includes/topbar.php";
                } else {
                    echo '<div class="alert alert-danger">Error: topbar.php file is missing</div>';
                }
                ?>
                <!-- Topbar -->

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            Attendance Analysis
                            <small class="text-muted">
                                (<?php echo $stats['subjectName'] . ' - ' . $stats['subjectCode']; ?>)
                            </small>
                        </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Attendance</li>
                        </ol>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="date-range-form">
                                <form method="get" class="form-inline justify-content-center">
                                    <div class="form-group mx-sm-3">
                                        <label for="daterange" class="mr-2">Date Range:</label>
                                        <input type="text" class="form-control" id="daterange" name="daterange" 
                                               value="<?php echo date('m/d/Y', strtotime($startDate)) . ' - ' . date('m/d/Y', strtotime($endDate)); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-3">
                        <!-- Total Students Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card total-students">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Students
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['totalStudents']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Days Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card total-days">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Days in Period
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo $stats['totalDays']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card attendance-rate">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Attendance Rate
                                            </div>
                                            <?php
                                            $totalPossible = $stats['totalStudents'] * $stats['totalDays'];
                                            $attendanceRate = $totalPossible > 0 ? 
                                                round(($stats['totalPresent'] / $totalPossible) * 100, 1) : 0;
                                            ?>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $attendanceRate; ?>%
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-info" role="progressbar" 
                                                             style="width: <?php echo $attendanceRate; ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Absent Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card absent-rate">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Absent Rate
                                            </div>
                                            <?php
                                            $absentRate = $totalPossible > 0 ? 
                                                round(($stats['totalAbsent'] / $totalPossible) * 100, 1) : 0;
                                            ?>
                                            <div class="row no-gutters align-items-center">
                                                <div class="col-auto">
                                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                        <?php echo $absentRate; ?>%
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="progress progress-sm mr-2">
                                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                             style="width: <?php echo $absentRate; ?>%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Records -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                                            aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Export Options:</div>
                                            <a class="dropdown-item" href="downloadRecord.php?type=csv&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>">
                                                <i class="fas fa-file-csv fa-sm fa-fw mr-2 text-gray-400"></i>
                                                Export as CSV
                                            </a>
                                            <a class="dropdown-item" href="downloadRecord.php?type=pdf&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>">
                                                <i class="fas fa-file-pdf fa-sm fa-fw mr-2 text-gray-400"></i>
                                                Export as PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Student Name</th>
                                                    <th>Admission No</th>
                                                    <th>Status</th>
                                                    <th>Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $recordsQuery = "SELECT 
                                                    sa.date,
                                                    s.firstName,
                                                    s.lastName,
                                                    s.admissionNumber,
                                                    sa.status,
                                                    sa.remarks                                                FROM tblsubjectattendance sa
                                                INNER JOIN tblstudents s ON s.Id = sa.studentId
                                                WHERE (sa.subjectId = ? OR sa.subjectTeacherId = ?) 
                                                AND sa.date BETWEEN ? AND ?
                                                ORDER BY sa.date DESC, s.lastName ASC, s.firstName ASC";
                                                  $stmt = $conn->prepare($recordsQuery);
                                                $stmt->bind_param("iiss", $_SESSION['subjectId'], $_SESSION['subjectId'], $startDate, $endDate);
                                                $stmt->execute();
                                                $records = $stmt->get_result();
                                                
                                                while ($record = $records->fetch_assoc()): 
                                                ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                    <td><?php echo str_replace('.', ' ', $record['firstName']) . ' ' . str_replace('.', ' ', $record['lastName']); ?></td>
                                                    <td><?php echo $record['admissionNumber']; ?></td>
                                                    <td>
                                                        <?php if($record['status'] == 1): ?>
                                                            <span class="badge badge-success">Present</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Absent</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $record['remarks'] ?? '-'; ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!---Container Fluid-->
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
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    
    <!-- Page level custom scripts -->
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#attendanceTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf']
        });

        // Initialize Date Range Picker
        $('#daterange').daterangepicker({
            startDate: moment('<?php echo $startDate; ?>'),
            endDate: moment('<?php echo $endDate; ?>'),
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end, label) {
            // Update hidden inputs for form submission
            $('input[name="startDate"]').val(start.format('YYYY-MM-DD'));
            $('input[name="endDate"]').val(end.format('YYYY-MM-DD'));
        });
    });
    </script>
</body>
</html>
