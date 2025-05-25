function allowOnlyLetters(event) {
  const char = String.fromCharCode(event.which);
  if (!/^[a-zA-Z\s]$/.test(char)) {
    event.preventDefault();
  }
}

function capitalizeFirstLetter(input) {
  if (input.value.length === 1) {
    input.value =
      input.value.charAt(0).toUpperCase() + input.value.slice(1).toLowerCase();
  }
}

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
    ul.innerHTML = listItems.join("\n");
  });

  tempDiv.querySelectorAll("ol").forEach((ol) => {
    // Convert ordered lists by extracting text content and splitting into numbered lines
    const listItems = Array.from(ol.querySelectorAll("li")).map((li) =>
      li.textContent.trim()
    );
    ol.innerHTML = listItems
      .map((item, index) => `${index + 1}. ${item}`)
      .join("\n");
  });

  // Remove all HTML tags
  const plainText = tempDiv.textContent.trim();
  return plainText;
}

function validateRadioGroup() {
  const radioGroup = document.querySelector(".radio-group");
  const radios = radioGroup.querySelectorAll('input[type="radio"]');
  const isChecked = Array.from(radios).some((radio) => radio.checked);

  // Remove any existing error message
  const existingError = radioGroup.querySelector(".radio-error");
  if (existingError) {
    existingError.remove();
  }

  if (!isChecked) {
    const errorMessage = document.createElement("div");
    errorMessage.className = "radio-error";
    errorMessage.textContent = "Please select an internship type";
    radioGroup.appendChild(errorMessage);
    return false;
  }
  return true;
}

// Current step tracker
let currentStep = 1;

// Function to add error message
function addErrorMessage(element, message) {
  removeErrorMessage(element);
  const errorDiv = document.createElement("span");
  errorDiv.className = "error-message";
  errorDiv.textContent = message;

  // For editors, add the error message after the editor-container
  if (element.classList.contains("editor")) {
    const editorContainer = element.closest(".editor-container");
    editorContainer.parentNode.insertBefore(
      errorDiv,
      editorContainer.nextSibling
    );
  } else {
    element.parentNode.insertBefore(errorDiv, element.nextSibling);
  }
}

// Function to remove error message
function removeErrorMessage(element) {
  // For editors, look for error message after the editor-container
  if (element.classList.contains("editor")) {
    const editorContainer = element.closest(".editor-container");
    const nextSibling = editorContainer.nextSibling;
    if (nextSibling && nextSibling.className === "error-message") {
      nextSibling.remove();
    }
  } else {
    const nextSibling = element.nextSibling;
    if (nextSibling && nextSibling.className === "error-message") {
      nextSibling.remove();
    }
  }
}

// Rich text editor commands
function execCommand(command) {
  document.execCommand(command, false, null);
}

// Basic form validation for each step
function validateStep(stepNumber) {
  const step = document.getElementById(`step-${stepNumber}`);
  let isValid = true;

  if (stepNumber === 1) {
    // Validate radio group
    if (!validateRadioGroup()) {
      isValid = false;
    }

    // Rest of your existing validation for step 1
    const inputs = step.querySelectorAll(
      'input[type="text"], input[type="number"]'
    );
    inputs.forEach((input) => {
      if (!input.value.trim()) {
        isValid = false;
        input.classList.add("input-error");
        addErrorMessage(input, "This field is required");
      }
    });
    
    // Add validation for select elements
    const selects = step.querySelectorAll('select');
    selects.forEach((select) => {
      if (!select.value) {
        isValid = false;
        select.classList.add("input-error");
        addErrorMessage(select, "Please select an option");
      }
    });
  }

  // Enhanced validation for step 2
  if (stepNumber === 2) {
    const descriptionEditor = document.getElementById(
      "internship-description-editor"
    );
    const summaryEditor = document.getElementById("internship-summary-editor");

    if (!descriptionEditor.textContent.trim()) {
      isValid = false;
      descriptionEditor
        .closest(".editor-container")
        .classList.add("input-error");
      addErrorMessage(descriptionEditor, "This field is required");
    } else {
      // Update hidden input when validation passes
      document.getElementById("internship-description").value =
        convertRichTextToPlainText(descriptionEditor);
    }

    if (!summaryEditor.textContent.trim()) {
      isValid = false;
      summaryEditor.closest(".editor-container").classList.add("input-error");
      addErrorMessage(summaryEditor, "This field is required");
    } else {
      // Update hidden input when validation passes
      document.getElementById("internship-summary").value =
        convertRichTextToPlainText(summaryEditor);
    }
    
    // Add validation for select elements
    const selects = step.querySelectorAll('select');
    selects.forEach((select) => {
      if (!select.value) {
        isValid = false;
        select.classList.add("input-error");
        addErrorMessage(select, "Please select an option");
      }
    });
  }

  return isValid;
}

// Event listeners for radio buttons
document
  .querySelectorAll('input[type="radio"][name="internship-type"]')
  .forEach((radio) => {
    radio.addEventListener("change", () => {
      const radioGroup = document.querySelector(".radio-group");
      const errorMessage = radioGroup.querySelector(".radio-error");
      if (errorMessage) {
        errorMessage.remove();
      }
    });
  });

// Event listener for DOM content loaded
document.addEventListener("DOMContentLoaded", function () {
  // Setup rich text editors
  const editors = document.querySelectorAll(".editor");
  editors.forEach((editor) => {
    let hasInteracted = false;

    editor.addEventListener("input", function () {
      if (!hasInteracted) {
        hasInteracted = true;
      }

      const editorContainer = this.closest(".editor-container");
      if (this.textContent.trim()) {
        editorContainer.classList.remove("input-error");
        editorContainer.classList.add("valid");
        removeErrorMessage(this);
      } else if (hasInteracted) {
        editorContainer.classList.add("input-error");
        editorContainer.classList.remove("valid");
        addErrorMessage(this, "This field is required");
      }
    });

    // Updated blur event handler for editors
    editor.addEventListener("blur", function () {
      const editorContainer = this.closest(".editor-container");
      if (hasInteracted) {
        if (!this.textContent.trim()) {
          editorContainer.classList.add("input-error");
          editorContainer.classList.remove("valid");
          addErrorMessage(this, "This field is required");
        } else {
          // Remove the valid class on blur even if the content is valid
          editorContainer.classList.remove("valid");
        }
      }
    });
  });

  // Regular input handling
  document.querySelectorAll("input, textarea").forEach((element) => {
    let hasInteracted = false;

    element.addEventListener("input", function () {
      if (!hasInteracted) {
        hasInteracted = true;
      }

      if (this.value.trim()) {
        this.classList.remove("input-error");
        this.classList.add("valid");
        removeErrorMessage(this);
      } else if (hasInteracted) {
        this.classList.add("input-error");
        this.classList.remove("valid");
        addErrorMessage(this, "This field is required");
      }
    });

    // Updated blur event handler
    element.addEventListener("blur", function () {
      if (hasInteracted) {
        if (!this.value.trim()) {
          this.classList.add("input-error");
          this.classList.remove("valid");
          addErrorMessage(this, "This field is required");
        } else {
          // Remove the valid class on blur even if the input is valid
          this.classList.remove("valid");
        }
      }
    });
  });
  
  // Select input handling
  document.querySelectorAll("select").forEach((element) => {
    let hasInteracted = false;
    element.addEventListener("change", function() {
      if (!hasInteracted) {
        hasInteracted = true;
      }
      if (this.value) {
        this.classList.remove("input-error");
        this.classList.add("valid");
        removeErrorMessage(this);
      } else if (hasInteracted) {
        this.classList.add("input-error");
        this.classList.remove("valid");
        addErrorMessage(this, "Please select an option");
      }
    });
    element.addEventListener("blur", function() {
      if (hasInteracted) {
        if (!this.value) {
          this.classList.add("input-error");
          this.classList.remove("valid");
          addErrorMessage(this, "Please select an option");
        } else {
          this.classList.remove("valid");
        }
      }
    });
  });

  // Keep track of button states for rich text editor
  const editorContainers = document.querySelectorAll(".editor");
  editorContainers.forEach((editor) => {
    editor.addEventListener("keyup", updateButtonStates);
    editor.addEventListener("mouseup", updateButtonStates);
  });
});

// Update rich text editor button states
function updateButtonStates() {
  const buttons = document.querySelectorAll(".toolbar button");
  buttons.forEach((button) => {
    const commandAttr = button.getAttribute("onclick");
    if (commandAttr) {
      const command = commandAttr.split("'")[1];
      if (["bold", "italic", "underline"].includes(command)) {
        if (document.queryCommandState(command)) {
          button.classList.add("active");
        } else {
          button.classList.remove("active");
        }
      }
    }
  });
}

// Function to show a specific step
function showStep(stepNumber) {
  document.querySelectorAll(".form-step").forEach((step) => {
    step.style.display = "none";
  });
  document.getElementById(`step-${stepNumber}`).style.display = "block";
}

// Function to update progress bar
function updateProgressBar(stepNumber) {
  const steps = document.querySelectorAll(".progress-bar .step");
  steps.forEach((step, index) => {
    if (index < stepNumber) {
      step.classList.add("active");
    } else {
      step.classList.remove("active");
    }
  });
}

// Function to go to next step
function nextStep(currentStepNumber) {
  if (validateStep(currentStepNumber)) {
    currentStep = currentStepNumber + 1;
    showStep(currentStep);
    updateProgressBar(currentStep);
  }
}

// Function to go to previous step
function prevStep(currentStepNumber) {
  currentStep = currentStepNumber - 1;
  showStep(currentStep);
  updateProgressBar(currentStep);
}

// Form submission handler
document
  .getElementById("internship-creation-form")
  .addEventListener("submit", function (e) {
    e.preventDefault();

    const step3 = document.getElementById("step-3");
    let isValid = true;

    // Validate rich text editors in step 3
    const editorFields = [
      "requirements",
      "qualifications",
      "skills-required",
      "additional-info",
    ];

    editorFields.forEach((field) => {
      const editor = document.getElementById(`${field}-editor`);
      const editorContainer = editor.closest(".editor-container");

      if (!editor.textContent.trim()) {
        isValid = false;
        editorContainer.classList.add("input-error");
        removeErrorMessage(editor);
        addErrorMessage(editor, "This field is required");
      } else {
        editorContainer.classList.remove("input-error");
        removeErrorMessage(editor);
      }
    });

    // Validate application deadline
    const deadlineInput = document.getElementById("application-deadline");
    if (!deadlineInput.value) {
      isValid = false;
      deadlineInput.classList.add("input-error");
      removeErrorMessage(deadlineInput);
      addErrorMessage(deadlineInput, "Please select a deadline date");
    } else {
      deadlineInput.classList.remove("input-error");
      removeErrorMessage(deadlineInput);
    }
    
    // Validate select elements in step 3
    const selects = step3.querySelectorAll('select');
    selects.forEach((select) => {
      if (!select.value) {
        isValid = false;
        select.classList.add("input-error");
        removeErrorMessage(select);
        addErrorMessage(select, "Please select an option");
      } else {
        select.classList.remove("input-error");
        removeErrorMessage(select);
      }
    });

    if (isValid) {
      // Update all hidden inputs with plain text content
      editorFields.forEach((field) => {
        const editor = document.getElementById(`${field}-editor`);
        const input = document.getElementById(field);
        if (editor && input) {
          input.value = convertRichTextToPlainText(editor);
        }
      });

      const formData = new FormData(this);

      // Log form data for testing
      for (let pair of formData.entries()) {
        console.log(pair[0] + ": " + pair[1]);
      }

      this.submit(); // Actually submits the form after validation
    } else {
      // Scroll to the first error
      const firstError = step3.querySelector(".input-error");
      if (firstError) {
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  });
