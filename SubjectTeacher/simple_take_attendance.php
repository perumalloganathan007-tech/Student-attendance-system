<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Simple session validation
if (!isset($_SESSION['userId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] !== 'SubjectTeacher') {
    header("Location: ../subjectTeacherLogin.php");
    exit();
}

$dateTaken = date("Y-m-d");
$message = '';
$messageType = '';

// Get Subject Teacher Information
$teacherQuery = "SELECT st.Id, st.firstName, st.lastName, s.Id as subjectId, s.subjectName, s.subjectCode
                FROM tblsubjectteacher st
                INNER JOIN tblsubjects s ON s.Id = st.subjectId
                WHERE st.Id = ?";
$stmt = $conn->prepare($teacherQuery);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$teacherResult = $stmt->get_result();
$teacherInfo = $teacherResult->fetch_assoc();

if (!$teacherInfo) {
    die("Error: Could not find subject teacher information.");
}

// Update session with subject info
$_SESSION['subjectId'] = $teacherInfo['subjectId'];
$_SESSION['subjectName'] = $teacherInfo['subjectName'];
$_SESSION['subjectCode'] = $teacherInfo['subjectCode'];

// Get active session term
$sessionQuery = "SELECT Id FROM tblsessionterm WHERE isActive = 1 LIMIT 1";
$sessionResult = $conn->query($sessionQuery);
$sessionTerm = $sessionResult->fetch_assoc();
$sessionTermId = $sessionTerm['Id'] ?? 1;

// Handle form submission
if (isset($_POST['save'])) {
    try {
        $conn->begin_transaction();
        
        // Delete existing attendance for today
        $deleteQuery = "DELETE FROM tblsubjectattendance WHERE subjectTeacherId = ? AND date = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("is", $_SESSION['userId'], $dateTaken);
        $stmt->execute();
        
        // Insert new attendance records
        $insertQuery = "INSERT INTO tblsubjectattendance (studentId, subjectTeacherId, subjectId, sessionTermId, status, date) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        
        $presentStudents = $_POST['attendance'] ?? [];
        
        // Get all students for this subject teacher
        $studentQuery = "SELECT s.Id, s.firstName, s.lastName 
                        FROM tblstudents s 
                        INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id 
                        WHERE sts.subjectTeacherId = ?";
        $studentStmt = $conn->prepare($studentQuery);
        $studentStmt->bind_param("i", $_SESSION['userId']);
        $studentStmt->execute();
        $studentResult = $studentStmt->get_result();
        
        while ($student = $studentResult->fetch_assoc()) {
            $status = in_array($student['Id'], $presentStudents) ? 1 : 0;
            $stmt->bind_param("iiiiss", $student['Id'], $_SESSION['userId'], $teacherInfo['subjectId'], $sessionTermId, $status, $dateTaken);
            $stmt->execute();
        }
        
        $conn->commit();
        $message = "Attendance saved successfully for " . date('d M Y', strtotime($dateTaken));
        $messageType = 'success';
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error saving attendance: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Check if attendance already taken today
$checkQuery = "SELECT COUNT(*) as count FROM tblsubjectattendance WHERE subjectTeacherId = ? AND date = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("is", $_SESSION['userId'], $dateTaken);
$stmt->execute();
$checkResult = $stmt->get_result();
$attendanceExists = $checkResult->fetch_assoc()['count'] > 0;

// Get students for this subject teacher
$studentQuery = "SELECT s.Id, s.admissionNumber, s.firstName, s.lastName, 
                        COALESCE(sa.status, 0) as status
                FROM tblstudents s 
                INNER JOIN tblsubjectteacher_student sts ON sts.studentId = s.Id 
                LEFT JOIN tblsubjectattendance sa ON sa.studentId = s.Id AND sa.date = ? AND sa.subjectTeacherId = ?
                WHERE sts.subjectTeacherId = ?
                ORDER BY s.firstName, s.lastName";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("sii", $dateTaken, $_SESSION['userId'], $_SESSION['userId']);
$stmt->execute();
$studentsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Take Attendance - Subject Teacher</title>
    <link href="img/logo/attnlg.jpg" rel="icon">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">    <style>
        .student-row {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .student-row:hover {
            background-color: #f8f9fa;
        }
        .student-row.present {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .student-row.absent {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .attendance-checkbox {
            transform: scale(1.5);
            margin-right: 10px;
            cursor: pointer;
        }
        .badge {
            transition: all 0.3s ease;
        }
    </style>
</head>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all rows' styles
    document.querySelectorAll('.attendance-checkbox').forEach(checkbox => {
        updateRowStyle(checkbox);
    });
});

function markAllPresent() {
    const checkboxes = document.querySelectorAll('.attendance-checkbox');
    checkboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.checked = true;
            updateRowStyle(checkbox);
            formChanged = true;
        }
    });
}

function markAllAbsent() {
    const checkboxes = document.querySelectorAll('.attendance-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkbox.checked = false;
            updateRowStyle(checkbox);
            formChanged = true;
        }
    });
}

function updateRowStyle(checkbox) {
    if (!checkbox) return;
    
    const row = checkbox.closest('.student-row');
    const statusBadge = row.querySelector('.badge');
    
    if (checkbox.checked) {
        row.classList.remove('absent');
        row.classList.add('present');
        statusBadge.classList.remove('badge-danger');
        statusBadge.classList.add('badge-success');
        statusBadge.textContent = 'Present';
    } else {
        row.classList.remove('present');
        row.classList.add('absent');
        statusBadge.classList.remove('badge-success');
        statusBadge.classList.add('badge-danger');
        statusBadge.textContent = 'Absent';
    }
}
</script>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Take Attendance</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Take Attendance</li>
                        </ol>
                    </div>

                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Attendance for <?php echo htmlspecialchars($teacherInfo['subjectName']); ?>
                                        <span class="text-muted">(<?php echo date('d M Y', strtotime($dateTaken)); ?>)</span>
                                    </h6>
                                    <?php if ($attendanceExists): ?>
                                    <small class="text-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Attendance already taken today. Saving will update existing records.
                                    </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <?php if ($studentsResult->num_rows > 0): ?>
                                    <form method="POST" action="">
                                        <div class="form-group">                                            <button type="button" class="btn btn-success btn-sm" onclick="markAllPresent()" title="Mark all students as present">
                                                <i class="fas fa-check"></i> Mark All Present
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="markAllAbsent()" title="Mark all students as absent">
                                                <i class="fas fa-times"></i> Mark All Absent
                                            </button>
                                        </div>
                                        
                                        <div id="student-list">
                                            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                                            <div class="student-row <?php echo $student['status'] ? 'present' : 'absent'; ?>" id="student-<?php echo $student['Id']; ?>">
                                                <div class="row align-items-center">
                                                    <div class="col-md-1">
                                                        <input type="checkbox" 
                                                               class="attendance-checkbox" 
                                                               name="attendance[]" 
                                                               value="<?php echo $student['Id']; ?>"
                                                               <?php echo $student['status'] ? 'checked' : ''; ?>
                                                               onchange="updateRowStyle(this)">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <strong><?php echo htmlspecialchars($student['admissionNumber']); ?></strong>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <span class="student-name">
                                                            <?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?>
                                                        </span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <span class="badge badge-pill <?php echo $student['status'] ? 'badge-success' : 'badge-danger'; ?>">
                                                            <?php echo $student['status'] ? 'Present' : 'Absent'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                        
                                        <div class="form-group mt-4">
                                            <button type="submit" name="save" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Save Attendance
                                            </button>
                                            <a href="index.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                                            </a>
                                            <a href="viewTodayAttendance.php" class="btn btn-info">
                                                <i class="fas fa-eye"></i> View Today's Attendance
                                            </a>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                    <div class="alert alert-warning">
                                        <h5><i class="fas fa-exclamation-triangle"></i> No Students Found</h5>
                                        <p>No students are currently assigned to your subject. Please contact the administrator to assign students to your subject.</p>
                                        <a href="debug_attendance.php" class="btn btn-info">Check Debug Information</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    
    <script>
        // Auto-save warning
        let formChanged = false;
        document.querySelectorAll('.attendance-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Reset form changed flag when saving
        document.querySelector('form').addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>
