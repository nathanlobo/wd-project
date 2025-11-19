// Theme Switcher
(function() {
  const theme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', theme);
  
  window.toggleTheme = function() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon();
  };
  
  window.updateThemeIcon = function() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const sliders = document.querySelectorAll('.theme-toggle-slider');
    sliders.forEach(slider => {
      slider.textContent = currentTheme === 'dark' ? '☀️' : '🌙';
    });
  };
  
  // Update icon on page load
  document.addEventListener('DOMContentLoaded', updateThemeIcon);
})();
