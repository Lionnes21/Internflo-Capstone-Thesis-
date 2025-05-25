document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const emailInput = document.querySelector('input[name="email"]');
  const passwordInput = document.querySelector('input[name="password"]');
  const togglePassword = document.querySelector(".toggle-password");
  const loginErrorContainer = document.getElementById("login-error-container");

  // Track if input has been invalid
  let wasEmailInvalid = false;
  let wasPasswordInvalid = false;

  function createErrorMessage(input, message) {
    const existingError = input.nextElementSibling;
    if (existingError && existingError.classList.contains("error-message")) {
      existingError.remove();
    }

    const errorDiv = document.createElement("div");
    errorDiv.className = "error-message";
    errorDiv.textContent = message;
    input.parentNode.insertBefore(errorDiv, input.nextSibling);
  }

  function removeErrorMessage(input) {
    const errorDiv = input.nextElementSibling;
    if (errorDiv && errorDiv.classList.contains("error-message")) {
      errorDiv.remove();
    }
  }

  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  function validatePassword(password) {
    return password.length >= 6;
  }

  // Add blur event listeners to remove valid class when clicking outside
  emailInput.addEventListener("blur", function () {
    this.classList.remove("valid");
  });

  passwordInput.addEventListener("blur", function () {
    this.classList.remove("valid");
  });

  emailInput.addEventListener("input", function () {
    // Clear login error when user starts typing
    if (loginErrorContainer) {
      loginErrorContainer.innerHTML = "";
      loginErrorContainer.classList.remove("show");
    }
    
    if (this.value.trim() === "") {
      // Keep red styling if it was previously invalid
      if (wasEmailInvalid) {
        this.classList.add("input-error");
        createErrorMessage(this, "Email is required");
      }
    } else if (!validateEmail(this.value)) {
      wasEmailInvalid = true;
      this.classList.remove("valid");
      this.classList.add("input-error");
      createErrorMessage(this, "Please enter a valid email address");
    } else {
      wasEmailInvalid = false;
      this.classList.remove("input-error");
      this.classList.add("valid");
      removeErrorMessage(this);
    }
  });

  passwordInput.addEventListener("input", function () {
    // Clear login error when user starts typing
    if (loginErrorContainer) {
      loginErrorContainer.innerHTML = "";
      loginErrorContainer.classList.remove("show");
    }
    
    if (this.value.trim() === "") {
      // Keep red styling if it was previously invalid
      if (wasPasswordInvalid) {
        this.classList.add("input-error");
        createErrorMessage(this, "Password is required");
      }
    } else if (!validatePassword(this.value)) {
      wasPasswordInvalid = true;
      this.classList.remove("valid");
      this.classList.add("input-error");
      createErrorMessage(this, "Password must be at least 6 characters");
    } else {
      wasPasswordInvalid = false;
      this.classList.remove("input-error");
      this.classList.add("valid");
      removeErrorMessage(this);
    }
  });

  // Toggle password visibility
  togglePassword.addEventListener("click", function () {
    const type =
      passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    if (type === "password") {
      this.classList.remove("fa-eye");
      this.classList.add("fa-eye-slash");
    } else {
      this.classList.remove("fa-eye-slash");
      this.classList.add("fa-eye");
    }
  });

  // Form submission - MODIFIED to allow server-side validation
  form.addEventListener("submit", function (e) {
    let isValid = true;

    // Validate email
    if (emailInput.value.trim() === "" || !validateEmail(emailInput.value)) {
      wasEmailInvalid = true;
      emailInput.classList.add("input-error");
      createErrorMessage(
        emailInput,
        emailInput.value.trim() === ""
          ? "Email is required"
          : "Please enter a valid email address"
      );
      isValid = false;
    }

    // Validate password
    if (
      passwordInput.value.trim() === "" ||
      !validatePassword(passwordInput.value)
    ) {
      wasPasswordInvalid = true;
      passwordInput.classList.add("input-error");
      createErrorMessage(
        passwordInput,
        passwordInput.value.trim() === ""
          ? "Password is required"
          : "Password must be at least 6 characters"
      );
      isValid = false;
    }

    // If the form is invalid, prevent submission
    if (!isValid) {
      e.preventDefault();
    }
    // Otherwise, allow the form to submit naturally to the server
    // No additional validation logic here that would block submission
  });
});