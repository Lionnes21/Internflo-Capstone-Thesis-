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
    
                    $filteredText = preg_replace('/\s+/', ' ', trim($filteredText)); // Clean whitespace
    
                    $stmt = $pdo->query('SELECT * FROM approvedrecruiters');
                    $allCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
                    $matchingCompanies = [];
                    $pdfContent = strtolower($filteredText);
    
                    foreach ($allCompanies as $company) {
                        if (!empty($company['Keywords'])) {
                            $keywords = explode(',', strtolower($company['Keywords']));
                            foreach ($keywords as $keyword) {
                                $keyword = trim($keyword);
                                if (!empty($keyword) && strpos($pdfContent, $keyword) !== false) {
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
                        'pdfText' => $filteredText, // Include scanned text here
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
            $stmt = $pdo->query('SELECT * FROM approvedrecruiters');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="map.css">
    <title>Map with Sidebar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
</head>
<body>



<div class="main-container">
    <!-- Side Menu 1 (Initial narrow state) -->
    <div class="side-menu" id="firstMenu">
        <div class="side-menu-header" onclick="toggleSecondMenu()">
            <img src="pics/ucclogo2.png" alt="UCC Logo">
        </div>
        <hr class="orange">
        <div class="side-menu-content">
            <div class="menu-item-container">
                <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#666666">
                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Zm120 480h200q17 0 28.5-11.5T560-320q0-17-11.5-28.5T520-360H320q-17 0-28.5 11.5T280-320q0 17 11.5 28.5T320-280Zm0-160h320q17 0 28.5-11.5T680-480q0-17-11.5-28.5T640-520H320q-17 0-28.5 11.5T280-480q0 17 11.5 28.5T320-440Zm0-160h320q17 0 28.5-11.5T680-640q0-17-11.5-28.5T640-680H320q-17 0-28.5 11.5T280-640q0 17 11.5 28.5T320-600Z"/>
                </svg>
                <div class="menu-item">Upload</div>
            </div>
        </div>
        <div class="side-menu-content">
            <div class="menu-item-container">
                <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#666666">
                    <path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm-40-82v-78q-33 0-56.5-23.5T360-320v-40L168-552q-3 18-5.5 36t-2.5 36q0 121 79.5 212T440-162Zm276-102q20-22 36-47.5t26.5-53q10.5-27.5 16-56.5t5.5-59q0-98-54.5-179T600-776v16q0 33-23.5 56.5T520-680h-80v80q0 17-11.5 28.5T400-560h-80v80h240q17 0 28.5 11.5T600-440v120h40q26 0 47 15.5t29 40.5Z"/>
                </svg>
                <div class="menu-item">Company</div>
            </div>
        </div>
        <div class="side-menu-content">
            <div class="menu-item-container">
                <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#666666">
                    <path d="M440-440H240q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h200v-200q0-17 11.5-28.5T480-760q17 0 28.5 11.5T520-720v200h200q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H520v200q0 17-11.5 28.5T480-200q-17 0-28.5-11.5T440-240v-200Z"/>
                </svg>
                <div class="menu-item">Build</div>
            </div>
        </div>
        <br><br><br><br><br><br><br><br><br>
        <hr class="bottomhr">
        <div class="side-menu-content">
            <div class="menu-item-bottom">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h240q17 0 28.5 11.5T480-800q0 17-11.5 28.5T440-760H200v560h240q17 0 28.5 11.5T480-160q0 17-11.5 28.5T440-120H200Zm487-320H400q-17 0-28.5-11.5T360-480q0-17 11.5-28.5T400-520h287l-75-75q-11-11-11-27t11-28q11-12 28-12.5t29 11.5l143 143q12 12 12 28t-12 28L669-309q-12 12-28.5 11.5T612-310q-11-12-10.5-28.5T613-366l74-74Z"/>
                </svg>
                <div class="menu-item">Home</div>
            </div>
        </div>
    </div>

    <!-- Side Menu 2 (Expanded state, initially hidden) -->
    <div class="side-menu" id="secondMenu" style="width: 300px; display: none;">
        <div class="side-menu-header" onclick="toggleSecondMenu()">
            <img src="pics/ucclogonav-t.png" alt="UCC Logo">
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

    <div id="loadingOverlay" style="display: none;">
        <div class="loading-container">
            <span class="loader"></span>
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
        <p>SCANNED PDF TEXT:</p>
        <p id="scannedText">Scanned Successfully</p> <!-- This will be updated dynamically -->
        <button class="result-button">Close</button>
    </div>
</div>






    <div id="main-content">
        <div class="search-container">
            <div class="search-box-container">
                <input type="text" class="search-input-bar" placeholder="Search Google Maps">
                <div class="search-icon-button">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666">
                        <path d="M380-320q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l224 224q11 11 11 28t-11 28q-11 11-28 11t-28-11L532-372q-30 24-69 38t-83 14Zm0-80q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                </div>
            </div>
            <div class="institutions-button">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M72.59-154.5v-588.57q0-11.43 4.83-20.86 4.84-9.44 14.28-16.16l161.91-117.37q12.19-8.71 26.39-8.71 14.2 0 26.39 8.71L468.3-780.09q9.44 6.72 14.28 16.16 4.83 9.43 4.83 20.86v59.48h354.5q19.16 0 32.33 13.18 13.17 13.17 13.17 32.32v483.59q0 19.15-13.17 32.33Q861.07-109 841.91-109H118.09q-19.16 0-32.33-13.17-13.17-13.18-13.17-32.33ZM160-196.41h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm0-160h80v-80h-80v80Zm160 0h80v-80h-80v80Zm0 480h480v-400H320v400Zm280-320h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5Zm0 160h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5Zm-120-120q0 17-11.5 28.5t-28.5 11.5q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5q17 0 28.5 11.5t11.5 28.5Zm-40 200q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5Z"/></svg>
                <span>Institutions</span>
            </div>
            <div class="institutions-button">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M163.59-109q-37.79 0-64.39-26.61Q72.59-162.22 72.59-200v-552.59q0-37.78 26.61-64.39 26.6-26.61 64.39-26.61h232.82q37.79 0 64.39 26.61 26.61 26.61 26.61 64.39v69h309q37.79 0 64.39 26.61 26.61 26.61 26.61 64.39V-200q0 37.78-26.61 64.39Q834.2-109 796.41-109H163.59ZM160-196.41h240v-80H160v80Zm0-160h240v-80H160v80Zm0-160h240v-80H160v80Zm0-160h240v-80H160v80Zm320 480h320v-400H480v400Zm120-240q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80Zm0 160q-17 0-28.5-11.5t-11.5-28.5q0-17 11.5-28.5t28.5-11.5h80q17 0 28.5 11.5t11.5 28.5q0 17-11.5 28.5t-28.5 11.5h-80Z"/></svg>
                <span>Goverment</span>
            </div>
            <div class="institutions-button">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#666666"><path d="M925.26-752.59v545.18q0 37.78-26.61 64.39t-64.39 26.61h-109q-18.52 0-31.05-12.53-12.54-12.54-12.54-31.06t12.54-31.06q12.53-12.53 31.05-12.53h112.83v-552.82H485.26v15.21q-2.87 12.94-15.21 21.69-12.33 8.75-28.38 8.75-18.52 0-31.05-12.46-12.53-12.47-12.53-30.89v-16.74q0-31.76 22.8-52.25t54.52-20.49h358.85q37.78 0 64.39 26.61t26.61 64.39Zm-891 277.42q0-22.75 10.2-42.08 10.19-19.34 28.34-32.01l192.83-137.5q12.44-8.96 25.59-12.94 13.15-3.97 26.87-3.97 13.91 0 26.95 3.94 13.03 3.95 25.5 12.97l192.59 137.5q18.14 12.58 28.34 32.02 10.2 19.44 10.2 42.07v282.82q0 31.33-22.3 53.63-22.31 22.31-53.63 22.31h-91.72q-31.32 0-53.63-22.31-22.3-22.3-22.3-53.63v-131.24h-80v131.24q0 31.33-22.31 53.63-22.3 22.31-53.63 22.31H110.2q-31.33 0-53.63-22.31-22.31-22.3-22.31-53.63v-282.82Zm87.41-2.86v274.44h76.42v-124.06q0-31.33 22.3-53.63 22.31-22.31 53.63-22.31h88.13q31.33 0 53.63 22.31 22.31 22.3 22.31 53.63v124.06h76.17v-274.44L318.09-617.22 121.67-478.03Zm522.46-110.51ZM438.09-203.59v-200h-240v200-200h240v200ZM700.72-600h41.91q7.76 0 13.4-5.64 5.64-5.64 5.64-13.4v-41.92q0-7.76-5.64-13.4-5.64-5.64-13.4-5.64h-41.91q-7.76 0-13.4 5.64-5.65 5.64-5.65 13.4v41.92q0 7.76 5.65 13.4 5.64 5.64 13.4 5.64Zm0 160h41.91q7.76 0 13.4-5.64 5.64-5.64 5.64-13.4v-41.92q0-7.76-5.64-13.4-5.64-5.64-13.4-5.64h-41.91q-7.76 0-13.4 5.64-5.65 5.64-5.65 13.4v41.92q0 7.76 5.65 13.4 5.64 5.64 13.4 5.64Zm0 160h41.91q7.76 0 13.4-5.64 5.64-5.64 5.64-13.4v-41.92q0-7.76-5.64-13.4-5.64-5.64-13.4-5.64h-41.91q-7.76 0-13.4 5.64-5.65 5.64-5.65 13.4v41.92q0 7.76 5.65 13.4 5.64 5.64 13.4 5.64Z"/></svg>
                <span>Local</span>
            </div>
        </div>
        <div id="map"></div>
    </div>
</div>



    <!-- LOADING ANIMATION SCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const uploadForm = document.getElementById('uploadForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const successOverlay = document.getElementById('successOverlay');
            const scannedTextElement = document.getElementById('scannedText');
            const loadingText = document.getElementById('loadingText');

            uploadForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(uploadForm);

                // Show loading animation
                loadingOverlay.style.display = 'flex';
                loadingText.textContent = 'Analyzing your Curriculum Vitae...';

                fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Failed to upload data');
                        }
                        return response.json(); // Parse JSON response
                    })
                    .then(data => {
                        // Simulate delay to show loading animation
                        setTimeout(() => {
                            loadingOverlay.style.display = 'none'; // Hide loading animation

                            if (data.success) {
                                // Update paragraph with scanned text
                                scannedTextElement.textContent = data.pdfText || 'No text extracted.';
                                successOverlay.style.display = 'flex'; // Show success overlay
                            } else {
                                alert('Error: ' + data.error);
                            }
                        }, 2000); // Adjust delay as needed (currently 2 seconds)
                    })
                    .catch(error => {
                        loadingOverlay.style.display = 'none'; // Hide loading animation
                        console.error('Error:', error);
                        alert('There was an issue uploading your file. Please try again.');
                    });
            });

            // Add click handler for "Close" button
            document.querySelector('.result-button').addEventListener('click', function () {
                successOverlay.style.display = 'none'; // Hide success overlay
            });
        });



    </script>

    <!-- PDF SCANNER SCRIPT -->
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



    <!-- SIDE MENU SCRIPT -->
    <script>
        function toggleSecondMenu() {
            const firstMenu = document.getElementById('firstMenu');
            const secondMenu = document.getElementById('secondMenu');
            const mainContent = document.getElementById('main-content');

            if (firstMenu.style.display === 'none') {
                firstMenu.style.display = 'block';
                secondMenu.style.display = 'none';
                mainContent.classList.remove('expanded');
            } else {
                firstMenu.style.display = 'none';
                secondMenu.style.display = 'block';
                mainContent.classList.add('expanded');
            }
        }
    </script>

    

    <!-- MAP SCRIPT -->
    <script>
        function initMap() {
        // Default center of the map
        const defaultLocation = { lat: 14.6896, lng: 121.0881 };
        
        // Create a new map centered on the default location
        const map = new google.maps.Map(document.getElementById("map"), {
            zoom: 15,
            center: defaultLocation,
            mapTypeControl: false,
            fullscreenControl: false,
            streetViewControl: false,
            styles: [
                {
                    featureType: "poi",
                    stylers: [{ visibility: "off" }],
                },
            ],
        });

        // Arrays to store all company markers, overlays, and cards
        const companyElements = [];
        let userMarker;
        let activeCardDiv = null;

        // Function to toggle company markers visibility with zoom animation
        function toggleCompanyMarkers(show, showMatching = false) {
            if (show) {
        // Calculate bounds to fit all markers
        const bounds = new google.maps.LatLngBounds();
        
        // Add user location to bounds if it exists
        if (userMarker) {
            bounds.extend(userMarker.getPosition());
        }
        
        // Filter companies based on matching keywords if showMatching is true
        const visibleCompanies = showMatching 
            ? companyElements.filter((element, index) => 
                matchingCompanies.some(match => match.id === companyData[index].id))
            : companyElements;

        // Add alert for keyword matching results
        if (showMatching) {
                    if (matchingCompanies.length > 0) {
                        const uploadDate = new Date(pdfUploadTime);
                        const formattedDate = uploadDate.toLocaleString();
                        
                        let alertMessage = `Based on your resume uploaded at ${formattedDate}:\n\n`;
                        alertMessage += `Found ${matchingCompanies.length} matching companies:\n`;
                        
                        matchingCompanies.forEach(company => {
                            alertMessage += `\n- ${company.company_name} (matched keyword: ${company.matched_keyword})`;
                        });
                        
                        alert(alertMessage);
                    } else {
                        if (pdfUploadTime) {
                            const uploadDate = new Date(pdfUploadTime);
                            alert(`No companies found matching keywords from your resume uploaded at ${uploadDate.toLocaleString()}.\nShowing all companies instead.`);
                        } else {
                            alert('No resume found in the system. Please upload your resume first.');
                        }
                        // Show all companies if no matches found
                        visibleCompanies = companyElements;
                    }
                }
        
        // Add filtered company locations to bounds
        visibleCompanies.forEach(element => {
            if (element.marker) {
                bounds.extend(element.marker.getPosition());
            }
        });

                // Start zoom out animation
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
                        
                        // After zoom animation, fit bounds with padding
                        map.fitBounds(bounds, {
                            padding: {
                                top: 50,
                                right: 50,
                                bottom: 50,
                                left: 50
                            }
                        });

                        // Show only matching markers
                        companyElements.forEach((element, index) => {
                            const shouldShow = !showMatching || 
                                matchingCompanies.some(match => match.id === companyData[index].id);
                            
                            if (shouldShow) {
                                if (element.marker) element.marker.setMap(map);
                                if (element.labelOverlay) element.labelOverlay.setMap(map);
                                if (element.cardOverlay) element.cardOverlay.setMap(map);
                            } else {
                                if (element.marker) element.marker.setMap(null);
                                if (element.labelOverlay) element.labelOverlay.setMap(null);
                                if (element.cardOverlay) element.cardOverlay.setMap(null);
                            }
                            if (element.cardDiv) element.cardDiv.style.display = 'none';
                        });
                    }
                }, 50);
            } else {
                // Hide all markers
                companyElements.forEach(element => {
                    if (element.marker) element.marker.setMap(null);
                    if (element.labelOverlay) element.labelOverlay.setMap(null);
                    if (element.cardOverlay) element.cardOverlay.setMap(null);
                    if (element.cardDiv) element.cardDiv.style.display = 'none';
                });
            }
            activeCardDiv = null;
        }


        // Add click event listener to the "Explore All" button
        document.querySelector('.view-button').addEventListener('click', () => {
            toggleCompanyMarkers(true);
        });

        // Add click event listener to the "See Result" button
        document.querySelector('.result-button').addEventListener('click', () => {
            toggleCompanyMarkers(true, true); // true for showing matching companies
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

                    userMarker = new google.maps.Marker({
                        position: userLocation,
                        map: map,
                        title: "Your Current Location",
                        icon: {
                            url: "pics/gps.png",
                            scaledSize: new google.maps.Size(50, 50),
                        },
                    });

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
                        userMarker.setIcon({
                            url: "pics/gps.png",
                            scaledSize: new google.maps.Size(newSize, newSize),
                        });
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

                    const companyMarker = new google.maps.Marker({
                        position: companyLocation,
                        map: null, // Start hidden
                        title: companyName,
                        icon: {
                            url: "pics/company.png",
                            scaledSize: new google.maps.Size(50, 50),
                        },
                    });

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
                        companyLabelDiv.parentNode.removeChild(companyLabelDiv);
                    };

                    companyOverlay.setMap(null); // Start hidden

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
                            <svg xmlns="http://www.w3.org/2000/svg" height="16px" viewBox="0 -960 960 960" width="16px" fill="#002B7F" class="external-link-icon">
                            <path d="M206.78-100.78q-44.3 0-75.15-30.85-30.85-30.85-30.85-75.15v-546.44q0-44.3 30.85-75.15 30.85-30.85 75.15-30.85H480v106H206.78v546.44h546.44V-480h106v273.22q0 44.3-30.85 75.15-30.85 30.85-75.15 30.85H206.78ZM405.52-332 332-405.52l347.69-347.7H560v-106h299.22V-560h-106v-119.69L405.52-332Z"/>
                            </svg>
                        </a>
                        </div>
                        <div class="button-container">
                            <button class="btn apply-btn">APPLY NOW</button>
                            <button class="btn view-btn">VIEW MORE</button>
                        </div>
                    `;

                    const viewMoreBtn = companyCardDiv.querySelector('.view-btn');
                    viewMoreBtn.addEventListener('click', (event) => {
                        event.stopPropagation();
                        window.location.href = `studentapply.php?company_id=${companyId}`;
                    });

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
                        companyCardDiv.parentNode.removeChild(companyCardDiv);
                    };

                    companyCardOverlay.setMap(null); // Start hidden

                    // Store all elements for this company
                    companyElements.push({
                        marker: companyMarker,
                        labelOverlay: companyOverlay,
                        cardOverlay: companyCardOverlay,
                        cardDiv: companyCardDiv
                    });

                    companyMarker.addListener('click', () => {
                        if (activeCardDiv === companyCardDiv) {
                            companyCardDiv.style.display = 'none';
                            activeCardDiv = null;
                        } else {
                            if (activeCardDiv) {
                                activeCardDiv.style.display = 'none';
                            }

                            companyCardDiv.style.display = 'block';
                            activeCardDiv = companyCardDiv;

                            map.setZoom(16);

                            const scale = Math.pow(2, map.getZoom());
                            const worldCoordinateCenter = map.getProjection().fromLatLngToPoint(companyLocation);
                            const pixelOffset = new google.maps.Point(0, (-210 / scale) || 0);

                            const worldCoordinateNewCenter = new google.maps.Point(
                                worldCoordinateCenter.x,
                                worldCoordinateCenter.y + pixelOffset.y
                            );

                            const newCenter = map.getProjection().fromPointToLatLng(worldCoordinateNewCenter);
                            map.panTo(newCenter);
                        }
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
            script.src = `https://maps.googleapis.com/maps/api/js?key=#&callback=initMap&loading=async`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Load the Google Maps API and initialize the map
        loadGoogleMapsAPI();
    </script>



</body>
</html>