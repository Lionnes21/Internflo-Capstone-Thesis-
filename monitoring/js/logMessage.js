// Function to confirm logout
function confirmLogout() {
    Swal.fire({
        title: 'Are you sure you want to logout?',
        text: "You will be redirected to the login page.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4CAF50',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Logout',
        cancelButtonText: 'Stay'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to the logout script
            window.location.href = 'logout.php'; // Update this to your logout script
        }
    });
}