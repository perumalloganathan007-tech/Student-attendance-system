<?php 
include 'Includes/dbcon.php';
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
    <title> SSA - Login</title>
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

<body class="bg-gradient-login" style="background-image: url('img/logo/loral1.jpe00g');">
    <!-- Login Content -->
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
                                        <h1 class="h4 text-gray-900 mb-4">Login Panel</h1>
                                    </div>
                                    <form class="user" method="Post" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <div class="form-group">
                                            <select required name="userType" class="form-control mb-3" onchange="updatePlaceholder(this.value)">
                                                <option value="">--Select User Roles--</option>
                                                <option value="Administrator">Administrator</option>
                                                <option value="ClassTeacher">Class Teacher</option>
                                                <option value="SubjectTeacher">Subject Teacher</option>
                                                <option value="Student">Student</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control" required name="emailAddress" id="exampleInputEmail" placeholder="Enter Email Address / Admission Number">
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
                                                <!-- <label class="custom-control-label" for="customCheck">Remember
                          Me</label> -->
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" class="btn btn-success btn-block" value="Login" name="login" />
                                        </div>
                                    </form>
                                    <div class="text-center">
                                        <a href="forgotPassword.php" class="font-weight-bold small">Forgot Password?</a>
                                    </div>

                                    <?php

  if(isset($_POST['login'])){
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo "<div class='alert alert-danger' role='alert'>Invalid request! Please try again.</div>";
        exit;
    }
    
    if(empty($_POST['userType'])) {
        echo "<div class='alert alert-danger' role='alert'>Please select a user type!</div>";
        exit;
    }

    $userType = sanitize_input($conn, $_POST['userType']);
    $username = sanitize_input($conn, isset($_POST['emailAddress']) ? $_POST['emailAddress'] : $_POST['username']);
    $password = trim($_POST['password']);

    if($userType == "Administrator"){
      $user = verify_login($conn, "tbladmin", "emailAddress", $username, $password);
      if($user) {
        // Regenerate session ID to prevent session fixation
        regenerate_session();
        
        $_SESSION['userId'] = $user['Id'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['emailAddress'] = $user['emailAddress'];
        $_SESSION['user_type'] = 'Administrator';
        $_SESSION['last_login'] = time();

        echo "<script type = \"text/javascript\">
        window.location = (\"Admin/index.php\")
        </script>";
      }

      else{

        echo "<div class='alert alert-danger' role='alert'>
        Invalid Username/Password!
        </div>";

      }
    }
    else if($userType == "ClassTeacher"){
      $user = verify_login($conn, "tblclassteacher", "emailAddress", $username, $password);
      if($user) {
        // Regenerate session ID to prevent session fixation
        regenerate_session();
        
        $_SESSION['userId'] = $user['Id'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['emailAddress'] = $user['emailAddress'];
        $_SESSION['classId'] = $user['classId'];
        $_SESSION['classArmId'] = $user['classArmId'];
        $_SESSION['user_type'] = 'ClassTeacher';
        $_SESSION['last_login'] = time();

        echo "<script type = \"text/javascript\">
        window.location = (\"ClassTeacher/index.php\")
        </script>";
      }

      else{

        echo "<div class='alert alert-danger' role='alert'>
        Invalid Username/Password!
        </div>";

      }
    }
    else if($userType == "SubjectTeacher"){
        $query = "SELECT st.*, s.subjectName, s.Id as subjectId 
                FROM tblsubjectteacher st
                INNER JOIN tblsubjects s ON s.Id = st.subjectId
                WHERE st.emailAddress = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent session fixation
            regenerate_session();
            
            $_SESSION['userId'] = $user['Id'];
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['lastName'] = $user['lastName'];
            $_SESSION['emailAddress'] = $user['emailAddress'];
            $_SESSION['subjectId'] = $user['subjectId'];
            $_SESSION['subjectName'] = $user['subjectName'];
            $_SESSION['userType'] = 'SubjectTeacher';
            $_SESSION['last_login'] = time();

            echo "<script type = \"text/javascript\">
            window.location = (\"SubjectTeacher/index.php\")
            </script>";
        } else {
            echo "<div class='alert alert-danger' role='alert'>
            Invalid Username/Password!
            </div>";
        }
    }
    else if($userType == "Student"){
        // Get student info with class details
        $query = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName 
                FROM tblstudents
                INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId 
                WHERE admissionNumber = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die('SQL Error: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && verify_password($password, $user['password'])) {
            // Basic validation of required fields
            if (empty($user['classId']) || empty($user['classArmId'])) {
                echo "<div class='alert alert-danger' role='alert'>
                Student record is incomplete. Please contact administrator.
                </div>";
                exit;
            }
            
            // Regenerate session ID to prevent session fixation
            regenerate_session();
            
            // Set all required session variables
            $_SESSION['userId'] = $user['Id'];
            $_SESSION['firstName'] = $user['firstName'];
            $_SESSION['lastName'] = $user['lastName']; 
            $_SESSION['classId'] = $user['classId'];
            $_SESSION['classArmId'] = $user['classArmId'];
            $_SESSION['className'] = $user['className'];
            $_SESSION['classArmName'] = $user['classArmName'];
            $_SESSION['admissionNumber'] = $user['admissionNumber'];
            $_SESSION['userType'] = 'Student';
            $_SESSION['last_login'] = time();
            
            // Record the login in login_logs table
            $ip = $_SERVER['REMOTE_ADDR'];
            $logQuery = "INSERT INTO login_logs (user_id, user_type, ip_address) VALUES (?, 'Student', ?)";
            if ($logStmt = $conn->prepare($logQuery)) {
                $logStmt->bind_param("is", $user['Id'], $ip);
                $logStmt->execute();
            }

            // Redirect to student dashboard
            echo "<script type='text/javascript'>
            window.location = ('Student/index.php');
            </script>";
        } else {
            // Rate limit login attempts
            $ip = $_SERVER['REMOTE_ADDR'];
            $time = time();
            $attempts_query = "INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, FROM_UNIXTIME(?))";
            if ($attempt_stmt = $conn->prepare($attempts_query)) {
                $attempt_stmt->bind_param("si", $ip, $time);
                $attempt_stmt->execute();
            }
            
            // Check if account should be temporarily blocked
            $check_attempts = "SELECT COUNT(*) as attempts FROM login_attempts 
                             WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            if ($check_stmt = $conn->prepare($check_attempts)) {
                $check_stmt->bind_param("s", $ip);
                $check_stmt->execute();
                $attempts_result = $check_stmt->get_result();
                $attempts = $attempts_result->fetch_assoc()['attempts'];
                
                if ($attempts >= 5) {
                    echo "<div class='alert alert-danger' role='alert'>
                    Too many failed login attempts. Please try again after 15 minutes.
                    </div>";
                    exit;
                }
            }

            echo "<div class='alert alert-danger' role='alert'>
            Invalid Admission Number/Password!
            </div>";
        }
    }
    else{

        echo "<div class='alert alert-danger' role='alert'>
        Invalid Username/Password!
        </div>";

    }
}
?>

                                    <!-- <hr>
                    <a href="index.html" class="btn btn-google btn-block">
                      <i class="fab fa-google fa-fw"></i> Login with Google
                    </a>
                    <a href="index.html" class="btn btn-facebook btn-block">
                      <i class="fab fa-facebook-f fa-fw"></i> Login with Facebook
                    </a> -->


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
    <!-- Login Content -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script>
        function updatePlaceholder(userType) {
            const emailInput = document.getElementById('exampleInputEmail');
            switch(userType) {
                case 'Administrator':
                case 'ClassTeacher':
                case 'SubjectTeacher':
                    emailInput.placeholder = 'Enter Email Address';
                    break;
                case 'Student':
                    emailInput.placeholder = 'Enter Admission Number';
                    break;
                default:
                    emailInput.placeholder = 'Enter Email Address / Admission Number';
            }
        }

        // Set initial placeholder based on selected value
        document.addEventListener('DOMContentLoaded', function() {
            const userType = document.querySelector('select[name="userType"]');
            updatePlaceholder(userType.value);
        });

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