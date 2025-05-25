<?php
    // Database connection
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname=u798912504_internflo;charset=utf8mb4",
            "u798912504_root",
            "Internfloucc2025*",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        die('Could not connect to the database');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv'])) {
        header('Content-Type: application/json');
        $response = ['success' => false, 'error' => null];
    
        try {
            $fileTmpPath = $_FILES['cv']['tmp_name'];
            $fileName = $_FILES['cv']['name'];
            $fileSize = $_FILES['cv']['size'];
            $fileType = $_FILES['cv']['type'];
    
            if ($fileType === 'application/pdf' && $fileSize <= 2 * 1024 * 1024) { // 2MB limit
                require 'vendor/autoload.php';
                $parser = new \Smalot\PdfParser\Parser();
    
                try {
                    $pdf = $parser->parseFile($fileTmpPath);
                    $pdfText = $pdf->getText();
    
                    $stopWords = [
                        'a', 'an', 'the', 'and', 'is', 'in', 'at', 'of', 'on', 'for', 'to', 'with',
                    ];
    
                    $filteredText = preg_replace_callback(
                        '/\b(' . implode('|', array_map('preg_quote', $stopWords)) . ')\b/i',
                        function () {
                            return '';
                        },
                        $pdfText
                    );
    
                    $filteredText = preg_replace('/\s+/', ' ', trim($filteredText));

                    // Modified query to get industry from internshipad
                    $stmt = $pdo->query('
                        SELECT DISTINCT 
                            r.id,
                            r.company_name,
                            r.company_address,
                            r.company_phone,
                            r.company_email,
                            r.company_logo,
                            r.latitude,
                            r.longitude,
                            i.Keywords,
                            i.industry
                        FROM approvedrecruiters r
                        INNER JOIN internshipad i ON i.user_id = r.id
                    ');
                    $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
                    $matchingCompanies = [];
                    $pdfContent = strtolower($filteredText);
    
                    foreach ($allCompanies as $company) {
                        if (!empty($company['Keywords'])) {
                            $keywords = array_map('trim', explode(',', strtolower($company['Keywords'])));
                            foreach ($keywords as $keyword) {
                                if (!empty($keyword) && preg_match("/\b" . preg_quote($keyword, '/') . "\b/i", $pdfContent)) {
                                    $matchingCompanies[] = array_merge($company, ['matched_keyword' => $keyword]);
                                    break;
                                }
                            }
                        }
                    }

                    foreach ($allCompanies as $company) {
                        if (!empty($company['Keywords'])) {
                            $keywords = explode(',', strtolower($company['Keywords']));
                            error_log('Company: ' . $company['company_name']);
                            error_log('Keywords: ' . print_r($keywords, true));
                            error_log('PDF Content: ' . $pdfContent);
                    
                            foreach ($keywords as $keyword) {
                                $keyword = trim($keyword);
                                error_log('Checking keyword: ' . $keyword);
                                
                                if (!empty($keyword) && strpos($pdfContent, $keyword) !== false) {
                                    error_log('Keyword matched: ' . $keyword);
                                    $matchingCompanies[] = array_merge($company, ['matched_keyword' => $keyword]);
                                    break;
                                }
                            }
                        }
                    }
    
                    echo json_encode([
                        'success' => true,
                        'allCompanies' => $allCompanies,
                        'matchingCompanies' => $matchingCompanies,
                        'message' => 'File processed successfully',
                        'pdfText' => $filteredText,
                    ]);
    
                } catch (Exception $e) {
                    throw new Exception('Error parsing PDF: ' . $e->getMessage());
                }
            } else {
                throw new Exception('Invalid file type or size. Ensure the file is a PDF and under 2MB.');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Handle GET request for fetching company data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            // Modified query to get industry from internshipad for GET request
            $stmt = $pdo->query('
                SELECT DISTINCT 
                    r.id,
                    r.company_name,
                    r.company_address,
                    r.company_phone,
                    r.company_email,
                    r.company_logo,
                    r.latitude,
                    r.longitude,
                    i.Keywords,
                    i.industry
                FROM approvedrecruiters r
                INNER JOIN internshipad i ON i.user_id = r.id
            ');
            $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<script>
                const companyData = " . json_encode($allCompanies) . ";
            </script>";

        } catch (PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            echo "<script>
                const companyData = [];
            </script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="map.css">
    <link rel="icon" href="pics/ucc.png">
    <title>UCC - Internflo Map</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
</head>
<body>


    <!-- MAIN -->
    <div class="main-container">
        <!-- Side Menu 1 (Initial narrow state) -->
        <div class="side-menu" id="firstMenu" style="display: flex;">
            <div class="side-menu-header" onclick="toggleSecondMenu()">
                <img src="pics/ucc.png" alt="UCC Logo">
            </div>
            <hr class="orange">
            <div class="main-menu-content">
                <div class="menu-item-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#2e3849">
                        <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Zm120 480h200q17 0 28.5-11.5T560-320q0-17-11.5-28.5T520-360H320q-17 0-28.5 11.5T280-320q0 17 11.5 28.5T320-280Zm0-160h320q17 0 28.5-11.5T680-480q0-17-11.5-28.5T640-520H320q-17 0-28.5 11.5T280-480q0 17 11.5 28.5T320-440Zm0-160h320q17 0 28.5-11.5T680-640q0-17-11.5-28.5T640-680H320q-17 0-28.5 11.5T280-640q0 17 11.5 28.5T320-600Z"/>
                    </svg>
                    <div class="menu-item">Upload</div>
                </div>
                <div class="menu-item-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#2e3849">
                        <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm-40-82v-78q-33 0-56.5-23.5T360-320v-40L168-552q-3 18-5.5 36t-2.5 36q0 121 79.5 212T440-162Zm276-102q20-22 36-47.5t26.5-53q10.5-27.5 16-56.5t5.5-59q0-98-54.5-179T600-776v16q0 33-23.5 56.5T520-680h-80v80q0 17-11.5 28.5T400-560h-80v80h240q17 0 28.5 11.5T600-440v120h40q26 0 47 15.5t29 40.5Z"/>
                    </svg>
                    <div class="menu-item">Company</div>
                </div>
                <div class="menu-item-container">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#2e3849">
                        <path d="M440-440H240q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h200v-200q0-17 11.5-28.5T480-760q17 0 28.5 11.5T520-720v200h200q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H520v200q0 17-11.5 28.5T480-200q-17 0-28.5-11.5T440-240v-200Z"/>
                    </svg>
                    <div class="menu-item">Build</div>
                </div>
            </div>
            <div class="bottom-menu-content">
                <hr class="bottomhr">
                <div class="menu-item-bottom" onclick="location.href='MAIN.php'">
                    <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#2e3849">
                        <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h240q17 0 28.5 11.5T480-800q0 17-11.5 28.5T440-760H200v560h240q17 0 28.5 11.5T480-160q0 17-11.5 28.5T440-120H200Zm487-320H400q-17 0-28.5-11.5T360-480q0-17 11.5-28.5T400-520h287l-75-75q-11-11-11-27t11-28q11-12 28-12.5t29 11.5l143 143q12 12 12 28t-12 28L669-309q-12 12-28.5 11.5T612-310q-11-12-10.5-28.5T613-366l74-74Z"/>
                    </svg>
                    <div class="menu-item">Home</div>
                </div>
            </div>
        </div>

        <!-- Side Menu 2 (Expanded state, initially hidden) -->
        <div class="side-menu hidden" id="secondMenu">
            <div class="side-menu-header" onclick="toggleSecondMenu()">
                <img src="pics/ucc-logo.png" alt="UCC Logo">
            </div>
            <hr class="orange">
            <p class="create-instructions">Create your own CV with<br>internflo. <a href="resume/resume.php">Click here</a></p>
            <hr class="bottomhr">
            <div class="upload-container">
                <p class="upload-instructions">Upload and we'll find<br>internships that suits you.</p>
                <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="document-section">
                        <h2 class="section-title">Curriculum Vitae</h2>
                        <div class="upload-option" id="cvUploadSection">
                            <div class="file-preview" id="cvPreview">
                                <canvas id="pdfCanvas" class="pdf-preview-canvas"></canvas>
                                <button class="close-button" id="closePreview">&times;</button>
                            </div>
                            <label for="cvUpload" class="upload-button" id="uploadButton">Upload</label>
                            <input name="cv" type="file" id="cvUpload" class="file-input" accept=".pdf" required>
                            <div class="file-types">Accepts .pdf file (2MB limit).</div>
                            <span class="error-message" style="display: none;">This field is required</span>
                        </div>
                    </div>

                    <button type="submit" class="submit-button">
                        <div class="button-content">
                            <span>Start Looking</span>
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                                <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q146 0 255.5 91.5T872-559h-82q-19-73-68.5-130.5T600-776v16q0 33-23.5 56.5T520-680h-80v80q0 17-11.5 28.5T400-560h-80v80h80v120h-40L168-552q-3 18-5.5 36t-2.5 36q0 131 92 225t228 95v80Zm364-20L716-228q-21 12-45 20t-51 8q-75 0-127.5-52.5T440-380q0-75 52.5-127.5T620-560q75 0 127.5 52.5T800-380q0 27-8 51t-20 45l128 128-56 56ZM620-280q42 0 71-29t29-71q0-42-29-71t-71-29q-42 0-71 29t-29 71q0 42 29 71t71 29Z"/>
                            </svg>
                        </div>
                    </button>
                </form>
                
                <button class="view-button">
                        <div class="button-content">
                            <span>Explore All</span>
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m600-200-57-56 184-184H80v-80h647L544-704l56-56 280 280-280 280Z"/></svg>
                        </div>
                </button>
            </div>
        </div>

        <div class="view-company-loading-overlay">
            <div class="view-company-loading-spinner"></div>
        </div>



        <div id="loadingOverlay" style="display: none;">
            <div class="loading-container">
                <div class="scanner-wrapper">
                    <div class="import-document-animation">
                        <svg xmlns="http://www.w3.org/2000/svg" class="document-gray" width="98" height="121" viewBox="0 0 98 121">
                            <g fill="none" fill-rule="evenodd">
                                <path fill="#F0F3F6" d="M0 119L96 119 96 0 0 0z" />
                                <path stroke="#C9CBCC" stroke-width="2" d="M0 119L96 119 96 0 0 0z" />
                            </g>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-left-background-gray" width="98" height="121" viewBox="0 0 98 121">
                            <g>
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="4" d="M10.5 13.5L85.5 13.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M25.5 22.5L75.5 22.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="3" d="M10.5 35.5L40.5 35.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 43.5L85.5 43.5" opacity=".6" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 48.5L80.5 48.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 53.5L75.5 53.5" opacity=".8" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="3" d="M10.5 65.5L40.5 65.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 73.5L85.5 73.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 78.5L70.5 78.5" opacity=".7" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="3" d="M10.5 90.5L40.5 90.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 98.5L85.5 98.5" />
                                <path stroke="#A9ABB5" stroke-linecap="round" stroke-width="2" d="M15.5 103.5L80.5 103.5" opacity=".8" />
                            </g>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="text-left-background-blue" width="98" height="121" viewBox="0 0 98 121">
                            <g>
                                <path stroke="#666666" stroke-linecap="round" stroke-width="4" d="M10.5 13.5L85.5 13.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M25.5 22.5L75.5 22.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="3" d="M10.5 35.5L40.5 35.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 43.5L85.5 43.5" opacity=".6" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 48.5L80.5 48.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 53.5L75.5 53.5" opacity=".8" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="3" d="M10.5 65.5L40.5 65.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 73.5L85.5 73.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 78.5L70.5 78.5" opacity=".7" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="3" d="M10.5 90.5L40.5 90.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 98.5L85.5 98.5" />
                                <path stroke="#666666" stroke-linecap="round" stroke-width="2" d="M15.5 103.5L80.5 103.5" opacity=".8" />
                            </g>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="139" viewBox="0 0 24 139" class="active-scanner">
                            <defs>
                                <linearGradient id="scanner-gradient-1" x1="100%" x2="19.594%" y1="50%" y2="50%">
                                    <stop offset="0%" stop-color="#FFF" stop-opacity="0" />
                                    <stop offset="100%" stop-color="#FFF" />
                                </linearGradient>
                                <linearGradient id="scanner-gradient-2" x1="100%" x2="0%" y1="50%" y2="50%">
                                    <stop offset="0%" stop-color="#00FFEC" stop-opacity="0" />
                                    <stop offset="100%" stop-color="#FFF" />
                                </linearGradient>
                            </defs>
                            <g fill="none" fill-rule="evenodd">
                                <rect width="13" height="139" fill="#24A8A8" rx="6.5" />
                                <path fill="#27817F" d="M7 1v137h-.5c-3.038 0-5.5-2.462-5.5-5.5V6.5C1 3.462 3.462 1 6.5 1H7z" />
                                <path fill="url(#scanner-gradient-1)" d="M9 6H13V134H9z" />
                                <path fill="url(#scanner-gradient-2)" d="M9 6H24V134H9z" class="active-scanner-light" />
                            </g>
                        </svg>
                    </div>
                </div>
                <p id="loadingText" class="loading-text">Analyzing your Curriculum Vitae...</p>
            </div>
        </div>

        <div id="successOverlay" style="display: none;">
            <div class="success-container">
                <div class="success-checkmark">
                    <svg xmlns="http://www.w3.org/2000/svg" height="150px" viewBox="0 -960 960 960" width="150px" fill="#155724">
                        <path d="m382-354 339-339q12-12 28-12t28 12q12 12 12 28.5T777-636L410-268q-12 12-28 12t-28-12L182-440q-12-12-11.5-28.5T183-497q12-12 28.5-12t28.5 12l142 143Z"/>
                    </svg>
                </div>
                <h2>Curriculum Vitae</h2>
                <p>Analysis Complete!</p>
                <p id="scannedText" style="display: none;"></p>
                <button class="result-button">See Result</button>
            </div>
        </div>

        <div id="main-content">
            <div class="search-container">
                <div class="search-box-container">
                    <input type="text" class="search-input-bar" placeholder="Search Company" id="searchInput">
                    <div id="autocompleteResults" class="autocomplete-results"></div>
                    <div class="search-icon-button">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849">
                            <path d="M380-320q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l224 224q11 11 11 28t-11 28q-11 11-28 11t-28-11L532-372q-30 24-69 38t-83 14Zm0-80q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="company-button">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849"><path d="M72.59-154.5v-588.57q0-11.43 4.83-20.86 4.84-9.44 14.28-16.16l161.91-117.37q12.19-8.71 26.39-8.71 14.2 0 26.39 8.71L468.3-780.09q9.44 6.72 14.28 16.16 4.83 9.43 4.83 20.86v59.48h354.5q19.16 0 32.33 13.18 13.17 13.17 13.17 32.32v483.59q0 19.15-13.17 32.33Q861.07-109 841.91-109H118.09q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33ZM160-196.41h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 0h80v-80h-80v80Zm0 480h480v-400H320v400Zm280-320h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5Zm0 160h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5Zm-120-120q0 17-11.5 28.5t-28.5 11.5q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5q17 0 28.5 11.5t11.5 28.5Zm-40 200q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5Z"/></svg>
                    <span>Institutions</span>
                </div>
                <div class="company-button">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849"><path d="M60.78-713.22q0-44.3 30.85-75.15 30.85-30.85 75.15-30.85h626.44q44.3 0 75.15 30.85 30.85 30.85 30.85 75.15V-653q0 22.09-15.46 37.54Q868.3-600 846.22-600q-22.09 0-37.55-15.46-15.45-15.45-15.45-37.54v-60.22H166.78V-653q0 22.09-15.45 37.54Q135.87-600 113.78-600q-22.08 0-37.54-15.46Q60.78-630.91 60.78-653v-60.22Zm106 572.44q-44.3 0-75.15-30.85-30.85-30.85-30.85-75.15V-307q0-22.09 15.46-37.54Q91.7-360 113.78-360q22.09 0 37.55 15.46 15.45 15.45 15.45 37.54v60.22h626.44V-307q0-22.09 15.45-37.54Q824.13-360 846.22-360q22.08 0 37.54 15.46 15.46 15.45 15.46 37.54v60.22q0 44.3-30.85 75.15-30.85 30.85-75.15 30.85H166.78ZM400-280q11 0 21-5.5t15-16.5l124-248 44 88q5 11 15 16.5t21 5.5h219.22q17 0 28.5-11.5t11.5-28.5q0-17-11.5-28.5t-28.5-11.5H665l-69-138q-5-11-15-15.5t-21-4.5q-11 0-21 4.5T524-658L400-410l-44-88q-5-11-15-16.5t-21-5.5H100.78q-17 0-28.5 11.5T60.78-480q0 17 11.5 28.5t28.5 11.5H295l69 138q5 11 15 16.5t21 5.5Zm80-200Z"/></svg>
                    <span>Healthcare</span>
                </div>
                <div class="company-button">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849"><path d="M182.48-322.83V-505.3q0-20.4 14.32-34.44 14.33-14.04 34.72-14.04t34.44 14.04Q280-525.7 280-505.3v183.04q0 20.39-14.04 34.43-14.05 14.05-34.44 14.05-20.39 0-34.72-14.33-14.32-14.32-14.32-34.72Zm249.04.57V-505.3q0-20.4 14.04-34.44 14.05-14.04 34.44-14.04 20.39 0 34.44 14.04 14.04 14.04 14.04 34.44v183.04q0 20.39-14.04 34.43-14.05 14.05-34.44 14.05-20.39 0-34.44-14.05-14.04-14.04-14.04-34.43ZM113.78-105.3q-22.08 0-37.54-15.46T60.78-158.3q0-22.09 15.46-37.55 15.46-15.45 37.54-15.45h732.44q22.08 0 37.54 15.45 15.46 15.46 15.46 37.55 0 22.08-15.46 37.54t-37.54 15.46H113.78ZM680-322.83V-505.3q0-20.4 14.33-34.44 14.32-14.04 34.71-14.04 20.4 0 34.44 14.04t14.04 34.44v183.04q0 20.39-14.04 34.43-14.04 14.05-34.44 14.05-20.39 0-34.71-14.33Q680-302.43 680-322.83Zm163.96-293.99H110.35q-20.65 0-35.11-14.46t-14.46-35.11v-31.04q0-14.4 7.48-26.07t19.87-18.37l344.57-172q22.08-11.39 47.3-11.39t47.3 11.39l341.44 170.43q14.39 6.7 22.43 20.37 8.05 13.68 8.05 29.77v20.65q0 23.22-16.02 39.52-16.03 16.31-39.24 16.31Z"/></svg>
                    <span>Finance</span>
                </div>
                <div class="company-button">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#2e3849"><path d="m677.57-66.61-8.44-41.17q-9.74-3.87-19.11-9.31-9.37-5.43-18.24-11.73l-40.3 13.3q-9.26 2.57-18.02-.5t-13.33-11.33L535-170.61q-5.13-8.26-3.07-17.52 2.07-9.26 8.77-15.96l31.3-27.3q-2.57-11.61-2.28-22.39.28-10.78 2.28-22.39l-31.3-27.31q-7.27-6.69-9.05-15.95-1.78-9.26 3.35-17.53l25.13-42.69q4.57-8.26 13.33-11.61 8.76-3.35 18.02-.22l39.74 13.31q8.87-6.31 18.52-12.24 9.65-5.94 19.39-9.81l8.44-41.17q2-9.26 9.04-15.17 7.04-5.92 16.74-5.92h50.82q9.7 0 16.74 5.92 7.05 5.91 9.05 15.17l8.43 41.17q9.74 3.87 19.11 9.31 9.37 5.43 18.24 12.87l39.74-13.87q9.26-3.13 18.3.22 9.05 3.34 13.61 11.6l25.13 43.83q4.57 8.26 2.79 17.52-1.79 9.26-8.48 15.96l-31.31 27.3q2.57 11.61 2.29 21.83-.29 10.22-2.29 21.83l31.31 27.3q7.26 6.7 9.04 15.96 1.78 9.26-3.35 17.52l-25.13 43.26q-4.56 8.26-13.11 11.33-8.54 3.06-17.8.5l-40.74-12.87q-8.87 6.3-18.24 11.73-9.37 5.44-19.11 9.31l-8.43 41.17q-2 9.26-9.05 15.18-7.04 5.91-16.74 5.91h-50.82q-9.7 0-16.74-5.91-7.04-5.92-9.04-15.18Zm50.91-111.91q30.74 0 53.39-22.37 22.65-22.37 22.65-53.11t-22.65-53.11q-22.65-22.37-53.39-22.37-30.74 0-53.11 22.37T653-254q0 30.74 22.37 53.11t53.11 22.37ZM423.61-435.48q-18.7 0-31.61-12.91T379.09-480q0-18.7 12.91-31.61t31.61-12.91q18.69 0 31.61 12.91 12.91 12.91 12.91 31.61t-12.91 31.61q-12.92 12.91-31.61 12.91Zm-294.7 334.7q-22.09 0-37.54-15.46-15.46-15.46-15.46-37.54 0-22.09 15.46-37.55 15.45-15.45 37.54-15.45h35.48v-546.44q0-44.3 30.85-75.15 30.85-30.85 75.15-30.85h233.22q25.26 0 46.43 10.72 21.18 10.72 35.57 29.28h104q44.3 0 75.15 30.85 30.85 30.85 30.85 75.15v117.74q0 22.09-15.46 37.55-15.45 15.45-37.54 15.45t-37.54-15.45q-15.46-15.46-15.46-37.55v-117.74h-80v171.09q0 25.65-16.46 39.04-16.45 13.4-36.54 13.4-19.52 0-36.26-12.83t-16.74-39.61v-211.09H270.39v546.44h139.09q26.22 0 39.11 16.45 12.89 16.46 12.89 36.55 0 19.52-12.61 36.26t-39.39 16.74H128.91Zm141.48-106v-546.44 546.44Z"/></svg>
                    <span>Technology</span>
                </div>
            </div>
            <!-- Add this new results overlay structure -->
            <div class="results-overlay-container" style="display: none;">
                <h2>Analysis Complete</h2>
                <div id="results-content">
                    <p class="results-count"></p>
                </div>
                <hr class="resulthr">
                <div class="results-footer">
                    Internflo will find companies that suits you. 
                    <a href="#">Learn more</a>
                </div>
            </div>
            <div class="view-overlay-container" style="display: none;">
                <h2>Explore all the companies</h2>
                <div id="view-content">
                    <p class="view-count"></p>
                    <!-- Company list will be dynamically populated here -->
                </div>
                <hr class="viewhr">
                <div class="view-footer">
                    Internflo will find companies that suits you. 
                    <a href="#">Learn more</a>
                </div>
            </div>
            <div id="map"></div>
            
        </div>
    </div>
    <!-- MAIN -->


    <!-- LOADING ANIMATION SCRIPT -->
    <script>
            document.addEventListener('DOMContentLoaded', function () {
                const uploadForm = document.getElementById('uploadForm');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const successOverlay = document.getElementById('successOverlay');
                const scannedTextElement = document.getElementById('scannedText');
                const loadingText = document.getElementById('loadingText');

                // Adjusted durations to total 15 seconds
                const loadingSequence = [
                    { message: "Initializing Analysis...", duration: 2000 },
                    { message: "Extracting Content...", duration: 4000 },
                    { message: "Processing Data...", duration: 4000 },
                    { message: "Analyzing...", duration: 3000 },
                    { message: "Finalizing...", duration: 2000 }
                ];

                uploadForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(uploadForm);

                    loadingOverlay.style.display = 'flex';
                    
                    let currentIndex = 0;
                    loadingText.textContent = loadingSequence[0].message;

                    const updateLoadingText = () => {
                        if (currentIndex < loadingSequence.length - 1) {
                            setTimeout(() => {
                                currentIndex++;
                                loadingText.textContent = loadingSequence[currentIndex].message;
                                loadingText.style.opacity = 0;
                                setTimeout(() => {
                                    loadingText.style.opacity = 1;
                                }, 100);
                                updateLoadingText();
                            }, loadingSequence[currentIndex].duration);
                        }
                    };

                    updateLoadingText();

                    fetch(uploadForm.action, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to upload data');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Total duration of loading sequence is now 15 seconds
                        setTimeout(() => {
                            loadingOverlay.style.display = 'none';

                            if (data.success) {
                                scannedTextElement.textContent = data.pdfText || 'No text extracted.';
                                successOverlay.style.display = 'flex';
                            } else {
                                alert('Error: ' + data.error);
                            }
                        }, 15000); // Changed to 15 second duration
                    })
                    .catch(error => {
                        loadingOverlay.style.display = 'none';
                        console.error('Error:', error);
                        alert('There was an issue uploading your file. Please try again.');
                    });
                });

                // Add click handler for "Close" button
                document.querySelector('.result-button').addEventListener('click', function () {
                    successOverlay.style.display = 'none';
                });
            });
    </script>
    <!-- LOADING ANIMATION SCRIPT -->


    <!-- NATURAL LANGUAGE PROCESSING -->
    <script>
        compromise.GlobalOptions = {
            workerSrc: 'https://unpkg.com/compromise@latest/builds/compromise.min.js',
            version: 'latest'
        };
    </script>
    <script>
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                const fileInput = document.getElementById('cvUpload');
                const preview = document.getElementById('cvPreview');
                const canvas = document.getElementById('pdfCanvas');
                const closeButton = document.getElementById('closePreview');
                const errorMessage = document.querySelector('.error-message');
                const uploadButton = document.getElementById('uploadButton');

                fileInput.addEventListener('change', async function(e) {
                    const file = e.target.files[0];
                    
                    errorMessage.style.display = 'none';

                    if (!file) return;

                    if (file.type !== 'application/pdf') {
                        errorMessage.textContent = 'Please upload a PDF file';
                        errorMessage.style.display = 'block';
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        errorMessage.textContent = 'File size exceeds 2MB limit';
                        errorMessage.style.display = 'block';
                        return;
                    }

                    try {
                        const arrayBuffer = await file.arrayBuffer();
                        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                        const page = await pdf.getPage(1);
                        
                        // Adjust these values to change preview size
                        const maxWidth = 110;  // Reduced from 280
                        const maxHeight = 110; // Reduced from 396
                        
                        const viewport = page.getViewport({ scale: 1.0 });
                        const scale = Math.min(maxWidth / viewport.width, maxHeight / viewport.height);
                        const scaledViewport = page.getViewport({ scale });

                        canvas.width = scaledViewport.width;
                        canvas.height = scaledViewport.height;

                        const renderContext = {
                            canvasContext: canvas.getContext('2d'),
                            viewport: scaledViewport,
                            enableWebGL: true // Enable WebGL for better rendering
                        };

                        await page.render(renderContext).promise;
                        preview.classList.add('show');
                        uploadButton.classList.add('hidden');

                    } catch (error) {
                        console.error('Error generating preview:', error);
                        errorMessage.textContent = 'Error generating preview';
                        errorMessage.style.display = 'block';
                    }
                });

                closeButton.addEventListener('click', function() {
                    preview.classList.remove('show');
                    fileInput.value = '';
                    errorMessage.style.display = 'none';
                    uploadButton.classList.remove('hidden');
                });
    </script>
    <!-- NATURAL LANGUAGE PROCESSING -->


    <!-- SIDE MENU SCRIPT -->
    <script>
        function toggleSecondMenu() {
            const firstMenu = document.getElementById('firstMenu');
            const secondMenu = document.getElementById('secondMenu');
            const mainContent = document.getElementById('main-content');

            // Toggle visibility with classes instead of inline styles
            firstMenu.classList.toggle('hidden');
            secondMenu.classList.toggle('hidden');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <!-- SIDE MENU SCRIPT -->


    <!-- MAP SCRIPT -->
    <script>
                 function initMap() {
                    // Default center of the map
                    const defaultLocation = { lat: 14.6896, lng: 121.0881 };
                    
                    // Create a new map centered on the default location
                    
                    // Create a new map centered on the default location
                    const map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 15,
                    center: defaultLocation,
                    mapTypeControl: true,
                    zoomControl: false,
                    clickableIcons: false,
                    streetViewControl: true,
                    fullscreenControl: false,
                    rotateControl: false,
                    gestureHandling: 'greedy',
                    mapTypeId: google.maps.MapTypeId.TERRAIN,
                    mapId: "21c183cfde31a888", // Add your Map ID here
                    mapTypeControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_BOTTOM,
                        style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                        mapTypeIds: [
                            google.maps.MapTypeId.TERRAIN,
                            google.maps.MapTypeId.SATELLITE
                        ]
                    },
                    streetViewControlOptions: {
                        position: google.maps.ControlPosition.LEFT_BOTTOM
                    },
                    streetViewOptions: {
                        addressControl: false
                    },
                    });
                        document.head.insertAdjacentHTML('beforeend', `
                        <style>
                            /* Hide the copyright text and ToS */
                            .gm-style-cc:not(.gmnoprint) { 
                                display: none !important;
                            }

                            .gmnoprint.gm-style-mtc-bbw {
                                bottom: 0 !important;
                            }
                            
                            /* Hide Google Maps text logo */
                            a[href^="http://maps.google.com/maps"]:not(.google-maps-link), 
                            a[href^="https://maps.google.com/maps"]:not(.google-maps-link) {
                                display: none !important;
                            }
                            
                            /* Hide Terms text specifically */
                            .gm-style-cc > div > a[href*="terms"] {
                                display: none !important;
                            }
                            
                            /* Hide the container of Terms text if empty */
                            .gm-style-cc:empty,
                            .gm-style-cc > div:empty {
                                display: none !important;
                            }

                            /* Style the Pegman (Street View control) in all states */
                            .gm-svpc {
                                background-color: white !important;
                                border-radius: 2px !important;
                            }

                            /* Base Pegman color (#449e25) */
                            .gm-svpc img,
                            [src*="cb_scout_sprite"],
                            [src*="cb_scout_sprite_2x"],
                            .gm-control-active img {
                                filter: hue-rotate(112deg) saturate(140%) brightness(80%) !important;
                            }

                            /* Hover state color (#4aa629) */
                            .gm-svpc:hover img {
                                filter: hue-rotate(112deg) saturate(150%) brightness(85%) !important;
                            }

                            /* Dragged Pegman color (#449e25) */
                            .gm-style [src*="pegman"]:not([src*="pegman_dock"]),
                            .gm-style [src*="cb_scout"] {
                                filter: hue-rotate(112deg) saturate(140%) brightness(80%) !important;
                            }

                            /* Style the highlighted roads when dragging Pegman */
                            .gm-style .gm-style-pbc {
                                background-color: rgba(68, 158, 37, 0.25) !important;
                            }
                        </style>
                `);

                    // Get the street view instance
                const streetView = map.getStreetView();

                    // Add event listener for street view visibility changes
                google.maps.event.addListener(streetView, 'visible_changed', function() {
                        const isVisible = streetView.getVisible();
                        const searchContainer = document.querySelector('.search-container');
                        
                        // Hide/show the search container based on street view visibility
                        if (searchContainer) {
                            searchContainer.style.display = isVisible ? 'none' : 'flex'; // Use 'flex' if that's your original display value
                        }
                });

                    // Set initial street view options
                streetView.setOptions({
                        addressControl: false,
                        enableCloseButton: true,    
                        showRoadLabels: false,             
                        fullscreenControl: false,    
                        motionTracking: false,       
                        motionTrackingControl: false,
                        zoomControl: false
                });


                // Arrays to store all company markers, overlays, and cards
                const companyElements = [];
                let userMarker;
                let activeCardDiv = null;


                // SEARCH COMPANY FILTERING

                // Add this new function for industry filtering
                function showCompaniesByIndustry(industry) {
                // Hide all markers and overlays
                companyElements.forEach(element => {
                    element.marker.map = null;
                    element.labelOverlay.setMap(null);
                    element.cardOverlay.setMap(null);
                    element.cardDiv.style.display = 'none';
                });

                let foundCompanies = false;
                let matchingElements = [];

                // Show companies matching the industry
                companyElements.forEach((element, index) => {
                    // Check if company has the selected industry in any of its internship ads
                    if (companyData[index] && companyData[index].industry && 
                        companyData[index].industry.toLowerCase() === industry.toLowerCase()) {
                        element.marker.map = map;
                        element.labelOverlay.setMap(map);
                        
                        // Set the industry data attribute for the card
                        element.cardDiv.dataset.industry = industry;
                        element.cardDiv.dataset.matchedKeyword = ''; // Clear any previous matched keyword
                        
                        matchingElements.push(element);
                        foundCompanies = true;
                    }
                });

                    if (foundCompanies && matchingElements.length > 0) {
                        // Calculate the bounds to include all matching companies
                        const bounds = new google.maps.LatLngBounds();
                        matchingElements.forEach(element => {
                            bounds.extend(element.marker.position);
                        });

                        // Add some padding to the bounds
                        const padding = 50; // pixels
                        map.fitBounds(bounds, padding);

                        // If there are multiple companies, adjust the zoom to show all
                        if (matchingElements.length > 1) {
                            // Wait a bit to ensure bounds are calculated before adjusting
                            setTimeout(() => {
                                // Zoom in slightly, then zoom out just a touch
                                const currentZoom = map.getZoom();
                                map.setZoom(currentZoom + 0.25);

                                // Minimal zoom out after the slight zoom in
                                setTimeout(() => {
                                    map.setZoom(currentZoom + 0.1);
                                }, 150);
                            }, 100);
                        } else {
                            // For a single company, use the standard zoom and offset
                            const center = bounds.getCenter();
                            const scale = Math.pow(2, 16); // Using zoom level 16
                            const worldCoordinateCenter = map.getProjection().fromLatLngToPoint(center);
                            const pixelOffset = new google.maps.Point(0, -180); // Offset upward by 200 pixels

                            const worldCoordinateNewCenter = new google.maps.Point(
                                worldCoordinateCenter.x,
                                worldCoordinateCenter.y + (pixelOffset.y / scale)
                            );

                            const newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
                            map.setZoom(16);
                            map.panTo(newCenter);
                        }

                        return true;
                    }
                    return false;
                }

                // Initialize industry button handlers
                function initializeIndustryButtons() {
                    const industryButtons = document.querySelectorAll('.company-button');
                    
                    industryButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            // Hide results and view overlays when filtering by industry
                            hideResultsOverlay();
                            
                            // Remove active class from all buttons
                            industryButtons.forEach(btn => btn.classList.remove('active'));
                            
                            // Add active class to clicked button
                            this.classList.add('active');
                            
                            const industry = this.querySelector('span').textContent;
                            const found = showCompaniesByIndustry(industry);
                            
                            if (!found) {
                                alert('No companies found in this industry.');
                            }
                        });
                    });
                }

                // Modify the showSelectedCompany function to set industry data
                function showSelectedCompany(companyName) {
                    // Hide all markers and overlays
                    companyElements.forEach(element => {
                        element.marker.map = null; // Hide marker
                        element.labelOverlay.setMap(null); // Hide label overlay
                        element.cardOverlay.setMap(null); // Hide card overlay
                        element.cardDiv.style.display = 'none'; // Hide card
                    });

                    // Find the matching company element
                    const matchingElement = companyElements.find(element => 
                        element.marker.title.toLowerCase() === companyName.toLowerCase()
                    );

                    if (matchingElement) {
                        // Find the company data to get the industry
                        const companyIndex = companyData.findIndex(company => 
                            company.company_name.toLowerCase() === companyName.toLowerCase()
                        );
                        
                        if (companyIndex !== -1 && companyData[companyIndex].industry) {
                            // Set the industry in the card's data attribute
                            matchingElement.cardDiv.dataset.industry = companyData[companyIndex].industry;
                            matchingElement.cardDiv.dataset.matchedKeyword = ''; // Clear any previous matched keyword
                        }

                        // Show the matching company marker and label
                        matchingElement.marker.map = map; // Show marker
                        matchingElement.labelOverlay.setMap(map); // Show label overlay

                        // Hide any active card
                        if (activeCardDiv) {
                            activeCardDiv.style.display = 'none';
                            companyElements.forEach(element => {
                                if (element.cardOverlay) element.cardOverlay.setMap(null);
                            });
                        }

                        // Show company card
                        matchingElement.cardDiv.style.display = 'block';
                        matchingElement.cardOverlay.setMap(map);
                        activeCardDiv = matchingElement.cardDiv;

                        // Set zoom level
                        map.setZoom(16);

                        // Calculate new center position with offset
                        const companyLocation = matchingElement.marker.position;
                        const scale = Math.pow(2, map.getZoom());
                        const worldCoordinateCenter = map.getProjection().fromLatLngToPoint(companyLocation);
                        const pixelOffset = new google.maps.Point(0, -180); // Offset upward by 200 pixels

                        const worldCoordinateNewCenter = new google.maps.Point(
                            worldCoordinateCenter.x,
                            worldCoordinateCenter.y + (pixelOffset.y / scale)
                        );

                        const newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
                        map.panTo(newCenter);

                        return true;
                    }
                    return false;
                }

                function hideResultsOverlay() {
                    const resultsOverlay = document.querySelector('.results-overlay-container');
                    const viewOverlay = document.querySelector('.view-overlay-container');
                    
                    if (resultsOverlay) {
                        resultsOverlay.style.display = 'none';
                    }
                    
                    if (viewOverlay) {
                        viewOverlay.style.display = 'none';
                    }
                }

                // Initialize search and autocomplete functionality
                // Modified initializeSearch function
                function initializeSearch() {
                    const searchInput = document.getElementById('searchInput');
                    const autocompleteResults = document.getElementById('autocompleteResults');
                    const searchButton = document.querySelector('.search-icon-button');

                    // Hide results overlay when focusing on search
                    searchInput.addEventListener('focus', hideResultsOverlay);

                    // Function to update autocomplete results
                    function updateAutocomplete() {
                        hideResultsOverlay(); // Hide results when typing
                        const searchText = searchInput.value.toLowerCase();
                        
                        if (searchText.length < 1) {
                            autocompleteResults.style.display = 'none';
                            return;
                        }
                        
                        const companyNames = companyElements.map(element => element.marker.title);
                        const matchingCompanies = companyNames.filter(name => 
                            name.toLowerCase().includes(searchText)
                        );
                        
                        if (matchingCompanies.length > 0) {
                            autocompleteResults.innerHTML = matchingCompanies
                                .map(name => `<div class="autocomplete-item">${name}</div>`)
                                .join('');
                            autocompleteResults.style.display = 'block';
                        } else {
                            autocompleteResults.style.display = 'none';
                        }
                    }

                    searchInput.addEventListener('input', updateAutocomplete);

                                // Autocomplete item click handler
                                // Modified autocomplete item click handler
                    autocompleteResults.addEventListener('click', function(e) {
                        if (e.target.classList.contains('autocomplete-item')) {
                            const selectedCompany = e.target.textContent;
                            searchInput.value = selectedCompany;
                            autocompleteResults.style.display = 'none';
                            hideResultsOverlay();
                            showSelectedCompany(selectedCompany);
                        }
                    });

                    // Modified search button click handler
                    searchButton.addEventListener('click', function() {
                        const searchText = searchInput.value.trim();
                        if (searchText) {
                            hideResultsOverlay();
                            const found = showSelectedCompany(searchText);
                            if (!found) {
                                alert('Company not found on the map.');
                            }
                        }
                    });

                                // Enter key handler
                                searchInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            const searchText = this.value.trim();
                            if (searchText) {
                                hideResultsOverlay();
                                const found = showSelectedCompany(searchText);
                                if (!found) {
                                    alert('Company not found on the map.');
                                }
                            }
                        }
                    });

                    document.addEventListener('click', function(e) {
                        if (!searchInput.contains(e.target) && !autocompleteResults.contains(e.target)) {
                            autocompleteResults.style.display = 'none';
                        }
                    });
                }

                // Modified toggleCompanyMarkers function
function toggleCompanyMarkers(show, showMatching = false) {
    // Store matched keywords in memory
    let allMatchedKeywords = [];

    if (show) {
        // Hide both results and view overlays when a button is clicked
        const resultsContainer = document.querySelector('.results-overlay-container');
        const viewOverlayContainer = document.querySelector('.view-overlay-container');
        
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
        
        if (viewOverlayContainer) {
            viewOverlayContainer.style.display = 'none';
        }

        // Only add loading animation for view-button if it triggered the function
        const viewButton = document.querySelector('.view-button');
        const resultButton = document.querySelector('.result-button');
        const isViewButtonTriggered = document.activeElement === viewButton;

        // Loading animation only for view-button
        if (isViewButtonTriggered) {
            // Loading animation
            const loadingOverlay = document.querySelector('.view-company-loading-overlay');
            
            // Create spinner
            const spinner = document.createElement('div');
            spinner.classList.add('view-company-loading-spinner');
            
            // Create loading text
            const loadingText = document.createElement('div');
            loadingText.classList.add('view-company-loading-text');
            loadingText.textContent = "Searching the map...";

            // Clear any existing children and add new elements
            loadingOverlay.innerHTML = '';
            loadingOverlay.appendChild(spinner);
            loadingOverlay.appendChild(loadingText);

            // Show loading overlay
            loadingOverlay.style.display = 'flex';
            setTimeout(() => {
                loadingOverlay.classList.add('show');
            }, 10);
        }

        // Delay the actual marker and zoom operations
        setTimeout(() => {
            const bounds = new google.maps.LatLngBounds();

            if (userMarker) {
                bounds.extend(userMarker.position);
            }

            const scannedText = document.getElementById('scannedText').textContent.toLowerCase();
            
            // Filter companies based on matching keywords
            let visibleCompanies = showMatching 
            ? companyElements.filter((element, index) => {
                if (!companyData[index].Keywords) return false;
                
                const companyKeywords = companyData[index].Keywords
                    .split(',')
                    .map(keyword => keyword.trim())
                    .filter(keyword => keyword !== '');
                
                // Check if any keyword matches
                let matchedKeyword = '';
                const hasMatch = companyKeywords.some(keyword => {
                    // Create a regex pattern that treats special characters as literal characters
                    const escapedKeyword = keyword
                        .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
                        .replace(/[#.+]/g, '\\$&');
                    
                    const pattern = new RegExp(`(?<=^|[^\\w./#+])(${escapedKeyword})(?=[^\\w./#+]|$)`, 'i');
                    if (pattern.test(scannedText)) {
                        matchedKeyword = keyword;
                        return true;
                    }
                    return false;
                });
                
                // If there's a match, store the matched keyword in the card's data attribute
                if (hasMatch) {
                    // Store all matched keywords as a comma-separated list
                    element.cardDiv.dataset.matchedKeywords = companyKeywords
                        .filter(keyword => {
                            const escapedKeyword = keyword
                                .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
                                .replace(/[#.+]/g, '\\$&');
                            return new RegExp(`(?<=^|[^\\w./#+])(${escapedKeyword})(?=[^\\w./#+]|$)`, 'i')
                                .test(scannedText);
                        })
                        .join(',');
                    element.cardDiv.dataset.industry = companyData[index].industry || '';
                }
                
                return hasMatch;
            })
            : companyElements;

            // Show results overlay for matching companies
            if (showMatching) {
                const matchingCompanies = visibleCompanies.map(element => {
                    const companyIndex = companyData.findIndex(company => 
                        parseFloat(company.latitude) === element.marker.position.lat &&
                        parseFloat(company.longitude) === element.marker.position.lng
                    );

                    if (companyIndex === -1) return null;

                    const company = companyData[companyIndex];
                    const matchedKeywords = company.Keywords.toLowerCase()
                        .split(',')
                        .map(keyword => keyword.trim())
                        .filter(keyword => {
                            const escapedKeyword = keyword
                                .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
                                .replace(/[#.+]/g, '\\$&');
                            return new RegExp(`(?<=^|[^\\w./#+])(${escapedKeyword})(?=[^\\w./#+]|$)`, 'i')
                                .test(scannedText);
                        });
                    
                    return {
                        name: company.company_name,
                        keywords: matchedKeywords
                    };
                }).filter(company => company !== null);

                // Get or create the results overlay
                let resultsOverlay = document.querySelector('.results-overlay-container');
                if (!resultsOverlay) {
                    resultsOverlay = createResultsOverlay();
                }

                // Get references to key elements
                const resultsContainer = document.querySelector('.results-overlay-container');
                const resultsCount = resultsContainer.querySelector('.results-count');

                if (matchingCompanies.length > 0) {
                    // Collect all unique matched keywords in memory
                    allMatchedKeywords = [
                        ...new Set(
                            matchingCompanies.flatMap(company => company.keywords)
                        )
                    ];

                    // Update results count
                    resultsCount.textContent = `We found ${matchingCompanies.length} companies that might be perfect for you. Go check them out!`;
                    
                    // Show the container
                    resultsContainer.style.display = 'block';
                } else {
                    // No matches found
                    resultsCount.textContent = 'No companies that might fit you were found. Showing all available companies instead.';
                    
                    // Clear keywords
                    allMatchedKeywords = [];
                    
                    // Show the container
                    resultsContainer.style.display = 'block';
                    // Show all companies if no matches found
                    visibleCompanies = companyElements;
                }
            }

            // Handle view overlay only when not showing matching results
            if (!showMatching) {
                const viewOverlayContainer = document.querySelector('.view-overlay-container');
                if (viewOverlayContainer) {
                    const viewCount = viewOverlayContainer.querySelector('.view-count');
                    const viewContent = viewOverlayContainer.querySelector('#view-content');
                    
                    if (viewCount) {
                        viewCount.textContent = `We found ${companyElements.length} companies for you to explore.`;
                    }

                    // Show the container
                    viewOverlayContainer.style.display = 'block';
                }
            }
        
            // Zoom out animation
            const currentZoom = map.getZoom();
            const targetZoom = 13;
            let step = 0;

            const zoomAnimation = setInterval(() => {
                if (step < 10) {
                    const newZoom = currentZoom - ((currentZoom - targetZoom) * (step / 10));
                    map.setZoom(newZoom);
                    step++;
                } else {
                    clearInterval(zoomAnimation);
                    
                    companyElements.forEach((element, index) => {
                        const shouldShow = visibleCompanies.includes(element);
                        
                        if (shouldShow) {
                            // Show marker and its overlays
                            element.marker.setMap(map);
                            element.labelOverlay.setMap(map);
                            
                            // Set industry data attribute from companyData
                            if (companyData[index] && companyData[index].industry) {
                                element.cardDiv.dataset.industry = companyData[index].industry;
                            }

                            if (element.marker) {
                                bounds.extend(element.marker.position);
                            }
                        } else {
                            // Hide marker and its overlays
                            element.marker.setMap(null);
                            element.labelOverlay.setMap(null);
                        }
                        
                        // Always hide card overlay initially
                        if (element.cardOverlay) element.cardOverlay.setMap(null);
                        if (element.cardDiv) element.cardDiv.style.display = 'none';
                    });

                    // Only fit bounds if we have visible companies
                    if (visibleCompanies.length > 0) {
                        map.fitBounds(bounds, {
                            padding: {
                                top: 50,
                                right: 50,
                                bottom: 50,
                                left: 50
                            }
                        });
                    }

                    // Hide loading overlay only if view-button was triggered
                    if (isViewButtonTriggered) {
                        const loadingOverlay = document.querySelector('.view-company-loading-overlay');
                        loadingOverlay.classList.remove('show');
                        setTimeout(() => {
                            loadingOverlay.style.display = 'none';
                        }, 300); // Match the CSS transition duration
                    }
                }
            }, 50);
        }, isViewButtonTriggered ? 2000 : 0); // Only add delay if view-button was triggered
    } else {
        // Hide all markers and overlays
        companyElements.forEach(element => {
            if (element.marker) element.marker.setMap(null);
            if (element.labelOverlay) element.labelOverlay.setMap(null);
            if (element.cardOverlay) element.cardOverlay.setMap(null);
            if (element.cardDiv) element.cardDiv.style.display = 'none';
        });
        activeCardDiv = null;

        // Hide both overlays
        const resultsContainer = document.querySelector('.results-overlay-container');
        const viewOverlayContainer = document.querySelector('.view-overlay-container');
        
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
        
        if (viewOverlayContainer) {
            viewOverlayContainer.style.display = 'none';
        }
        
        // Clear keywords when hiding
        allMatchedKeywords = [];
    }

    // Return object to access keywords if needed
    return {
        getMatchedKeywords: () => allMatchedKeywords
    };
}

                // Event listeners remain the same
                document.querySelector('.view-button').addEventListener('click', () => {
                    toggleCompanyMarkers(true);
                });

                document.querySelector('.result-button').addEventListener('click', () => {
                    toggleCompanyMarkers(true, true);
                });

                google.maps.event.addListenerOnce(map, 'idle', function() {
                        initializeSearch();
                        initializeIndustryButtons();
                });
                // Handle user location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const userLocation = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                            };

                            map.setCenter(userLocation);

                            let currentZoom = map.getZoom();
                            const targetZoom = 18;
                            const zoomIn = setInterval(() => {
                                if (currentZoom >= targetZoom) {
                                    clearInterval(zoomIn);
                                } else {
                                    currentZoom++;
                                    map.setZoom(currentZoom);
                                }
                            }, 300);

                            // New code
                            userMarker = new google.maps.marker.AdvancedMarkerElement({
                                position: userLocation,
                                map: map,
                                title: "Your Current Location",
                                content: document.createElement('img'), // Use an image element for the icon
                            });

                            // Set the icon
                            const userIcon = userMarker.content;
                            userIcon.src = "pics/gps.png";
                            userIcon.style.width = "50px";
                            userIcon.style.height = "50px";

                            const userLabelDiv = document.createElement("div");
                            userLabelDiv.innerHTML = "Current location";
                            userLabelDiv.classList.add("custom-label-current");

                            const userOverlay = new google.maps.OverlayView();
                            userOverlay.onAdd = function () {
                                const panes = this.getPanes();
                                panes.floatPane.appendChild(userLabelDiv);
                            };

                            userOverlay.draw = function () {
                                const projection = this.getProjection();
                                const position = projection.fromLatLngToDivPixel(userLocation);
                                const labelOffsetX = -55;
                                const labelOffsetY = 5;
                                userLabelDiv.style.left = position.x + labelOffsetX + "px";
                                userLabelDiv.style.top = position.y + labelOffsetY + "px";
                            };

                            userOverlay.onRemove = function () {
                                userLabelDiv.parentNode.removeChild(userLabelDiv);
                            };

                            userOverlay.setMap(map);

                            google.maps.event.addListener(map, "zoom_changed", () => {
                                const zoom = map.getZoom();
                                const newSize = Math.max(30, 60 - (zoom - 15) * 3);
                                userMarker.content.style.width = `${newSize}px`;
                                userMarker.content.style.height = `${newSize}px`;

                            });
                        },
                        () => {
                            console.log("Error: The Geolocation service failed.");
                        }
                    );
                    } else {
                        console.log("Error: Your browser doesn't support geolocation.");
                }
                // Company markers setup
                if (companyData && Array.isArray(companyData)) {
                    companyData.forEach(company => {
                        const latitude = parseFloat(company.latitude);
                        const longitude = parseFloat(company.longitude);
                        const companyName = company.company_name;
                        const companyAddress = company.company_address;
                        const contactDetails = company.company_phone;
                        const companyWebsite = company.company_email;
                        const companyLogo = company.company_logo ? `../RECRUITER/${company.company_logo}` : 'pics/default_logo.png';
                        const companyId = company.id;
                        

                        if (latitude && longitude && companyName) {
                            const companyLocation = { lat: latitude, lng: longitude };

                            // New code
                            const companyMarker = new google.maps.marker.AdvancedMarkerElement({
                                position: companyLocation,
                                map: null, // Start hidden
                                title: companyName,
                                content: document.createElement('img'), // Use an image element for the icon
                            });

                            // Set the icon
                            const companyIcon = companyMarker.content;
                            companyIcon.src = "pics/company.png";
                            companyIcon.style.width = "50px";
                            companyIcon.style.height = "50px";

                            // Create label div
                            const companyLabelDiv = document.createElement("div");
                            companyLabelDiv.innerHTML = companyName;
                            companyLabelDiv.classList.add("custom-label-internship");

                            const companyOverlay = new google.maps.OverlayView();
                            companyOverlay.onAdd = function () {
                                const panes = this.getPanes();
                                panes.floatPane.appendChild(companyLabelDiv);
                            };

                            companyOverlay.draw = function () {
                                const projection = this.getProjection();
                                const position = projection.fromLatLngToDivPixel(companyLocation);
                                const labelOffsetX = -85;
                                const labelOffsetY = 5;
                                companyLabelDiv.style.left = position.x + labelOffsetX + "px";
                                companyLabelDiv.style.top = position.y + labelOffsetY + "px";
                            };

                            companyOverlay.onRemove = function () {
                                if (companyLabelDiv.parentNode) {
                                    companyLabelDiv.parentNode.removeChild(companyLabelDiv);
                                }
                            };

                            // Create company card
                            const companyCardDiv = document.createElement("div");
                            companyCardDiv.classList.add("company-card");
                            companyCardDiv.style.display = 'none';
                            companyCardDiv.innerHTML = `
                                <img src="${companyLogo}" alt="Company Logo" class="company-logo"> 
                                <div class="company-name">${companyName}</div>
                                <div class="company-address">${companyAddress}</div>
                                <div class="company-contact">${contactDetails}</div>
                                <div class="company-website">
                                    <a href="${companyWebsite}" target="_blank" class="website-link">
                                        ${companyWebsite}
                                        <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#0000ee" class="external-link-icon">
                                        <path d="M206.78-100.78q-44.3 0-75.15-30.85-30.85-30.85-30.85-75.15v-546.44q0-44.3 30.85-75.15 30.85-30.85 75.15-30.85H480v106H206.78v546.44h546.44V-480h106v273.22q0 44.3-30.85 75.15-30.85 30.85-75.15 30.85H206.78ZM405.52-332 332-405.52l347.69-347.7H560v-106h299.22V-560h-106v-119.69L405.52-332Z"/>
                                        </svg>
                                    </a>
                                </div>
                                <div class="button-container">
                                    <button class="btn apply-btn">APPLY NOW</button>
                                    <button class="btn view-btn">VIEW MORE</button>
                                </div>
                            `;

                            // Add overlay for company card
                            const companyCardOverlay = new google.maps.OverlayView();
                            companyCardOverlay.onAdd = function () {
                                const panes = this.getPanes();
                                panes.floatPane.appendChild(companyCardDiv);
                            };

                            companyCardOverlay.draw = function () {
                                const projection = this.getProjection();
                                const position = projection.fromLatLngToDivPixel(companyLocation);
                                companyCardDiv.style.left = position.x + "px";
                                companyCardDiv.style.top = (position.y - 60) + "px";
                            };

                            companyCardOverlay.onRemove = function () {
                                if (companyCardDiv.parentNode) {
                                    companyCardDiv.parentNode.removeChild(companyCardDiv);
                                }
                            };

                            // Add click handler for marker
                            companyMarker.addListener('click', () => {
                                if (activeCardDiv === companyCardDiv) {
                                    companyCardDiv.style.display = 'none';
                                    companyCardOverlay.setMap(null);
                                    activeCardDiv = null;
                                } else {
                                    if (activeCardDiv) {
                                        activeCardDiv.style.display = 'none';
                                        companyElements.forEach(element => {
                                            if (element.cardOverlay) element.cardOverlay.setMap(null);
                                        });
                                    }

                                    companyCardDiv.style.display = 'block';
                                    companyCardOverlay.setMap(map);
                                    activeCardDiv = companyCardDiv;

                                    // Adjust map view
                                    map.setZoom(16);
                                    
                                    const scale = Math.pow(2, map.getZoom());
                                    const worldCoordinateCenter = map.getProjection().fromLatLngToPoint(companyLocation);
                                    const pixelOffset = new google.maps.Point(0, -180);

                                    const worldCoordinateNewCenter = new google.maps.Point(
                                        worldCoordinateCenter.x,
                                        worldCoordinateCenter.y + (pixelOffset.y / scale)
                                    );

                                    const newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
                                    map.panTo(newCenter);
                                }
                            });

                            

                            companyCardDiv.dataset.industry = company.industry || '';
                            companyCardDiv.dataset.matchedKeywords = ''; // Will be populated when keywords match // Will be populated when keywords match
                            // Update the click handlers for both buttons
                            // Add click handler for view more button
                           // Add click handler for view more button
                            // Add click handler for view more button
                            const viewMoreBtn = companyCardDiv.querySelector('.view-btn');
                            viewMoreBtn.addEventListener('click', (event) => {
                                event.stopPropagation();
                                const industry = companyCardDiv.dataset.industry;
                                let redirectUrl = `COMPANYCARDINFO-VIEW.php?id=${companyId}`;
                                
                                // Check if this company was displayed via the view-button (Explore All)
                                const wasTriggeredByViewButton = document.querySelector('.view-overlay-container').style.display === 'block';
                                
                                // Only add industry/keywords if NOT triggered by Explore All button
                                if (!wasTriggeredByViewButton) {
                                    // Use ALL matched keywords from the UI instead of just this company's keywords
                                    const allMatchedKeywords = document.body.dataset.allMatchedKeywords;
                                    if (allMatchedKeywords && allMatchedKeywords.length > 0) {
                                        redirectUrl += `&texts=${encodeURIComponent(allMatchedKeywords)}`;
                                    } else if (industry) {
                                        redirectUrl += `&industry=${encodeURIComponent(industry)}`;
                                    }
                                }
                                
                                window.location.href = redirectUrl;
                            });

                            // Add click handler for apply now button
                            const applyNowBtn = companyCardDiv.querySelector('.apply-btn');
                            applyNowBtn.addEventListener('click', (event) => {
                                event.stopPropagation();
                                const industry = companyCardDiv.dataset.industry;
                                let redirectUrl = `COMPANYCARDINFO-APPLY.php?id=${companyId}`;
                                
                                // Check if this company was displayed via the view-button (Explore All)
                                const wasTriggeredByViewButton = document.querySelector('.view-overlay-container').style.display === 'block';
                                
                                // Only add industry/keywords if NOT triggered by Explore All button
                                if (!wasTriggeredByViewButton) {
                                    // Use ALL matched keywords from the UI instead of just this company's keywords
                                    const allMatchedKeywords = document.body.dataset.allMatchedKeywords;
                                    if (allMatchedKeywords && allMatchedKeywords.length > 0) {
                                        redirectUrl += `&texts=${encodeURIComponent(allMatchedKeywords)}`;
                                    } else if (industry) {
                                        redirectUrl += `&industry=${encodeURIComponent(industry)}`;
                                    }
                                }
                                
                                window.location.href = redirectUrl;
                            });

                            // Store elements
                            companyElements.push({
                                marker: companyMarker,
                                labelOverlay: companyOverlay,
                                cardOverlay: companyCardOverlay,
                                cardDiv: companyCardDiv
                            });
                        } else {
                            console.log("Error: Invalid coordinates or company data.");
                        }
                    });
                    } else {
                        console.log("Error: No company data available.");
                }
                map.addListener('click', () => {
                    if (activeCardDiv) {
                        activeCardDiv.style.display = 'none';
                        activeCardDiv = null;
                    }
                });
                }
                function loadGoogleMapsAPI() {
                const script = document.createElement("script");
                script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyDtbgRcgki0qgsq4Kt6c0JlhhUhEUH7PXQ&callback=initMap&loading=async&libraries=marker`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
            // Load the Google Maps API and initialize the map
            loadGoogleMapsAPI();
    </script>
    <!-- MAP SCRIPT -->


</body>
</html>