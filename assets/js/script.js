document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Confirm before delete actions
  document.querySelectorAll(".confirm-delete").forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Are you sure you want to delete this?")) {
        e.preventDefault();
      }
    });
  });

  // Auto-refresh dashboard every 5 minutes
  if (document.querySelector(".dashboard-auto-refresh")) {
    setTimeout(function () {
      window.location.reload();
    }, 300000); // 5 minutes
  }
});
