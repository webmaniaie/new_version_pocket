/* ---------------------------------------------------------------------
   /admin — the two bits of help the forms need.

   1. the web address writes itself from the headline, until it is touched
   2. delete asks first
   --------------------------------------------------------------------- */
(function () {
  "use strict";

  /* ---------------- 1. slug follows the headline ---------------- */
  var source = document.querySelector("[data-slug-source]");
  var target = document.querySelector("[data-slug-target]");

  if (source && target) {
    // Only auto-fill while the slug is untouched. An existing post
    // arrives with one already set, so its URL never moves by itself.
    var linked = target.value.trim() === "";

    target.addEventListener("input", function () {
      linked = target.value.trim() === "";
    });

    source.addEventListener("input", function () {
      if (!linked) return;
      target.value = source.value
        .toLowerCase()
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "")
        .slice(0, 80);
    });
  }

  /* ---------------- 2. confirm destructive submits ---------------- */
  var forms = document.querySelectorAll("form[data-confirm]");
  for (var i = 0; i < forms.length; i++) {
    forms[i].addEventListener("submit", function (event) {
      if (!window.confirm(this.getAttribute("data-confirm"))) {
        event.preventDefault();
      }
    });
  }
})();
