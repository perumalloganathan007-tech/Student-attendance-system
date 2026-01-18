<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Initialize session variables
include 'Includes/init_session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Ensure subject info is available
if (!isset($_SESSION['userId']) || empty($_SESSION['userId'])) {
    echo "<div class='alert alert-danger' style='margin: 20px;'>
            <h4>Error: Teacher information is missing</h4>
            <p>Please go to the <a href='fix_session.php'>Session Repair Tool</a> page to check and fix your session.</p>
            <p>Then return to the <a href='index.php'>Dashboard</a> and try again.</p>
         </div>";
    include "Includes/footer.php";
    exit();
}

// Get Subject Teacher Information
$query = "SELECT 
    s.Id as subjectId,
    s.subjectName,
    s.subjectCode,
    st.Id as teacherId
FROM tblsubjectteacher st
INNER JOIN tblsubjects s ON s.Id = st.subjectId
WHERE st.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

// Get active session and term
$activeSessionQuery = "SELECT * FROM tblsessionterm WHERE isActive = 1";
$activeSessionResult = $conn->query($activeSessionQuery);
$activeSession = $activeSessionResult->fetch_assoc();

// Default to current year if no active session
$currentYear = date('Y');

// Get attendance statistics for this teacher's subject
$statsQuery = "SELECT 
                DATE_FORMAT(sa.date, '%Y-%m') as month,
                COUNT(DISTINCT sa.studentId) as totalStudents,
                COUNT(DISTINCT CASE WHEN sa.status = 1 THEN sa.studentId END) as presentStudents,
                COUNT(DISTINCT CASE WHEN sa.status = 0 THEN sa.studentId END) as absentStudents,
                ROUND(SUM(sa.status) / COUNT(*) * 100, 1) as attendanceRate
              FROM tblsubjectattendance sa
              WHERE sa.subjectTeacherId = ?
              GROUP BY DATE_FORMAT(sa.date, '%Y-%m')
              ORDER BY month DESC";

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$monthlyStats = $stmt->get_result();

// Get daily attendance rate for chart
$dailyStatsQuery = "SELECT 
                    sa.date,
                    COUNT(*) as totalAttendance,
                    SUM(sa.status) as presentCount,
                    ROUND(SUM(sa.status) / COUNT(*) * 100, 1) as attendanceRate
                  FROM tblsubjectattendance sa
                  WHERE sa.subjectTeacherId = ?
                  AND sa.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  GROUP BY sa.date
                  ORDER BY sa.date";

$stmt = $conn->prepare($dailyStatsQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$dailyStats = $stmt->get_result();

// Get student-wise attendance data
$studentStatsQuery = "SELECT 
                       s.Id,
                       s.firstName,
                       s.lastName,
                       s.admissionNumber,
                       c.className,
                       COUNT(sa.id) as totalClasses,
                       SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) as presentCount,
                       SUM(CASE WHEN sa.status = 0 THEN 1 ELSE 0 END) as absentCount,
                       ROUND(SUM(CASE WHEN sa.status = 1 THEN 1 ELSE 0 END) / COUNT(sa.id) * 100, 1) as attendanceRate
                    FROM tblstudents s
                    INNER JOIN tblclass c ON s.classId = c.Id
                    INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId
                    WHERE sa.subjectTeacherId = ?
                    GROUP BY s.Id
                    ORDER BY attendanceRate DESC";

$stmt = $conn->prepare($studentStatsQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$studentStats = $stmt->get_result();

// Format data for charts
$chartLabels = [];
$chartData = [];
$chartColors = [];

if ($dailyStats->num_rows > 0) {
    while ($row = $dailyStats->fetch_assoc()) {
        $chartLabels[] = date('M d', strtotime($row['date']));
        $chartData[] = $row['attendanceRate'];
        // Generate color based on attendance rate
        if ($row['attendanceRate'] >= 90) {
            $chartColors[] = 'rgba(40, 167, 69, 0.7)'; // Green
        } elseif ($row['attendanceRate'] >= 75) {
            $chartColors[] = 'rgba(23, 162, 184, 0.7)'; // Blue
        } elseif ($row['attendanceRate'] >= 50) {
            $chartColors[] = 'rgba(255, 193, 7, 0.7)'; // Yellow
        } else {
            $chartColors[] = 'rgba(220, 53, 69, 0.7)'; // Red
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Subject Teacher Attendance Management System">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Analytics Dashboard - <?php echo $teacherInfo['subjectName']; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        .chart-card {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        .chart-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e3e6f0;
            background-color: #f8f9fc;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .chart-container {
            padding: 1.25rem;
            height: 350px;
        }
        .stats-card {
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .student-table th, .student-table td {
            vertical-align: middle !important;
        }
        .attendance-rate {
            font-size: 0.85rem;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .excellent {
            background-color: #d4edda;
            color: #155724;
        }
        .good {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .moderate {
            background-color: #fff3cd;
            color: #856404;
        }
        .poor {
            background-color: #f8d7da;
            color: #721c24;
        }
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
                
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            Attendance Analytics
                            <small class="text-muted">
                                (<?php echo $teacherInfo['subjectName'] . ' - ' . $teacherInfo['subjectCode']; ?>)
                            </small>
                        </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Analytics Dashboard</li>
                        </ol>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row mb-3">
                        <!-- Overall Stats -->
                        <?php
                        // Calculate overall statistics
                        $totalAttendances = 0;
                        $totalPresent = 0;
                        $totalAbsent = 0;
                        $overallRate = 0;
                        
                        if ($monthlyStats->num_rows > 0) {
                            $monthlyStats->data_seek(0); // Reset pointer
                            while ($row = $monthlyStats->fetch_assoc()) {
                                $totalAttendances += $row['totalStudents'];
                                $totalPresent += $row['presentStudents'];
                                $totalAbsent += $row['absentStudents'];
                            }
                            $overallRate = $totalAttendances > 0 ? round(($totalPresent / $totalAttendances) * 100, 1) : 0;
                        }
                        ?>
                        <!-- Overall Attendance Rate Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card" style="border-left-color: #4e73df;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Overall Attendance Rate</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overallRate; ?>%</div>
                                            <div class="mt-2 mb-0 text-muted text-xs">
                                                <span>Average attendance</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percentage fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Present Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card" style="border-left-color: #1cc88a;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Present</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresent; ?></div>
                                            <div class="mt-2 mb-0 text-muted text-xs">
                                                <span>Student attendances</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Absent Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card" style="border-left-color: #e74a3b;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Absent</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAbsent; ?></div>
                                            <div class="mt-2 mb-0 text-muted text-xs">
                                                <span>Student absences</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Total Attendance Records Card -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card h-100 stats-card" style="border-left-color: #f6c23e;">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Records</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAttendances; ?></div>
                                            <div class="mt-2 mb-0 text-muted text-xs">
                                                <span>Attendance entries</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <!-- Daily Attendance Chart -->
                        <div class="col-lg-8">
                            <div class="chart-card mb-4">
                                <div class="chart-card-header">
                                    <h6 class="m-0 font-weight-bold text-primary">Daily Attendance Rate (Last 30 Days)</h6>
                                </div>
                                <div class="chart-container">
                                    <canvas id="dailyAttendanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly Stats -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Monthly Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Rate</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($monthlyStats->num_rows > 0) {
                                                    $monthlyStats->data_seek(0); // Reset pointer
                                                    while ($row = $monthlyStats->fetch_assoc()) {
                                                        $monthName = date('M Y', strtotime($row['month'] . '-01'));
                                                        $rateClass = '';
                                                        if ($row['attendanceRate'] >= 90) {
                                                            $rateClass = 'excellent';
                                                        } elseif ($row['attendanceRate'] >= 75) {
                                                            $rateClass = 'good';
                                                        } elseif ($row['attendanceRate'] >= 50) {
                                                            $rateClass = 'moderate';
                                                        } else {
                                                            $rateClass = 'poor';
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?php echo $monthName; ?></td>
                                                    <td><span class="attendance-rate <?php echo $rateClass; ?>"><?php echo $row['attendanceRate']; ?>%</span></td>
                                                    <td class="text-success"><?php echo $row['presentStudents']; ?></td>
                                                    <td class="text-danger"><?php echo $row['absentStudents']; ?></td>
                                                </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center">No monthly data available</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Student Performance -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Attendance Performance</h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" 
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Export
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="#" id="printReport"><i class="fas fa-print fa-sm fa-fw mr-2"></i>Print</a>
                                            <a class="dropdown-item" href="#" id="exportCsv"><i class="fas fa-file-csv fa-sm fa-fw mr-2"></i>Export CSV</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered student-table" id="studentPerformanceTable">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <th>Admission No</th>
                                                    <th>Total Classes</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>Attendance Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($studentStats->num_rows > 0) {
                                                    while ($row = $studentStats->fetch_assoc()) {
                                                        $rateClass = '';
                                                        if ($row['attendanceRate'] >= 90) {
                                                            $rateClass = 'excellent';
                                                        } elseif ($row['attendanceRate'] >= 75) {
                                                            $rateClass = 'good';
                                                        } elseif ($row['attendanceRate'] >= 50) {
                                                            $rateClass = 'moderate';
                                                        } else {
                                                            $rateClass = 'poor';
                                                        }
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['Id']; ?></td>
                                                    <td><?php echo str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']); ?></td>
                                                    <td><?php echo $row['className']; ?></td>
                                                    <td><?php echo $row['admissionNumber']; ?></td>
                                                    <td><?php echo $row['totalClasses']; ?></td>
                                                    <td class="text-success"><?php echo $row['presentCount']; ?></td>
                                                    <td class="text-danger"><?php echo $row['absentCount']; ?></td>
                                                    <td>
                                                        <span class="attendance-rate <?php echo $rateClass; ?>">
                                                            <?php echo $row['attendanceRate']; ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="8" class="text-center">No student performance data available</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <!-- Dashboard Charts -->
    <script>
    // Setup the daily attendance chart
    const dailyChartCtx = document.getElementById('dailyAttendanceChart').getContext('2d');
    const dailyAttendanceChart = new Chart(dailyChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Daily Attendance Rate (%)',
                data: <?php echo json_encode($chartData); ?>,
                backgroundColor: <?php echo json_encode($chartColors); ?>,
                borderColor: <?php echo json_encode($chartColors); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Attendance Rate (%)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Attendance: ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
    
    // Export functionality
    document.getElementById('printReport').addEventListener('click', function(e) {
        e.preventDefault();
        window.print();
    });
    
    document.getElementById('exportCsv').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get the table data
        const table = document.getElementById('studentPerformanceTable');
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Get the text content and remove any commas to avoid CSV issues
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/,/g, ';');
                // Add the data wrapped in quotes to handle special characters
                row.push('"' + data + '"');
            }
            
            csv.push(row.join(','));
        }
        
        // Download the CSV file
        const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'student_attendance_report.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    </script>
</body>
</html>
