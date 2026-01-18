<?php
// Test script for print and export functionality
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Print and Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-present {
            background-color: #d4edda;
            color: #155724;
        }
        .status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 3px;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #4e73df;
        }
        .btn-info {
            background-color: #36b9cc;
        }
        .btn-success {
            background-color: #1cc88a;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Print and CSV Export</h1>
        <p>This page tests the print and export to CSV functionality for attendance reports.</p>
        
        <div>
            <a href="#" class="btn btn-info" onclick="printTable()">
                <i class="fas fa-print"></i> Test Print
            </a>
            
            <a href="#" class="btn btn-success" onclick="exportTableToCSV()">
                <i class="fas fa-file-csv"></i> Test Export CSV
            </a>
        </div>
        
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Admission No.</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <!-- Sample Data -->
                <tr>
                    <td>1</td>
                    <td>Smith, John</td>
                    <td>ADM001</td>
                    <td>Class A</td>
                    <td class="status-present">Present</td>
                    <td>Participated well</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Johnson, Lisa</td>
                    <td>ADM002</td>
                    <td>Class A</td>
                    <td class="status-present">Present</td>
                    <td>Completed all tasks</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Williams, Robert</td>
                    <td>ADM003</td>
                    <td>Class B</td>
                    <td class="status-absent">Absent</td>
                    <td>Medical leave</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Brown, Emily</td>
                    <td>ADM004</td>
                    <td>Class B</td>
                    <td class="status-present">Present</td>
                    <td>Late by 10 minutes</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>Jones, Michael</td>
                    <td>ADM005</td>
                    <td>Class C</td>
                    <td class="status-absent">Absent</td>
                    <td>No notification</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
    function printTable() {
        // Store the original content
        var originalContents = document.body.innerHTML;
        
        // Get table content
        var printContents = document.getElementById('attendanceTable').outerHTML;
        var printHeader = '<div style="text-align:center; margin-bottom:20px;"><h3>Subject: Test Subject (TST101)</h3>' +
                         '<h4>Attendance for Test Date</h4></div>';
        
        // Create print window
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Attendance Report</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
        printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('th { background-color: #f2f2f2; }');
        printWindow.document.write('.status-present { background-color: #d4edda; color: #155724; }');
        printWindow.document.write('.status-absent { background-color: #f8d7da; color: #721c24; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div style="padding:20px;">' + printHeader + printContents + '</div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // Wait for content to load
        printWindow.onload = function() {
            // Print and close the new window
            printWindow.print();
            //printWindow.close();
        };
    }
    
    function exportTableToCSV() {
        // Get the table
        var table = document.getElementById('attendanceTable');
        if (!table) return;
        
        // Prepare CSV content
        var csv = [];
        var rows = table.querySelectorAll('tr');
        
        // Get header row
        var headerRow = [];
        var headers = rows[0].querySelectorAll('th');
        for (var i = 0; i < headers.length; i++) {
            headerRow.push('"' + headers[i].innerText.trim() + '"');
        }
        csv.push(headerRow.join(','));
        
        // Get data rows
        for (var i = 1; i < rows.length; i++) {
            var row = [];
            var cols = rows[i].querySelectorAll('td');
            
            // Skip empty rows or "No students found" messages
            if (cols.length <= 1) continue;
            
            for (var j = 0; j < cols.length; j++) {
                // Get text content and clean it
                var text = cols[j].innerText.trim().replace(/"/g, '""');
                row.push('"' + text + '"');
            }
            
            csv.push(row.join(','));
        }
        
        // Generate CSV file
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // Create download link
        var link = document.createElement('a');
        if (link.download !== undefined) {
            // Create a link to the file
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'attendance_report_test.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            alert('Your browser does not support downloading CSV files directly. Please try using a modern browser like Chrome, Firefox, or Edge.');
        }
    }
    </script>
</body>
</html>
