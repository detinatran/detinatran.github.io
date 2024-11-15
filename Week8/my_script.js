function changeText() {
  console.log("Hello, Javascript");
  document.getElementById("demo").innerHTML = "Hello, Javascript!";
}
function calculate() {
  var a = document.getElementById("a_value").value;
  // var b = document.getElementById("b_value").value;
  // var result = parseInt(a) + parseInt(b);//ParseInt return a integer value
  // // var result = a * 2;
  // if (a % 2 == 0) {
  //   result = "Even";
  // } else {
  //   result = "Odd";
  // }
  var result = 1;
  for (var i = 1; i <= a; i++) {
    result *= i;
  }
  var output = "Result: "
  document.getElementById("result").innerHTML = output + result;
}