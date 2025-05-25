document.addEventListener("DOMContentLoaded", function () {
  let currentStep = 1;
  const totalSteps = 3;

  const previousButton = document.getElementById("previousButton");
  const continueButton = document.getElementById("continueButton");

  function updateButtons() {
    previousButton.disabled = currentStep === 1;

    if (currentStep === totalSteps) {
      continueButton.innerHTML =
        'Submit Application <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Zm0-243.82q-34.46 0-59.16-24.55-24.69-24.54-24.69-59.01 0-34.46 24.69-59.04 24.7-24.58 59.16-24.58 34.47 0 59.02 24.55Q320-514.5 320-480.03q0 34.46-24.54 59.04-24.55 24.58-59.02 24.58Zm0-243.59q-34.46 0-59.16-24.54-24.69-24.55-24.69-59.02 0-34.46 24.69-59.16 24.7-24.69 59.16-24.69 34.47 0 59.02 24.69Q320-758.02 320-723.56q0 34.47-24.54 59.02Q270.91-640 236.44-640Zm243.59 0q-34.46 0-59.04-24.54-24.58-24.55-24.58-59.02 0-34.46 24.55-59.16 24.54-24.69 59.01-24.69 34.46 0 59.04 24.69 24.58 24.7 24.58 59.16 0 34.47-24.55 59.02Q514.5-640 480.03-640Zm243.53 0q-34.47 0-59.02-24.54Q640-689.09 640-723.56q0-34.46 24.54-59.16 24.55-24.69 59.02-24.69 34.46 0 59.16 24.69 24.69 24.7 24.69 59.16 0 34.47-24.69 59.02Q758.02-640 723.56-640ZM480.03-396.41q-34.46 0-59.04-24.55-24.58-24.54-24.58-59.01 0-34.46 24.55-59.04 24.54-24.58 59.01-24.58 34.46 0 59.04 24.55 24.58 24.54 24.58 59.01 0 34.46-24.55 59.04-24.54 24.58-59.01 24.58Zm38.54 198.32v-65.04q0-9.2 3.47-17.53 3.48-8.34 10.2-15.06l208.76-208q9.72-9.76 21.59-14.09 11.88-4.34 23.76-4.34 12.95 0 24.8 4.86 11.85 4.86 21.55 14.57l37 37q8.67 9.72 13.55 21.6 4.88 11.87 4.88 23.75 0 12.2-4.36 24.41-4.36 12.22-14.07 21.94l-208 208q-6.69 6.72-15.04 10.07-8.36 3.36-17.55 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.17-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/></svg>';
      continueButton.classList.add("submit-button");
    } else {
      continueButton.innerHTML =
        'Continue <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg>';
      continueButton.classList.remove("submit-button");
    }
  }

  function updateStepVisibility() {
    for (let i = 1; i <= totalSteps; i++) {
      const stepContent = document.getElementById(`step${i}-content`);
      if (stepContent) {
        stepContent.style.display = "none";
      }
    }

    const currentContent = document.getElementById(
      `step${currentStep}-content`
    );
    if (currentContent) {
      currentContent.style.display = "block";
    }
  }

  function updateStepperIndicators() {
    for (let i = 1; i <= totalSteps; i++) {
      const stepIndicator = document.getElementById(`step${i}`);
      if (stepIndicator) {
        stepIndicator.classList.remove("active", "completed");
        if (i < currentStep) {
          stepIndicator.classList.add("completed");
        } else if (i === currentStep) {
          stepIndicator.classList.add("active");
        }
      }
    }
  }

  function validateAllStep1Fields() {
    let isValid = true;

    // Validate text inputs
    const inputs = document.querySelectorAll(
      '#step1-content input[type="text"], #step1-content input[type="email"]'
    );
    inputs.forEach((input) => {
      const errorMessage = input.nextElementSibling;
      if (input.value.trim() === "") {
        input.classList.add("input-error");
        input.classList.remove("valid");
        if (errorMessage) errorMessage.style.display = "block";
        isValid = false;
      }
    });

    // Validate file inputs
    const requiredFiles = [
      { inputId: "cvUpload", sectionId: "cvUploadSection" },
      { inputId: "endorsementUpload", sectionId: "endorsementUploadSection" },
    ];

    requiredFiles.forEach((file) => {
      const input = document.getElementById(file.inputId);
      const section = document.getElementById(file.sectionId);
      const errorMessage = section.querySelector(".error-message");

      if (!input.files || input.files.length === 0) {
        section.classList.add("error");
        if (errorMessage) errorMessage.style.display = "block";
        isValid = false;
      }
    });

    return isValid;
  }

function goToNextStep() {
    if (currentStep === 1) {
        if (!validateAllStep1Fields()) {
            return;
        }
    }

    // Step 2 validation
    if (currentStep === 2) {
      const assessmentScoreInputs = document.querySelectorAll('input[name="assessment_score"]');
      let allAssessmentsCompleted = true;
      const validateMessage = document.getElementById('assessment-validate-message');
      const completedMessage = document.getElementById('assessment-completed-message');

      assessmentScoreInputs.forEach(input => {
          if (input.value === "0/0") {
              allAssessmentsCompleted = false;
          }
      });

      if (!allAssessmentsCompleted) {
          if (validateMessage) {
              validateMessage.style.display = 'inline-block';
          }
          if (completedMessage) {
              completedMessage.classList.remove('visible');
          }
          return;
      } else {
          if (validateMessage) {
              validateMessage.style.display = 'none';
          }
          if (completedMessage) {
              completedMessage.classList.add('visible');
          }
      }
  }
    

    if (currentStep < totalSteps) {
        currentStep++;
        updateStepVisibility();
        updateButtons();
        updateStepperIndicators();
    }
}
  function goToPreviousStep() {
    if (currentStep > 1) {
      currentStep--;
      updateStepVisibility();
      updateButtons();
      updateStepperIndicators();
    }
  }

  function handleContinueClick(e) {
    e.preventDefault(); // Prevent default submit action

    if (currentStep === totalSteps) {
      // Only submit if we are on the final step
      document.getElementById("applicationForm").submit();
    } else {
      goToNextStep();
    }
  }

  // Handle input changes
  const step1Inputs = document.querySelectorAll(
    '#step1-content input[type="text"], #step1-content input[type="email"]'
  );

  step1Inputs.forEach((input) => {
    let hasInput = false;

    input.addEventListener("input", function () {
      const errorMessage = input.nextElementSibling;
      hasInput = true;

      if (input.value.trim() !== "") {
        input.classList.remove("input-error");
        input.classList.add("valid");
        if (errorMessage) errorMessage.style.display = "none";
      } else {
        input.classList.remove("valid");
        input.classList.add("input-error");
        if (errorMessage) errorMessage.style.display = "block";
      }
    });

    input.addEventListener("blur", function () {
      if (hasInput) {
        if (input.value.trim() === "") {
          input.classList.remove("valid");
          input.classList.add("input-error");
        } else {
          input.classList.remove("valid");
        }
      }
    });
  });

  continueButton.addEventListener("click", handleContinueClick);
  previousButton.addEventListener("click", goToPreviousStep);

  // Initialize stepper
  updateButtons();
  updateStepVisibility();
  updateStepperIndicators();

  // File handling functions
  function handleFileSelect(inputId, previewId, fileNameId, sectionId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const fileName = document.getElementById(fileNameId);
    const section = document.getElementById(sectionId);
    const errorMessage = section.querySelector('.error-message');

    input.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const file = this.files[0];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (fileExtension !== 'pdf') {
                // Show error state for non-PDF files
                fileName.textContent = '';
                preview.classList.remove('show');
                section.classList.remove('has-file');
                section.classList.add('error');
                if (errorMessage) {
                    errorMessage.style.display = 'block';
                    errorMessage.textContent = 'Only accept file of pdf';
                }
                
                // Clear the input
                this.value = '';
                return;
            }

            // Valid PDF file
            fileName.textContent = file.name;
            preview.classList.add('show');
            section.classList.add('has-file');
            section.classList.remove('error');
            if (errorMessage) errorMessage.style.display = 'none';
        }
    });
}

  window.removeFile = function (type) {
    const input = document.getElementById(`${type}Upload`);
    const preview = document.getElementById(`${type}Preview`);
    const section = document.getElementById(`${type}UploadSection`);
    const fileName = document.getElementById(`${type}FileName`);

    input.value = "";
    preview.classList.remove("show");
    section.classList.remove("has-file");
    fileName.textContent = "";
  };

  // Initialize file handlers
  handleFileSelect("cvUpload", "cvPreview", "cvFileName", "cvUploadSection");
  handleFileSelect(
    "endorsementUpload",
    "endorsementPreview",
    "endorsementFileName",
    "endorsementUploadSection"
  );
});

function allowOnlyNumbers(event) {
  // Get the character code from the event
  const charCode = event.which ? event.which : event.keyCode;

  // Allow only numbers (0-9)
  if (charCode < 48 || charCode > 57) {
    // Prevent all non-numeric keys
    event.preventDefault();
    return false;
  }
  return true;
}
function capitalizeFirstLetter(input) {
    input.value = input.value.replace(/(?:^|\s)\S/g, function (a) {
      return a.toUpperCase();
    });
  }
  function allowOnlyLetters(event) {
    if (!/[a-zA-Z ]/.test(event.key)) {
      event.preventDefault();
    }
  }