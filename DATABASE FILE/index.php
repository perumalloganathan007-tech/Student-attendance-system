<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance System - Database Tools</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            margin: 10px 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .button:hover {
            background-color: #45a049;
        }
        .info {
            background-color: #e7f3fe;
            border-left: 6px solid #2196F3;
            padding: 10px;
            margin: 10px 0;
        }
        .warning {
            background-color: #ffffcc;
            border-left: 6px solid #ffeb3b;
            padding: 10px;
            margin: 10px 0;
        }
        hr {
            border: 0;
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student Attendance System - Database Tools</h1>
        <div class="info">
            <p>This page provides access to tools for fixing and diagnosing issues with the database structure and login functionality.</p>
        </div>
        
        <h2>Diagnostic Tools</h2>
        <p>Click the buttons below to run diagnostic tools:</p>
        <a href="fix_database.php" class="button">Run Full Database Diagnostic & Fix</a>
        <a href="php_check.php" class="button">Check PHP Configuration</a>
        <a href="verify_teachers.php" class="button">Verify Subject Teachers</a>
        <a href="login_test.php" class="button">Test Login Process</a>
        
        <hr>
        
        <h2>Login Credentials</h2>
        <div class="warning">
            <p><strong>After running the fixes above, you can log in with these credentials:</strong></p>
            <ul>
                <li><strong>Email:</strong> john.smith@school.com</li>
                <li><strong>Password:</strong> Password@123</li>
            </ul>
            <p>Other test accounts are also available with the same password.</p>
        </div>
        
        <hr>
        
        <h2>Return to Application</h2>
        <a href="../subjectTeacherLogin.php" class="button">Go to Subject Teacher Login</a>
        <a href="../index.php" class="button">Go to Main Login</a>
    </div>
</body>
</html>
