
// FOR DELETE CONFIRMATION REPORT
function confirmDelete(reportId) {
Swal.fire({
  title: 'Are you sure to delete it?',
  icon: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#4CAF50',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Yes, delete it!'
}).then((result) => {
  if (result.isConfirmed) {
// If confirmed, submit the form
    const form = document.createElement('form');
    form.method = 'POST';
form.innerHTML = `<input type="hidden" name="report_id" value="${reportId}">
                  <input type="hidden" name="delete_report" value="1">`;
document.body.appendChild(form);
form.submit();
}
});
}


// FOR MODAL in VIEW REPORTS

var modal = document.getElementById("reportModal");
var closeBtn = document.querySelector(".close-btn");

function openReportModal(filename) {
var viewer = document.getElementById("reportViewer");
viewer.src = "view_report.php?file=" + filename;
modal.style.display = "block";
}

// Close button event listener
closeBtn.addEventListener('click', function() {
modal.style.display = "none";
});

// Click outside modal to close
window.addEventListener('click', function(event) {
if (event.target == modal) {
    modal.style.display = "none";
}
});