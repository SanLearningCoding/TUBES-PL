// UI micro-interactions
document.addEventListener("DOMContentLoaded", function () {
  // Brand badge micro animation
  var badge = document.querySelector(".brand-badge");
  if (badge) {
    badge.animate(
      [
        { transform: "translateY(0)" },
        { transform: "translateY(-2px)" },
        { transform: "translateY(0)" },
      ],
      { duration: 600, easing: "ease-out" }
    );
  }

  // Active nav highlighting (based on ?action=...)
  var urlParams = new URLSearchParams(window.location.search);
  var action = urlParams.get("action") || "dashboard";
  var navLinks = document.querySelectorAll(".navbar .nav-link");
  navLinks.forEach(function (link) {
    if (
      link.getAttribute("href") &&
      link.getAttribute("href").includes("action=" + action)
    ) {
      link.classList.add("active");
    }
  });

  // Auto-hide flash alerts after 5s (hanya alert dismissible dari session, bukan alert di card body)
  var flashAlerts = document.querySelectorAll(".alert.alert-dismissible");
  flashAlerts.forEach(function (a) {
    setTimeout(function () {
      try {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(a);
        bsAlert.close();
      } catch (e) {
        a.style.display = "none";
      }
    }, 5200);
  });

  // Show/Hide password for auth pages
  var pwToggles = document.querySelectorAll(".password-toggle");
  pwToggles.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      var card = btn.closest(".auth-card");
      if (!card) return;
      var pw = card.querySelector('input[type="password"], input[type="text"]');
      if (!pw) return;
      if (pw.type === "password") {
        pw.type = "text";
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        pw.type = "password";
        btn.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  });

  // Subtle parallax on auth wrapper (mouse move) for added polish
  var authWrapper = document.querySelector(".auth-wrapper");
  if (
    authWrapper &&
    window.matchMedia("(prefers-reduced-motion: no-preference)").matches
  ) {
    authWrapper.addEventListener("mousemove", function (e) {
      var rect = authWrapper.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width - 0.5; // -0.5 .. 0.5
      var y = (e.clientY - rect.top) / rect.height - 0.5;
      var card = authWrapper.querySelector(".auth-card");
      var badge = authWrapper.querySelector(".brand-badge");
      if (card) card.style.transform = `translate3d(${x * 6}px, ${y * 6}px, 0)`;
      if (badge)
        badge.style.transform = `translate3d(${x * -6}px, ${y * -6}px, 0)`;
    });
    authWrapper.addEventListener("mouseleave", function () {
      var card = authWrapper.querySelector(".auth-card");
      var badge = authWrapper.querySelector(".brand-badge");
      if (card) card.style.transform = "";
      if (badge) badge.style.transform = "";
    });
  }
  // Ensure inputs in auth-card expose full content via tooltip and auto-scroll on focus
  var authInputs = document.querySelectorAll(
    '.auth-card input[type="email"], .auth-card input[type="text"], .auth-card input[type="password"]'
  );
  authInputs.forEach(function (inp) {
    // set initial title for hover
    if (inp.value && inp.value.length > 20)
      inp.setAttribute("title", inp.value);
    // update title as user types
    inp.addEventListener("input", function (e) {
      if (e.target.value && e.target.value.length > 20)
        e.target.setAttribute("title", e.target.value);
      else e.target.removeAttribute("title");
    });
    // try to scroll to the end on focus so the user sees the end of their email
    inp.addEventListener("focus", function (e) {
      try {
        e.target.scrollLeft = e.target.scrollWidth;
      } catch (err) {}
    });
  });
});
