<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../Includes/dbcon.php';

echo "<h1>Assign Students to Subject Teacher</h1>";

// 1. Check if the subject teacher student table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteacher_student'");
if ($tableCheck->num_rows == 0) {
    echo "<p>Error: Subject teacher student table doesn't exist!</p>";
    exit;
}

// 2. List all subject teachers
echo "<h2>Subject Teachers</h2>";
$teachersQuery = "SELECT st.Id, st.firstName, st.lastName, s.subjectName, s.subjectCode 
                FROM tblsubjectteacher st
                INNER JOIN tblsubjects s ON s.Id = st.subjectId";
$teachersResult = $conn->query($teachersQuery);

if ($teachersResult->num_rows == 0) {
    echo "<p>No subject teachers found!</p>";
    exit;
}

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Subject</th><th>Code</th></tr>";
while ($teacher = $teachersResult->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $teacher['Id'] . "</td>";
    echo "<td>" . $teacher['firstName'] . " " . $teacher['lastName'] . "</td>";
    echo "<td>" . $teacher['subjectName'] . "</td>";
    echo "<td>" . $teacher['subjectCode'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Form for assigning students
if (isset($_POST['assign'])) {
    $teacherId = $_POST['teacherId'];
    $studentIds = isset($_POST['students']) ? $_POST['students'] : [];

    if (empty($studentIds)) {
        echo "<p>Error: No students selected!</p>";
    } else {
        // Delete existing assignments if requested
        if (isset($_POST['clearExisting']) && $_POST['clearExisting'] == 1) {
            $conn->query("DELETE FROM tblsubjectteacher_student WHERE subjectTeacherId = $teacherId");
            echo "<p>Cleared existing student assignments</p>";
        }

        // Insert new assignments
        $inserted = 0;
        foreach ($studentIds as $studentId) {
            // Check if assignment already exists
            $checkQuery = "SELECT id FROM tblsubjectteacher_student 
                         WHERE subjectTeacherId = $teacherId AND studentId = $studentId";
            $checkResult = $conn->query($checkQuery);

            if ($checkResult->num_rows == 0) {
                $insertQuery = "INSERT INTO tblsubjectteacher_student (subjectTeacherId, studentId)
                              VALUES ($teacherId, $studentId)";
                if ($conn->query($insertQuery)) {
                    $inserted++;
                }
            }
        }

        echo "<p>Successfully assigned $inserted new students to subject teacher</p>";
    }
}

// Get subject teacher ID
echo "<h2>Assign Students</h2>";
echo "<form method='post'>";
echo "<label for='teacherId'>Select Subject Teacher:</label>";
echo "<select name='teacherId' required>";
$teachersResult->data_seek(0);
while ($teacher = $teachersResult->fetch_assoc()) {
    echo "<option value='" . $teacher['Id'] . "'>" . $teacher['firstName'] . " " . $teacher['lastName'] . " (" . $teacher['subjectName'] . ")</option>";
}
echo "</select>";
echo "<br><br>";

// Option to clear existing
echo "<input type='checkbox' name='clearExisting' value='1'>";
echo "<label for='clearExisting'>Clear existing student assignments</label><br><br>";

// Get students
$studentsQuery = "SELECT s.Id, s.firstName, s.lastName, s.admissionNumber, c.className 
                FROM tblstudents s
                INNER JOIN tblclass c ON c.Id = s.classId
                ORDER BY c.className, s.firstName, s.lastName";
$studentsResult = $conn->query($studentsQuery);

if ($studentsResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Select</th><th>ID</th><th>Name</th><th>Admission No</th><th>Class</th></tr>";

    // Group students by class
    $classes = [];
    while ($student = $studentsResult->fetch_assoc()) {
        $className = $student['className'];
        
        if (!isset($classes[$className])) {
            $classes[$className] = [];
        }
        
        $classes[$className][] = $student;
    }

    // Display students by class
    foreach ($classes as $className => $students) {
        echo "<tr><td colspan='5'><strong>Class: $className</strong></td></tr>";
        
        foreach ($students as $student) {
            echo "<tr>";
            echo "<td><input type='checkbox' name='students[]' value='" . $student['Id'] . "'></td>";
            echo "<td>" . $student['Id'] . "</td>";
            echo "<td>" . $student['firstName'] . " " . $student['lastName'] . "</td>";
            echo "<td>" . $student['admissionNumber'] . "</td>";
            echo "<td>" . $student['className'] . "</td>";
            echo "</tr>";
        }
    }

    echo "</table>";
    echo "<br>";
    echo "<input type='submit' name='assign' value='Assign Selected Students'>";
} else {
    echo "<p>No students found!</p>";
}

echo "</form>";

// 3. List existing assignments
echo "<h2>Current Assignments</h2>";

$assignmentsQuery = "SELECT 
                      st.firstName as teacherFirstName, 
                      st.lastName as teacherLastName,
                      s.subjectName,
                      stu.firstName as studentFirstName,
                      stu.lastName as studentLastName,
                      stu.admissionNumber,
                      c.className,
                      sts.id as assignmentId
                    FROM tblsubjectteacher_student sts
                    INNER JOIN tblsubjectteacher st ON st.Id = sts.subjectTeacherId
                    INNER JOIN tblsubjects s ON s.Id = st.subjectId
                    INNER JOIN tblstudents stu ON stu.Id = sts.studentId
                    INNER JOIN tblclass c ON c.Id = stu.classId
                    ORDER BY st.lastName, s.subjectName, c.className, stu.lastName";

$assignmentsResult = $conn->query($assignmentsQuery);

if ($assignmentsResult->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Teacher</th><th>Subject</th><th>Student</th><th>Admission No</th><th>Class</th></tr>";
    
    while ($assignment = $assignmentsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $assignment['teacherFirstName'] . " " . $assignment['teacherLastName'] . "</td>";
        echo "<td>" . $assignment['subjectName'] . "</td>";
        echo "<td>" . $assignment['studentFirstName'] . " " . $assignment['studentLastName'] . "</td>";
        echo "<td>" . $assignment['admissionNumber'] . "</td>";
        echo "<td>" . $assignment['className'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No students assigned to any subject teacher!</p>";
}

$conn->close();
?>
