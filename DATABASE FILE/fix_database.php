<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for easy viewing in browser
header('Content-Type: text/plain');

echo "===== Student Attendance System Database Diagnostic =====\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Database Connection
echo "Step 1: Testing Database Connection\n";
echo "==================================\n";

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendancesystem');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Step 2: Check Tables
echo "Step 2: Checking Tables\n";
echo "=====================\n";

$tables = [
    'tblsubjects',
    'tblsubjectteachers',
    'tblstudents',
    'tblclass',
    'tblclassarms',
    'tblclassteacher'
];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
        
        // Check columns for tables we're interested in
        if ($table === 'tblsubjects') {
            checkColumns($conn, $table, ['Id', 'subjectName', 'subjectCode', 'classId']);
        }
        else if ($table === 'tblsubjectteachers') {
            checkColumns($conn, $table, ['Id', 'firstName', 'lastName', 'emailAddress', 'password', 'phoneNo', 'subjectId']);
        }
    } else {
        echo "✗ Table '$table' does not exist!\n";
    }
}
echo "\n";

// Step 3: Check tblsubjects structure
echo "Step 3: Checking tblsubjects structure\n";
echo "===================================\n";
$result = $conn->query("DESCRIBE tblsubjects");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === "NO" ? "NOT NULL" : "NULL") . "\n";
    }
} else {
    echo "✗ Could not get tblsubjects structure!\n";
}
echo "\n";

// Step 4: Check tblsubjectteachers structure
echo "Step 4: Checking tblsubjectteachers structure\n";
echo "=========================================\n";
$result = $conn->query("DESCRIBE tblsubjectteachers");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === "NO" ? "NOT NULL" : "NULL") . "\n";
    }
} else {
    echo "✗ Could not get tblsubjectteachers structure!\n";
}
echo "\n";

// Step 5: Check if we need to add subjectCode
echo "Step 5: Adding required columns if missing\n";
echo "======================================\n";

// Check if subjectCode column exists
$result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'subjectCode'");
if ($result->num_rows == 0) {
    echo "Adding 'subjectCode' column to tblsubjects...\n";
    if ($conn->query("ALTER TABLE tblsubjects ADD COLUMN subjectCode varchar(50) NOT NULL AFTER subjectName")) {
        echo "✓ 'subjectCode' column added successfully\n";
    } else {
        echo "✗ Failed to add 'subjectCode' column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'subjectCode' column already exists\n";
}

// Check if classId column exists
$result = $conn->query("SHOW COLUMNS FROM tblsubjects LIKE 'classId'");
if ($result->num_rows == 0) {
    echo "Adding 'classId' column to tblsubjects...\n";
    if ($conn->query("ALTER TABLE tblsubjects ADD COLUMN classId varchar(10) NOT NULL AFTER subjectCode")) {
        echo "✓ 'classId' column added successfully\n";
    } else {
        echo "✗ Failed to add 'classId' column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'classId' column already exists\n";
}
echo "\n";

// Step 6: Check Subject Teacher Sample Data
echo "Step 6: Check Subject Teacher Sample Data\n";
echo "=====================================\n";
$result = $conn->query("SELECT COUNT(*) as count FROM tblsubjectteachers");
$row = $result->fetch_assoc();
echo "Total subject teachers: " . $row['count'] . "\n";

if ($row['count'] == 0) {
    echo "No subject teachers found. Adding sample data...\n";
    
    // First add subjects if needed
    $subjectsQuery = "INSERT INTO `tblsubjects` (`subjectName`, `subjectCode`, `classId`) VALUES
    ('Mathematics', 'MATH101', '1'),
    ('Physics', 'PHY101', '1'),
    ('Chemistry', 'CHEM101', '2'),
    ('Biology', 'BIO101', '2'),
    ('English', 'ENG101', '1')
    ON DUPLICATE KEY UPDATE subjectName=subjectName";
    
    if ($conn->query($subjectsQuery)) {
        echo "✓ Sample subjects added\n";
    } else {
        echo "✗ Failed to add sample subjects: " . $conn->error . "\n";
    }
    
    // Add subject teachers
    $teachersQuery = "INSERT INTO `tblsubjectteachers` (
        `firstName`, 
        `lastName`, 
        `emailAddress`, 
        `password`, 
        `phoneNo`, 
        `subjectId`
    ) VALUES
    ('John', 'Smith', 'john.smith@school.com', '" . password_hash("Password@123", PASSWORD_BCRYPT) . "', '1234567890', 
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'MATH101' LIMIT 1)),
     
    ('Sarah', 'Johnson', 'sarah.johnson@school.com', '" . password_hash("Password@123", PASSWORD_BCRYPT) . "', '2345678901',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'PHY101' LIMIT 1)),
     
    ('Michael', 'Williams', 'michael.williams@school.com', '" . password_hash("Password@123", PASSWORD_BCRYPT) . "', '3456789012',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'CHEM101' LIMIT 1)),
     
    ('Emily', 'Brown', 'emily.brown@school.com', '" . password_hash("Password@123", PASSWORD_BCRYPT) . "', '4567890123',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'BIO101' LIMIT 1)),
     
    ('David', 'Jones', 'david.jones@school.com', '" . password_hash("Password@123", PASSWORD_BCRYPT) . "', '5678901234',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'ENG101' LIMIT 1))
    ON DUPLICATE KEY UPDATE emailAddress=emailAddress";
    
    if ($conn->query($teachersQuery)) {
        echo "✓ Sample subject teachers added\n";
    } else {
        echo "✗ Failed to add sample subject teachers: " . $conn->error . "\n";
    }
} else {
    echo "Existing subject teachers found. Updating passwords...\n";
    
    $newHash = password_hash("Password@123", PASSWORD_BCRYPT);
    if ($conn->query("UPDATE tblsubjectteachers SET password = '$newHash'")) {
        echo "✓ All subject teacher passwords updated to 'Password@123'\n";
    } else {
        echo "✗ Failed to update subject teacher passwords: " . $conn->error . "\n";
    }
    
    $result = $conn->query("SELECT firstName, lastName, emailAddress FROM tblsubjectteachers LIMIT 5");
    if ($result->num_rows > 0) {
        echo "\nAvailable subject teacher accounts:\n";
        echo "----------------------------------------\n";
        while ($row = $result->fetch_assoc()) {
            echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "\n";
            echo "Email: " . $row['emailAddress'] . "\n";
            echo "Password: Password@123\n";
            echo "----------------------------------------\n";
        }
    }
}

echo "\n";
echo "===== Diagnostic Complete =====\n";
echo "You can now log in using any of the subject teacher accounts with password 'Password@123'\n";

$conn->close();

// Helper function to check columns
function checkColumns($conn, $table, $requiredColumns) {
    echo "  Checking required columns for $table:\n";
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM $table");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "  ✓ '$column' exists\n";
        } else {
            echo "  ✗ '$column' is missing!\n";
        }
    }
}
?>
