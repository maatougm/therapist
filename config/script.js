document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.getElementById("themeToggle");
    const icon = document.querySelector(".theme-icon");
  
    const current = localStorage.getItem("theme");
    if (current === "dark") {
      document.documentElement.setAttribute("data-bs-theme", "dark");
      if (icon) icon.textContent = "☀️";
    }
  
    toggle?.addEventListener("click", () => {
      const isDark = document.documentElement.getAttribute("data-bs-theme") === "dark";
      document.documentElement.setAttribute("data-bs-theme", isDark ? "light" : "dark");
      localStorage.setItem("theme", isDark ? "light" : "dark");
      if (icon) icon.textContent = isDark ? "🌙" : "☀️";
    });
  });
  