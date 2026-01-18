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

// Include TCPDF library
require_once('../vendor/tcpdf/tcpdf.php');

// Get date range
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-01');
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-t');

// Get student information
$query = "SELECT 
            s.admissionNumber,
            s.firstName,
            s.lastName,
            c.className,
            ca.classArmName
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
          WHERE s.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$studentInfo = $result->fetch_assoc();

// Get attendance records
$attendanceQuery = "SELECT 
                    s.subjectName,
                    s.subjectCode,
                    COUNT(DISTINCT sa.date) as totalDays,
                    COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) as daysPresent,
                    COUNT(DISTINCT CASE WHEN sa.status = 0 THEN sa.date END) as daysAbsent,
                    ROUND(COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.date END) * 100.0 / 
                          NULLIF(COUNT(DISTINCT sa.date), 0), 1) as attendanceRate
                   FROM tblsubjects s
                   INNER JOIN tblsubjectattendance sa ON sa.subjectId = s.Id
                   WHERE sa.studentId = ? AND sa.date BETWEEN ? AND ?
                   GROUP BY s.Id, s.subjectName, s.subjectCode
                   ORDER BY s.subjectName";

$stmt = $conn->prepare($attendanceQuery);
$stmt->bind_param("iss", $_SESSION['userId'], $startDate, $endDate);
$stmt->execute();
$attendanceResult = $stmt->get_result();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Student Attendance System');
$pdf->SetAuthor('Student Attendance System');
$pdf->SetTitle('Attendance Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add title
$pdf->Cell(0, 10, 'Student Attendance Report', 0, 1, 'C');
$pdf->Ln(5);

// Add student information
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 10, 'Student Information:', 0, 1);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Name: ' . $studentInfo['firstName'] . ' ' . $studentInfo['lastName'], 0, 1);
$pdf->Cell(0, 6, 'Admission Number: ' . $studentInfo['admissionNumber'], 0, 1);
$pdf->Cell(0, 6, 'Class: ' . $studentInfo['className'] . ' ' . $studentInfo['classArmName'], 0, 1);
$pdf->Cell(0, 6, 'Period: ' . date('M d, Y', strtotime($startDate)) . ' to ' . date('M d, Y', strtotime($endDate)), 0, 1);
$pdf->Ln(5);

// Add attendance table
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 7, 'Subject', 1);
$pdf->Cell(25, 7, 'Total Days', 1);
$pdf->Cell(25, 7, 'Present', 1);
$pdf->Cell(25, 7, 'Absent', 1);
$pdf->Cell(30, 7, 'Rate (%)', 1);
$pdf->Ln();

$pdf->SetFont('helvetica', '', 10);
$totalDays = 0;
$totalPresent = 0;

while ($row = $attendanceResult->fetch_assoc()) {
    $pdf->Cell(60, 6, $row['subjectName'] . ' (' . $row['subjectCode'] . ')', 1);
    $pdf->Cell(25, 6, $row['totalDays'], 1);
    $pdf->Cell(25, 6, $row['daysPresent'], 1);
    $pdf->Cell(25, 6, $row['daysAbsent'], 1);
    $pdf->Cell(30, 6, $row['attendanceRate'] . '%', 1);
    $pdf->Ln();
    
    $totalDays += $row['totalDays'];
    $totalPresent += $row['daysPresent'];
}

// Add total row
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 7, 'Overall', 1);
$pdf->Cell(25, 7, $totalDays, 1);
$pdf->Cell(25, 7, $totalPresent, 1);
$pdf->Cell(25, 7, $totalDays - $totalPresent, 1);
$pdf->Cell(30, 7, ($totalDays > 0 ? round(($totalPresent * 100.0) / $totalDays, 1) : 0) . '%', 1);

// Output PDF
$pdf->Output('attendance_report.pdf', 'D');
