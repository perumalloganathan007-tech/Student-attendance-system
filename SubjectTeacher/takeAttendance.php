<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

// Initialize session variables
include 'Includes/init_session.php';

/**
 * Check if a column exists in a table
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $column Column name
 * @return bool True if column exists, false otherwise
 */
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0);
}

// Debug information
echo "<div style='position:fixed; bottom:0; right:0; background:white; border:1px solid black; padding:10px; z-index:9999; max-width:300px; font-size:12px;'>";
echo "<strong>Debug Info:</strong><br>";
echo "Date: " . $dateTaken = date("Y-m-d") . "<br>";
echo "User ID: " . ($_SESSION['userId'] ?? 'Not set') . "<br>";
echo "Subject ID: " . ($_SESSION['subjectId'] ?? 'Not set') . "<br>";
echo "Subject Name: " . ($_SESSION['subjectName'] ?? 'Not set') . "<br>";
echo "Subject Code: " . ($_SESSION['subjectCode'] ?? 'Not set') . "<br>";
echo "</div>";

// Validate session
validate_session('SubjectTeacher');

// Ensure subject info is available
if (!isset($_SESSION['subjectId']) || empty($_SESSION['subjectId'])) {
    echo "<div class='alert alert-danger' style='margin: 20px;'>
            <h4>Error: Subject information is missing</h4>
            <p>Please go to the <a href='fix_attendance.php'>Fix Attendance</a> page first to resolve database issues.</p>
            <p>Then go to the <a href='debug_session.php'>Debug Session</a> page to check if your session has all required information.</p>
            <p>Finally, return to the <a href='index.php'>Dashboard</a> and try again.</p>
         </div>";
    include "Includes/footer.php";
    exit();
}

// Get Subject Teacher Information
$query = "SELECT 
    s.Id as subjectId,
    s.subjectName,
    s.subjectCode,
    st.Id as teacherId
FROM tblsubjectteacher st
INNER JOIN tblsubjects s ON s.Id = st.subjectId
WHERE st.Id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$teacherInfo = $result->fetch_assoc();

// Set session variables with the retrieved information
if ($teacherInfo) {
    $_SESSION['subjectId'] = $teacherInfo['subjectId'];
    $_SESSION['subjectName'] = $teacherInfo['subjectName'];
    $_SESSION['subjectCode'] = $teacherInfo['subjectCode'];
}

// Get active session term
$sessionQuery = "SELECT Id FROM tblsessionterm WHERE isActive = 1";
$stmt = $conn->prepare($sessionQuery);
$stmt->execute();
$sessionResult = $stmt->get_result();
$sessionTerm = $sessionResult->fetch_assoc();
$sessionTermId = $sessionTerm['Id'];

$dateTaken = date("Y-m-d");

// Check if attendance has been taken
$checkQuery = "SELECT COUNT(*) as count 
               FROM tblsubjectattendance 
               WHERE subjectTeacherId = ? 
               AND date = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $_SESSION['userId'], $dateTaken);
$stmt->execute();
$countResult = $stmt->get_result();
$count = $countResult->fetch_assoc()['count'];

if($count == 0) { // If attendance has not been taken
    // Get all students in this subject
    $studentQuery = "SELECT studentId 
                    FROM tblsubjectteacher_student 
                    WHERE subjectTeacherId = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $_SESSION['userId']);    $stmt->execute();
    $studentResult = $stmt->get_result();
    
    // Get teacher's subject ID
    $getSubjectQuery = "SELECT subjectId FROM tblsubjectteacher WHERE Id = ?";
    $subjectStmt = $conn->prepare($getSubjectQuery);
    $subjectStmt->bind_param("i", $_SESSION['userId']);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    $subjectInfo = $subjectResult->fetch_assoc();
    $subjectId = $subjectInfo['subjectId'] ?? 0;
    
    // Update session with subject ID
    $_SESSION['subjectId'] = $subjectId;
      // Get columns in tblsubjectattendance
    $columnsQuery = "SHOW COLUMNS FROM tblsubjectattendance";
    $columnsResult = $conn->query($columnsQuery);
    if (!$columnsResult) {
        $_SESSION['error'] = "Error getting table structure: " . $conn->error;
        header("Location: index.php");
        exit;
    }
    
    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
      // Dynamically build INSERT query based on available columns
    $insertFields = ["studentId", "date", "status", "sessionTermId"];
    $insertValues = ["?", "?", "0", "?"];
    $bindTypes = "iss"; // studentId (i), date (s), sessionTermId (s)
    $bindValues = [0, $dateTaken, $sessionTermId]; // Placeholder for studentId, will be replaced in loop
    
    // Add teacherId if column exists
    if (in_array('teacherId', $columns)) {
        $insertFields[] = "teacherId";
        $insertValues[] = "?";
        $bindTypes .= "i";
        $bindValues[] = $_SESSION['userId'];
    }
    
    // Add subjectId if column exists
    if (in_array('subjectId', $columns)) {
        $insertFields[] = "subjectId";
        $insertValues[] = "?";
        $bindTypes .= "i";
        $bindValues[] = $subjectId;
    }
    
    // Add subjectTeacherId if column exists
    if (in_array('subjectTeacherId', $columns)) {
        $insertFields[] = "subjectTeacherId";
        $insertValues[] = "?";
        $bindTypes .= "i";
        $bindValues[] = $_SESSION['userId'];
    }
    
    // Add remarks if column exists
    if (in_array('remarks', $columns)) {
        $insertFields[] = "remarks";
        $insertValues[] = "?";
        $bindTypes .= "s";
        $bindValues[] = "";
    }
    
    // Build final INSERT query
    $insertQuery = "INSERT INTO tblsubjectattendance (" . implode(", ", $insertFields) . ") VALUES (" . implode(", ", $insertValues) . ")";
    $insertStmt = $conn->prepare($insertQuery);
    
    // Insert default attendance records
    while ($student = $studentResult->fetch_assoc()) {
        $currentBindValues = $bindValues;
        $currentBindValues[0] = $student['studentId']; // Update studentId for this record
        
        // Dynamic binding of parameters
        $bindParams = array_merge([$bindTypes], $currentBindValues);
        $insertStmt->bind_param(...$bindParams);
        $insertStmt->execute();
    }
}

// Handle form submission
if(isset($_POST['save'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First set all students as absent for this date
        $updateAbsentQuery = "UPDATE tblsubjectattendance 
                            SET status = 0 
                            WHERE date = ? 
                            AND subjectTeacherId = ?";
        $stmt = $conn->prepare($updateAbsentQuery);
        $stmt->bind_param("si", $dateTaken, $_SESSION['userId']);
        $stmt->execute();
        
        // Then update present students
        if(!empty($_POST['studentIds'])) {
            $updatePresentQuery = "UPDATE tblsubjectattendance 
                                 SET status = 1 
                                 WHERE studentId = ? 
                                 AND date = ? 
                                 AND subjectTeacherId = ?";
            $stmt = $conn->prepare($updatePresentQuery);
            
            foreach($_POST['studentIds'] as $studentId) {
                $stmt->bind_param("isi", $studentId, $dateTaken, $_SESSION['userId']);
                $stmt->execute();
            }
        }        // Check if remarks column exists before processing remarks
        $hasRemarksColumn = columnExists($conn, 'tblsubjectattendance', 'remarks');
        
        // Update remarks for all students if the column exists
        if($hasRemarksColumn && !empty($_POST['remarks'])) {
            $updateRemarksQuery = "UPDATE tblsubjectattendance 
                                  SET remarks = ? 
                                  WHERE studentId = ? 
                                  AND date = ? 
                                  AND subjectTeacherId = ?";
            $stmt = $conn->prepare($updateRemarksQuery);
            
            foreach($_POST['remarks'] as $studentId => $remark) {
                $stmt->bind_param("sisi", $remark, $studentId, $dateTaken, $_SESSION['userId']);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Attendance has been updated successfully!";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error updating attendance: " . $e->getMessage();
    }}

// Handle form submission for individual class
foreach ($_POST as $key => $value) {
    if (strpos($key, 'save_') === 0) {
        $classId = $_POST['classId'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First set all students in this class as absent for this date
            $updateAbsentQuery = "UPDATE tblsubjectattendance sa
                                INNER JOIN tblstudents s ON sa.studentId = s.Id 
                                SET sa.status = 0 
                                WHERE sa.date = ? 
                                AND sa.subjectTeacherId = ?
                                AND s.classId = ?";
            $stmt = $conn->prepare($updateAbsentQuery);
            $stmt->bind_param("sii", $dateTaken, $_SESSION['userId'], $classId);
            $stmt->execute();
            
            // Then update present students
            if(!empty($_POST['studentIds'])) {
                $updatePresentQuery = "UPDATE tblsubjectattendance 
                                     SET status = 1 
                                     WHERE studentId = ? 
                                     AND date = ? 
                                     AND subjectTeacherId = ?";
                $stmt = $conn->prepare($updatePresentQuery);
                
                foreach($_POST['studentIds'] as $studentId) {
                    $stmt->bind_param("isi", $studentId, $dateTaken, $_SESSION['userId']);
                    $stmt->execute();
                }
            }
            
            // Update remarks if column exists and remarks provided
            if(columnExists($conn, 'tblsubjectattendance', 'remarks') && !empty($_POST['remarks'])) {
                $updateRemarksQuery = "UPDATE tblsubjectattendance 
                                     SET remarks = ? 
                                     WHERE studentId = ? 
                                     AND date = ? 
                                     AND subjectTeacherId = ?";
                $stmt = $conn->prepare($updateRemarksQuery);
                
                foreach($_POST['remarks'] as $studentId => $remark) {
                    $stmt->bind_param("sisi", $remark, $studentId, $dateTaken, $_SESSION['userId']);
                    $stmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            $_SESSION['success'] = "Attendance has been updated successfully for " . str_replace("save_", "", $key) . "!";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error'] = "Error updating attendance for " . str_replace("save_", "", $key) . ": " . $e->getMessage();
        }
        
        break; // Only process one class at a time
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Subject Teacher Attendance Management System">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>Take Attendance - <?php echo $teacherInfo['subjectName']; ?></title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    
    <!-- Custom styles for attendance page -->
    <style>
        .attendance-table th, .attendance-table td {
            vertical-align: middle !important;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            border-color: #28a745;
            background-color: #28a745;
        }
        .attendance-summary {
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .attendance-date {
            font-weight: bold;
            color: #4e73df;
        }
        .class-section {
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
            padding: 1rem;
        }
        .class-header {
            margin: -1rem -1rem 1rem -1rem;
            border-top-left-radius: 0.35rem;
            border-top-right-radius: 0.35rem;
            border-bottom: 1px solid #e3e6f0;
            font-weight: bold;
        }
        .class-section .btn-primary {
            min-width: 150px;
        }
        .form-check-inline {
            margin-right: 0;
        }
    </style>
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
                        <h1 class="h3 mb-0 text-gray-800">
                            Take Attendance
                            <small class="text-muted">
                                (<?php echo $teacherInfo['subjectName'] . ' - ' . $teacherInfo['subjectCode']; ?>)
                            </small>
                        </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Take Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Attendance taking card -->
                            <div class="card mb-4">
                                <div class="card-header py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="m-0 font-weight-bold text-primary">
                                                Attendance for <span class="attendance-date"><?php echo date('l, F j, Y', strtotime($dateTaken)); ?></span>
                                            </h6>
                                        </div>
                                        <div class="text-right">
                                            <button type="button" class="btn btn-sm btn-success" id="checkAll">Select All</button>
                                            <button type="button" class="btn btn-sm btn-danger" id="uncheckAll">Unselect All</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($_SESSION['success'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?php 
                                                echo $_SESSION['success']; 
                                                unset($_SESSION['success']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if(isset($_SESSION['error'])): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <?php 
                                                echo $_SESSION['error']; 
                                                unset($_SESSION['error']);
                                            ?>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>                                    <form method="post" id="attendanceForm">
                                        <div class="table-responsive">
                                            <?php
                                            // Check if remarks column exists in tblsubjectattendance
                                            $hasRemarksColumn = columnExists($conn, 'tblsubjectattendance', 'remarks');
                                            
                                            // Get all students grouped by class
                                            $query = "SELECT 
                                                        s.Id,
                                                        s.firstName,
                                                        s.lastName,
                                                        s.admissionNumber,
                                                        c.Id as classId,
                                                        c.className,
                                                        sa.status,
                                                        sa.remarks
                                                     FROM tblstudents s
                                                     INNER JOIN tblsubjectteacher_student sts ON s.Id = sts.studentId
                                                     INNER JOIN tblclass c ON s.classId = c.Id
                                                     LEFT JOIN tblsubjectattendance sa ON s.Id = sa.studentId 
                                                        AND sa.date = ?
                                                        AND sa.subjectTeacherId = ?
                                                     WHERE sts.subjectTeacherId = ?
                                                     ORDER BY c.className, s.lastName ASC, s.firstName ASC";

                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("sii", $dateTaken, $_SESSION['userId'], $_SESSION['userId']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            
                                            if ($result->num_rows == 0) {
                                                echo '<div class="alert alert-warning">No students found. Please assign students to this subject first.</div>';
                                            } else {
                                                // Group students by class
                                                $currentClass = '';
                                                $cnt = 1;

                                                while ($row = $result->fetch_assoc()) {
                                                    // Start new class section
                                                    if ($currentClass != $row['className']) {
                                                        if ($currentClass != '') {
                                                            // Close previous class form
                                                            echo '</tbody></table>';
                                                            echo '<button type="submit" name="save_'.htmlspecialchars($currentClass).'" class="btn btn-primary mt-3 mb-4">Save '.htmlspecialchars($currentClass).' Attendance</button>';
                                                            echo '</form></div>';
                                                        }
                                                        $currentClass = $row['className'];
                                                        // Start new class section
                                                        echo '<div class="class-section mb-4">';
                                                        echo '<h5 class="class-header bg-light p-2 rounded">Class: '.htmlspecialchars($currentClass).'</h5>';
                                                        echo '<form method="post" class="attendance-form">';
                                                        echo '<input type="hidden" name="classId" value="'.htmlspecialchars($row['classId']).'">';
                                                        echo '<table class="table align-items-center table-flush attendance-table">';
                                                        echo '<thead class="thead-light"><tr>
                                                                <th width="5%">#</th>
                                                                <th width="20%">Student Name</th>
                                                                <th width="15%">Admission No.</th>
                                                                <th width="15%">Class</th>
                                                                <th width="15%">Attendance</th>
                                                                <th width="30%">Remarks</th>
                                                            </tr></thead><tbody>';
                                                    }                                                    echo '<tr>';
                                                    echo '<td>'.$cnt.'</td>';
                                                    echo '<td>'.htmlspecialchars(str_replace('.', ' ', $row['firstName']) . ' ' . str_replace('.', ' ', $row['lastName'])).'</td>';
                                                    echo '<td>'.htmlspecialchars($row['admissionNumber']).'</td>';
                                                    echo '<td>'.htmlspecialchars($row['className']).'</td>';
                                                    echo '<td>';
                                                    echo '<div class="form-check form-check-inline">';
                                                    echo '<input type="checkbox" name="studentIds[]" value="'.$row['Id'].'" class="form-check-input attendance-check" 
                                                            '.($row['status'] == 1 ? 'checked' : '').'>';
                                                    echo '<label class="form-check-label">Present</label>';
                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '<td>';
                                                    if ($hasRemarksColumn) {
                                                        echo '<input type="text" name="remarks['.$row['Id'].']" class="form-control form-control-sm" 
                                                                value="'.(isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '').'">';
                                                    }
                                                    echo '</td>';
                                                    echo '</tr>';
                                                    $cnt++;
                                                }
                                                // Close last class form
                                                if ($currentClass != '') {
                                                    echo '</tbody></table>';
                                                    echo '<button type="submit" name="save_'.htmlspecialchars($currentClass).'" class="btn btn-primary mt-3 mb-4">Save '.htmlspecialchars($currentClass).' Attendance</button>';
                                                    echo '</form></div>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!---Container Fluid-->
            </div>
            <!-- Content ends -->
            
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
    
    <!-- Custom Script for Attendance Page -->
    <script>
    $(document).ready(function() {
        // Handle Select All button
        $('#checkAll').click(function() {
            $('.attendance-check').prop('checked', true);
        });
        
        // Handle Unselect All button
        $('#uncheckAll').click(function() {
            $('.attendance-check').prop('checked', false);
        });
        
        // Auto-hide alerts after 5 seconds
        $('.alert').delay(5000).fadeOut(500);
        
        // Confirm before submitting
        $('.attendance-form').submit(function(e) {
            if (!confirm('Are you sure you want to save the attendance?')) {
                e.preventDefault();
            }
        });
        
        // Show/hide present/absent text based on checkbox state
        $('.attendance-check').each(function() {
            updateLabel($(this));
        });
        
        $('.attendance-check').change(function() {
            updateLabel($(this));
        });
        
        function updateLabel(checkbox) {
            var label = checkbox.next('label');
            if (checkbox.is(':checked')) {
                label.find('.present-text').show();
                label.find('.absent-text').hide();
            } else {
                label.find('.present-text').hide();
                label.find('.absent-text').show();
            }
        }
    });
    </script>
</body>
</html>
