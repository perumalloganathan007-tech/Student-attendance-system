<?php
/**
 * Initialize session variables for Subject Teacher module
 * This file ensures all necessary session variables are set
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as SubjectTeacher
if (!isset($_SESSION['userId']) || !isset($_SESSION['userType']) || $_SESSION['userType'] != 'SubjectTeacher') {
    header("Location: ../subjectTeacherLogin.php");
    exit();
}

// Get database connection
if (!isset($conn)) {
    include_once('../Includes/dbcon.php');
}

// Get Subject Teacher Information including subject ID
function ensureSubjectTeacherInfo($conn, $teacherId) {
    // Get teacher's subject information
    $query = "SELECT 
                st.Id as teacherId,
                s.Id as subjectId,
                s.subjectName,
                s.subjectCode
              FROM tblsubjectteacher st
              INNER JOIN tblsubjects s ON s.Id = st.subjectId
              WHERE st.Id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacherInfo = $result->fetch_assoc();
    
    // Set session variables if info found
    if ($teacherInfo) {
        $_SESSION['subjectId'] = $teacherInfo['subjectId'];
        $_SESSION['subjectName'] = $teacherInfo['subjectName'];
        $_SESSION['subjectCode'] = $teacherInfo['subjectCode'];
        return true;
    }
    
    return false;
}

// Ensure session variables are set
if (!isset($_SESSION['subjectId']) || !isset($_SESSION['subjectName'])) {
    ensureSubjectTeacherInfo($conn, $_SESSION['userId']);
}

// Ensure subject information is set in the session
if (!isset($_SESSION['subjectId']) || empty($_SESSION['subjectId'])) {
    ensureSubjectTeacherInfo($conn, $_SESSION['userId']);
}
?>
