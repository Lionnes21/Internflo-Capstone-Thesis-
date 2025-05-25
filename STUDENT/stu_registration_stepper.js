const stepIndicators = document.querySelectorAll(".step-indicator");
const stepContents = document.querySelectorAll(".step-content");
let currentStep = 1;

// Function to update step indicators and heights
function updateStepIndicators(step) {
  const imageContainer = document.querySelector(".image-container");
  const fieldset = document.querySelector("fieldset");

  // Remove all step-specific classes
  imageContainer.classList.remove("step-2", "step-3", "step-4");
  fieldset.classList.remove("step-2", "step-3", "step-4");

  // Add appropriate class based on current step
  if (step === 2) {
    imageContainer.classList.add("step-2");
    fieldset.classList.add("step-2");
  } else if (step === 3) {
    imageContainer.classList.add("step-3");
    fieldset.classList.add("step-3");
  } else if (step === 4) {
    imageContainer.classList.add("step-4");
    fieldset.classList.add("step-4");
  }

  // Update step indicators as before
  stepIndicators.forEach((indicator) => {
    const indicatorStep = parseInt(indicator.dataset.step);
    if (indicatorStep === step) {
      indicator.classList.add("active");
    } else if (indicatorStep < step) {
      indicator.classList.add("completed");
      indicator.classList.remove("active");
    } else {
      indicator.classList.remove("active", "completed");
    }
  });
}

// Function to show/hide step content
function showStep(step) {
  stepContents.forEach((content, index) => {
    if (index + 1 === step) {
      content.style.display = "block";
    } else {
      content.style.display = "none";
    }
  });
  updateStepIndicators(step);

  // Reset border styles when switching steps
  if (step === 1) {
    document
      .getElementById("verify_lastname")
      .classList.remove("valid", "error");
  }
}

// Add input event listeners for step 2 fields
const step2Fields = [
  "city",
  "region",
  "postal_code",
  "baranggay",
  "home_address",
];

step2Fields.forEach((fieldId) => {
  const input = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + "Error");

  input.addEventListener("input", function () {
    if (this.value.trim() === "") {
      this.classList.remove("valid");
      this.classList.add("error");
      errorDiv.textContent = "This is a required field";
      errorDiv.style.display = "block";
    } else {
      this.classList.remove("error");
      this.classList.add("valid");
      errorDiv.style.display = "none";
    }
  });

  input.addEventListener("blur", function () {
    if (!this.classList.contains("error")) {
      this.classList.remove("valid");
    }
  });
});

// Function to validate step 1
document
  .getElementById("verify_lastname")
  .addEventListener("input", function () {
    const errorDiv = document.getElementById("lastname_error");

    if (this.value.trim() === "") {
      this.classList.remove("valid");
      this.classList.add("error");
      errorDiv.textContent = "Please enter your last name";
      errorDiv.style.display = "block";
    } else {
      this.classList.remove("error");
      this.classList.add("valid");
      errorDiv.style.display = "none";
    }
  });

document
  .getElementById("verify_lastname")
  .addEventListener("blur", function () {
    if (!this.classList.contains("error")) {
      this.classList.remove("valid");
    }
  });

function validateStep1() {
  return new Promise((resolve) => {
    const verifyLastNameInput = document.getElementById("verify_lastname");
    const errorDiv = document.getElementById("lastname_error");
    const studentId = new URLSearchParams(window.location.search).get(
      "student_id"
    );

    if (verifyLastNameInput.value.trim() === "") {
      verifyLastNameInput.classList.remove("valid");
      verifyLastNameInput.classList.add("error");
      errorDiv.textContent = "Please enter your last name";
      errorDiv.style.display = "block";
      resolve(false);
      return;
    }

    // Send verification request to server
    fetch("stu_registration_stepper.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "verify_lastname",
        entered_lastname: verifyLastNameInput.value,
        student_id: studentId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          if (data.matches) {
            verifyLastNameInput.classList.add("valid");
            verifyLastNameInput.classList.remove("error");
            errorDiv.style.display = "none";
            resolve(true);
          } else {
            verifyLastNameInput.classList.remove("valid");
            verifyLastNameInput.classList.add("error");
            errorDiv.textContent = "Incorrect last name. Please try again.";
            errorDiv.style.display = "block";
            resolve(false);
          }
        } else {
          verifyLastNameInput.classList.remove("valid");
          verifyLastNameInput.classList.add("error");
          errorDiv.textContent = "An error occurred. Please try again.";
          errorDiv.style.display = "block";
          resolve(false);
        }
      })
      .catch((error) => {
        verifyLastNameInput.classList.remove("valid");
        verifyLastNameInput.classList.add("error");
        errorDiv.textContent = "An error occurred. Please try again.";
        errorDiv.style.display = "block";
        resolve(false);
      });
  });
}

// Function to validate step 2
function validateStep2() {
  let isValid = true;

  step2Fields.forEach((fieldId) => {
    const input = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + "Error");

    if (input.value.trim() === "") {
      input.classList.remove("valid");
      input.classList.add("error");
      errorDiv.textContent = "This is a required field";
      errorDiv.style.display = "block";
      isValid = false;
    }
  });

  return isValid;
}

// Define step 3 fields
const step3Fields = ["mobile_number", "email"];

// Add input event listeners for step 3 fields
step3Fields.forEach((fieldId) => {
  const input = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + "Error");

  input.addEventListener("input", function () {
    if (fieldId === "mobile_number") {
      validateMobileNumber(this, errorDiv);
    } else if (fieldId === "email") {
      validateEmail(this, errorDiv);
    }
  });

  input.addEventListener("blur", function () {
    if (!this.classList.contains("error")) {
      this.classList.remove("valid");
    }
  });
});

// Mobile number validation function
function validateMobileNumber(input, errorDiv) {
  const value = input.value.trim();
  const startsWithZeroNine = value.startsWith("09");

  if (value === "") {
    input.classList.remove("valid");
    input.classList.add("error");
    errorDiv.textContent = "Mobile number is required";
    errorDiv.style.display = "block";
  } else if (!startsWithZeroNine) {
    input.classList.remove("valid");
    input.classList.add("error");
    errorDiv.textContent = "Mobile number must start with 09";
    errorDiv.style.display = "block";
  } else if (value.length !== 11) {
    input.classList.remove("valid");
    input.classList.add("error");
    errorDiv.textContent = "Mobile number must be 11 digits";
    errorDiv.style.display = "block";
  } else {
    input.classList.remove("error");
    input.classList.add("valid");
    errorDiv.style.display = "none";
  }
}

// Email validation function
function validateEmail(input, errorDiv) {
  const value = input.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.(com)$/i;

  if (value === "") {
    input.classList.remove("valid");
    input.classList.add("error");
    errorDiv.textContent = "Email is required";
    errorDiv.style.display = "block";
    return false;
  } else if (!emailRegex.test(value)) {
    input.classList.remove("valid");
    input.classList.add("error");
    errorDiv.textContent = "Please enter a valid email";
    errorDiv.style.display = "block";
    return false;
  } else {
    return checkEmailUniqueness(value, input, errorDiv);
  }
}

// Function to check email uniqueness using AJAX
function checkEmailUniqueness(email, input, errorDiv) {
  return new Promise((resolve) => {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "stu_registration_stepper.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        if (response.exists) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "Email is already registered";
          errorDiv.style.display = "block";
          resolve(false);
        } else {
          input.classList.remove("error");
          input.classList.add("valid");
          errorDiv.style.display = "none";
          resolve(true);
        }
      } else {
        console.error("Error checking email uniqueness");
        resolve(false);
      }
    };

    xhr.send("email=" + encodeURIComponent(email));
  });
}

// Function to validate step 3
async function validateStep3() {
  let isValid = true;

  for (const fieldId of step3Fields) {
    const input = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + "Error");

    if (fieldId === "mobile_number") {
      const value = input.value.trim();
      if (value === "" || value.length !== 11 || !value.startsWith("09")) {
        input.classList.remove("valid");
        input.classList.add("error");
        errorDiv.textContent =
          value === ""
            ? "Mobile number is required"
            : "Please enter a valid mobile number";
        errorDiv.style.display = "block";
        isValid = false;
      }
    } else if (fieldId === "email") {
      const value = input.value.trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.(com)$/i;

      if (value === "" || !emailRegex.test(value)) {
        input.classList.remove("valid");
        input.classList.add("error");
        errorDiv.textContent =
          value === "" ? "Email is required" : "Please enter a valid email";
        errorDiv.style.display = "block";
        isValid = false;
      } else {
        const isEmailValid = await checkEmailUniqueness(value, input, errorDiv);
        if (!isEmailValid) {
          isValid = false;
        }
      }
    }
  }

  return isValid;
}

document.addEventListener('DOMContentLoaded', function() {
  // Define step 4 fields including radio button
  const step4Fields = ["password", "confirm_password", "terms-acceptance"];
  const form = document.querySelector("form");
  const registerBtn = document.getElementById("registerBtn");

  // Add password toggle functionality
  document.querySelectorAll(".toggle-password").forEach((icon) => {
      icon.addEventListener("click", function () {
          const targetId = this.getAttribute("data-target");
          const passwordInput = document.querySelector(targetId);

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

  // Add input event listeners for step 4 fields
  step4Fields.forEach((fieldId) => {
      const input = document.getElementById(fieldId);
      const errorDiv = document.getElementById(fieldId === "terms-acceptance" ? "radioError" : fieldId + "Error");

      if (fieldId === "terms-acceptance") {
          input.addEventListener("change", function() {
              if (this.checked) {
                  this.classList.remove("error");
                  errorDiv.style.display = "none";
              } else {
                  validateRadio(this, errorDiv);
              }
          });
      } else {
          input.addEventListener("input", function () {
              if (fieldId === "password") {
                  validatePassword(this, errorDiv);
              } else if (fieldId === "confirm_password") {
                  validateConfirmPassword(this, errorDiv);
              }
          });
      }

      input.addEventListener("blur", function () {
          if (!this.classList.contains("error")) {
              this.classList.remove("valid");
          }
      });
  });

  // Terms modal functionality
  const termsRadio = document.getElementById("terms-acceptance");
  const termsLink = document.querySelector(".terms-link");
  const termsModal = document.getElementById("termsModal");
  const acceptButton = termsModal.querySelector(".acceptbtn");
  const modalContent = termsModal.querySelector(".terms-modal-content");
  const radioError = document.getElementById("radioError");

  // Original radio button click handler
  termsRadio.addEventListener("click", function (event) {
      event.preventDefault();
      termsModal.style.display = "block";
      termsModal.offsetHeight;
  });

  // New terms link click handler
  termsLink.addEventListener("click", function (event) {
      event.preventDefault();
      termsModal.style.display = "block";
      termsModal.offsetHeight;
  });

  // Close modal when clicking outside
  termsModal.addEventListener("click", function (event) {
      if (event.target === termsModal) {
          closeModal();
      }
  });

  // Handle accept button click
  acceptButton.addEventListener("click", function () {
      termsRadio.checked = true;
      termsRadio.classList.remove("error");
      termsRadio.classList.add("valid");
      radioError.style.display = "none";
      closeModal();
  });

  // Modal close function
  function closeModal() {
      termsModal.style.animation = "fadeIn 0.3s ease-in-out reverse";
      modalContent.style.animation = "slideIn 0.3s ease-in-out reverse";

      setTimeout(() => {
          termsModal.style.display = "none";
          termsModal.style.animation = "";
          modalContent.style.animation = "";
      }, 300);
  }

  // Keyboard accessibility for modal
  document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && termsModal.style.display === "block") {
          closeModal();
      }
  });

  modalContent.addEventListener("click", function (event) {
      event.stopPropagation();
  });

  // Validation functions
  function validateRadio(input, errorDiv) {
      if (!input.checked) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "You must accept the Terms and Conditions";
          errorDiv.style.display = "block";
          return false;
      } else {
          input.classList.remove("error");
          input.classList.add("valid");
          errorDiv.style.display = "none";
          return true;
      }
  }

  function validatePassword(input, errorDiv) {
      const value = input.value.trim();
      const hasUpperCase = /[A-Z]/.test(value);
      const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(value);
      const isLengthValid = value.length >= 8;

      if (value === "") {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "This is a required field";
          errorDiv.style.display = "block";
          return false;
      } else if (!hasUpperCase) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "Password must include at least one uppercase letter";
          errorDiv.style.display = "block";
          return false;
      } else if (!hasSpecialChar) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "Password must include at least one special character (e.g., @, #, $)";
          errorDiv.style.display = "block";
          return false;
      } else if (!isLengthValid) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "Password must be at least 8 characters long";
          errorDiv.style.display = "block";
          return false;
      } else {
          input.classList.remove("error");
          input.classList.add("valid");
          errorDiv.style.display = "none";

          const confirmInput = document.getElementById("confirm_password");
          const confirmError = document.getElementById("confirm_passwordError");
          if (confirmInput.value.trim() !== "") {
              validateConfirmPassword(confirmInput, confirmError);
          }
          return true;
      }
  }

  function validateConfirmPassword(input, errorDiv) {
      const value = input.value.trim();
      const passwordValue = document.getElementById("password").value.trim();

      if (value === "") {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "This is a required field";
          errorDiv.style.display = "block";
          return false;
      } else if (value !== passwordValue) {
          input.classList.remove("valid");
          input.classList.add("error");
          errorDiv.textContent = "Passwords do not match";
          errorDiv.style.display = "block";
          return false;
      } else {
          input.classList.remove("error");
          input.classList.add("valid");
          errorDiv.style.display = "none";
          return true;
      }
  }

  function validateStep4() {
      let isValid = true;

      step4Fields.forEach((fieldId) => {
          const input = document.getElementById(fieldId);
          const errorDiv = document.getElementById(fieldId === "terms-acceptance" ? "radioError" : fieldId + "Error");

          if (fieldId === "terms-acceptance") {
              if (!validateRadio(input, errorDiv)) {
                  isValid = false;
              }
          } else if (!input.value.trim()) {
              input.classList.add("error");
              errorDiv.textContent = "This is a required field";
              errorDiv.style.display = "block";
              isValid = false;
          }
      });

      if (!isValid) return false;

      const passwordInput = document.getElementById("password");
      const passwordError = document.getElementById("passwordError");
      const confirmInput = document.getElementById("confirm_password");
      const confirmError = document.getElementById("confirm_passwordError");
      const radioInput = document.getElementById("terms-acceptance");
      const radioError = document.getElementById("radioError");

      const passwordValid = validatePassword(passwordInput, passwordError);
      const confirmValid = validateConfirmPassword(confirmInput, confirmError);
      const radioValid = validateRadio(radioInput, radioError);

      return passwordValid && confirmValid && radioValid;
  }

  // Register button handler
  registerBtn.addEventListener("click", function(event) {
      event.preventDefault();
      
      if (validateStep4()) {
          form.submit();
      }
  });

  // Next button handlers (excluding register button)
  document.querySelectorAll(".nextbtn:not(#registerBtn)").forEach((button) => {
      button.addEventListener("click", async () => {
          let isValid = true;

          if (currentStep === 1) {
              isValid = await validateStep1();
          } else if (currentStep === 2) {
              isValid = validateStep2();
          } else if (currentStep === 3) {
              isValid = await validateStep3();
          }

          if (!isValid) return;

          if (currentStep < stepContents.length) {
              currentStep++;
              showStep(currentStep);
          }
      });
  });

  // Previous button handlers
  document.querySelectorAll(".previousbtn").forEach((button) => {
      button.addEventListener("click", () => {
          if (currentStep > 1) {
              currentStep--;
              showStep(currentStep);
          }
      });
  });
});



function allowOnlyLettersAndSpace(event) {
  if (!/[a-zA-Z0-9 ]/.test(event.key)) {
    event.preventDefault();
  }
}

function allowOnlyNumbers(event) {
  // Get the character code from the event
  const charCode = (event.which) ? event.which : event.keyCode;
  
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

function validateNumber(input) {
  input.value = input.value.replace(/[^0-9]/g, "");
}

