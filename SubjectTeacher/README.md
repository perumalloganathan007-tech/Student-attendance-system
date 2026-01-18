# Subject Teacher Module

## Overview

The Subject Teacher module allows teachers to manage attendance for specific subjects they are assigned to teach. This module includes functionality for taking attendance, viewing reports, and analyzing student performance.

## Setup Instructions

1. **Access the System:**
   - Navigate to `http://localhost/tax/Student-Attendance-System/`
   - Click on "Subject Teacher Login"
   - Use the provided credentials (default: Email from the database, Password: `Password@123`)

2. **If Blank Page After Login:**
   - Run the master fix script: `http://localhost/tax/Student-Attendance-System/SubjectTeacher/master_fix.php`
   - This script will automatically fix common issues with the Subject Teacher module

3. **Diagnostic Tools:**
   - Debug Tool: `http://localhost/tax/Student-Attendance-System/SubjectTeacher/debug.php`
   - Database Fix Tool: `http://localhost/tax/Student-Attendance-System/SubjectTeacher/fix_database.php`
   - Table Check: `http://localhost/tax/Student-Attendance-System/table_check.php`

## Features

1. **Dashboard:**
   - View summary of students assigned to your subject
   - See today's attendance statistics
   - Access recent attendance records

2. **Take Attendance:**
   - Record daily attendance for students in your subject
   - Mark students as present or absent
   - View historical attendance data

3. **Reports:**
   - Generate date range reports
   - View monthly attendance summaries
   - Identify students with low attendance

4. **Student Management:**
   - View all students assigned to your subject
   - Track individual student performance

## Troubleshooting

If you encounter any issues:

1. Run the master fix script (`master_fix.php`)
2. Check the debug log for specific errors
3. Verify database tables exist using table check script
4. Ensure proper CSS and JS files are loaded
5. Reset your browser cache

## For Developers

The Subject Teacher module uses the following tables:
- `tblsubjectteachers` - Stores teacher information
- `tblsubjects` - Contains subject details
- `tblsubjectteacher_student` - Maps students to subject teachers
- `tblsubjectattendance` - Stores attendance records for subjects

Important files:
- `index.php` - Main dashboard
- `takeAttendance.php` - Attendance recording interface
- `Includes/sidebar.php` - Navigation menu
- `Includes/topbar.php` - Top navigation bar
