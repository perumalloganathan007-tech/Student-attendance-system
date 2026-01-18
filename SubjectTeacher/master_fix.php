<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Master Fix for Subject Teacher Login</h1>";

// 1. Copy CSS file if missing
echo "<h2>Checking CSS Files</h2>";
if (!file_exists('css/ruang-admin.min.css')) {
    if (!is_dir('css')) {
        mkdir('css', 0755, true);
        echo "Created CSS directory<br>";
    }
    
    if (copy('../css/ruang-admin.min.css', 'css/ruang-admin.min.css')) {
        echo "✓ Copied CSS file<br>";
    } else {
        echo "✗ Failed to copy CSS file<br>";
    }
} else {
    echo "✓ CSS file already exists<br>";
}

// 2. Copy JS file if missing
echo "<h2>Checking JS Files</h2>";
if (!file_exists('js/ruang-admin.min.js')) {
    if (!is_dir('js')) {
        mkdir('js', 0755, true);
        echo "Created JS directory<br>";
    }
    
    if (copy('../js/ruang-admin.min.js', 'js/ruang-admin.min.js')) {
        echo "✓ Copied JS file<br>";
    } else {
        echo "✗ Failed to copy JS file<br>";
    }
} else {
    echo "✓ JS file already exists<br>";
}

// 3. Copy images if missing
echo "<h2>Checking Images</h2>";
if (!file_exists('img/user-icn.png')) {
    if (!is_dir('img')) {
        mkdir('img', 0755, true);
        echo "Created img directory<br>";
    }
    
    if (copy('../img/user-icn.png', 'img/user-icn.png')) {
        echo "✓ Copied user icon<br>";
    } else {
        echo "✗ Failed to copy user icon<br>";
    }
}

if (!file_exists('img/logo/attnlg.jpg')) {
    if (!is_dir('img/logo')) {
        mkdir('img/logo', 0755, true);
        echo "Created img/logo directory<br>";
    }
    
    if (copy('../img/logo/attnlg.jpg', 'img/logo/attnlg.jpg')) {
        echo "✓ Copied logo image<br>";
    } else {
        echo "✗ Failed to copy logo image<br>";
    }
}

// 4. Create footer.php if missing
echo "<h2>Checking Include Files</h2>";
if (!file_exists('Includes/footer.php')) {
    if (!is_dir('Includes')) {
        mkdir('Includes', 0755, true);
        echo "Created Includes directory<br>";
    }
    
    $footer = <<<'EOD'
<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>&copy; <script> document.write(new Date().getFullYear()); </script> - Student Attendance System</span>
        </div>
    </div>
</footer>
EOD;

    if (file_put_contents('Includes/footer.php', $footer)) {
        echo "✓ Created footer.php<br>";
    } else {
        echo "✗ Failed to create footer.php<br>";
    }
} else {
    echo "✓ footer.php already exists<br>";
}

// 5. Connect to database and fix tables
echo "<h2>Database Connection</h2>";
include '../Includes/dbcon.php';
echo "Connected to database<br>";

// 6. Check tables
echo "<h2>Check Required Tables</h2>";
$requiredTables = [
    'tblsubjects',
    'tblsubjectteachers',
    'tblsubjectteacher_student',
    'tblsubjectattendance'
];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists<br>";
    } else {
        echo "✗ Table '$table' does not exist<br>";
        
        // Create missing tables
        if ($table == 'tblsubjectteacher_student') {
            $createTable = "CREATE TABLE `tblsubjectteacher_student` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `subjectTeacherId` int(11) NOT NULL,
                `studentId` int(11) NOT NULL,
                `dateAssigned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `subjectTeacherId` (`subjectTeacherId`),
                KEY `studentId` (`studentId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if ($conn->query($createTable)) {
                echo "✓ Created table $table<br>";
            } else {
                echo "✗ Error creating table: " . $conn->error . "<br>";
            }
        } else if ($table == 'tblsubjectattendance') {
            $createTable = "CREATE TABLE `tblsubjectattendance` (
                `Id` int(11) NOT NULL AUTO_INCREMENT,
                `subjectTeacherId` int(11) NOT NULL,
                `studentId` int(11) NOT NULL,
                `status` tinyint(1) NOT NULL,
                `date` date NOT NULL,
                `sessionTermId` int(11) NOT NULL,
                `dateTimeTaken` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`Id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            if ($conn->query($createTable)) {
                echo "✓ Created table $table<br>";
            } else {
                echo "✗ Error creating table: " . $conn->error . "<br>";
            }
        }
    }
}

// 7. Ensure Subject Teachers have a Subject assigned
echo "<h2>Checking Teacher-Subject Assignments</h2>";
$teacherQuery = "SELECT Id, firstName, lastName, subjectId FROM tblsubjectteachers";
$teacherResult = $conn->query($teacherQuery);

if ($teacherResult->num_rows > 0) {
    $needsFix = false;
    
    while ($teacher = $teacherResult->fetch_assoc()) {
        echo "Teacher: " . $teacher['firstName'] . " " . $teacher['lastName'] . " - Subject ID: " . $teacher['subjectId'] . "<br>";
        
        if (!$teacher['subjectId']) {
            $needsFix = true;
            
            // Get an available subject
            $subjectQuery = "SELECT Id FROM tblsubjects ORDER BY RAND() LIMIT 1";
            $subjectResult = $conn->query($subjectQuery);
            
            if ($subjectResult->num_rows > 0) {
                $subject = $subjectResult->fetch_assoc();
                
                // Assign subject to teacher
                $updateQuery = "UPDATE tblsubjectteachers SET subjectId = ? WHERE Id = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ii", $subject['Id'], $teacher['Id']);
                
                if ($updateStmt->execute()) {
                    echo "✓ Assigned subject ID " . $subject['Id'] . " to teacher ID " . $teacher['Id'] . "<br>";
                } else {
                    echo "✗ Failed to update teacher: " . $conn->error . "<br>";
                }
            } else {
                echo "✗ No subjects available to assign<br>";
            }
        }
    }
    
    if (!$needsFix) {
        echo "✓ All teachers have subjects assigned<br>";
    }
} else {
    echo "No subject teachers found in database<br>";
}

// 8. Assign Students to Subject Teachers
echo "<h2>Checking Student Assignments</h2>";
$checkAssignments = $conn->query("SELECT COUNT(*) as count FROM tblsubjectteacher_student");

if ($checkAssignments) {
    $result = $checkAssignments->fetch_assoc();
    
    if ($result['count'] == 0) {
        echo "No student assignments found. Adding sample assignments...<br>";
        
        // Get subject teachers
        $teacherQuery = "SELECT Id, subjectId FROM tblsubjectteachers";
        $teacherResult = $conn->query($teacherQuery);
        
        if ($teacherResult->num_rows > 0) {
            // Get students
            $studentQuery = "SELECT Id FROM tblstudents";
            $studentResult = $conn->query($studentQuery);
            
            if ($studentResult->num_rows > 0) {
                $students = [];
                while ($student = $studentResult->fetch_assoc()) {
                    $students[] = $student['Id'];
                }
                
                $inserted = 0;
                while ($teacher = $teacherResult->fetch_assoc()) {
                    // Assign 5-10 random students to each teacher
                    $numToAssign = min(rand(5, 10), count($students));
                    
                    for ($i = 0; $i < $numToAssign; $i++) {
                        if (count($students) > 0) {
                            $randomIndex = array_rand($students);
                            $studentId = $students[$randomIndex];
                            
                            $insertQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId) 
                                          VALUES (?, ?)";
                            $insertStmt = $conn->prepare($insertQuery);
                            $insertStmt->bind_param("ii", $teacher['Id'], $studentId);
                            
                            if ($insertStmt->execute()) {
                                $inserted++;
                                // Remove this student from array
                                array_splice($students, $randomIndex, 1);
                            }
                        }
                    }
                }
                
                echo "✓ Assigned $inserted students to subject teachers<br>";
            } else {
                echo "No students found in database<br>";
            }
        } else {
            echo "No subject teachers found in database<br>";
        }
    } else {
        echo "✓ Students already assigned to subject teachers (count: {$result['count']})<br>";
    }
} else {
    echo "✗ Error checking student assignments: " . $conn->error . "<br>";
}

// 9. Create TakeAttendance.php if missing
echo "<h2>Creating Missing Pages</h2>";
if (!file_exists('takeAttendance.php')) {
    $takeAttendance = <<<'EOD'
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Check if the form is submitted
if (isset($_POST['save'])) {
    $sessionTermId = $_POST['sessionTermId'];
    $subjectId = $_SESSION['subjectId'];
    $subjectTeacherId = $_SESSION['userId'];
    $date = $_POST['date'];
    $students = $_POST['students'];
    $statuses = $_POST['status'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        for ($i = 0; $i < count($students); $i++) {
            $studentId = $students[$i];
            $status = $statuses[$i];
            
            // Check if attendance record already exists for this date/student/subject
            $checkQuery = "SELECT * FROM tblsubjectattendance 
                         WHERE studentId = ? 
                         AND subjectTeacherId = ? 
                         AND date = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("iis", $studentId, $subjectTeacherId, $date);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing record
                $updateQuery = "UPDATE tblsubjectattendance 
                              SET status = ?, 
                                  dateTimeTaken = CURRENT_TIMESTAMP 
                              WHERE studentId = ? 
                              AND subjectTeacherId = ? 
                              AND date = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("iiis", $status, $studentId, $subjectTeacherId, $date);
                $updateStmt->execute();
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO tblsubjectattendance (subjectTeacherId, studentId, status, date, sessionTermId) 
                             VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("iiisi", $subjectTeacherId, $studentId, $status, $date, $sessionTermId);
                $insertStmt->execute();
            }
        }
        
        // Commit the transaction
        $conn->commit();
        $statusMsg = "<div class='alert alert-success'>Attendance taken successfully</div>";
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        $statusMsg = "<div class='alert alert-danger'>Error taking attendance: " . $e->getMessage() . "</div>";
    }
}

// Get active session/term
$sessionQuery = "SELECT tblsessionterm.Id, tblsessionterm.sessionName, tblterm.termName
               FROM tblsessionterm
               INNER JOIN tblterm ON tblterm.sessionTermId = tblsessionterm.Id
               WHERE tblsessionterm.isActive = 1";
$sessionResult = $conn->query($sessionQuery);
$session = $sessionResult->fetch_assoc();

// Get students assigned to this subject teacher
$studentQuery = "SELECT s.Id, s.firstName, s.lastName, s.admissionNumber, s.classId, s.classArmId
               FROM tblstudents s
               INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id
               WHERE sts.subjectTeacherId = ?
               ORDER BY s.firstName, s.lastName";
$studentStmt = $conn->prepare($studentQuery);
$studentStmt->bind_param("i", $_SESSION['userId']);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
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
    <title>Take Attendance</title>
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
                        <h1 class="h3 mb-0 text-gray-800">Take Attendance (<?php echo $_SESSION['subjectName']; ?>)</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Take Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Take Attendance</h6>
                                    <?php echo isset($statusMsg) ? $statusMsg : ''; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Session & Term<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" name="sessionTerm" value="<?php echo $session['sessionName'].' - '.$session['termName']; ?>" readonly>
                                                <input type="hidden" name="sessionTermId" value="<?php echo $session['Id']; ?>">
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Date<span class="text-danger ml-2">*</span></label>
                                                <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                                            </div>
                                        </div>

                                        <?php if($studentResult->num_rows > 0) { ?>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>S/N</th>
                                                            <th>Admission No</th>
                                                            <th>Student Name</th>
                                                            <th>Present</th>
                                                            <th>Absent</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $sn = 0;
                                                        while ($student = $studentResult->fetch_assoc()) {
                                                            $sn++;
                                                        ?>
                                                            <tr>
                                                                <td><?php echo $sn; ?></td>
                                                                <td><?php echo $student['admissionNumber']; ?></td>
                                                                <td><?php echo $student['firstName'].' '.$student['lastName']; ?></td>
                                                                <td>
                                                                    <input type="hidden" name="students[]" value="<?php echo $student['Id']; ?>">
                                                                    <input type="radio" name="status[<?php echo $sn-1; ?>]" value="1" required> Present
                                                                </td>
                                                                <td>
                                                                    <input type="radio" name="status[<?php echo $sn-1; ?>]" value="0" required> Absent
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" name="save" class="btn btn-primary">Save Attendance</button>
                                        <?php } else { ?>
                                            <div class="alert alert-warning">No students assigned to this subject teacher. Please contact the admin to assign students.</div>
                                        <?php } ?>
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
EOD;

    if (file_put_contents('takeAttendance.php', $takeAttendance)) {
        echo "✓ Created takeAttendance.php<br>";
    } else {
        echo "✗ Failed to create takeAttendance.php<br>";
    }
} else {
    echo "✓ takeAttendance.php already exists<br>";
}

// 10. Summary
echo "<h2>Fix Complete</h2>";
echo "<p>All fixes have been applied. You can now:</p>";
echo "<ul>";
echo "<li><a href='index.php'>Go to the Subject Teacher Dashboard</a></li>";
echo "<li><a href='debug.php'>Run the debugging diagnostics</a></li>";
echo "</ul>";

$conn->close();
?>
