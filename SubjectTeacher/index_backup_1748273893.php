<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Simple session validation for Subject Teacher
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'SubjectTeacher') {
    header("Location: ../subjectTeacherLogin.php");
    exit();
}

// Check if required tables exist first
$tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
$tableExists = $tableCheck->num_rows > 0;

if (!$tableExists) {
    // Create missing table
    $createTable = "CREATE TABLE IF NOT EXISTS `tblsubjectteacher_student` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `subjectTeacherId` int(11) NOT NULL,
      `studentId` int(11) NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `unique_subject_teacher_student` (`subjectTeacherId`, `studentId`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTable);
      // Create attendance table too
    $createAttendance = "CREATE TABLE IF NOT EXISTS `tblsubjectattendance` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `studentId` int(11) NOT NULL,
      `subjectTeacherId` int(11) NOT NULL,
      `subjectId` int(11) NOT NULL,
      `classId` int(11) DEFAULT 1,
      `classArmId` int(11) DEFAULT 1,
      `sessionTermId` int(11) DEFAULT 1,
      `status` tinyint(1) NOT NULL COMMENT '1=Present, 0=Absent',
      `date` date NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      KEY `studentId` (`studentId`),
      KEY `subjectTeacherId` (`subjectTeacherId`),
      KEY `subjectId` (`subjectId`),
      KEY `date` (`date`),
      KEY `idx_attendance_lookup` (`subjectTeacherId`, `date`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conn->query($createAttendance)) {
        error_log("Failed to create tblsubjectattendance: " . $conn->error);
    }
}

// Verify that tblsubjectattendance table has the correct structure
$verifyColumns = $conn->query("SHOW COLUMNS FROM tblsubjectattendance LIKE 'date'");
if (!$verifyColumns || $verifyColumns->num_rows == 0) {
    // The date column doesn't exist, let's recreate the table with proper structure
    $conn->query("DROP TABLE IF EXISTS tblsubjectattendance");
    $createAttendance = "CREATE TABLE `tblsubjectattendance` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `studentId` int(11) NOT NULL,
      `subjectTeacherId` int(11) NOT NULL,
      `subjectId` int(11) NOT NULL,
      `classId` int(11) DEFAULT 1,
      `classArmId` int(11) DEFAULT 1,
      `sessionTermId` int(11) DEFAULT 1,
      `status` tinyint(1) NOT NULL COMMENT '1=Present, 0=Absent',
      `date` date NOT NULL,
      `dateCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      KEY `studentId` (`studentId`),
      KEY `subjectTeacherId` (`subjectTeacherId`),
      KEY `subjectId` (`subjectId`),
      KEY `date` (`date`),
      KEY `idx_attendance_lookup` (`subjectTeacherId`, `date`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->query($createAttendance);
}

// Get Subject Teacher Information and Statistics
$query = "SELECT 
            s.subjectName,
            s.Id as subjectId,
            COALESCE(s.subjectCode, CONCAT('SUB', s.Id)) as subjectCode,
            COUNT(DISTINCT sts.studentId) as totalStudents,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() THEN sa.studentId END) as todayAttendance,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 1 THEN sa.studentId END) as todayPresent,
            COUNT(DISTINCT CASE WHEN sa.date = CURDATE() AND sa.status = 0 THEN sa.studentId END) as todayAbsent
          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          LEFT JOIN tblsubjectteacher_student sts ON sts.subjectTeacherId = st.Id
          LEFT JOIN tblsubjectattendance sa ON sa.subjectTeacherId = st.Id
          WHERE st.Id = ?
          GROUP BY s.Id";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['userId']);

// Add error handling for the query execution
try {
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    // Use null coalescing to handle cases where stats might be empty
    $_SESSION['subjectName'] = $stats['subjectName'] ?? 'Unknown Subject';
    $_SESSION['subjectId'] = $stats['subjectId'] ?? 0;
    $_SESSION['subjectCode'] = $stats['subjectCode'] ?? 'N/A';
    
    // If we have no stats, set defaults for the page to render properly
    if (!$stats) {
        $stats = [
            'subjectName' => $_SESSION['subjectName'],
            'subjectId' => $_SESSION['subjectId'],
            'subjectCode' => 'N/A',
            'totalStudents' => 0,
            'todayAttendance' => 0,
            'todayPresent' => 0,
            'todayAbsent' => 0
        ];
    }
} catch (Exception $e) {
    // Log error and create a default stats array
    error_log('Error executing subject teacher query: ' . $e->getMessage());
    
    $stats = [
        'subjectName' => 'Error Loading Subject',
        'subjectId' => 0,
        'subjectCode' => 'ERROR',
        'totalStudents' => 0,
        'todayAttendance' => 0,
        'todayPresent' => 0,
        'todayAbsent' => 0
    ];
    
    $_SESSION['subjectName'] = $stats['subjectName'];
    $_SESSION['subjectId'] = $stats['subjectId'];
}

// Get recent attendance records
$recentQuery = "SELECT 
                    s.admissionNumber,
                    s.firstName,
                    s.lastName,
                    sa.date,
                    sa.status
                FROM tblsubjectattendance sa
                INNER JOIN tblstudents s ON s.Id = sa.studentId
                WHERE sa.subjectTeacherId = ?
                ORDER BY sa.date DESC, sa.dateTimeTaken DESC
                LIMIT 5";

try {
    $stmt = $conn->prepare($recentQuery);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $recentResult = $stmt->get_result();
} catch (Exception $e) {
    // If there's an error, create an empty result set
    error_log('Error getting recent attendance: ' . $e->getMessage());
    
    // Create an empty MySQLi Result object
    $recentResult = mysqli_query($conn, "SELECT 1 LIMIT 0");
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
    <title>Subject Teacher Dashboard</title>
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
                <!-- Container Fluid-->                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            Subject Teacher Dashboard (<?php echo $stats['subjectName'].' - '.$stats['subjectCode'];?>)
                            <small class="text-muted">(<?php echo $stats['subjectName'] . ' - ' . $stats['subjectCode']; ?>)</small>
                        </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>

                    <div class="row mb-3">
                        <!-- Total Students Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Students</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['totalStudents']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                        <!-- Today's Attendance Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <a href="viewTodayAttendance.php" style="text-decoration: none; color: inherit;">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Today's Attendance</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo $stats['todayAttendance'] ?: '0'; ?>/<?php echo $stats['totalStudents']; ?>
                                                </div>
                                                <small class="text-primary">Click to view details →</small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-clipboard-list fa-2x text-success"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>                        <!-- Present Today Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <a href="viewTodayAttendance.php?filter=present" style="text-decoration: none; color: inherit;">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Present Today</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['todayPresent'] ?: '0'; ?></div>
                                                <small class="text-primary">Click to view details →</small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-check-circle fa-2x text-info"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>                        <!-- Absent Today Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <a href="viewTodayAttendance.php?filter=absent" style="text-decoration: none; color: inherit;">
                                        <div class="row align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-uppercase mb-1">Absent Today</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['todayAbsent'] ?: '0'; ?></div>
                                                <small class="text-primary">Click to view details →</small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-times-circle fa-2x text-danger"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Attendance Records -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Attendance Records</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Admission No.</th>
                                                <th>Student Name</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($record = $recentResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $record['admissionNumber']; ?></td>
                                                <td><?php echo $record['firstName'] . ' ' . $record['lastName']; ?></td>
                                                <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                                <td>
                                                    <?php if($record['status'] == 1): ?>
                                                        <span class="badge badge-success">Present</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Absent</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
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
    <script src="js/ruang-admin.min.js"></script>
</body>
</html>
