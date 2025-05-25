// Handle form data and UI updates
document.addEventListener('DOMContentLoaded', function() {
    // Initial setup
    updatePreview();
    setupEventListeners();
    loadExistingData();
});

// Setup all event listeners
function setupEventListeners() {
    // Live update as user types
    document.querySelectorAll('.live-input').forEach(input => {
        input.addEventListener('input', function() {
            console.log('Input changed:', this.name, this.value); // Debug log
            updatePreview();
        });
    });

    // Handle signature upload
    const signatureUpload = document.getElementById('signatureUpload');
    if (signatureUpload) {
        signatureUpload.addEventListener('change', handleSignatureUpload);
    }
    
    // Handle consent image upload
    const consentImageUpload = document.getElementById('consentImageUpload');
    if (consentImageUpload) {
        consentImageUpload.addEventListener('change', handleConsentImageUpload);
    }
}

// Handle signature upload
function handleSignatureUpload(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('signaturePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            updatePreview();
        };
        reader.readAsDataURL(file);
    }
}

// Handle consent image upload
function handleConsentImageUpload(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('consentImagePreview');
    const container = preview.parentElement;
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
            updatePreview();
        };
        reader.readAsDataURL(file);
    }
}

// Remove the consent image
function removeConsentImage() {
    const preview = document.getElementById('consentImagePreview');
    const container = preview.parentElement;
    const upload = document.getElementById('consentImageUpload');
    
    preview.src = '';
    container.style.display = 'none';
    upload.value = '';
    updatePreview();
}

// Update preview content in real-time
function updatePreview() {
    const previewContent = document.getElementById('contentArea');
    if (previewContent) {
        const content = generateLetterContent();
        console.log('Updating preview with content:', content); // Debug log
        previewContent.innerHTML = content;
    } else {
        console.error('Preview content element not found!'); // Debug log
    }
}

// Generate the letter content
function generateLetterContent() {
    const formData = getFormData();
    const signaturePreview = document.getElementById('signaturePreview');
    const signatureHtml = signaturePreview && signaturePreview.style.display !== 'none' 
        ? `<img src="${signaturePreview.src}" alt="Signature" style="max-width: 200px; max-height: 100px;">` 
        : '_________________';
    
    // Get the ID image
    const consentImagePreview = document.getElementById('consentImagePreview');
    const consentImageHtml = consentImagePreview && consentImagePreview.src 
        ? `<div style="text-align: center; margin-top: 40px; page-break-before: always;">
             <h3 style="font-family: Arial, sans-serif; font-size: 16px; margin-bottom: 10px;">Valid ID</h3>
             <img src="${consentImagePreview.src}" alt="Valid ID" style="max-width: 80%; max-height: 500px; border: 1px solid #ccc; padding: 5px;">
           </div>` 
        : '';
    
    // Debug log
    console.log('Form Data:', formData);

    const content = `
        <div class="letter-content" style="padding: 20px; font-family: 'Times New Roman', Times, serif;">
            <p style="text-align: justify; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                &emsp;&emsp;&emsp;This is to certify that I am allowing <strong>${formData.studentName || '(STUDENT NAME)'}</strong> to go on an internship in 
                <strong>${formData.companyName || '(COMPANY NAME)'}</strong> for <strong>${formData.weeks || '(NO. OF WEEKS)'}</strong> weeks from 
                <strong>${formData.startDate || '(STARTING MONTH)'}</strong> to 
                <strong>${formData.endDate || '(END MONTH)'}</strong> at 
                <strong>${formData.hours || '(REQUIRED HOURS)'}</strong> in partial fulfilment of the requirements for the degree in 
                <strong>${formData.course || '(COURSE)'}</strong>.
            </p>

            <p style="text-align: justify; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                &emsp;&emsp;&emsp;I also allow 
                <strong>${formData.studentName || '(STUDENT NAME)'}</strong> to go on field work if the job assigned requires such.
            </p>

            <p style="text-align: justify; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
                &emsp;&emsp;&emsp;It is understood that 
                <strong>${formData.studentName || '(STUDENT NAME)'}</strong> will follow the policies and guidelines set by the University, and abide by the rules and regulations that may be imposed by the school's Cooperating Teacher for his/her welfare and safety.
            </p>

            <p style="text-align: justify; font-size: 16px; line-height: 1.6; margin-bottom: 40px;">
                &emsp;&emsp;&emsp;I fully agree to waive, release and discharge University of Caloocan City and the Cooperating Teacher in case of any untoward incident that may happen in the duration of the internship.
            </p>

            <div style="margin-top: 40px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; padding: 10px; text-align: left; font-family: Arial, sans-serif; font-size: 14px;">
                            <strong>Signature (with proper identification)</strong>
                        </td>
                        <td style="width: 50%; padding: 10px; text-align: left;">
                            ${signatureHtml}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; text-align: left; font-family: Arial, sans-serif; font-size: 14px;">
                            <strong>Name of Signatory</strong>
                        </td>
                        <td style="padding: 10px; text-align: left;">
                            ${formData.parentName || '(PARENT NAME)'}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; text-align: left; font-family: Arial, sans-serif; font-size: 14px;">
                            <strong>Relationship of Signatory to Intern</strong>
                        </td>
                        <td style="padding: 10px; text-align: left;">
                            ${formData.relationship || '(RELATION TO INTERN)'}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; text-align: left; font-family: Arial, sans-serif; font-size: 14px;">
                            <strong>Date Signed</strong>
                        </td>
                        <td style="padding: 10px; text-align: left;">
                            ${new Date().toLocaleDateString('en-US', {
                                month: 'long',
                                day: 'numeric',
                                year: 'numeric'
                            })}
                        </td>
                    </tr>
                </table>
            </div>
            
            ${consentImageHtml}
        </div>
    `;

    return content;
}

// Get all form data
function getFormData() {
    const formElements = {
        studentName: document.querySelector('input[name="studentName"]'),
        companyName: document.querySelector('input[name="companyName"]'),
        weeks: document.querySelector('input[name="weeks"]'),
        startDate: document.querySelector('input[name="startDate"]'),
        endDate: document.querySelector('input[name="endDate"]'),
        hours: document.querySelector('input[name="hours"]'),
        course: document.querySelector('select[name="course"]'),
        parentName: document.querySelector('input[name="parentName"]'),
        relationship: document.querySelector('input[name="relationship"]')
    };

    // Debug log
    console.log('Form Elements:', formElements);

    const formData = {};
    for (const [key, element] of Object.entries(formElements)) {
        formData[key] = element?.value || '';
    }

    return formData;
}

// Save form data to server
function saveForm() {
    const form = document.getElementById('consentForm');
    if (!form) {
        console.error('Form not found!');
        return;
    }

    const formData = new FormData(form);
    
    // Add the signature file if it exists
    const signatureFile = document.getElementById('signatureUpload').files[0];
    if (signatureFile) {
        formData.append('signatureUpload', signatureFile);
    }
    
    // Add the consent image file if it exists
    const consentImageFile = document.getElementById('consentImageUpload').files[0];
    if (consentImageFile) {
        formData.append('consentImageUpload', consentImageFile);
    }
    
    // Debug log
    console.log('Saving form...');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        console.log('Save result:', result);
        alert('Form saved successfully!');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving form data');
    });
}

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
            
            /* Force appropriate sizing */
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Style the main content */
            .letter-content {
                padding: 20mm 25mm !important;
                box-sizing: border-box;
            }
            
            /* Adjust font sizes and spacing */
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
    console.log('Loading existing data...'); // Debug log
    const formElements = document.querySelectorAll('.live-input');
    formElements.forEach(element => {
        const name = element.getAttribute('name');
        const value = element.value;
        console.log(`Loading ${name}: ${value}`); // Debug log
        if (value) {
            if (element.tagName === 'SELECT') {
                const option = element.querySelector(`option[value="${value}"]`);
                if (option) {
                    option.selected = true;
                }
            } else {
                element.value = value;
            }
        }
    });
    updatePreview();
}