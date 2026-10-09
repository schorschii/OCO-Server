/**
 * self-service/js/redesign-navbar.js
 * Management of the top navigation bar (Top App Bar M3) and active state
 */

// Safeguard against missing legacy elements expected by main.js
if (typeof window.btnRefresh === 'undefined') {
    window.btnRefresh = document.getElementById('btnRefresh') || { classList: { add: function(){}, remove: function(){} } };
}

/**
 * Updates the active class on navigation buttons depending on current view
 * @param {string|null} targetUrl - Optional target URL (used during navigation interception)
 */
window.updateActiveNavbarLink = function(targetUrl = null) {
    const btnComputers = document.getElementById('m3NavComputers');
    const btnStore = document.getElementById('m3NavStore');
    const btnJobs = document.getElementById('m3NavJobs');

    if (!btnComputers || !btnStore || !btnJobs) return;

    btnComputers.classList.remove('active');
    btnStore.classList.remove('active');
    btnJobs.classList.remove('active');

    let currentUrl = targetUrl || window.currentExplorerContentUrl || '';

    if (!currentUrl) {
        const urlParams = new URLSearchParams(window.location.search);
        const viewParam = urlParams.get('view');
        if (viewParam) {
            currentUrl = `views/${viewParam}.php`;
        }
    }

    if (currentUrl.includes('views/computers.php') || currentUrl.includes('views/computer-details.php')) {
        btnComputers.classList.add('active');
    } else if (currentUrl.includes('views/packages.php') || currentUrl.includes('views/package-details.php')) {
        btnStore.classList.add('active');
    } else if (currentUrl.includes('views/job-containers.php') || currentUrl.includes('views/job-container-details.php') || currentUrl.includes('views/job-container-new.php')) {
        btnJobs.classList.add('active');
    }
};

// Globally intercept OCO AJAX navigation to update Navbar active state immediately
if (typeof window.refreshContentExplorer === 'function' && !window.refreshContentExplorer.isWrapped) {
    const originalRefreshContentExplorer = window.refreshContentExplorer;
    window.refreshContentExplorer = function(url, ...args) {
        if (typeof originalRefreshContentExplorer === 'function') {
            originalRefreshContentExplorer(url, ...args);
        }
        window.updateActiveNavbarLink(url);
    };
    window.refreshContentExplorer.isWrapped = true;
}

window.initM3Favicon = function() {
    let link = document.querySelector("link[rel~='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }
    link.href = 'img/logo.dyn.svg';
};

// Close mobile burger menu on click outside
document.addEventListener('click', (e) => {
    const navLinks = document.getElementById('m3NavLinks');
    const burgerBtn = document.getElementById('m3BurgerBtn');
    if (navLinks && navLinks.classList.contains('show-mobile-menu')) {
        if (!navLinks.contains(e.target) && (!burgerBtn || !burgerBtn.contains(e.target))) {
            navLinks.classList.remove('show-mobile-menu');
        }
    }
});

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    window.updateActiveNavbarLink();
    window.initM3Favicon();
});
