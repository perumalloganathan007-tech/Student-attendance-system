<?php 
error_reporting(E_ALL); // Enable error reporting for debugging
include '../Includes/dbcon.php';

// Initialize status message variable
$statusMsg = "";

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
        $firstName = sanitize_input($conn, $_POST['firstName']);
        $lastName = sanitize_input($conn, $_POST['lastName']);
        $emailAddress = sanitize_input($conn, $_POST['emailAddress']);
        $phoneNo = sanitize_input($conn, $_POST['phoneNo']);
        $subjectId = sanitize_input($conn, $_POST['subjectId']);
        $password = $_POST['password'];

        // Validate required fields
        if(empty($firstName) || empty($lastName) || empty($emailAddress) || empty($phoneNo) || empty($subjectId) || empty($password)) {
            throw new Exception("All fields are required!");
        }

        // Check if email already exists
        $query = "SELECT * FROM tblsubjectteacher WHERE emailAddress = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $emailAddress);
        if (!$stmt->execute()) {
            throw new Exception("Error checking email: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            throw new Exception("This Email Address Already Exists!");
        }

        // Hash password using password_hash for better security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new subject teacher
        $query = "INSERT INTO tblsubjectteacher(firstName, lastName, emailAddress, password, phoneNo, subjectId, dateCreated) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ssssss", $firstName, $lastName, $emailAddress, $hashedPassword, $phoneNo, $subjectId);
        if($stmt->execute()) {
            $statusMsg = "<div class='alert alert-success'>Subject Teacher created successfully!</div>";
            // Clear form data after successful save
            $_POST = array();
        } else {
            throw new Exception("Error saving subject teacher: " . $stmt->error);
        }
    } catch (Exception $e) {
        $statusMsg = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
    }
}

//------------------------DELETE--------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    $Id = $_GET['Id'];
    $query = mysqli_query($conn,"DELETE FROM tblsubjectteachers WHERE Id='$Id'");

    if ($query == TRUE) {
        echo "<script type = \"text/javascript\">
        window.location = (\"createSubjectTeacher.php\")
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
    <title>Dashboard - Create Subject Teachers</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Create Subject Teachers</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Subject Teachers</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Create Subject Teachers</h6>
                                    <?php echo $statusMsg; ?>
                                </div>                                <div class="card-body">
                                    <form method="post" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">First Name<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" required name="firstName" value="<?php echo isset($row['firstName']) ? $row['firstName'] : ''; ?>" placeholder="Enter First Name">
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Last Name<span class="text-danger ml-2">*</span></label>                                                <input type="text" class="form-control" required name="lastName" value="<?php echo isset($row['lastName']) ? $row['lastName'] : ''; ?>" placeholder="Enter Last Name">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Email Address<span class="text-danger ml-2">*</span></label>
                                                <input type="email" class="form-control" required name="emailAddress" value="<?php echo isset($row['emailAddress']) ? $row['emailAddress'] : ''; ?>" placeholder="Enter Email Address">
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Phone No<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" required name="phoneNo" value="<?php echo isset($row['phoneNo']) ? $row['phoneNo'] : ''; ?>" placeholder="Enter Phone No">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Select Subject<span class="text-danger ml-2">*</span></label>
                                                <?php
                                                $qry= "SELECT * FROM tblsubjects ORDER BY subjectName ASC";
                                                $result = $conn->query($qry);
                                                $num = $result->num_rows;		
                                                if ($num > 0){
                                                    echo '<select required name="subjectId" class="form-control mb-3">';
                                                    echo '<option value="">--Select Subject--</option>';
                                                    while ($rows = $result->fetch_assoc()){
                                                        echo '<option value="'.$rows['Id'].'" >'.$rows['subjectName'].' ('.$rows['subjectCode'].')</option>';
                                                    }
                                                    echo '</select>';
                                                }
                                                ?>
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Password<span class="text-danger ml-2">*</span></label>
                                                <input type="password" class="form-control" required name="password" placeholder="Enter Password">
                                            </div>
                                        </div>
                                        <button type="submit" name="save" class="btn btn-primary">Save</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Input Group -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card mb-4">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">All Subject Teachers</h6>
                                        </div>
                                        <div class="table-responsive p-3">
                                            <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>First Name</th>
                                                        <th>Last Name</th>
                                                        <th>Email Address</th>
                                                        <th>Phone No</th>
                                                        <th>Subject</th>
                                                        <th>Date Created</th>
                                                        <th>Delete</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php                                                    $query = "SELECT st.*, s.subjectName, s.subjectCode 
                                                             FROM tblsubjectteacher st
                                                             INNER JOIN tblsubjects s ON s.Id = st.subjectId";
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
                                                                    <td>".$rows['firstName']."</td>
                                                                    <td>".$rows['lastName']."</td>
                                                                    <td>".$rows['emailAddress']."</td>
                                                                    <td>".$rows['phoneNo']."</td>
                                                                    <td>".$rows['subjectName']." (".$rows['subjectCode'].")</td>
                                                                    <td>".$rows['dateCreated']."</td>
                                                                    <td><a href='?action=delete&Id=".$rows['Id']."'><i class='fas fa-fw fa-trash'></i></a></td>
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
