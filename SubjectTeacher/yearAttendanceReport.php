<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
require_once('Includes/session_utils.php');

// Get active session and term information
$session_result = getActiveSessionTerm($conn);
$current_session = $session_result['sessionName'];
$current_term = $session_result['termName'];

// Initialize year selection
$selected_year = isset($_POST['year']) ? $_POST['year'] : date('Y');

// Get years from attendance records
$query = "SELECT DISTINCT YEAR(date) as year 
          FROM tblsubjectattendance 
          WHERE subjectTeacherId = '".$_SESSION['userId']."'";
$years_result = mysqli_query($conn, $query);

// Create an array to store available years
$available_years = array();
while ($row = mysqli_fetch_array($years_result)) {
    $available_years[] = $row['year'];
}

// Add last 5 years if they're not in the results
$current_year = date('Y');
for ($i = 0; $i <= 5; $i++) {
    $year = $current_year - $i;
    if (!in_array($year, $available_years)) {
        $available_years[] = $year;
    }
}

// Sort years in descending order
rsort($available_years);

// Export functionality
if(isset($_POST['export'])) {
    $year = $_POST['year'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="yearly_attendance_'.$year.'_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, array('Month', 'Total Classes', 'Present', 'Absent', 'Attendance Rate (%)'));
    
    $months = array(1=>'January', 'February', 'March', 'April', 'May', 'June', 
                   'July', 'August', 'September', 'October', 'November', 'December');
    
    foreach($months as $month_num => $month_name) {
        $query = "SELECT 
            COUNT(DISTINCT date) as total_classes,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent_count
        FROM tblsubjectattendance 
        WHERE YEAR(date) = '$year' 
        AND MONTH(date) = '$month_num'
        AND subjectTeacherId = '".$_SESSION['userId']."'";

        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
        
        $total_classes = $row['total_classes'] ?: 0;
        $present_count = $row['present_count'] ?: 0;
        $absent_count = $row['absent_count'] ?: 0;
        $attendance_rate = $total_classes > 0 ? round(($present_count / ($present_count + $absent_count)) * 100, 2) : 0;
        
        fputcsv($output, array($month_name, $total_classes, $present_count, $absent_count, $attendance_rate));
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Yearly Attendance Report</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php";?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php";?>
                <!-- TopBar -->

                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Yearly Attendance Report</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Yearly Report</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Year Selection</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Year<span class="text-danger ml-2">*</span></label>
                                                <div class="input-group">
                                                    <select required name="year" class="form-control" onchange="this.form.submit()">
                                                        <?php 
                                                        foreach ($available_years as $year) {
                                                            $selected = ($year == $selected_year) ? 'selected' : '';
                                                            echo "<option value='".$year."' ".$selected.">".$year."</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button type="submit" name="view" class="btn btn-primary">
                                                            <i class="fas fa-search"></i> View
                                                        </button>
                                                        <button type="submit" name="export" class="btn btn-success">
                                                            <i class="fas fa-file-export"></i> Export CSV
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="export_type" value="csv">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <?php if(isset($_POST['year']) || isset($_POST['view'])) { ?>
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Yearly Attendance Summary for <?php echo $selected_year; ?></h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table align-items-center table-flush" id="dataTableHover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Total Classes</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>Attendance Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $months = array(1=>'January', 'February', 'March', 'April', 'May', 'June', 
                                                              'July', 'August', 'September', 'October', 'November', 'December');
                                                
                                                $total_yearly_classes = 0;
                                                $total_yearly_present = 0;
                                                $total_yearly_absent = 0;

                                                foreach($months as $month_num => $month_name) {
                                                    $query = "SELECT 
                                                        COUNT(DISTINCT date) as total_classes,
                                                        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_count,
                                                        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent_count
                                                    FROM tblsubjectattendance 
                                                    WHERE YEAR(date) = '$selected_year' 
                                                    AND MONTH(date) = '$month_num'
                                                    AND subjectTeacherId = '".$_SESSION['userId']."'";
                                                    
                                                    $result = mysqli_query($conn, $query);
                                                    $row = mysqli_fetch_assoc($result);
                                                    
                                                    $total_classes = $row['total_classes'] ?: 0;
                                                    $present_count = $row['present_count'] ?: 0;
                                                    $absent_count = $row['absent_count'] ?: 0;
                                                    
                                                    $attendance_rate = $total_classes > 0 ? round(($present_count / ($present_count + $absent_count)) * 100, 2) : 0;

                                                    // Add to yearly totals
                                                    $total_yearly_classes += $total_classes;
                                                    $total_yearly_present += $present_count;
                                                    $total_yearly_absent += $absent_count;
                                                    ?>
                                                    <tr>
                                                        <td><?php echo $month_name; ?></td>
                                                        <td><?php echo $total_classes; ?></td>
                                                        <td><?php echo $present_count; ?></td>
                                                        <td><?php echo $absent_count; ?></td>
                                                        <td><?php echo $attendance_rate; ?>%</td>
                                                    </tr>
                                                <?php } 
                                                
                                                // Calculate yearly attendance rate
                                                $yearly_attendance_rate = $total_yearly_classes > 0 ? 
                                                    round(($total_yearly_present / ($total_yearly_present + $total_yearly_absent)) * 100, 2) : 0;
                                                ?>
                                                <tr class="table-info font-weight-bold">
                                                    <td>Yearly Total</td>
                                                    <td><?php echo $total_yearly_classes; ?></td>
                                                    <td><?php echo $total_yearly_present; ?></td>
                                                    <td><?php echo $total_yearly_absent; ?></td>
                                                    <td><?php echo $yearly_attendance_rate; ?>%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
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
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function () {
            $('#dataTableHover').DataTable({
                "order": [[0, "asc"]]
            });
        });
    </script>
</body>
</html>
