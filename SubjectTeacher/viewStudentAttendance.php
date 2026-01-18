<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

//------------------------SAVE--------------------------------------------------

if(isset($_POST['view'])){
    $admissionNumber =  $_POST['admissionNumber'];
    $type =  $_POST['type'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Dashboard - View Student Attendance</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">

    <script>
    function typeDropDown(str) {
    if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
        return;
    } else { 
        if (window.XMLHttpRequest) {
            // code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        } else {
            // code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("txtHint").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET","ajaxCallTypes.php?tid="+str,true);
        xmlhttp.send();
    }
}
</script>
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
                        <h1 class="h3 mb-0 text-gray-800">View Student Attendance</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Student Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Student Attendance</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                                                <?php
                                                $qry = "SELECT DISTINCT s.admissionNumber, s.firstName, s.lastName 
                                                        FROM tblstudents s 
                                                        INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId 
                                                        WHERE sts.subjectTeacherId = ?
                                                        ORDER BY s.firstName ASC";
                                                $stmt = $conn->prepare($qry);
                                                $stmt->bind_param("s", $_SESSION['userId']);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $num = $result->num_rows;

                                                if ($num > 0) {
                                                    echo '<select required name="admissionNumber" class="form-control mb-3">';
                                                    echo '<option value="">--Select Student--</option>';
                                                    while ($rows = $result->fetch_assoc()) {
                                                        echo '<option value="'.$rows['admissionNumber'].'">'.str_replace('.', ' ', $rows['firstName']).' '.str_replace('.', ' ', $rows['lastName']).' ('.$rows['admissionNumber'].')</option>';
                                                    }
                                                    echo '</select>';
                                                }
                                                ?>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Type<span class="text-danger ml-2">*</span></label>
                                                <select required name="type" onchange="typeDropDown(this.value)" class="form-control mb-3">
                                                    <option value="">--Select--</option>
                                                    <option value="1">All</option>
                                                    <option value="2">By Single Date</option>
                                                    <option value="3">By Date Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <?php
                                        echo "<div id='txtHint'></div>";
                                        ?>
                                        <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Attendance Records -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
                                        </div>
                                        <div class="table-responsive p-3">
                                            <table class="table align-items-center table-flush table-hover">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>First Name</th>
                                                        <th>Last Name</th>
                                                        <th>Admission No</th>
                                                        <th>Class</th>
                                                        <th>Session</th>
                                                        <th>Term</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if(isset($_POST['view'])){
                                                        $admissionNumber = $_POST['admissionNumber'];
                                                        $type = $_POST['type'];

                                                        if($type == "1"){ //All Attendance
                                                            $query = "SELECT s.firstName, s.lastName, s.admissionNumber,
                                                                     c.className, st.sessionName, t.termName, sa.status, sa.date
                                                                     FROM tblstudents s
                                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                                     INNER JOIN tblclass c ON s.classId = c.Id
                                                                     INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId AND sa.subjectTeacherId = sts.subjectTeacherId
                                                                     INNER JOIN tblsessionterm st ON sa.sessionTermId = st.Id
                                                                     INNER JOIN tblterm t ON st.termId = t.Id
                                                                     WHERE s.admissionNumber = ? AND sts.subjectTeacherId = ?
                                                                     ORDER BY sa.date DESC";
                                                            $stmt = $conn->prepare($query);
                                                            $stmt->bind_param("ss", $admissionNumber, $_SESSION['userId']);
                                                        }
                                                        else if($type == "2"){ //Single Date Attendance
                                                            $singleDate = $_POST['singleDate'];
                                                            $query = "SELECT s.firstName, s.lastName, s.admissionNumber,
                                                                     c.className, st.sessionName, t.termName, sa.status, sa.date
                                                                     FROM tblstudents s
                                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                                     INNER JOIN tblclass c ON s.classId = c.Id
                                                                     INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId AND sa.subjectTeacherId = sts.subjectTeacherId
                                                                     INNER JOIN tblsessionterm st ON sa.sessionTermId = st.Id
                                                                     INNER JOIN tblterm t ON st.termId = t.Id
                                                                     WHERE s.admissionNumber = ? AND sts.subjectTeacherId = ? 
                                                                     AND sa.date = ?
                                                                     ORDER BY sa.date DESC";
                                                            $stmt = $conn->prepare($query);
                                                            $stmt->bind_param("sss", $admissionNumber, $_SESSION['userId'], $singleDate);
                                                        }
                                                        else if($type == "3"){ //Date Range Attendance
                                                            $fromDate = $_POST['fromDate'];
                                                            $toDate = $_POST['toDate'];
                                                            $query = "SELECT s.firstName, s.lastName, s.admissionNumber,
                                                                     c.className, st.sessionName, t.termName, sa.status, sa.date
                                                                     FROM tblstudents s
                                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                                     INNER JOIN tblclass c ON s.classId = c.Id
                                                                     INNER JOIN tblsubjectattendance sa ON s.Id = sa.studentId AND sa.subjectTeacherId = sts.subjectTeacherId
                                                                     INNER JOIN tblsessionterm st ON sa.sessionTermId = st.Id
                                                                     INNER JOIN tblterm t ON st.termId = t.Id
                                                                     WHERE s.admissionNumber = ? AND sts.subjectTeacherId = ? 
                                                                     AND (sa.date BETWEEN ? AND ?)
                                                                     ORDER BY sa.date DESC";
                                                            $stmt = $conn->prepare($query);
                                                            $stmt->bind_param("ssss", $admissionNumber, $_SESSION['userId'], $fromDate, $toDate);
                                                        }

                                                        // Execute the query and fetch results
                                                        if($stmt->execute()) {
                                                            $result = $stmt->get_result();
                                                            if($result->num_rows > 0) {
                                                                $cnt = 1;
                                                                while ($row = $result->fetch_assoc()) {
                                                                    echo '
                                                                    <tr>
                                                                        <td>' . $cnt . '</td>
                                                                        <td>' . $row['firstName'] . '</td>
                                                                        <td>' . $row['lastName'] . '</td>
                                                                        <td>' . $row['admissionNumber'] . '</td>
                                                                        <td>' . $row['className'] . '</td>
                                                                        <td>' . $row['sessionName'] . '</td>
                                                                        <td>' . $row['termName'] . '</td>
                                                                        <td>' . ($row['status'] == '1' ? '<span class="badge badge-success">Present</span>' : '<span class="badge badge-danger">Absent</span>') . '</td>
                                                                        <td>' . date('Y-m-d', strtotime($row['date'])) . '</td>
                                                                    </tr>';
                                                                    $cnt++;
                                                                }
                                                            } else {
                                                                echo '<tr><td colspan="8" class="text-center">No attendance records found</td></tr>';
                                                            }
                                                        } else {
                                                            echo '<tr><td colspan="8" class="text-danger text-center">Error executing query: ' . $stmt->error . '</td></tr>';
                                                        }
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
</body>
</html>
