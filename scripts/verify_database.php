<?php
require_once('../Includes/dbcon.php');

// Function to execute an SQL file
function executeSQLFile($conn, $sqlFile) {
    $queries = file_get_contents($sqlFile);
    
    if ($queries === false) {
        die("Error reading SQL file");
    }
    
    // Split SQL file into individual queries
    $queries = explode(';', $queries);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!$conn->query($query)) {
                echo "Error executing query: " . $conn->error . "\n";
                echo "Query was: " . $query . "\n\n";
            } else {
                echo "Successfully executed: " . substr($query, 0, 50) . "...\n";
            }
        }
    }
}

// Test database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!\n\n";

// Execute the SQL file to create tables and insert test data
$sqlFile = __DIR__ . '/create_subject_tables.sql';
echo "Executing SQL file...\n";
executeSQLFile($conn, $sqlFile);

// Verify subject teacher account
$email = 'math.teacher@school.com';
$query = "SELECT * FROM tblsubjectteachers WHERE emailAddress = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "\nTest subject teacher account found:\n";
    echo "Email: " . $row['emailAddress'] . "\n";
    echo "Name: " . $row['firstName'] . " " . $row['lastName'] . "\n";
} else {
    echo "\nTest subject teacher account not found!\n";
}

$conn->close();
?>
