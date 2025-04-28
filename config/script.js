document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.getElementById("themeToggle");
    const icon = document.querySelector(".theme-icon");
    const html = document.documentElement;
  
    // Check for saved theme preference
    const currentTheme = localStorage.getItem("theme") || "light";
    if (currentTheme === "dark") {
      html.setAttribute("data-bs-theme", "dark");
      if (icon) icon.classList.replace("bi-moon-stars-fill", "bi-sun-fill");
    }
  
    // Add click event listener to toggle button
    toggle?.addEventListener("click", () => {
      const isDark = html.getAttribute("data-bs-theme") === "dark";
      const newTheme = isDark ? "light" : "dark";
      
      // Update theme
      html.setAttribute("data-bs-theme", newTheme);
      localStorage.setItem("theme", newTheme);
      
      // Update icon
      if (icon) {
        if (isDark) {
          icon.classList.replace("bi-sun-fill", "bi-moon-stars-fill");
        } else {
          icon.classList.replace("bi-moon-stars-fill", "bi-sun-fill");
        }
      }
    });
});
  