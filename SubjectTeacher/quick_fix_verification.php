<?php
require_once('../Includes/dbcon.php');

echo "<h2>🔧 Quick Fix Verification</h2>";
echo "<hr>";

// Test 1: Check database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn) {
    echo "<span style='color: green;'>✓ Database connection successful</span><br>";
} else {
    echo "<span style='color: red;'>✗ Database connection failed: " . mysqli_connect_error() . "</span><br>";
    exit;
}

// Test 2: Check if tblsubjectteacher table exists (correct name)
echo "<h3>2. Table Existence Check</h3>";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteacher'");
if (mysqli_num_rows($result) > 0) {
    echo "<span style='color: green;'>✓ Table 'tblsubjectteacher' exists</span><br>";
} else {
    echo "<span style='color: red;'>✗ Table 'tblsubjectteacher' missing</span><br>";
    echo "<p><strong>Action needed:</strong> Run the fix_missing_tables.php script from the dashboard</p>";
}

// Test 3: Check if incorrect table name still exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteachers'");
if (mysqli_num_rows($result) > 0) {
    echo "<span style='color: orange;'>⚠ Old table 'tblsubjectteachers' (plural) still exists</span><br>";
} else {
    echo "<span style='color: green;'>✓ Old incorrect table 'tblsubjectteachers' not found (good)</span><br>";
}

// Test 4: Check essential tables
echo "<h3>3. Essential Tables Check</h3>";
$essential_tables = [
    'tblsubjectteacher',
    'tblsubjects', 
    'tblstudents',
    'tblsubjectattendance',
    'tblsubjectteacher_student'
];

foreach ($essential_tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "<span style='color: green;'>✓ $table</span><br>";
    } else {
        echo "<span style='color: red;'>✗ $table (missing)</span><br>";
    }
}

// Test 5: Test topbar.php query (simulated)
echo "<h3>4. topbar.php Query Test</h3>";
try {
    $testQuery = "SELECT COUNT(*) as count FROM tblsubjectteacher";
    $result = mysqli_query($conn, $testQuery);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<span style='color: green;'>✓ topbar.php query syntax is now correct</span><br>";
        echo "<span style='color: blue;'>ℹ Subject teachers in database: " . $row['count'] . "</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ topbar.php query still has issues: " . $e->getMessage() . "</span><br>";
}

// Test 6: Session simulation test
echo "<h3>5. Session Simulation Test</h3>";
session_start();
if (!isset($_SESSION['userId'])) {
    // Create a test session for demonstration
    $_SESSION['userId'] = 1;
    echo "<span style='color: blue;'>ℹ Test session created with userId = 1</span><br>";
}

try {
    $userId = $_SESSION['userId'];
    $query = "SELECT * FROM tblsubjectteacher WHERE Id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<span style='color: green;'>✓ Session-based query working correctly</span><br>";
        } else {
            echo "<span style='color: orange;'>⚠ Query works but no data found for userId $userId</span><br>";
        }
    } else {
        echo "<span style='color: red;'>✗ Query preparation failed</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>✗ Session test failed: " . $e->getMessage() . "</span><br>";
}

echo "<hr>";
echo "<h3>🎯 Next Steps</h3>";
echo "<ol>";
echo "<li>If any tables are missing, run <strong>fix_missing_tables.php</strong> from the dashboard</li>";
echo "<li>Test the actual navigation: Dashboard → Take Attendance</li>";
echo "<li>If login issues persist, check login credentials</li>";
echo "<li>Verify student assignments to subject teachers</li>";
echo "</ol>";

echo "<p><a href='test_attendance_dashboard.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Back to Testing Dashboard</a></p>";
?>
