// Handle sidebar toggle
let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".bx-menu");

// Check if the sidebar state is stored in localStorage
if (localStorage.getItem("sidebarState") === "open") {
    sidebar.classList.remove("close"); // Sidebar open
    document.body.classList.remove("sidebar-closed");
} else {
    sidebar.classList.add("close"); // Sidebar closed
    document.body.classList.add("sidebar-closed");
}

// Toggle the sidebar state when clicking the menu button
sidebarBtn.addEventListener("click", () => {
    sidebar.classList.toggle("close");

    if (sidebar.classList.contains("close")) {
        localStorage.setItem("sidebarState", "closed");
        document.body.classList.add("sidebar-closed");
    } else {
        localStorage.setItem("sidebarState", "open");
        document.body.classList.remove("sidebar-closed");
    }
});

