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

if(isset($_POST['download'])) {
    $month = $_POST['month'];
    $year = $_POST['year'];
    $exportType = isset($_POST['exportType']) ? $_POST['exportType'] : 'excel';
    
    if(empty($month) || empty($year)) {
        $message = "<div class='alert alert-danger'>Please select both month and year</div>";
    } else {
        // Calculate start and end dates for the selected month
        $fromDate = $year . '-' . $month . '-01';
        $toDate = date('Y-m-t', strtotime($fromDate));
        
        // Redirect to download with date parameters
        header("Location: downloadRecord.php?start=".$fromDate."&end=".$toDate."&type=".$exportType);
        exit();
    }
}
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
    <title>Dashboard - Monthly Report</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Monthly Subject Attendance Report</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Monthly Report</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Select Month and Year</h6>
                                    <?php echo $message; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Month<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" name="month" required>
                                                    <option value="">--Select Month--</option>
                                                    <option value="01">January</option>
                                                    <option value="02">February</option>
                                                    <option value="03">March</option>
                                                    <option value="04">April</option>
                                                    <option value="05">May</option>
                                                    <option value="06">June</option>
                                                    <option value="07">July</option>
                                                    <option value="08">August</option>
                                                    <option value="09">September</option>
                                                    <option value="10">October</option>
                                                    <option value="11">November</option>
                                                    <option value="12">December</option>
                                                </select>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Year<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" name="year" required>
                                                    <option value="">--Select Year--</option>
                                                    <?php 
                                                    $currentYear = date('Y');
                                                    for($year = $currentYear; $year >= $currentYear - 5; $year--) {
                                                        echo "<option value='$year'>$year</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Export Format</label>
                                                <select class="form-control" name="exportType">
                                                    <option value="excel">Excel</option>
                                                    <option value="pdf">PDF</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="submit" name="view" class="btn btn-primary">View Report</button>
                                        <button type="submit" name="download" class="btn btn-success">Download Report</button>
                                    </form>
                                </div>
                            </div>

                            <?php
                            if(isset($_POST['view'])){
                                $month = $_POST['month'];
                                $year = $_POST['year'];
                                
                                // Calculate start and end dates for the selected month
                                $fromDate = $year . '-' . $month . '-01';
                                $toDate = date('Y-m-t', strtotime($fromDate));
                                
                                // Get month name
                                $monthName = date('F', strtotime($fromDate));
                            ?>
                            <!-- Report Display -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Report (<?php echo $monthName . ' ' . $year; ?>)</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Export Options:</div>
                                            <a class="dropdown-item" href="downloadRecord.php?start=<?php echo $fromDate; ?>&end=<?php echo $toDate; ?>&type=excel">Export to Excel</a>
                                            <a class="dropdown-item" href="downloadRecord.php?start=<?php echo $fromDate; ?>&end=<?php echo $toDate; ?>&type=pdf">Export to PDF</a>
                                        </div>
                                    </div>
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
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Query to get attendance data
                                            $query = "SELECT 
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
                                            ORDER BY s.lastName ASC, s.firstName ASC";
                                            
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("ssiii", $fromDate, $toDate, $_SESSION['subjectId'], $_SESSION['subjectId'], $_SESSION['userId']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            $count = 1;
                                            while ($row = $result->fetch_assoc()) {
                                                $attendancePercent = $row['totalDays'] > 0 ? 
                                                    round(($row['daysPresent'] / $row['totalDays']) * 100, 1) : 0;
                                                
                                                $status = 'N/A';
                                                $statusClass = '';
                                                
                                                if ($row['totalDays'] > 0) {
                                                    if ($attendancePercent >= 90) {
                                                        $status = 'Excellent';
                                                        $statusClass = 'text-success';
                                                    } elseif ($attendancePercent >= 75) {
                                                        $status = 'Good';
                                                        $statusClass = 'text-info';
                                                    } elseif ($attendancePercent >= 60) {
                                                        $status = 'Warning';
                                                        $statusClass = 'text-warning';
                                                    } else {
                                                        $status = 'Critical';
                                                        $statusClass = 'text-danger';
                                                    }
                                                }
                                                
                                                echo '<tr>
                                                    <td>' . $count++ . '</td>
                                                    <td>' . str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']) . '</td>
                                                    <td>' . $row['admissionNumber'] . '</td>
                                                    <td>' . $row['className'] . '</td>
                                                    <td>' . $row['totalDays'] . '</td>
                                                    <td>' . $row['daysPresent'] . '</td>
                                                    <td>' . $row['daysAbsent'] . '</td>
                                                    <td>' . $attendancePercent . '%</td>
                                                    <td class="'.$statusClass.'">' . $status . '</td>
                                                </tr>';
                                            }
                                            
                                            if ($count === 1) {
                                                echo '<tr><td colspan="9" class="text-center">No attendance data found for the selected month</td></tr>';
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
                "order": [[7, "desc"]]  // Sort by attendance percentage (8th column, 0-indexed)
            });
        });
    </script>
</body>
</html>
