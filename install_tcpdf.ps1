# PowerShell script to install TCPDF library
Write-Host "============================================="
Write-Host "TCPDF Installer for Student Attendance System"
Write-Host "============================================="
Write-Host ""

# Get the script directory path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpPath = "php"

# Check if PHP is available
try {
    $phpVersion = & php -v
    Write-Host "✓ PHP detected: $($phpVersion[0])"
} catch {
    Write-Host "✗ PHP not found in PATH. Please ensure PHP is installed and available in your PATH."
    Write-Host "  You can download PHP from: https://windows.php.net/download/"
    exit 1
}

# Run the installer
Write-Host ""
Write-Host "Running TCPDF direct installer..."
Write-Host ""
& php "$scriptPath\install_tcpdf_direct.php"

# Pause to view the result
Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
