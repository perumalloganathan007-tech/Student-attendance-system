h<?php 
error_reporting(0);
include '../Includes/dbcon.php';

// Validate that user is an Administrator
validate_session('Administrator');

//------------------------SAVE--------------------------------------------------

if(isset($_POST['save'])){
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            throw new Exception("Invalid request! Please try again.");
        }

        // Get and sanitize form data
        $subjectName = isset($_POST['subjectName']) ? sanitize_input($conn, $_POST['subjectName']) : '';
        $subjectCode = isset($_POST['subjectCode']) ? sanitize_input($conn, $_POST['subjectCode']) : '';
        $classId = isset($_POST['classId']) ? sanitize_input($conn, $_POST['classId']) : '';
        
        // Validate required fields
        if(empty($subjectName) || empty($subjectCode) || empty($classId)) {
            throw new Exception("All fields are required!");
        }        // Check if subject with same name or code exists
        $query = "SELECT * FROM tblsubjects WHERE subjectName = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $subjectName);
        if (!$stmt->execute()) {
            throw new Exception("Error checking existing subjects: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if($result->num_rows > 0) { 
            throw new Exception("This Subject Name Already Exists!");
        }
        
        // Check if subject code exists
        $query = "SELECT * FROM tblsubjects WHERE subjectCode = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $subjectCode);
        if (!$stmt->execute()) {
            throw new Exception("Error checking existing subject code: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if($result->num_rows > 0) { 
            throw new Exception("This Subject or Subject Code Already Exists!");
        }

        // Insert the subject
        $query = "INSERT INTO tblsubjects (subjectName, subjectCode, classId, dateCreated) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("sss", $subjectName, $subjectCode, $classId);
        if($stmt->execute()) {
            $statusMsg = "<div class='alert alert-success'>Subject created successfully!</div>";
            // Clear form data after successful save
            $_POST = array();
        } else {
            throw new Exception("Error saving subject: " . $stmt->error);
        }    } catch (Exception $e) {
        $statusMsg = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

//---------------------------------------EDIT-------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $Id = $_GET['Id'];
    $query = mysqli_query($conn, "select * from tblsubjects where Id ='$Id'");
    $row = mysqli_fetch_array($query);
}

//------------------------UPDATE--------------------------------------------------

if(isset($_POST['update'])){
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $statusMsg = "<div class='alert alert-danger'>Invalid request! Please try again.</div>";
    } else {
        // Get and sanitize form data
        $subjectName = sanitize_input($conn, $_POST['subjectName']);
        $subjectCode = sanitize_input($conn, $_POST['subjectCode']);
        $classId = sanitize_input($conn, $_POST['classId']);
        $Id = $_GET['Id'];
        
        // Check if another subject with same code exists (excluding current subject)
        $query = "SELECT * FROM tblsubjects WHERE (subjectName = ? OR subjectCode = ?) AND Id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $subjectName, $subjectCode, $Id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0){
            $statusMsg = "<div class='alert alert-danger'>This Subject or Subject Code Already Exists!</div>";
        } else {
            $query = "UPDATE tblsubjects SET subjectName=?, subjectCode=?, classId=? WHERE Id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $subjectName, $subjectCode, $classId, $Id);
            
            if($stmt->execute()){
                echo "<script type = \"text/javascript\">
                window.location = (\"createSubjects.php\")
                </script>";
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred: " . $conn->error . "</div>";
            }
        }
    }
}

//------------------------DELETE--------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    $Id = $_GET['Id'];
    $query = mysqli_query($conn, "DELETE FROM tblsubjects WHERE Id='$Id'");

    if ($query == TRUE) {
        echo "<script type = \"text/javascript\">
        window.location = (\"createSubjects.php\")
        </script>";
    }
    else {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>"; 
    }
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
    <title>Dashboard - Create Subjects</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Create Subjects</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Subjects</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Create Subjects</h6>
                                    <?php echo $statusMsg; ?>
                                </div>                                <div class="card-body">
                                    <form method="post" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Subject Name<span class="text-danger ml-2">*</span></label>                                                <input type="text" class="form-control" required name="subjectName" value="<?php echo isset($row['subjectName']) ? $row['subjectName'] : ''; ?>" placeholder="Enter Subject Name">
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Subject Code<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" required name="subjectCode" value="<?php echo isset($row['subjectCode']) ? $row['subjectCode'] : ''; ?>" placeholder="Enter Subject Code">
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" name="classId" required>
                                                    <option value="">--Select Class--</option>
                                                    <?php 
                                                    $query = "SELECT * FROM tblclass ORDER BY className ASC";
                                                    $result = $conn->query($query);
                                                    if($result) {
                                                        while($classRow = $result->fetch_assoc()){
                                                            $selected = (isset($row['classId']) && $classRow['Id'] == $row['classId']) ? 'selected' : '';
                                                    ?>
                                                            <option value="<?php echo $classRow['Id']; ?>" <?php echo $selected; ?>><?php echo $classRow['className']; ?></option>
                                                    <?php 
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <?php
                                        if (isset($Id)) {
                                        ?>
                                        <button type="submit" name="update" class="btn btn-warning">Update</button>
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <?php
                                        } else {           
                                        ?>
                                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                                        <?php
                                        }         
                                        ?>
                                    </form>
                                </div>
                            </div>

                            <!-- Input Group -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">All Subjects</h6>
                                        </div>
                                        <div class="table-responsive p-3">
                                            <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                                <thead class="thead-light">                                                    <tr>
                                                        <th>#</th>
                                                        <th>Subject Name</th>
                                                        <th>Subject Code</th>
                                                        <th>Class</th>
                                                        <th>Date Created</th>
                                                        <th>Edit</th>
                                                        <th>Delete</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $query = "SELECT s.*, c.className 
                                                             FROM tblsubjects s
                                                             LEFT JOIN tblclass c ON c.Id = s.classId
                                                             ORDER BY s.dateCreated DESC";
                                                    $rs = $conn->query($query);
                                                    $num = $rs->num_rows;
                                                    $sn=0;
                                                    if($num > 0)
                                                    { 
                                                        while ($rows = $rs->fetch_assoc())
                                                        {
                                                            $sn = $sn + 1;
                                                            echo"
                                                                <tr>
                                                                    <td>".$sn."</td>
                                                                    <td>".$rows['subjectName']."</td>
                                                                    <td>".$rows['subjectCode']."</td>
                                                                    <td>".$rows['className']."</td>
                                                                    <td>".$rows['dateCreated']."</td>
                                                                    <td><a href='?action=edit&Id=".$rows['Id']."'><i class='fas fa-fw fa-edit'></i></a></td>
                                                                    <td><a href='?action=delete&Id=".$rows['Id']."' onclick=\"return confirm('Are you sure you want to delete this subject?');\"><i class='fas fa-fw fa-trash'></i></a></td>
                                                                </tr>";
                                                        }
                                                    }
                                                    else {
                                                        echo   
                                                        "<div class='alert alert-danger' role='alert'>
                                                            No Record Found!
                                                        </div>";
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
    <script>
        $(document).ready(function () {
            $('#dataTableHover').DataTable();
        });
    </script>
</body>

</html>
