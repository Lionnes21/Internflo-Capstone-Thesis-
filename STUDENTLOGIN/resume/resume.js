function showTab(tabName) {
  // Hide all content sections
  document.querySelectorAll(".form-content > div").forEach((div) => {
    div.classList.remove("active");
  });

  // Show selected content section
  document.getElementById(tabName).classList.add("active");

  // Update tab styling
  document.querySelectorAll(".tab").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Find the clicked tab and add the active class
  let clickedTab = document.querySelector(
    `.tab[onclick="showTab('${tabName}')"]`
  );
  if (clickedTab) {
    clickedTab.classList.add("active");
  }

  // Close the dropdown if it's open
  document.querySelector(".dropdown-content").classList.remove("show");
}

function toggleDropdown(event) {
  event.stopPropagation();
  document.querySelector(".dropdown-content").classList.toggle("show");
}

// Close the dropdown if the user clicks outside of it
window.onclick = function (event) {
  if (!event.target.matches(".dropdown-toggle")) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    for (var i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains("show")) {
        openDropdown.classList.remove("show");
      }
    }
  }
};

// Global Variables
let experienceCounter = 1;
let educationCounter = 1;
let skillCounter = 0;
let certificateCounter = 0;

// ====================================
// Initialization Functions
// ====================================

function updateResume() {
  updateProfilePicture();
  updateTextInputs();
  updateCareerObjective();
  updateAllExperiences();
  updateAllEducation();
  updateAllSkills();
  updateAllCertifications(); // Add this line
}

function initializeExperience() {
  const addButton = document.querySelector(".experience-add-button");
  addButton.addEventListener("click", addExperienceForm);
  attachEventListeners(document.querySelector(".originalform"));
}

function initializeEducation() {
  const addButton = document.querySelector(".add-education");
  addButton.addEventListener("click", addEducationForm);
  attachEducationEventListeners(document.querySelector(".education-entry"));
}

function initializeSkills() {
  const addButton = document.getElementById("add-skill-btn");
  addButton.addEventListener("click", addSkillForm);
  const skillInput = document.getElementById("new-skill");
  skillInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      addSkillForm();
    }
  });
}

function initializeCertifications() {
  const addButton = document.querySelector(".add-certificate-button");
  addButton.addEventListener("click", addCertificationForm);
  attachCertificationEventListeners(document.querySelector(".certform"));
}

function initializeResumePreview() {
  const inputIds = ["name", "course", "email", "mobile", "address"];
  inputIds.forEach((id) => {
    const input = document.getElementById(id);
    const resumeElement = document.getElementById(`resume-${id}`);
    if (input.value) {
      resumeElement.textContent = input.value;
    }
  });

  const careerObjectiveInput = document.getElementById("career-objective");
  const resumeCareerObjective = document.getElementById(
    "resume-career-objective"
  );
  if (careerObjectiveInput.value) {
    resumeCareerObjective.textContent = careerObjectiveInput.value;
  }

  updateProfilePicture();
}

// ====================================
// Experience Functions
// ====================================
function updateAllExperiences() {
  const resumeExperience = document.getElementById(
    "resume-professional-experience"
  );
  let allExperiencesHTML = "";
  // Changed selector to get all forms in the experience-container
  const experienceForms = document.querySelectorAll(
    ".experience-container .originalform, .experience-container .experience-form"
  );

  experienceForms.forEach((form, index) => {
    const company =
      form.querySelector(`[id^="experience-company"]`)?.value || "";
    const position =
      form.querySelector(`[id^="experience-position"]`)?.value || "";
    const startDate = formatDate(
      form.querySelector(`[id^="experience-start-date"]`)?.value
    );
    const endDateInput = form.querySelector(`[id^="experience-end-date"]`);
    const currentlyWorking = form.querySelector(
      `[id^="currently-working"]`
    )?.checked;
    const endDate = currentlyWorking
      ? "Present"
      : formatDate(endDateInput?.value);
    const description =
      form.querySelector(`[id^="experience-description"]`)?.value || "";

    if (company || position || startDate || endDate || description) {
      allExperiencesHTML += `<strong>${company}, ${position}</strong><br>`;
      allExperiencesHTML += `<em>${startDate} - ${endDate}</em><br>`;
      allExperiencesHTML += `${description.replace(/\n/g, "<br>")}<br><br>`;
    }
  });

  resumeExperience.innerHTML =
    allExperiencesHTML || "Professional experience will appear here.";
}

// Also update the initialization function to handle both new and existing forms
function initializeExperience() {
  const addButton = document.querySelector(".experience-add-button");
  addButton.addEventListener("click", addExperienceForm);

  // Attach event listeners to all existing experience forms
  const existingForms = document.querySelectorAll(
    ".experience-container .originalform, .experience-container .experience-form"
  );
  existingForms.forEach((form) => {
    attachEventListeners(form);
  });

  // Set initial experience counter based on existing forms
  experienceCounter = existingForms.length;
}

function formatDate(dateString) {
  if (!dateString) return "";
  const date = new Date(dateString);
  const month = date.toLocaleString("default", { month: "short" });
  const year = date.getFullYear();
  return `${month} ${year}`;
}

function attachEventListeners(form) {
  const inputs = form.querySelectorAll("input, textarea");
  inputs.forEach((input) => {
    input.addEventListener("input", updateAllExperiences);
  });

  const checkbox = form.querySelector('[id^="currently-working"]');
  const endDateInput = form.querySelector('[id^="experience-end-date"]');
  if (checkbox && endDateInput) {
    checkbox.addEventListener("change", () => {
      endDateInput.disabled = checkbox.checked;
      updateAllExperiences();
    });
    // Set initial state of end date input based on checkbox
    endDateInput.disabled = checkbox.checked;
  }
}

function addExperienceForm() {
  const experienceSection = document.querySelector(".experience-container");
  const newForm = document.createElement("div");
  newForm.className = "originalform";
  newForm.innerHTML = `
      <input type="text" class="experience-input" id="experience-company-${experienceCounter}" name="experience[${experienceCounter}][company]" placeholder="Company" oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1)" />
      <input type="text" class="experience-input" id="experience-position-${experienceCounter}" name="experience[${experienceCounter}][position]" placeholder="Position" oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1)" />
      <div class="experience-date-container">
        <div class="experience-date-field">
          <label for="experience-start-date-${experienceCounter}">Start Date</label>
          <input type="date" class="form-control experience-input" name="experience[${experienceCounter}][start_date]" id="experience-start-date-${experienceCounter}" />
        </div>
        <div class="experience-date-field">
          <label for="experience-end-date-${experienceCounter}">End Date</label>
          <input type="date" class="form-control experience-input" name="experience[${experienceCounter}][end_date]" id="experience-end-date-${experienceCounter}" />
        </div>
      </div>
      <div class="experience-checkbox-container">
        <input type="checkbox" name="experience[${experienceCounter}][currently_working]" id="currently-working-${experienceCounter}" />
        <label for="currently-working-${experienceCounter}" class="experience-checkboxlabel">Currently working here</label>
      </div>
      <textarea class="form-control experience-input experience-textarea" id="experience-description-${experienceCounter}" name="experience[${experienceCounter}][description]" placeholder="Job Description" oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);" ></textarea>
    `;

  experienceSection.appendChild(newForm);
  attachEventListeners(newForm);
  experienceCounter++;
  updateAllExperiences(); // Update preview after adding new form
}

// ====================================
// Education Functions
// ====================================

function updateAllEducation() {
  const resumeEducation = document.getElementById("resume-education");
  let allEducationHTML = "";
  // Changed selector to get all education entries
  const educationForms = document.querySelectorAll(
    "#education-entries .education-entry"
  );

  educationForms.forEach((form, index) => {
    const school = form.querySelector(`[id^="school"]`)?.value || "";
    const startYear = form.querySelector(`[id^="start-year"]`)?.value || "";
    const endYearInput = form.querySelector(`[id^="end-year"]`);
    const currentlyStudying = form.querySelector(
      `[id^="current-study"]`
    )?.checked;
    const endYear = currentlyStudying ? "Present" : endYearInput?.value || "";
    const degree = form.querySelector(`[id^="degree"]`)?.value || ""; // Added degree field

    if (school || startYear || endYear || degree) {
      allEducationHTML += `<strong>${school}</strong><br>`;
      if (degree) {
        allEducationHTML += `<em>${degree}</em><br>`;
      }
      allEducationHTML += `<em>${startYear} - ${endYear}</em>`;
      allEducationHTML += `<div style="margin-bottom: 7px;"></div>`;
    }
  });

  resumeEducation.innerHTML =
    allEducationHTML || "Education details will appear here.";
}

function attachEducationEventListeners(entry) {
  // Add listeners to all input elements
  const inputs = entry.querySelectorAll("input");
  inputs.forEach((input) => {
    input.addEventListener("input", () => {
      updateAllEducation();
    });
  });

  // Handle the currently studying checkbox
  const checkbox = entry.querySelector(`[id^="current-study"]`);
  const endYearInput = entry.querySelector(`[id^="end-year"]`);
  if (checkbox && endYearInput) {
    checkbox.addEventListener("change", () => {
      endYearInput.disabled = checkbox.checked;
      updateAllEducation();
    });
    // Set initial state
    endYearInput.disabled = checkbox.checked;
  }
}

function initializeEducation() {
  const addButton = document.querySelector(".add-education");
  addButton.addEventListener("click", addEducationForm);

  // Initialize existing education entries
  const existingEntries = document.querySelectorAll(".education-entry");
  existingEntries.forEach((entry) => {
    attachEducationEventListeners(entry);
  });

  // Set initial counter based on existing entries
  educationCounter = existingEntries.length;

  // Initial update
  updateAllEducation();
}

function addEducationForm() {
  const educationEntries = document.getElementById("education-entries");
  const newEntry = document.createElement("div");
  newEntry.className = "education-entry";
  newEntry.innerHTML = `
      <input class="education-input" type="text" 
             name="education[${educationCounter}][school]" 
             id="school-${educationCounter}" 
             placeholder="School/University" 
             oninput="this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);">
      


      <div class="date-container">
          <div class="date-field">
              <label for="start-year-${educationCounter}">Start Year</label>
              <input class="education-input" type="number" 
                     name="education[${educationCounter}][start_year]" 
                     id="start-year-${educationCounter}" 
                     placeholder="YYYY" min="1900" max="2099" step="1" 
                     oninput="this.value = this.value.slice(0, 4);">
          </div>
          <div class="date-field">
              <label for="end-year-${educationCounter}">End Year</label>
              <input class="education-input" type="number" 
                     name="education[${educationCounter}][end_year]" 
                     id="end-year-${educationCounter}" 
                     placeholder="YYYY" min="1900" max="2099" step="1" 
                     oninput="this.value = this.value.slice(0, 4);">
          </div>
      </div>

      <div class="education-checkbox-container">
          <input type="checkbox" 
                 name="education[${educationCounter}][current_study]" 
                 id="current-study-${educationCounter}">
          <label class="checkboxlabel" 
                 for="current-study-${educationCounter}">Currently studying here</label>
      </div>
  `;
  educationEntries.appendChild(newEntry);
  attachEducationEventListeners(newEntry);
  educationCounter++;
  updateAllEducation(); // Update preview after adding new form
}

// ====================================
// Skills Functions
// ====================================
// Skills Functions
function updateAllSkills() {
  const resumeCapabilities = document.getElementById("resume-capabilities");
  let allSkillsHTML = "";
  const skillInputs = document.querySelectorAll(".skill-input");

  skillInputs.forEach((input) => {
    const skill = input.value.trim();
    if (skill) {
      allSkillsHTML += `<li>${skill}</li>`;
    }
  });

  resumeCapabilities.innerHTML =
    allSkillsHTML || "<li>Capabilities will appear here</li>";
}

function addSkillForm() {
  const skillsSection = document.getElementById("skills");
  const addSkillSection = document.getElementById("add-skill-section");

  const newSkillInput = document.createElement("input");
  newSkillInput.type = "text";
  newSkillInput.className = "skill-input";
  newSkillInput.name = "skills[]";
  newSkillInput.id = `skill-${skillCounter}`;
  newSkillInput.placeholder = "Skill";

  // Add input event listener for real-time updates
  newSkillInput.addEventListener("input", (e) => {
    e.target.value = e.target.value
      .replace(/[^A-Za-z,\s]/g, "")
      .replace(/^./, e.target.value.charAt(0).toUpperCase());
    updateAllSkills();
  });

  skillsSection.insertBefore(newSkillInput, addSkillSection);
  skillCounter++;
  updateAllSkills();

  return newSkillInput;
}

function initializeSkills() {
  const addButton = document.getElementById("add-skill-btn");
  const skillsList = document.getElementById("skills-list");
  const initialSkillInput = document.getElementById("new-skill");

  // Initialize all existing skill inputs
  const existingSkillInputs = document.querySelectorAll(".skill-input");
  existingSkillInputs.forEach((input) => {
    input.addEventListener("input", (e) => {
      e.target.value = e.target.value
        .replace(/[^A-Za-z,\s]/g, "")
        .replace(/^./, e.target.value.charAt(0).toUpperCase());
      updateAllSkills();
    });
  });

  // Initialize the add button click handler
  addButton.addEventListener("click", () => {
    if (initialSkillInput.value.trim()) {
      const newInput = addSkillForm();
      newInput.value = initialSkillInput.value;
      initialSkillInput.value = "";
      updateAllSkills();
    } else {
      addSkillForm();
    }
  });

  // Initialize the initial skill input
  initialSkillInput.addEventListener("input", (e) => {
    e.target.value = e.target.value
      .replace(/[^A-Za-z,\s]/g, "")
      .replace(/^./, e.target.value.charAt(0).toUpperCase());
    updateAllSkills();
  });

  initialSkillInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter" && this.value.trim()) {
      const newInput = addSkillForm();
      newInput.value = this.value;
      this.value = "";
      updateAllSkills();
    }
  });

  // Initialize skill badges click handling
  skillsList.addEventListener("click", function (e) {
    if (e.target.classList.contains("skill-badge")) {
      const skillText = e.target.textContent;

      // Check if skill already exists
      const existingInputs = document.querySelectorAll(".skill-input");
      const skillExists = Array.from(existingInputs).some(
        (input) => input.value.trim().toLowerCase() === skillText.toLowerCase()
      );

      if (!skillExists) {
        if (!initialSkillInput.value.trim()) {
          initialSkillInput.value = skillText;
        } else {
          const newInput = addSkillForm();
          newInput.value = skillText;
        }
        updateAllSkills();
      }
    }
  });

  // Initial update of skills display
  updateAllSkills();
}

// ====================================
// Utility Functions
// ====================================

function printResume() {
  window.print();
}

// ====================================
// Profile Picture Functions
// ====================================

function updateProfilePicture() {
  const profilePicInput = document.getElementById("profile-pic-input");
  const profilePic = document.getElementById("resume-profile-pic");
}

function updateProfilePic(event) {
  const input = event.target;
  const profilePic = document.getElementById("resume-profile-pic");
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      profilePic.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function updateSignaturePic(event) {
  const input = event.target;
  const signaturePic = document.getElementById("resume-signature-pic");
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      signaturePic.src = e.target.result;
      signaturePic.style.display = "block";
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function updateImage(event, elementId) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      const resumeElement = document.getElementById(elementId);
      resumeElement.src = e.target.result;
      resumeElement.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

function updateFileName(input, spanId) {
  const fileName = input.files[0] ? input.files[0].name : "No file chosen";
  document.getElementById(spanId).textContent = fileName;
}

// ====================================
// Text Input Functions
// ====================================

function updateTextInputs() {
  const inputIds = ["name", "course", "email", "mobile", "address"];

  inputIds.forEach((id) => {
    const input = document.getElementById(id);
    const resumeElement = document.getElementById(`resume-${id}`);

    input.addEventListener("input", () => {
      // Capitalize 'name' and 'course' fields and set placeholders accordingly
      if (id === "name" || id === "course") {
        resumeElement.textContent =
          input.value.toUpperCase() || id.toUpperCase();
      } else {
        resumeElement.textContent =
          input.value || `${id.charAt(0).toUpperCase() + id.slice(1)}`;
      }
    });
  });
}

function updateCareerObjective() {
  const careerObjectiveInput = document.getElementById("career-objective");
  const resumeCareerObjective = document.getElementById(
    "resume-career-objective"
  );
  careerObjectiveInput.addEventListener("input", () => {
    resumeCareerObjective.textContent =
      careerObjectiveInput.value || "Career objective will appear here.";
  });
}

// ====================================
// Certification Functions
// ====================================
function formatCertDate(dateString) {
  // No conversion for start_date since it's now a text input
  return dateString ? dateString : "";
}

function formatEndDate(year) {
  // End date is a numeric input (year), so we just return the year
  return year ? year : "";
}

function updateAllCertifications() {
  const resumeCertifications = document.getElementById("resume-certifications");
  let allCertificationsHTML = "";

  // Get all certificate forms, both existing and new
  const certificationForms = document.querySelectorAll(
    ".cert-container .certform"
  );

  certificationForms.forEach((form, index) => {
    // Handle both new and existing certificate fields
    const certNameInput = form.querySelector(`[name^="certificate-name"]`);
    const orgInput = form.querySelector(`[name^="issuing-organization"]`);
    const startDateInput = form.querySelector(`[name^="start-date"]`);
    const endDateInput = form.querySelector(`[name^="end-date"]`);

    const certName = certNameInput?.value || "";
    const organization = orgInput?.value || "";
    const startDate = startDateInput?.value
      ? formatCertDate(startDateInput.value)
      : "";
    const endDate = endDateInput?.value
      ? formatEndDate(endDateInput.value)
      : "";

    if (certName || organization || startDate || endDate) {
      allCertificationsHTML += `
        <li>
          <strong>${certName}</strong> - ${organization}<br>
          <em>${startDate} - ${endDate}</em>
        </li><br>`;
    }
  });

  resumeCertifications.innerHTML =
    allCertificationsHTML || "<li>Your certifications will appear here</li>";
}

function attachCertificationEventListeners(form) {
  const inputs = form.querySelectorAll("input");
  inputs.forEach((input) => {
    input.addEventListener("input", updateAllCertifications);
  });
}

function addCertificationForm() {
  const certificatesSection = document.querySelector(".cert-container");
  const newForm = document.createElement("div");
  newForm.className = "certform";

  // Updated to match the new HTML input types
  newForm.innerHTML = `
    <input type="text" class="cert-input" name="certificate-name[${certificateCounter}]" 
      id="certificate-name-${certificateCounter}" 
      placeholder="Certificate Name" 
      oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
    <input type="text" class="cert-input" name="issuing-organization[${certificateCounter}]" 
      id="issuing-organization-${certificateCounter}" 
      placeholder="Issuing Organization" 
      oninput="this.value = this.value.replace(/^./, this.value.charAt(0).toUpperCase());" />
    <div class="cert-date-container">
      <div class="cert-date-field">
        <label for="start-date-${certificateCounter}">Start Date</label>
        <input type="text" class="cert-input" name="start-date[${certificateCounter}]" 
          id="start-date-${certificateCounter}" placeholder="Enter start date as text" />
      </div>
      <div class="cert-date-field">
        <label for="end-date-${certificateCounter}">End Date</label>
        <input type="number" class="cert-input" name="end-date[${certificateCounter}]" 
          id="end-date-${certificateCounter}" placeholder="Enter year as number" />
      </div>
    </div>
  `;

  // Insert before the add button
  const addButton = document.querySelector(".add-certificate-button");
  certificatesSection.insertBefore(newForm, addButton);

  attachCertificationEventListeners(newForm);
  certificateCounter++;
  updateAllCertifications();
}

function initializeCertifications() {
  // Set initial counter based on existing certificates
  const existingForms = document.querySelectorAll(".cert-container .certform");
  certificateCounter = existingForms.length;

  // Change this line - use the correct button class
  const addButton = document.querySelector(".add-certificate-button"); // FIXED: was using '.experience-add-button'
  if (addButton) {
    addButton.addEventListener("click", addCertificationForm);
  } else {
    console.error("Certificate add button not found"); // For debugging
  }

  // Attach listeners to all existing forms
  existingForms.forEach((form) => {
    attachCertificationEventListeners(form);
  });

  // Initial update of the preview
  updateAllCertifications();
}

// ====================================
// Event Listeners
// ====================================

document.addEventListener("DOMContentLoaded", () => {
  initializeResumePreview();
  updateResume();
  initializeExperience();
  initializeEducation();
  initializeSkills();
  initializeCertifications();
  updateAllSkills();
});
