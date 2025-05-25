document.addEventListener('DOMContentLoaded', function() {
    const emailField = document.getElementById('email');
    const emailError = document.getElementById('emailError');
    const form = document.querySelector('form[name="resetForm"]');
    let isErrorShown = false;

    function validateEmailFormat(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i;
        return emailPattern.test(email) && email.endsWith('.com');
    }

    function setDefaultStyle() {
        emailField.style.borderColor = "";
        emailField.style.boxShadow = "";
        emailError.style.display = "none";
        isErrorShown = false;
    }

    function setErrorStyle(message) {
        emailField.style.borderColor = "red";
        emailField.style.boxShadow = "0 0 0 0.3rem rgba(255, 0, 0, 0.25)";
        if (message) {
            emailError.style.display = "block";
            emailError.innerText = message;
        }
        isErrorShown = true;
    }

    function setGreenBorder() {
        emailField.style.borderColor = "green";
        emailField.style.boxShadow = "none";
        emailError.style.display = "none";
        isErrorShown = false;
    }

    function validateEmail(showRequiredError = false) {
        const emailValue = emailField.value.trim();

        if (emailValue === "") {
            if (showRequiredError) {
                setErrorStyle("Email is required");
            } else {
                setErrorStyle(); // This will only set the red border without showing the message
            }
            return false;
        } else if (!validateEmailFormat(emailValue)) {
            setErrorStyle("Please enter a valid email address");
            return false;
        } else {
            // Valid email: Set green border
            setGreenBorder();
            return true;
        }
    }

    emailField.addEventListener('input', function() {
        validateEmail();
    });

    emailField.addEventListener('focus', function() {
        // Apply blue border if not showing an error, green border, or after resetting
        if (!isErrorShown && emailField.style.borderColor !== "green") {
            emailField.style.borderColor = "blue";
            emailField.style.boxShadow = "0 0 0 0.3rem rgba(0, 123, 255, 0.25)";
        }
    });

    emailField.addEventListener('blur', function() {
        // Clear green border on blur if it exists
        if (emailField.style.borderColor === "green") {
            setDefaultStyle(); // Clear green border and reset styles
        } else if (!isErrorShown) {
            // Reset styles if there is no error
            if (emailField.value.trim() === "") {
                setDefaultStyle();
            } else {
                // If it's not empty, also clear the blue border on blur
                emailField.style.borderColor = ""; 
                emailField.style.boxShadow = ""; 
            }
        }
    });

    form.addEventListener('submit', function(event) {
        if (!validateEmail(true)) {
            event.preventDefault();
        }
    });
});
