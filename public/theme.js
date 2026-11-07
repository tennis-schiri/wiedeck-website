/* wiedeck.de – lokaler Hell-/Dunkelmodus ohne Tracking */
(function () {
  "use strict";
  var storageKey = "wiedeck-theme";
  var root = document.documentElement;
  var systemMode = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
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
    var selectedIcon = theme === "dark" ? "logos/mw-favicon-dark.png" : "logos/mw-favicon-light.png";
    if (favicon) favicon.setAttribute("href", selectedIcon);
    var appleIcon = document.querySelector('link[rel="apple-touch-icon"]');
    if (appleIcon) appleIcon.setAttribute("href", selectedIcon);
    var button = document.getElementById("theme-toggle");
    if (button) {
      var isDark = theme === "dark";
      button.setAttribute("aria-pressed", String(isDark));
      button.setAttribute("aria-label", isDark ? "Hellen Modus einschalten" : "Dunklen Modus einschalten");
      button.setAttribute("title", isDark ? "Zum hellen Modus wechseln" : "Zum dunklen Modus wechseln");
    }
  }
  applyTheme(storedTheme() || (systemMode && systemMode.matches ? "dark" : "light"));
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
  });
  if (systemMode) {
    var updateFromSystem = function (event) {
      if (!storedTheme()) applyTheme(event.matches ? "dark" : "light");
    };
    if (typeof systemMode.addEventListener === "function") {
      systemMode.addEventListener("change", updateFromSystem);
    } else if (typeof systemMode.addListener === "function") {
      systemMode.addListener(updateFromSystem);
    }
  }
})();
