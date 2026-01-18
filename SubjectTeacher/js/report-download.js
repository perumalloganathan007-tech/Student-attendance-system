/**
 * Helper functions for attendance report downloads
 */

/**
 * Initiates a download for attendance reports
 * @param {string} fromDate - Start date in YYYY-MM-DD format
 * @param {string} toDate - End date in YYYY-MM-DD format
 * @param {string} type - Export type ('excel' or 'pdf')
 */
function downloadReport(fromDate, toDate, type = 'excel') {
    // Create a form element
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'downloadRecord.php';
    form.style.display = 'none';
    
    // Add the parameters
    const params = {
        'direct_download': '1',
        'start': fromDate,
        'end': toDate,
        'type': type
    };
    
    // Create input elements for each parameter
    for (const key in params) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = params[key];
        form.appendChild(input);
    }
    
    // Add the form to the document body
    document.body.appendChild(form);
    
    // Submit the form
    form.submit();
    
    // Clean up
    setTimeout(() => {
        document.body.removeChild(form);
    }, 1000);
}

// Add event listeners to download buttons when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Find all download buttons with data attributes
    const downloadButtons = document.querySelectorAll('[data-download-report]');
    
    downloadButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const fromDate = this.getAttribute('data-from-date');
            const toDate = this.getAttribute('data-to-date');
            const type = this.getAttribute('data-export-type') || 'excel';
            
            if (fromDate && toDate) {
                downloadReport(fromDate, toDate, type);
            }
        });
    });
});
