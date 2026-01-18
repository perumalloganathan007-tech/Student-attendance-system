<?php
require_once('Includes/dbcon.php');

echo "<h1>🎯 Complete System Status Check</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; }
    .section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
    .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn-success { background: #28a745; }
    .btn-warning { background: #ffc107; color: #212529; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>";

echo "<div class='container'>";

// 1. Database Connection Test
echo "<div class='section'>";
echo "<h2>1. 🔌 Database Connection</h2>";
if ($conn) {
    echo "<span class='success'>✓ Database connection successful</span><br>";
    echo "<span class='info'>Connected to: " . mysqli_get_server_info($conn) . "</span>";
} else {
    echo "<span class='error'>✗ Database connection failed: " . mysqli_connect_error() . "</span>";
    exit;
}
echo "</div>";

// 2. Table Structure Check
echo "<div class='section'>";
echo "<h2>2. 🗄️ Database Tables Status</h2>";

$requiredTables = [
    'tblsubjectteacher' => 'Subject Teacher profiles',
    'tblsubjects' => 'Subjects list',
    'tblstudents' => 'Student profiles', 
    'tblsubjectattendance' => 'Subject attendance records',
    'tblsubjectteacher_student' => 'Student-Teacher assignments',
    'tblclass' => 'Class definitions',
    'tblclassarms' => 'Class arms'
];

echo "<table>";
echo "<tr><th>Table Name</th><th>Description</th><th>Status</th><th>Record Count</th></tr>";

foreach ($requiredTables as $table => $description) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        // Get record count
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
        $count = mysqli_fetch_assoc($countResult)['count'];
        
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>$description</td>";
        echo "<td><span class='success'>✓ Exists</span></td>";
        echo "<td>$count records</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>$description</td>";
        echo "<td><span class='error'>✗ Missing</span></td>";
        echo "<td>-</td>";
        echo "</tr>";
    }
}
echo "</table>";
echo "</div>";

// 3. Subject Teacher Data Check
echo "<div class='section'>";
echo "<h2>3. 👨‍🏫 Subject Teacher Data</h2>";

$teacherResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM tblsubjectteacher");
if ($teacherResult) {
    $teacherCount = mysqli_fetch_assoc($teacherResult)['count'];
    echo "<p>Subject Teachers in database: <strong>$teacherCount</strong></p>";
    
    if ($teacherCount > 0) {
        // Show sample teacher data
        $sampleResult = mysqli_query($conn, "SELECT st.firstName, st.lastName, st.emailAddress, s.subjectName 
                                           FROM tblsubjectteacher st 
                                           LEFT JOIN tblsubjects s ON s.Id = st.subjectId 
                                           LIMIT 3");
        
        echo "<table>";
        echo "<tr><th>Name</th><th>Email</th><th>Subject</th></tr>";
        while ($teacher = mysqli_fetch_assoc($sampleResult)) {
            echo "<tr>";
            echo "<td>" . $teacher['firstName'] . " " . $teacher['lastName'] . "</td>";
            echo "<td>" . $teacher['emailAddress'] . "</td>";
            echo "<td>" . ($teacher['subjectName'] ?: 'Not assigned') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No subject teachers found. Run the table creation script to add sample data.</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot query tblsubjectteacher table</p>";
}
echo "</div>";

// 4. Login System Test
echo "<div class='section'>";
echo "<h2>4. 🔐 Login System Test</h2>";

// Test the query structure used in login
try {
    $testEmail = 'john.smith@email.com';
    $query = "SELECT st.*, s.subjectName, s.Id as subjectId 
              FROM tblsubjectteacher st
              INNER JOIN tblsubjects s ON s.Id = st.subjectId
              WHERE st.emailAddress = ?";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("s", $testEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo "<span class='success'>✓ Login query structure is correct</span><br>";
            echo "<span class='info'>Test user found: " . $user['firstName'] . " " . $user['lastName'] . "</span><br>";
            echo "<span class='info'>Subject: " . $user['subjectName'] . "</span>";
        } else {
            echo "<span class='warning'>⚠ Login query works but test user not found</span><br>";
            echo "<span class='info'>Create a subject teacher with email: $testEmail</span>";
        }
    } else {
        echo "<span class='error'>✗ Login query preparation failed</span>";
    }
} catch (Exception $e) {
    echo "<span class='error'>✗ Login query error: " . $e->getMessage() . "</span>";
}
echo "</div>";

// 5. File Fix Status
echo "<div class='section'>";
echo "<h2>5. 📁 File Fix Status</h2>";

$filesFixed = [
    'index.php' => 'Main login page - table name fixed',
    'SubjectTeacher/index.php' => 'Dashboard page - table name fixed', 
    'SubjectTeacher/Includes/topbar.php' => 'Navigation bar - table name fixed',
    'SubjectTeacher/viewTodayAttendance.php' => 'Take attendance page - table name and null safety fixed',
    'SubjectTeacher/changePassword.php' => 'Password change - table name fixed'
];

echo "<table>";
echo "<tr><th>File</th><th>Fix Applied</th><th>Status</th></tr>";

foreach ($filesFixed as $file => $fix) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'tblsubjectteachers') !== false) {
            echo "<tr><td>$file</td><td>$fix</td><td><span class='warning'>⚠ Still has old table name</span></td></tr>";
        } else {
            echo "<tr><td>$file</td><td>$fix</td><td><span class='success'>✓ Fixed</span></td></tr>";
        }
    } else {
        echo "<tr><td>$file</td><td>$fix</td><td><span class='error'>✗ File not found</span></td></tr>";
    }
}
echo "</table>";
echo "</div>";

// 6. Next Steps
echo "<div class='section'>";
echo "<h2>6. 🎯 Testing Instructions</h2>";
echo "<ol>";
echo "<li><strong>Login Test:</strong> Use credentials <code>john.smith@email.com</code> / <code>password123</code></li>";
echo "<li><strong>Navigation Test:</strong> After login, check if sidebar loads without errors</li>";
echo "<li><strong>Take Attendance:</strong> Click 'Take Attendance' in sidebar</li>";
echo "<li><strong>Error Check:</strong> Look for any remaining 'tblsubjectteachers' errors</li>";
echo "</ol>";

echo "<p>";
echo "<a href='index.php' class='btn'>🔐 Test Login</a>";
echo "<a href='SubjectTeacher/navigation_test_fixed.html' class='btn btn-success'>🧪 Navigation Test</a>";
echo "<a href='create_tables_simple.php' class='btn btn-warning'>🔧 Create Tables</a>";
echo "</p>";
echo "</div>";

echo "</div>";
?>
