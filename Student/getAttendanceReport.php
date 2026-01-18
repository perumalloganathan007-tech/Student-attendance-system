<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to prevent any unwanted output
ob_start();

// Custom error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error: [$errno] $errstr in $errfile on line $errline");
    return true;
});

try {
    include '../Includes/dbcon.php';
    include '../Includes/session.php';

    // Clear any existing output
    if (ob_get_length()) ob_clean();

    // Validate database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }

    // Validate that user is a Student
    if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'Student') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Access denied'
        ]);
        exit();
    }

    // Get student information
    $studentId = $_SESSION['userId'];
    $format = $_GET['format'] ?? 'json';
    $type = $_GET['type'] ?? '';

    // Validate input parameters
    if (empty($type)) {
        throw new Exception('Report type is required');
    }

    // Base query for attendance with LEFT JOIN to get subject and teacher info
    $baseQuery = "SELECT 
        DATE_FORMAT(sa.date, '%d/%m/%Y') as date,
        CASE 
            WHEN sa.status = '1' THEN 'Present'
            ELSE 'Absent'
        END as status,
        COALESCE(s.subjectName, 'N/A') as subject,
        COALESCE(CONCAT(st.firstName, ' ', st.lastName), 'N/A') as teacher
        FROM tblsubjectattendance sa
        LEFT JOIN tblsubjects s ON s.Id = sa.subjectId
        LEFT JOIN tblsubjectteachers st ON st.Id = sa.subjectTeacherId
        WHERE sa.studentId = ?";

    $params = [$studentId];
    $types = "i"; // For studentId

    // Add date filtering based on report type
    switch($type) {
        case 'monthly':
            $monthYear = $_GET['monthYear'] ?? date('Y-m');
            if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
                throw new Exception('Invalid month/year format');
            }
            $baseQuery .= " AND DATE_FORMAT(sa.date, '%Y-%m') = ?";
            $params[] = $monthYear;
            $types .= "s";
            break;
            
        case 'dateRange':
            $startDate = $_GET['startDate'] ?? '';
            $endDate = $_GET['endDate'] ?? '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                throw new Exception('Invalid date format');
            }
            $baseQuery .= " AND DATE(sa.date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
            break;
            
        case 'yearly':
            $year = $_GET['year'] ?? date('Y');
            if (!preg_match('/^\d{4}$/', $year)) {
                throw new Exception('Invalid year format');
            }
            $baseQuery .= " AND YEAR(sa.date) = ?";
            $params[] = $year;
            $types .= "i";
            break;
            
        default:
            throw new Exception('Invalid report type');
    }

    $baseQuery .= " ORDER BY sa.date DESC";

    // Prepare and execute statement
    $stmt = $conn->prepare($baseQuery);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = [];

    while($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'status' => $row['status'],
            'subject' => htmlspecialchars($row['subject']),
            'teacher' => htmlspecialchars($row['teacher'])
        ];
    }

    // Clear any buffered output before sending response
    if (ob_get_length()) ob_clean();

    if($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Date', 'Status', 'Subject', 'Teacher']);
        
        foreach($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => count($data) . ' records found'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in getAttendanceReport.php: " . $e->getMessage());
    
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while generating the report: ' . $e->getMessage()
    ]);
} finally {
    restore_error_handler();
}

// End output buffering and send response
ob_end_flush();
?>
