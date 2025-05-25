// Add this to templateScript/EndorsementLetter.js

function generateFormContent() {
    const letterDate = new Date(document.querySelector('input[name="date"]').value)
        .toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });

    const formData = {
        department: document.querySelector('input[name="department"]').value,
        date: letterDate,
        company: document.querySelector('input[name="company"]').value,
        studentName: document.querySelector('input[name="studentName"]').value,
        course: document.querySelector('select[name="course"]').value,
        hours: document.querySelector('input[name="hours"]').value,
        coordinatorName: document.querySelector('input[name="coordinatorName"]').value
    };

    return `
        <div style="text-align: left; font-family: 'Times New Roman', Times, serif; font-size: 16px;">
            <p style="text-align: center;"><strong>${formData.department || '(DEPARTMENT)'}</strong></p>
            <p>________________________________________________________________________________________</p>
            
            <p>Date: ${formData.date}</p>
            
            <p><strong>${formData.company || '(COMPANY)'}</strong></p>
            
            <p>Dear Sir/Madam,</p>
            
            <p>Warm greetings from the University of Caloocan City -- Congress Campus.</p>
            
            <p style="text-align: justify;">&emsp;&emsp;&emsp;As it has been every learning institution's quest for excellence especially in its products, the University of Caloocan City -- ${formData.department || '(DEPARTMENT)'} requests that <strong>${formData.studentName || '(NAME)'}</strong>, a <strong>${formData.course || '(COURSE)'}</strong> student be accommodated and given on-the-job training for <strong>${formData.hours || '(NO. HOURS)'}</strong> hours as partial fulfillment of the requirements for the course.</p>
            
            <p style="text-align: justify;">&emsp;&emsp;&emsp;We anticipate that the student will be provided with comprehensive hands-on experience in tasks related to their specific field. He/She will also be inquiring on your institution's profile and taking photos which will be submitted to the undersigned as part of their practicum documentation.</p>
            
            <p style="text-align: justify;">&emsp;&emsp;&emsp;The University wished to thank you for whatever assistance your institution can extend to him/her. Be assured that the best outcome of our student-trainee will signify our cooperation with each other and that he/she, together with us in the university, consider that agendum for our debt of gratitude to your institution.</p>
            
            <br>
            <p>With utmost sincerity,</p>
            
            <br><br>
            <p><strong>${formData.coordinatorName || '(COORDINATOR NAME)'}</strong></p>
            <p style="margin-top: -15px;">Program Coordinator</p>
        </div>
    `;
}

// Function to save form data
async function saveForm() {
    const formData = new FormData(document.getElementById('letterForm'));
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.text();
        alert(result);
    } catch (error) {
        console.error('Error:', error);
        alert('Error saving the form. Please try again.');
    }
}

// Function to generate PDF
function generatePDF() {
    // Update the content area with the formatted content
    document.getElementById('contentArea').innerHTML = generateFormContent();
    
    // Create PDF using window.print() with specific print styles
    const printStyles = `
        <style>
            @media print {
                body * {
                    visibility: hidden;
                }
                .preview-content, .preview-content * {
                    visibility: visible;
                }
                .preview-content {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
                .button-container, .form-container {
                    display: none;
                }
                @page {
                    size: A4;
                    margin: 0;
                }
                .page {
                    margin: 0;
                    padding: 20px;
                }
            }
        </style>
    `;
    
    // Add print styles to head
    document.head.insertAdjacentHTML('beforeend', printStyles);
    
    // Trigger print dialog
    window.print();
}

// Update preview content in real-time as user types
document.querySelectorAll('.live-input').forEach(input => {
    input.addEventListener('input', () => {
        document.getElementById('contentArea').innerHTML = generateFormContent();
    });
});

// Initial content load
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('contentArea').innerHTML = generateFormContent();
});