<?php
    session_start();
    include 'config.php'; // Include database configuration

    // Handle logout
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: companymainpage.html");
        exit();
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Check if user is logged in and has a user_id
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];

            $sql = "INSERT INTO internshipad (
                internship_title, 
                department,
                industry,
                internship_type, 
                number_of_openings, 
                duration, 
                internship_description, 
                internship_summary, 
                requirements, 
                qualifications, 
                skills_required,
                Keywords, 
                application_deadline, 
                additional_info,
                user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                // Bind parameters, including the user_id
                $stmt->bind_param(
                    "ssssisssssssssi",
                    $_POST['internship-title'],
                    $_POST['department'],
                    $_POST['industry'],
                    $_POST['internship-type'],
                    $_POST['number-of-openings'],
                    $_POST['duration'],
                    $_POST['internship-description'],
                    $_POST['internship-summary'],
                    $_POST['requirements'],
                    $_POST['qualifications'],
                    $_POST['skills-required'],
                    $_POST['keywords'], // New keywords field
                    $_POST['application-deadline'],
                    $_POST['additional-info'],
                    $user_id
                );

                if ($stmt->execute()) {
                    // Get the ID of the last inserted internship ad
                    $internship_ad_id = $conn->insert_id;
                    
                    // Redirect to questionnaire page with internship ad ID as a parameter
                    header("Location: questionaire.php?internship_ad_id=" . $internship_ad_id);
                    exit();
                }

                $stmt->close();
            } else {
                echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
            }
        } else {
            echo "<script>alert('You need to be logged in to post an internship ad.');</script>";
        }
    }

    // Function to get full name (your existing function)
    function getFullName() {
        if (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            return htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
        }
        return 'Your Name'; // Fallback if names are not set
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Internship Ad Creation</title>
    <link rel="stylesheet" href="NAV-login.css">
    <link rel="stylesheet" href="FOOTER.css">
    <link rel="stylesheet" href="createinternship.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- NAVIGATION -->
    <div class="navbar">
        <div class="logo-container">
            <button class="menu-toggle" aria-label="Toggle Navigation">☰</button>
            <img src="pics/ucc-logo.png" alt="Logo" class="logo-img">
        </div>
        <div class="nav-links">
            <a href="companyloginpage.php">HOME</a>
            <a href="#about">ABOUT US</a>
            <a href="#contact">CONTACT US</a>
            <?php if(!isset($_SESSION['email'])): ?>
                <a href="../STUDENTCOORLOG/login.php" class="login-btn">LOGIN</a>
                <a href="../MAIN/MAIN.php" class="employer-btn">APPLICANT SITE</a>
            <?php endif; ?>
        </div>
        <?php if(isset($_SESSION['email'])): ?>
        <div class="auth-buttons">
            <div class="dropdown-container">
                <div class="border">
                    <span class="greeting-text"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                    <div class="dropdown-btn" onclick="toggleDropdown(event)">
                        <img src="pics/profile.png" alt="Profile" onerror="this.onerror=null;this.src='pics/default_profile.jpg';">
                    </div>
                </div>
                <div id="dropdown-content" class="dropdown-content">
                    <div class="user-fullname"><?php echo getFullName(); ?></div>
                    <hr style="margin: 0 auto">
                    <a href="company-profile.php">Profile</a>
                    <a href="company-overview.php">Interns</a>
                    <a href="chat-inbox.php">Emails</a>
                    <a href="company-account.php">Settings</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Elements
            const navbar = document.querySelector('.navbar');
            const menuToggle = document.querySelector('.menu-toggle');
            const dropdownContent = document.getElementById("dropdown-content");
            let timeout;

            // Navbar visibility functions
            const hideNavbar = () => {
                if (window.scrollY > 0) {
                    navbar.style.opacity = '0';
                    navbar.style.pointerEvents = 'none';
                }
            };

            const showNavbar = () => {
                navbar.style.opacity = '1';
                navbar.style.pointerEvents = 'auto';
            };

            const resetNavbarTimeout = () => {
                showNavbar();
                clearTimeout(timeout);
                if (window.scrollY > 0) {
                    timeout = setTimeout(hideNavbar, 1000);
                }
            };

            // Scroll event listeners
            window.addEventListener('scroll', () => {
                if (window.scrollY === 0) {
                    showNavbar();
                    clearTimeout(timeout);
                } else {
                    resetNavbarTimeout();
                }
            });

            // User interaction listeners
            window.addEventListener('mousemove', resetNavbarTimeout);
            window.addEventListener('click', resetNavbarTimeout);
            window.addEventListener('keydown', resetNavbarTimeout);

            // Initial check
            if (window.scrollY > 0) {
                timeout = setTimeout(hideNavbar, 1000);
            }

            // Mobile menu toggle functionality
            menuToggle.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent event from bubbling
                navbar.classList.toggle('active');
                
                if (navbar.classList.contains('active')) {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#e77d33';
                } else {
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });

            // Dropdown toggle function
            window.toggleDropdown = function(event) {
                if (event) {
                    event.stopPropagation();
                }
                
                const isDisplayed = dropdownContent.style.display === "block";
                
                // Close dropdown if it's open
                if (isDisplayed) {
                    dropdownContent.style.display = "none";
                } else {
                    // Close any other open dropdowns first
                    const allDropdowns = document.querySelectorAll('.dropdown-content');
                    allDropdowns.forEach(dropdown => {
                        dropdown.style.display = "none";
                    });
                    
                    // Open this dropdown
                    dropdownContent.style.display = "block";
                }
            };

            // Close menu and dropdown when clicking outside
            document.addEventListener('click', function(event) {
                // Handle mobile menu
                const isClickInsideNavbar = navbar.contains(event.target);
                if (!isClickInsideNavbar && navbar.classList.contains('active')) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }

                // Handle dropdown
                const isClickInsideDropdown = event.target.closest('.dropdown-container');
                if (!isClickInsideDropdown && dropdownContent) {
                    dropdownContent.style.display = "none";
                }
            });

            // Window resize handler
            window.addEventListener('resize', function() {
                if (window.innerWidth > 1300) {
                    navbar.classList.remove('active');
                    menuToggle.innerHTML = '☰';
                    menuToggle.style.color = '#fd6f41';
                }
            });
        });
    </script>
    <!-- NAVIGATION -->



    <!-- Alert -->
    <div class="alert" >
                        <div class="alert__wrapper">
                            <span class="alert__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2d6a2d"><path d="m702-593 141-142q12-12 28.5-12t28.5 12q12 12 12 28.5T900-678L730-508q-12 12-28 12t-28-12l-85-85q-12-12-12-28.5t12-28.5q12-12 28-12t28 12l57 57ZM360-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM40-240v-32q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v32q0 33-23.5 56.5T600-160H120q-33 0-56.5-23.5T40-240Z"/></svg>
                            </span>
                            <p class="alert__message">
                            Account successfully verified. You are now eligible to create internship advertisements.
                            </p>
                        </div>
    </div>

    <div class="wrapper"> 
            <form id="internship-creation-form" method="POST" enctype="multipart/form-data">
                <div class="bgwidth">
                    <h1 class="header-title">Create <span class="highlight"> Internship Ad </span></h1>
                    <p class="subheader-title">Complete the following to create your Internship Ad</p>
                </div>
                <div class="progress-bar">
                    <div class="step active">
                        <div class="step-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                                <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                            </svg>
                        </div>
                        <span class="step-label">Classify</span>
                    </div>
                    <div class="progress-line active"></div>
                    <div class="step">
                        <div class="step-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                                <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                            </svg>
                        </div>
                        <span class="step-label">Write</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="step">
                        <div class="step-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px">
                                <path d="M480-280q83 0 141.5-58.5T680-480q0-83-58.5-141.5T480-680q-83 0-141.5 58.5T280-480q0 83 58.5 141.5T480-280Zm0 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                            </svg>
                        </div>
                        <span class="step-label">Manage</span>
                    </div>
                </div>
                <div class="container">
                <!-- Step 1: Classify -->
                <div id="step-1" class="form-step">

                    <h2 class="step-h2" >Classify Internship Role</h2>

                    <div class="form-group">
                        <label class="form-label" for="internship-title">Title</label>
                        <input type="text" id="internship-title" name="internship-title" placeholder="Enter Internship Title" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="width: 49%; margin: 0;">
                            <label class="form-label" for="department">Department</label>
                            <input type="text" id="department" name="department" placeholder="Enter interns department" onkeypress="allowOnlyLetters(event)" oninput="capitalizeFirstLetter(this)">
                        </div>
                        <div class="form-group" style="width: 49%; margin: 0;">
                            <label class="form-label" for="industry">Carreer Fields</label>
                            <select id="industry" name="industry" required>
                                <option value="" disabled selected>Select an industry</option>
                                <option value="Media">Journalism and Broadcasting</option>
                                <option value="Advertisement">Advertising and Public Relations</option>
                                <option value="Health and Human Services">Social and Community Services</option>
                                <option value="Civil Society">Nonprofit and Advocacy</option>
                                <option value="Entertainment">Arts, Media, and Entertainment</option>
                                <option value="Marketing">Marketing and Brand Management</option>
                                <option value="Law Enforcement">Criminal Justice and Public Safety</option>
                                <option value="Institutions">Education and Research</option>
                                <option value="Finance">Banking and Financial Services</option>
                                <option value="Technology">IT and Software Development</option>
                                <option value="Healthcare">Medical and Healthcare Services</option>
                            </select>
                        </div>
                    </div>

                    <script>
                                              const industryKeywords = {
                            'Media': `Political, Media, Communication, Research, Writing, Journalism, Public Speaking, Content Creation, Social Media, Editorial Writing, Digital Media, Storytelling, Media Relations, Intercultural Communication, Reporting, News Writing, Qualitative Research, Press Release, Multimedia Production, Broadcast Journalism, Political Communication, Academic Writing, Comparative Politics, International Relations, Media Ethics, Documentary Production, Rhetoric, Conflict Resolution, Strategic Communication, Visual Communication, Narrative Journalism, Political Campaign, Media Analysis, Public Diplomacy, Discourse Analysis, Investigative Reporting, Corporate Communications, Advocacy, Community Engagement, Cultural Studies, Narrative Analysis, Public Opinion, Critical Theory, Global Studies, Media Literacy, Persuasive Communication, Bachelor of Arts in Political Science, BA Political Science, BA in Political Science, BA Pol Sci, Bachelor Arts Political Science`,

                            'Advertisement': `Strategic Communication, Media Planning, Campaign Development, Creative Strategy, Copywriting, Content Creation, Brand Management, Market Research, Consumer Behavior, Audience Analysis, Digital Marketing, Social Media Management, Public Relations, Media Relations, Press Release Writing, Corporate Communication, Advertising Design, Visual Communication, Graphic Design, Typography, Layout Design, Art Direction, Advertising Photography, Video Production, Multimedia Content, Advertisement Copywriting, Tagline Development, Branding Concepts, Creative Brief Development, Client Presentations, Pitch Development, Competitive Analysis, Message Strategy, Communication Theory, Persuasive Communication, Integrated Marketing Communications, Advertising Ethics, Media Buying, Media Strategy, ROI Analysis, Advertising Metrics, Campaign Performance, Google Analytics, SEO/SEM, Digital Advertising, Print Advertising, Broadcast Media, Radio Production, Television Advertising, Billboard Design, Guerrilla Marketing, Experiential Marketing, Event Promotion, Sales Promotion, Direct Marketing, Email Marketing, Content Strategy, Storytelling, Narrative Development, Advertising Psychology, Consumer Insights, Focus Group Moderation, Survey Design, Qualitative Research, Quantitative Analysis, Communication Planning, Crisis Communication, Reputation Management, Internal Communications, External Communications, Stakeholder Engagement, Cross-Platform Campaigns, Mobile Advertising, Social Media Advertising, Influencer Marketing, Content Calendar Management, Editorial Planning, Adobe Creative Suite, Communication Technology, Digital Portfolio, Presentation Skills, Interpersonal Communication, Team Collaboration, Client Relationship Management, Agency Operations, Freelance Management, Budget Planning, Campaign Timeline Development, Creative Thinking, Strategic Problem Solving, Cultural Sensitivity, Global Communication, Multilingual Communication, Communication Ethics, Media Literacy, Communication Research, Advertising Law, Regulatory Compliance, Bachelor of Arts in Communication, BA Communication, BA in Communication, Bachelor Arts Communication`,

                            'Health and Human Services': `Psychological Assessment, Cognitive Development, Mental Health Services, Clinical Psychology, Behavioral Analysis, Psychological Testing, Counseling Techniques, Therapeutic Interventions, Adolescent Psychology, Abnormal Psychology, Psychopathology, Neuropsychology, Social Psychology, Developmental Psychology, Personality Assessment, Psychological Research, Data Collection, Research Methodology, Experimental Design, Human Behavior, Cognitive Psychology, Biopsychology, Psychological Theory, Applied Psychology, Health Psychology, Psychotherapy, Crisis Intervention, Group Therapy, Individual Therapy, Family Therapy, Psychological Screening, Case, Client Assessment, Treatment Planning, Psychological Documentation, Rehabilitation Psychology, Ethical Practice, APA Guidelines, Confidentiality Protocols, Office Administration, Administrative Support, Document, Records, Filing Systems, Appointment Scheduling, Calendar, Office Coordination, Executive Assistance, Administrative Procedures, Office Communications, Front Office, Customer Service, Client Relations, Patient Intake, Medical Records, Office Technology, Microsoft Office Suite, Electronic Health Records, Administrative Reporting, Office Workflow, Process Improvement, Supply, Inventory Control, Administrative Leadership, Staff Coordination, Office Budget, Human Resources Support, Training Coordination, Meeting Planning, Event Coordination, Correspondence, Receptionist Duties, Client Services, Organizational Skills, Time, Multitasking, Problem-Solving, Communication Skills, Interpersonal Relations, Professional Ethics, HIPAA Compliance, Confidentiality Standards, Healthcare Administration, Mental Health Administration, Practice, Billing Procedures, Insurance Processing, Service Coordination, Referral, Patient Advocacy, Community Resources, Crisis, Documentation Standards, Professional Correspondence, Quality Assurance, Program Support, Administrative Assistance, Team Collaboration, Departmental Coordination, Bachelor of Science in Psychology, BS Psychology, BS in Psychology, Bachelor of Science in Office Administration, BSOA, BS Office Administration, Bachelor Science Office Administration, Bachelor Science Psychology`,

                            'Civil Society': `Policy Analysis, Public Governance, Community Development, Nonprofit Management, Civic Engagement, Grant Writing, Public Policy, Legislative Affairs, Social Justice, Program Evaluation, Government Relations, Stakeholder Engagement, International Development, Public Sector Management, Administrative Law, Budget Management, Public Finance, Social Policy, Advocacy, Regulatory Compliance, Government Operations, Community Outreach, Public Service Delivery, Civil Society Organizations, NGO Management, Public Administration, Human Rights, Sustainable Development, Democracy Building, Electoral Systems, Public-Private Partnerships, Fundraising, Capacity Building, Public Consultation, Strategic Planning, Research Methodology, Data Analysis, Community Organizing, Local Government, Public Health Policy, Resource Mobilization, Good Governance, Political Systems, Conflict Resolution, Public Speaking, Ethics in Government, Urban Planning, Rural Development, Environmental Policy, Citizen Participation, Social Welfare Programs, Donor Relations, Public Leadership, Institution Building, Humanitarian Assistance, Socioeconomic Development, Policy Implementation, Needs Assessment, Foreign Aid, Grassroots Organizations, Volunteerism, Fiscal Policy, Policy Reform, Government Accountability, Constitutional Law, Organizational Development, Crisis Management, International Relations, Diplomacy, Public Education Campaigns, Monitoring and Evaluation, Impact Assessment, Proposal Writing, Community Empowerment, Decentralization, Social Innovation, Change Management, Inclusive Governance, Transparency Initiatives, Anti-corruption, Population Studies, Demographic Analysis, Peace Building, Humanitarian Law, Intergovernmental Relations, Civil Rights, Political Economy, Social Entrepreneurship, Public Interest Law, Community-Based Initiatives, Participatory Governance, Institutional Reform, Public Sector Innovation, Global Governance, Bachelor of Public Administration, BPA, BA Public Administration, Bachelor Public Administration`,

                            'Entertainment': `Media Production, Content Creation, Digital Media, Creative Writing, Video Production, Audio Production, Film Making, Animation, Visual Effects, Game Development, Entertainment Marketing, Production Management, Talent Management, Creative Direction, Art Direction, Sound Design, Script Writing, Digital Animation, Media Distribution, Event Management, Hospitality Operations, Tourism Planning, Guest Relations, Entertainment Production, Venue Management, Customer Service, Hotel Administration, Travel Coordination, Food & Beverage Management, Destination Marketing, Resort Operations, Entertainment Programming, Tourism Development, Hospitality Analytics, Revenue Management, Accommodation Services, Tour Operations, Entertainment Booking, Experience Design, Hospitality Law, Recreation Management, Front Office Operations, Reservation Systems, Sustainable Tourism, Corporate Events, Entertainment Technology, Catering Services, Tourist Attraction Management, Hospitality Marketing, Entertainment Logistics, Tourism Policy, Guest Experience, Entertainment Promotion, Hospitality Finance, Tourism Research, Property Management, Festival Planning, Tourism Economics, Quality Assurance, Banquet Operations, Entertainment Contracts, Hospitality Strategy, Tourism Trends, Concierge Services, Cultural Tourism, Entertainment Budgeting, Housekeeping Management, Leisure Services, Entertainment Safety, Tourism Impact Assessment, Conference Planning, Luxury Hospitality, Entertainment Licensing, Tourism Development, Facilities Management, Entertainment Marketing, Hospitality Branding, Tourism Entrepreneurship, Cruise Operations, Entertainment Industry Relations, Tourism Forecasting, Hotel Development, Entertainment Merchandising, Tour Guide Management, Resort Planning, Restaurant Management, Spa Operations, Theme Park Management, Casino Operations, VIP Services, Travel Technology, Adventure Tourism, Ecotourism, Heritage Tourism, Sports Tourism, Tourism Geography, Hospitality HR, Special Events, Travel Agency Operations, Bachelor of Science in Hospitality Management, BSHM, BS Hospitality Management, Bachelor of Science in Tourism Management, BSTM, BS Tourism Management, Bachelor Science Hospitality Management, Bachelor Science Tourism Management`,

                            'Marketing': `Brand Management, Digital Marketing, Market Research, Marketing Strategy, Social Media Marketing, Content Marketing, Campaign Management, SEO, Analytics, Email Marketing, Marketing Communications, Product Marketing, Market Analysis, Brand Development, Consumer Behavior, Marketing Analytics, Digital Advertising, Marketing Automation, CRM, Marketing Strategy, Digital Marketing, Social Media Marketing, Brand Management, Market Research, Consumer Behavior, Advertising, Content Marketing, SEO, SMA, Campaign Management, Marketing Analytics, Market Segmentation, Product Development, Competitive Analysis, CRM, Customer Acquisition, Lead Generation, Conversion Optimization, A/B Testing, Email Marketing, Copywriting, Creative Direction, Public Relations, Brand Positioning, Customer Journey Mapping, Marketing Automation, Marketing Communications, Google Analytics, Marketing ROI, Influencer Marketing, Viral Marketing, Direct Marketing, Integrated Marketing, Guerrilla Marketing, Relationship Marketing, Mobile Marketing, Omnichannel Marketing, Marketing Ethics, Sales Funnel, Customer Retention, Buyer Personas, SWOT Analysis, Marketing Plan, Focus Groups, Survey Design, Data Visualization, Pricing Strategy, Distribution Channels, Target Audience, Customer Engagement, Branding, Value Proposition, Marketing Mix, Customer Lifetime Value, Marketing Psychology, Neuromarketing, Product Placement, Promotional Strategy, International Marketing, Cross-Cultural Marketing, B2B Marketing, B2C Marketing, Inbound Marketing, Outbound Marketing, Retargeting, Affiliate Marketing, Marketing Metrics, KPIs, Media Planning, Marketing Budget, Market Trends, Customer Satisfaction, Bachelor of Science in Business Administration Major in Human Resource Management, BSBA-HRM, BS Business Administration Major in Human Resource Management, Bachelor Science Business Administration, Major Human Resource Management`,

                            'Law Enforcement': `Criminal Justice, Public Safety, Law Enforcement, Investigation, Security Management, Emergency Response, Crime Prevention, Risk Assessment, Compliance, Legal Procedures, Criminal Law, Evidence Management, Crisis Management, Security Operations, Patrol Operations, Surveillance, Emergency Management, Crime Analysis, Public Relations, anomie, strain theory, social control, differential association, labeling theory, rational choice theory, deterrence, social disorganization, self-control theory, routine activity theory, broken windows theory, general strain theory, cultural criminology, critical criminology, feminist criminology, positivist criminology, classical criminology, recidivism, desistance, white-collar crime, corporate crime, organized crime, cybercrime, hate crime, juvenile delinquency, domestic violence, homicide, sexual assault, human trafficking, drug trafficking, transnational crime, terrorism, biosocial criminology, environmental criminology, penal system, incarceration, rehabilitation, retribution, restorative justice, diversion programs, probation, parole, sentencing, plea bargaining, criminal procedure, bail, criminal profiling, penology, carceral state, mass incarceration, victimology, crime mapping, crime statistics, longitudinal studies, self-report surveys, ethnography, content analysis, crime rates, dark figure of crime, victim surveys, criminogenic, Bachelor of Science in Criminology, BS Crim, BS Criminology, Bachelor Science Criminology`,

                            'Institutions': `Pedagogical Knowledge, Curriculum Development, Lesson Planning, Teaching Strategies, Classroom Management, Educational Technologies, Learning Management Systems, LMS, Interactive Whiteboards, Google Classroom, Digital Learning Tools, Student Engagement, Student-Centered Learning, Active Learning, Differentiated Instruction, Project-Based Learning, Assessment & Evaluation, Formative Assessment, Summative Assessment, Feedback Techniques, Student Progress Monitoring, Classroom Management Techniques, Student Support, Grading and Feedback, Group Work Facilitation, Bachelor in Secondary Education Major in Science, BSEd Science, BS Secondary Education Major Science, Bachelor in Secondary Education Major in English, BSEd English, BS Secondary Education Major English, Bachelor in Secondary Education Major in English - Chinese, BSEd English Chinese, BS Secondary Education Major English Chinese, Bachelor of Early Childhood Education, BECEd, BS Early Childhood Education, Bachelor in Secondary Education Major in Technology and Livelihood Education, BTLEd Home Economics, BS Technology and Livelihood Education Major Home Economics, Bachelor in Secondary Education, BSEd, BS Secondary Education, Bachelor Early Childhood Education, Bachelor Secondary Education Major English, Bachelor Secondary Education Major English - Chinese, Bachelor Secondary Education Major Science, Bachelor Secondary Education Major Technology Livelihood Education`,

                            'Finance': `Financial Analysis, Financial Statements, Financial Modeling, Statistical Analysis, Budgeting and Forecasting, Variance Analysis, General Ledger, Accounts Payable, Accounts Receivable, Reconciliation, Portfolio Management, Equity Valuation, Risk Assessment, Capital Budgeting, Excel, QuickBooks, SAP, Bloomberg Terminal, Business Strategy, Market Research, SWOT Analysis, Financial Reporting, Investment Management, Financial Technology, Bachelor of Science in Mathematics, BS Math, Bachelor of Science in Accountancy, BSA, Bachelor of Science in Accounting Information System, BS AIS, Bachelor of Science in Entrepreneurship, BS Entrep, Bachelor of Science in Business Administration Major in Financial Management, BSBA-FM, Bachelor of Science in Business Administration Major in Marketing Management, BSBA-MM, Bachelor Science Accountancy, Bachelor Science Accounting Information System, Bachelor Science Business Administration, Major Financial Management, Bachelor Science Business Administration, Major Marketing Management, Bachelor Science Entrepreneurship, Bachelor Science Mathematics`,

                            'Technology': `IT Services, Programming, AI, Artificial Intelligence, ML, Machine Learning, Data Science, Cybersecurity, Networking, Infrastructure, Virtualization, SaaS Software as a Service, IoT, Internet of Things, Programming, Coding, Python, Java, C++, JavaScript, PHP, Swift, Kotlin, SQL, Ruby, APIs, Full Stack, Backend, Frontend, Agile, DevOps, Version Control Git, Mobile Development, Web Development, Cloud, AWS Amazon Web Services, Azure, Google Cloud, Virtual Machines, Kubernetes, Docker, Containers, DevOps, CI, CD, Continuous Integration, Continuous Deployment, Infrastructure, Cloud Architecture, Serverless Computing, Neural Networks, Deep Learning, Natural Language Processing, NLP, Reinforcement Learning, Computer Vision, Predictive Analytics, Big Data, Data Mining, TensorFlow, PyTorch, Data Modeling, Data Processing, Robotics, Algorithm Design, Network Security, Information Security, Vulnerability Assessment, Penetration Testing, Firewall, Encryption, Ethical Hacking, Malware, Incident Response, Compliance, Risk Management, Threat Intelligence, Security Analyst, Data Analysis, Data Engineering, NoSQL, Hadoop, Apache Spark, Data Visualization, ETL, Extract Transform Load, Tableau, Power BI, R Programming Language, Excel, Predictive Modeling, Blockchain, Cryptocurrency, Web3, Augmented Reality, AR, Virtual Reality VR, Mixed Reality, Quantum Computing, 5G, Edge Computing, Software Engineer, Data Scientist, DevOps Engineer, UI Designer, UX Designer, Frontend Developer, Backend Developer, Systems Administrator, IT Manager, Project Manager, Product Manager, QA Tester, Cybersecurity Analyst, SaaS Sales, Business Development, B2B, B2C, CRM, Customer Relationship Management, Lead Generation, Salesforce, Marketing Automation, Digital Marketing, SEO, Search Engine Optimization, SEM, Search Engine Marketing, PPC, Pay-Per-Click, Embedded Systems, Microcontrollers, FPGA, VHDL, Verilog, PCB Design, Signal Processing, Control Systems, Mechatronics, Sensors, Actuators, Embedded Software, Real-Time Systems, Circuit Design, Electronic Design Automation, EDA, Analog Electronics, Digital Electronics, Microprocessors, ARM, RISC-V, System on Chip, SoC, Telecommunications, Network Engineering, Wireless Communications, Protocols, MQTT, UART, I2C, SPI, CAD, Computer-Aided Design, Simulation, Systems Integration, Industrial Automation, PLC, Programmable Logic Controllers, HMI Human-Machine Interface, Embedded Linux, Real-Time Operating Systems, RTOS, Firmware Development, Power Electronics, Signal Integrity, EMI, Electromagnetic Interference, EMC, Electromagnetic Compatibility, Research and Development, Technical Documentation, Systems Analysis, Enterprise Architecture, Business Intelligence, ERP, Enterprise Resource Planning, Supply Chain Management, IT Infrastructure, Network Administration, Cloud Migration, Hybrid Cloud, Multi-Cloud Strategy, Information Architecture, Enterprise Applications, Systems Integration, Technical Consulting, Digital Transformation, Innovation Management, Emerging Technologies, Courses Introduction to Computer Science, Programming Fundamentals, Data Structures and Algorithms, Object-Oriented Programming, Computer Architecture, Operating Systems, Database Management Systems, Computer Networks, Software Engineering, Web Development, Mobile App Development, Cloud Computing, Cybersecurity Fundamentals, Network Security, Ethical Hacking, Data Science Foundations, Artificial Intelligence, Big Data Analytics, Business Intelligence, Information Systems Design, IT Service Management, Project Management, Systems Analysis and Design, Enterprise Architecture, Information Security Management, IT Governance, Embedded Systems Design, Microcontroller Programming, VLSI Design, Telecommunications, IoT System Design, Sensor Technologies, Computer Vision, Natural Language Processing, Deep Learning, Blockchain Technologies, Cryptography, Computer Graphics, Human-Computer Interaction, User Experience Design, Software Testing and Quality Assurance, Agile Methodologies, Version Control Systems, Cloud Infrastructure, Serverless Computing, Microservices Architecture, Enterprise Application Integration, Business Process Management, Digital Marketing Technologies, E-commerce Systems, Customer Relationship Management, Supply Chain Management, Data Visualization, Research Methods in Computing, Technical Writing, Professional Communication in IT, Capstone Project in Computer Science, Information Systems Consulting, IT Strategy and Management,  CompTIA A+, CompTIA Network+, CompTIA Security+, Cisco, CCNA, Microsoft Certified Azure Fundamentals, AWS Certified Cloud Practitioner, Google Cloud Associate Cloud Engineer, Certified Information Systems Security Professional, CISSP, Certified Ethical Hacker, CEH, Certified Information Security Manager, CISM, Project Management Professional, PMP, Certified Scrum Master, Oracle Certified Professional, Red Hat Certified Engineer, Certified Information Systems Analyst, CISA, Certified Information Technology Professional, CITP, Bachelor of Science in Computer Science, BSCS, BS Computer Science, Bachelor of Science in Information Technology, BSIT, BS Information Technology, Bachelor of Science in Information System, BSIS, BS Information System, Bachelor of Science in Entertainment and Multimedia Computing, BS EMC, BS Entertainment and Multimedia Computing, Bachelor Science Computer Science, Bachelor Science Information System, Bachelor Science Entertainment Multimedia, Bachelor Science Information Technology`,

                            'Healthcare': `Anatomy, Physiology, Biochemistry, Pharmacology, Pathophysiology, Microbiology, Immunology, Medical Terminology, Clinical Practices, Diagnostics, Patient Care, Patient Assessment, Blood Pressure Measurement, EKG Interpretation, Wound Care, Phlebotomy, IV Insertion, Vital Signs Monitoring, First Aid, CPR, Electronic Health Records, EHR, Electronic Medical Records, EMR, Medical Coding, ICD-10, Radiology Information System, RIS, Picture Archiving and Communication System, PACS, Patient Education, Communication Skills, Compassionate Care, Patient Advocacy, Empathy, Nursing, Medicine, Dentistry, Physical Therapy, Occupational Therapy, Speech-Language Pathology, Radiology, Laboratory Technician, Public Health, CPR Certified, Basic Life Support, BLS, Advanced Cardiovascular Life Support, ACLS, First Aid Certified, Nursing Assistant Certified, CNA, Medical Assistant Certified, CMA, Phlebotomy Certification, Radiology Certification, Research, Healthcare Administration, Medical Ethics, Clinical Research, Medical Billing and Coding, Health Promotion, Mental Health, Bachelor of Science in Entrepreneurship, BS Entrep, BS Entrepreneurship, Bachelor Science Entrepreneurship`
                        };


                        // Function to handle industry selection and keyword updates
                        document.addEventListener('DOMContentLoaded', function() {
                            const industrySelect = document.getElementById('industry');
                            
                            if (industrySelect) {
                                industrySelect.addEventListener('change', function() {
                                    const selectedIndustry = this.value;
                                    updateKeywords(selectedIndustry);
                                });
                            }
                        });

                        // Function to update keywords
                        function updateKeywords(selectedIndustry) {
                            // Create or get the hidden input field for keywords
                            let keywordsInput = document.getElementById('keywords-input');
                            if (!keywordsInput) {
                                keywordsInput = document.createElement('input');
                                keywordsInput.type = 'hidden';
                                keywordsInput.name = 'keywords';
                                keywordsInput.id = 'keywords-input';
                                document.getElementById('industry').parentNode.appendChild(keywordsInput);
                            }
                            
                            // Set the keywords based on selected industry
                            if (industryKeywords[selectedIndustry]) {
                                keywordsInput.value = industryKeywords[selectedIndustry];
                                
                                // Optional: Display keywords in a preview div if you want to show them to the user
                                let keywordsPreview = document.getElementById('keywords-preview');
                                if (keywordsPreview) {
                                    keywordsPreview.textContent = `Selected Keywords: ${industryKeywords[selectedIndustry]}`;
                                }
                            } else {
                                keywordsInput.value = '';
                                if (keywordsPreview) {
                                    keywordsPreview.textContent = '';
                                }
                            }
                        }


                    </script>

                    <div class="form-group">
                        <label class="form-label">Internship Type</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="full-time" name="internship-type" value="full-time">
                                <label for="full-time">Full-time</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="part-time" name="internship-type" value="part-time">
                                <label for="part-time">Part-time</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="contract" name="internship-type" value="contract">
                                <label for="contract">Contract</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="casual" name="internship-type" value="casual">
                                <label for="casual">Casual</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="width: 49%; margin: 0;">
                            <label class="form-label" for="number-of-openings">Number of Openings</label>
                            <input type="number" id="number-of-openings" name="number-of-openings" placeholder="Enter open slots of internships" required>
                        </div>
                        <div class="form-group" style="width: 49%; margin: 0;">
                            <label class="form-label" for="duration">Duration</label>
                            <input type="text" id="duration" name="duration" placeholder="Enter amount of hours" required>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="next-button" onclick="nextStep(1)">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                    </div>
                </div>

                <!-- Step 2: Write -->
                <div id="step-2" class="form-step" style="display: none;">
                    <h2 class="step-h2" >Write Internship Details</h2>
                    
                    <div class="form-group">
                        <label class="form-label" for="internship-description">Internship Description</label>
                        <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>
                            
                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>
                           
                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
                           
                        </div>
                            <div class="editor" id="internship-description-editor" contenteditable="true" data-placeholder="Brief Description about the internship"></div>
                            <input type="hidden" id="internship-description" name="internship-description">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internship-summary">Internship Summary</label>
                        <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>
                          
                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>
                         
                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
                       
                        </div>
                            <div class="editor" id="internship-summary-editor" contenteditable="true" data-placeholder="Overall Summary of the internship"></div>
                            <input type="hidden" id="internship-summary" name="internship-summary">
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="prev-button" onclick="prevStep(2)"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg>Previous</button>
                        <button type="button" class="next-button" onclick="nextStep(2)">Next <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M373.91-480 222.74-632.17q-12.67-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.83-12.67 19.15 0 31.82 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.1 8.19 3.1 17.15 0 8.96-3.1 17.15-3.1 8.2-9.82 14.92L286.39-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.67-12.68-12.67-31.83t12.67-31.83L373.91-480Zm267.11 0L489.61-632.17q-12.68-12.68-13.06-31.45-.38-18.77 13.06-32.21 12.67-12.67 31.82-12.67 19.16 0 31.83 12.67l183.76 183.76q6.72 6.72 9.82 14.92 3.09 8.19 3.09 17.15 0 8.96-3.09 17.15-3.1 8.2-9.82 14.92L553.26-264.17q-12.67 12.67-31.44 13.05-18.78.38-32.21-13.05-12.68-12.68-12.68-31.83t12.68-31.83L641.02-480Z"/></svg></button>
                    </div>
                </div>

                <!-- Step 3: Manage -->
                <div id="step-3" class="form-step" style="display: none;">
                    <h2 class="step-h2" >Manage Candidate Applications</h2>
                    <div class="form-group">
                    <label class="form-label" for="requirements">Requirements</label>
                    <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>
                
                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>

                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
            
                        </div>
                            <div class="editor" id="requirements-editor" contenteditable="true" data-placeholder="Enter requirements"></div>
                            <input type="hidden" id="requirements" name="requirements">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="qualifications">Qualifications</label>
                        <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>

                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>
            
                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
    
                        </div>
                            <div class="editor" id="qualifications-editor" contenteditable="true" data-placeholder="Enter qualifications for internship"></div>
                            <input type="hidden" id="qualifications" name="qualifications">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="skills-required">Skills Required</label>
                        <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>

                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>
        
                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>

                        </div>
                            <div class="editor" id="skills-required-editor" contenteditable="true" data-placeholder="Minimum skill set for the interns"></div>
                            <input type="hidden" id="skills-required" name="skills-required">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="application-deadline">Application Deadline</label>
                        <input type="date" id="application-deadline" name="application-deadline">
                        <script>
                          const dateInput = document.getElementById('application-deadline');
                          
                          // Set initial min value
                          const today = new Date().toISOString().split('T')[0];
                          dateInput.min = today;
                          
                          // Add validation on change
                          dateInput.addEventListener('change', function() {
                            const selectedDate = new Date(this.value);
                            const currentDate = new Date();
                            
                            // Reset to today if selected date is in the past
                            if (selectedDate < currentDate) {
                              alert('Please select a future date');
                              this.value = today;
                            }
                          });
                        </script>
                        <div class="date-banner">
                            <p class="date-banner-text">
                                <span class="date-banner-content">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666"><path d="M479.89-240Q500-240 514-253.89q14-13.88 14-34Q528-308 514.11-322q-13.88-14-34-14Q460-336 446-322.11q-14 13.88-14 34Q432-268 445.89-254q13.88 14 34 14Zm.39 144Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm2.93-554q24.33 0 43.56 15.18Q546-619.64 546-596.87 546-576 533.31-559q-12.7 17-29.31 31-23 20-42 44t-19 54q0 15 10.68 25t24.92 10q16.07 0 27.23-10.5Q517-416 520-432q4-21 18-37.14 14-16.13 30-30.8 23-21.06 39-47.75T623-605q0-51-41.5-83.5T484.91-721q-38.06 0-71.98 17-33.93 17-56.09 49.27-7.84 10.81-4.34 23.77Q356-618 367-609q14 11 30 6.5t27-18.5q11-14 26.35-21.5 15.35-7.5 32.86-7.5Z"/></svg>
                                    <span class="text">Upon reaching the application deadline, the internship ad will be deleted.</span>
                                </span>
                            </p>
                        </div>
                    </div>


                    <div class="form-group">
                        <label class="form-label" for="additional-info">Additional Information</label>
                        <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" onclick="execCommand('undo')" title="Undo"><i>↩</i></button>
                            <button type="button" onclick="execCommand('redo')" title="Redo"><i>↪</i></button>
         
                            <button type="button" onclick="execCommand('bold')" title="Bold"><i>B</i></button>
                            <button type="button" onclick="execCommand('italic')" title="Italic"><i>I</i></button>
                            <button type="button" onclick="execCommand('underline')" title="Underline"><i>U</i></button>
 
                            <button type="button" onclick="execCommand('insertUnorderedList')" title="Bullet List"><i>•</i></button>
                            <button type="button" onclick="execCommand('insertOrderedList')" title="Numbered List"><i>#</i></button>
     
                        </div>
                            <div class="editor" id="additional-info-editor" contenteditable="true" data-placeholder="Provide any important student intern resources, such as FAQs or special requirements"></div>
                            <input type="hidden" id="additional-info" name="additional-info">
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="prev-button" onclick="prevStep(3)"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m318.98-480 151.41 152.17q12.68 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.82 12.67-19.16 0-31.83-12.67L222.98-447.93q-6.72-6.72-9.82-14.92-3.09-8.19-3.09-17.15 0-8.96 3.09-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.68 12.68 12.68 31.83t-12.68 31.83L318.98-480Zm267.11 0 151.17 152.17q12.67 12.68 13.06 31.45.38 18.77-13.06 32.21-12.67 12.67-31.83 12.67-19.15 0-31.82-12.67L489.85-447.93q-6.72-6.72-9.82-14.92-3.1-8.19-3.1-17.15 0-8.96 3.1-17.15 3.1-8.2 9.82-14.92l183.76-183.76q12.67-12.67 31.44-13.05 18.78-.38 32.21 13.05 12.67 12.68 12.67 31.83t-12.67 31.83L586.09-480Z"/></svg>Previous</button>
                        <button type="submit" class="submit-button">Create Internship <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M236.44-152.59q-34.46 0-59.16-24.69-24.69-24.7-24.69-59.16 0-34.47 24.69-59.02Q201.98-320 236.44-320q34.47 0 59.02 24.54Q320-270.91 320-236.44q0 34.46-24.54 59.16-24.55 24.69-59.02 24.69Zm0-243.82q-34.46 0-59.16-24.55-24.69-24.54-24.69-59.01 0-34.46 24.69-59.04 24.7-24.58 59.16-24.58 34.47 0 59.02 24.55Q320-514.5 320-480.03q0 34.46-24.54 59.04-24.55 24.58-59.02 24.58Zm0-243.59q-34.46 0-59.16-24.54-24.69-24.55-24.69-59.02 0-34.46 24.69-59.16 24.7-24.69 59.16-24.69 34.47 0 59.02 24.69Q320-758.02 320-723.56q0 34.47-24.54 59.02Q270.91-640 236.44-640Zm243.59 0q-34.46 0-59.04-24.54-24.58-24.55-24.58-59.02 0-34.46 24.55-59.16 24.54-24.69 59.01-24.69 34.46 0 59.04 24.69 24.58 24.7 24.58 59.16 0 34.47-24.55 59.02Q514.5-640 480.03-640Zm243.53 0q-34.47 0-59.02-24.54Q640-689.09 640-723.56q0-34.46 24.54-59.16 24.55-24.69 59.02-24.69 34.46 0 59.16 24.69 24.69 24.7 24.69 59.16 0 34.47-24.69 59.02Q758.02-640 723.56-640ZM480.03-396.41q-34.46 0-59.04-24.55-24.58-24.54-24.58-59.01 0-34.46 24.55-59.04 24.54-24.58 59.01-24.58 34.46 0 59.04 24.55 24.58 24.54 24.58 59.01 0 34.46-24.55 59.04-24.54 24.58-59.01 24.58Zm38.54 198.32v-65.04q0-9.2 3.47-17.53 3.48-8.34 10.2-15.06l208.76-208q9.72-9.76 21.59-14.09 11.88-4.34 23.76-4.34 12.95 0 24.8 4.86 11.85 4.86 21.55 14.57l37 37q8.67 9.72 13.55 21.6 4.88 11.87 4.88 23.75 0 12.2-4.36 24.41-4.36 12.22-14.07 21.94l-208 208q-6.69 6.72-15.04 10.07-8.36 3.36-17.55 3.36h-65.04q-19.16 0-32.33-13.17-13.17-13.17-13.17-32.33Zm270.17-185.67 34.61-36.61-37-37-35.61 35.61 38 38Z"/></svg></button>
                    </div>
                    <div class="note-banner">
                        <p class="note-banner-text">
                            <span class="note-banner-content">
                            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#666666"><path d="M479.79-288q15.21 0 25.71-10.29t10.5-25.5q0-15.21-10.29-25.71t-25.5-10.5q-15.21 0-25.71 10.29t-10.5 25.5q0 15.21 10.29 25.71t25.5 10.5ZM444-432h72v-240h-72v240Zm36.28 336Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Z"/></svg>
                                <span class="text">Setting up an assessment form is the next step after completing the internship ad.</span>
                            </span>
                        </p>
                    </div>
                </div>
            </form>

            <script src="createinternship.js" ></script>
        </div>
    </div>

    <script>
                (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="sqZ5VD70WA_0wO97JZLEZ";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <!-- FOOTER -->
        <div class="footbg">
            <div class="properdiv">
                <footer>
        
                    <!-- Logo Section -->
                    <div class="rightside">
                        <h2 class="university-name">UNIVERSITY OF CALOOCAN CITY</h2>
                        <h4 class="program-name">COMPUTER SCIENCE DEPARTMENT</h4>
                        <p>
                            Biglang Awa Street <br> Cor 11th Ave Catleya,<br> Caloocan 1400 Metro Manila, Philippines
                        </p>
                        <br>
                        <p style="margin: 0">
                            <strong>Phone:</strong>&nbsp;(02) 5310 6855
                        </p>
                        <p style="margin: 0">
                            <strong>Email:</strong>&nbsp;support@uccinternshipportal.ph
                        </p>
    
                    </div>
                
                    <!-- Internship Seekers Section -->
                    <div class="centerside">
                        <h4>INTERNSHIP SEEKERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php#advertise">Internship by Company</a></li>
                            <li><a href="companyloginpage.php#advertise">Internship by City</a></li>
                            <li><a href="companyloginpage.php#advertise">Search Nearby Internship</a></li>
                        </ul>
                    </div>
                
                    <!-- Employers Section -->
                    <div class="centerside">
                        <h4>EMPLOYERS</h4>
                        <ul>
                            <li><a href="companyloginpage.php">Post Internships</a></li>
                        </ul>
                    </div>
                
                    <!-- About Interflo Section -->
                    <div class="centerside">
                        <h4>ABOUT INTERNFLO</h4>
                        <ul>
                            <li><a href="companyloginpage.php#about">About Us</a></li>
                            <li><a href="companyloginpage.php#chatbot">How It Works</a></li>
                            <li><a href="companyloginpage.php#contact">Contact Us</a></li>
                        </ul>
                    </div>
                
                </footer>
            </div>
        </div>
        <div class="underfooter-bg">
            <div class="underfooter">
                <div class="uf-content">
                    <p>Copyright <strong>University of Caloocan City</strong> Internflo©2025. All Rights Reserved</p>
                </div>
            </div>
        </div>
    <!-- FOOTER -->	
</body>
</html>
