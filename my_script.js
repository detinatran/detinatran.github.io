function calculate(operator) {
  const num1 = document.getElementById('number1').value.trim();
  const num2 = document.getElementById('number2').value.trim();
  const resultDisplay = document.getElementById('result');

  // Clear previous result
  resultDisplay.textContent = '';

  // Validate inputs
  if (num1 === '' || num2 === '') {
      resultDisplay.textContent = 'Inputs cannot be empty!';
      resultDisplay.style.color = 'red';
      return;
  }

  const number1 = parseFloat(num1);
  const number2 = parseFloat(num2);

  if (isNaN(number1) || isNaN(number2)) {
      resultDisplay.textContent = 'Please enter valid numbers!';
      resultDisplay.style.color = 'red';
      return;
  }

  // Prevent division by zero
  if (operator === '/' && number2 === 0) {
      resultDisplay.textContent = 'Cannot divide by zero!';
      resultDisplay.style.color = 'red';
      return;
  }

  // Perform calculation
  let result;
  switch (operator) {
      case '+':
          result = number1 + number2;
          break;
      case '-':
          result = number1 - number2;
          break;
      case '*':
          result = number1 * number2;
          break;
      case '/':
          result = number1 / number2;
          break;
  }

  // Display result
  resultDisplay.textContent = `Result: ${result}`;
  resultDisplay.style.color = 'green';
}
