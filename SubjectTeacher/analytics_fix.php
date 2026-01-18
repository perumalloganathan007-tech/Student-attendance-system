<?php
// Fix for Session Term ID issue in the Student Attendance System

// Include database connection
include '../Includes/dbcon.php';

echo "<h1>Fix for Session Term ID and Database Issues</h1>";
echo "<p>This script will diagnose and fix issues with the analytics page.</p>";

// Check if attendanceAnalytics.php exists
echo "<h2>1. File Existence Check</h2>";
$analyticsFile = "./attendanceAnalytics.php";
if (file_exists($analyticsFile)) {
    echo "<p style='color:green'>✓ Analytics file exists at: $analyticsFile</p>";
} else {
    echo "<p style='color:red'>✗ Analytics file does not exist at: $analyticsFile</p>";
    
    // Check in the current directory with different case
    $files = scandir('.');
    $found = false;
    foreach ($files as $file) {
        if (strtolower($file) === 'attendanceanalytics.php') {
            echo "<p>Found file with different case: $file</p>";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "<p>File not found with any case variation.</p>";
    }
}

// Check database tables
echo "<h2>2. Database Tables Check</h2>";

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result->num_rows > 0;
}

// Check required tables
$requiredTables = [
    'tblsubjects',
    'tblsubjectteachers',
    'tblsubjectattendance',
    'tblsessionterm',
    'tblterm'
];

$allTablesExist = true;
foreach ($requiredTables as $table) {
    if (tableExists($conn, $table)) {
        echo "<p style='color:green'>✓ Table $table exists</p>";
    } else {
        echo "<p style='color:red'>✗ Table $table does not exist</p>";
        $allTablesExist = false;
    }
}

// Check specific columns if all tables exist
if ($allTablesExist) {
    echo "<h2>3. Column Check</h2>";
    
    // Check for sessionTermId column in tblterm
    if (columnExists($conn, 'tblterm', 'sessionTermId')) {
        echo "<p style='color:green'>✓ Column 'sessionTermId' exists in tblterm</p>";
        
        // Check if sessionTermId has proper values
        $result = $conn->query("SELECT COUNT(*) as count FROM tblterm WHERE sessionTermId IS NULL OR sessionTermId = 0");
        $row = $result->fetch_assoc();
        if ($row['count'] > 0) {
            echo "<p style='color:orange'>⚠ Found {$row['count']} rows with empty sessionTermId values</p>";
        } else {
            echo "<p style='color:green'>✓ All sessionTermId values are properly set</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Column 'sessionTermId' does not exist in tblterm</p>";
        
        // Fix: Add sessionTermId column to tblterm
        echo "<p>Adding sessionTermId column to tblterm...</p>";
        $addColumn = $conn->query("ALTER TABLE tblterm ADD COLUMN sessionTermId INT(11)");
        
        if ($addColumn) {
            echo "<p style='color:green'>✓ Added sessionTermId column to tblterm</p>";
            
            // Fix: Update the sessionTermId values
            $updateIds = $conn->query("UPDATE tblterm t 
                                      INNER JOIN tblsessionterm st ON t.Id = st.termId
                                      SET t.sessionTermId = st.Id
                                      WHERE t.sessionTermId IS NULL");
            
            if ($updateIds) {
                echo "<p style='color:green'>✓ Updated sessionTermId values</p>";
            } else {
                echo "<p style='color:red'>✗ Failed to update sessionTermId values: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:red'>✗ Failed to add sessionTermId column: " . $conn->error . "</p>";
        }
    }
}

// Check the SQL query in session_utils.php
echo "<h2>4. SQL Query Check</h2>";
$sessionUtilsFile = "./Includes/session_utils.php";

if (file_exists($sessionUtilsFile)) {
    echo "<p style='color:green'>✓ session_utils.php file exists</p>";
    
    // Read the file contents
    $contents = file_get_contents($sessionUtilsFile);
    if (strpos($contents, "INNER JOIN tblterm t ON t.sessionTermId = st.Id") !== false) {
        echo "<p style='color:red'>✗ Found incorrect JOIN condition in session_utils.php</p>";
        echo "<p>The query should be using t.Id = st.termId instead of t.sessionTermId = st.Id</p>";
        
        // Check if we've already fixed this in a previous run
        if (strpos($contents, "INNER JOIN tblterm t ON t.Id = st.termId") !== false) {
            echo "<p style='color:green'>✓ Correct JOIN condition also exists (might be a previous fix)</p>";
        } else {
            echo "<p>This issue needs to be fixed.</p>";
        }
    } else if (strpos($contents, "INNER JOIN tblterm t ON t.Id = st.termId") !== false) {
        echo "<p style='color:green'>✓ JOIN condition in session_utils.php is correct</p>";
    } else {
        echo "<p style='color:orange'>⚠ Could not identify the JOIN condition pattern in the file</p>";
    }
} else {
    echo "<p style='color:red'>✗ session_utils.php file does not exist at the expected location</p>";
}

// Check .htaccess configuration
echo "<h2>5. .htaccess Configuration Check</h2>";
$htaccessFile = "./.htaccess";

if (file_exists($htaccessFile)) {
    echo "<p style='color:green'>✓ .htaccess file exists</p>";
    
    // Read the file contents
    $htaccessContents = file_get_contents($htaccessFile);
    
    if (strpos($htaccessContents, "RewriteMap") !== false) {
        echo "<p style='color:red'>✗ Found potentially problematic RewriteMap directive in .htaccess</p>";
    } else {
        echo "<p style='color:green'>✓ No problematic RewriteMap directive found in .htaccess</p>";
    }
    
    if (strpos($htaccessContents, "ErrorDocument 404") !== false) {
        echo "<p style='color:green'>✓ Found ErrorDocument 404 directive in .htaccess</p>";
    } else {
        echo "<p style='color:orange'>⚠ No ErrorDocument 404 directive found in .htaccess</p>";
    }
} else {
    echo "<p style='color:orange'>⚠ No .htaccess file found at the expected location</p>";
}

echo "<h2>6. URL Structure Test</h2>";
$currentPath = $_SERVER['PHP_SELF'];
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$analyticsUrl = $baseUrl . str_replace(basename($currentPath), "attendanceAnalytics.php", $currentPath);

echo "<p>Current path: $currentPath</p>";
echo "<p>Base URL: $baseUrl</p>";
echo "<p>Analytics URL should be: $analyticsUrl</p>";

echo "<h2>Recommended Actions</h2>";
echo "<ol>";
echo "<li>Verify that the correct relationship in session_utils.php: INNER JOIN tblterm t ON t.Id = st.termId</li>";
echo "<li>Ensure the tblterm table has proper sessionTermId values</li>";
echo "<li>Simplify the .htaccess file to use only ErrorDocument directive for custom 404 pages</li>";
echo "<li>Test accessing the analytics page directly: <a href='attendanceAnalytics.php'>Open Analytics Page</a></li>";
echo "</ol>";

echo "<p><a href='index.php'>Return to Dashboard</a></p>";
?>
