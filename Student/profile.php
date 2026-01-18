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

// Get student profile information
$query = "SELECT 
            s.*,
            c.className,
            ca.classArmName,
            t.firstName as teacherFirstName,
            t.lastName as teacherLastName
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
          LEFT JOIN tblclassteacher t ON t.classId = s.classId AND t.classArmId = s.classArmId
          WHERE s.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

include('Includes/header.php');
?>

<!-- Container Fluid-->
<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <!-- Profile Basic Info -->
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3">
                            <img src="../img/user-icn.png" class="img-fluid rounded-circle mx-auto d-block" style="max-width: 150px;">
                        </div>
                        <div class="col-lg-9">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">Full Name</th>
                                    <td><?php echo $profile['firstName'] . ' ' . $profile['lastName']; ?></td>
                                </tr>
                                <tr>
                                    <th>Admission Number</th>
                                    <td><?php echo $profile['admissionNumber']; ?></td>
                                </tr>
                                <tr>
                                    <th>Class</th>
                                    <td><?php echo $profile['className'] . ' ' . $profile['classArmName']; ?></td>
                                </tr>
                                <tr>
                                    <th>Class Teacher</th>
                                    <td><?php echo $profile['teacherFirstName'] . ' ' . $profile['teacherLastName']; ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td><?php echo date('M d, Y', strtotime($profile['dateCreated'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Subjects -->
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Enrolled Subjects</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Subject Code</th>
                                    <th>Subject Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php                                $subjectsQuery = "SELECT DISTINCT 
                                                  s.subjectName,
                                                  s.subjectCode,
                                                  st.firstName as teacherFirstName,
                                                  st.lastName as teacherLastName
                                                FROM tblsubjectteacher_student sts
                                                INNER JOIN tblsubjectteacher st ON st.Id = sts.subjectTeacherId
                                                INNER JOIN tblsubjects s ON s.Id = st.subjectId
                                                WHERE sts.studentId = ?
                                                ORDER BY s.subjectName";
                                
                                $stmt = $conn->prepare($subjectsQuery);
                                $stmt->bind_param("i", $_SESSION['userId']);
                                $stmt->execute();
                                $subjectsResult = $stmt->get_result();
                                
                                while ($subject = $subjectsResult->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $subject['subjectName']; ?></td>
                                    <td><?php echo $subject['subjectCode']; ?></td>
                                    <td><?php echo $subject['teacherFirstName'] . ' ' . $subject['teacherLastName']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('Includes/footer.php'); ?>
