<?php
// Server Status Checker
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Server Status Checker</h1>";

// Check if the server is running
echo "<h2>Web Server Status</h2>";
echo "Current script path: " . __FILE__ . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not available') . "<br>";

// Check MySQL connection
echo "<h2>Database Connection Status</h2>";
try {
    // Try to connect to the database
    if (file_exists('../Includes/dbcon.php')) {
        // Use the existing connection file
        include '../Includes/dbcon.php';
        echo "Loaded database configuration from ../Includes/dbcon.php<br>";
        
        if (isset($conn) && $conn instanceof mysqli) {
            echo "<p style='color: green;'>✓ Successfully connected to MySQL database!</p>";
            echo "MySQL Server Info: " . $conn->server_info . "<br>";
            echo "Connection character set: " . $conn->character_set_name() . "<br>";
            
            // Test query
            $result = $conn->query("SELECT VERSION() as version");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "MySQL Version: " . $row['version'] . "<br>";
            }
        } else {
            echo "<p style='color: red;'>✗ Database connection variable not available!</p>";
        }
    } else {
        // Manual connection attempt
        echo "Database configuration file not found, trying direct connection.<br>";
        
        // Default XAMPP credentials        $host = 'localhost';
        $user = 'root';
        $password = '';
        $database = 'attendancesystem';
        
        // Try to connect
        $conn = new mysqli($host, $user, $password, $database);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        echo "<p style='color: green;'>✓ Successfully connected to MySQL database using default credentials!</p>";
        echo "MySQL Server Info: " . $conn->server_info . "<br>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Failed to connect to MySQL: " . $e->getMessage() . "</p>";
    echo "<p>This usually means that the MySQL service is not running.</p>";
}

// Check XAMPP Control Panel status
echo "<h2>XAMPP Status Check</h2>";
if (strpos(strtolower($_SERVER['SERVER_SOFTWARE'] ?? ''), 'apache') !== false) {
    echo "<p style='color: green;'>✓ Apache appears to be running</p>";
} else {
    echo "<p style='color: orange;'>? Could not confirm Apache status from server variables</p>";
}

// Check if we can access localhost
echo "<h2>Network Connectivity Check</h2>";
function check_port($host, $port) {
    $connection = @fsockopen($host, $port);
    if (is_resource($connection)) {
        echo "<p style='color: green;'>✓ Successfully connected to $host:$port</p>";
        fclose($connection);
        return true;
    } else {
        echo "<p style='color: red;'>✗ Could not connect to $host:$port</p>";
        return false;
    }
}

// Check common XAMPP ports
check_port('localhost', 80);   // Apache
check_port('localhost', 3306); // MySQL

// Additional information
echo "<h2>System Information</h2>";
echo "Server time: " . date('Y-m-d H:i:s') . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "OS: " . PHP_OS . "<br>";

// Navigation links
echo "<h2>Navigation</h2>";
echo "<ul>";
echo "<li><a href='../index.php'>Main Page</a></li>";
echo "<li><a href='index.php'>Subject Teacher Dashboard</a></li>";
echo "<li><a href='fix_session.php'>Fix Session Issues</a></li>";
echo "<li><a href='attendanceAnalytics.php'>Try Analytics Dashboard Again</a></li>";
echo "</ul>";

echo "<h2>Recommended Actions</h2>";
echo "<ol>";
echo "<li>Check if XAMPP Control Panel is running</li>";
echo "<li>Ensure that Apache and MySQL services are started in XAMPP Control Panel</li>";
echo "<li>Try restarting both services if they're not responding</li>";
echo "<li>Check for any permissions issues with the files</li>";
echo "</ol>";
?>
