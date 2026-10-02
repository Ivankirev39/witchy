document.addEventListener("DOMContentLoaded", function () {
    const menuButton = document.getElementById("mobile-menu-button");
    const menuClose = document.getElementById("mobile-menu-close");
    const sidebar = document.getElementById("user-sidebar");
    const overlay = document.getElementById("mobile-menu-overlay");
    if (!menuButton || !menuClose || !sidebar || !overlay) return;
    function openMobileMenu() {
        sidebar.classList.add("mobile-open");
        overlay.classList.add("active");
        document.body.classList.add("mobile-menu-open");
        menuButton.setAttribute("aria-expanded", "true");
        menuButton.setAttribute("aria-label", "Close navigation");
    }
    function closeMobileMenu() {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        document.body.classList.remove("mobile-menu-open");
        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute("aria-label", "Open navigation");
    }
    menuButton.addEventListener("click", function () {
        if (sidebar.classList.contains("mobile-open")) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    });
    menuClose.addEventListener("click", closeMobileMenu);
    overlay.addEventListener("click", closeMobileMenu);
    sidebar.querySelectorAll(".user-nav a, .create-post-button").forEach(function (link) {
        link.addEventListener("click", closeMobileMenu);
    });
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeMobileMenu();
        }
    });
    window.addEventListener("resize", function () {
        if (window.innerWidth > 700) {
            closeMobileMenu();
        }
    });
});