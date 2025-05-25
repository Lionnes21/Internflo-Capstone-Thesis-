// Add this at the beginning of your file
const printStyles = `
@media print {
    @page {
        margin: 0;
        size: auto;
    }
    
    /* Hide browser header/footer */
    html {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    body {
        margin: 1cm !important;
        padding: 0 !important;
    }
    
    /* Hide URL, date, and page numbers */
    body::before, body::after {
        display: none !important;
    }
    
    /* Support page breaks */
    div[style*="page-break-before: always"] {
        page-break-before: always;
    }
}
`;

// Add this function to inject print styles
function injectPrintStyles() {
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = printStyles;
    document.head.appendChild(styleSheet);
}

// Handle form data and UI updates
document.addEventListener('DOMContentLoaded', function() {
    // Initial setup
    updatePreview();
    setupEventListeners();
    setupImagePreviewListeners();
});

// Setup all event listeners
function setupEventListeners() {
    // Live update as user types
    document.querySelectorAll('.live-input').forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview); // Add change event for date inputs
    });

    // Form submission handler
    const form = document.getElementById('reportForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

// Setup image preview listeners
function setupImagePreviewListeners() {
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const previewContainer = this.nextElementSibling;
            previewContainer.innerHTML = '';
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    img.style.maxWidth = '200px';
                    img.style.maxHeight = '150px';
                    img.style.marginTop = '10px';
                    previewContainer.appendChild(img);
                    
                    // Update the preview
                    updatePreview();
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

// Update preview content in real-time
function updatePreview() {
    const contentArea = document.getElementById('contentArea');
    if (contentArea) {
        const content = generateReportContent();
        contentArea.innerHTML = content;
    }
}

// Get all form data
function getFormData() {
    const formData = {
        practicumerName: document.querySelector('input[name="practicumerName"]')?.value || '',
        course: document.querySelector('input[name="course"]')?.value || '',
        company: document.querySelector('input[name="company"]')?.value || '',
        department: document.querySelector('input[name="department"]')?.value || '',
        inclusiveDateFrom: document.querySelector('input[name="inclusiveDateFrom"]')?.value || '',
        inclusiveDateTo: document.querySelector('input[name="inclusiveDateTo"]')?.value || '',
        weekNumber: document.querySelector('select[name="weekNumber"]')?.value || '',
        timeIn: document.querySelector('input[name="timeIn"]')?.value || '',
        timeOut: document.querySelector('input[name="timeOut"]')?.value || '',
        weeklyReport: document.querySelector('textarea[name="weeklyReport"]')?.value || '',
        preparedBy: document.querySelector('input[name="preparedBy"]')?.value || document.querySelector('input[name="practicumerName"]')?.value || '',
        certifiedBy: document.querySelector('input[name="certifiedBy"]')?.value || '',
        images: []
    };
    
    // Get multiple images from image entries
    const imageEntries = document.querySelectorAll('.image-entry');
    imageEntries.forEach((entry, index) => {
        const dateInput = entry.querySelector('input[type="date"]');
        const fileInput = entry.querySelector('input[type="file"]');
        const previewImg = entry.querySelector('.image-preview');
        
        if (dateInput && fileInput && fileInput.files && fileInput.files[0]) {
            formData.images.push({
                date: dateInput.value,
                file: fileInput.files[0],
                url: URL.createObjectURL(fileInput.files[0])
            });
        } else if (dateInput && previewImg && previewImg.src) {
            formData.images.push({
                date: dateInput.value,
                url: previewImg.src
            });
        }
    });
    
    // Get existing images
    const existingImages = document.querySelectorAll('.existing-image-item');
    existingImages.forEach(item => {
        const img = item.querySelector('img');
        const dateText = item.querySelector('p').textContent.replace('Date: ', '');
        
        if (img && img.src) {
            formData.images.push({
                date: dateText,
                url: img.src
            });
        }
    });
    
    return formData;
}

// Format date to readable format
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
}

// Format time from 24-hour to 12-hour format
function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const formattedHour = hour % 12 || 12;
    
    return `${formattedHour}:${minutes} ${ampm}`;
}

// Generate the report content
function generateReportContent() {
    const formData = getFormData();
    
    // Format the date range
    let formattedDateRange = '';
    if (formData.inclusiveDateFrom && formData.inclusiveDateTo) {
        const dateFrom = formatDate(formData.inclusiveDateFrom);
        const dateTo = formatDate(formData.inclusiveDateTo);
        formattedDateRange = `${dateFrom} to ${dateTo}`;
    } else {
        formattedDateRange = '_______________________';
    }
    
    // Convert the weeklyReport text to preserve line breaks
    const formattedReport = formData.weeklyReport.replace(/\n/g, '<br>');
    
    // Format time
    const formattedTimeIn = formData.timeIn ? formatTime(formData.timeIn) : '_______________________';
    const formattedTimeOut = formData.timeOut ? formatTime(formData.timeOut) : '';
    
    // Create content for first page
    let content = `
        <div style="text-align: left; font-family: 'Times New Roman', Times, serif; font-size: 16px; padding: 20px;">
            <div style="margin-bottom: 20px;">
                <p style="margin: 5px 0;">Practicumer: <u>${formData.practicumerName || '_______________________'}</u></p>
                <p style="margin: 5px 0;">Course: <u>${formData.course || '_______________________'}</u></p>
                <p style="margin: 5px 0;">Company: <u>${formData.company || '_______________________'}</u></p>
                <p style="margin: 5px 0;">Department: <u>${formData.department || '_______________________'}</u></p>
                <p style="margin: 5px 0;">Inclusive Date: <u>${formattedDateRange}</u></p>
                <p style="margin: 5px 0;">Time In: <u>${formattedTimeIn} / Time Out: ${formattedTimeOut}</u></p>
                <p style="margin: 5px 0;">Week Number: <u>Week ${formData.weekNumber || '_______'}</u></p>     
                
            </div>
            
            <h3 style="text-align: center; margin: 20px 0;">Weekly Accomplishment Report</h3>
            
            <div style="border: 1px solid black; padding: 15px; min-height: 300px; margin: 20px 0; white-space: pre-line;">
                ${formattedReport || 'Type your weekly report here...'}
            </div>
            
            <div style="margin-top: 30px;">
                <p style="margin: 5px 0;">Prepared by: <u>${formData.preparedBy || formData.practicumerName || '_______________________'}</u></p>
                <p style="margin: 0; font-size: 12px; ">(Name of the Practicumer)</p>
                
                <p style="margin: 20px 0 5px 0;">Certified by: <u>${formData.certifiedBy || '_______________________'}</u></p>
                <p style="margin: 0; font-size: 12px; ">(Name of Supervisor)</p>
            </div>`;
    
    // Add a new page for supporting documents/images if we have any images
    if (formData.images && formData.images.length > 0) {
        content += `
            <div style="page-break-before: always; margin: 20px 0;">
            <h3 style="text-align: center; margin: 20px 0;">Documentation Photos</h3>
            <div style="display: flex; flex-wrap: wrap; justify-content: space-around;">`;
        
        // Add each image with its date
        formData.images.forEach((image, index) => {
            const dateText = image.date ? formatDate(image.date) : '';
            
            content += `
                <div style="margin: 10px; text-align: center; width: 45%; max-width: 250px;">
                    <img src="${image.url}" alt="Report Image" style="max-width: 100%; height: auto; max-height: 200px;">
                    <p style="margin: 5px 0;">Date: ${dateText}</p>
                </div>`;
        });
        
        content += `
            </div>
        </div>`;
    }
    
    content += `</div>`;
    
    return content;
}

// Handle form submission
function handleFormSubmit(event) {
    if (event) {
        event.preventDefault();
    }
    saveForm();
}

// Save form data to server
function saveForm() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    // Create an AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'AR_template.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            if (xhr.responseText === 'success') {
                alert('Report saved successfully!');
                // Optional: Reload the page to show the latest data
                window.location.reload();
            } else {
                alert('Error: ' + xhr.responseText);
            }
        } else {
            alert('Request failed. Status: ' + xhr.status);
        }
    };
    
    xhr.send(formData);
}

// Generate PDF for printing
function generatePDF() {
    updatePreview();
    
    // Create and append print-specific styles
    const styleSheet = document.createElement('style');
    styleSheet.media = 'print';
    styleSheet.textContent = `
        @page {
            size: A4;
            margin: 0.5in;
        }
        
        @media print {
            /* Hide browser headers/footers */
            @page {
                margin: 0;
            }
            
            /* Hide any system-generated headers/footers */
            head, header, footer {
                display: none !important;
            }
            
            body::before,
            body::after {
                content: none !important;
            }
            
            /* Basic styling */
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Style the content */
            .letter-content {
                padding: 20mm 25mm !important;
                box-sizing: border-box;
            }
            
            /* Support page breaks */
            div[style*="page-break-before: always"] {
                page-break-before: always;
            }
            
            /* Hide elements not needed in print */
            .button-container, 
            .form-container,
            *[class*="filepath"],
            *[class*="button-container"],
            *[class*="page-number"],
            *[id*="filepath"],
            *[id*="page-number"] {
                display: none !important;
            }
        }
    `;
    document.head.appendChild(styleSheet);
    
    // Trigger print
    window.print();
    
    // Remove the style sheet after printing
    setTimeout(() => {
        document.head.removeChild(styleSheet);
    }, 1000);
}

// Initialize on page load
window.addEventListener('load', function() {
    updatePreview();
    loadExistingData();
    injectPrintStyles();
});

// Load existing data if available
function loadExistingData() {
    const formElements = document.querySelectorAll('.live-input');
    formElements.forEach(element => {
        if (element.value) {
            if (element.tagName === 'TEXTAREA') {
                element.textContent = element.value;
            }
            updatePreview();
        }
    });
}