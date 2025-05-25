
function openModal(filePath) {
    var modal = document.getElementById("documentModal");
    var iframe = document.getElementById("documentIframe");
    
iframe.src = filePath; 
modal.style.display = "block";  
}

function closeModal() {
    var modal = document.getElementById("documentModal");
    var iframe = document.getElementById("documentIframe");

iframe.src = ""; 
modal.style.display = "none";  
}

function confirmDelete(documentType) {
    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to delete this ${documentType}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4CAF50', // Confirm button color
        cancelButtonColor: '#d33', // Cancel button color (default red)
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
                window.location.href = `deleteUpload.php?document_type=${encodeURIComponent(documentType)}`;
        }
    });
}
