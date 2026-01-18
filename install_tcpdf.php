<?php
// This is an installation helper script for the Student Attendance System

// Check if we're running in web mode or CLI
$isWeb = isset($_SERVER['HTTP_HOST']);

if ($isWeb) {
    // Output as HTML for web requests
    echo "<!DOCTYPE html>
<html>
<head>
    <title>TCPDF Installer - Student Attendance System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #4e73df; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        hr { border: 0; height: 1px; background: #e3e6f0; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 15px; background: #4e73df; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        pre { background: #f8f9fc; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Student Attendance System - TCPDF Installation</h1>
    <hr />
";

    // Function to display status
    function webStatus($text, $status) {
        $class = $status ? 'success' : 'error';
        $icon = $status ? '✅' : '❌';
        return "<p><span class='{$class}'>{$icon} {$text}</span></p>";
    }
} else {
    // CLI output
    echo "=================================================\n";
    echo "Student Attendance System - TCPDF Installation Helper\n";
    echo "=================================================\n\n";

    // Function to display status in CLI
    function webStatus($text, $status) {
        $icon = $status ? '✅' : '❌';
        return "{$icon} {$text}\n";
    }
}

// Check if Composer is installed
$composerInstalled = false;
exec('composer -V', $output, $returnVar);
if ($returnVar === 0) {
    echo webStatus("Composer is installed: {$output[0]}", true);
    $composerInstalled = true;
} else {
    echo webStatus("Composer is NOT installed", false);
    if (!$isWeb) {
        echo "   Please download from https://getcomposer.org/download/\n\n";
    }
}

// Check if vendor directory exists
$vendorExists = file_exists(__DIR__ . '/vendor');
if ($vendorExists) {
    echo webStatus("Vendor directory exists", true);
    
    // Check if autoload.php exists
    $autoloadExists = file_exists(__DIR__ . '/vendor/autoload.php');
    echo webStatus("vendor/autoload.php exists", $autoloadExists);
    
    // Check if TCPDF library exists
    $tcpdfExists = file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf') || file_exists(__DIR__ . '/vendor/tcpdf/tcpdf.php');
    echo webStatus("TCPDF library is installed", $tcpdfExists);
    
    if (file_exists(__DIR__ . '/vendor/tcpdf/tcpdf.php')) {
        echo webStatus("TCPDF is installed manually in vendor/tcpdf", true);
    }
    
    if (file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf')) {
        echo webStatus("TCPDF is installed via Composer in vendor/tecnickcom/tcpdf", true);
    }
} else {
    echo webStatus("Vendor directory does not exist", false);
}

// Display installation options
if ($isWeb) {
    echo "<hr /><h2>Installation Options</h2>";

    // Direct installer option
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3>Option 1: Automatic Installation (Recommended)</h3>";
    echo "<p>This option will automatically download and install TCPDF files directly without requiring Composer:</p>";
    echo "<a href='install_tcpdf_direct.php' class='btn'>Install TCPDF Automatically</a>";
    echo "</div>";
    
    // Composer option
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3>Option 2: Install with Composer</h3>";
    if ($composerInstalled) {
        echo "<p>Run this command in your terminal:</p>";
        echo "<pre>cd \"" . __DIR__ . "\"\ncomposer install</pre>";
    } else {
        echo "<ol>";
        echo "<li>Download and install <a href='https://getcomposer.org/download/' target='_blank'>Composer</a></li>";
        echo "<li>Open a command prompt and navigate to: <pre>cd \"" . __DIR__ . "\"</pre></li>";
        echo "<li>Run: <pre>composer install</pre></li>";
        echo "</ol>";
    }
    echo "</div>";
    
    // Manual option
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3>Option 3: Manual Installation</h3>";
    echo "<ol>";
    echo "<li>Download <a href='https://github.com/tecnickcom/TCPDF/releases' target='_blank'>TCPDF from GitHub</a></li>";
    echo "<li>Create a folder named 'tcpdf' inside the 'vendor' directory</li>";
    echo "<li>Extract all files into the 'vendor/tcpdf' directory</li>";
    echo "<li>Make sure 'tcpdf.php' is directly inside 'vendor/tcpdf'</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<hr /><p>After installation is complete, you can try downloading reports again.</p>";
    echo "</body></html>";
} else {
    echo "\n=================================================\n";
    echo "INSTALLATION OPTIONS:\n";
    echo "=================================================\n\n";

    echo "OPTION 1 (Recommended): Use the direct installer\n";
    echo "Run: php install_tcpdf_direct.php\n\n";
    
    if ($composerInstalled) {
        echo "OPTION 2: Install TCPDF with Composer\n";
        echo "Run: composer install\n\n";
    } else {
        echo "OPTION 2: Install Composer then TCPDF\n";
        echo "1. Download Composer from https://getcomposer.org/download/\n";
        echo "2. Run the installer and follow the instructions\n";
        echo "3. Open command prompt in this directory\n";
        echo "4. Run: composer install\n\n";
    }
    
    echo "OPTION 3: Manual Installation\n";
    echo "1. Download TCPDF from https://github.com/tecnickcom/TCPDF/releases\n";
    echo "2. Create a folder named 'tcpdf' inside the 'vendor' directory\n";
    echo "3. Extract all files into the 'vendor/tcpdf' directory\n";
    echo "4. Make sure 'tcpdf.php' is directly inside 'vendor/tcpdf'\n\n";
    
    echo "Once installed, try downloading reports again.\n";
    echo "=================================================\n";
}
?>
?>
