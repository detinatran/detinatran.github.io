const form = document.getElementById('registrationForm');
    const successMessage = document.getElementById('successMessage');

    form.addEventListener('submit', function (event) {
      event.preventDefault(); // Prevent form from submitting

      // Reset error messages
      let isValid = true;
      successMessage.style.display = 'none';

      // Username validation
      const username = document.getElementById('username');
      const usernameError = document.getElementById('usernameError');
      if (username.value.trim().length < 3) {
        usernameError.style.display = 'block';
        username.style.border = '1px solid red';
        isValid = false;
      } else {
        usernameError.style.display = 'none';
        username.style.border = '';
      }

      // Email validation
      const email = document.getElementById('email');
      const emailError = document.getElementById('emailError');
      if (!email.validity.valid) {
        emailError.style.display = 'block';
        email.style.border = '1px solid red';
        isValid = false;
      } else {
        emailError.style.display = 'none';
        email.style.border = '';
      }

      // Password validation
      const password = document.getElementById('password');
      const passwordError = document.getElementById('passwordError');
      const passwordPattern = /(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*[A-Z]).{8,}/;
      if (!passwordPattern.test(password.value)) {
        passwordError.style.display = 'block';
        password.style.border = '1px solid red';
        isValid = false;
      } else {
        passwordError.style.display = 'none';
        password.style.border = '';
      }

      // Confirm password validation
      const confirmPassword = document.getElementById('confirmPassword');
      const confirmPasswordError = document.getElementById('confirmPasswordError');
      if (password.value !== confirmPassword.value) {
        confirmPasswordError.style.display = 'block';
        confirmPassword.style.border = '1px solid red';
        isValid = false;
      } else {
        confirmPasswordError.style.display = 'none';
        confirmPassword.style.border = '';
      }

      // Phone number validation
      const phoneNumber = document.getElementById('phoneNumber');
      const phoneNumberError = document.getElementById('phoneNumberError');
      const phonePattern = /^\d{3}-\d{3}-\d{4}$/;
      if (!phonePattern.test(phoneNumber.value)) {
        phoneNumberError.style.display = 'block';
        phoneNumber.style.border = '1px solid red';
        isValid = false;
      } else {
        phoneNumberError.style.display = 'none';
        phoneNumber.style.border = '';
      }

      // Show success message if form is valid
      if (isValid) {
        successMessage.style.display = 'block';
        form.reset(); // Clear form fields
      }
    });