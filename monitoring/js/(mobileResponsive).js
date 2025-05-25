document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('#sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('mobile-show');
        overlay.classList.toggle('show');
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-show');
        overlay.classList.remove('show');
    });
});