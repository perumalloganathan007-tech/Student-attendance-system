<?php 
error_reporting(E_ALL); // Enable error reporting for debugging
include '../Includes/dbcon.php';

// Validate that user is a ClassTeacher
validate_session('ClassTeacher');

try {
    // Get the teacher's assigned class and section if not already set
    if (!isset($_SESSION['classId']) || !isset($_SESSION['classArmId'])) {
        $teacherQuery = "SELECT classId, classArmId FROM tblclassteacher WHERE Id = ?";
        $stmt = $conn->prepare($teacherQuery);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $_SESSION['userId']);
        if (!$stmt->execute()) {
            throw new Exception("Error getting teacher data: " . $stmt->error);
        }
        
        $teacherResult = $stmt->get_result();
        if ($teacherResult->num_rows == 0) {
            throw new Exception("No class assigned to this teacher.");
        }
        
        $teacherData = $teacherResult->fetch_assoc();
        $_SESSION['classId'] = $teacherData['classId'];
        $_SESSION['classArmId'] = $teacherData['classArmId'];
    }
} catch (Exception $e) {
    die("<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>");
}

// Initialize status message variable
$statusMsg = "";
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
  <title>Dashboard</title>
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
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
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
                    <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                        <?php
                        try {
                            $qry = "SELECT * FROM tblstudents WHERE classId = ? AND classArmId = ? ORDER BY firstName ASC";
                            $stmt = $conn->prepare($qry);
                            if (!$stmt) {
                                throw new Exception("Database error: " . $conn->error);
                            }
                            
                            $stmt->bind_param("ss", $_SESSION['classId'], $_SESSION['classArmId']);
                            if (!$stmt->execute()) {
                                throw new Exception("Error getting students: " . $stmt->error);
                            }
                            
                            $result = $stmt->get_result();		
                            if ($result->num_rows > 0) {
                                echo '<select required name="admissionNumber" class="form-control mb-3">';
                                echo '<option value="">--Select Student--</option>';
                                while ($rows = $result->fetch_assoc()) {
                                    echo '<option value="'.htmlspecialchars($rows['admissionNumber']).'">'
                                         .htmlspecialchars(str_replace('.', ' ', $rows['firstName'])).' '.htmlspecialchars(str_replace('.', ' ', $rows['lastName'])).'</option>';
                                }
                                echo '</select>';
                            } else {
                                echo '<div class="alert alert-info">No students found in this class.</div>';
                            }
                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
                        }
                        ?>  
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Type<span class="text-danger ml-2">*</span></label>
                          <select required name="type" onchange="typeDropDown(this.value)" class="form-control mb-3">
                          <option value="">--Select--</option>
                          <option value="1" >All</option>
                          <option value="2" >By Single Date</option>
                          <option value="3" >By Date Range</option>
                        </select>
                        </div>
                    </div>
                      <?php
                        echo"<div id='txtHint'></div>";
                      ?>
                    <!-- <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Student<span class="text-danger ml-2">*</span></label>
                        
                        </div>
                        <div class="col-xl-6">
                        <label class="form-control-label">Type<span class="text-danger ml-2">*</span></label>
                        
                        </div>
                    </div> -->
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                  </form>
                </div>
              </div>

              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Class Attendance</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Class Arm</th>
                        <th>Session</th>
                        <th>Term</th>
                        <th>Status</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                   
                    <tbody>

                  <?php

                    if(isset($_POST['view'])){
                        try {
                            if(empty($_POST['admissionNumber'])) {
                                throw new Exception("Please select a student.");
                            }
                            if(empty($_POST['type'])) {
                                throw new Exception("Please select attendance type.");
                            }

                            $admissionNumber = $_POST['admissionNumber'];
                            $type = $_POST['type'];
                            $stmt = null; // Initialize stmt

                            if($type == "1"){ //All Attendance
                                $query = "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken,
                                    tblclass.className, tblclassarms.classArmName, tblsessionterm.sessionName,
                                    tblsessionterm.termId, tblterm.termName, tblstudents.firstName,
                                    tblstudents.lastName, tblstudents.admissionNumber
                                    FROM tblattendance
                                    INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
                                    INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
                                    INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
                                    INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
                                    INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                                    WHERE tblattendance.admissionNo = ? 
                                    AND tblattendance.classId = ? 
                                    AND tblattendance.classArmId = ?
                                    ORDER BY tblattendance.dateTimeTaken DESC";

                                $stmt = $conn->prepare($query);
                                if (!$stmt) {
                                    throw new Exception("Database error (Type 1): " . $conn->error);
                                }
                                $stmt->bind_param("sss", $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
                            } else if($type == "2"){ //Single Date Attendance
                                if(empty($_POST['singleDate'])) {
                                    throw new Exception("Please select a single date.");
                                }
                                $singleDate =  $_POST['singleDate'];
                                $query = "SELECT tblattendance.Id,tblattendance.status,tblattendance.dateTimeTaken,tblclass.className,
                                    tblclassarms.classArmName,tblsessionterm.sessionName,tblsessionterm.termId,tblterm.termName,
                                    tblstudents.firstName,tblstudents.lastName,tblstudents.admissionNumber
                                    FROM tblattendance
                                    INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
                                    INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
                                    INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
                                    INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
                                    INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                                    WHERE tblattendance.dateTimeTaken = ? AND tblattendance.admissionNo = ? AND tblattendance.classId = ? AND tblattendance.classArmId = ?
                                    ORDER BY tblattendance.dateTimeTaken DESC";
                                $stmt = $conn->prepare($query);
                                if (!$stmt) {
                                    throw new Exception("Database error (Type 2): " . $conn->error);
                                }
                                $stmt->bind_param("ssss", $singleDate, $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
                            } else if($type == "3"){ //Date Range Attendance
                                if(empty($_POST['fromDate']) || empty($_POST['toDate'])) {
                                    throw new Exception("Please select both from and to dates.");
                                }
                                $fromDate =  $_POST['fromDate'];
                                $toDate =  $_POST['toDate'];
                                $query = "SELECT tblattendance.Id,tblattendance.status,tblattendance.dateTimeTaken,tblclass.className,
                                    tblclassarms.classArmName,tblsessionterm.sessionName,tblsessionterm.termId,tblterm.termName,
                                    tblstudents.firstName,tblstudents.lastName,tblstudents.admissionNumber
                                    FROM tblattendance
                                    INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
                                    INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
                                    INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
                                    INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
                                    INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                                    WHERE tblattendance.dateTimeTaken BETWEEN ? AND ? AND tblattendance.admissionNo = ? AND tblattendance.classId = ? AND tblattendance.classArmId = ?
                                    ORDER BY tblattendance.dateTimeTaken DESC";
                                $stmt = $conn->prepare($query);
                                if (!$stmt) {
                                    throw new Exception("Database error (Type 3): " . $conn->error);
                                }
                                $stmt->bind_param("sssss", $fromDate, $toDate, $admissionNumber, $_SESSION['classId'], $_SESSION['classArmId']);
                            } else {
                                throw new Exception("Invalid attendance type selected.");
                            }

                            if ($stmt && !$stmt->execute()) { // Check if $stmt is not null before executing
                                throw new Exception("Error fetching attendance: " . $stmt->error);
                            }

                            if ($stmt) { // Check if $stmt is not null before getting result
                                $rs = $stmt->get_result();
                                $num = $rs->num_rows;
                                $sn = 0;
                                
                                if($num > 0) { 
                                    while ($rows = $rs->fetch_assoc()) {
                                        $status = $rows['status'] == '1' ? "<span class='badge badge-success'>Present</span>" : "<span class='badge badge-danger'>Absent</span>";
                                        $sn++;
                                        echo "<tr>
                                                <td>".htmlspecialchars($sn)."</td>
                                                <td>".htmlspecialchars($rows['firstName'])."</td>
                                                <td>".htmlspecialchars(str_replace('.', ' ', $rows['lastName']))."</td>
                                                <td>".htmlspecialchars($rows['admissionNumber'])."</td>
                                                <td>".htmlspecialchars($rows['className'])."</td>
                                                <td>".htmlspecialchars($rows['classArmName'])."</td>
                                                <td>".htmlspecialchars($rows['sessionName'])."</td>
                                                <td>".htmlspecialchars($rows['termName'])."</td>
                                                <td>".$status."</td>
                                                <td>".htmlspecialchars(date('F j, Y', strtotime($rows['dateTimeTaken'])))."</td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='11'><div class='alert alert-info' role='alert'>No Record Found!</div></td></tr>";
                                }
                                $stmt->close(); // Close the prepared statement
                            } else if ($type >= 1 && $type <=3) { // Only show this if a valid type was selected but $stmt remained null (e.g. prepare failed)
                                 echo "<tr><td colspan='11'><div class='alert alert-danger' role='alert'>Could not prepare the database query.</div></td></tr>";
                            }

                        } catch (Exception $e) {
                            echo "<tr><td colspan='11'><div class='alert alert-danger' role='alert'>" . htmlspecialchars($e->getMessage()) . "</div></td></tr>";
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
          <!--Row-->

          <!-- Documentation Link -->
          <!-- <div class="row">
            <div class="col-lg-12 text-center">
              <p>For more documentations you can visit<a href="https://getbootstrap.com/docs/4.3/components/forms/"
                  target="_blank">
                  bootstrap forms documentations.</a> and <a
                  href="https://getbootstrap.com/docs/4.3/components/input-group/" target="_blank">bootstrap input
                  groups documentations</a></p>
            </div>
          </div> -->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
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
   <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>