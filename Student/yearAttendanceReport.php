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

// Handle Export
if(isset($_GET['export']) && isset($_GET['year'])) {
    $year = intval($_GET['year']);
    
    // Get student info for filename
    $studentQuery = "SELECT firstName, lastName, admissionNumber FROM tblstudents WHERE Id = ?";
    $studentStmt = $conn->prepare($studentQuery);
    $studentStmt->bind_param("i", $_SESSION['userId']);
    $studentStmt->execute();
    $studentInfo = $studentStmt->get_result()->fetch_assoc();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_'.$studentInfo['admissionNumber'].'_'.$year.'_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add student info to CSV
    fputcsv($output, array('Student Report'));
    fputcsv($output, array('Name:', $studentInfo['firstName'].' '.$studentInfo['lastName']));
    fputcsv($output, array('Admission Number:', $studentInfo['admissionNumber']));
    fputcsv($output, array('Year:', $year));
    fputcsv($output, array()); // Empty line
    
    // Add headers
    fputcsv($output, array('Month', 'Total Days', 'Days Present', 'Days Absent', 'Attendance Rate (%)'));
    
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    // Get monthly data
    $monthlyQuery = "SELECT 
        MONTH(date) as month,
        COUNT(DISTINCT date) as totalDays,
        COUNT(DISTINCT CASE WHEN status = 1 THEN date END) as daysPresent,
        COUNT(DISTINCT CASE WHEN status = 0 THEN date END) as daysAbsent
    FROM tblsubjectattendance 
    WHERE studentId = ? AND YEAR(date) = ?
    GROUP BY MONTH(date)
    ORDER BY MONTH(date)";
    
    $monthlyStmt = $conn->prepare($monthlyQuery);
    $monthlyStmt->bind_param("ii", $_SESSION['userId'], $year);
    $monthlyStmt->execute();
    $monthlyResult = $monthlyStmt->get_result();
    
    $yearTotal = 0;
    $yearPresent = 0;
    $yearAbsent = 0;
    
    while($row = $monthlyResult->fetch_assoc()) {
        $totalDays = $row['totalDays'];
        $presentDays = $row['daysPresent'];
        $absentDays = $row['daysAbsent'];
        $rate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 1) : 0;
        
        fputcsv($output, array(
            $monthNames[$row['month']],
            $totalDays,
            $presentDays,
            $absentDays,
            $rate
        ));
        
        $yearTotal += $totalDays;
        $yearPresent += $presentDays;
        $yearAbsent += $absentDays;
    }
    
    // Add yearly total
    fputcsv($output, array()); // Empty line
    $yearRate = $yearTotal > 0 ? round(($yearPresent / $yearTotal) * 100, 1) : 0;
    fputcsv($output, array('Year Total', $yearTotal, $yearPresent, $yearAbsent, $yearRate));
    
    fclose($output);
    exit();
}

// Get year parameter, default to current year
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get student information and attendance statistics for the selected year
$query = "SELECT 
            s.admissionNumber,
            s.firstName,
            s.lastName,
            c.className,
            ca.classArmName,
            COALESCE(YEAR(sa.date), ?) as year,
            m.month,
            COUNT(DISTINCT sa.date) as totalDays,
            COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) as daysPresent,
            COUNT(DISTINCT CASE WHEN sa.status = 0 THEN sa.date END) as daysAbsent,
            ROUND(COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) * 100.0 / NULLIF(COUNT(DISTINCT sa.date), 0), 1) as attendanceRate
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
          CROSS JOIN (
              SELECT 1 as month UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
              UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8
              UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
          ) m
          LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id 
              AND YEAR(sa.date) = ? 
              AND MONTH(sa.date) = m.month
          WHERE s.Id = ?
          GROUP BY m.month
          ORDER BY m.month";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $selectedYear, $selectedYear, $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

// Get available years for dropdown
$yearsQuery = "SELECT DISTINCT YEAR(date) as year 
               FROM tblsubjectattendance 
               WHERE studentId = ?";
$yearsStmt = $conn->prepare($yearsQuery);
$yearsStmt->bind_param("i", $_SESSION['userId']);
$yearsStmt->execute();
$yearsResult = $yearsStmt->get_result();

// Create array of available years from attendance records
$available_years = array();
while ($yearRow = $yearsResult->fetch_assoc()) {
    $available_years[] = $yearRow['year'];
}

// Add current year and previous 5 years if not already in the list
$current_year = date('Y');
for ($i = 0; $i <= 5; $i++) {
    $year = $current_year - $i;
    if (!in_array($year, $available_years)) {
        $available_years[] = $year;
    }
}

// Sort years in descending order
rsort($available_years);

include('Includes/header.php');
?>

<!-- Container Fluid-->
<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Yearly Attendance Report (<?php echo $selectedYear; ?>)</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Year Report</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- Year Selection -->
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Select Year</h6>
                </div>
                <div class="card-body">
                    <form method="get" class="form-inline">
                        <div class="input-group">
                            <select name="year" class="form-control" id="yearSelect">
                                <?php
                                foreach ($available_years as $year) {
                                    $selected = ($year == $selectedYear) ? 'selected' : '';
                                    echo "<option value='{$year}' {$selected}>{$year}</option>";
                                }
                                ?>
                            </select>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> View
                                </button>
                                <button type="submit" name="export" class="btn btn-success">
                                    <i class="fas fa-file-export"></i> Export CSV
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Monthly Breakdown -->
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Attendance Breakdown</h6>
                </div>
                <div class="table-responsive p-3">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Month</th>
                                <th>Total Days</th>
                                <th>Present Days</th>
                                <th>Absent Days</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $monthNames = [
                                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                            ];
                            
                            $yearTotalDays = 0;
                            $yearPresentDays = 0;
                            $yearAbsentDays = 0;

                            while ($row = $result->fetch_assoc()) {
                                $yearTotalDays += $row['totalDays'];
                                $yearPresentDays += $row['daysPresent'];
                                $yearAbsentDays += $row['daysAbsent'];

                                $statusClass = $row['attendanceRate'] < 75 ? 'text-danger' : 
                                             ($row['attendanceRate'] < 90 ? 'text-warning' : 'text-success');
                                
                                echo "<tr>
                                    <td>{$monthNames[$row['month']]}</td>
                                    <td>{$row['totalDays']}</td>
                                    <td>{$row['daysPresent']}</td>
                                    <td>{$row['daysAbsent']}</td>
                                    <td class='{$statusClass}'>{$row['attendanceRate']}%</td>
                                </tr>";
                            }

                            // Calculate year total
                            $yearAttendanceRate = $yearTotalDays > 0 ? 
                                round(($yearPresentDays / $yearTotalDays) * 100, 1) : 0;
                            
                            $yearStatusClass = $yearAttendanceRate < 75 ? 'text-danger' : 
                                           ($yearAttendanceRate < 90 ? 'text-warning' : 'text-success');
                            ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td>Year Total</td>
                                <td><?php echo $yearTotalDays; ?></td>
                                <td><?php echo $yearPresentDays; ?></td>
                                <td><?php echo $yearAbsentDays; ?></td>
                                <td class="<?php echo $yearStatusClass; ?>"><?php echo $yearAttendanceRate; ?>%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Visual Summary -->
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Year Summary</h6>
                </div>
                <div class="card-body">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $yearPresentDays/$yearTotalDays*100; ?>%">
                            Present (<?php echo $yearPresentDays; ?> days)
                        </div>
                        <div class="progress-bar bg-danger" role="progressbar" 
                             style="width: <?php echo $yearAbsentDays/$yearTotalDays*100; ?>%">
                            Absent (<?php echo $yearAbsentDays; ?> days)
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <h4 class="<?php echo $yearStatusClass; ?>">
                            Overall Attendance Rate: <?php echo $yearAttendanceRate; ?>%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('Includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any specific JavaScript for the year report page here
});
</script>
