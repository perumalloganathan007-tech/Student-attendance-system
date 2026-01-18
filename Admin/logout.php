<?php
require_once '../Includes/dbcon.php';

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect using header instead of JavaScript
header("Location: ../index.php");
exit();

