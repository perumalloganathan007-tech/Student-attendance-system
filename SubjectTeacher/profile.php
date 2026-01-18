<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Subject Teacher
validate_session('SubjectTeacher');

$userId = $_SESSION['userId'];
$statusMsg = "";

// Get teacher information
$query = "SELECT * FROM tblsubjectteacher WHERE Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Get subject information
$subjectQuery = "SELECT * FROM tblsubjects WHERE Id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param('i', $teacher['subjectId']);
$stmt->execute();
$subjectResult = $stmt->get_result();
$subject = $subjectResult->fetch_assoc();

// Handle form submission for profile update
if (isset($_POST['update'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];
    $phoneNo = $_POST['phoneNo'];
      // Update teacher information
    $updateQuery = "UPDATE tblsubjectteacher SET 
                    firstName = ?, 
                    lastName = ?, 
                    emailAddress = ?, 
                    phoneNo = ? 
                    WHERE Id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('ssssi', $firstName, $lastName, $emailAddress, $phoneNo, $userId);
    
    if ($updateStmt->execute()) {
        $statusMsg = "<div class='alert alert-success'>Profile updated successfully!</div>";
        
        // Refresh teacher data
        $stmt->execute();
        $result = $stmt->get_result();
        $teacher = $result->fetch_assoc();
    } else {
        $statusMsg = "<div class='alert alert-danger'>Error updating profile: " . $conn->error . "</div>";
    }
}

// Handle password change
if (isset($_POST['changePassword'])) {
    $currentPassword = $_POST['currentPassword'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
      // Verify current password
    if (password_verify($currentPassword, $teacher['password'])) {
        // Check if new passwords match
        if ($newPassword == $confirmPassword) {
            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE tblsubjectteacher SET password = ? WHERE Id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('si', $passwordHash, $userId);
            
            if ($updateStmt->execute()) {
                $statusMsg = "<div class='alert alert-success'>Password changed successfully!</div>";
            } else {
                $statusMsg = "<div class='alert alert-danger'>Error changing password: " . $conn->error . "</div>";
            }
        } else {
            $statusMsg = "<div class='alert alert-danger'>New passwords do not match!</div>";
        }
    } else {
        $statusMsg = "<div class='alert alert-danger'>Current password is incorrect!</div>";
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
    <title>Teacher Profile</title>
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
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </div>
                    
                    <?php echo $statusMsg; ?>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <!-- Profile Information -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="firstName">First Name</label>
                                            <input type="text" class="form-control" id="firstName" name="firstName" 
                                                value="<?php echo $teacher['firstName']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastName">Last Name</label>
                                            <input type="text" class="form-control" id="lastName" name="lastName" 
                                                value="<?php echo $teacher['lastName']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="emailAddress">Email Address</label>
                                            <input type="email" class="form-control" id="emailAddress" name="emailAddress" 
                                                value="<?php echo $teacher['emailAddress']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="phoneNo">Phone Number</label>
                                            <input type="text" class="form-control" id="phoneNo" name="phoneNo" 
                                                value="<?php echo $teacher['phoneNo']; ?>">
                                        </div>
                                        <button type="submit" name="update" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <!-- Subject Information -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Subject Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Subject Name</label>
                                        <input type="text" class="form-control" value="<?php echo $subject['subjectName']; ?>" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>Subject Code</label>
                                        <input type="text" class="form-control" value="<?php echo $subject['subjectCode']; ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Change Password -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label for="currentPassword">Current Password</label>
                                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="newPassword">New Password</label>
                                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirmPassword">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                        </div>
                                        <button type="submit" name="changePassword" class="btn btn-primary">Change Password</button>
                                    </form>
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
