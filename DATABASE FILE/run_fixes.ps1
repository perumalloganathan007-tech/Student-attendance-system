Write-Host "Running Subject Teacher Login Fix Scripts"

$PHP_PATH = 'd:\LOQ Backup 12-05-2025\D Backup\xampp\php\php.exe'
$SCRIPT_PATH = 'd:\LOQ Backup 12-05-2025\D Backup\xampp\htdocs\tax\Student-Attendance-System'

Write-Host "`n===== Updating Subject Teacher Passwords ====="
& "$PHP_PATH" "$SCRIPT_PATH\DATABASE FILE\update_passwords.php"

Write-Host "`n===== Done! ====="
Write-Host "Please try logging in with the following credentials:"
Write-Host "Email: john.smith@school.com"
Write-Host "Password: Password@123"
