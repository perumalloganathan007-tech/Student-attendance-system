<?php
// Mail Configuration
define('SMTP_HOST', 'smtp.gmail.com');  // Change this to your SMTP host
define('SMTP_PORT', 587);               // Change this to your SMTP port
define('SMTP_USERNAME', '');            // Your email address
define('SMTP_PASSWORD', '');            // Your email password or app password
define('SMTP_FROM', 'noreply@attendancesystem.com');
define('SMTP_FROM_NAME', 'Attendance System');

// For Gmail, you need to:
// 1. Enable 2-step verification on your Google account
// 2. Generate an App Password: Google Account > Security > App Passwords
// 3. Use that App Password here instead of your regular password
?>
