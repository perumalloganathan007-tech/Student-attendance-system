<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is an Administrator
validate_session('Administrator');

// Save assignments
if(isset($_POST['save'])){
    $subjectTeacherId = $_POST['subjectTeacherId'];
    $students = isset($_POST['students']) ? $_POST['students'] : array();
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete existing assignments for this teacher
        $query = "DELETE FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $subjectTeacherId);
        $stmt->execute();
        
        // Insert new assignments
        if(!empty($students)) {
            $query = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $subjectTeacherId, $studentId);
            
            foreach($students as $studentId) {
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        $statusMsg = "<div class='alert alert-success'>Students assigned successfully!</div>";
    }
    catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $statusMsg = "<div class='alert alert-danger'>An error occurred: " . $e->getMessage() . "</div>";
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
    <link href="../img/logo/attnlg.jpg" rel="icon">
    <title>Dashboard - Assign Students to Subject Teacher</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Assign Students to Subject Teacher</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Assign Students</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Assign Students</h6>
                                    <?php echo $statusMsg; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label class="form-control-label">Select Subject Teacher<span class="text-danger ml-2">*</span></label>
                                            <select required name="subjectTeacherId" class="form-control mb-3" onchange="this.form.submit()">
                                                <option value="">--Select Subject Teacher--</option>
                                                <?php 
                                                $query = "SELECT st.Id, st.firstName, st.lastName, s.subjectName, s.subjectCode 
                                                         FROM tblsubjectteachers st
                                                         INNER JOIN tblsubjects s ON s.Id = st.subjectId
                                                         ORDER BY st.lastName ASC";
                                                $result = $conn->query($query);
                                                while($row = $result->fetch_assoc()){
                                                    $selected = (isset($_POST['subjectTeacherId']) && $_POST['subjectTeacherId'] == $row['Id']) ? 'selected' : '';
                                                    echo "<option value='".$row['Id']."' ".$selected.">".$row['lastName'].", ".$row['firstName']." - ".$row['subjectName']." (".$row['subjectCode'].")</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <?php if(isset($_POST['subjectTeacherId']) && !isset($_POST['save'])): ?>
                                            <div class="table-responsive">
                                                <table class="table align-items-center table-flush table-hover">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Select</th>
                                                            <th>Student Name</th>
                                                            <th>Admission No</th>
                                                            <th>Class</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        // Get currently assigned students
                                                        $assignedStudents = array();
                                                        $query = "SELECT studentId FROM tblsubjectteacher_student WHERE subjectTeacherId = ?";
                                                        $stmt = $conn->prepare($query);
                                                        $stmt->bind_param("i", $_POST['subjectTeacherId']);
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        while($row = $result->fetch_assoc()) {
                                                            $assignedStudents[] = $row['studentId'];
                                                        }
                                                        
                                                        // Get all students
                                                        $query = "SELECT s.*, c.className, ca.classArmName
                                                                 FROM tblstudents s
                                                                 INNER JOIN tblclass c ON c.Id = s.classId
                                                                 INNER JOIN tblclassarms ca ON ca.Id = s.classArmId
                                                                 ORDER BY s.lastName ASC";
                                                        $result = $conn->query($query);
                                                        while($row = $result->fetch_assoc()){
                                                            $checked = in_array($row['Id'], $assignedStudents) ? 'checked' : '';
                                                            echo "<tr>
                                                                    <td><input type='checkbox' name='students[]' value='".$row['Id']."' ".$checked."></td>
                                                                    <td>".$row['lastName'].", ".$row['firstName']." ".$row['otherName']."</td>
                                                                    <td>".$row['admissionNumber']."</td>
                                                                    <td>".$row['className']." ".$row['classArmName']."</td>
                                                                </tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" name="save" class="btn btn-primary mt-3">Save Assignments</button>
                                        <?php endif; ?>
                                    </form>
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
</body>
</html>
