<?php
// This is a direct download form that posts directly to downloadRecord.php
// It's a more reliable alternative to redirecting
?>
<!DOCTYPE html>
<html>
<head>
    <title>Download Attendance Report</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
        .download-message { margin: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="download-message">
        <h2>Your download should start automatically...</h2>
        <p>If your download doesn't start within a few seconds, <a href="#" id="submit-link">click here</a>.</p>
    </div>

    <form id="downloadForm" method="post" action="downloadRecord.php" style="display:none;">
        <input type="hidden" name="direct_download" value="1">
        <input type="hidden" name="start" value="<?php echo isset($_GET['start']) ? htmlspecialchars($_GET['start']) : ''; ?>">
        <input type="hidden" name="end" value="<?php echo isset($_GET['end']) ? htmlspecialchars($_GET['end']) : ''; ?>">
        <input type="hidden" name="type" value="<?php echo isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'excel'; ?>">
        <button type="submit">Download</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Submit the form automatically
            document.getElementById('downloadForm').submit();
            
            // Add event listener to the link
            document.getElementById('submit-link').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('downloadForm').submit();
            });
        });
    </script>
</body>
</html>
