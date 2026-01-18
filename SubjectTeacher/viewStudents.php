<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a Subject Teacher
if (!isset($_SESSION['userId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] != 'SubjectTeacher') {
    header("Location: ../subjectTeacherLogin.php");
    exit();
}

// Debug session information
echo "<!-- Debug: User ID = " . $_SESSION['userId'] . ", User Type = " . ($_SESSION['userType'] ?? 'not set') . " -->";

// Get Subject Teacher Information - try multiple approaches
$subjectInfo = null;

// First try with the correct table name
$query = "SELECT s.subjectName, s.subjectCode
          FROM tblsubjectteacher st
          INNER JOIN tblsubjects s ON s.Id = st.subjectId
          WHERE st.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$subjectInfo = $result->fetch_assoc();

// If not found, try to get from session variables
if (!$subjectInfo && isset($_SESSION['subjectName'])) {
    $subjectInfo = [
        'subjectName' => $_SESSION['subjectName'],
        'subjectCode' => $_SESSION['subjectCode'] ?? ''
    ];
}

// If still not found, try to fix the session
if (!$subjectInfo) {
    // Try to get subject info and update session
    $fixQuery = "SELECT st.Id, s.Id as subjectId, s.subjectName, s.subjectCode
                 FROM tblsubjectteacher st
                 INNER JOIN tblsubjects s ON s.Id = st.subjectId
                 WHERE st.Id = ?";
    $fixStmt = $conn->prepare($fixQuery);
    $fixStmt->bind_param("i", $_SESSION['userId']);
    $fixStmt->execute();
    $fixResult = $fixStmt->get_result();
    $teacherData = $fixResult->fetch_assoc();
    
    if ($teacherData) {
        // Update session with subject information
        $_SESSION['subjectId'] = $teacherData['subjectId'];
        $_SESSION['subjectName'] = $teacherData['subjectName'];
        $_SESSION['subjectCode'] = $teacherData['subjectCode'];
        
        $subjectInfo = [
            'subjectName' => $teacherData['subjectName'],
            'subjectCode' => $teacherData['subjectCode']
        ];
    }
}

// If no subject found, show error with helpful links
if (!$subjectInfo) {
    echo "<div style='padding: 20px; color: red; font-family: Arial, sans-serif;'>";
    echo "<h3>Error: Subject information not found for this teacher</h3>";
    echo "<p>User ID: " . $_SESSION['userId'] . "</p>";
    echo "<p>This usually means:</p>";
    echo "<ul>";
    echo "<li>Your teacher account is not assigned to a subject</li>";
    echo "<li>The subject assignment data is missing</li>";
    echo "<li>Database table issues need to be resolved</li>";
    echo "</ul>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<p><a href='fix_view_students.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix Student Assignment Issues</a></p>";
    echo "<p><a href='debug_session.php' style='background: #FF9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Debug Session Information</a></p>";
    echo "<p><a href='index.php' style='background: #607D8B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Return to Dashboard</a></p>";
    echo "</div>";
    exit();
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
    <title>Dashboard - View Students</title>
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
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">All Students (<?php echo $subjectInfo['subjectName'] ?? 'Subject Not Set'; ?>)</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Students</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student List</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                                <th>Admission No</th>
                                                <th>Class</th>
                                                <th>Section</th>
                                            </tr>
                                        </thead>                                        <tbody>
                                            <?php
                                            $query = "SELECT s.*, c.className, ca.classArmName
                                                     FROM tblstudents s
                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                     INNER JOIN tblclass c ON s.classId = c.Id
                                                     INNER JOIN tblclassarms ca ON s.classArmId = ca.Id
                                                     WHERE sts.subjectTeacherId = ?
                                                     ORDER BY s.firstName ASC";
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("i", $_SESSION['userId']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $cnt = 1;
                                            
                                            if ($result->num_rows == 0) {
                                                echo "<tr><td colspan='6' class='text-center'>No students assigned to this subject teacher yet.</td></tr>";
                                            } else {
                                                while ($row = $result->fetch_assoc()) {
                                            ?>
                                                <tr>
                                                    <td><?php echo $cnt; ?></td>
                                                    <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['lastName']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['admissionNumber']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['className']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['classArmName']); ?></td>
                                                </tr>
                                            <?php 
                                                    $cnt++;
                                                } 
                                            }
                                            ?>
                                        </tbody>
                                    </table>
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
