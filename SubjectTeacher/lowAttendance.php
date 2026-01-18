<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Include session utilities if available
if (file_exists('Includes/init_session.php')) {
    include 'Includes/init_session.php';
}

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

$message = "";
$thresholdPercent = isset($_POST['threshold']) ? intval($_POST['threshold']) : 75;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Dashboard - Low Attendance Students</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Low Attendance Students</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Low Attendance List</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Low Attendance Filter -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Threshold Settings</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Attendance Threshold (%)</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="threshold" value="<?php echo $thresholdPercent; ?>" min="1" max="100" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                                <small class="text-muted">Students with attendance below this threshold will be considered at risk</small>
                                            </div>
                                        </div>                                        <button type="submit" name="filter" class="btn btn-primary">Apply Filter</button>
                                    </form>
                                </div>
                            </div>                            <?php
                            if(isset($_POST['filter']) || !isset($_POST['threshold'])) {
                            ?>
                            <!-- Students List -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Students Below <?php echo $thresholdPercent; ?>% Attendance</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Total Days</th>
                                                <th>Present</th>
                                                <th>Absent</th>
                                                <th>Attendance %</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Get the current session dates - start with current month
                                            $fromDate = date('Y-m-01'); // First day of current month
                                            $toDate = date('Y-m-d'); // Today's date
                                            
                                            // Query to get attendance data for students with attendance below threshold
                                            $query = "SELECT 
                                                s.Id as studentId,
                                                s.firstName,
                                                s.lastName,
                                                s.admissionNumber,
                                                c.className,
                                                COUNT(DISTINCT sa.date) as totalDays,
                                                SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as daysPresent,
                                                SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as daysAbsent
                                            FROM tblstudents s
                                            INNER JOIN tblclass c ON c.Id = s.classId
                                            INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id                                                LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id 
                                                AND sa.date BETWEEN ? AND ?
                                                AND (sa.subjectId = ? OR sa.subjectTeacherId = ?)
                                                WHERE sts.subjectTeacherId = ?
                                            GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className
                                            HAVING (SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / COUNT(DISTINCT sa.date)) * 100 < ?
                                                OR COUNT(DISTINCT sa.date) = 0
                                            ORDER BY (SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT sa.date), 0)) * 100 ASC, 
                                                s.lastName ASC, s.firstName ASC";
                                            
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("ssiiid", $fromDate, $toDate, $_SESSION['subjectId'], $_SESSION['subjectId'], $_SESSION['userId'], $thresholdPercent);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            $count = 1;
                                            while ($row = $result->fetch_assoc()) {
                                                $attendancePercent = $row['totalDays'] > 0 ? 
                                                    round(($row['daysPresent'] / $row['totalDays']) * 100, 1) : 0;
                                                
                                                $statusClass = 'text-danger';
                                                if ($attendancePercent >= 60) {
                                                    $statusClass = 'text-warning';
                                                }
                                                
                                                echo '<tr>
                                                    <td>' . $count++ . '</td>
                                                    <td>' . str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']) . '</td>
                                                    <td>' . $row['admissionNumber'] . '</td>
                                                    <td>' . $row['className'] . '</td>
                                                    <td>' . $row['totalDays'] . '</td>
                                                    <td>' . $row['daysPresent'] . '</td>
                                                    <td>' . $row['daysAbsent'] . '</td>
                                                    <td class="'.$statusClass.'">' . $attendancePercent . '%</td>
                                                    <td>
                                                        <a href="viewStudentAttendance.php?studentId='.$row['studentId'].'" 
                                                           class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> Details
                                                        </a>
                                                    </td>
                                                </tr>';
                                            }
                                            
                                            if ($count === 1) {
                                                echo '<tr><td colspan="9" class="text-center">
                                                    <div class="alert alert-success">
                                                        <i class="fas fa-check-circle mr-2"></i>
                                                        Great job! No students below '.$thresholdPercent.'% attendance threshold.
                                                    </div>
                                                </td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <!---Container Fluid-->
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php";?>
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
    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function () {
            $('#dataTableHover').DataTable({
                "order": [[7, "asc"]]  // Sort by attendance percentage ascending (lowest first)
            });
        });
    </script>
</body>
</html>
