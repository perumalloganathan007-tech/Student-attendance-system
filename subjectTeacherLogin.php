<?php 
error_reporting(E_ALL); // Enable error reporting for debugging
ini_set('display_errors', 1);
include 'Includes/dbcon.php';
include 'Includes/session.php';

// Initialize status message variable
$statusMsg = "";

if(isset($_POST['login'])){
    if(empty($_POST['emailAddress']) || empty($_POST['password'])) {
        $statusMsg = "<div class='alert alert-danger' role='alert'>All fields are required</div>";
    } else {
        $emailAddress = filter_var($_POST['emailAddress'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if(!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid email format</div>";
        } else {
            $query = "SELECT * FROM tblsubjectteacher WHERE emailAddress = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $emailAddress);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                
                // Update password hash for all predefined teachers (temporary fix)
                if ($emailAddress == "john.smith@school.com" || 
                    $emailAddress == "sarah.johnson@school.com" || 
                    $emailAddress == "michael.williams@school.com" || 
                    $emailAddress == "emily.brown@school.com" || 
                    $emailAddress == "david.jones@school.com") {
                    
                    // The default password is Password@123
                    if ($password == "Password@123") {                // Set session variables and log the user in
                        $_SESSION['userId'] = $row['Id'];
                        $_SESSION['firstName'] = $row['firstName'];
                        $_SESSION['lastName'] = $row['lastName'];
                        $_SESSION['emailAddress'] = $row['emailAddress'];
                        $_SESSION['subjectId'] = $row['subjectId'];
                        $_SESSION['userType'] = 'SubjectTeacher';
                        $_SESSION['user_type'] = 'SubjectTeacher'; // For compatibility
                        $_SESSION['last_login'] = time();
                        $_SESSION['LAST_ACTIVITY'] = time(); // For compatibility
                        
                        // Update the password hash for future logins
                        $newHash = password_hash("Password@123", PASSWORD_BCRYPT);
                        $updateQuery = "UPDATE tblsubjectteacher SET password = ? WHERE emailAddress = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("ss", $newHash, $emailAddress);
                        $updateStmt->execute();
                        
                        header("Location: SubjectTeacher/index.php");
                        exit();                    } else {
                        $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid Password! Use 'Password@123' for demo accounts. Debug: Entered password='$password'</div>";
                    }
                }                // Regular login check with password_verify
                else if(password_verify($password, $row['password'])) {
                    $_SESSION['userId'] = $row['Id'];
                    $_SESSION['firstName'] = $row['firstName'];
                    $_SESSION['lastName'] = $row['lastName'];
                    $_SESSION['emailAddress'] = $row['emailAddress'];
                    $_SESSION['subjectId'] = $row['subjectId'];
                    $_SESSION['userType'] = 'SubjectTeacher';
                    $_SESSION['user_type'] = 'SubjectTeacher'; // For compatibility
                    $_SESSION['last_login'] = time();
                    $_SESSION['LAST_ACTIVITY'] = time(); // For compatibility
            
                    header("Location: SubjectTeacher/index.php");
                    exit();                } else {
                    $statusMsg = "<div class='alert alert-danger' role='alert'>Invalid Password! Debug: Email=$emailAddress, Password length=" . strlen($password) . "</div>";
                }
            } else {
                $statusMsg = "<div class='alert alert-danger' role='alert'>User not found! Debug: Email=$emailAddress</div>";
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
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Subject Teacher Login</title>
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <style>
    .input-group-append .btn {
      padding: .375rem .75rem;
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }
  </style>
</head>
<body class="bg-gradient-login">
  <div class="container-login">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-lg-12 col-md-9">
        <div class="card shadow-sm my-5">
          <div class="card-body p-0">
            <div class="row">
              <div class="col-lg-12">
                <div class="login-form">
                  <h5 align="center">STUDENT ATTENDANCE SYSTEM</h5>
                  <div class="text-center">
                    <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                    <br><br>
                    <h1 class="h4 text-gray-900 mb-4">Subject Teacher Login</h1>
                  </div>
                  <?php echo $statusMsg; ?>
                  <form class="user" method="Post" action="">
                    <div class="form-group">
                      <input type="email" class="form-control" required name="emailAddress" id="exampleInputEmail" placeholder="Enter Email Address">
                    </div>
                    <div class="form-group">
                      <div class="input-group">
                        <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="Enter Password">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('exampleInputPassword')">
                            <i class="fas fa-eye" id="exampleInputPassword-icon"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="form-group">
                      <div class="custom-control custom-checkbox small" style="line-height: 1.5rem;">
                        <input type="checkbox" class="custom-control-input" id="customCheck">
                        <label class="custom-control-label" for="customCheck">Remember Me</label>
                      </div>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary btn-block" value="Login" name="login" />
                    </div>
                  </form>
                  <hr>
                  <div class="text-center">
                    <a class="font-weight-bold small" href="classTeacherLogin.php">Class Teacher Login</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="font-weight-bold small" href="index.php">Student/Admin Login</a>
                  </div>
                  <div class="text-center">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <script>
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
