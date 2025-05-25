document.getElementById('resumeForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('save_resume.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert('Resume created successfully!');
        // Optionally, redirect to the resume template
        window.location.href = 'resume_template.php';
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
