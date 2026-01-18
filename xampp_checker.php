<?php
// XAMPP Service Checker
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if a service/port is running
function check_service_port($host, $port) {
    $connection = @fsockopen($host, $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

// Check common XAMPP services
$services = [
    'Apache' => [
        'port' => 80,
        'status' => false,
        'important' => true,
        'start_cmd' => 'Open XAMPP Control Panel and click "Start" next to Apache'
    ],
    'MySQL' => [
        'port' => 3306,
        'status' => false,
        'important' => true,
        'start_cmd' => 'Open XAMPP Control Panel and click "Start" next to MySQL'
    ],
    'FileZilla FTP' => [
        'port' => 21,
        'status' => false,
        'important' => false,
        'start_cmd' => 'Open XAMPP Control Panel and click "Start" next to FileZilla'
    ],
    'ProFTPD' => [
        'port' => 21,
        'status' => false,
        'important' => false,
        'start_cmd' => 'Open XAMPP Control Panel and click "Start" next to ProFTPD'
    ],
    'Tomcat' => [
        'port' => 8080,
        'status' => false,
        'important' => false,
        'start_cmd' => 'Open XAMPP Control Panel and click "Start" next to Tomcat'
    ],
];

// Check each service
foreach ($services as $name => &$service) {
    $service['status'] = check_service_port('localhost', $service['port']);
}

// Check if the script is included from another file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    // This script is being executed directly
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XAMPP Services Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #4e73df;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .status {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .running {
            background-color: #d4edda;
            color: #155724;
        }
        .stopped {
            background-color: #f8d7da;
            color: #721c24;
        }
        .normal {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .action {
            margin-top: 5px;
        }
        .important {
            font-weight: bold;
        }
        .instructions {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        button {
            padding: 8px 12px;
            background: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #2e59d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>XAMPP Services Checker</h1>
        <p>This tool checks if the essential XAMPP services are running on your server.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Port</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $name => $service): ?>
                <tr>
                    <td><?php echo $name; ?><?php echo $service['important'] ? ' <span class="important">(Required)</span>' : ''; ?></td>
                    <td><?php echo $service['port']; ?></td>
                    <td>
                        <span class="status <?php echo $service['status'] ? 'running' : 'stopped'; ?>">
                            <?php echo $service['status'] ? 'Running' : 'Stopped'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$service['status']): ?>
                            <div class="action"><?php echo $service['start_cmd']; ?></div>
                        <?php else: ?>
                            <span class="status normal">No action needed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
        // Check if all important services are running
        $all_important_running = true;
        foreach ($services as $name => $service) {
            if ($service['important'] && !$service['status']) {
                $all_important_running = false;
                break;
            }
        }
        
        if (!$all_important_running): ?>
        <div class="instructions">
            <h3>How to Start XAMPP Services:</h3>
            <ol>
                <li>Open XAMPP Control Panel (look for it in the Start menu or on your desktop)</li>
                <li>For each service marked as "Stopped" and "Required" above, click the corresponding "Start" button</li>
                <li>Wait a few seconds for the services to start</li>
                <li><button onclick="window.location.reload();">Refresh This Page</button> to check if the services are now running</li>
            </ol>
            <p><strong>Note:</strong> If you cannot start the services, there might be conflicts with other software using the same ports or permissions issues. Try stopping conflicting services or running XAMPP as administrator.</p>
        </div>
        <?php else: ?>
        <div class="instructions" style="background-color: #d4edda; border-color: #c3e6cb; color: #155724;">
            <h3>All Required Services Are Running!</h3>
            <p>Your XAMPP environment appears to be working correctly. You should now be able to access:</p>
            <ul>
                <li>Your website: <a href="http://localhost/tax/Student-Attendance-System/" target="_blank">http://localhost/tax/Student-Attendance-System/</a></li>
                <li>Subject Teacher area: <a href="http://localhost/tax/Student-Attendance-System/SubjectTeacher/" target="_blank">http://localhost/tax/Student-Attendance-System/SubjectTeacher/</a></li>
                <li>PhpMyAdmin: <a href="http://localhost/phpmyadmin/" target="_blank">http://localhost/phpmyadmin/</a></li>
            </ul>
            <p>If you're still having issues, try:</p>
            <ol>
                <li>Checking your browser's console for JavaScript errors</li>
                <li>Looking at Apache's error log in your XAMPP installation</li>
                <li>Verifying your database connections in the application</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <p><small>Note: This tool checks only if the ports are open, not if the services are fully functional.</small></p>
    </div>
</body>
</html>
<?php
}
?>
