<?php 
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';
// Try using composer autoload first
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php'; // For TCPDF
} else if (file_exists('../vendor/tcpdf/tcpdf.php')) {
    // Fallback to direct include if downloaded manually
    require '../vendor/tcpdf/tcpdf.php';
} else {
    die("TCPDF library not found. Please install it using Composer or download it manually.");
}

// Include session utilities if available
if (file_exists('Includes/init_session.php')) {
    include 'Includes/init_session.php';
}

// For debugging - save diagnostic info
$debug = array(
    'session' => $_SESSION,
    'get' => $_GET,
    'post' => $_POST
);

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Ensure subject ID is available
if (!isset($_SESSION['subjectId']) && isset($_SESSION['userId'])) {
    // Try to get subject ID from the database    $query = "SELECT subjectId FROM tblsubjectteacher WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['subjectId'] = $row['subjectId'];
    } else {
        // Log error for debugging
        file_put_contents('debug_download.log', 'Could not find subject ID for teacher: ' . $_SESSION['userId'] . "\n", FILE_APPEND);
    }
}

// Get parameters - support both GET and POST (for direct form submissions)
$type = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 'excel');
$fromDate = isset($_GET['start']) ? $_GET['start'] : (isset($_POST['start']) ? $_POST['start'] : date('Y-m-01'));
$toDate = isset($_GET['end']) ? $_GET['end'] : (isset($_POST['end']) ? $_POST['end'] : date('Y-m-t'));
$threshold = isset($_GET['threshold']) ? intval($_GET['threshold']) : (isset($_POST['threshold']) ? intval($_POST['threshold']) : 0);

// Check if remarks column exists
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0);
}

$hasRemarksColumn = columnExists($conn, 'tblsubjectattendance', 'remarks');

// Get subject information
$query = "SELECT s.subjectName, s.subjectCode 
          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          WHERE st.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$subjectInfo = $result->fetch_assoc();

// If subject info not found, try to retrieve it directly
if (!$subjectInfo && isset($_SESSION['subjectId'])) {
    $query = "SELECT subjectName, subjectCode FROM tblsubjects WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['subjectId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjectInfo = $result->fetch_assoc();
}

// If still no subject info, create a placeholder
if (!$subjectInfo) {
    $subjectInfo = [
        'subjectName' => 'Unknown Subject',
        'subjectCode' => 'UNKNOWN'
    ];
}

// Check if debugging is requested
if (isset($_GET['debug'])) {
    echo "<h2>Debug Information:</h2>";
    echo "<pre>";
    echo "Session Variables:\n";
    print_r($_SESSION);
    echo "\n\nGET Variables:\n";
    print_r($_GET);
    echo "</pre>";
}

// Log important information
file_put_contents('download_debug.log', 
    "Time: " . date('Y-m-d H:i:s') . 
    "\nSession: " . json_encode($_SESSION) . 
    "\nGET: " . json_encode($_GET) .
    "\n\n", FILE_APPEND);

// Ensure we have the required session variables
if (!isset($_SESSION['subjectId'])) {
    die("Error: Subject ID not found in session. Please go back and try again.");
}

// Fetch attendance summary for each student
try {
    $query = "SELECT 
        s.firstName,
        s.lastName,
        s.admissionNumber,
        c.className,
        COUNT(DISTINCT sa.date) as totalDays,
        SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as daysPresent,
        SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as daysAbsent";

    if ($hasRemarksColumn) {
        $query .= ",
        GROUP_CONCAT(
            CONCAT(
                DATE_FORMAT(sa.date, '%Y-%m-%d'),
                ': ',
                CASE WHEN sa.status = 1 THEN 'Present' ELSE 'Absent' END,
                CASE WHEN sa.remarks IS NOT NULL THEN CONCAT(' (', sa.remarks, ')') ELSE '' END
            )
            ORDER BY sa.date DESC
            SEPARATOR '\n'
        ) as attendance_details";
    } else {
        $query .= ",
        GROUP_CONCAT(
            CONCAT(
                DATE_FORMAT(sa.date, '%Y-%m-%d'),
                ': ',
                CASE WHEN sa.status = 1 THEN 'Present' ELSE 'Absent' END
            )
            ORDER BY sa.date DESC
            SEPARATOR '\n'
        ) as attendance_details";
    }

    $query .= " FROM tblstudents s
    INNER JOIN tblclass c ON c.Id = s.classId
    INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id    LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id 
        AND sa.date BETWEEN ? AND ?
        AND (sa.subjectId = ? OR sa.subjectTeacherId = ?)
    WHERE sts.subjectTeacherId = ?
    GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className
    ORDER BY s.lastName ASC, s.firstName ASC";

    // Add threshold filter if specified
    if ($threshold > 0) {
        // Modify query to filter by threshold
        $query = str_replace(
            "GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className", 
            "GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className
             HAVING (SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT sa.date), 0)) * 100 < ?
                OR COUNT(DISTINCT sa.date) = 0",
            $query
        );
        
        // Update order by clause for threshold query
        $query = str_replace(
            "ORDER BY s.lastName ASC, s.firstName ASC",
            "ORDER BY (SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(DISTINCT sa.date), 0)) * 100 ASC, 
                     s.lastName ASC, s.firstName ASC",
            $query
        );
        
        // Prepare statement with threshold parameter
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error . " SQL: " . $query);
        }
        $stmt->bind_param("ssiiii", $fromDate, $toDate, $_SESSION['subjectId'], $_SESSION['subjectId'], $_SESSION['userId'], $threshold);
    } else {
        // Standard query without threshold filtering
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare query: " . $conn->error . " SQL: " . $query);
        }
        $stmt->bind_param("ssiii", $fromDate, $toDate, $_SESSION['subjectId'], $_SESSION['subjectId'], $_SESSION['userId']);
    }

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Log success
    file_put_contents('download_debug.log', 
        "Query executed successfully. Row count: " . $result->num_rows . 
        "\n\n", FILE_APPEND);
        
} catch (Exception $e) {
    // Log the error
    file_put_contents('download_error.log', 
        "Time: " . date('Y-m-d H:i:s') . 
        "\nError: " . $e->getMessage() . 
        "\nQuery: " . $query .
        "\n\n", FILE_APPEND);
    
    // Display user-friendly error
    die("An error occurred while generating the report. Please try again or contact support.<br>Details: " . $e->getMessage());
}

// Process based on export type
if ($type === 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Subject_Attendance_' . date('Y-m-d') . '.xls"');
      // Create Excel table
    echo '<table border="1">
        <tr>
            <th colspan="10" style="background-color: #f2f2f2; text-align: center; font-size: 14pt;">
                ' . $subjectInfo['subjectName'] . ' (' . $subjectInfo['subjectCode'] . ') - Attendance Report
                ' . ($threshold > 0 ? '(Below '.$threshold.'% Attendance)' : '') . '
            </th>
        </tr>
        <tr>
            <th colspan="10" style="text-align: center;">
                Period: ' . date('F j, Y', strtotime($fromDate)) . ' to ' . date('F j, Y', strtotime($toDate)) . '
            </th>
        </tr>
        <tr style="background-color: #e6e6e6;">
            <th>#</th>
            <th>Student Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Total Days</th>
            <th>Days Present</th>
            <th>Days Absent</th>
            <th>Attendance %</th>
            <th>Status</th>
            <th>Attendance Details</th>
        </tr>';
    
    $count = 1;
    while ($row = $result->fetch_assoc()) {
        $attendancePercent = $row['totalDays'] > 0 ? 
            round(($row['daysPresent'] / $row['totalDays']) * 100, 1) : 0;
        
        $status = 'N/A';
        if ($row['totalDays'] > 0) {
            if ($attendancePercent >= 75) {
                $status = 'Good';
            } elseif ($attendancePercent >= 60) {
                $status = 'Warning';
            } else {
                $status = 'Critical';
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
            <td>' . $status . '</td>
            <td style="white-space: pre-wrap;">' . $row['attendance_details'] . '</td>
        </tr>';
    }
    
    echo '</table>';
} else if ($type === 'pdf') {    // Check if TCPDF class exists
    if (!class_exists('TCPDF')) {
        echo "<div class='alert alert-danger'>";
        echo "<h3>TCPDF Library Missing</h3>";
        echo "<p>The TCPDF library is required for PDF generation but is not installed.</p>";
        echo "<h4>Installation Options:</h4>";
        echo "<p><strong>Option 1:</strong> <a href='../install_tcpdf.php' target='_blank' class='btn btn-primary btn-sm'>Click here for installation instructions</a></p>";
        echo "<p><strong>Option 2:</strong> You can download the report as Excel instead by going back and selecting the Excel option.</p>";
        
        // Check if we might be able to install TCPDF automatically
        if (function_exists('file_get_contents') && function_exists('file_put_contents') && is_writable('../vendor')) {
            echo "<p><strong>Option 3:</strong> <a href='../install_tcpdf_direct.php' target='_blank' class='btn btn-success btn-sm'>Install TCPDF Automatically</a></p>";
        }
        
        echo "</div>";
        exit;
    }
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // Set document information
    $pdf->SetCreator('Subject Attendance System');
    $pdf->SetAuthor($_SESSION['firstName'] . ' ' . $_SESSION['lastName']);
    $title = $subjectInfo['subjectName'] . ' - Attendance Report';
    if ($threshold > 0) {
        $title .= ' (Below ' . $threshold . '% Attendance)';
    }
    $pdf->SetTitle($title);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 11);
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $subjectInfo['subjectName'] . ' (' . $subjectInfo['subjectCode'] . ')', 0, 1, 'C');
    if ($threshold > 0) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Students Below ' . $threshold . '% Attendance', 0, 1, 'C');
    }
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Attendance Report: ' . date('F j, Y', strtotime($fromDate)) . ' to ' . date('F j, Y', strtotime($toDate)), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $header = array('Name', 'Adm. No', 'Class', 'Days', 'Present', 'Absent', '%', 'Status');
    $w = array(50, 25, 20, 15, 20, 20, 15, 25);
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('helvetica', '', 10);
    $fill = false;
    
    while ($row = $result->fetch_assoc()) {
        $attendancePercent = $row['totalDays'] > 0 ? 
            round(($row['daysPresent'] / $row['totalDays']) * 100, 1) : 0;
        
        $status = 'N/A';
        if ($row['totalDays'] > 0) {
            if ($attendancePercent >= 75) {
                $status = 'Good';
            } elseif ($attendancePercent >= 60) {
                $status = 'Warning';
            } else {
                $status = 'Critical';
            }
        }
        
        $pdf->Cell($w[0], 6, str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']), 1);
        $pdf->Cell($w[1], 6, $row['admissionNumber'], 1);
        $pdf->Cell($w[2], 6, $row['className'], 1);
        $pdf->Cell($w[3], 6, $row['totalDays'], 1, 0, 'C');
        $pdf->Cell($w[4], 6, $row['daysPresent'], 1, 0, 'C');
        $pdf->Cell($w[5], 6, $row['daysAbsent'], 1, 0, 'C');
        $pdf->Cell($w[6], 6, $attendancePercent . '%', 1, 0, 'C');
        $pdf->Cell($w[7], 6, $status, 1);
        $pdf->Ln();
    }
    
    // Output the PDF
    $pdf->Output('attendance_report_' . date('Y-m-d') . '.pdf', 'D');
} else {
    // Invalid type requested
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid export type requested';
}

// The following code appears to be a duplicate or incomplete implementation
// It's been commented out to prevent syntax errors
/*
// This appears to be an HTML attendance report that was left incomplete
// Below is the fixed version of what seems to be intended

// Define a function for the HTML report
function generateHTMLReport($conn, $fromDate, $toDate) {
    // Fetch the data
    $query = "SELECT 
                 s.firstName, 
                 s.lastName,
                 s.admissionNumber,
                 c.className,
                 COUNT(sa.Id) as totalClasses,
                 SUM(CASE WHEN sa.status = '1' THEN 1 ELSE 0 END) as presentClasses,
                 SUM(CASE WHEN sa.status = '0' THEN 1 ELSE 0 END) as absentClasses
                 FROM tblstudents s
                 INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                 INNER JOIN tblclass c ON s.classId = c.Id
                 LEFT JOIN tblsubjectattendance sa ON s.Id = sa.studentId 
                    AND sa.dateTimeTaken BETWEEN ? AND ?
                 WHERE sts.subjectTeacherId = ?
                 GROUP BY s.Id, s.firstName, s.lastName, s.admissionNumber, c.className
                 ORDER BY s.firstName ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $fromDate, $toDate, $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cnt = 1;
    
    // Generate HTML output
    $html = '<table border="1" cellpadding="5" cellspacing="0" width="100%">
              <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Admission Number</th>
                <th>Class</th>
                <th>Total Classes</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Attendance %</th>
                <th>Remarks</th>
              </tr>';
    
    while($row = $result->fetch_assoc()) {
        $attendancePercentage = ($row['totalClasses'] > 0) ? 
            round(($row['presentClasses'] / $row['totalClasses']) * 100, 2) : 0;
        
        // Calculate remarks based on attendance percentage
        $remarks = "";
        if($attendancePercentage >= 90) {
            $remarks = "Excellent";
        } else if($attendancePercentage >= 80) {
            $remarks = "Good";
        } else if($attendancePercentage >= 75) {
            $remarks = "Average";
        } else {
            $remarks = "Poor - Needs Improvement";
        }
        
        $html .= "<tr>
                <td>".$cnt."</td>
                <td>".$row['firstName']."</td>
                <td>".$row['lastName']."</td>
                <td>".$row['admissionNumber']."</td>
                <td>".$row['className']."</td>
                <td>".$row['totalClasses']."</td>
                <td>".$row['presentClasses']."</td>
                <td>".$row['absentClasses']."</td>
                <td>".$attendancePercentage."%</td>
                <td>".$remarks."</td>
              </tr>";
        $cnt++;
    }
      $html .= '</table>';
    return $html;
}
*/
// The HTML code below was incomplete and causing syntax errors - commenting it out
/*
                    <td>".$row['absentClasses']."</td>
                    <td>".$attendancePercentage."%</td>
                    <td>".$remarks."</td>
                </tr>";
            $cnt++;
        }
        ?>
    </tbody>
</table>
*/
