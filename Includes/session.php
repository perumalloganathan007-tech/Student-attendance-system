<?php
require_once 'dbcon.php';

// Simply call validate_session from dbcon.php
// The function is already defined in dbcon.php, so we don't need to redefine it here
if (function_exists('validate_session')) {
    validate_session();
} else {
    // This should never happen as dbcon.php should define this function
    session_start();
    if (!isset($_SESSION['userId'])) {
        header("Location: ../index.php");
        exit();
    }
    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();
}
?>