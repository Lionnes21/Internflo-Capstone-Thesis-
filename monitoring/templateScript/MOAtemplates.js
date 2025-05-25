document.addEventListener('DOMContentLoaded', function() {
    // Trigger initial preview
    updatePreview();
            
    // Add event listeners to all form inputs
    const form = document.getElementById('moaForm');
    const inputs = form.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', updatePreview);
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', updatePreview);
        }
    });
});

function addStudent() {
    const studentsContainer = document.getElementById('students');
    const newEntry = document.createElement('div');
    newEntry.className = 'student-entry';
    newEntry.innerHTML = `
        <input type="text" name="student_names[]" placeholder="Student Name" required>
        <button type="button" onclick="removeStudent(this)" class="remove-btn">Remove</button>
    `;
    studentsContainer.appendChild(newEntry);
    updatePreview();
}

function removeStudent(button) {
    button.parentElement.remove();
    updatePreview();
}

function updatePreview() {
    const formData = new FormData(document.getElementById('moaForm'));
    const preview = document.getElementById('moaPreview');
    
    // Get all student names and join them with commas
    const studentNames = Array.from(formData.getAll('student_names[]'))
        .filter(name => name.trim() !== '')
        .join(', ');
    
    // Get current year
    const currentYear = new Date().getFullYear();
    
    let moaContent = `
                        <div class="page">
                            <h3 style="text-align: center;">MEMORANDUM OF AGREEMENT</h3>
                            <p class="text-block">KNOW ALL MEN BY THESE PRESENTS</p>
                            
                            <p class="text-block indent">This Memorandum of Agreement was made and entered into by and between:</p>
                            
                            <p class="text-block indent">The <strong>UNIVERSITY OF CALOOCAN CITY</strong>, a locally funded public university created and existing under the laws of the Philippines, with principal address at Biglang Awa corner Cattleya St., EDSA, Caloocan City and represented by its Vice- President for Academic Affairs, Atty. Roderick P. Vera, hereinafter referred to as the "<strong>UNIVERSITY</strong>";</p>
                            
                            <p style="text-align: center;">-and-</p>
                            
                            <p class="text-block indent">The <strong>${formData.get('ojt_company')|| '(OJT COMPANY)'}</strong>, ${formData.get('company_description')|| '(COMPANY DESC.)'}, with office address at ${formData.get('company_address')|| '(COMPANY ADDRESS)'},<strong> ${formData.get('company_representative')|| '(COMPANY REPRESENTATIVE)'}</strong>, referred to as the "<strong>OJT COMPANY</strong>".</p>
        
                            <p style="text-align: center;"><strong>WITNESSETH THAT:</strong></p>
        
                            <p class="text-block indent"><strong>WHEREAS</strong>, the <strong>UNIVERSITY</strong> is a duly recognized higher education institution that offers an <strong>${formData.get('student_course')|| '(COURSE)'}</strong>, with a curriculum requirement that the students enrolled therein undergo an <strong>Internship Program (OJT)</strong> where qualified students will undergo industry exposure prior to their graduation in order to become adequately familiar to the actual industry operations and management, thereby augmenting their formal training;</p>
        
                            <p class="text-block indent"><strong>WHEREAS</strong>, <strong>OJT COMPANY</strong> supports the <strong>Internship Program</strong> of the <strong>UNIVERSITY</strong>, and agrees to accept and accommodate the qualified students of the College of Liberty Arts and Sciences of the <strong>UNIVERSITY</strong>; and,</p>
        
                            <p class="text-block indent"><strong>WHEREAS</strong>, the <strong>UNIVERSITY</strong> and <strong>OJT COMPANY</strong> shall endeavor to ensure that the <strong>Internship Program</strong> will forge strong academic-industry linkage between them and that it will complement existing course curricula to match industry demand.</p>
        
                            <p class="text-block indent"><strong>NOW, THEREFORE,</strong> for and in consideration of the foregoing premises, the <strong>UNIVERSITY</strong> and <strong>OJT COMPANY</strong> do hereby agree and stipulate on the following:</p>
        
                <p>A.&emsp;&emsp;The<strong> UNIVERSITY</strong> shall:</p>
                <ol>
                    <li>Recommend qualified students who will undergo the <strong>Internship Program</strong> taking into consideration the requirements of <strong>OJT COMPANY</strong> in terms of qualification and the number of students;</li>
                    <li>Submit the documents required for the <strong>Internship Program</strong> to <strong>OJT COMPANY</strong>; and,</li>
                    <li>Together with the students and parents concerned, renounce and waive any claim against <strong>OJT COMPANY</strong> for any injury that the student-trainee <strong>${studentNames || '(STUDENT NAME/S)'}</strong> may sustain or any loss that they may suffer, personal, or pecuniary, arising from the negligence in the performance of their duties and functions while under training, except those which may arise from the willful or negligent act or omission of <strong>OJT COMPANY</strong>, its officials, employees, representatives, or agents.</li>
                </ol>
        
                <p>B.&emsp;&emsp; <strong>OJT COMPANY</strong> shall:</p>
                <ol>
                    <li>Deploy the interns to the different operating units of <strong>OJT COMPANY</strong> (as stated in the Recommendation Letter) for a period of <strong>${formData.get('training_hours')|| '(REQUIRED HOURS)'} Hours</strong>, unless extended or terminated upon a written agreement between the <strong>UNIVERSITY</strong> and <strong>OJT COMPANY</strong>, duly signed by their respective representatives;</li>
                    <li>Assign personnel who shall orient the interns on the rules and regulations of the <strong>OJT COMPANY</strong>, especially on such matters which pertain to safety and security precautions, and who shall monitor and supervise the interns;</li>
                    <li>Involve the interns in activities and tasks to develop their work attitude and creative abilities to become professional employees based on the areas stated in the Recommendation Letter;</li>
                    <li>Enforce such rules and regulations that will apply in the conduct of the <strong>Internship Program</strong> within its premises. These rules and regulations shall be made known to the <strong>UNIVERSITY</strong> and to the interns;</li>
                    <li>Accomplish the necessary forms (e.g. Acceptance Form, Accomplishment Report, Performance Evaluation Report) required by the <strong>UNIVERSITY</strong>, in connection with the training requirements of the interns; and,</li>
                    <li>Issue a Certificate of Completion to each intern upon fulfillment of all the training requirements.</li>
                </ol>
                </div>
        
                <!-- Page 2 -->
                <div class="page2">
                    <p><strong>C. OTHER TERMS AND CONDITIONS.</strong></p>
                    <ol>
                        <li>It is expressly understood that there will be no employer-employee relationship between <strong>OJT COMPANY</strong> and the intern of the <strong>UNIVERSITY</strong>.</li>
                        <li>The intern shall abide by <strong>OJT COMPANY's</strong> rules and regulations and those imposed under the <strong>Internship Program;</strong> otherwise, he/she shall be excluded from further participation.</li>
                        <li>It is expressly understood by the <strong>UNIVERSITY</strong> and the interns that all information on technology, process, process standards, quality assurance methodologies, quality standards, production capabilities, marketing, finance, and all other related documents, manuals, and operational or technical matters that <strong>OJT COMPANY</strong> shall make available to them shall be used for the sole purpose of internship training. All of these matters are classified as confidential in nature and proprietary to <strong>OJT COMPANY</strong> and the interns hereby undertake to prevent the transfer of such information by any of its members to any party outside of <strong>OJT COMPANY</strong> without the knowledge and written consent of <strong>OJT COMPANY</strong>;</li>
                        <li>Any intellectual property owned by the parties prior to this agreement shall continue to be owned by <strong>OJT COMPANY</strong>. The student interns cannot use any confidential information or data from <strong>OJT COMPANY</strong> to create intellectual property without the express written approval of <strong>OJT COMPANY</strong>;</li>
                        <li>The <strong>UNIVERSITY</strong> and interns agree that any invention, publication, or proprietary information the interns developed during his internship with <strong>OJT COMPANY</strong> shall be <strong>OJT COMPANY's</strong> property, and to evidence such ownership, the <strong>UNIVERSITY</strong> and intern agree to execute the corresponding assignments and patent applications as <strong>OJT COMPANY</strong> requests.</li>
                        <li>This Agreement shall take effect immediately upon the signing hereof and shall continue to be effective until the accomplishment of the purpose stated herein, provided, however, that any of the terms and conditions in this Agreement may be amended through a written agreement mutually consented and agreed upon by both parties; provided further that <strong>OJT COMPANY</strong> and the <strong>UNIVERSITY</strong> reserve their right to withdraw their participation in this Agreement upon written notice to the other and upon mutual terms and conditions agreed upon by both parties herein.</li>
                    </ol>
        
                    <p><strong>&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;IN WITNESS WHEREOF</strong>, the parties have here to set their hands and affixed their signatures on this___day of __________2024, at the University of Caloocan City, Philippines.</p>
        
                    <div style="display: flex; justify-content: space-between; margin-top: 40px;">
                        <div style="text-align: center;">
                            <p><strong>UNIVERSITY OF CALOOCAN CITY</strong></p>
                            <p>By:</p>
                            <p><strong>ATTY. RODERICK P. VERA</strong></p>
                            <p><em>Vice President for Academic Affairs</em></p>
                        </div>
                        <div style="margin-right:300px;">
                            <p><strong>${formData.get('ojt_company')|| '(COMPANY)'}</strong></p>
                            <p>By:</p>
                            <p><strong>${formData.get('company_representative')|| '(COMPANY REPRESENTATIVE)'}</strong></p>
                            <p><em>${formData.get('representative_position')|| '(REPRESENTATIVE POSITION)'}</em></p>
                        </div>
                    </div>
        
                    <p style="text-align:center;">WITNESSES:</p>
                    <div id="witness-signatures">
                            <p><strong>DR. BERNADETTE B. ENRIQUEZ</strong></p>
                            <p><em>CLAS Dean</em></p>
                        </div>
                        ${Array.from(formData.getAll('witness_names')).map((name, index) => `
                            <div style="text-align: center;">
                                <p><strong>${name}</strong></p>
                                <p><em>${formData.getAll('witness_positions')[index]}</em></p>
                            </div>
                        `).join('')}
        
                    <p style="margin-top: 10px; text-align:center;"><strong>ACKNOWLEDGMENT</strong></p>
                    <p><strong>REPUBLIC OF THE PHILIPPINES )</strong></p>
                    <p><strong>CALOOCAN CITY ) S.S.</strong></p>
                    <p><strong>BEFORE ME,</strong> a Notary Public for and in Caloocan City, Philippines, this _____ day of _______________, personally appeared the following:</p>
                    
                    <table style="width: 100%; margin-top: 20px;">
                        <tr>
                            <th style="text-align: left;">Name</th>
                            <th style="text-align: left;">I.D. No.</th>
                            <th style="text-align: left;">Date and Place of Issue</th>
                        </tr>
                        <tr>
                            <td><strong>ATTY. RODERICK P. VERA</strong></td>
                            <td>UCC-ID</td>
                            <td>Caloocan City</td>
                        </tr>
                        <tr>
                            <td><strong>${formData.get('company_representative')|| '(COMPANY REPRESENTATIVE)'}</strong></td>
                            <td>COMPANY ID</td>
                            <td>${formData.get('city')|| '(CITY)'}</td>
                        </tr>
                    </table>
        
                    <p style="margin-top: 20px;">Known to me and to me known to be the same persons who executed the foregoing instrument and who acknowledged to me that the same is their free and voluntary act and deed, and of the entities they respectively represent.</p>
        
                    <p>This instrument refers to a Memorandum of Agreement consisting of two (2) pages, including this page where this Acknowledgement is written, and has been signed by the parties and their witnesses on each and every page thereof.</p>
        
                    <p><strong>WITNESS MY HAND AND SEAL,</strong> on the date and at a place first above written.</p>
        
                    <div style="margin-top: 40px;">
                        <p>Doc. No.: ___;</p>
                        <p>Page No.: ___;</p>
                        <p>Book No.: ___;</p>
                        <p>Series of ${currentYear}</p>
                    </div>
                </div>
            `;
    
    preview.innerHTML = moaContent;
}

function saveForm() {
    const formData = new FormData(document.getElementById('moaForm'));
    
    fetch('moa_template.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('MOA saved successfully!');
            window.location.href = 'std_documents.php';
        } else {
            alert('Error saving MOA: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving MOA');
    });
}

function generatePDF() {
    // Make sure preview is up to date
    updatePreview();
    
    // Create print-specific styles
    const printStyles = `
@media print {
    /* Reset visibility */
    body * {
        visibility: hidden;
    }
    
    /* Show only MOA content */
    #moaPreview, #moaPreview * {
        visibility: visible;
    }
    
    /* Position and size for long bond paper */
    #moaPreview {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background-color: white;
        margin: 0;
    }
    
    /* Page setup for long bond paper */
    @page {
        size: 8.5in 13in; /* Long bond paper size */
        margin: 0.5in; /* Fixed margins of 0.5 inches */
    }
    
    /* Hide UI elements */
    .button-container, .form-container {
        display: none;
    }
    
    /* Page formatting */
    .page {
        page-break-after: always;
        font-size: 12pt;
        line-height: 1.8;
        font-family: "Times New Roman", Times, serif;
        page-break-inside: avoid; /* Prevent breaking inside this element */
    }
    
    /* Header adjustments */
    h3 {
        font-size: 14pt;
        margin: 20px 0;
        text-transform: uppercase;
    }
    
    /* Text formatting */
    p, .text-block {
        margin: 8px 0;
        text-align: justify;
    }
    
    .indent {
        text-indent: 2em;
    }
    
    /* Logos and header */
    .header-logos {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .header-logos img {
        height: 1in;
        margin: 0 0.5in;
    }
    
    /* List formatting */
    ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    li {
        margin-bottom: 8px;
        text-align: justify;
    }
    
    /* Table adjustments */
    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    
    th, td {
        padding: 6px;
        text-align: left;
        vertical-align: top;
    }
    
    /* Signature section */
    .signature-section {
        margin-top: 30px;
    }
    
    .signature-line {
        width: 200px;
        border-bottom: 1px solid black;
        margin: 40px 0 5px 0;
    }
    
    /* Specific text alignments */
    .center-text {
        text-align: center;
    }
    
    .right-text {
        text-align: right;
    }
    
    /* Ensure white background */
    * {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: white;
        color: black;
    }
    
    /* Ensure content fits within 2-3 pages */
    .page {
        page-break-after: always; /* Ensure each page starts on a new page */
    }
    
    /* Adjustments for content overflow */
    .page:last-child {
        page-break-after: auto; /* Prevents an extra page if not needed */
    }
}
    `;
    
    // Add print styles
    const styleElement = document.createElement('style');
    styleElement.textContent = printStyles;
    document.head.appendChild(styleElement);
    
    // Trigger print dialog
    window.print();
    
    // Remove print styles after printing
    document.head.removeChild(styleElement);
}

// Initialize on page load
window.addEventListener('load', function() {
    updatePreview();
    
    // Add event listeners to any dynamically added elements
    document.addEventListener('input', function(e) {
        if (e.target.closest('#moaForm')) {
            updatePreview();
        }
    });
});