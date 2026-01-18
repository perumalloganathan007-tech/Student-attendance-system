<?php
require_once('Includes/dbcon.php');

echo "<h1>🔧 Subject Information Fix</h1>";
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
    .btn-danger { background: #dc3545; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>";

echo "<div class='container'>";

// 1. Check current subject teacher data
echo "<div class='section'>";
echo "<h2>1. 🔍 Current Subject Teacher Analysis</h2>";

// Check if tblsubjectteacher exists and has data
$result = mysqli_query($conn, "SHOW TABLES LIKE 'tblsubjectteacher'");
if (mysqli_num_rows($result) == 0) {
    echo "<p class='error'>✗ Table 'tblsubjectteacher' does not exist!</p>";
    echo "<p><a href='create_tables_simple.php' class='btn btn-danger'>Create Missing Tables</a></p>";
} else {
    echo "<p class='success'>✓ Table 'tblsubjectteacher' exists</p>";
    
    // Check subject teachers and their subject assignments
    $teacherQuery = "SELECT st.*, s.subjectName, s.subjectCode 
                     FROM tblsubjectteacher st 
                     LEFT JOIN tblsubjects s ON s.Id = st.subjectId 
                     ORDER BY st.Id";
    $teacherResult = mysqli_query($conn, $teacherQuery);
    
    if ($teacherResult && mysqli_num_rows($teacherResult) > 0) {
        echo "<h3>Current Subject Teachers:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Subject ID</th><th>Subject Name</th><th>Status</th></tr>";
        
        while ($teacher = mysqli_fetch_assoc($teacherResult)) {
            echo "<tr>";
            echo "<td>" . $teacher['Id'] . "</td>";
            echo "<td>" . $teacher['firstName'] . " " . $teacher['lastName'] . "</td>";
            echo "<td>" . $teacher['emailAddress'] . "</td>";
            echo "<td>" . ($teacher['subjectId'] ?: 'NULL') . "</td>";
            echo "<td>" . ($teacher['subjectName'] ?: 'No Subject Assigned') . "</td>";
            
            if ($teacher['subjectId'] && $teacher['subjectName']) {
                echo "<td><span class='success'>✓ Complete</span></td>";
            } else {
                echo "<td><span class='error'>✗ Missing Subject</span></td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No subject teachers found in database</p>";
    }
}
echo "</div>";

// 2. Check subjects table
echo "<div class='section'>";
echo "<h2>2. 📚 Available Subjects</h2>";

$subjectResult = mysqli_query($conn, "SELECT * FROM tblsubjects ORDER BY Id");
if ($subjectResult && mysqli_num_rows($subjectResult) > 0) {
    echo "<table>";
    echo "<tr><th>Subject ID</th><th>Subject Name</th><th>Subject Code</th></tr>";
    
    while ($subject = mysqli_fetch_assoc($subjectResult)) {
        echo "<tr>";
        echo "<td>" . $subject['Id'] . "</td>";
        echo "<td>" . $subject['subjectName'] . "</td>";
        echo "<td>" . ($subject['subjectCode'] ?? 'No Code') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No subjects found in database</p>";
    echo "<p>Creating sample subjects...</p>";
    
    // Create sample subjects
    $subjects = [
        ['Mathematics', 'MATH101'],
        ['English', 'ENG101'], 
        ['Science', 'SCI101'],
        ['History', 'HIST101']
    ];
    
    foreach ($subjects as $subject) {
        $insertSubject = "INSERT INTO tblsubjects (subjectName, subjectCode) VALUES (?, ?)";
        $stmt = $conn->prepare($insertSubject);
        $stmt->bind_param('ss', $subject[0], $subject[1]);
        
        if ($stmt->execute()) {
            echo "<p class='success'>✓ Created subject: " . $subject[0] . " (" . $subject[1] . ")</p>";
        }
    }
}
echo "</div>";

// 3. Fix subject assignments
echo "<div class='section'>";
echo "<h2>3. 🔧 Fix Subject Assignments</h2>";

if (isset($_POST['fix_assignments'])) {
    // Get all teachers without proper subject assignments
    $fixQuery = "SELECT st.Id, st.emailAddress, st.firstName, st.lastName, st.subjectId 
                 FROM tblsubjectteacher st 
                 LEFT JOIN tblsubjects s ON s.Id = st.subjectId 
                 WHERE s.Id IS NULL OR st.subjectId IS NULL";
    $fixResult = mysqli_query($conn, $fixQuery);
    
    if ($fixResult && mysqli_num_rows($fixResult) > 0) {
        // Get first available subject
        $firstSubjectResult = mysqli_query($conn, "SELECT Id FROM tblsubjects ORDER BY Id LIMIT 1");
        if ($firstSubjectResult && mysqli_num_rows($firstSubjectResult) > 0) {
            $firstSubject = mysqli_fetch_assoc($firstSubjectResult);
            $defaultSubjectId = $firstSubject['Id'];
            
            while ($teacher = mysqli_fetch_assoc($fixResult)) {
                $updateQuery = "UPDATE tblsubjectteacher SET subjectId = ? WHERE Id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param('ii', $defaultSubjectId, $teacher['Id']);
                
                if ($stmt->execute()) {
                    echo "<p class='success'>✓ Fixed subject assignment for: " . $teacher['firstName'] . " " . $teacher['lastName'] . "</p>";
                } else {
                    echo "<p class='error'>✗ Failed to fix assignment for: " . $teacher['firstName'] . " " . $teacher['lastName'] . "</p>";
                }
            }
        }
    } else {
        echo "<p class='success'>✓ All teachers already have valid subject assignments</p>";
    }
}

// Check if fixes are needed
$needsFixQuery = "SELECT COUNT(*) as count 
                  FROM tblsubjectteacher st 
                  LEFT JOIN tblsubjects s ON s.Id = st.subjectId 
                  WHERE s.Id IS NULL OR st.subjectId IS NULL";
$needsFixResult = mysqli_query($conn, $needsFixQuery);
$needsFix = mysqli_fetch_assoc($needsFixResult)['count'];

if ($needsFix > 0) {
    echo "<p class='warning'>⚠ Found $needsFix teacher(s) with missing subject assignments</p>";
    echo "<form method='post'>";
    echo "<button type='submit' name='fix_assignments' class='btn btn-success'>Fix All Subject Assignments</button>";
    echo "</form>";
} else {
    echo "<p class='success'>✓ All teachers have valid subject assignments</p>";
}
echo "</div>";

// 4. Create/verify john.smith user
echo "<div class='section'>";
echo "<h2>4. 👤 Test User: john.smith@email.com</h2>";

$johnQuery = "SELECT st.*, s.subjectName 
              FROM tblsubjectteacher st 
              LEFT JOIN tblsubjects s ON s.Id = st.subjectId 
              WHERE st.emailAddress = 'john.smith@email.com'";
$johnResult = mysqli_query($conn, $johnQuery);

if ($johnResult && mysqli_num_rows($johnResult) > 0) {
    $john = mysqli_fetch_assoc($johnResult);
    echo "<p class='success'>✓ Test user exists</p>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>" . $john['Id'] . "</td></tr>";
    echo "<tr><td>Name</td><td>" . $john['firstName'] . " " . $john['lastName'] . "</td></tr>";
    echo "<tr><td>Email</td><td>" . $john['emailAddress'] . "</td></tr>";
    echo "<tr><td>Subject ID</td><td>" . ($john['subjectId'] ?: 'NULL') . "</td></tr>";
    echo "<tr><td>Subject Name</td><td>" . ($john['subjectName'] ?: 'Not Assigned') . "</td></tr>";
    echo "</table>";
    
    if (!$john['subjectId'] || !$john['subjectName']) {
        echo "<p class='warning'>⚠ John Smith needs subject assignment</p>";
        
        if (isset($_POST['fix_john'])) {
            $firstSubjectResult = mysqli_query($conn, "SELECT Id FROM tblsubjects ORDER BY Id LIMIT 1");
            if ($firstSubjectResult && mysqli_num_rows($firstSubjectResult) > 0) {
                $firstSubject = mysqli_fetch_assoc($firstSubjectResult);
                $subjectId = $firstSubject['Id'];
                
                $updateJohn = "UPDATE tblsubjectteacher SET subjectId = ? WHERE emailAddress = 'john.smith@email.com'";
                $stmt = $conn->prepare($updateJohn);
                $stmt->bind_param('i', $subjectId);
                
                if ($stmt->execute()) {
                    echo "<p class='success'>✓ Fixed John Smith's subject assignment</p>";
                    echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
                }
            }
        } else {
            echo "<form method='post'>";
            echo "<button type='submit' name='fix_john' class='btn btn-success'>Fix John's Subject Assignment</button>";
            echo "</form>";
        }
    }
} else {
    echo "<p class='error'>✗ Test user 'john.smith@email.com' not found</p>";
    
    if (isset($_POST['create_john'])) {
        // Create john.smith user
        $firstSubjectResult = mysqli_query($conn, "SELECT Id FROM tblsubjects ORDER BY Id LIMIT 1");
        if ($firstSubjectResult && mysqli_num_rows($firstSubjectResult) > 0) {
            $firstSubject = mysqli_fetch_assoc($firstSubjectResult);
            $subjectId = $firstSubject['Id'];
            
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $createJohn = "INSERT INTO tblsubjectteacher (firstName, lastName, emailAddress, password, phoneNo, subjectId) 
                          VALUES ('John', 'Smith', 'john.smith@email.com', ?, '1234567890', ?)";
            $stmt = $conn->prepare($createJohn);
            $stmt->bind_param('si', $hashedPassword, $subjectId);
            
            if ($stmt->execute()) {
                echo "<p class='success'>✓ Created test user John Smith</p>";
                echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
            }
        }
    } else {
        echo "<form method='post'>";
        echo "<button type='submit' name='create_john' class='btn btn-success'>Create Test User</button>";
        echo "</form>";
    }
}
echo "</div>";

// 5. Session test
echo "<div class='section'>";
echo "<h2>5. 🔐 Login Session Test</h2>";

// Simulate login process
$testQuery = "SELECT st.*, s.subjectName, s.Id as subjectId 
              FROM tblsubjectteacher st
              INNER JOIN tblsubjects s ON s.Id = st.subjectId
              WHERE st.emailAddress = 'john.smith@email.com'";
$testResult = mysqli_query($conn, $testQuery);

if ($testResult && mysqli_num_rows($testResult) > 0) {
    $user = mysqli_fetch_assoc($testResult);
    echo "<p class='success'>✓ Login query will work correctly</p>";
    echo "<p class='info'>Session will contain:</p>";
    echo "<ul>";
    echo "<li>userId: " . $user['Id'] . "</li>";
    echo "<li>firstName: " . $user['firstName'] . "</li>";
    echo "<li>lastName: " . $user['lastName'] . "</li>";
    echo "<li>subjectId: " . $user['subjectId'] . "</li>";
    echo "<li>subjectName: " . $user['subjectName'] . "</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>✗ Login query will fail - subject information missing</p>";
    echo "<p>This is the root cause of the 'Subject information is missing' error.</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>6. 🎯 Next Steps</h2>";
echo "<ol>";
echo "<li>Fix any missing subject assignments using the buttons above</li>";
echo "<li>Test login with: <strong>john.smith@email.com</strong> / <strong>password123</strong></li>";
echo "<li>Go to <a href='index.php' target='_blank'>Login Page</a></li>";
echo "<li>Select 'Subject Teacher' and login</li>";
echo "<li>Check if the dashboard loads without 'Subject information is missing' error</li>";
echo "</ol>";

echo "<p>";
echo "<a href='index.php' class='btn'>🔐 Test Login Now</a>";
echo "<a href='complete_system_check.php' class='btn'>🔍 Full System Check</a>";
echo "</p>";
echo "</div>";

echo "</div>";
?>
