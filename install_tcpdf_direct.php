<?php
// This script will download and install TCPDF without requiring Composer

// Display header
echo "=================================================\n";
echo "TCPDF Direct Installer for Student Attendance System\n";
echo "=================================================\n\n";

// Function to create directory if it doesn't exist
function createDir($dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
            return true;
        } else {
            echo "❌ Failed to create directory: $dir\n";
            return false;
        }
    } else {
        echo "✅ Directory already exists: $dir\n";
        return true;
    }
}

// Function to download a file
function downloadFile($url, $destination) {
    echo "⏳ Downloading from $url...\n";
    
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "❌ Failed to download file from $url\n";
        return false;
    }
    
    if (file_put_contents($destination, $content) === false) {
        echo "❌ Failed to save file to $destination\n";
        return false;
    }
    
    echo "✅ Downloaded and saved to $destination\n";
    return true;
}

// TCPDF direct files we need
$files = [
    'tcpdf.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/tcpdf.php',
    'tcpdf_autoconfig.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/tcpdf_autoconfig.php',
    'tcpdf_barcodes_1d.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/tcpdf_barcodes_1d.php',
    'tcpdf_barcodes_2d.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/tcpdf_barcodes_2d.php',
    'tcpdf_parser.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/tcpdf_parser.php',
    'include/tcpdf_colors.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_colors.php',
    'include/tcpdf_filters.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_filters.php',
    'include/tcpdf_font_data.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_font_data.php',
    'include/tcpdf_fonts.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_fonts.php',
    'include/tcpdf_images.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_images.php',
    'include/tcpdf_static.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/include/tcpdf_static.php',
    'fonts/helvetica.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/fonts/helvetica.php',
    'fonts/helveticab.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/fonts/helveticab.php',
    'fonts/helveticabi.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/fonts/helveticabi.php',
    'fonts/helveticai.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/fonts/helveticai.php',
    'config/tcpdf_config.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/6.6.2/config/tcpdf_config.php'
];

// Base directory for TCPDF installation
$tcpdfBaseDir = __DIR__ . '/vendor/tcpdf';

// Create base directory
if (!createDir($tcpdfBaseDir)) {
    die("❌ Cannot continue without the base directory\n");
}

// Create necessary subdirectories
createDir($tcpdfBaseDir . '/include');
createDir($tcpdfBaseDir . '/fonts');
createDir($tcpdfBaseDir . '/config');

// Set default timezone to prevent warnings
date_default_timezone_set('UTC');

// Download each file
$success = true;
foreach ($files as $file => $url) {
    $destination = $tcpdfBaseDir . '/' . $file;
    $dirName = dirname($destination);
    
    if (!file_exists($dirName)) {
        createDir($dirName);
    }
    
    if (!downloadFile($url, $destination)) {
        $success = false;
    }
}

// Create a simple autoloader for TCPDF if we're not using Composer
$autoloaderPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloaderPath)) {
    $autoloaderContent = <<<'PHP'
<?php
// Simple autoloader created by install_tcpdf_direct.php
spl_autoload_register(function ($class) {
    // Only handle TCPDF class
    if ($class === 'TCPDF') {
        require_once __DIR__ . '/tcpdf/tcpdf.php';
    }
});
PHP;

    if (file_put_contents($tcpdfBaseDir . '/../autoload.php', $autoloaderContent)) {
        echo "✅ Created simple autoloader at vendor/autoload.php\n";
    } else {
        echo "❌ Failed to create autoloader\n";
        $success = false;
    }
}

// Display result
echo "\n=================================================\n";
if ($success) {
    echo "✅ TCPDF installation completed successfully!\n";
    echo "   You can now generate PDF reports in the system.\n";
} else {
    echo "⚠️ TCPDF installation completed with some errors.\n";
    echo "   PDF generation might not work correctly.\n";
}
echo "=================================================\n";

// Test TCPDF installation
echo "\nTesting TCPDF installation...\n";
if (file_exists($tcpdfBaseDir . '/tcpdf.php')) {
    include_once($tcpdfBaseDir . '/tcpdf.php');
    try {
        $testPdf = new TCPDF();
        echo "✅ TCPDF class loaded successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error loading TCPDF class: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ TCPDF main file not found\n";
}
echo "=================================================\n";
?>
