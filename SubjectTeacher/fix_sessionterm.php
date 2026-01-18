<?php
// Script to fix the missing sessionTermId column in tblsubjectattendance table
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';

echo "<h1>Database Schema Fix Tool</h1>";

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $result = $conn->query($query);
    return ($result && $result->num_rows > 0);
}

// Check if the tblsubjectattendance table has the sessionTermId column
echo "<h2>Checking for sessionTermId column in tblsubjectattendance table...</h2>";

if (columnExists($conn, 'tblsubjectattendance', 'sessionTermId')) {
    echo "<p style='color: green;'>✓ Column sessionTermId already exists in tblsubjectattendance table.</p>";
} else {
    echo "<p style='color: red;'>✗ Column sessionTermId does not exist in tblsubjectattendance table.</p>";
    
    // Add the column if it doesn't exist
    echo "Attempting to add sessionTermId column to tblsubjectattendance table...<br>";
    
    try {
        $alterQuery = "ALTER TABLE tblsubjectattendance ADD sessionTermId INT(11) DEFAULT 1 AFTER date";
        if ($conn->query($alterQuery) === TRUE) {
            echo "<p style='color: green;'>✓ Successfully added sessionTermId column to tblsubjectattendance table!</p>";
            
            // Get the current active session term ID
            $sessionQuery = "SELECT Id FROM tblsessionterm WHERE isActive = 1";
            $sessionResult = $conn->query($sessionQuery);
            
            if ($sessionResult && $sessionResult->num_rows > 0) {
                $sessionRow = $sessionResult->fetch_assoc();
                $activeSessionId = $sessionRow['Id'];
                
                // Update all existing records with the active session term ID
                $updateQuery = "UPDATE tblsubjectattendance SET sessionTermId = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("i", $activeSessionId);
                
                if ($updateStmt->execute()) {
                    echo "<p style='color: green;'>✓ Updated all existing records with the active session term ID ($activeSessionId).</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to update existing records: " . $updateStmt->error . "</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠ No active session term found. Using default value of 1 for sessionTermId.</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to add sessionTermId column: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

// Check if the sessionTermId column has properly populated values
echo "<h2>Verifying sessionTermId values...</h2>";

$query = "SELECT COUNT(*) as total, COUNT(sessionTermId) as populated FROM tblsubjectattendance";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row['total'] == $row['populated']) {
    echo "<p style='color: green;'>✓ All records have sessionTermId values populated.</p>";
} else {
    echo "<p style='color: orange;'>⚠ Some records may have NULL sessionTermId values.</p>";
    
    // Fix any NULL values
    $updateQuery = "UPDATE tblsubjectattendance SET sessionTermId = 1 WHERE sessionTermId IS NULL";
    if ($conn->query($updateQuery) === TRUE) {
        echo "<p style='color: green;'>✓ Updated all NULL sessionTermId values to 1.</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to update NULL sessionTermId values: " . $conn->error . "</p>";
    }
}

// Check if the tblterm table references tblsessionterm correctly
echo "<h2>Checking tblterm table structure...</h2>";

if (!columnExists($conn, 'tblterm', 'sessionTermId')) {
    echo "<p>tblterm table doesn't have a sessionTermId column. This might be OK if it uses a different structure.</p>";
    
    // Get column names from tblterm
    $columnsQuery = "SHOW COLUMNS FROM tblterm";
    $columnsResult = $conn->query($columnsQuery);
    
    echo "<p>tblterm columns:</p><ul>";
    while ($column = $columnsResult->fetch_assoc()) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
}

// Fix session_utils.php if needed
echo "<h2>Checking session_utils.php for potential issues...</h2>";

$sessionUtilsPath = "Includes/session_utils.php";
if (file_exists($sessionUtilsPath)) {
    $sessionUtilsContent = file_get_contents($sessionUtilsPath);
    
    // Check if it contains the t.sessionTermId reference
    if (strpos($sessionUtilsContent, "t.sessionTermId") !== false) {
        echo "<p style='color: orange;'>⚠ Found references to t.sessionTermId in session_utils.php</p>";
        
        // Backup the file
        $backupPath = "Includes/session_utils.php.bak." . time();
        if (copy($sessionUtilsPath, $backupPath)) {
            echo "<p>Created backup at $backupPath</p>";
            
            // Fix the query
            $fixedContent = str_replace(
                "INNER JOIN tblterm t ON t.sessionTermId = s.Id", 
                "INNER JOIN tblterm t ON t.Id = s.termId", 
                $sessionUtilsContent
            );
            
            if (file_put_contents($sessionUtilsPath, $fixedContent)) {
                echo "<p style='color: green;'>✓ Updated session_utils.php with corrected query.</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to update session_utils.php.</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to create backup of session_utils.php.</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ No problematic references found in session_utils.php.</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ session_utils.php not found at expected path.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If all fixes were applied successfully, try accessing the attendance analytics page now:</p>";
echo "<a href='attendanceAnalytics.php' style='padding: 10px 20px; background-color: #4e73df; color: white; text-decoration: none; border-radius: 4px;'>Go to Analytics Dashboard</a>";
echo "<br><br>";
echo "<p>If you still encounter issues, you can also try checking the server status:</p>";
echo "<a href='server_status.php' style='padding: 10px 20px; background-color: #1cc88a; color: white; text-decoration: none; border-radius: 4px;'>Check Server Status</a>";
?>
