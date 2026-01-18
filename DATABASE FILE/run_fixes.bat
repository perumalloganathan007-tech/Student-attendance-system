@echo off
echo Running Subject Teacher Login Fix Scripts

set PHP_PATH=d:\LOQ Backup 12-05-2025\D Backup\xampp\php\php.exe
set SCRIPT_PATH=d:\LOQ Backup 12-05-2025\D Backup\xampp\htdocs\tax\Student-Attendance-System

echo.
echo ===== Updating Subject Teacher Passwords =====
"%PHP_PATH%" "%SCRIPT_PATH%\DATABASE FILE\update_passwords.php"

echo.
echo ===== Checking PHP Configuration =====
"%PHP_PATH%" "%SCRIPT_PATH%\DATABASE FILE\php_check.php" > "%SCRIPT_PATH%\php_check_results.txt"

echo.
echo ===== Results saved to php_check_results.txt =====
echo.
echo ===== Testing Login Flow =====
"%PHP_PATH%" "%SCRIPT_PATH%\DATABASE FILE\login_test.php" > "%SCRIPT_PATH%\login_test_results.txt"

echo.
echo ===== Results saved to login_test_results.txt =====
echo.
echo All done! Please check the results files.
