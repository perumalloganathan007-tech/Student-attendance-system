<?php
// File check script to verify and fix case sensitivity and file existence issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check file existence regardless of case
function file_exists_case_insensitive($file) {
    if (file_exists($file)) {
        return $file; // File exists with exact case
    }
    
    $dir = dirname($file);
    $basename = basename($file);
    
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = scandir($dir);
    foreach ($files as $filename) {
        if (strtolower($filename) === strtolower($basename)) {
            return $dir . DIRECTORY_SEPARATOR . $filename;
        }
    }
    
    return false;
}

// Function to normalize file names in a directory
function normalize_file_case($dirname, $files_to_check) {
    echo "<h2>File Case Normalization Report</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Expected Filename</th><th>Actual Filename</th><th>Status</th></tr>";
    
    foreach ($files_to_check as $expected_file) {
        $full_path = $dirname . DIRECTORY_SEPARATOR . $expected_file;
        $actual_file = file_exists_case_insensitive($full_path);
        
        if ($actual_file === false) {
            echo "<tr><td>$expected_file</td><td>Not found</td><td style='background-color: #ffcccc;'>Missing</td></tr>";
        } else {
            $actual_filename = basename($actual_file);
            if ($actual_filename === $expected_file) {
                echo "<tr><td>$expected_file</td><td>$actual_filename</td><td style='background-color: #ccffcc;'>Correct</td></tr>";
            } else {
                echo "<tr><td>$expected_file</td><td>$actual_filename</td><td style='background-color: #ffffcc;'>Case Mismatch</td></tr>";
            }
        }
    }
    
    echo "</table>";
}

// List all PHP files in the directory
function list_all_files($dirname) {
    echo "<h2>All Files in Directory</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr><th>Filename</th><th>Size</th><th>Last Modified</th></tr>";
    
    $files = scandir($dirname);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $full_path = $dirname . DIRECTORY_SEPARATOR . $file;
        $size = is_dir($full_path) ? "-" : number_format(filesize($full_path)) . " bytes";
        $last_modified = date("Y-m-d H:i:s", filemtime($full_path));
        $type = is_dir($full_path) ? "Directory" : "File";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($file) . " ($type)</td>";
        echo "<td>$size</td>";
        echo "<td>$last_modified</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// Main script
$dir = __DIR__;
echo "<h1>File System Check</h1>";
echo "<p>Directory: " . htmlspecialchars($dir) . "</p>";

// List of key files to check
$key_files = [
    'index.php',
    'viewTodayAttendance.php',
    'takeAttendance.php',
    'viewAttendance.php',
    'viewStudentAttendance.php',
    'fix_session.php',
    '404.php'
];

// Check these files
normalize_file_case($dir, $key_files);

// List all files
list_all_files($dir);

// Specific check for viewTodayAttendance.php
$target_file = 'viewTodayAttendance.php';
$full_target_path = $dir . DIRECTORY_SEPARATOR . $target_file;
echo "<h2>Specific Check for '$target_file'</h2>";
echo "<p>Full path: " . htmlspecialchars($full_target_path) . "</p>";

// Check if the file exists with the exact case
if (file_exists($full_target_path)) {
    echo "<p style='color: green;'>File exists with correct case.</p>";
} else {
    // Try to find it with case-insensitive search
    $actual_file = file_exists_case_insensitive($full_target_path);
    if ($actual_file !== false) {
        echo "<p style='color: orange;'>File exists but with different case: " . htmlspecialchars(basename($actual_file)) . "</p>";
        
        // Offer to create a simple redirect file
        echo "<h3>Create a Redirect File?</h3>";
        
        if (isset($_GET['create_redirect']) && $_GET['create_redirect'] == 'yes') {
            $redirect_content = "<?php\n// Simple redirect file\nheader('Location: " . basename($actual_file) . "');\nexit;";
            file_put_contents($full_target_path, $redirect_content);
            echo "<p style='color: green;'>Redirect file created successfully!</p>";
            echo "<p>Contents:</p>";
            echo "<pre>" . htmlspecialchars($redirect_content) . "</pre>";
        } else {
            echo "<p>Would you like to create a simple redirect file that will redirect requests from '$target_file' to '" . basename($actual_file) . "'?</p>";
            echo "<a href='?create_redirect=yes' style='padding: 5px 10px; background-color: #4e73df; color: white; text-decoration: none; border-radius: 3px;'>Create Redirect File</a>";
        }
    } else {
        echo "<p style='color: red;'>File does not exist in any form.</p>";
        
        echo "<h3>Check File Contents</h3>";
        echo "<p>Checking if any file contains 'Today's Attendance' text:</p>";
        
        $found = false;
        $files = scandir($dir);
        foreach ($files as $file) {
            if (is_file($dir . DIRECTORY_SEPARATOR . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $contents = file_get_contents($dir . DIRECTORY_SEPARATOR . $file);
                if (strpos($contents, "Today's Attendance") !== false) {
                    echo "<p>Found in file: <strong>" . htmlspecialchars($file) . "</strong></p>";
                    $found = true;
                }
            }
        }
        
        if (!$found) {
            echo "<p>No files found containing 'Today's Attendance' text.</p>";
        }
    }
}
