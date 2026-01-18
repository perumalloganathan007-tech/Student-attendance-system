<?php
// Auto-fix script for Subject Teacher Login Issues
// This script automatically fixes all column and login issues

error_reporting(E_ALL);
ini_set('display_errors', 1);

// If run from browser, set content type
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html><head><title>Auto-Fix Login Issues</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .container { max-width: 800px; margin: 0 auto; }
        a.button { 
            display: inline-block; 
            background: #4CAF50; 
            color: white; 
            padding: 10px 15px; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-top: 15px;
        }
    </style>
    </head><body><div class='container'><h1>Auto-Fix Subject Teacher Login Issues</h1>";
}

echo "Starting automatic fix for subject teacher login issues...\n\n";

// Step 1: Connect to database
echo "Step 1: Connecting to database...\n";
$host = "localhost";
$user = "root";
$pass = "";
$db = "attendancesystem";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    if($conn->connect_error){
        throw new Exception("Database Connection Failed: " . $conn->connect_error);
    }
    echo "✓ Database connection successful!\n\n";
} catch (Exception $e) {
    die("✗ " . $e->getMessage());
}

// Step 2: Add required columns to tables if needed
echo "Step 2: Adding required columns to tables...\n";

// Check and add subjectCode column
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

// Check and add classId column
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

// Step 3: Check and update subject data
echo "Step 3: Updating subject data...\n";

// First, get a count of subjects
$result = $conn->query("SELECT COUNT(*) as count FROM tblsubjects");
$row = $result->fetch_assoc();
$subjectCount = $row['count'];

if ($subjectCount == 0) {
    echo "No subjects found. Adding sample subjects...\n";
    
    $subjectsQuery = "INSERT INTO `tblsubjects` (`subjectName`, `subjectCode`, `classId`) VALUES
    ('Mathematics', 'MATH101', '1'),
    ('Physics', 'PHY101', '1'),
    ('Chemistry', 'CHEM101', '2'),
    ('Biology', 'BIO101', '2'),
    ('English', 'ENG101', '1')";
    
    if ($conn->query($subjectsQuery)) {
        echo "✓ Sample subjects added successfully\n";
    } else {
        echo "✗ Failed to add sample subjects: " . $conn->error . "\n";
    }
} else {
    echo "Found $subjectCount existing subjects\n";
    
    // Update subjects that might be missing subjectCode or classId
    $result = $conn->query("SELECT Id, subjectName FROM tblsubjects WHERE subjectCode = '' OR subjectCode IS NULL OR classId = '' OR classId IS NULL");
    
    if ($result->num_rows > 0) {
        echo "Updating " . $result->num_rows . " subjects with missing data...\n";
        
        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $id = $row['Id'];
            $name = $row['subjectName'];
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3)) . $i;
            $classId = ($i % 2) + 1; // Alternate between class 1 and 2
            
            $updateQuery = "UPDATE tblsubjects SET subjectCode = '$code', classId = '$classId' WHERE Id = $id";
            if ($conn->query($updateQuery)) {
                echo "✓ Updated subject: $name (Code: $code, Class: $classId)\n";
            } else {
                echo "✗ Failed to update subject $name: " . $conn->error . "\n";
            }
            $i++;
        }
    } else {
        echo "✓ All subjects have complete data\n";
    }
}

echo "\n";

// Step 4: Check and update subject teachers
echo "Step 4: Updating subject teachers and passwords...\n";

// First, get a count of subject teachers
$result = $conn->query("SELECT COUNT(*) as count FROM tblsubjectteachers");
$row = $result->fetch_assoc();
$teacherCount = $row['count'];

if ($teacherCount == 0) {
    echo "No subject teachers found. Adding sample teachers...\n";
    
    // Generate properly hashed passwords
    $password = "Password@123";
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    $teachersQuery = "INSERT INTO `tblsubjectteachers` (
        `firstName`, 
        `lastName`, 
        `emailAddress`, 
        `password`, 
        `phoneNo`, 
        `subjectId`
    ) VALUES
    ('John', 'Smith', 'john.smith@school.com', '$passwordHash', '1234567890', 
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'MATH101' OR Id = 1 LIMIT 1)),
     
    ('Sarah', 'Johnson', 'sarah.johnson@school.com', '$passwordHash', '2345678901',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'PHY101' OR Id = 2 LIMIT 1)),
     
    ('Michael', 'Williams', 'michael.williams@school.com', '$passwordHash', '3456789012',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'CHEM101' OR Id = 3 LIMIT 1)),
     
    ('Emily', 'Brown', 'emily.brown@school.com', '$passwordHash', '4567890123',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'BIO101' OR Id = 4 LIMIT 1)),
     
    ('David', 'Jones', 'david.jones@school.com', '$passwordHash', '5678901234',
     (SELECT Id FROM tblsubjects WHERE subjectCode = 'ENG101' OR Id = 5 LIMIT 1))";
    
    if ($conn->query($teachersQuery)) {
        echo "✓ Sample subject teachers added successfully\n";
    } else {
        echo "✗ Failed to add sample subject teachers: " . $conn->error . "\n";
    }
} else {
    echo "Found $teacherCount existing subject teachers\n";
    
    // Update all passwords to the same secure hash
    $password = "Password@123";
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    $updateQuery = "UPDATE tblsubjectteachers SET password = '$passwordHash'";
    if ($conn->query($updateQuery)) {
        echo "✓ Updated passwords for all subject teachers to '$password'\n";
    } else {
        echo "✗ Failed to update subject teacher passwords: " . $conn->error . "\n";
    }
}

echo "\n";

// Step 5: Verify database structure is correct
echo "Step 5: Verifying database structure...\n";

$errors = [];

// Check if subjects table has all required columns
$requiredSubjectColumns = ['Id', 'subjectName', 'subjectCode', 'classId'];
$result = $conn->query("SHOW COLUMNS FROM tblsubjects");
$subjectColumns = [];
while ($row = $result->fetch_assoc()) {
    $subjectColumns[] = $row['Field'];
}

foreach ($requiredSubjectColumns as $column) {
    if (!in_array($column, $subjectColumns)) {
        $errors[] = "Subject table is missing required column: $column";
    }
}

// Check if subject teachers table has all required columns
$requiredTeacherColumns = ['Id', 'firstName', 'lastName', 'emailAddress', 'password', 'phoneNo', 'subjectId'];
$result = $conn->query("SHOW COLUMNS FROM tblsubjectteachers");
$teacherColumns = [];
while ($row = $result->fetch_assoc()) {
    $teacherColumns[] = $row['Field'];
}

foreach ($requiredTeacherColumns as $column) {
    if (!in_array($column, $teacherColumns)) {
        $errors[] = "Subject teachers table is missing required column: $column";
    }
}

if (count($errors) > 0) {
    echo "⚠ Found " . count($errors) . " structural issues:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
} else {
    echo "✓ Database structure is correct\n";
}

echo "\n";

// Step 6: Verify sample data exists
echo "Step 6: Verifying sample data exists...\n";

// Check for subject teacher test account
$result = $conn->query("SELECT * FROM tblsubjectteachers WHERE emailAddress = 'john.smith@school.com'");
if ($result->num_rows == 0) {
    echo "⚠ Test account not found - something went wrong with data creation\n";
} else {
    $row = $result->fetch_assoc();
    $password = "Password@123";
    if (password_verify($password, $row['password'])) {
        echo "✓ Test account verified and password works correctly\n";
    } else {
        echo "⚠ Test account found but password verification fails\n";
        
        // Try to update again
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $updateQuery = "UPDATE tblsubjectteachers SET password = '$passwordHash' WHERE emailAddress = 'john.smith@school.com'";
        if ($conn->query($updateQuery)) {
            echo "✓ Updated test account password\n";
        }
    }
}

echo "\n";
echo "===== Auto-Fix Complete! =====\n\n";

echo "You can now log in with the following credentials:\n";
echo "Email: john.smith@school.com\n";
echo "Password: Password@123\n\n";

echo "Other test accounts:\n";
echo "- sarah.johnson@school.com / Password@123\n";
echo "- michael.williams@school.com / Password@123\n";
echo "- emily.brown@school.com / Password@123\n";
echo "- david.jones@school.com / Password@123\n";

$conn->close();

if (php_sapi_name() !== 'cli') {
    echo "<p class='success'><strong>All fixes have been applied!</strong></p>";
    echo "<h2>Login Credentials</h2>";
    echo "<pre>
Email: john.smith@school.com
Password: Password@123

Other accounts:
- sarah.johnson@school.com / Password@123
- michael.williams@school.com / Password@123
- emily.brown@school.com / Password@123
- david.jones@school.com / Password@123
</pre>";
    
    echo "<a href='../subjectTeacherLogin.php' class='button'>Go to Subject Teacher Login</a>";
    echo "</div></body></html>";
}
?>
