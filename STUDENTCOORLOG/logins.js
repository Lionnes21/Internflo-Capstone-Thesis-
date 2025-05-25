document.addEventListener("DOMContentLoaded", function () {
  // PASSWORD VISIBILITY TOGGLE FUNCTIONALITY
  const passwordField = document.getElementById("password");
  const togglePassword = document.querySelector(".toggle-password");

  if (togglePassword) {
    togglePassword.addEventListener("click", function () {
      const type =
        passwordField.getAttribute("type") === "password" ? "text" : "password";
      passwordField.setAttribute("type", type);
      this.classList.toggle("fa-eye");
      this.classList.toggle("fa-eye-slash");
    });
  }

  // VALIDATION FUNCTIONS
  function validateEmail(value) {
    // Check if it's a valid email
    const emailRegex = /^[^\s@]+@[^\s@]+\.com$/;
    const isEmail = value.trim() !== "" && emailRegex.test(value.trim());

    // Check if it's a valid student number
    const isStudentNumber = validateStudentNumber(value.trim());

    // Return true if it's either a valid email or a valid student number
    return isEmail || isStudentNumber;
  }

    function validateStudentNumber(value) {
      const regex = /^20\d{2}0\d{3}-[NnSs]$/;
      return regex.test(value);
    }
  function validatePassword(password) {
    return password.length >= 8;
  }

  // REAL-TIME VALIDATION LISTENERS
  const emailField = document.getElementById("email");

  emailField.addEventListener("input", function () {
    validateField(this);
    clearFormError();
  });

  passwordField.addEventListener("input", function () {
    validateField(this);
    clearFormError();
  });

  // FOCUS EVENT LISTENERS
  emailField.addEventListener("focus", function () {
    if (!this.classList.contains("invalid")) {
      applyFocusStyles(this);
    }
  });

  passwordField.addEventListener("focus", function () {
    if (!this.classList.contains("invalid")) {
      applyFocusStyles(this);
    }
  });

  // BLUR EVENT LISTENERS
  emailField.addEventListener("blur", function () {
    if (!this.classList.contains("invalid")) {
      removeFocusStyles(this);
    }
  });

  passwordField.addEventListener("blur", function () {
    if (!this.classList.contains("invalid")) {
      removeFocusStyles(this);
    }
  });

  // FIELD VALIDATION FUNCTION
  function validateField(inputField) {
    const value = inputField.value.trim();
    let isValid = false;
    let errorMessage = "";

    if (value === "") {
      errorMessage = `This field is required`;
    } else if (inputField.id === "email") {
      isValid = validateEmail(value);
      if (!isValid) {
        errorMessage =
          value === ""
            ? "This field is required"
            : "Invalid email address or student number";
      }
    } else if (inputField.id === "password") {
      isValid = validatePassword(value);
      if (!isValid) {
        errorMessage = "Password must be at least 8 characters";
      }
    }

    if (value !== "" && isValid) {
      applySuccessStyles(inputField);
    } else {
      applyErrorStyles(inputField, errorMessage);
    }
  }

  // APPLY FOCUS STYLES
  function applyFocusStyles(inputField) {
    if (!inputField.classList.contains("invalid")) {
      inputField.style.borderColor = "blue";
      inputField.style.boxShadow = "0 0 0 0.3rem rgba(0, 123, 255, 0.25)";
    }
  }

  // REMOVE FOCUS STYLES
  function removeFocusStyles(inputField) {
    if (!inputField.classList.contains("invalid")) {
      inputField.style.borderColor = "";
      inputField.style.boxShadow = "";
    }
  }

  // APPLY ERROR STYLES
  function applyErrorStyles(inputField, errorMessage) {
    inputField.classList.add("invalid");
    inputField.style.borderColor = "red";
    inputField.style.boxShadow = "0 0 0 0.3rem rgba(255, 0, 0, 0.25)";

    const errorElement = document.getElementById(inputField.id + "Error");
    if (errorElement) {
      errorElement.style.display = "block";
      errorElement.style.fontSize = "15px";
      errorElement.style.marginTop = "8px";
      errorElement.style.color = "red";
      errorElement.innerText = errorMessage;
    }
  }

  // APPLY SUCCESS STYLES
  function applySuccessStyles(inputField) {
    inputField.classList.remove("invalid");
    inputField.style.borderColor = "green";
    inputField.style.boxShadow = "none";

    const errorElement = document.getElementById(inputField.id + "Error");
    if (errorElement) {
      errorElement.style.display = "none";
    }
  }

  // FORM VALIDATION FUNCTION
  function validateForm() {
    const email = document.getElementById("email");
    const password = document.getElementById("password");

    clearErrorStyles(email);
    clearErrorStyles(password);

    const isEmailValid = validateEmail(email.value.trim());
    const isPasswordValid = validatePassword(password.value.trim());

    if (isEmailValid) {
      applySuccessStyles(email);
    } else {
      applyErrorStyles(
        email,
        email.value.trim() === ""
          ? "This field is required"
          : "Invalid email address or student number"
      );
    }

    if (isPasswordValid) {
      applySuccessStyles(password);
    } else {
      applyErrorStyles(
        password,
        password.value.trim() === ""
          ? "This field is required"
          : "Password must be at least 8 characters"
      );
    }

    return isEmailValid && isPasswordValid;
  }

  // CLEAR ERROR STYLES
  function clearErrorStyles(inputField) {
    inputField.style.borderColor = "";
    inputField.style.boxShadow = "";

    const errorElement = document.getElementById(inputField.id + "Error");
    if (errorElement) {
      errorElement.style.display = "none";
    }
  }

  // CLEAR FORM ERROR MESSAGE
  function clearFormError() {
    const formError = document.getElementById("formError");
    if (formError) {
      formError.style.display = "none";
    }
  }

  // FORM SUBMISSION EVENT LISTENER
  document
    .querySelector("form[name='loginForm']")
    .addEventListener("submit", function (event) {
      if (!validateForm()) {
        event.preventDefault();
      }
    });
});
