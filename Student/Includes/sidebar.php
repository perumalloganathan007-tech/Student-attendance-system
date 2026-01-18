<?php
include '../Includes/dbcon.php';
?>

<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <img src="../img/logo/attnlg.jpg" style="width: 40px; height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">Student Panel</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">
        Features
    </div>
    <li class="nav-item">        <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['viewAttendance.php', 'viewAttendanceReport.php', 'yearAttendanceReport.php']) ? '' : 'collapsed'; ?>" 
           href="#" data-toggle="collapse" data-target="#collapseAttendance"
           aria-expanded="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['viewAttendance.php', 'viewAttendanceReport.php', 'yearAttendanceReport.php']) ? 'true' : 'false'; ?>" 
           aria-controls="collapseAttendance">
            <i class="fas fa-fw fa-calendar-alt"></i>
            <span>Attendance</span>
        </a>        <div id="collapseAttendance" class="collapse <?php echo in_array(basename($_SERVER['PHP_SELF']), ['viewAttendance.php', 'viewAttendanceReport.php', 'yearAttendanceReport.php']) ? 'show' : ''; ?>" 
             aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">View Attendance</h6>                <a class="collapse-item <?php echo basename($_SERVER['PHP_SELF']) == 'viewAttendance.php' ? 'active' : ''; ?>" 
                   href="viewAttendance.php">Today's Attendance</a>
                <a class="collapse-item <?php echo basename($_SERVER['PHP_SELF']) == 'viewAttendanceReport.php' ? 'active' : ''; ?>" 
                   href="viewAttendanceReport.php">Attendance Reports</a>
                <a class="collapse-item <?php echo basename($_SERVER['PHP_SELF']) == 'yearAttendanceReport.php' ? 'active' : ''; ?>" 
                   href="yearAttendanceReport.php">Yearly Report</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="profile.php">
            <i class="fas fa-fw fa-user"></i>
            <span>My Profile</span>
        </a>
    </li>

    <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'downloadRecord.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="downloadRecord.php">
            <i class="fas fa-fw fa-download"></i>
            <span>Download Report</span>
        </a>
    </li>

    <hr class="sidebar-divider">
</ul>
<!-- Sidebar -->
