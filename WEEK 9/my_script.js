// Function to perform calculations
function calculate(operation) {
  // Get the values of the input fields
  let num1 = document.getElementById('number1').value;
  let num2 = document.getElementById('number2').value;
  
  // Check if both inputs are empty
  if (num1 === '' || num2 === '') {
      document.getElementById('result').innerHTML = 'Both fields are required!';
      document.getElementById('result').style.color = 'red';
      return;
  }

  // Check if both inputs are valid numbers
  if (isNaN(num1) || isNaN(num2)) {
      document.getElementById('result').innerHTML = 'Please enter valid numbers!';
      document.getElementById('result').style.color = 'red';
      return;
  }

  // Convert inputs to numbers
  num1 = parseFloat(num1);
  num2 = parseFloat(num2);

  let result;
  
  // Perform calculation based on the operation
  if (operation === '+') {
      result = num1 + num2;
  } else if (operation === '-') {
      result = num1 - num2;
  } else if (operation === '*') {
      result = num1 * num2;
  } else if (operation === '/') {
      if (num2 === 0) {
          document.getElementById('result').innerHTML = 'Cannot divide by 0!';
          document.getElementById('result').style.color = 'red';
          return;
      }
      result = num1 / num2;
  }

  // Display the result
  document.getElementById('result').innerHTML = 'Result: ' + result;
  document.getElementById('result').style.color = 'green'; 
}
function reset(){
  document.getElementById('number1').value='';
  document.getElementById('number2').value='';
}
