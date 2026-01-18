<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Get Subject Teacher Information
$query = "SELECT s.subjectName, s.subjectCode
          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          WHERE st.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$subjectInfo = $result->fetch_assoc();

// Export function
if(isset($_POST['export'])) {
    $format = $_POST['format'] ?? 'csv';
    $threshold = $_POST['threshold'] ?? 75; // Default attendance threshold
    
    // Get student performance data
    $performanceQuery = "SELECT 
                            s.admissionNumber,
                            s.firstName,
                            s.lastName,
                            c.className,
                            COUNT(DISTINCT sa.date) as totalDays,
                            SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as presentDays,
                            SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as absentDays,
                            ROUND(
                                (SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) * 100.0) / 
                                COUNT(DISTINCT sa.date),
                                2
                            ) as attendancePercentage
                        FROM tblstudents s
                        INNER JOIN tblclass c ON c.Id = s.classId
                        INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id
                        LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id 
                            AND sa.subjectTeacherId = sts.subjectTeacherId
                        WHERE sts.subjectTeacherId = ?
                        GROUP BY s.Id, s.admissionNumber, s.firstName, s.lastName, c.className
                        HAVING COUNT(DISTINCT sa.date) > 0
                        ORDER BY c.className, attendancePercentage DESC";

    $performanceStmt = $conn->prepare($performanceQuery);
    $performanceStmt->bind_param("i", $_SESSION['userId']);
    $performanceStmt->execute();
    $performanceResult = $performanceStmt->get_result();

    if($format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="performance_report_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            'Admission Number', 
            'Student Name', 
            'Class', 
            'Total Days', 
            'Present Days',
            'Absent Days',
            'Attendance %',
            'Performance Status'
        ));
        
        // Add data
        while($row = $performanceResult->fetch_assoc()) {
            $status = $row['attendancePercentage'] >= $threshold ? 'Good Standing' : 'Needs Improvement';
            
            fputcsv($output, array(
                $row['admissionNumber'],
                $row['firstName'] . ' ' . $row['lastName'],
                $row['className'],
                $row['totalDays'],
                $row['presentDays'],
                $row['absentDays'],
                $row['attendancePercentage'] . '%',
                $status
            ));
        }
        
        fclose($output);
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
    <title>Export Performance Report</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php";?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php";?>
                <!-- Topbar -->
                
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Export Performance Report</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Subject: <?php echo $subjectInfo['subjectName']; ?> (<?php echo $subjectInfo['subjectCode']; ?>)</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label>Attendance Threshold (%)</label>
                                            <input type="number" name="threshold" class="form-control" value="75" min="0" max="100" required>
                                            <small class="form-text text-muted">Students below this percentage will be marked as "Needs Improvement"</small>
                                        </div>
                                        <div class="form-group">
                                            <label>Export Format</label>
                                            <select name="format" class="form-control">
                                                <option value="csv">CSV (Excel)</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="export" class="btn btn-primary">
                                            <i class="fas fa-file-export"></i> Export Performance Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!---Container Fluid-->
            </div>
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
