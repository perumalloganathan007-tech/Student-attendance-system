<?php
include 'Includes/dbcon.php';
include 'Includes/mailConfig.php';
require_once __DIR__ . '/PHPMailer-6.10.0/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-6.10.0/src/SMTP.php';
require_once __DIR__ . '/PHPMailer-6.10.0/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST['submit'])){
    $userType = $_POST['userType'];
    $identifier = $_POST['identifier']; // This will be email for teacher or admission number for student
    
    // Get admin email from database
    $adminQuery = "SELECT emailAddress FROM tbladmin LIMIT 1";
    $adminResult = $conn->query($adminQuery);
    $adminRow = $adminResult->fetch_assoc();
    $adminEmail = $adminRow['emailAddress'];
    
    // Verify user exists
    if($userType == "ClassTeacher"){
        $query = "SELECT * FROM tblclassteacher WHERE emailAddress = '".$conn->real_escape_string($identifier)."'";
    } else {
        $query = "SELECT * FROM tblstudents WHERE admissionNumber = '".$conn->real_escape_string($identifier)."'";
    }
    
    $result = $conn->query($query);
    
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        
        // Prepare email content
        $to = $adminEmail;
        $subject = "Password Reset Request";
        $message = "A password reset has been requested for:\n\n";
        $message .= "User Type: " . $userType . "\n";
        if($userType == "ClassTeacher"){
            $message .= "Teacher Email: " . $identifier . "\n";
            $message .= "Teacher Name: " . $user['firstName'] . " " . $user['lastName'] . "\n";
        } else {
            $message .= "Student Admission Number: " . $identifier . "\n";
            $message .= "Student Name: " . $user['firstName'] . " " . $user['lastName'] . "\n";
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            echo "<div class='alert alert-success' role='alert'>Password reset request has been sent to administrator. You will be contacted soon.</div>";
        } catch (Exception $e) {
            echo "<div class='alert alert-danger' role='alert'>Failed to send reset request. Mailer Error: ".htmlspecialchars($mail->ErrorInfo)."</div>";
        }
    } else {
        echo "<div class='alert alert-danger' role='alert'>No account found with provided details.</div>";
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
    <title>Forgot Password</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
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
                                    <h4 class="text-center">Forgot Password</h4>
                                    <form class="user" method="POST" action="">
                                        <div class="form-group">
                                            <select required name="userType" class="form-control mb-3">
                                                <option value="">--Select User Type--</option>
                                                <option value="ClassTeacher">Class Teacher</option>
                                                <option value="Student">Student</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="form-control" required name="identifier" 
                                                placeholder="Enter Email (Teacher) / Admission Number (Student)">
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" class="btn btn-primary btn-block" value="Submit Request" name="submit">
                                        </div>
                                    </form>
                                    <div class="text-center">
                                        <a class="font-weight-bold small" href="index.php">Back to Login</a>
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
</body>

</html>
