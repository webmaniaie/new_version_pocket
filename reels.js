/* ---------------------------------------------------------------------
   reels agency — brand interactions. Runs after script.js / fx.js.
   1. the home wordmark writing itself on
   2. half-screen product sheets
   --------------------------------------------------------------------- */
(function () {
  "use strict";

  /* ---------------- 1. home hero: write the wordmark ---------------- */
  var mark = document.querySelector(".reel-hero-mark");
  if (mark) {
    // flush layout so the un-drawn state is the transition's start value,
    // then arm the animation. A forced reflow beats requestAnimationFrame
    // here because rAF is throttled in background tabs.
    void mark.offsetHeight;
    document.body.classList.add("is-writing");
  }

  /* ---------------- 2. contact form ----------------
     GitHub Pages cannot send mail itself. The form posts to FormSubmit,
     which keeps mail credentials and user input away from this site. We do
     client-side validation for good feedback; the provider still performs
     the security checks, reCAPTCHA and honeypot handling server-side. */
  var contactForm = document.getElementById("contact-form");
  if (contactForm) {
    var note = document.getElementById("cf-note");
    var service = document.getElementById("cf-service");
    var serviceMenu = document.getElementById("cf-service-menu");
    var serviceInput = document.getElementById("cf-service-value");
    var serviceValue = "";

    // Custom listbox: a native <select> let the OS draw its menu upward, over
    // the field above it. This one is anchored under the control.
    if (service && serviceMenu) {
      var options = Array.prototype.slice.call(
        serviceMenu.querySelectorAll("[role=option]")
      );

      var closeMenu = function () {
        serviceMenu.hidden = true;
        service.setAttribute("aria-expanded", "false");
      };

      var openMenu = function () {
        serviceMenu.hidden = false;
        service.setAttribute("aria-expanded", "true");
        var current = options.filter(function (o) {
          return o.getAttribute("aria-selected") === "true";
        })[0];
        (current || options[0]).focus();
      };

      var pick = function (option) {
        options.forEach(function (o) {
          o.setAttribute("aria-selected", String(o === option));
        });
        serviceValue = option.textContent.trim();
        if (serviceInput) serviceInput.value = serviceValue;
        service.textContent = serviceValue;
        service.classList.add("has-value");
        closeMenu();
        service.focus();
      };

      service.addEventListener("click", function () {
        if (serviceMenu.hidden) openMenu();
        else closeMenu();
      });

      service.addEventListener("keydown", function (event) {
        if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          openMenu();
        }
      });

      options.forEach(function (option, index) {
        option.addEventListener("click", function () {
          pick(option);
        });
        option.addEventListener("keydown", function (event) {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            pick(option);
          } else if (event.key === "ArrowDown") {
            event.preventDefault();
            (options[index + 1] || options[0]).focus();
          } else if (event.key === "ArrowUp") {
            event.preventDefault();
            (options[index - 1] || options[options.length - 1]).focus();
          } else if (event.key === "Escape") {
            closeMenu();
            service.focus();
          }
        });
      });

      document.addEventListener("click", function (event) {
        if (serviceMenu.hidden) return;
        if (service.contains(event.target) || serviceMenu.contains(event.target)) return;
        closeMenu();
      });

      document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !serviceMenu.hidden) closeMenu();
      });

      // arriving from a product sheet: contacts.html?service=marathon
      var wanted = (function () {
        var match = window.location.search.match(/[?&]service=([a-z-]+)/i);
        return match ? match[1].toLowerCase() : "";
      })();
      if (wanted) {
        var byKey = {
          sprint: "Reels Sprint",
          marathon: "Reels Marathon",
          symbiosis: "Symbiosis",
          courses: "Video Courses",
        };
        var label = byKey[wanted];
        var preset = label
          ? options.filter(function (o) {
              return o.textContent.indexOf(label) === 0;
            })[0]
          : null;
        if (preset) {
          options.forEach(function (o) {
            o.setAttribute("aria-selected", String(o === preset));
          });
          serviceValue = preset.textContent.trim();
          if (serviceInput) serviceInput.value = serviceValue;
          service.textContent = serviceValue;
          service.classList.add("has-value");
        }
      }
    }

    var say = function (message, isError) {
      if (!note) return;
      note.textContent = message;
      note.classList.toggle("is-error", !!isError);
    };

    // FormSubmit redirects back with this flag after the protected request.
    // Remove it from the address bar so a refresh does not repeat the notice.
    if (/[?&]sent=1(?:&|$)/.test(window.location.search)) {
      say("Thanks — your enquiry is in. Check your inbox for confirmation.", false);
      if (window.history && window.history.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    }

    contactForm.addEventListener("submit", function (event) {
      var get = function (id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : "";
      };
      var name = get("cf-name");
      var email = get("cf-email");
      var phone = get("cf-phone");
      var picked = serviceValue; // the listbox is a button, not an <input>
      var message = get("cf-message");
      var consent = document.getElementById("cf-consent");

      if (!name) {
        event.preventDefault();
        return say("Add your name so we know who we're talking to.", true);
      }
      if (!email || email.indexOf("@") < 1 || email.indexOf(".") < 0) {
        event.preventDefault();
        return say("That email doesn't look right — check it and try again.", true);
      }
      if (consent && !consent.checked) {
        event.preventDefault();
        return say("Tick the box so we're allowed to reply.", true);
      }

      // Reject non-printing control characters. Newlines remain allowed in
      // the message body, but no typed value is ever used as a mail header.
      var unsafeControls = /[\u0000-\u0008\u000b\u000c\u000e-\u001f\u007f]/;
      if ([name, email, phone, message].some(function (value) {
        return unsafeControls.test(value);
      })) {
        event.preventDefault();
        return say("Remove unsupported characters and try again.", true);
      }

      if (!contactForm.checkValidity()) {
        event.preventDefault();
        contactForm.reportValidity();
        return say("Check the highlighted fields and try again.", true);
      }

      if (serviceInput) serviceInput.value = picked;
      var submit = contactForm.querySelector(".contact-submit");
      if (submit) {
        submit.disabled = true;
        submit.textContent = "Sending…";
      }
      say("Sending securely…", false);
    });
  }

  /* ---------------- 3. nav over a case cover video ----------------
     While the page is still on the cover clip the nav pill goes translucent
     so the video reads through it, then returns to solid on scroll. */
  if (document.body.classList.contains("has-cover")) {
    var cover = document.querySelector(".case-cover");
    if (cover) {
      var syncCoverNav = function () {
        var passed = window.scrollY > cover.offsetHeight - 90;
        document.body.classList.toggle("at-cover", !passed);
      };
      window.addEventListener("scroll", syncCoverNav, { passive: true });
      window.addEventListener("resize", syncCoverNav);
      syncCoverNav();
    }
  }

  /* ---------------- 4. product sheets ---------------- */
  var sheets = Array.prototype.slice.call(
    document.querySelectorAll("[data-product-sheet]")
  );
  if (!sheets.length) return;

  var overlay = document.querySelector(".product-sheet-overlay");
  var openSheet = null;
  var lastTrigger = null;

  // sheets start display:none so they never eat clicks; mount them once
  sheets.forEach(function (sheet) {
    sheet.classList.add("is-mounted");
    sheet.setAttribute("aria-hidden", "true");
  });

  function lockScroll(locked) {
    document.body.classList.toggle("modal-open", locked);
    document.documentElement.classList.toggle("modal-open", locked);
  }

  function close() {
    if (!openSheet) return;
    openSheet.classList.remove("is-open");
    openSheet.setAttribute("aria-hidden", "true");
    if (overlay) overlay.classList.remove("is-open");
    lockScroll(false);
    var closing = openSheet;
    openSheet = null;
    // reset the scroll position once the slide-down has finished
    window.setTimeout(function () {
      if (openSheet !== closing) closing.scrollTop = 0;
    }, 450);
    if (lastTrigger && typeof lastTrigger.focus === "function") {
      lastTrigger.focus();
    }
  }

  function open(name, trigger) {
    var sheet = sheets.filter(function (s) {
      return s.getAttribute("data-product-sheet") === name;
    })[0];
    if (!sheet) return;
    if (openSheet && openSheet !== sheet) {
      openSheet.classList.remove("is-open");
      openSheet.setAttribute("aria-hidden", "true");
    }
    lastTrigger = trigger || null;
    openSheet = sheet;
    sheet.setAttribute("aria-hidden", "false");
    if (overlay) overlay.classList.add("is-open");
    lockScroll(true);
    // flush layout so the slide-up transition has a start value to run from
    void sheet.offsetHeight;
    sheet.classList.add("is-open");
    var closeButton = sheet.querySelector(".product-sheet-close");
    if (closeButton) closeButton.focus();
  }

  document.querySelectorAll("[data-product-open]").forEach(function (trigger) {
    trigger.addEventListener("click", function (event) {
      event.preventDefault();
      open(trigger.getAttribute("data-product-open"), trigger);
    });
    trigger.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        open(trigger.getAttribute("data-product-open"), trigger);
      }
    });
  });

  document
    .querySelectorAll(".product-sheet-close")
    .forEach(function (button) {
      button.addEventListener("click", close);
    });

  if (overlay) overlay.addEventListener("click", close);

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") close();
  });

  // keep focus inside an open sheet
  document.addEventListener("focusin", function (event) {
    if (!openSheet) return;
    if (openSheet.contains(event.target)) return;
    if (event.target === document.body) return;
    var close_ = openSheet.querySelector(".product-sheet-close");
    if (close_) close_.focus();
  });
})();

// Replays the client-counter celebration each time the section re-enters view.
(function () {
  var counter = document.querySelector(".client-counter");
  if (!counter || !("IntersectionObserver" in window)) return;
  if (
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches
  ) return;

  var isInView = false;
  var cleanupTimer = null;

  function burst() {
    counter.classList.remove("is-confetti-popping");
    void counter.offsetWidth;
    counter.classList.add("is-confetti-popping");
    window.clearTimeout(cleanupTimer);
    cleanupTimer = window.setTimeout(function () {
      counter.classList.remove("is-confetti-popping");
    }, 2380);
  }

  new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && !isInView) {
          isInView = true;
          burst();
        } else if (!entry.isIntersecting) {
          isInView = false;
          window.clearTimeout(cleanupTimer);
          counter.classList.remove("is-confetti-popping");
        }
      });
    },
    { threshold: 0.25 }
  ).observe(counter);
})();
