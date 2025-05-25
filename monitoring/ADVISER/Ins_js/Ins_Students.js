// FOR SEARCH BAR
function searchFunction() {
  var input, filter, table, tr, td, i, j, txtValue;
  input = document.getElementById("searchInput");
  filter = input.value.toUpperCase();
  table = document.getElementById("studentTable");
  tr = table.getElementsByTagName("tr");

  for (i = 1; i < tr.length; i++) {
    tr[i].style.display = "none"; // Hide the row initially
    td = tr[i].getElementsByTagName("td");
    for (j = 0; j < td.length; j++) { // Loop through all columns
      if (td[j]) {
        txtValue = td[j].textContent || td[j].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          tr[i].style.display = ""; // Show the row if any column matches
          break; // If a match is found, no need to check other columns in this row
        }
      }
    }
  }
}

// FOR PAGINATION
// Variables for pagination
let currentPage = 1;
const rowsPerPage = 10;

// Function to display paginated data
function displayStudentsTable(page) {
    const tableBody = document.getElementById("studentTableBody");
    const rows = tableBody.querySelectorAll("tr");
    
    // Get only visible rows (not filtered out)
    const visibleRows = Array.from(rows).filter(row => {
        // Check if row matches current course filter
        const activeButton = document.querySelector('.course-btn.active');
        if (!activeButton) return true;
        
        const courseCell = row.cells[4];
        const yearCell = row.cells[5];
        const studentCourse = courseCell.textContent.trim();
        const studentYear = yearCell.textContent.trim();
        
        return studentCourse === activeButton.dataset.course && 
               studentYear === activeButton.dataset.year;
    });

    // Calculate total pages based on visible rows
    const totalPages = Math.ceil(visibleRows.length / rowsPerPage);

    // Ensure the current page is within bounds
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    currentPage = page;

    // Hide all rows first
    rows.forEach(row => row.style.display = "none");

    // Show only the relevant rows for the current page
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    visibleRows.slice(start, end).forEach(row => {
        row.style.display = "";
    });

    // Update pagination controls
    setupPagination(totalPages, page);
}

// Function to set up pagination controls
function setupPagination(totalPages, currentPage) {
    const paginationDiv = document.getElementById("paginationControls");
    paginationDiv.innerHTML = "";

    if (totalPages <= 1) {
        return; // Don't show pagination if there's only one page
    }

    // Create "Previous" button
    const prevButton = document.createElement("button");
    prevButton.innerText = "Previous";
    prevButton.classList.add("page-btn");
    prevButton.disabled = currentPage === 1;
    prevButton.onclick = () => {
        if (currentPage > 1) {
            displayStudentsTable(currentPage - 1);
        }
    };
    paginationDiv.appendChild(prevButton);

    // Add page numbers
    for (let i = 1; i <= totalPages; i++) {
        const pageButton = document.createElement("button");
        pageButton.innerText = i;
        pageButton.classList.add("page-btn");
        if (i === currentPage) {
            pageButton.classList.add("active");
        }
        pageButton.onclick = () => displayStudentsTable(i);
        paginationDiv.appendChild(pageButton);
    }

    // Create "Next" button
    const nextButton = document.createElement("button");
    nextButton.innerText = "Next";
    nextButton.classList.add("page-btn");
    nextButton.disabled = currentPage === totalPages;
    nextButton.onclick = () => {
        if (currentPage < totalPages) {
            displayStudentsTable(currentPage + 1);
        }
    };
    paginationDiv.appendChild(nextButton);
}

// Modify the existing filterStudents function
function filterStudents(course, year) {
    const tbody = document.getElementById('studentTableBody');
    const rows = tbody.getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const courseCell = row.cells[4];
        const yearCell = row.cells[5];
        
        if (courseCell && yearCell) {
            const studentCourse = courseCell.textContent.trim();
            const studentYear = yearCell.textContent.trim();
            
            if (studentCourse === course && studentYear === year) {
                row.style.display = ''; // Show the row
            } else {
                row.style.display = 'none'; // Hide the row
            }
        }
    }
    
    // Reset to first page and update pagination
    currentPage = 1;
    displayStudentsTable(currentPage);
}

// Modify the search function
function searchFunction() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const tbody = document.getElementById("studentTableBody");
    const rows = tbody.getElementsByTagName("tr");
    const activeButton = document.querySelector('.course-btn.active');
    
    for (let row of rows) {
        const cells = row.getElementsByTagName("td");
        let found = false;
        
        // Only search within the current course/year filter
        if (activeButton) {
            const courseCell = row.cells[4];
            const yearCell = row.cells[5];
            const studentCourse = courseCell.textContent.trim();
            const studentYear = yearCell.textContent.trim();
            
            if (studentCourse === activeButton.dataset.course && 
                studentYear === activeButton.dataset.year) {
                for (let cell of cells) {
                    const text = cell.textContent || cell.innerText;
                    if (text.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
        }
        row.style.display = found ? "" : "none";
    }
    
    // Reset to first page and update pagination
    currentPage = 1;
    displayStudentsTable(currentPage);
}

// Event listeners for course buttons
document.addEventListener('DOMContentLoaded', function() {
    const courseButtons = document.querySelectorAll('.course-btn');
    
    // Activate first button by default
    if (courseButtons.length > 0) {
        courseButtons[0].classList.add('active');
        filterStudents(courseButtons[0].dataset.course, courseButtons[0].dataset.year);
    }
    
    courseButtons.forEach(button => {
        button.addEventListener('click', function() {
            courseButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            filterStudents(this.dataset.course, this.dataset.year);
        });
    });
    
    // Initialize pagination
    displayStudentsTable(currentPage);
});

