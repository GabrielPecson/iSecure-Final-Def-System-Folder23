window.addEventListener('load', () => {
  const bluePanel = document.getElementById('blue-panel');
  const logo = document.getElementById('logo');
  const whitePanel = document.getElementById('white-panel');

  // Fade in logo
  setTimeout(() => { logo.classList.add('logo-fade-in'); }, 1000);

  // Slide blue panel left + shrink logo
  setTimeout(() => {
    bluePanel.classList.add('slide-left');
    logo.classList.add('logo-shrink');
  }, 1700);

  // Reveal white panel
  setTimeout(() => {
    whitePanel.classList.add('white-reveal');
  }, 600);

  // Move logo up
  setTimeout(() => {
    logo.classList.add('logo-up');
  }, 1200);
});

// Password toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const togglePassword = document.getElementById('togglePassword');
  const password = document.getElementById('password');

  if (togglePassword && password) {
    togglePassword.addEventListener('click', function () {
      const type = password.type === 'password' ? 'text' : 'password';
      password.type = type;
      this.innerHTML = type === 'password' ?
        `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>` :
        `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        </svg>`;
    });
  }
});

// Custom Modal functionality
document.addEventListener('DOMContentLoaded', function() {
  const forgotPasswordLink = document.getElementById('forgotPasswordLink');
  const forgotPasswordModal = document.getElementById('forgotPasswordModal');
  const closeModal = document.getElementById('closeModal');
  const modalOverlay = document.getElementById('modalOverlay');

  if (forgotPasswordLink && forgotPasswordModal) {
    forgotPasswordLink.addEventListener('click', function (e) {
      e.preventDefault();
      forgotPasswordModal.style.display = 'block';
    });
  }

  if (closeModal) {
    closeModal.addEventListener('click', function () {
      forgotPasswordModal.style.display = 'none';
    });
  }

  if (modalOverlay) {
    modalOverlay.addEventListener('click', function () {
      forgotPasswordModal.style.display = 'none';
    });
  }

  // Error modal functionality
  const loginErrorModal = document.getElementById('loginErrorModal');
  const closeErrorModal = document.getElementById('closeErrorModal');
  const closeErrorBtn = document.getElementById('closeErrorBtn');
  const errorModalOverlay = document.getElementById('errorModalOverlay');

  if (closeErrorModal) {
    closeErrorModal.addEventListener('click', function () {
      loginErrorModal.style.display = 'none';
    });
  }

  if (closeErrorBtn) {
    closeErrorBtn.addEventListener('click', function () {
      loginErrorModal.style.display = 'none';
    });
  }

  if (errorModalOverlay) {
    errorModalOverlay.addEventListener('click', function () {
      loginErrorModal.style.display = 'none';
    });
  }

  // Handle login form submission with AJAX
  const loginForm = document.querySelector('.login-form');
  const loaderOverlay = document.getElementById('loader-overlay');

  if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
      e.preventDefault(); // Prevent default form submission

      const formData = new FormData(loginForm);
      const loaderText = document.getElementById('loader-text');

      // Show loader with initial text
      if (loaderOverlay) {
        loaderOverlay.style.display = 'flex';
      }
      if (loaderText) {
        loaderText.textContent = 'Loading...';
      }

      // Send AJAX request
      fetch("login.php", {
        method: "POST",
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.redirect) {
          // Success: show welcome message, then redirect
          if (loaderText) {
            loaderText.textContent = 'Welcome to iSecure';
          }
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 8000); // 8 second delay before redirect
        } else if (data.error) {
          // Error: hide loader and show error modal
          if (loaderOverlay) {
            loaderOverlay.style.display = 'none';
          }
          // Show custom error modal
          const errorMessage = document.getElementById('errorMessage');
          if (errorMessage) {
            errorMessage.textContent = data.error;
          }
          if (loginErrorModal) {
            loginErrorModal.style.display = 'block';
          }
        }
      })
      .catch(error => {
        console.error('Error:', error);
        if (loaderOverlay) {
          loaderOverlay.style.display = 'none';
        }
        // Handle network errors
        const errorMessage = document.getElementById('errorMessage');
        if (errorMessage) {
          errorMessage.textContent = 'An error occurred. Please try again.';
        }
        if (loginErrorModal) {
          loginErrorModal.style.display = 'block';
        }
      });
    });
  }
});
  
