// Handle form data and UI updates
document.addEventListener('DOMContentLoaded', function() {
    // Initial setup
    updatePreview();
    setupEventListeners();
});

// Setup all event listeners
function setupEventListeners() {
    // Live update as user types
    document.querySelectorAll('.live-input').forEach(input => {
        input.addEventListener('input', updatePreview);
    });

    // Form submission handler
    const form = document.getElementById('letterForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

// Update preview content in real-time
function updatePreview() {
    const previewContent = document.getElementById('contentArea');
    if (previewContent) {
        previewContent.innerHTML = generateLetterContent();
    }
}

// Get all form data from the OJT Pull-out Letter form
function getFormData() {
    return {
        department: document.querySelector('input[name="department"]')?.value?.trim() || '',
        date: document.querySelector('input[name="date"]')?.value?.trim() || '',
        company: document.querySelector('input[name="company"]')?.value?.trim() || '',
        studentName: document.querySelector('input[name="studentName"]')?.value?.trim() || '',
        reasonPullout: document.querySelector('textarea[name="reason-pullout"]')?.value?.trim() || '',
        coordinatorName: document.querySelector('input[name="coordinatorName"]')?.value?.trim() || ''
    };
}

// Generate the letter content
function generateLetterContent() {
    const formData = getFormData();

    // Create the letter content
    return `
        <div style="text-align: left; font-family: 'Times New Roman', Times, serif; font-size: 16px;">
            <p style="text-align: center;"><strong>${formData.department || '(DEPARTMENT)'}</strong></p>
            <p>________________________________________________________________________________________</p>
            <h3 style="text-align: center; font-weight:bold;">OJT Pull-out Letter</h3>
            <p>Date: ${formatDate(formData.date)}</p>
            
            <p><strong>${formData.company || '(COMPANY)'}</strong></p>
            
            <p>Dear Sir/Madam,</p>
            
            <p>&emsp;&emsp;&emsp;Greetings!</p>
            
            <p style="text-align: justify;">Much as we wanted that our student <strong>${formData.studentName || '(STUDENT NAME)'}</strong>, continue his/her On-The-Job work in your good office,
            we are deemed to pull him/her out due to ${formData.reasonPullout || '(Reason for Pull-out)'} reason.
            He/She preferred to finish her OJT at another company. Nevertheless, we thank you for your assistance extended to him/her during his/her stay in your office.</p>
            
            <br>
            <p>Very sincerely yours,</p>
            
            <br><br>
            <p><strong>${formData.coordinatorName || '(COORDINATOR NAME)'}</strong></p>
            <p style="margin-top: -15px;">Practicum Coordinator</p>
        </div>
    `;
}

// Format date to long format (e.g., January 18, 2024)
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
}


// Handle form submission
function handleFormSubmit(event) {
    event.preventDefault();
    saveForm();
}

// Save form data to server
function saveForm() {
    const formData = new FormData(document.getElementById('letterForm'));
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        alert('Form saved successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving form data');
    });
}

// Generate PDF for printing
function generatePDF() {
    // Ensure preview is up to date
    updatePreview();
    
    // Add print-specific styles
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
            
            /* Force single page */
            html, body {
                height: 100%;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Style the main content for single page */
            .letter-content {
                padding: 20mm 25mm !important;
                max-height: 100vh;
                box-sizing: border-box;
                page-break-after: avoid;
                page-break-before: avoid;
            }
            
            /* Adjust font sizes and spacing for single page fit */
            .letter-content p {
                margin-bottom: 15px !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
            }
            
            /* Adjust table spacing */
            .letter-content table {
                margin-top: 20px !important;
                page-break-inside: avoid;
            }
            
            .letter-content table td {
                padding: 5px 10px !important;
                font-size: 12px !important;
            }
            
            /* Ensure header fits */
            .letter-content .header {
                margin-bottom: 20px !important;
            }
            
            .letter-content .header h2 {
                font-size: 16px !important;
                margin: 5px 0 !important;
            }
            
            .letter-content .header h3 {
                font-size: 14px !important;
                margin: 5px 0 !important;
            }
            
            /* Hide filepath and page numbers */
            *[class*="filepath"],
            *[class*="page-number"],
            *[class*="button-container"],
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

// Load existing data if available
function loadExistingData() {
    const formElements = document.querySelectorAll('.live-input');
    formElements.forEach(element => {
        const name = element.getAttribute('name');
        const value = element.value;
        if (value) {
            if (element.tagName === 'TEXTAREA') {
                element.textContent = value;
            } else {
                element.value = value;
            }
        }
    });
    updatePreview();
}

// Initialize on page load
window.addEventListener('load', loadExistingData);