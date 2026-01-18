<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Get active session term
$sessionQuery = "SELECT Id FROM tblsessionterm WHERE isActive = 1 LIMIT 1";
$sessionResult = $conn->query($sessionQuery);
$sessionTerm = $sessionResult->fetch_assoc();
$sessionTermId = $sessionTerm['Id'] ?? 1;

// Get Student Analysis Data with improved query
$query = "SELECT 
            s.Id,
            s.firstName,
            s.lastName,
            s.admissionNumber,
            c.className,
            sbj.subjectName,
            sbj.subjectCode,
            COUNT(DISTINCT sa.date) as totalClasses,
            SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as attended,
            ROUND((SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / GREATEST(COUNT(DISTINCT sa.date), 1)) * 100, 1) as attendancePercentage,
            MAX(sa.date) as lastAttendance,
            GROUP_CONCAT(DISTINCT 
                CASE WHEN sa.status = 0 
                THEN CONCAT(DATE_FORMAT(sa.date, '%d %b %Y'))
                END ORDER BY sa.date DESC SEPARATOR ',') as recentAbsences
          FROM tblstudents s
          INNER JOIN tblclass c ON c.Id = s.classId
          INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id
          INNER JOIN tblsubjectteacher st ON st.Id = sts.subjectTeacherId
          INNER JOIN tblsubjects sbj ON sbj.Id = st.subjectId
          LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id 
            AND sa.subjectTeacherId = st.Id
            AND sa.sessionTermId = ?
          WHERE st.Id = ?
          GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className, sbj.subjectName, sbj.subjectCode
          ORDER BY attendancePercentage DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $sessionTermId, $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

// Calculate overall statistics
$stats = [
    'excellent' => 0,
    'good' => 0,
    'warning' => 0,
    'critical' => 0,
    'total' => 0
];

$studentData = [];
while ($row = $result->fetch_assoc()) {
    $stats['total']++;
    if ($row['attendancePercentage'] >= 90) {
        $stats['excellent']++;
        $row['status'] = 'excellent';
    } elseif ($row['attendancePercentage'] >= 75) {
        $stats['good']++;
        $row['status'] = 'good';
    } elseif ($row['attendancePercentage'] >= 60) {
        $stats['warning']++;
        $row['status'] = 'warning';
    } else {
        $stats['critical']++;
        $row['status'] = 'critical';
    }
    $studentData[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Student Performance Analysis</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .performance-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .excellent { background-color: #1cc88a; }
        .good { background-color: #36b9cc; }
        .warning { background-color: #f6c23e; }
        .critical { background-color: #e74a3b; }
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
                        <h1 class="h3 mb-0 text-gray-800">Student Performance Analysis</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Performance Analysis</li>
                        </ol>
                    </div>

                    <!-- Performance Categories -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Attendance Categories</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-3 col-md-6">
                                            <div class="card h-100 border-left-success shadow">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                                Excellent Attendance</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">>90%</div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="performance-indicator excellent"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-md-6">
                                            <div class="card h-100 border-left-info shadow">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                                Good Attendance</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">75-90%</div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="performance-indicator good"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-md-6">
                                            <div class="card h-100 border-left-warning shadow">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                                Warning Level</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">60-75%</div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="performance-indicator warning"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-3 col-md-6">
                                            <div class="card h-100 border-left-danger shadow">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                                Critical Level</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><60%</div>
                                                        </div>
                                                        <div class="col-auto">
                                                            <div class="performance-indicator critical"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Performance Records -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Performance Records</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Student Name</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Subject</th>
                                                <th>Total Classes</th>
                                                <th>Classes Attended</th>
                                                <th>Attendance %</th>
                                                <th>Status</th>
                                                <th>Last Attendance</th>
                                                <th>Recent Absences</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($studentData)): ?>
                                                <?php foreach($studentData as $row): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars(str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['admissionNumber']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['className']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['subjectName'] . ' (' . $row['subjectCode'] . ')'); ?></td>
                                                        <td><?php echo $row['totalClasses']; ?></td>
                                                        <td><?php echo $row['attended']; ?></td>
                                                        <td>
                                                            <div class="progress">
                                                                <div class="progress-bar bg-<?php echo $row['status']; ?>" 
                                                                     role="progressbar" 
                                                                     style="width: <?php echo $row['attendancePercentage']; ?>%"
                                                                     aria-valuenow="<?php echo $row['attendancePercentage']; ?>" 
                                                                     aria-valuemin="0" 
                                                                     aria-valuemax="100">
                                                                    <?php echo $row['attendancePercentage']; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="status-box status-<?php echo $row['status']; ?>">
                                                                <?php echo ucfirst($row['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo $row['lastAttendance'] ? date('d M Y', strtotime($row['lastAttendance'])) : 'N/A'; ?></td>
                                                        <td>
                                                            <?php 
                                                            if ($row['recentAbsences']) {
                                                                $absences = explode(',', $row['recentAbsences']);
                                                                foreach ($absences as $date) {
                                                                    echo '<span class="badge badge-danger">' . $date . '</span> ';
                                                                }
                                                            } else {
                                                                echo '<span class="badge badge-success">No recent absences</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="10" class="text-center">
                                                        <div class="alert alert-info">
                                                            No student performance data available. Make sure students are assigned and attendance has been taken.
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>    <?php if ($result->num_rows > 0): ?>
    <script>
        $(document).ready(function () {
            $('#dataTableHover').DataTable({
                "order": [[5, "desc"]], // Sort by attendance percentage by default
                "pageLength": 25
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>
