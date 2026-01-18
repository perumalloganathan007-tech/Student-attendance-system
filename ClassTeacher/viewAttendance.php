<?php 
error_reporting(E_ALL); // Enable error reporting for debugging
include '../Includes/dbcon.php';

// Validate that user is a ClassTeacher
validate_session('ClassTeacher');

try {
    // Get the teacher's assigned class and section
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
    
} catch (Exception $e) {
    die("<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>");
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
  <title>Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
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
            <h1 class="h3 mb-0 text-gray-800">View Class Attendance</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Class Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Today's Attendance Report -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Today's Attendance Report</h6>
                </div>
                <div class="table-responsive p-3">
                  <?php
                    try {
                        $today = date("Y-m-d");
                        $todayQuery = "SELECT s.firstName, s.lastName, s.admissionNumber,
                                    c.className, ca.classArmName,
                                    CASE 
                                        WHEN a.status = '1' THEN 'Present'
                                        WHEN a.status = '0' THEN 'Absent'
                                        ELSE 'Not Taken'
                                    END as status
                                    FROM tblstudents s
                                    INNER JOIN tblclass c ON c.Id = s.classId
                                    INNER JOIN tblclassarms ca ON ca.Id = s.classArmId
                                    LEFT JOIN tblattendance a ON s.admissionNumber = a.admissionNo 
                                        AND a.dateTimeTaken = ?
                                        AND a.classId = s.classId 
                                        AND a.classArmId = s.classArmId
                                    WHERE s.classId = ? 
                                    AND s.classArmId = ?
                                    ORDER BY s.firstName ASC";
                                 
                    $stmt = $conn->prepare($todayQuery);
                    if (!$stmt) {
                        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($conn->error) . "</div>";
                        return;
                    }
                    
                    $stmt->bind_param("sss", $today, $_SESSION['classId'], $_SESSION['classArmId']);
                    if (!$stmt->execute()) {
                        echo "<div class='alert alert-danger'>Error getting attendance: " . htmlspecialchars($stmt->error) . "</div>";
                        return;
                    }
                    
                    $todayResult = $stmt->get_result();
                    if($todayResult->num_rows > 0) {
                        echo '<table class="table align-items-center table-flush table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Admission No</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>';
                        $sn = 0;
                        while($row = $todayResult->fetch_assoc()) {
                            $sn++;
                            $statusClass = $row['status'] == 'Present' ? 'badge-success' : 
                                           ($row['status'] == 'Absent' ? 'badge-danger' : 'badge-warning');
                            echo "<tr>
                                    <td>".$sn."</td>
                                    <td>".$row['firstName']."</td>
                                    <td>".$row['lastName']."</td>
                                    <td>".$row['admissionNumber']."</td>
                                    <td><span class='badge ".$statusClass."'>".$row['status']."</span></td>
                                  </tr>";
                        }
                        echo '</tbody></table>';
                    } else {
                        echo "<div class='alert alert-info'>No attendance records for today yet.</div>";
                    }
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                  ?>
                </div>
              </div>

              <!-- Previous Attendance Records -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Previous Attendance Records</h6>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                        <div class="col-xl-6">
                        <label class="form-control-label">Select Date<span class="text-danger ml-2">*</span></label>
                            <input type="date" class="form-control" name="dateTaken" id="exampleInputFirstName" required>
                        </div>
                    </div>
                    <button type="submit" name="view" class="btn btn-primary">View Attendance</button>
                  </form>
                </div>
              </div>

              <!-- Selected Date Attendance -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
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

                      $dateTaken =  $_POST['dateTaken'];
                      $classId = $_SESSION['classId'];
                      $classArmId = $_SESSION['classArmId'];

                      $query = "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, 
                                tblclass.className, tblclassarms.classArmName, 
                                tblsessionterm.sessionName, tblsessionterm.termId, tblterm.termName,
                                tblstudents.firstName, tblstudents.lastName, tblstudents.admissionNumber
                                FROM tblattendance
                                INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
                                INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
                                INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
                                INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
                                INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
                                WHERE tblattendance.dateTimeTaken = ? 
                                  AND tblattendance.classId = ? 
                                  AND tblattendance.classArmId = ?";
                      
                      $stmt = $conn->prepare($query);
                      if (!$stmt) {
                          echo "<div class='alert alert-danger' role='alert'>Database prepare error: " . htmlspecialchars($conn->error) . "</div>";
                      } else {
                          $stmt->bind_param("sss", $dateTaken, $classId, $classArmId);
                          if (!$stmt->execute()) {
                              echo "<div class='alert alert-danger' role='alert'>Error executing query: " . htmlspecialchars($stmt->error) . "</div>";
                          } else {
                              $rs = $stmt->get_result();
                              $num = $rs->num_rows;
                              $sn=0;
                              $status="";
                              if($num > 0)
                              { 
                                while ($rows = $rs->fetch_assoc())
                                  {
                                      if($rows['status'] == '1'){
                                          $status = "<span class='badge badge-success'>Present</span>";
                                      } else {
                                          $status = "<span class='badge badge-danger'>Absent</span>";
                                      }
                                     $sn = $sn + 1;
                                    echo"
                                      <tr>
                                        <td>".htmlspecialchars($sn)."</td>
                                        <td>".htmlspecialchars($rows['firstName'])."</td>
                                        <td>".htmlspecialchars($rows['lastName'])."</td>
                                        <td>".htmlspecialchars($rows['admissionNumber'])."</td>
                                        <td>".htmlspecialchars($rows['className'])."</td>
                                        <td>".htmlspecialchars($rows['classArmName'])."</td>
                                        <td>".htmlspecialchars($rows['sessionName'])."</td>
                                        <td>".htmlspecialchars($rows['termName'])."</td>
                                        <td>".$status."</td>
                                        <td>".htmlspecialchars(date('F j, Y', strtotime($rows['dateTimeTaken'])))."</td>
                                      </tr>";
                                  }
                              }
                              else
                              {
                                   echo   
                                   "<div class='alert alert-danger' role='alert'>
                                    No Record Found for the selected date!
                                    </div>";
                              }
                              $stmt->close();
                          }
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