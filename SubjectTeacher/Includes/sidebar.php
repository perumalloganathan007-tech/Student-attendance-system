<?php
// Include the session utilities file
require_once('../SubjectTeacher/Includes/session_utils.php');

// Get active session and term information
$session_result = getActiveSessionTerm($conn);
?>

<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center bg-gradient-primary justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <img src="img/logo/attnlg.jpg">
        </div>
        <div class="sidebar-brand-text mx-3">SAS</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item active">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Current Session: <?php echo $session_result['sessionName'] . ' - ' . $session_result['termName']; ?>
    </div>
    
    <!-- Attendance Section -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAttendance"
            aria-expanded="true" aria-controls="collapseAttendance">
            <i class="fa fa-calendar-alt"></i>
            <span>Attendance Management</span>
        </a>
        <div id="collapseAttendance" class="collapse" aria-labelledby="headingAttendance"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Daily Operations</h6>
                <a class="collapse-item" href="takeAttendance.php">Take Attendance</a>
                <a class="collapse-item" href="viewTodayAttendance.php">Today's Attendance</a>
                
                <h6 class="collapse-header mt-3">Reports & Analysis</h6>
                <a class="collapse-item" href="viewAttendance.php">Attendance Records</a>
                <a class="collapse-item" href="dateRangeReport.php">Date Range Report</a>                <a class="collapse-item" href="monthlyReport.php">Monthly Report</a>
                <a class="collapse-item" href="yearAttendanceReport.php">Yearly Report</a>
                <a class="collapse-item" href="lowAttendance.php">Low Attendance List</a>
                <a class="collapse-item" href="attendanceAnalytics.php">Analytics Dashboard</a>
            </div>
        </div>
    </li>

    <!-- Students Section -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStudents"
            aria-expanded="true" aria-controls="collapseStudents">
            <i class="fas fa-user-graduate"></i>
            <span>Students</span>
        </a>
        <div id="collapseStudents" class="collapse" aria-labelledby="headingStudents"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Student Management</h6>
                <a class="collapse-item" href="viewStudents.php">View All Students</a>
                <a class="collapse-item" href="studentPerformance.php">Performance Analysis</a>
                <a class="collapse-item" href="viewStudentAttendance.php">Individual Reports</a>
            </div>
        </div>
    </li>

    <!-- Export Section -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseExports"
            aria-expanded="true" aria-controls="collapseExports">
            <i class="fas fa-file-export"></i>
            <span>Export Data</span>
        </a>
        <div id="collapseExports" class="collapse" aria-labelledby="headingExports"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Export Options</h6>
                <a class="collapse-item" href="exportStudentList.php">Student List</a>
                <a class="collapse-item" href="exportAttendance.php">Attendance Records</a>
                <a class="collapse-item" href="exportPerformance.php">Performance Reports</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Settings
    </div>
    <li class="nav-item">
        <a class="nav-link" href="changePassword.php">
            <i class="fas fa-key"></i>
            <span>Change Password</span>
        </a>
    </li>
</ul>
