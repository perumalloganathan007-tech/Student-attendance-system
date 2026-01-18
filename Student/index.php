<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Student
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

// Get student information and attendance statistics
$query = "SELECT 
            s.admissionNumber,
            s.firstName,
            s.lastName,
            c.className,
            ca.classArmName,
            COUNT(DISTINCT sa.date) as totalDays,
            COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) as daysPresent,
            COUNT(DISTINCT CASE WHEN sa.status = 0 THEN sa.date END) as daysAbsent,
            ROUND(COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) * 100.0 / NULLIF(COUNT(DISTINCT sa.date), 0), 1) as attendanceRate
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
          LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id
          WHERE s.Id = ?
          GROUP BY s.Id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();

// Get recent attendance records
$recentQuery = "SELECT 
                sa.date,
                sa.status,
                s.subjectName,
                s.subjectCode,
                st.firstName as teacherFirstName,
                st.lastName as teacherLastName
              FROM tblsubjectattendance sa
              INNER JOIN tblsubjects s ON s.Id = sa.subjectId
              INNER JOIN tblsubjectteacher st ON st.Id = sa.subjectTeacherId
              WHERE sa.studentId = ?
              ORDER BY sa.date DESC
              LIMIT 5";

$recentStmt = $conn->prepare($recentQuery);
$recentStmt->bind_param("i", $_SESSION['userId']);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();

include('Includes/header.php');
?>

<!-- Container Fluid-->
<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Student Dashboard</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </div>

    <div class="row mb-3">
        <!-- Student Information Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Student Info</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['firstName'] . ' ' . $stats['lastName']; ?></div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span>Admission No: <?php echo $stats['admissionNumber']; ?></span>
                            </div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span>Class: <?php echo $stats['className'] . ' ' . $stats['classArmName']; ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Days Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total taken attendance Days</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['totalDays']; ?></div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span>Academic Year</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Days Present Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Days Present</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['daysPresent']; ?></div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-success"><?php echo $stats['attendanceRate']; ?>% Attendance Rate</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Days Absent Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Days Absent</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['daysAbsent']; ?></div>
                            <div class="mt-2 mb-0 text-muted text-xs">
                                <span class="text-danger">Missed Classes</span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                    </div>
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
                    <table class="table align-items-center table-flush table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($record = $recentResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                <td><?php echo $record['subjectName'] . ' (' . $record['subjectCode'] . ')'; ?></td>
                                <td><?php echo $record['teacherFirstName'] . ' ' . $record['teacherLastName']; ?></td>
                                <td>
                                    <?php if ($record['status'] == 1): ?>
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
                <div class="card-footer text-center">
                    <a class="m-0 small text-primary card-link" href="viewAttendance.php">View More <i
                            class="fas fa-chevron-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('Includes/footer.php'); ?>
