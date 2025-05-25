// NAVIGATION SCRIPT
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navbar = document.querySelector('.navbar');
    const navLinks = document.querySelector('.nav-links');
    const authButtons = document.querySelector('.auth-buttons');

    // Toggle navbar visibility
    menuToggle.addEventListener('click', function() {
        navbar.classList.toggle('active');

        if (navbar.classList.contains('active')) {
            navLinks.style.maxHeight = navLinks.scrollHeight + 'px';
            authButtons.style.maxHeight = authButtons.scrollHeight + 'px';
        } else {
            navLinks.style.maxHeight = '0';
            authButtons.style.maxHeight = '0';
        }
    });
});