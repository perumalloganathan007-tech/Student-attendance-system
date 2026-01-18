<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    $fromDate = $_POST['fromDate'];
    $toDate = $_POST['toDate'];
    $exportType = isset($_POST['exportType']) ? $_POST['exportType'] : 'excel';
    
    if(empty($fromDate) || empty($toDate)) {
        $message = "<div class='alert alert-danger'>Please select both start and end dates</div>";
    } else {
        // Make sure session variables are set before redirecting
        if (!isset($_SESSION['subjectId'])) {
            // Try to get the subject ID and name from database
            $query = "SELECT st.subjectId, s.subjectName FROM tblsubjectteacher st LEFT JOIN tblsubjects s ON s.Id = st.subjectId WHERE st.Id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $_SESSION['userId']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $_SESSION['subjectId'] = $row['subjectId'];
                if (!empty($row['subjectName'])) {
                    $_SESSION['subjectName'] = $row['subjectName'];
                }
            } else {
                $message = "<div class='alert alert-danger'>Could not retrieve subject information. Please try again or contact support.</div>";
                // Log the error
                file_put_contents('daterange_error.log', 
                    "Time: " . date('Y-m-d H:i:s') . 
                    "\nSession: " . json_encode($_SESSION) . 
                    "\nCould not find subject ID for teacher\n\n", FILE_APPEND);
            }
        }
          if (isset($_SESSION['subjectId'])) {
            // Redirect to the direct download page with parameters
            $url = "direct_download.php?start=".urlencode($fromDate)."&end=".urlencode($toDate)."&type=".urlencode($exportType);
            header("Location: $url");
            exit();
        }
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
    <title>Dashboard - Generate Report</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Generate Subject Attendance Report</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Generate Report</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Select Date Range</h6>
                                    <?php echo $message; ?>
                                </div>
                                <!-- TCPDF Requirement Check -->
                                <?php if (!file_exists('../vendor/autoload.php') && !file_exists('../vendor/tcpdf/tcpdf.php')) : ?>
                                <div class="alert alert-warning alert-dismissible fade show">
                                    <strong>Note:</strong> PDF export requires the TCPDF library which is not yet installed.
                                    <a href="../install_tcpdf.php" target="_blank">Click here for installation instructions</a> or use Excel export instead.
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <form method="post">                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">From Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" class="form-control" name="fromDate" required>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">To Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" class="form-control" name="toDate" required>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Export Format</label>                                                <select class="form-control" name="exportType" id="exportType">
                                                    <option value="excel">Excel</option>
                                                    <option value="pdf" <?php if (!file_exists('../vendor/autoload.php') && !file_exists('../vendor/tcpdf/tcpdf.php')) echo 'data-requires-tcpdf="true"'; ?>>PDF</option>
                                                </select>
                                            </div>                                        </div>
                                        <button type="submit" name="view" class="btn btn-primary">View Report</button>
                                        <button type="submit" name="download" class="btn btn-success">Download Report</button>
                                    </form>
                                </div>
                            </div>
                            <!-- Debugging Information for Admin -->
                            <?php if(isset($_GET['debug'])) { ?>
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Debug Information</h6>
                                </div>
                                <div class="card-body">
                                    <pre><?php print_r($_SESSION); ?></pre>
                                </div>
                            </div>
                            <?php } ?><?php
                            if(isset($_POST['view'])){
                                $fromDate = $_POST['fromDate'];
                                $toDate = $_POST['toDate'];
                                $exportType = isset($_POST['exportType']) ? $_POST['exportType'] : 'excel';
                            ?>
                            <!-- Report Display -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Report (<?php echo $fromDate; ?> to <?php echo $toDate; ?>)</h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>                                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                            <div class="dropdown-header">Export Options:</div>
                                            <a class="dropdown-item" href="direct_download.php?start=<?php echo urlencode($fromDate); ?>&end=<?php echo urlencode($toDate); ?>&type=excel">Export to Excel</a>
                                            <a class="dropdown-item" href="direct_download.php?start=<?php echo urlencode($fromDate); ?>&end=<?php echo urlencode($toDate); ?>&type=pdf">Export to PDF</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Total Classes</th>
                                                <th>Classes Present</th>
                                                <th>Classes Absent</th>
                                                <th>Attendance %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php                                            $query = "SELECT s.firstName, s.lastName, s.admissionNumber, c.className,
                                                     COUNT(DISTINCT sa.date) as totalClasses,
                                                     SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as presentClasses,
                                                     SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as absentClasses
                                                     FROM tblstudents s
                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                     INNER JOIN tblclass c ON s.classId = c.Id                                                     LEFT JOIN tblsubjectattendance sa ON s.Id = sa.studentId 
                                                        AND sa.date BETWEEN ? AND ?
                                                        AND (sa.subjectId = ? OR sa.subjectTeacherId = ?)
                                                     WHERE sts.subjectTeacherId = ?
                                                     GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className
                                                     ORDER BY s.firstName ASC";                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("ssiii", $fromDate, $toDate, $_SESSION['subjectId'], $_SESSION['subjectId'], $_SESSION['userId']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $cnt = 1;
                                            while($row = $result->fetch_assoc()) {
                                                $attendancePercentage = ($row['totalClasses'] > 0) ? 
                                                    round(($row['presentClasses'] / $row['totalClasses']) * 100, 2) : 0;
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td><?php echo $row['firstName']; ?></td>
                                                    <td><?php echo $row['lastName']; ?></td>
                                                    <td><?php echo $row['admissionNumber']; ?></td>
                                                    <td><?php echo $row['className']; ?></td>
                                                    <td><?php echo $row['totalClasses']; ?></td>
                                                    <td><?php echo $row['presentClasses']; ?></td>
                                                    <td><?php echo $row['absentClasses']; ?></td>
                                                    <td><?php echo $attendancePercentage; ?>%</td>
                                                </tr>                                            <?php 
                                                $cnt++;
                                            } 
                                            ?>
                                        </tbody>
                                    </table>
                                </div>                                <div class="card-footer">
                                    <div class="d-flex justify-content-end">
                                        <a href="direct_download.php?start=<?php echo urlencode($fromDate); ?>&end=<?php echo urlencode($toDate); ?>&type=excel" class="btn btn-success mr-2">
                                            <i class="fas fa-file-excel mr-2"></i>Download Excel
                                        </a>
                                        <a href="direct_download.php?start=<?php echo urlencode($fromDate); ?>&end=<?php echo urlencode($toDate); ?>&type=pdf" class="btn btn-danger">
                                            <i class="fas fa-file-pdf mr-2"></i>Download PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                            ?>
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
    </a>    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/tcpdf-check.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script src="js/report-download.js"></script>
</body>
</html>
