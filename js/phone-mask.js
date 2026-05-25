"use strict";

document.addEventListener("DOMContentLoaded", function () {
  function formatPhone(input) {
    let value = input.value.replace(/\D/g, "");
    let formatted = "";

    if (value.length > 0) {
      if (value[0] === "8") value = "7" + value.substring(1);
      if (value[0] === "9") value = "7" + value;
      if (value.length > 11) value = value.substring(0, 11);

      formatted = "+7";
      if (value.length > 1) formatted += " (" + value.substring(1, 4);
      if (value.length >= 4) formatted += ") " + value.substring(4, 7);
      if (value.length >= 7) formatted += "-" + value.substring(7, 9);
      if (value.length >= 9) formatted += "-" + value.substring(9, 11);
    }

    input.value = formatted;
  }

  document.querySelectorAll('input[type="tel"]').forEach((input) => {
    input.addEventListener("input", () => formatPhone(input));

    input.addEventListener("click", () => {
      if (input.value === "") {
        input.value = "+7 (";
        input.setSelectionRange(4, 4);
      }
    });

    input.addEventListener("blur", () => {
      if (input.value === "+7 (" || input.value === "+7") {
        input.value = "";
      }
    });
  });
});
