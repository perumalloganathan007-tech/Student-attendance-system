<?php 
error_reporting(0);
include '../Includes/dbcon.php';

// Validate that user is a ClassTeacher
validate_session('ClassTeacher');

// Get class teacher info
$query = "SELECT tblclass.className,tblclassarms.classArmName 
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
    Where tblclassteacher.Id = '$_SESSION[userId]'";
$rs = $conn->query($query);
$rrw = $rs->fetch_assoc();

//session and Term
$querey=mysqli_query($conn,"select * from tblsessionterm where isActive ='1'");
$rwws=mysqli_fetch_array($querey);
$sessionTermId = $rwws['Id'];

// Validate and get selected date
$dateTaken = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
if (strtotime($dateTaken) > strtotime(date("Y-m-d"))) {
    $statusMsg = "<div class='alert alert-danger'>Cannot take or edit attendance for future dates!</div>";
    $dateTaken = date("Y-m-d");
}

// Handle form submission
if(isset($_POST['save'])){
    $admissionNo = $_POST['admissionNo'];
    $check = isset($_POST['check']) ? $_POST['check'] : array();
    $selectedDate = $_POST['selected_date'];
    
    if (strtotime($selectedDate) > strtotime(date("Y-m-d"))) {
        $statusMsg = "<div class='alert alert-danger'>Cannot take or edit attendance for future dates!</div>";
    } else {
        // First set all students to absent (status = 0) for the selected date
        $resetQuery = mysqli_query($conn, "UPDATE tblattendance SET status='0' 
            WHERE classId = '$_SESSION[classId]' 
            AND classArmId = '$_SESSION[classArmId]' 
            AND dateTimeTaken='$selectedDate'");

        // Then set the checked students to present (status = 1)
        foreach($check as $studentId) {
            $updateQuery = mysqli_query($conn, "UPDATE tblattendance SET status='1' 
                WHERE admissionNo = '$studentId' 
                AND dateTimeTaken='$selectedDate'");
        }
        
        if ($selectedDate == date('Y-m-d')) {
            $statusMsg = "<div class='alert alert-success'>Attendance has been taken successfully for today!</div>";
        } else {
            $statusMsg = "<div class='alert alert-success'>Attendance has been updated successfully for " . date('F d, Y', strtotime($selectedDate)) . "!</div>";
        }
        
        // Redirect to refresh the page and avoid form resubmission
        header("Location: takeAttendance.php?date=" . $selectedDate);
        exit();
    }
}

// Check if we need to create new attendance records for today
$qurty = mysqli_query($conn, "SELECT * FROM tblattendance 
    WHERE classId = '$_SESSION[classId]' 
    AND classArmId = '$_SESSION[classArmId]' 
    AND dateTimeTaken='$dateTaken'");
$count = mysqli_num_rows($qurty);

if($count == 0 && $dateTaken == date("Y-m-d")){ 
    // Create new attendance records for all students
    $qus = mysqli_query($conn, "SELECT * FROM tblstudents 
        WHERE classId = '$_SESSION[classId]' 
        AND classArmId = '$_SESSION[classArmId]'");
        
    while ($ros = $qus->fetch_assoc()) {
        mysqli_query($conn, "INSERT INTO tblattendance(
            admissionNo,classId,classArmId,sessionTermId,status,dateTimeTaken
        ) VALUES (
            '$ros[admissionNumber]','$_SESSION[classId]','$_SESSION[classArmId]',
            '$sessionTermId','0','$dateTaken'
        )");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Take/Edit Attendance</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include "Includes/sidebar.php";?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include "Includes/topbar.php";?>
                
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Take/Edit Attendance</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active">Class Attendance</li>
                        </ol>
                    </div>

                    <?php if(isset($statusMsg)) echo $statusMsg; ?>

                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Previous Attendance Records</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTableHover" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Total Present</th>
                                                    <th>Total Absent</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $attendanceQuery = mysqli_query($conn, "SELECT dateTimeTaken, 
                                                COUNT(CASE WHEN status = 1 THEN 1 END) as present_count,
                                                COUNT(CASE WHEN status = 0 THEN 1 END) as absent_count
                                                FROM tblattendance 
                                                WHERE classId = '$_SESSION[classId]' 
                                                AND classArmId = '$_SESSION[classArmId]'
                                                GROUP BY dateTimeTaken 
                                                ORDER BY dateTimeTaken DESC");
                                            
                                            while($row = mysqli_fetch_array($attendanceQuery)) {
                                                $date = date('Y-m-d', strtotime($row['dateTimeTaken']));
                                                echo "<tr>
                                                    <td>".date('F d, Y', strtotime($row['dateTimeTaken']))."</td>
                                                    <td>".$row['present_count']."</td>
                                                    <td>".$row['absent_count']."</td>
                                                    <td>
                                                        <a href='?date=".$date."' class='btn btn-primary btn-sm'>
                                                            <i class='fas fa-edit'></i> Edit
                                                        </a>
                                                    </td>
                                                </tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="get" class="form-inline">
                                        <div class="form-group mb-2">
                                            <label for="attendanceDate" class="mr-2">Select Date:</label>
                                            <input type="date" class="form-control" id="attendanceDate" name="date" 
                                                   value="<?php echo $dateTaken; ?>" 
                                                   max="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary mb-2 ml-2">Load Attendance</button>
                                        <?php if ($dateTaken != date('Y-m-d')) : ?>
                                            <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-secondary mb-2 ml-2">
                                                Back to Today
                                            </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <form method="post">
                                <div class="card mb-4">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            All Students in <?php echo $rrw['className'].' - '.$rrw['classArmName']; ?> Class
                                            (<?php echo date('F d, Y', strtotime($dateTaken)); ?>)
                                        </h6>
                                        <h6 class="m-0 font-weight-bold text-danger">
                                            Note: <i>Click on the checkboxes besides each student to mark them as present</i>
                                        </h6>
                                    </div>
                                    <div class="table-responsive p-3">
                                        <table class="table align-items-center table-flush table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>First Name</th>
                                                    <th>Last Name</th>
                                                    <th>Other Name</th>
                                                    <th>Admission No</th>
                                                    <th>Class</th>
                                                    <th>Class Arm</th>
                                                    <th>Check</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $query = "SELECT tblstudents.Id,tblstudents.admissionNumber,tblclass.className,
                                                tblclass.Id As classId,tblclassarms.classArmName,tblclassarms.Id AS classArmId,
                                                tblstudents.firstName,tblstudents.lastName,tblstudents.otherName,
                                                tblstudents.admissionNumber,tblstudents.dateCreated
                                                FROM tblstudents
                                                INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                                                INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                                                WHERE tblstudents.classId = '$_SESSION[classId]' 
                                                AND tblstudents.classArmId = '$_SESSION[classArmId]'";
                                            $rs = $conn->query($query);
                                            $num = $rs->num_rows;
                                            $sn = 0;
                                            
                                            if($num > 0) { 
                                                while ($rows = $rs->fetch_assoc()) {
                                                    $sn = $sn + 1;
                                                    
                                                    // Get attendance status for this student
                                                    $attendanceQuery = mysqli_query($conn, "SELECT status FROM tblattendance 
                                                        WHERE admissionNo = '".$rows['admissionNumber']."' 
                                                        AND dateTimeTaken = '$dateTaken'");
                                                    $attRow = mysqli_fetch_assoc($attendanceQuery);
                                                    $isChecked = ($attRow && $attRow['status'] == 1) ? 'checked' : '';
                                                    
                                                    echo "<tr>
                                                        <td>".$sn."</td>
                                                        <td>".$rows['firstName']."</td>
                                                        <td>".$rows['lastName']."</td>
                                                        <td>".$rows['otherName']."</td>
                                                        <td>".$rows['admissionNumber']."</td>
                                                        <td>".$rows['className']."</td>
                                                        <td>".$rows['classArmName']."</td>
                                                        <td>
                                                            <input name='check[]' type='checkbox' value='".$rows['admissionNumber']."' 
                                                                   class='form-control' ".$isChecked.">
                                                        </td>
                                                    </tr>";
                                                    echo "<input name='admissionNo[]' value='".$rows['admissionNumber']."' 
                                                           type='hidden' class='form-control'>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center'>No Record Found!</td></tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                        <br>
                                        <input type="hidden" name="selected_date" value="<?php echo $dateTaken; ?>">
                                        <button type="submit" name="save" class="btn btn-primary">
                                            <?php echo ($dateTaken == date('Y-m-d')) ? 'Take Attendance' : 'Update Attendance'; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "Includes/footer.php";?>
        </div>
    </div>

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
            $('#dataTableHover').DataTable();
        });
    </script>
</body>
</html>
