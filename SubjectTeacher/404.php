<?php
// Set error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a file exists (case-insensitive)
function file_exists_ci($filepath) {
    if (file_exists($filepath)) {
        return $filepath;
    }
    
    $dirname = dirname($filepath);
    $filename = basename($filepath);
    
    if (!is_dir($dirname)) {
        return false;
    }
    
    $filenames = scandir($dirname);
    foreach ($filenames as $fn) {
        if (strtolower($fn) === strtolower($filename)) {
            return $dirname . DIRECTORY_SEPARATOR . $fn;
        }
    }
    
    return false;
}

// Get the requested URI path
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_file = basename($request_path);
$physical_path = __DIR__ . '/' . $request_file;

// Debug information - will be shown only if debugging
$show_debug = true; // Set to false in production

// Check if we're looking for a specific file
if ($request_file === 'viewTodayAttendance.php' || strtolower($request_file) === 'viewtodayattendance.php') {
    // File we're looking for
    $target_file = 'viewTodayAttendance.php';
    $actual_file = file_exists_ci(__DIR__ . '/' . $target_file);
    
    if ($actual_file) {
        // Construct query string if present
        $query_string = '';
        if(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            $query_string = '?' . $_SERVER['QUERY_STRING'];
        }
        
        // Check if the session variables needed are set
        if (!isset($_SESSION['userId']) || !isset($_SESSION['user_type'])) {
            // Redirect to the session fix tool
            header("Location: fix_session.php");
            exit();
        }
        
        // If file exists but isn't being found, try including it directly
        if ($request_file !== $target_file) { // If it's the wrong case
            header("Location: $target_file$query_string");
            exit();
        }
    }
}

// Generate the HTML response
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .error-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-top: 30px;
        }
        h1 {
            color: #dc3545;
        }
        .debug-info {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            border-radius: 4px;
            padding: 15px;
            margin-top: 30px;
            font-family: monospace;
            font-size: 14px;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #4e73df;
        }
        .fix-button {
            display: inline-block;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .fix-button:hover {
            background-color: #2e59d9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Page Not Found</h1>
        <p>The page you are looking for might have been removed or is temporarily unavailable.</p>
        <p>Please go back to the <a href="index.php">dashboard</a> and try again.</p>
        
        <?php if ($request_file === 'viewTodayAttendance.php' || strtolower($request_file) === 'viewtodayattendance.php'): ?>
        <h2>Looking for Today's Attendance?</h2>
        <p>It appears you're trying to access the Today's Attendance page. Here are some options to fix this issue:</p>
        <ul>
            <li>Check if your session is properly initialized: <a href="fix_session.php" class="fix-button">Fix Session Issues</a></li>
            <li>Try accessing the page directly: <a href="viewTodayAttendance.php" class="fix-button">View Today's Attendance</a></li>
            <li>Go back to the dashboard: <a href="index.php" class="fix-button">Dashboard</a></li>
        </ul>
        <?php endif; ?>
    </div>
    
    <?php if ($show_debug): ?>
    <div class="debug-info">
        <h3>Debug Information</h3>
        <table>
            <tr>
                <th>Information</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Requested URI</td>
                <td><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></td>
            </tr>
            <tr>
                <td>Request File</td>
                <td><?php echo htmlspecialchars($request_file); ?></td>
            </tr>
            <tr>
                <td>Physical Path</td>
                <td><?php echo htmlspecialchars($physical_path); ?></td>
            </tr>
            <tr>
                <td>File Exists</td>
                <td><?php echo file_exists($physical_path) ? 'Yes' : 'No'; ?></td>
            </tr>
            <tr>
                <td>Case-Insensitive Check</td>
                <td><?php echo file_exists_ci($physical_path) ? 'Found: ' . htmlspecialchars(file_exists_ci($physical_path)) : 'Not found'; ?></td>
            </tr>
            <tr>
                <td>Session Status</td>
                <td><?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not active'; ?></td>
            </tr>
            <tr>
                <td>User ID in Session</td>
                <td><?php echo isset($_SESSION['userId']) ? htmlspecialchars($_SESSION['userId']) : 'Not set'; ?></td>
            </tr>
            <tr>
                <td>User Type in Session</td>
                <td><?php echo isset($_SESSION['user_type']) ? htmlspecialchars($_SESSION['user_type']) : 'Not set'; ?></td>
            </tr>
        </table>

        <h3>Directory Contents</h3>
        <table>
            <tr>
                <th>Filename</th>
                <th>Size</th>
                <th>Modified</th>
            </tr>
            <?php 
            $files = scandir(__DIR__);
            foreach ($files as $file):
                if ($file === '.' || $file === '..') continue;
                $file_path = __DIR__ . '/' . $file;
                $is_dir = is_dir($file_path);
                $size = $is_dir ? '-' : filesize($file_path) . ' bytes';
                $modified = date("Y-m-d H:i:s", filemtime($file_path));
            ?>
            <tr>
                <td><?php echo htmlspecialchars($file) . ($is_dir ? ' (Directory)' : ''); ?></td>
                <td><?php echo $size; ?></td>
                <td><?php echo $modified; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</body>
</html>
