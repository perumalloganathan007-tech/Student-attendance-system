// Check if PDF export is selected but TCPDF isn't installed
document.addEventListener('DOMContentLoaded', function() {
    var exportType = document.getElementById('exportType');
    if (exportType) {
        exportType.addEventListener('change', function() {
            // Check if the selected option has the data-requires-tcpdf attribute
            var selectedOption = exportType.options[exportType.selectedIndex];
            if (selectedOption.getAttribute('data-requires-tcpdf')) {
                // Show warning
                if (confirm('PDF export requires the TCPDF library which appears to be missing.\n\nDo you want to install it now? (Opens in a new tab)')) {
                    window.open('../install_tcpdf.php', '_blank');
                }
            }
        });
    }
});
