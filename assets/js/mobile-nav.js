
document.addEventListener("DOMContentLoaded", () => {
    const menuButton = document.getElementById("mobile-menu-toggle");
    const navigation = document.getElementById("site-navigation");

    if (!menuButton || !navigation) {
        console.warn("Mobile navigation elements were not found.");
        return;
    }

    function openMenu() {
        navigation.classList.add("is-open");
        menuButton.classList.add("is-open");

        menuButton.setAttribute("aria-expanded", "true");
        menuButton.setAttribute("aria-label", "Close navigation menu");
    }

    function closeMenu() {
        navigation.classList.remove("is-open");
        menuButton.classList.remove("is-open");

        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute("aria-label", "Open navigation menu");
    }

    menuButton.addEventListener("click", () => {
        const isOpen = menuButton.getAttribute("aria-expanded") === "true";

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Close after selecting a navigation link.
    navigation.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", closeMenu);
    });

    // Close with Escape.
    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            closeMenu();
        }
    });

    // Close when returning to desktop width.
    window.addEventListener("resize", () => {
        if (window.innerWidth > 1050) {
            closeMenu();
        }
    });
});
