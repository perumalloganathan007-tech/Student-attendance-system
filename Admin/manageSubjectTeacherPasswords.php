<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is an Administrator
validate_session('Administrator');

// Handle password reset/update
if(isset($_POST['updatePassword'])) {
    $teacherId = $_POST['teacherId'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    
    if($newPassword === $confirmPassword) {
        // Hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update the password
        $query = "UPDATE tblsubjectteachers SET password = ? WHERE Id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashedPassword, $teacherId);
        
        if($stmt->execute()) {
            $successMsg = "<div class='alert alert-success'>Password updated successfully!</div>";
        } else {
            $errorMsg = "<div class='alert alert-danger'>Error updating password!</div>";
        }
    } else {
        $errorMsg = "<div class='alert alert-danger'>Passwords do not match!</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Subject Teacher Password Management</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
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
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Subject Teacher Password Management</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Password Management</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Subject Teacher Passwords</h6>
                                </div>
                                <div class="card-body">
                                    <?php 
                                        if(isset($successMsg)) { echo $successMsg; }
                                        if(isset($errorMsg)) { echo $errorMsg; }
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="dataTable">
                                            <thead>
                                                <tr>
                                                    <th>Teacher Name</th>
                                                    <th>Email</th>
                                                    <th>Subject</th>
                                                    <th>Last Password Change</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "SELECT st.*, s.subjectName, s.subjectCode 
                                                         FROM tblsubjectteachers st
                                                         INNER JOIN tblsubjects s ON s.Id = st.subjectId
                                                         ORDER BY st.lastName, st.firstName";
                                                $result = $conn->query($query);
                                                while($row = $result->fetch_assoc()):
                                                ?>
                                                <tr>
                                                    <td><?php echo str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName']); ?></td>
                                                    <td><?php echo $row['emailAddress']; ?></td>
                                                    <td><?php echo $row['subjectName'] . ' (' . $row['subjectCode'] . ')'; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['dateCreated'])); ?></td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-primary btn-sm" 
                                                                data-toggle="modal" 
                                                                data-target="#resetPassword<?php echo $row['Id']; ?>">
                                                            Reset Password
                                                        </button>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Reset Password Modal -->
                                                <div class="modal fade" id="resetPassword<?php echo $row['Id']; ?>" tabindex="-1" role="dialog">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reset Password for <?php echo $row['firstName'] . ' ' . $row['lastName']; ?></h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="teacherId" value="<?php echo $row['Id']; ?>">
                                                                    
                                                                    <div class="form-group">
                                                                        <label>New Password</label>
                                                                        <input type="password" 
                                                                               class="form-control" 
                                                                               name="newPassword" 
                                                                               required 
                                                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                                                               title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                                                    </div>
                                                                    
                                                                    <div class="form-group">
                                                                        <label>Confirm Password</label>
                                                                        <input type="password" 
                                                                               class="form-control" 
                                                                               name="confirmPassword" 
                                                                               required>
                                                                    </div>
                                                                    
                                                                    <div class="alert alert-info">
                                                                        Password must contain:
                                                                        <ul class="mb-0">
                                                                            <li>At least 8 characters</li>
                                                                            <li>At least one uppercase letter</li>
                                                                            <li>At least one lowercase letter</li>
                                                                            <li>At least one number</li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                    <button type="submit" name="updatePassword" class="btn btn-primary">Update Password</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>
