/* wiedeck.de – lokaler Hell-/Dunkelmodus ohne Tracking */
(function () {
  "use strict";
  var storageKey = "wiedeck-theme";
  var root = document.documentElement;
  function storedTheme() {
    try {
      var value = window.localStorage.getItem(storageKey);
      return value === "light" || value === "dark" ? value : null;
    } catch (error) {
      return null;
    }
  }
  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    root.style.colorScheme = theme;
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute("content", theme === "dark" ? "#111920" : "#f4f6f7");
    var favicon = document.querySelector("link[data-theme-favicon]");
    var prefix = root.lang === "en" ? "../" : "";
    var selectedIcon = prefix + (theme === "dark" ? "logos/mw-favicon-dark.png" : "logos/mw-favicon-light.png");
    if (favicon) favicon.setAttribute("href", selectedIcon);
    var appleIcon = document.querySelector('link[rel="apple-touch-icon"]');
    if (appleIcon) appleIcon.setAttribute("href", selectedIcon);
    var button = document.getElementById("theme-toggle");
    if (button) {
      var isDark = theme === "dark";
      button.setAttribute("aria-pressed", String(isDark));
      var english = root.lang === "en";
      button.setAttribute("aria-label", isDark ? (english ? "Switch to light mode" : "Hellen Modus einschalten") : (english ? "Switch to dark mode" : "Dunklen Modus einschalten"));
      button.setAttribute("title", isDark ? (english ? "Switch to light mode" : "Zum hellen Modus wechseln") : (english ? "Switch to dark mode" : "Zum dunklen Modus wechseln"));
    }
  }
  applyTheme(storedTheme() || "dark");

  /* Einheitlicher Scroll-Komfort auf Start- und Rechtstextseiten. */
  function initializeScrollControls() {
    var header = document.querySelector(".header, .top");
    var button = document.createElement("button");
    button.type = "button";
    button.className = "back-to-top";
    button.textContent = "↑";
    var english = root.lang === "en";
    button.setAttribute("aria-label", english ? "Scroll to top" : "Nach oben scrollen");
    button.setAttribute("title", english ? "Back to top" : "Nach oben");
    document.body.appendChild(button);

    var pending = false;
    function refresh() {
      var position = window.scrollY || window.pageYOffset || 0;
      if (header) header.classList.toggle("is-scrolled", position > 12);
      button.classList.toggle("is-visible", position > 320);
      pending = false;
    }
    window.addEventListener("scroll", function () {
      if (!pending) {
        pending = true;
        window.requestAnimationFrame(refresh);
      }
    }, { passive: true });

    button.addEventListener("click", function () {
      var reduceMotion = window.matchMedia &&
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      window.scrollTo({ top: 0, behavior: reduceMotion ? "auto" : "smooth" });
    });
    refresh();
  }
  document.addEventListener("DOMContentLoaded", function () {
    var button = document.getElementById("theme-toggle");
    if (button) {
      button.addEventListener("click", function () {
        var next = root.getAttribute("data-theme") === "dark" ? "light" : "dark";
        try {
          window.localStorage.setItem(storageKey, next);
        } catch (error) {
          /* Umschalten funktioniert auch, wenn Browser-Speicherung gesperrt ist. */
        }
        applyTheme(next);
      });
    }
    applyTheme(root.getAttribute("data-theme") || "light");
    initializeScrollControls();
  });
})();
