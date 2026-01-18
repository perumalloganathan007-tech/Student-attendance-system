<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

// Handle password change
if(isset($_POST['changePassword'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
      // Verify current password
    $query = "SELECT password FROM tblsubjectteacher WHERE Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if(password_verify($currentPassword, $user['password'])) {
        if($newPassword === $confirmPassword) {
            // Password complexity validation
            if(strlen($newPassword) < 8 || 
               !preg_match("#[0-9]+#", $newPassword) || 
               !preg_match("#[a-z]+#", $newPassword) || 
               !preg_match("#[A-Z]+#", $newPassword)) {
                $errorMsg = "<div class='alert alert-danger'>
                    Password must be at least 8 characters long and contain:
                    <ul>
                        <li>At least one number</li>
                        <li>At least one uppercase letter</li>
                        <li>At least one lowercase letter</li>
                    </ul>
                </div>";
            } else {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                  // Update the password
                $query = "UPDATE tblsubjectteacher SET password = ? WHERE Id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashedPassword, $_SESSION['userId']);
                
                if($stmt->execute()) {
                    $successMsg = "<div class='alert alert-success'>Password changed successfully!</div>";
                } else {
                    $errorMsg = "<div class='alert alert-danger'>Error updating password!</div>";
                }
            }
        } else {
            $errorMsg = "<div class='alert alert-danger'>New passwords do not match!</div>";
        }
    } else {
        $errorMsg = "<div class='alert alert-danger'>Current password is incorrect!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Change Password</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
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
                        <h1 class="h3 mb-0 text-gray-800">Change Password</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Change Password</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Change Your Password</h6>
                                </div>
                                <div class="card-body">
                                    <?php 
                                        if(isset($successMsg)) { echo $successMsg; }
                                        if(isset($errorMsg)) { echo $errorMsg; }
                                    ?>
                                    <form method="post" onsubmit="return validatePassword()">
                                        <div class="form-group">
                                            <label>Current Password</label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="currentPassword" 
                                                       id="currentPassword"
                                                       required>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                                        <i class="fas fa-eye" id="currentPassword-icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>New Password</label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="newPassword" 
                                                       id="newPassword"
                                                       required 
                                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                       title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                                        <i class="fas fa-eye" id="newPassword-icon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Confirm New Password</label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       class="form-control" 
                                                       name="confirmPassword" 
                                                       id="confirmPassword"
                                                       required>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                                        <i class="fas fa-eye" id="confirmPassword-icon"></i>
                                                    </button>
                                                </div>
                                            </div>
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
                                        
                                        <button type="submit" name="changePassword" class="btn btn-primary">Change Password</button>
                                    </form>
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
    <script src="js/ruang-admin.min.js"></script>
    
    <script>
    function validatePassword() {
        var newPassword = document.getElementById("newPassword").value;
        var confirmPassword = document.getElementById("confirmPassword").value;
        
        if(newPassword != confirmPassword) {
            alert("Passwords do not match!");
            return false;
        }
        
        // Check password complexity
        var passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/;
        if(!passwordRegex.test(newPassword)) {
            alert("Password must contain at least 8 characters, including uppercase, lowercase, and numbers!");
            return false;
        }
        
        return true;
    }

    function togglePassword(inputId) {
        const passwordInput = document.getElementById(inputId);
        const icon = document.getElementById(inputId + '-icon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
