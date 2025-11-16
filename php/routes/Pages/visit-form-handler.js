document.addEventListener('DOMContentLoaded', function () {
  // Ensure this form ID matches the ID of your visitation form
  const visitForm = document.getElementById('visitationForm');
  if (!visitForm) {
    // console.error('Visitation form with ID "visitationForm" not found.');
    return;
  }

  visitForm.addEventListener('submit', async function (event) {
    event.preventDefault(); // Prevent the default browser submission

    const submitButton = visitForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = 'Submitting...';

    const formData = new FormData(visitForm);

    try {
      const response = await fetch('visitation_submit.php', {
        method: 'POST',
        body: formData,
        // No 'Content-Type' header needed; browser sets it for FormData
      });

      const result = await response.json();

      if (result.success && result.redirectUrl) {
        // On success, redirect to the URL provided by the server
        window.location.href = result.redirectUrl;
      } else {
        // Use the notification script to show an error
        showNotification(result.message || 'An unknown error occurred.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
      }
    } catch (error) {
      showNotification('A network error occurred. Please try again.', 'error');
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
    }
  });
});