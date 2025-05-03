// Main JavaScript functionality

// Navigation active state highlighting
document.addEventListener('DOMContentLoaded', function() {
  // Get current page path
  const currentPage = window.location.pathname.split('/').pop();
  
  // Find sidebar links
  const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
  
  // Set active class on current page link
  sidebarLinks.forEach(link => {
      const href = link.getAttribute('href').split('/').pop();
      if (href === currentPage) {
          link.parentElement.classList.add('active');
      }
  });

  // Connect view detail buttons to their detail pages
  const viewDetailButtons = document.querySelectorAll('.view-detail');
  if (viewDetailButtons) {
      viewDetailButtons.forEach(button => {
          button.addEventListener('click', function() {
              const type = this.getAttribute('data-type');
              if (type === 'record') {
                  window.location.href = 'record-detail.html';
              } else if (type === 'medication') {
                  window.location.href = 'medication-detail.html';
              }
          });
      });
  }

  // Handle submit buttons
  const submitButtons = document.querySelectorAll('.submit-button');
  if (submitButtons) {
      submitButtons.forEach(button => {
          button.addEventListener('click', function(e) {
              e.preventDefault();
              alert('Form submitted successfully!');
              
              // Redirect based on form type
              const formType = this.getAttribute('data-form-type');
              if (formType === 'appointment') {
                  window.location.href = 'appointment.html';
              } else if (formType === 'feedback') {
                  window.location.href = 'feedback.html';
              }
          });
      });
  }

  // Handle star ratings
  const stars = document.querySelectorAll('.stars input[type="radio"]');
  if (stars) {
      stars.forEach(star => {
          star.addEventListener('change', function() {
              console.log('Rating selected:', this.value);
          });
      });
  }

  // Handle filter buttons
  const filterButtons = document.querySelectorAll('.filter-button');
  if (filterButtons) {
      filterButtons.forEach(button => {
          button.addEventListener('click', function() {
              alert('Filter applied: ' + this.textContent);
          });
      });
  }
});

// Mock authentication - not for production use
function mockLogin(username, password) {
  // In real app, this would validate against backend
  if (username && password) {
      // Store session token
      localStorage.setItem('isLoggedIn', 'true');
      window.location.href = 'dashboard.html';
      return true;
  }
  return false;
}

// Check if user is logged in
function isAuthenticated() {
  return localStorage.getItem('isLoggedIn') === 'true';
}

// Log out user
function logout() {
  localStorage.removeItem('isLoggedIn');
  window.location.href = '../login.html';
}