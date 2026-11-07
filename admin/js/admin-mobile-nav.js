document.addEventListener("DOMContentLoaded", () => {
    const menuButton = document.getElementById(
        "admin-mobile-menu-toggle"
    );

    const navigation = document.getElementById(
        "admin-navigation"
    );

    if (!menuButton || !navigation) {
        return;
    }

    function openMenu() {
        navigation.classList.add("is-open");
        menuButton.classList.add("is-open");

        menuButton.setAttribute("aria-expanded", "true");
        menuButton.setAttribute(
            "aria-label",
            "Close admin navigation menu"
        );
    }

    function closeMenu() {
        navigation.classList.remove("is-open");
        menuButton.classList.remove("is-open");

        menuButton.setAttribute("aria-expanded", "false");
        menuButton.setAttribute(
            "aria-label",
            "Open admin navigation menu"
        );
    }

    menuButton.addEventListener("click", () => {
        const isOpen =
            menuButton.getAttribute("aria-expanded") === "true";

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Close the menu after selecting a navigation link.
    navigation.querySelectorAll("a").forEach(link => {
        link.addEventListener("click", closeMenu);
    });

    // Close the menu with Escape.
    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            closeMenu();
        }
    });

    // Close the menu when returning to desktop width.
    window.addEventListener("resize", () => {
        if (window.innerWidth > 1050) {
            closeMenu();
        }
    });
});
