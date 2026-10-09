/**
 * self-service/js/redesign-theme.js
 * Dark Mode (M3) and theme management
 */

// --- Dark Mode / Theme Toggle Management ---
(function() {
    var theme = localStorage.getItem('oco_theme') || 'auto';
    var isDark = theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.remove('theme-dark', 'theme-light');
    document.documentElement.classList.add(isDark ? 'theme-dark' : 'theme-light');
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
})();

window.updateThemeIcon = function() {
    var btn = document.getElementById('btnThemeToggle');
    var img = document.getElementById('imgThemeToggle');
    if (!btn || !img) return;
    
    var isDark = document.documentElement.classList.contains('theme-dark');
    var lightTitle = (typeof LANG !== 'undefined' && LANG['portal_redesign_theme_light']) || (window.OcoLangNavbar && window.OcoLangNavbar.themeLight) || 'Basculer en thème clair';
    var darkTitle = (typeof LANG !== 'undefined' && LANG['portal_redesign_theme_dark']) || (window.OcoLangNavbar && window.OcoLangNavbar.themeDark) || 'Basculer en thème sombre';

    if (isDark) {
        img.src = 'img/theme-sun.light.svg';
        btn.title = lightTitle;
    } else {
        img.src = 'img/theme-moon.light.svg';
        btn.title = darkTitle;
    }
};

var _easterEggClickCount = 0;
var _easterEggLastClick = 0;

window.toggleDarkMode = function() {
    // --- Easter Egg Logic ---
    var now = Date.now();
    if (now - _easterEggLastClick < 400) {
        _easterEggClickCount++;
    } else {
        _easterEggClickCount = 1;
    }
    _easterEggLastClick = now;

    if (_easterEggClickCount >= 15) {
        document.documentElement.style.filter = 'invert(1)';
        var btn = document.getElementById('btnThemeToggle');
        if (btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
            btn.title = "Easter egg activé ! Rafraîchissez la page.";
        }
        return;
    }
    // --- End Easter Egg Logic ---

    var isCurrentlyDark = document.documentElement.classList.contains('theme-dark');
    var nextTheme = isCurrentlyDark ? 'light' : 'dark';
    
    localStorage.setItem('oco_theme', nextTheme);
    
    document.documentElement.classList.remove('theme-dark', 'theme-light');
    document.documentElement.classList.add('theme-' + nextTheme);
    document.documentElement.style.colorScheme = nextTheme;
    
    window.updateThemeIcon();
};

document.addEventListener('DOMContentLoaded', function() {
    window.updateThemeIcon();
});
