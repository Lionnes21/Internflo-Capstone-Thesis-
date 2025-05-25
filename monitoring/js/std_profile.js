  // Handle menu arrow toggle
  let arrow = document.querySelectorAll(".arrow");
for (let i = 0; i < arrow.length; i++) {
    arrow[i].addEventListener("click", (e) => {
        let arrowParent = e.target.parentElement.parentElement; // Selecting the main parent of arrow
        arrowParent.classList.toggle("showMenu");
    });
}

// Handle sidebar toggle
let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".bx-menu");

// Check if the sidebar state is stored in localStorage
if (localStorage.getItem("sidebarState") === "open") {
    sidebar.classList.remove("close"); // Ensure the sidebar is open
} else {
    sidebar.classList.add("close"); // Ensure the sidebar is closed by default
}

// Toggle the sidebar state when clicking the menu button
sidebarBtn.addEventListener("click", () => {
    sidebar.classList.toggle("close");

    // Store the sidebar state in localStorage
    if (sidebar.classList.contains("close")) {
        localStorage.setItem("sidebarState", "closed");
    } else {
        localStorage.setItem("sidebarState", "open");
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const page1Link = document.getElementById('page1');
    const page2Link = document.getElementById('page2');
    const page3Link = document.getElementById('page3');

    let currentPage = 1;

    function showPage(pageNumber) {
        document.getElementById('page1Content').style.display = (pageNumber === 1) ? 'block' : 'none';
        document.getElementById('page2Content').style.display = (pageNumber === 2) ? 'block' : 'none';
        document.getElementById('page3Content').style.display = (pageNumber === 3) ? 'block' : 'none';
        currentPage = pageNumber;

        prevPage.classList.toggle('disabled', currentPage === 1);
        nextPage.classList.toggle('disabled', currentPage === 3);
    }

    prevPage.addEventListener('click', function() {
        if (currentPage > 1) {
            showPage(currentPage - 1);
        }
    });

    nextPage.addEventListener('click', function() {
        if (currentPage < 3) {
            showPage(currentPage + 1);
        }
    });

    page1Link.addEventListener('click', function() {
        showPage(1);
    });

    page2Link.addEventListener('click', function() {
        showPage(2);
    });

    page3Link.addEventListener('click', function() {
        showPage(3);
    });

    // Initialize to show the first page
    showPage(1);
});
