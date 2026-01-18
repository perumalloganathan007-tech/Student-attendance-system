<?php
// Simple test file to verify server access
echo "<h1>Server Test - PHP Working</h1>";
echo "<p>Current date and time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Server path: " . __FILE__ . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p>Script filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not set') . "</p>";
echo "<h2>Session Information:</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
