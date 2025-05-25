let currentStep = 0;
const form = document.getElementById('resumeForm');
const steps = form.getElementsByClassName('form-step');
const progressSteps = document.getElementsByClassName('step');
const buttonGroup = document.querySelector('.button-group');

function showStep(n) {
    steps[currentStep].classList.remove('active');
    progressSteps[currentStep].classList.remove('active');
    currentStep = n;
    steps[n].classList.add('active');
    progressSteps[n].classList.add('active');
    
    if (currentStep === 0) {
        buttonGroup.classList.add('first-step');
    } else {
        buttonGroup.classList.remove('first-step');
    }
    
    if (currentStep === steps.length - 1) {
        document.getElementById('nextBtn').innerHTML = 'Submit';
    } else {
        document.getElementById('nextBtn').innerHTML = 'Next';
    }
}

function nextPrev(n) {
    if (n === 1 && !validateStep()) return false;
    
    if (currentStep + n >= steps.length) {
        form.submit();
        return false;
    }
    
    showStep(currentStep + n);
}

function validateStep() {
    const currentStepElement = steps[currentStep];
    const inputs = currentStepElement.querySelectorAll('input[required], textarea[required]');
    
    for (let input of inputs) {
        if (!input.reportValidity()) {
            return false; // Stop at the first invalid input
        }
    }
    
    return true; // All inputs are valid
}


function addMoreEducation(button, level) {
    const educationLevel = button.parentElement.parentElement; // Get the parent .education-level div
    const formGroup = educationLevel.querySelector('.education-form-group').cloneNode(true);

    // Clear the input values in the clone
    formGroup.querySelectorAll('input').forEach(input => {
        input.value = '';
    });

    // Append the cloned form group before the button container
    educationLevel.insertBefore(formGroup, button.parentElement);

    // Show the "Remove" button
    educationLevel.querySelector('.remove-btn').style.display = 'inline-block';
}

function removeEducation(button) {
    const educationLevel = button.parentElement.parentElement;
    const formGroups = educationLevel.querySelectorAll('.education-form-group');

    // Remove the last form group
    if (formGroups.length > 1) {
        formGroups[formGroups.length - 1].remove();
    }

    // Hide the "Remove" button if only one form group remains
    if (formGroups.length <= 2) {
        button.style.display = 'none';
    }
}









// UPLOADING
const MAX_PHOTO_SIZE = 10 * 1024 * 1024;

function triggerFileInput(inputId) {
    document.getElementById(inputId).click();
}

function handleFileSelect(input, containerId) {
    const file = input.files[0];
    const container = document.getElementById(containerId);
    const warning = document.getElementById(containerId + '-warning');

    if (!file) return;

    // Check file size limit
    if (file.size > MAX_PHOTO_SIZE) {
        warning.style.display = 'block';
        input.value = ''; // Clear the input
        return;
    }

    warning.style.display = 'none';

    // Remove the upload box but keep the label
    const uploadBox = container.querySelector('.picture');
    if (uploadBox) {
        uploadBox.remove();
    }

    const reader = new FileReader();

    reader.onload = function(e) {
        const photoBox = document.createElement('div');
        photoBox.className = 'photo-box';
        photoBox.innerHTML = `
            <img src="${e.target.result}" class="preview-image">
            <div class="remove-photo" onclick="removePhoto('${containerId}', '${input.id}')">
                <i class="fas fa-times"></i>
            </div>
        `;
        container.appendChild(photoBox);
    }

    reader.readAsDataURL(file);
}

function removePhoto(containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);

    // Clear the uploaded image
    input.value = '';  // Reset the file input

    // Remove the photo box
    const existingPhotoBox = container.querySelector('.photo-box');
    if (existingPhotoBox) {
        existingPhotoBox.remove();
    }

    // Restore the upload box but keep the label
    const uploadBox = document.createElement('div');
    uploadBox.className = 'picture';
    uploadBox.setAttribute('onclick', `triggerFileInput('${inputId}')`);
    uploadBox.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" height="30px" viewBox="0 -960 960 960" width="30px" fill="#ddd">
            <path d="M760-680v-80h-80v-80h80v-80h80v80h80v80h-80v80h-80ZM440-260q75 0 127.5-52.5T620-440q0-75-52.5-127.5T440-620q-75 0-127.5 52.5T260-440q0 75 52.5 127.5T440-260Zm0-80q-42 0-71-29t-29-71q0-42 29-71t71-29q42 0 71 29t29 71q0 42-29 71t-71 29ZM120-120q-33 0-56.5-23.5T40-200v-480q0-33 23.5-56.5T120-760h126l74-80h280v160h80v80h160v400q0 33-23.5 56.5T760-120H120Z"/>
        </svg>
        <span>${containerId === 'resume-photo-upload-container' ? 'Personal Picture' : 'E-Signature'}</span>
    `;
    container.appendChild(uploadBox);
}

