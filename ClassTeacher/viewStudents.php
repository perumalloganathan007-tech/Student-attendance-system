<?php 
error_reporting(0);
include '../Includes/dbcon.php';

// Validate that user is a ClassTeacher
validate_session('ClassTeacher');

try {
    $query = "SELECT tblclass.className, tblclassarms.classArmName 
        FROM tblclassteacher
        INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
        WHERE tblclassteacher.Id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $_SESSION['userId']);
    if (!$stmt->execute()) {
        throw new Exception("Error fetching class details: " . $stmt->error);
    }

    $rs = $stmt->get_result();
    if ($rs->num_rows == 0) {
        throw new Exception("No class assigned to this teacher.");
    }

    $rrw = $rs->fetch_assoc();
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
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



   <script>
    function classArmDropdown(str) {
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
        xmlhttp.open("GET","ajaxClassArms2.php?cid="+str,true);
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
            <h1 class="h3 mb-0 text-gray-800">All Student in (<?php echo $rrw['className'].' - '.$rrw['classArmName'];?>) Class</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">All Student in Class</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->


              <!-- Input Group -->
                 <div class="row">
              <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Student In Class</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Other Name</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Class Arm</th>
                      </tr>
                    </thead>
                    
                    <tbody>

                  <?php
                      try {
                          // First get the teacher's assigned class and section
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
                          
                          // Then get all students in that class and section using prepared statement
                          $studentQuery = "SELECT s.Id, c.className, ca.classArmName,
                                   s.firstName, s.lastName, s.otherName, s.admissionNumber
                                   FROM tblstudents s
                                   INNER JOIN tblclass c ON c.Id = s.classId
                                   INNER JOIN tblclassarms ca ON ca.Id = s.classArmId
                                   WHERE s.classId = ? AND s.classArmId = ?
                                   ORDER BY s.firstName ASC";
                          
                          $stmt = $conn->prepare($studentQuery);
                          if (!$stmt) {
                              throw new Exception("Database error: " . $conn->error);
                          }
                          
                          $stmt->bind_param("ss", $teacherData['classId'], $teacherData['classArmId']);
                          if (!$stmt->execute()) {
                              throw new Exception("Error fetching students: " . $stmt->error);
                          }
                          
                          $rs = $stmt->get_result();
                          $num = $rs->num_rows;
                          $sn = 0;
                          
                          if($num > 0) { 
                              while ($rows = $rs->fetch_assoc()) {
                                  $sn = $sn + 1;
                                  echo"
                                  <tr>
                                      <td>".$sn."</td>
                                      <td>".$rows['firstName']."</td>
                                      <td>".$rows['lastName']."</td>
                                      <td>".$rows['otherName']."</td>
                                      <td>".$rows['admissionNumber']."</td>
                                      <td>".$rows['className']."</td>
                                      <td>".$rows['classArmName']."</td>
                                  </tr>";
                              }
                          } else {
                              echo   
                              "<tr><td colspan='7'>
                              <div class='alert alert-info' role='alert'>
                                  No students found in this class section. Please contact the administrator if you think this is an error.
                              </div></td></tr>";
                          }
                      } catch (Exception $e) {
                          echo "<tr><td colspan='7'>
                          <div class='alert alert-danger' role='alert'>
                              Error: " . htmlspecialchars($e->getMessage()) . "
                          </div></td></tr>";
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