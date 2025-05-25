// Function to validate form before saving
function saveForm() {
    const form = document.getElementById('letterForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    });

    if (!isValid) {
        alert('Please fill in all required fields.');
        return;
    }

    // Collect form data
    const formData = new FormData(form);

    // Send data to server using AJAX
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the form.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Live input preview functionality
    const liveInputs = document.querySelectorAll('.live-input');
    
    liveInputs.forEach(input => {
        input.addEventListener('input', updatePreview);
    });

    // Initial preview update
    updatePreview();
});

function updatePreview() {
    const contentArea = document.getElementById('contentArea');
    const formData = {
        department: document.querySelector('input[name="department"]').value,
        date: document.querySelector('input[name="date"]').value,
        company: document.querySelector('input[name="company"]').value,
        studentName: document.querySelector('input[name="studentName"]').value,
        course: document.querySelector('select[name="course"]').value,
        hours: document.querySelector('input[name="hours"]').value,
        coordinatorName: document.querySelector('input[name="coordinatorName"]').value
    };

    // Only update preview if all required fields are filled
    const requiredFields = document.querySelectorAll('[required]');
    const allFieldsFilled = Array.from(requiredFields).every(field => field.value.trim() !== '');

    if (allFieldsFilled) {
        contentArea.innerHTML = `
            <div style="text-align: left; margin: 20px 0;">
                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px;">${getCurrentDate()}</p>

                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px;">
                    <strong>${formData.company}</strong><br>
                    Human Resources Department
                </p>

                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px;">
                    <strong>Dear Sir/Madam:</strong>
                </p>

                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; text-indent: 50px;">
                    We are writing to endorse <strong>${formData.studentName}</strong>, a ${formData.course} student 
                    of the University of Caloocan City, for On-the-Job Training (OJT) in your esteemed organization. 
                    The student is required to complete ${formData.hours} hours of practical training as part of 
                    the curriculum.
                </p>

                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; text-indent: 50px;">
                    We would greatly appreciate your consideration in accepting our student for OJT. 
                    Should you require any further information, please do not hesitate to contact our 
                    OJT Coordinator, ${formData.coordinatorName}.
                </p>

                <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; text-indent: 50px;">
                    Thank you for your support in our students' professional development.
                </p>

                <div style="margin-top: 50px;">
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px;">
                        Sincerely,
                    </p>
                    <p style="font-family: 'Times New Roman', Times, serif; font-size: 14px; margin-top: 50px;">
                        <strong>${formData.coordinatorName}</strong><br>
                        OJT Coordinator<br>
                        ${formData.department}
                    </p>
                </div>
            </div>
        `;
    }
}

function generatePDF() {
    // Check if all required fields are filled
    const form = document.getElementById('letterForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    });

    if (!isValid) {
        alert('Please fill in all required fields.');
        return;
    }

    // Get student name for PDF filename
    const studentName = document.querySelector('input[name="studentName"]').value;
    const element = document.getElementById('previewContent');
    
    html2pdf().from(element).save(`OJT_Endorsement_${studentName}.pdf`);
}

// Helper function to get current date in a readable format
function getCurrentDate() {
    const options = { year: 'long', month: 'long', day: 'numeric' };
    return new Date().toLocaleDateString('en-US', options);
}