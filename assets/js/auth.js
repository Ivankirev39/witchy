// ========================================
// WITCHY THEME
// ========================================

const themeButton =
    document.getElementById("theme-toggle");


function setTheme(theme) {

    const isDark = theme === "dark";

    // Update both HTML and body.
    document.documentElement.classList.toggle(
        "dark-mode",
        isDark
    );

    document.body.classList.toggle(
        "dark-mode",
        isDark
    );

    // Use the same preference as logged-in pages.
    try {
        localStorage.setItem("theme", theme);
    } catch (error) {
        // Switching still works for the current page.
    }

    updateThemeButton();
}


function updateThemeButton() {

    if (!themeButton) {
        return;
    }

    const isDark =
        document.body.classList.contains("dark-mode");


    themeButton.textContent =
        isDark ? "☀" : "☾";


    themeButton.setAttribute(
        "aria-label",
        isDark
            ? "Switch to light mode"
            : "Switch to dark mode"
    );
}


let savedTheme = null;

try {
    savedTheme = localStorage.getItem("theme")
        ?? localStorage.getItem("witchy-theme");
} catch (error) {
    // Continue using the device preference.
}


if (savedTheme === "dark") {

    document.body.classList.add("dark-mode");

} else if (savedTheme === "light") {

    document.body.classList.remove("dark-mode");

} else {

    const prefersDark =
        window.matchMedia(
            "(prefers-color-scheme: dark)"
        ).matches;

    if (prefersDark) {
        document.body.classList.add("dark-mode");
    }
}


updateThemeButton();


if (themeButton) {

    themeButton.addEventListener(
        "click",
        function () {

            const isDark =
                document.body.classList.contains(
                    "dark-mode"
                );

            setTheme(
                isDark ? "light" : "dark"
            );
        }
    );
}



// ========================================
// SHOW / HIDE PASSWORD
// ========================================

const passwordButtons =
    document.querySelectorAll(".password-toggle");


passwordButtons.forEach(function (button) {

    button.addEventListener(
        "click",
        function () {

            const inputId =
                button.dataset.password;

            const input =
                document.getElementById(inputId);


            if (!input) {
                return;
            }


            const isHidden =
                input.type === "password";


            input.type =
                isHidden ? "text" : "password";


            button.setAttribute(
                "aria-label",
                isHidden
                    ? "Hide password"
                    : "Show password"
            );


            button.classList.toggle(
                "password-visible",
                isHidden
            );
        }
    );
});