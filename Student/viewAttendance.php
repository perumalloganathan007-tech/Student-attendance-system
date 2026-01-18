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

// Get date range from request or default to current month
$today = new DateTime();
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : $today->format('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : $today->format('Y-m-t');

// Validate date range
$startDateTime = new DateTime($startDate);
$endDateTime = new DateTime($endDate);

if ($endDateTime < $startDateTime) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// Get student information and subject-wise attendance
$query = "SELECT 
            s.subjectName,
            s.subjectCode,
            COUNT(DISTINCT sa.date) as totalDays,
            SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as daysPresent,
            SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as daysAbsent,
            ROUND(SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) * 100.0 / 
                  NULLIF(COUNT(DISTINCT sa.date), 0), 1) as attendanceRate
          FROM tblsubjectteacher_student sts
          INNER JOIN tblsubjectteacher st ON st.Id = sts.subjectTeacherId
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          LEFT JOIN tblsubjectattendance sa ON sa.subjectTeacherId = st.Id 
            AND sa.studentId = sts.studentId
            AND sa.date BETWEEN ? AND ?
          WHERE sts.studentId = ?
          GROUP BY s.Id, s.subjectName, s.subjectCode
          ORDER BY s.subjectName";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $startDate, $endDate, $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

// Calculate overall attendance
$overallQuery = "SELECT 
                  COUNT(DISTINCT date) as totalDays,
                  SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as daysPresent,
                  SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as daysAbsent,
                  ROUND(SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) * 100.0 / 
                        NULLIF(COUNT(DISTINCT date), 0), 1) as attendanceRate
                FROM tblsubjectattendance 
                WHERE studentId = ? AND date BETWEEN ? AND ?";

$overallStmt = $conn->prepare($overallQuery);
$overallStmt->bind_param("iss", $_SESSION['userId'], $startDate, $endDate);
$overallStmt->execute();
$overallResult = $overallStmt->get_result();
$overall = $overallResult->fetch_assoc();

include('Includes/header.php');
?>

<!-- Container Fluid-->
<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Attendance Records</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">View Attendance</li>
        </ol>
    </div>

    <!-- Date Range Filter -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Filter by Date Range</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="startDate" class="sr-only">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="startDate" 
                                   value="<?php echo $startDate; ?>">
                        </div>
                        <div class="form-group mx-sm-3 mb-2">
                            <label for="endDate" class="sr-only">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="endDate" 
                                   value="<?php echo $endDate; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary mb-2">Filter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Subject-wise Attendance Records -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Subject-wise Attendance</h6>
                </div>
                <div class="table-responsive p-3">
                    <table class="table align-items-center table-flush table-hover">
                        <thead class="thead-light">
                                <tr>
                                    <th>Subject</th>
                                    <th>Total Days</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['subjectName'] . ' (' . $row['subjectCode'] . ')'; ?></td>
                                    <td><?php echo $row['totalDays']; ?></td>
                                    <td class="text-success"><?php echo $row['daysPresent']; ?></td>
                                    <td class="text-danger"><?php echo $row['daysAbsent']; ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo $row['attendanceRate']; ?>%"
                                                 aria-valuenow="<?php echo $row['attendanceRate']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $row['attendanceRate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                                <tr class="table-info">
                                    <th>Overall</th>
                                    <th><?php echo $overall['totalDays']; ?></th>
                                    <th class="text-success"><?php echo $overall['daysPresent']; ?></th>
                                    <th class="text-danger"><?php echo $overall['daysAbsent']; ?></th>
                                    <th>
                                        <div class="progress">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $overall['attendanceRate']; ?>%"
                                                 aria-valuenow="<?php echo $overall['attendanceRate']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $overall['attendanceRate']; ?>%
                                            </div>
                                        </div>
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('Includes/footer.php'); ?>
