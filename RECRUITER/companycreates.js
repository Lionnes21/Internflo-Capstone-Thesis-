function allowOnlyLetters(event) {
  const char = String.fromCharCode(event.which);
  if (!/^[a-zA-Z\s]$/.test(char)) {
    event.preventDefault();
  }
}

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
  if (input.value.length === 1) {
    input.value =
      input.value.charAt(0).toUpperCase() + input.value.slice(1).toLowerCase();
  }
}

document.addEventListener("DOMContentLoaded", function () {
  let currentStep = 1;
  const form = document.getElementById("multi-step-form");
  const nextBtns = document.querySelectorAll(".next-btn");
  const prevBtns = document.querySelectorAll(".prev-btn");
  const steps = document.querySelectorAll(".form-step");
  const signInSection = document.getElementById("sign-in-section");

  // Track whether each field has been interacted with
  const fieldsInteracted = {
    firstName: false,
    lastName: false,
    // Add all step 2 fields
    companyName: false,
    industry: false,
    companyNumber: false,
    companyEmail: false,
    companyAddress: false,
    companyOverview: false,
    profilePhoto: false,
  };

  // Set up input validation for step 1
  const firstNameInput = document.querySelector(
    '#step-1 input[name="firstName"]'
  );
  const lastNameInput = document.querySelector(
    '#step-1 input[name="lastName"]'
  );

  // Set up input validation for step 2
  const companyNameInput = document.querySelector(
    '#step-2 input[name="companyName"]'
  );
  const industrySelect = document.querySelector(
    '#step-2 select[name="industry"]'
  );
  const companyNumberInput = document.querySelector(
    '#step-2 input[name="companyNumber"]'
  );
  const companyEmailInput = document.querySelector(
    '#step-2 input[name="companyEmail"]'
  );
  const companyAddressInput = document.querySelector(
    '#step-2 input[name="company-address"]'
  );
  const companyOverviewEditor = document.getElementById(
    "company-overview-editor"
  );
  const companyOverviewInput = document.getElementById("companyOverview");
  const profilePhotoInput = document.getElementById("profilePhoto");

  // Get elements for organization logo display
  const previewImage = document.getElementById("preview-image");
  const uploadContent = document.querySelector(".upload-content");
  const removeButton = document.querySelector(".remove-image");
  const fileInputWrapper = document.querySelector(".file-input-wrapper");

  // Add error message elements after specific inputs
  function addErrorMessageElement(input) {
    if (!input.parentElement.querySelector(".error-message")) {
      const errorMsg = document.createElement("div");
      errorMsg.className = "error-message";
      errorMsg.style.display = "none";
      input.parentElement.appendChild(errorMsg);
    }
  }

  // Change this function
  function addLogoErrorMessageElement() {
    const logoContainer = document.querySelector(".logo-container-org");
    if (
      logoContainer &&
      !logoContainer.parentElement.querySelector(".error-message")
    ) {
      const errorMsg = document.createElement("div");
      errorMsg.className = "error-message";
      errorMsg.style.display = "none";
      errorMsg.style.textAlign = "center"; // Center the text
      errorMsg.style.width = "100%"; // Make it full width of parent
      logoContainer.parentElement.appendChild(errorMsg);
    }
  }

  // Add error message for editor container
  function addEditorErrorMessage(editor) {
    const container = editor.closest(".editor-container");
    if (container && !container.parentElement.querySelector(".error-message")) {
      const errorMsg = document.createElement("div");
      errorMsg.className = "error-message";
      errorMsg.style.display = "none";
      container.parentElement.appendChild(errorMsg);
    }
  }

  // Add error message elements for all required inputs
  if (firstNameInput) addErrorMessageElement(firstNameInput);
  if (lastNameInput) addErrorMessageElement(lastNameInput);
  if (companyNameInput) addErrorMessageElement(companyNameInput);
  if (industrySelect) addErrorMessageElement(industrySelect);
  if (companyNumberInput) addErrorMessageElement(companyNumberInput);
  if (companyEmailInput) addErrorMessageElement(companyEmailInput);
  if (companyAddressInput) addErrorMessageElement(companyAddressInput);
  if (companyOverviewEditor) addEditorErrorMessage(companyOverviewEditor);
  addLogoErrorMessageElement();

  // Step 1 validation listeners
  if (firstNameInput) {
    firstNameInput.addEventListener("input", function () {
      fieldsInteracted.firstName = true;
      validateSpecificInput(this, "First Name");
    });

    firstNameInput.addEventListener("blur", function () {
      if (fieldsInteracted.firstName) {
        validateSpecificInput(this, "First Name");
      }
      this.classList.remove("valid");
    });
  }

  if (lastNameInput) {
    lastNameInput.addEventListener("input", function () {
      fieldsInteracted.lastName = true;
      validateSpecificInput(this, "Last Name");
    });

    lastNameInput.addEventListener("blur", function () {
      if (fieldsInteracted.lastName) {
        validateSpecificInput(this, "Last Name");
      }
      this.classList.remove("valid");
    });
  }

  // Step 2 validation listeners
  if (companyNameInput) {
    companyNameInput.addEventListener("input", function () {
      fieldsInteracted.companyName = true;
      validateSpecificInput(this, "Organization Name");
    });

    companyNameInput.addEventListener("blur", function () {
      if (fieldsInteracted.companyName) {
        validateSpecificInput(this, "Organization Name");
      }
      this.classList.remove("valid");
    });
  }

  if (industrySelect) {
    industrySelect.addEventListener("change", function () {
      fieldsInteracted.industry = true;
      validateSelectInput(this, "General Industry");
    });

    industrySelect.addEventListener("blur", function () {
      if (fieldsInteracted.industry) {
        validateSelectInput(this, "General Industry");
      }
      this.classList.remove("valid");
    });
  }

  if (companyNumberInput) {
    companyNumberInput.addEventListener("input", function () {
      fieldsInteracted.companyNumber = true;
      validatePhoneNumber(this, "Official Phone Number");
    });

    companyNumberInput.addEventListener("blur", function () {
      if (fieldsInteracted.companyNumber) {
        validatePhoneNumber(this, "Official Phone Number");
      }
      this.classList.remove("valid");
    });
  }

  if (companyEmailInput) {
    companyEmailInput.addEventListener("input", function () {
      fieldsInteracted.companyEmail = true;
      validateWebsiteInput(this, "Official Website");
    });

    companyEmailInput.addEventListener("blur", function () {
      if (fieldsInteracted.companyEmail) {
        validateWebsiteInput(this, "Official Website");
      }
      this.classList.remove("valid");
    });
  }

  if (companyAddressInput) {
    companyAddressInput.addEventListener("input", function () {
      fieldsInteracted.companyAddress = true;
      validateSpecificInput(this, "Organization Address");
    });

    companyAddressInput.addEventListener("blur", function () {
      if (fieldsInteracted.companyAddress) {
        validateSpecificInput(this, "Organization Address");
      }
      this.classList.remove("valid");
    });
  }

  if (companyOverviewEditor) {
    companyOverviewEditor.addEventListener("input", function () {
      fieldsInteracted.companyOverview = true;
      validateEditorInput(this, "Organization Overview", companyOverviewInput);
    });

    companyOverviewEditor.addEventListener("blur", function () {
      if (fieldsInteracted.companyOverview) {
        validateEditorInput(
          this,
          "Organization Overview",
          companyOverviewInput
        );
      }
      this.closest(".editor-container").classList.remove("valid");
    });
  }

  // Profile photo validation
  // Profile photo validation
  if (profilePhotoInput) {
    profilePhotoInput.addEventListener("change", function (e) {
      fieldsInteracted.profilePhoto = true;
      const file = e.target.files[0];

      if (file) {
        // First check if the file is an image
        if (!file.type.match("image.*")) {
          const logoContainer = document.querySelector(".logo-container-org");
          const errorMsg =
            logoContainer.parentElement.querySelector(".error-message");

          fileInputWrapper.classList.remove("valid");
          fileInputWrapper.classList.add("input-error");
          errorMsg.textContent = "Please upload an image file";
          errorMsg.style.display = "block";

          // Reset the file input
          this.value = "";
          return;
        }

        // Then check file size
        if (file.size > 5 * 1024 * 1024) {
          const logoContainer = document.querySelector(".logo-container-org");
          const errorMsg =
            logoContainer.parentElement.querySelector(".error-message");

          fileInputWrapper.classList.remove("valid");
          fileInputWrapper.classList.add("input-error");
          errorMsg.textContent = "Image size should be less than 5MB";
          errorMsg.style.display = "block";

          // Reset the file input
          this.value = "";
          return;
        }

        // If validation passes, show the preview
        validateProfilePhoto();

        const reader = new FileReader();
        reader.onload = function (e) {
          previewImage.src = e.target.result;
          previewImage.style.display = "block";
          uploadContent.style.display = "none";
          removeButton.style.display = "flex";
          fileInputWrapper.classList.add("has-image");
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Add remove button functionality
  if (removeButton) {
    removeButton.addEventListener("click", function (e) {
      e.preventDefault();

      // Reset the file input
      profilePhotoInput.value = "";

      // Reset the UI
      previewImage.src = "";
      previewImage.style.display = "none";
      uploadContent.style.display = "block";
      removeButton.style.display = "none";
      fileInputWrapper.classList.remove("has-image");

      // Trigger validation if user has interacted with the field
      if (fieldsInteracted.profilePhoto) {
        validateProfilePhoto();
      }
    });
  }

  // Save editor content to hidden input before submission
  if (companyOverviewEditor && companyOverviewInput) {
    companyOverviewEditor.addEventListener("input", function () {
      companyOverviewInput.value = this.innerHTML;
    });
  }

  function validateSpecificInput(input, fieldName) {
    const errorMsg = input.parentElement.querySelector(".error-message");

    // Check if input is empty
    if (!input.value.trim()) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    } else {
      input.classList.remove("input-error");
      input.classList.add("valid");
      errorMsg.style.display = "none";
      return true;
    }
  }

  function validateSelectInput(select, fieldName) {
    const errorMsg = select.parentElement.querySelector(".error-message");

    // Check if a value is selected (not the default empty one)
    if (!select.value || select.selectedIndex === 0) {
      select.classList.remove("valid");
      select.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    } else {
      select.classList.remove("input-error");
      select.classList.add("valid");
      errorMsg.style.display = "none";
      return true;
    }
  }

  function validateEditorInput(editor, fieldName, hiddenInput) {
    const container = editor.closest(".editor-container");
    const errorMsg = container.parentElement.querySelector(".error-message");

    // Strip HTML tags to check if there's actual content
    const content = editor.innerHTML;
    const textContent = editor.textContent.trim();

    // Save content to hidden input
    if (hiddenInput) {
      hiddenInput.value = content;
    }

    if (!textContent) {
      container.classList.remove("valid");
      container.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    } else {
      container.classList.remove("input-error");
      container.classList.add("valid");
      errorMsg.style.display = "none";
      return true;
    }
  }

  // Validate profile photo
  // Validate profile photo
  function validateProfilePhoto() {
    const input = profilePhotoInput;
    const logoContainer = document.querySelector(".logo-container-org");
    const errorMsg =
      logoContainer.parentElement.querySelector(".error-message");

    if (!input.files || input.files.length === 0) {
      fileInputWrapper.classList.remove("valid");
      fileInputWrapper.classList.add("input-error");
      errorMsg.textContent = "Organization Logo is required";
      errorMsg.style.display = "block";
      return false;
    }

    const file = input.files[0];
    // Check file type
    if (!file.type.match("image.*")) {
      fileInputWrapper.classList.remove("valid");
      fileInputWrapper.classList.add("input-error");
      errorMsg.textContent = "Please upload an image file";
      errorMsg.style.display = "block";
      // Reset the file input to clear the selection
      input.value = "";

      // Reset the UI
      previewImage.src = "";
      previewImage.style.display = "none";
      uploadContent.style.display = "block";
      removeButton.style.display = "none";
      fileInputWrapper.classList.remove("has-image");

      return false;
    }

    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      fileInputWrapper.classList.remove("valid");
      fileInputWrapper.classList.add("input-error");
      errorMsg.textContent = "Image size should be less than 5MB";
      errorMsg.style.display = "block";
      // Reset the file input to clear the selection
      input.value = "";

      // Reset the UI
      previewImage.src = "";
      previewImage.style.display = "none";
      uploadContent.style.display = "block";
      removeButton.style.display = "none";
      fileInputWrapper.classList.remove("has-image");

      return false;
    }

    fileInputWrapper.classList.remove("input-error");
    fileInputWrapper.classList.add("valid");
    errorMsg.style.display = "none";
    return true;
  }

  // Add website validation function
  function validateWebsiteInput(input, fieldName) {
    const errorMsg = input.parentElement.querySelector(".error-message");

    // Check if input is empty
    if (!input.value.trim()) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    }

    // Simple website validation - checks for domain and TLD
    const websitePattern =
      /^(https?:\/\/)?(www\.)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/.*)?$/;
    if (!websitePattern.test(input.value.trim())) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Please enter a valid website URL";
      errorMsg.style.display = "block";
      return false;
    } else {
      input.classList.remove("input-error");
      input.classList.add("valid");
      errorMsg.style.display = "none";
      return true;
    }
  }

  // Add phone number validation function
  function validatePhoneNumber(input, fieldName) {
    const errorMsg = input.parentElement.querySelector(".error-message");

    // Check if input is empty
    if (!input.value.trim()) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    }

    // Simple phone validation - allowing various formats
    const phonePattern = /^[\d\s()+\-\.]{7,20}$/;
    if (!phonePattern.test(input.value.trim())) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Please enter a valid phone number";
      errorMsg.style.display = "block";
      return false;
    } else {
      input.classList.remove("input-error");
      input.classList.add("valid");
      errorMsg.style.display = "none";
      return true;
    }
  }

  function forceValidateInput(input, fieldName) {
    // This will always validate, even if user hasn't interacted yet
    return validateSpecificInput(input, fieldName);
  }

  function forceValidateSelect(select, fieldName) {
    return validateSelectInput(select, fieldName);
  }

  function forceValidateEditor(editor, fieldName, hiddenInput) {
    return validateEditorInput(editor, fieldName, hiddenInput);
  }

  function forceValidateProfilePhoto() {
    fieldsInteracted.profilePhoto = true;
    return validateProfilePhoto();
  }

  function resetValidationState(stepNumber) {
    const currentStepElement = document.getElementById(`step-${stepNumber}`);
    if (currentStepElement) {
      const errorElements =
        currentStepElement.querySelectorAll(".error-message");
      errorElements.forEach((element) => {
        element.textContent = "";
        element.style.display = "none";
      });

      const inputElements = currentStepElement.querySelectorAll(
        "input, select, textarea"
      );
      inputElements.forEach((element) => {
        element.classList.remove("input-error");
        element.classList.remove("valid");
      });

      const editorContainers =
        currentStepElement.querySelectorAll(".editor-container");
      editorContainers.forEach((container) => {
        container.classList.remove("input-error");
        container.classList.remove("valid");
      });
    }
  }

  function updateStep(stepNumber) {
    steps.forEach((step) => step.classList.remove("active"));
    document.getElementById(`step-${stepNumber}`).classList.add("active");

    // Update step indicators
    const stepIndicators = document.querySelectorAll(".step-indicator");
    stepIndicators.forEach((indicator, index) => {
      if (index + 1 <= stepNumber) {
        indicator.classList.add("active");
      } else {
        indicator.classList.remove("active");
      }
    });

    const signInSection = document.getElementById("sign-in-section");
    if (signInSection) {
      signInSection.style.display = stepNumber === 1 ? "block" : "none";
    }
  }

  function validateStep1() {
    // When clicking next, we force validation regardless of previous interaction
    let isFirstNameValid = true;
    let isLastNameValid = true;

    if (firstNameInput) {
      isFirstNameValid = forceValidateInput(firstNameInput, "First Name");
      // Mark as interacted since user tried to proceed
      fieldsInteracted.firstName = true;
    }

    if (lastNameInput) {
      isLastNameValid = forceValidateInput(lastNameInput, "Last Name");
      // Mark as interacted since user tried to proceed
      fieldsInteracted.lastName = true;
    }

    return isFirstNameValid && isLastNameValid;
  }

  function validateStep2() {
    let isCompanyNameValid = true;
    let isIndustryValid = true;
    let isCompanyNumberValid = true;
    let isCompanyEmailValid = true;
    let isCompanyAddressValid = true;
    let isCompanyOverviewValid = true;
    let isProfilePhotoValid = true;

    if (profilePhotoInput) {
      isProfilePhotoValid = forceValidateProfilePhoto();
    }

    if (companyNameInput) {
      isCompanyNameValid = forceValidateInput(
        companyNameInput,
        "Organization Name"
      );
      fieldsInteracted.companyName = true;
    }

    if (industrySelect) {
      isIndustryValid = forceValidateSelect(industrySelect, "General Industry");
      fieldsInteracted.industry = true;
    }

    if (companyNumberInput) {
      isCompanyNumberValid = validatePhoneNumber(
        companyNumberInput,
        "Official Phone Number"
      );
      fieldsInteracted.companyNumber = true;
    }

    if (companyEmailInput) {
      isCompanyEmailValid = validateWebsiteInput(
        companyEmailInput,
        "Official Website"
      );
      fieldsInteracted.companyEmail = true;
    }

    if (companyAddressInput) {
      isCompanyAddressValid = forceValidateInput(
        companyAddressInput,
        "Organization Address"
      );
      fieldsInteracted.companyAddress = true;
    }

    if (companyOverviewEditor) {
      isCompanyOverviewValid = forceValidateEditor(
        companyOverviewEditor,
        "Organization Overview",
        companyOverviewInput
      );
      fieldsInteracted.companyOverview = true;
    }

    return (
      isCompanyNameValid &&
      isIndustryValid &&
      isCompanyNumberValid &&
      isCompanyEmailValid &&
      isCompanyAddressValid &&
      isCompanyOverviewValid &&
      isProfilePhotoValid
    );
  }

  function validateStep2() {
    let isCompanyNameValid = true;
    let isIndustryValid = true;
    let isCompanyNumberValid = true;
    let isCompanyEmailValid = true;
    let isCompanyAddressValid = true;
    let isCompanyOverviewValid = true;
    let isProfilePhotoValid = true;

    if (profilePhotoInput) {
      isProfilePhotoValid = forceValidateProfilePhoto();
    }

    if (companyNameInput) {
      isCompanyNameValid = forceValidateInput(
        companyNameInput,
        "Organization Name"
      );
      fieldsInteracted.companyName = true;
    }

    if (industrySelect) {
      isIndustryValid = forceValidateSelect(industrySelect, "General Industry");
      fieldsInteracted.industry = true;
    }

    if (companyNumberInput) {
      isCompanyNumberValid = validatePhoneNumber(
        companyNumberInput,
        "Official Phone Number"
      );
      fieldsInteracted.companyNumber = true;
    }

    if (companyEmailInput) {
      isCompanyEmailValid = validateWebsiteInput(
        companyEmailInput,
        "Official Website"
      );
      fieldsInteracted.companyEmail = true;
    }

    if (companyAddressInput) {
      isCompanyAddressValid = forceValidateInput(
        companyAddressInput,
        "Organization Address"
      );
      fieldsInteracted.companyAddress = true;
    }

    if (companyOverviewEditor) {
      isCompanyOverviewValid = forceValidateEditor(
        companyOverviewEditor,
        "Organization Overview",
        companyOverviewInput
      );
      fieldsInteracted.companyOverview = true;
    }

    return (
      isCompanyNameValid &&
      isIndustryValid &&
      isCompanyNumberValid &&
      isCompanyEmailValid &&
      isCompanyAddressValid &&
      isCompanyOverviewValid &&
      isProfilePhotoValid
    );
  }

  // INSERT YOUR STEP 3 VALIDATION CODE HERE

  // Add these to your existing field interactions object
  fieldsInteracted.certReg = false;
  fieldsInteracted.birReg = false;
  fieldsInteracted.permit = false;

  // Get the file inputs
  const certRegInput = document.querySelector('input[name="certReg"]');
  const birRegInput = document.querySelector('input[name="birReg"]');
  const permitInput = document.querySelector('input[name="permit"]');

  // Add error message elements for all the file inputs
  function addFileErrorMessageElement(input) {
    const inputContainer = input.closest(".input-container");
    if (!inputContainer.querySelector(".error-message")) {
      const errorMsg = document.createElement("div");
      errorMsg.className = "error-message";
      errorMsg.style.display = "none";
      inputContainer.appendChild(errorMsg);
    }
  }

  if (certRegInput) addFileErrorMessageElement(certRegInput);
  if (birRegInput) addFileErrorMessageElement(birRegInput);
  if (permitInput) addFileErrorMessageElement(permitInput);

  // File validation function
  function validatePdfFile(input, fieldName) {
    const inputContainer = input.closest(".input-container");
    const errorMsg = inputContainer.querySelector(".error-message");

    // Check if a file is selected
    if (!input.files || input.files.length === 0) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = fieldName + " is required";
      errorMsg.style.display = "block";
      return false;
    }

    const file = input.files[0];
    // Check file type - ensure it's a PDF
    if (
      file.type !== "application/pdf" &&
      !file.name.toLowerCase().endsWith(".pdf")
    ) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Please upload a PDF file for " + fieldName;
      errorMsg.style.display = "block";
      return false;
    }

    input.classList.remove("input-error");
    input.classList.add("valid");
    errorMsg.style.display = "none";
    return true;
  }

  // Add event listeners for file inputs
  if (certRegInput) {
    certRegInput.addEventListener("change", function () {
      fieldsInteracted.certReg = true;
      if (this.files && this.files.length > 0) {
        const file = this.files[0];
        // Check if it's not a PDF
        if (
          file.type !== "application/pdf" &&
          !file.name.toLowerCase().endsWith(".pdf")
        ) {
          // Show error message before clearing
          const inputContainer = this.closest(".input-container");
          const errorMsg = inputContainer.querySelector(".error-message");
          this.classList.remove("valid");
          this.classList.add("input-error");
          errorMsg.textContent =
            "Please upload a PDF file for Certificate of Registration";
          errorMsg.style.display = "block";

          // Reset the file input to clear the selection
          this.value = "";
          return;
        }
      }
      validatePdfFile(this, "Certificate of Registration");
    });

    // Add blur event listener to remove valid class
    certRegInput.addEventListener("blur", function () {
      this.classList.remove("valid");
    });
  }

  if (birRegInput) {
    birRegInput.addEventListener("change", function () {
      fieldsInteracted.birReg = true;
      if (this.files && this.files.length > 0) {
        const file = this.files[0];
        // Check if it's not a PDF
        if (
          file.type !== "application/pdf" &&
          !file.name.toLowerCase().endsWith(".pdf")
        ) {
          // Show error message before clearing
          const inputContainer = this.closest(".input-container");
          const errorMsg = inputContainer.querySelector(".error-message");
          this.classList.remove("valid");
          this.classList.add("input-error");
          errorMsg.textContent =
            "Please upload a PDF file for BIR Registration";
          errorMsg.style.display = "block";

          // Reset the file input to clear the selection
          this.value = "";
          return;
        }
      }
      validatePdfFile(this, "BIR Registration");
    });

    // Add blur event listener to remove valid class
    birRegInput.addEventListener("blur", function () {
      this.classList.remove("valid");
    });
  }

  if (permitInput) {
    permitInput.addEventListener("change", function () {
      fieldsInteracted.permit = true;
      if (this.files && this.files.length > 0) {
        const file = this.files[0];
        // Check if it's not a PDF
        if (
          file.type !== "application/pdf" &&
          !file.name.toLowerCase().endsWith(".pdf")
        ) {
          // Show error message before clearing
          const inputContainer = this.closest(".input-container");
          const errorMsg = inputContainer.querySelector(".error-message");
          this.classList.remove("valid");
          this.classList.add("input-error");
          errorMsg.textContent = "Please upload a PDF file for Business Permit";
          errorMsg.style.display = "block";

          // Reset the file input to clear the selection
          this.value = "";
          return;
        }
      }
      validatePdfFile(this, "Business Permit");
    });

    // Add blur event listener to remove valid class
    permitInput.addEventListener("blur", function () {
      this.classList.remove("valid");
    });
  }

  // Add this to force validation
  function forceValidatePdfFile(input, fieldName) {
    return validatePdfFile(input, fieldName);
  }

  // Update the validateStep3 function
  function validateStep3() {
    let isCertRegValid = true;
    let isBirRegValid = true;
    let isPermitValid = true;

    if (certRegInput) {
      isCertRegValid = forceValidatePdfFile(
        certRegInput,
        "Certificate of Registration"
      );
      fieldsInteracted.certReg = true;
    }

    if (birRegInput) {
      isBirRegValid = forceValidatePdfFile(birRegInput, "BIR Registration");
      fieldsInteracted.birReg = true;
    }

    if (permitInput) {
      isPermitValid = forceValidatePdfFile(permitInput, "Business Permit");
      fieldsInteracted.permit = true;
    }

    return isCertRegValid && isBirRegValid && isPermitValid;
  }

  let emailValidationStatus = true;

  // Add these to your existing field interactions object
  fieldsInteracted.email = false;
  fieldsInteracted.mobileNumber = false;
  fieldsInteracted.password = false;
  fieldsInteracted.confirmPassword = false;
  fieldsInteracted.terms = false;

  // Get the inputs for step 4
  const emailInput = document.querySelector('input[name="email"]');
  const mobileNumberInput = document.querySelector(
    'input[name="mobileNumber"]'
  );
  const passwordInput = document.querySelector('input[name="password"]');
  const confirmPasswordInput = document.querySelector(
    'input[name="confirmPassword"]'
  );
  const termsRadio = document.querySelector("#terms-acceptance");
  const radioError = document.getElementById("radioError");

  // Add error message elements for step 4 inputs
  if (emailInput) addErrorMessageElement(emailInput);
  if (mobileNumberInput) addErrorMessageElement(mobileNumberInput);
  // Password inputs already have error message elements in HTML

  // Email validation function
  function validateEmail(input) {
    const errorMsg = input.parentElement.querySelector(".error-message");
    const emailValue = input.value.trim();

    if (!emailValue) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Email is required";
      errorMsg.style.display = "block";
      emailValidationStatus = false;
      return false;
    }

    if (!emailValue.includes("@") || !emailValue.includes(".com")) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Please enter a valid email";
      errorMsg.style.display = "block";
      emailValidationStatus = false;
      return false;
    }

    // Check email existence via AJAX
    checkEmailExists(emailValue, input, errorMsg);
    return emailValidationStatus; // Return current status (will be updated by AJAX callback)
  }

  // Function to check if email exists in database
  function checkEmailExists(email, input, errorMsg) {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "check_email.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
      if (this.status === 200) {
        try {
          const response = JSON.parse(this.responseText);

          if (response.exists) {
            input.classList.remove("valid");
            input.classList.add("input-error");
            errorMsg.textContent = "This email is already registered";
            errorMsg.style.display = "block";
            emailValidationStatus = false;
          } else {
            input.classList.remove("input-error");
            input.classList.add("valid");
            errorMsg.style.display = "none";
            emailValidationStatus = true;
          }
        } catch (e) {
          console.error("Error parsing JSON response:", e);
          // Allow submission if there's an error with the check
          emailValidationStatus = true;
        }
      } else {
        console.error("Error checking email:", this.status);
        // Allow submission if there's an error with the check
        emailValidationStatus = true;
      }
    };

    xhr.onerror = function () {
      console.error("Request error");
      // Allow submission if there's an error with the check
      emailValidationStatus = true;
    };

    xhr.send("email=" + encodeURIComponent(email));
  }

  // Mobile number validation function
  function validateMobileNumber(input) {
    const errorMsg = input.parentElement.querySelector(".error-message");
    const mobileValue = input.value.trim();

    if (!mobileValue) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Mobile number is required";
      errorMsg.style.display = "block";
      return false;
    }

    if (!mobileValue.startsWith("09")) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Mobile number must start with 09";
      errorMsg.style.display = "block";
      return false;
    }

    if (mobileValue.length !== 11) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Mobile number must be 11 digits";
      errorMsg.style.display = "block";
      return false;
    }

    input.classList.remove("input-error");
    input.classList.add("valid");
    errorMsg.style.display = "none";
    return true;
  }

  // Password validation function
  function validatePassword(input) {
    const errorMsg = input.parentElement.querySelector(".error-message");
    const passwordValue = input.value.trim();

    if (!passwordValue) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Password is required";
      errorMsg.style.display = "block";
      return false;
    }

    if (!/[A-Z]/.test(passwordValue)) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent =
        "Password must include at least one uppercase letter";
      errorMsg.style.display = "block";
      return false;
    }
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(passwordValue)) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent =
        "Password must include at least one special character (e.g., @, #, $)";
      errorMsg.style.display = "block";
      return false;
    }

    if (passwordValue.length < 8) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Password must be at least 8 characters long";
      errorMsg.style.display = "block";
      return false;
    }

    input.classList.remove("input-error");
    input.classList.add("valid");
    errorMsg.style.display = "none";
    return true;
  }

  // Confirm password validation function
  function validateConfirmPassword(input, passwordInput) {
    const errorMsg = input.parentElement.querySelector(".error-message");
    const confirmValue = input.value.trim();
    const passwordValue = passwordInput.value.trim();

    if (!confirmValue) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Please confirm your password";
      errorMsg.style.display = "block";
      return false;
    }

    if (confirmValue !== passwordValue) {
      input.classList.remove("valid");
      input.classList.add("input-error");
      errorMsg.textContent = "Passwords do not match";
      errorMsg.style.display = "block";
      return false;
    }

    input.classList.remove("input-error");
    input.classList.add("valid");
    errorMsg.style.display = "none";
    return true;
  }

  // Terms validation function
  function validateTerms(input) {
    if (!input.checked) {
      radioError.textContent = "You must accept the terms and conditions";
      radioError.style.display = "block";
      return false;
    }

    radioError.style.display = "none";
    return true;
  }

  // Add event listeners for step 4 inputs
  if (emailInput) {
    emailInput.addEventListener("input", function () {
      fieldsInteracted.email = true;
      validateEmail(this);
    });

    emailInput.addEventListener("blur", function () {
      if (fieldsInteracted.email) {
        validateEmail(this);
      }
      this.classList.remove("valid");
    });
  }

  if (mobileNumberInput) {
    mobileNumberInput.addEventListener("input", function () {
      fieldsInteracted.mobileNumber = true;
      validateMobileNumber(this);
    });

    mobileNumberInput.addEventListener("blur", function () {
      if (fieldsInteracted.mobileNumber) {
        validateMobileNumber(this);
      }
      this.classList.remove("valid");
    });
  }

  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      fieldsInteracted.password = true;
      validatePassword(this);

      // If confirm password is already filled, validate it too
      if (
        fieldsInteracted.confirmPassword &&
        confirmPasswordInput.value.trim()
      ) {
        validateConfirmPassword(confirmPasswordInput, this);
      }
    });

    passwordInput.addEventListener("blur", function () {
      if (fieldsInteracted.password) {
        validatePassword(this);
      }
      this.classList.remove("valid");
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener("input", function () {
      fieldsInteracted.confirmPassword = true;
      validateConfirmPassword(this, passwordInput);
    });

    confirmPasswordInput.addEventListener("blur", function () {
      if (fieldsInteracted.confirmPassword) {
        validateConfirmPassword(this, passwordInput);
      }
      this.classList.remove("valid");
    });
  }

  if (termsRadio) {
    termsRadio.addEventListener("change", function () {
      fieldsInteracted.terms = true;
      validateTerms(this);
    });
  }

  // Update validateStep4 function
  function validateStep4() {
    let isMobileValid = true;
    let isPasswordValid = true;
    let isConfirmPasswordValid = true;
    let isTermsValid = true;

    if (emailInput) {
      validateEmail(emailInput);
      fieldsInteracted.email = true;
    }

    if (mobileNumberInput) {
      isMobileValid = validateMobileNumber(mobileNumberInput);
      fieldsInteracted.mobileNumber = true;
    }

    if (passwordInput) {
      isPasswordValid = validatePassword(passwordInput);
      fieldsInteracted.password = true;
    }

    if (confirmPasswordInput) {
      isConfirmPasswordValid = validateConfirmPassword(
        confirmPasswordInput,
        passwordInput
      );
      fieldsInteracted.confirmPassword = true;
    }

    if (termsRadio) {
      isTermsValid = validateTerms(termsRadio);
      fieldsInteracted.terms = true;
    }

    return (
      emailValidationStatus &&
      isMobileValid &&
      isPasswordValid &&
      isConfirmPasswordValid &&
      isTermsValid
    );
  }

  // Terms modal logic
  const termsModal = document.getElementById("termsModal");
  const acceptButton = termsModal.querySelector(".acceptbtn");

  function showTermsModal() {
    termsModal.style.display = "block";
  }

  function hideTermsModal() {
    termsModal.style.display = "none";
  }

  termsRadio.addEventListener("click", function (event) {
    event.preventDefault();
    showTermsModal();
  });

  acceptButton.addEventListener("click", function () {
    termsRadio.checked = true;
    hideTermsModal();
    validateTerms(termsRadio);
  });

  const termsLink = document.querySelector(".terms-link");
  if (termsLink) {
    termsLink.addEventListener("click", function (event) {
      event.preventDefault();
      showTermsModal();
    });
  }

  const privacyLink = document.querySelector(".privacy-link");
  if (privacyLink) {
    privacyLink.addEventListener("click", function (event) {
      event.preventDefault();
      showTermsModal();
    });
  }

  // Form submission logic
  form.addEventListener("submit", function (event) {
    if (currentStep === 4) {
      if (!validateStep4()) {
        event.preventDefault();
      }
    } else {
      event.preventDefault();
    }
  });

  nextBtns.forEach((button, index) => {
    button.addEventListener("click", function (event) {
      if (index === 0 && !validateStep1()) return;
      if (index === 1 && !validateStep2()) return;
      if (index === 2 && !validateStep3()) return;

      if (currentStep < 4) {
        currentStep++;
        updateStep(currentStep);
      }
    });
  });

  prevBtns.forEach((button) => {
    button.addEventListener("click", function () {
      if (currentStep > 1) {
        currentStep--;
        updateStep(currentStep);
      }
    });
  });

  function updateStep(step) {
    // Hide all steps and show only the current one
    steps.forEach((stepElement, index) => {
      if (index + 1 === step) {
        stepElement.style.display = "block";
      } else {
        stepElement.style.display = "none";
      }
    });
  
    // Update the stepper indicators
    const stepIndicators = document.querySelectorAll(".step-indicator");
    stepIndicators.forEach((indicator, index) => {
      if (index + 1 <= step) {
        indicator.classList.add("active");
      } else {
        indicator.classList.remove("active");
      }
    });
  
    // Show sign-in section only on step 1
    if (step === 1) {
      signInSection.style.display = "block";
    } else {
      signInSection.style.display = "none";
    }
  }

  updateStep(currentStep);
});

function convertRichTextToPlainText(richTextElement) {
  // Create a temporary div to process the content
  const tempDiv = document.createElement("div");
  tempDiv.innerHTML = richTextElement.innerHTML;

  // Replace list elements with appropriate text representations
  tempDiv.querySelectorAll("ul").forEach((ul) => {
    // Convert unordered lists by extracting text content and splitting into lines
    const listItems = Array.from(ul.querySelectorAll("li")).map((li) =>
      li.textContent.trim()
    );
    ul.outerHTML = listItems.join("\n");
  });

  tempDiv.querySelectorAll("ol").forEach((ol) => {
    // Convert ordered lists by extracting text content and splitting into numbered lines
    const listItems = Array.from(ol.querySelectorAll("li")).map((li) =>
      li.textContent.trim()
    );
    ol.outerHTML = listItems.join("\n");
  });

  // Remove all remaining HTML tags
  const plainText = tempDiv.textContent.trim();
  return plainText;
}

// Get all toggle password icons
const togglePasswordIcons = document.querySelectorAll(".toggle-password");

// Add click event listener to each toggle icon
togglePasswordIcons.forEach((icon) => {
  icon.addEventListener("click", function () {
    // Get the parent password container
    const passwordContainer = this.closest(".password-container");
    // Get the password input within this container
    const passwordInput = passwordContainer.querySelector("input");

    // Toggle the input type between 'password' and 'text'
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      this.classList.remove("fa-eye-slash");
      this.classList.add("fa-eye");
    } else {
      passwordInput.type = "password";
      this.classList.remove("fa-eye");
      this.classList.add("fa-eye-slash");
    }
  });
});
