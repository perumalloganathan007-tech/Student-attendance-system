<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Student
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

$statusMsg = '';

if(isset($_POST['update'])) {
    $currentPassword = $_POST['currentpassword'];
    $newPassword = $_POST['newpassword'];
    $confirmPassword = $_POST['confirmpassword'];
    
    // Verify current password
    $query = "SELECT password FROM tblstudents WHERE Id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if(password_verify($currentPassword, $row['password'])) {
        if($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $query = "UPDATE tblstudents SET password=? WHERE Id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('si', $hashedPassword, $_SESSION['userId']);
            
            if($stmt->execute()) {
                $statusMsg = '<div class="alert alert-success">Password changed successfully!</div>';
            } else {
                $statusMsg = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            }
        } else {
            $statusMsg = '<div class="alert alert-danger">New Password and Confirm Password do not match!</div>';
        }
    } else {
        $statusMsg = '<div class="alert alert-danger">Current Password is incorrect!</div>';
    }
}

include('Includes/header.php');
?>

<!-- Container Fluid-->
<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Change Password</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Change Password</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- Form Basic -->
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                </div>
                <div class="card-body">
                    <?php echo $statusMsg; ?>
                    <form method="post">
                        <div class="form-group row mb-3">
                            <div class="col-xl-6">
                                <label class="form-control-label">Current Password<span class="text-danger ml-2">*</span></label>
                                <input type="password" class="form-control" name="currentpassword" required>
                            </div>
                        </div>
                        <div class="form-group row mb-3">
                            <div class="col-xl-6">
                                <label class="form-control-label">New Password<span class="text-danger ml-2">*</span></label>
                                <input type="password" class="form-control" name="newpassword" required>
                            </div>
                        </div>
                        <div class="form-group row mb-3">
                            <div class="col-xl-6">
                                <label class="form-control-label">Confirm New Password<span class="text-danger ml-2">*</span></label>
                                <input type="password" class="form-control" name="confirmpassword" required>
                            </div>
                        </div>
                        <button type="submit" name="update" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('Includes/footer.php'); ?>
