<?php 
error_reporting(0);
include '../Includes/dbcon.php';

// Validate that user is a ClassTeacher
session_start();
if (strlen($_SESSION['userId']) == 0) {
    header('location:classTeacherLogin.php');
    exit();
}

// Get the date range parameters
$fromDate = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
$toDate = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$filename = "Attendance_Report_" . date('Y-m-d', strtotime($fromDate)) . "_to_" . date('Y-m-d', strtotime($toDate)) . "_" . time();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// Get class info
$classQuery = "SELECT tblclass.className, tblclassarms.classArmName 
               FROM tblclassteacher
               INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
               INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
               WHERE tblclassteacher.Id = ?";
$stmtClass = $conn->prepare($classQuery);
$stmtClass->bind_param("i", $_SESSION['userId']);
$stmtClass->execute();
$classResult = $stmtClass->get_result();
$classInfo = $classResult->fetch_assoc();
?>
<html>
<head>
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid black; padding: 5px; }
    .header { background-color: #f2f2f2; text-align: center; font-weight: bold; }
    .present { background-color: #E8F5E9; }
    .absent { background-color: #FFEBEE; }
    .center { text-align: center; }
</style>
</head>
<body>
<table border="1">
    <tr>
        <th colspan="12" class="header" style="font-size: 16pt; height: 40px; text-align: center; vertical-align: middle; background-color: #f2f2f2;">
            Attendance Report
        </th>
    </tr>
    <tr>
        <th colspan="12" class="header" style="font-size: 14pt; height: 30px; text-align: center; vertical-align: middle; background-color: #f2f2f2;">
            <?php echo $classInfo['className'] . ' - ' . $classInfo['classArmName']; ?>
        </th>
    </tr>
    <tr>
        <th colspan="12" class="header" style="font-size: 12pt; height: 25px; text-align: center; vertical-align: middle; background-color: #f2f2f2;">
            <?php echo date('F d, Y', strtotime($fromDate)) . ' to ' . date('F d, Y', strtotime($toDate)); ?>
        </th>
    </tr>
    <tr><td colspan="12">&nbsp;</td></tr>
    <thead>
        <tr style="background-color: #e6e6e6; height: 25px;">
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">#</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">First Name</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Last Name</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Other Name</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Admission No</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Student ID</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Class</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Class Arm</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Session</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Term</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Status</th>
            <th style="background-color: #e6e6e6; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid black;">Date</th>
        </tr>
    </thead>

<?php 
$cnt=1;

// Main query to fetch all attendance records with student and class details
$query = "SELECT 
        tblstudents.firstName,
        tblstudents.lastName,
        tblstudents.otherName,
        tblstudents.admissionNumber,
        tblstudents.student_id,
        tblclass.className,
        tblclassarms.classArmName,
        tblsessionterm.sessionName,
        tblterm.termName,
        tblattendance.status,
        tblattendance.dateTimeTaken
        FROM tblattendance
        INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
        INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
        INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
        INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
        WHERE DATE(tblattendance.dateTimeTaken) BETWEEN ? AND ?
        AND tblattendance.classId = ? AND tblattendance.classArmId = ?
        ORDER BY tblattendance.dateTimeTaken ASC, tblstudents.firstName ASC, tblstudents.lastName ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssii", $fromDate, $toDate, $_SESSION['classId'], $_SESSION['classArmId']);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $cnt = 1;
    while ($row = $result->fetch_assoc()) {
        echo "<tr class='" . ($cnt % 2 == 0 ? 'even' : 'odd') . "'>";
        echo "<td class='center'>" . $cnt++ . "</td>";
        echo "<td>" . htmlspecialchars($row['firstName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['lastName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['otherName']) . "</td>";
        echo "<td class='center'>" . htmlspecialchars($row['admissionNumber']) . "</td>";
        echo "<td class='center'>" . htmlspecialchars($row['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['className']) . "</td>";
        echo "<td>" . htmlspecialchars($row['classArmName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sessionName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['termName']) . "</td>";
        
        $statusValue = $row['status'];
        $statusClass = $statusValue == '1' ? 'present' : 'absent';
        $statusText = $statusValue == '1' ? 'Present' : 'Absent';
        echo "<td class='center " . $statusClass . "'><b>" . $statusText . "</b></td>";
        echo "<td class='center'>" . date('Y-m-d', strtotime($row['dateTimeTaken'])) . "</td>";
        echo "</tr>";
    }
    
    // Get unique dates count
    $dateQuery = "SELECT COUNT(DISTINCT DATE(dateTimeTaken)) as total_days 
                  FROM tblattendance 
                  WHERE DATE(dateTimeTaken) BETWEEN ? AND ?
                  AND classId = ? AND classArmId = ?";
    $stmtDate = $conn->prepare($dateQuery);
    $stmtDate->bind_param("ssii", $fromDate, $toDate, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmtDate->execute();
    $dateResult = $stmtDate->get_result();
    $totalDays = $dateResult->fetch_assoc()['total_days'];

    // Get total present and absent counts
    $presentQuery = "SELECT COUNT(*) as present_count FROM tblattendance 
                    WHERE DATE(dateTimeTaken) BETWEEN ? AND ?
                    AND classId = ? AND classArmId = ? AND status = 1";
    $stmtPresent = $conn->prepare($presentQuery);
    $stmtPresent->bind_param("ssii", $fromDate, $toDate, $_SESSION['classId'], $_SESSION['classArmId']);
    $stmtPresent->execute();
    $presentResult = $stmtPresent->get_result();
    $presentCount = $presentResult->fetch_assoc()['present_count'];
    
    $totalRecords = $result->num_rows; // Corrected: Use num_rows from the main query result
    $absentCount = $totalRecords - $presentCount;
    $attendancePercentage = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 2) : 0;
    
    echo "<tr><td colspan='12'>&nbsp;</td></tr>";
    echo "<tr>";
    echo "<td colspan='3' class='header'>Total Days: " . $totalDays . "</td>";
    echo "<td colspan='3' class='header'>Total Records: " . $totalRecords . "</td>";
    echo "<td colspan='3' class='header present'>Present: " . $presentCount . "</td>";
    echo "<td colspan='3' class='header absent'>Absent: " . $absentCount . " (" . $attendancePercentage . "%)</td>";
    echo "</tr>";
} else {
    echo "<tr><td colspan='12' style='text-align:center'>No Records Found</td></tr>";
}
?>
</table>
</body>
</html>
<?php
// Close database connections
$stmt->close(); // Close main query statement
$stmtClass->close();
$stmtDate->close();
$stmtPresent->close();
$conn->close();
?>