<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Initialize variables
$msg = '';

// If the form is submitted
if(isset($_POST['save'])){
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $msg = '<div class="alert alert-danger">Invalid request! Please try again.</div>';
    } else {
        // Get form data
        $subjectName = sanitize_input($conn, $_POST['subjectName']);
        $subjectCode = sanitize_input($conn, $_POST['subjectCode']);
        $classId = sanitize_input($conn, $_POST['classId']);
        
        // Check if subject with same code exists
        $query = "SELECT * FROM tblsubjects WHERE subjectCode = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $subjectCode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $msg = '<div class="alert alert-danger">Subject with this code already exists!</div>';
        } else {
            // Insert the subject
            $query = "INSERT INTO tblsubjects (subjectName, subjectCode, classId, dateCreated) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $subjectName, $subjectCode, $classId);
            
            if($stmt->execute()){
                $msg = '<div class="alert alert-success">Subject created successfully!</div>';
            } else {
                $msg = '<div class="alert alert-danger">An error occurred while creating the subject!</div>';
            }
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
  <title>Create Subject - Admin Dashboard</title>
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="../css/ruang-admin.min.css" rel="stylesheet">
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
            <h1 class="h3 mb-0 text-gray-800">Create Subject</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Subject</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Subject</h6>
                </div>
                <div class="card-body">
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <?php echo $msg; ?>
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Subject Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="subjectName" required placeholder="Enter Subject Name">
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Subject Code<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="subjectCode" required placeholder="Enter Subject Code">
                      </div>
                    </div>
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                        <select class="form-control" name="classId" required>
                          <option value="">--Select Class--</option>
                          <?php 
                          $query = "SELECT * FROM tblclass ORDER BY className ASC";
                          $result = $conn->query($query);
                          while($row = $result->fetch_assoc()){
                          ?>
                            <option value="<?php echo $row['Id']; ?>"><?php echo $row['className']; ?></option>
                          <?php } ?>
                        </select>
                      </div>
                    </div>
                    <button type="submit" name="save" class="btn btn-primary">Save Subject</button>
                  </form>
                </div>
              </div>

              <!-- Subject List -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Subjects</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Subject Code</th>
                        <th>Class</th>
                        <th>Date Created</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $query = "SELECT s.*, c.className 
                               FROM tblsubjects s
                               INNER JOIN tblclass c ON c.Id = s.classId
                               ORDER BY s.dateCreated DESC";
                      $result = $conn->query($query);
                      $cnt = 1;
                      while($row = $result->fetch_assoc()){
                      ?>
                        <tr>
                          <td><?php echo $cnt; ?></td>
                          <td><?php echo $row['subjectName']; ?></td>
                          <td><?php echo $row['subjectCode']; ?></td>
                          <td><?php echo $row['className']; ?></td>
                          <td><?php echo $row['dateCreated']; ?></td>
                          <td>
                            <a href="editSubject.php?Id=<?php echo $row['Id']; ?>" class="btn btn-primary btn-sm">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="deleteSubject.php?Id=<?php echo $row['Id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this subject?');">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php 
                        $cnt++;
                      } 
                      ?>
                    </tbody>
                  </table>
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
  <script src="../js/ruang-admin.min.js"></script>
  <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>
    $(document).ready(function () {
      $('#dataTableHover').DataTable();
    });
  </script>
</body>
</html>
