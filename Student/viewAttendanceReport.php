<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate that user is a Student
if (!isset($_SESSION['userId']) || $_SESSION['userType'] !== 'Student') {
    header("Location: ../index.php");
    exit();
}

$studentId = $_SESSION['userId'];
$classId = $_SESSION['classId'];
$classArmId = $_SESSION['classArmId'];

// Get student's class and section info
$query = "SELECT c.className, ca.classArmName 
          FROM tblstudents s 
          INNER JOIN tblclass c ON c.Id = s.classId
          INNER JOIN tblclassarms ca ON ca.Id = s.classArmId
          WHERE s.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$classInfo = $result->fetch_assoc();

include 'Includes/header.php';
?>

<div class="container-fluid" id="container-wrapper">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Attendance Reports</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="./">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Attendance Reports</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">View Attendance Reports</h6>
                </div>
                <div class="card-body">
                    <!-- Alert for success/error messages -->
                    <div id="alertMessage" class="alert" style="display:none;" role="alert"></div>
                    
                    <!-- Report Type Selection -->
                    <div class="row mb-4">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="reportType">Select Report Type</label>
                                <select class="form-control" id="reportType">
                                    <option value="monthly">Monthly Report</option>
                                    <option value="dateRange">Date Range Report</option>
                                    <option value="yearly">Yearly Report</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Report Options -->
                    <div id="monthlyOptions" class="report-options">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Select Month</label>
                                    <input type="month" class="form-control" id="monthYear" value="<?php echo date('Y-m'); ?>" max="<?php echo date('Y-m'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date Range Options -->
                    <div id="dateRangeOptions" class="report-options" style="display:none;">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>From Date</label>
                                    <input type="date" class="form-control" id="startDate" max="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>To Date</label>
                                    <input type="date" class="form-control" id="endDate" max="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yearly Report Options -->
                    <div id="yearlyOptions" class="report-options" style="display:none;">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Select Year</label>
                                    <select class="form-control" id="year">
                                        <?php 
                                        $currentYear = date('Y');
                                        for($i = $currentYear; $i >= $currentYear - 5; $i--) {
                                            echo "<option value='$i'>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="button-group mb-4">
                        <button type="button" class="btn btn-primary" id="generateReport">
                            <i class="fas fa-sync-alt"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-success" id="exportCSV" style="display:none;">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </button>
                    </div>

                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center" style="display:none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <!-- Report Data Table -->
                    <div class="table-responsive p-3" id="reportData" style="display:none;">
                        <table class="table align-items-center table-flush table-hover" id="attendanceTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Helper function to show alerts
function showAlert(message, type) {
    const alertDiv = document.getElementById('alertMessage');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    alertDiv.style.display = 'block';
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        alertDiv.style.display = 'none';
    }, 5000);
}

// Helper function to toggle loading state
function toggleLoading(show) {
    document.getElementById('loadingSpinner').style.display = show ? 'block' : 'none';
    document.getElementById('generateReport').disabled = show;
    if(document.getElementById('exportCSV').style.display !== 'none') {
        document.getElementById('exportCSV').disabled = show;
    }
}

// Add validation helper function before the event listeners
function validateDates(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Check if dates are valid
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        return { valid: false, message: 'Invalid date format' };
    }

    // Check if dates are in the future
    if (start > today || end > today) {
        return { valid: false, message: 'Dates cannot be in the future' };
    }

    // Check if start date is after end date
    if (start > end) {
        return { valid: false, message: 'Start date cannot be after end date' };
    }

    // Check if date range is too large (e.g., more than 1 year)
    const oneYear = 365 * 24 * 60 * 60 * 1000;
    if (end - start > oneYear) {
        return { valid: false, message: 'Date range cannot exceed 1 year' };
    }

    return { valid: true };
}

// Handle report type change
document.getElementById('reportType').addEventListener('change', function() {
    // Hide all option divs
    document.querySelectorAll('.report-options').forEach(div => div.style.display = 'none');
    
    // Show selected option div
    const selectedType = this.value;
    document.getElementById(selectedType + 'Options').style.display = 'block';
    
    // Hide report data and export button when changing report type
    document.getElementById('reportData').style.display = 'none';
    document.getElementById('exportCSV').style.display = 'none';
});

// Handle generate report
document.getElementById('generateReport').addEventListener('click', async function() {
    const reportType = document.getElementById('reportType').value;
    let params = new URLSearchParams();
    params.append('type', reportType);
    
    // Validate inputs based on report type
    switch(reportType) {
        case 'monthly':
            const monthYear = document.getElementById('monthYear').value;
            if (!monthYear) {
                showAlert('Please select a month and year', 'warning');
                return;
            }
            params.append('monthYear', monthYear);
            break;
            
        case 'dateRange':
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            if (!startDate || !endDate) {
                showAlert('Please select both start and end dates', 'warning');
                return;
            }
            const validation = validateDates(startDate, endDate);
            if (!validation.valid) {
                showAlert(validation.message, 'warning');
                return;
            }
            params.append('startDate', startDate);
            params.append('endDate', endDate);
            break;
            
        case 'yearly':
            params.append('year', document.getElementById('year').value);
            break;
    }
    
    try {
        toggleLoading(true);
        
        const response = await fetch('getAttendanceReport.php?' + params.toString());
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch report data');
            }
            
            const tbody = document.querySelector('#attendanceTable tbody');
            tbody.innerHTML = '';
            
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No attendance records found for the selected period</td></tr>';
                document.getElementById('exportCSV').style.display = 'none';
            } else {
                data.data.forEach(record => {
                    const row = document.createElement('tr');
                    const status = record.status?.trim() || 'Unknown';
                    const statusClass = status.toLowerCase() === 'present' ? 'success' : 'danger';
                    
                    row.innerHTML = `
                        <td>${record.date || 'N/A'}</td>
                        <td><span class="badge badge-${statusClass}">${status}</span></td>
                        <td>${record.subject || 'N/A'}</td>
                        <td>${record.teacher || 'N/A'}</td>
                    `;
                    tbody.appendChild(row);
                });
                document.getElementById('exportCSV').style.display = 'inline-block';
            }
            
            document.getElementById('reportData').style.display = 'block';
            showAlert(`${data.data ? data.data.length : 0} attendance records found`, 'success');
        } else {
            throw new Error('Received non-JSON response from server');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message || 'An error occurred while fetching the report', 'danger');
        document.getElementById('reportData').style.display = 'none';
        document.getElementById('exportCSV').style.display = 'none';
    } finally {
        toggleLoading(false);
    }
});

// Handle CSV export
document.getElementById('exportCSV').addEventListener('click', async function(e) {
    e.preventDefault();
    const reportType = document.getElementById('reportType').value;
    let params = new URLSearchParams();
    params.append('type', reportType);
    params.append('format', 'csv');
    
    switch(reportType) {
        case 'monthly':
            params.append('monthYear', document.getElementById('monthYear').value);
            break;
        case 'dateRange':
            params.append('startDate', document.getElementById('startDate').value);
            params.append('endDate', document.getElementById('endDate').value);
            break;
        case 'yearly':
            params.append('year', document.getElementById('year').value);
            break;
    }

    try {
        toggleLoading(true);
        const response = await fetch('getAttendanceReport.php?' + params.toString());
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('text/csv')) {
            // Get filename from Content-Disposition header or create a default one
            let filename = 'attendance_report.csv';
            const disposition = response.headers.get('content-disposition');
            if (disposition && disposition.includes('filename=')) {
                const filenameMatch = disposition.match(/filename=(.+)/);
                if (filenameMatch.length > 1) filename = filenameMatch[1];
            }

            // Create a blob and download it
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showAlert('Report downloaded successfully', 'success');
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to download CSV report');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert(error.message || 'An error occurred while downloading the report', 'danger');
    } finally {
        toggleLoading(false);
    }
});

// Set max date for date inputs to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    if (startDate) startDate.max = today;
    if (endDate) endDate.max = today;
    
    // Set initial end date to today and start date to 30 days ago
    if (endDate) endDate.value = today;
    if (startDate) {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
    }
});

// Add input validation for month selection
document.getElementById('monthYear').addEventListener('change', function() {
    const selectedDate = new Date(this.value);
    const today = new Date();
    
    if (selectedDate > today) {
        showAlert('Cannot select a future month', 'warning');
        this.value = today.toISOString().slice(0, 7);
    }
});

// Add validation for date range inputs
document.getElementById('startDate').addEventListener('change', function() {
    const endDate = document.getElementById('endDate');
    if (endDate.value) {
        const validation = validateDates(this.value, endDate.value);
        if (!validation.valid) {
            showAlert(validation.message, 'warning');
            // Reset to 30 days before end date
            const end = new Date(endDate.value);
            const start = new Date(end);
            start.setDate(end.getDate() - 30);
            this.value = start.toISOString().split('T')[0];
        }
    }
});

document.getElementById('endDate').addEventListener('change', function() {
    const startDate = document.getElementById('startDate');
    if (startDate.value) {
        const validation = validateDates(startDate.value, this.value);
        if (!validation.valid) {
            showAlert(validation.message, 'warning');
            this.value = new Date().toISOString().split('T')[0];
        }
    }
});
</script>

<?php include 'Includes/footer.php'; ?>
