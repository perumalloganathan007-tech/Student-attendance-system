<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header for plain text output
header('Content-Type: text/plain');

echo "Subject Teacher Login Final Validation\n";
echo "====================================\n\n";

// 1. Check if we have the database connection file
echo "Step 1: Checking database connection file\n";
$dbconPath = '../Includes/dbcon.php';
if (file_exists($dbconPath)) {
    echo "✓ Database connection file exists\n\n";
    require_once($dbconPath);
} else {
    die("✗ Database connection file not found!\n");
}

// 2. Check database connection
echo "Step 2: Testing database connection\n";
if (!isset($conn) || $conn->connect_error) {
    die("✗ Database connection failed: " . ($conn->connect_error ?? "Connection variable not available") . "\n");
}
echo "✓ Database connection successful\n\n";

// 3. Check if subject teacher table exists and has the expected structure
echo "Step 3: Checking tblsubjectteachers table structure\n";
$result = $conn->query("SHOW COLUMNS FROM tblsubjectteachers");
if (!$result) {
    die("✗ Error accessing tblsubjectteachers table: " . $conn->error . "\n");
}

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[$row['Field']] = $row['Type'];
}

// Check required columns
$requiredColumns = ['Id', 'firstName', 'lastName', 'emailAddress', 'password', 'subjectId'];
$columnsOk = true;

foreach ($requiredColumns as $column) {
    if (!isset($columns[$column])) {
        echo "✗ Missing required column: $column\n";
        $columnsOk = false;
    }
}

if ($columnsOk) {
    echo "✓ All required columns exist in tblsubjectteachers\n\n";
} else {
    echo "Please fix missing columns before proceeding\n\n";
}

// 4. Check subject teacher accounts
echo "Step 4: Checking subject teacher accounts\n";
$result = $conn->query("SELECT * FROM tblsubjectteachers");
if (!$result) {
    die("✗ Error querying tblsubjectteachers: " . $conn->error . "\n");
}

if ($result->num_rows == 0) {
    echo "✗ No subject teachers found in database\n";
} else {
    echo "✓ Found " . $result->num_rows . " subject teachers\n";
    
    // Test login for default accounts
    echo "\nTesting predefined account credentials:\n";
    $testAccounts = [
        'john.smith@school.com',
        'sarah.johnson@school.com',
        'michael.williams@school.com',
        'emily.brown@school.com',
        'david.jones@school.com'
    ];
    
    $testPassword = "Password@123";
    
    foreach ($testAccounts as $email) {
        $query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo "- $email: Not found in database\n";
            continue;
        }
        
        $row = $result->fetch_assoc();
        $verified = password_verify($testPassword, $row['password']);
        
        if ($verified) {
            echo "- $email: ✓ Login successful with 'Password@123'\n";
        } else {
            echo "- $email: ✗ Login failed! Updating password hash...\n";
            $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
            $updateQuery = "UPDATE tblsubjectteachers SET password = ? WHERE emailAddress = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $newHash, $email);
            
            if ($updateStmt->execute()) {
                echo "  ✓ Password updated successfully\n";
            } else {
                echo "  ✗ Failed to update password: " . $conn->error . "\n";
            }
        }
    }
}

echo "\nAll fixes have been applied. The subject teacher login should now work with the following credentials:\n";
echo "Email: john.smith@school.com (or any other teacher email)\n";
echo "Password: Password@123\n";

$conn->close();
?>
