<?php
echo "<h1>PHP Configuration Check</h1>";

// PHP Version
echo "<h2>PHP Version</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
if (version_compare(phpversion(), '5.5.0', '>=')) {
    echo "<p style='color:green;'>PHP version is sufficient for password_hash() and password_verify()</p>";
} else {
    echo "<p style='color:red;'>PHP version is too old! password_hash() and password_verify() require PHP 5.5.0 or later</p>";
}

// Loaded Extensions
echo "<h2>Required Extensions</h2>";
$requiredExtensions = ['mysqli', 'openssl', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color:green;'>✓ $ext extension is loaded</p>";
    } else {
        echo "<p style='color:red;'>✗ $ext extension is NOT loaded</p>";
    }
}

// Password hashing tests
echo "<h2>Password Hashing Tests</h2>";
$testPassword = "Password@123";

// Test password_hash
if (function_exists('password_hash')) {
    echo "<p style='color:green;'>✓ password_hash() function exists</p>";
    $hash = password_hash($testPassword, PASSWORD_BCRYPT);
    echo "<p>Generated hash: " . htmlspecialchars($hash) . "</p>";
} else {
    echo "<p style='color:red;'>✗ password_hash() function does NOT exist!</p>";
}

// Test password_verify
if (function_exists('password_verify')) {
    echo "<p style='color:green;'>✓ password_verify() function exists</p>";
    if (isset($hash)) {
        if (password_verify($testPassword, $hash)) {
            echo "<p style='color:green;'>✓ password_verify() successfully verified the hash</p>";
        } else {
            echo "<p style='color:red;'>✗ password_verify() FAILED to verify the hash</p>";
        }
    }
} else {
    echo "<p style='color:red;'>✗ password_verify() function does NOT exist!</p>";
}

// Hash used in the database
echo "<h2>Test Against Database Hash</h2>";
$dbHash = '$2y$10$xJ9Y1PFUlKGF1liaSr7vgOBlcqK3s1n3B0uICQxK.d5GYlN9l1vYS';
echo "<p>Database hash: " . htmlspecialchars($dbHash) . "</p>";

if (function_exists('password_verify')) {
    if (password_verify($testPassword, $dbHash)) {
        echo "<p style='color:green;'>✓ password_verify() successfully verified the database hash</p>";
    } else {
        echo "<p style='color:red;'>✗ password_verify() FAILED to verify the database hash</p>";
        // Check if hash format is valid
        if (strpos($dbHash, '$2y$') === 0) {
            echo "<p style='color:green;'>Hash format looks valid (starts with $2y$)</p>";
        } else {
            echo "<p style='color:red;'>Hash format may be invalid (should start with $2y$)</p>";
        }
    }
}

// Generate a proper hash to use
$properHash = password_hash("Password@123", PASSWORD_BCRYPT);
echo "<h2>New Hash to Use</h2>";
echo "<p>Copy this hash into your SQL script to update passwords:</p>";
echo "<pre>" . htmlspecialchars($properHash) . "</pre>";

// Server information
echo "<h2>Server Information</h2>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
?>
