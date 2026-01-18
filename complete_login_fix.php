<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'Includes/dbcon.php';

echo "<h2>Complete Subject Teacher Login Fix</h2>";

// 1. Check database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
} else {
    echo "<p style='color:green'>✅ Database connected successfully</p>";
}

// 2. Check if table exists
echo "<h3>2. Table Structure Check</h3>";
$tableCheck = $conn->query("SHOW TABLES LIKE 'tblsubjectteachers'");
if ($tableCheck->num_rows == 0) {
    echo "<p style='color:red'>❌ tblsubjectteachers table does not exist!</p>";
    exit;
} else {
    echo "<p style='color:green'>✅ tblsubjectteachers table exists</p>";
}

// 3. Create/Update john.smith@school.com account
echo "<h3>3. Subject Teacher Account Setup</h3>";
$email = "john.smith@school.com";
$password = "Password@123";
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Check if user exists
$checkUser = $conn->prepare("SELECT * FROM tblsubjectteachers WHERE emailAddress = ?");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows > 0) {
    // Update existing user
    $updateUser = $conn->prepare("UPDATE tblsubjectteachers SET password = ?, firstName = 'John', lastName = 'Smith' WHERE emailAddress = ?");
    $updateUser->bind_param("ss", $hashedPassword, $email);
    if ($updateUser->execute()) {
        echo "<p style='color:green'>✅ Updated existing account for $email</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to update account: " . $conn->error . "</p>";
    }
} else {
    // Create new user
    $insertUser = $conn->prepare("INSERT INTO tblsubjectteachers (firstName, lastName, emailAddress, password, subjectId) VALUES ('John', 'Smith', ?, ?, 1)");
    $insertUser->bind_param("ss", $email, $hashedPassword);
    if ($insertUser->execute()) {
        echo "<p style='color:green'>✅ Created new account for $email</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create account: " . $conn->error . "</p>";
    }
}

// 4. Verify the account
echo "<h3>4. Account Verification</h3>";
$verifyUser = $conn->prepare("SELECT * FROM tblsubjectteachers WHERE emailAddress = ?");
$verifyUser->bind_param("s", $email);
$verifyUser->execute();
$verifyResult = $verifyUser->get_result();

if ($verifyResult->num_rows > 0) {
    $user = $verifyResult->fetch_assoc();
    echo "<p style='color:green'>✅ Account verified:</p>";
    echo "<ul>";
    echo "<li>ID: " . $user['Id'] . "</li>";
    echo "<li>Name: " . $user['firstName'] . " " . $user['lastName'] . "</li>";
    echo "<li>Email: " . $user['emailAddress'] . "</li>";
    echo "<li>Subject ID: " . $user['subjectId'] . "</li>";
    echo "</ul>";
    
    // Test password
    if (password_verify($password, $user['password'])) {
        echo "<p style='color:green'>✅ Password verification successful</p>";
    } else {
        echo "<p style='color:red'>❌ Password verification failed</p>";
    }
} else {
    echo "<p style='color:red'>❌ Account verification failed</p>";
}

// 5. Test login simulation
echo "<h3>5. Login Simulation</h3>";
if (isset($_POST['test_login'])) {
    $testEmail = $_POST['email'];
    $testPassword = $_POST['password'];
    
    $loginQuery = $conn->prepare("SELECT * FROM tblsubjectteachers WHERE emailAddress = ?");
    $loginQuery->bind_param("s", $testEmail);
    $loginQuery->execute();
    $loginResult = $loginQuery->get_result();
    
    if ($loginResult->num_rows > 0) {
        $loginUser = $loginResult->fetch_assoc();
        if (password_verify($testPassword, $loginUser['password'])) {
            echo "<p style='color:green; font-weight:bold'>🎉 LOGIN SUCCESSFUL!</p>";
            echo "<p>You can now login with these credentials on the actual login page.</p>";
        } else {
            echo "<p style='color:red'>❌ Wrong password</p>";
        }
    } else {
        echo "<p style='color:red'>❌ User not found</p>";
    }
}

echo "<form method='POST'>";
echo "<h4>Test Login:</h4>";
echo "<p>Email: <input type='email' name='email' value='$email' style='width:250px'></p>";
echo "<p>Password: <input type='password' name='password' value='$password' style='width:250px'></p>";
echo "<p><input type='submit' name='test_login' value='Test Login' style='padding:10px 20px; background:green; color:white; border:none; cursor:pointer'></p>";
echo "</form>";

echo "<hr>";
echo "<h3>6. Quick Access Links</h3>";
echo "<div style='padding:15px; background:#f0f0f0; border:1px solid #ddd;'>";
echo "<p><strong>Ready to login? Use these links:</strong></p>";
echo "<p><a href='subjectTeacherLogin.php' target='_blank' style='background:blue; color:white; padding:10px 15px; text-decoration:none; border-radius:5px;'>➤ Go to Subject Teacher Login</a></p>";
echo "<p><strong>Credentials:</strong><br>";
echo "Email: <code>john.smith@school.com</code><br>";
echo "Password: <code>Password@123</code></p>";
echo "</div>";
?>
