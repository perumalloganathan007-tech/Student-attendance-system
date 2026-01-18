# TCPDF Installation Guide for Student Attendance System

The Student Attendance System requires the TCPDF library for PDF report generation. This guide provides instructions on how to install this dependency.

## Automatic Installation (Recommended)

The easiest way to install TCPDF is to use the automatic installer:

1. Access your Student Attendance System installation
2. Navigate to `http://your-website/install_tcpdf.php` in your browser
3. Click the "Install TCPDF Automatically" button
4. Wait for the installation to complete

## Command Line Installation

If you have access to the command line on your server:

1. Navigate to your Student Attendance System directory
2. Run one of these commands:
   - With PHP CLI: `php install_tcpdf_direct.php`
   - With PowerShell: `.\install_tcpdf.ps1`

## Manual Installation

If the automatic installation doesn't work:

1. Download TCPDF from [GitHub Releases](https://github.com/tecnickcom/TCPDF/releases)
2. Create a directory called `tcpdf` inside your `vendor` directory 
3. Extract the TCPDF files into this directory
4. Make sure `tcpdf.php` is directly inside `vendor/tcpdf`

## Installation with Composer (Advanced)

If you're familiar with Composer:

1. Make sure Composer is installed on your system
2. Navigate to your Student Attendance System directory
3. Run `composer install`

## Troubleshooting

If you encounter issues with PDF report generation:

1. Check if the TCPDF files are properly installed in `vendor/tcpdf`
2. Verify that PHP has read permissions for these files
3. Check PHP error logs for specific error messages

For additional help, please contact your system administrator.
