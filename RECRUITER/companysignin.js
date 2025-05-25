document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("form");
  const emailInput = document.querySelector('input[name="email"]');
  const passwordInput = document.querySelector('input[name="password"]');
  const togglePassword = document.querySelector(".toggle-password");
  const emailError = document.getElementById("emailError");
  const passwordError = document.getElementById("passwordError");
  const formError = document.getElementById("formError");

  let wasEmailInvalid = false;
  let wasPasswordInvalid = false;

  function showError(errorDiv, message) {
    errorDiv.style.display = "block";
    errorDiv.textContent = message;
  }

  function hideError(errorDiv) {
    errorDiv.style.display = "none";
    errorDiv.textContent = "";
  }

  function validateEmail(email) {
    // Updated regex to require .com specifically
    const emailRegex = /^[^\s@]+@[^\s@]+\.com$/;
    return emailRegex.test(email);
  }

  function validatePassword(password) {
    // Updated to require 8 characters minimum
    return password.length >= 8;
  }

  // Add blur event listeners to remove styles when clicking outside
  emailInput.addEventListener("blur", function () {
    if (!wasEmailInvalid) {
      this.classList.remove("valid");
      this.classList.remove("input-error");
    }
  });

  passwordInput.addEventListener("blur", function () {
    if (!wasPasswordInvalid) {
      this.classList.remove("valid");
      this.classList.remove("input-error");
    }
  });

  emailInput.addEventListener("input", function () {
    if (this.value.trim() === "") {
      if (wasEmailInvalid) {
        this.classList.add("input-error");
        this.classList.remove("valid");
        showError(emailError, "Email is required");
      }
    } else if (!validateEmail(this.value)) {
      wasEmailInvalid = true;
      this.classList.remove("valid");
      this.classList.add("input-error");
      showError(
        emailError,
        "Please enter a valid email address"
      );
    } else {
      wasEmailInvalid = false;
      this.classList.remove("input-error");
      this.classList.add("valid");
      hideError(emailError);
    }
    
    // Hide form error when user starts typing
    hideError(formError);
  });

  passwordInput.addEventListener("input", function () {
    if (this.value.trim() === "") {
      if (wasPasswordInvalid) {
        this.classList.add("input-error");
        this.classList.remove("valid");
        showError(passwordError, "Password is required");
      }
    } else if (!validatePassword(this.value)) {
      wasPasswordInvalid = true;
      this.classList.remove("valid");
      this.classList.add("input-error");
      showError(passwordError, "Password must be at least 8 characters");
    } else {
      wasPasswordInvalid = false;
      this.classList.remove("input-error");
      this.classList.add("valid");
      hideError(passwordError);
    }
    
    // Hide form error when user starts typing
    hideError(formError);
  });

  // Toggle password visibility (unchanged)
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

  // Form submission
  form.addEventListener("submit", function (e) {
    e.preventDefault();

    let isValid = true;

    // Validate email
    if (emailInput.value.trim() === "" || !validateEmail(emailInput.value)) {
      wasEmailInvalid = true;
      emailInput.classList.add("input-error");
      emailInput.classList.remove("valid");
      showError(
        emailError,
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
      passwordInput.classList.remove("valid");
      showError(
        passwordError,
        passwordInput.value.trim() === ""
          ? "Password is required"
          : "Password must be at least 8 characters"
      );
      isValid = false;
    }

    if (isValid) {
      this.submit();
    }
  });
});