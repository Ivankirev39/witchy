<?php
require_once __DIR__ . "/../config/bootstrap.php";
?>

<!-- ========================================
     USER FOOTER
     ======================================== -->

<footer class="user-footer">

    <a
        href="<?= BASE_URL ?>feed.php"
        class="user-footer-logo"
        aria-label="Witchy home"
    >
        <img
            src="<?= BASE_URL ?>images/vertical_black.svg"
            alt="Witchy"
            class="user-footer-logo-light"
        >

        <img
            src="<?= BASE_URL ?>images/vertical_white.svg"
            alt=""
            class="user-footer-logo-dark"
            aria-hidden="true"
        >
    </a>

    <nav class="user-footer-nav" aria-label="Footer navigation">

        <a href="<?= BASE_URL ?>feed.php">Home</a>

        <a href="<?= BASE_URL ?>explore.php">Explore</a>

        <a href="<?= BASE_URL ?>rules.php">Rules</a>

        <a href="<?= BASE_URL ?>saved.php">Saved</a>

        <a href="<?= BASE_URL ?>profile.php">Profile</a>

    </nav>

    <p class="user-footer-copy">
        &copy; <?= date("Y") ?> Witchy. All rights reserved.
    </p>

</footer>

</main>

</div>

<script>
const themeToggles = document.querySelectorAll(".theme-toggle");
const root = document.documentElement;

function updateThemeIcons() {
    const isDark = root.classList.contains("dark-mode");
    const label = isDark ? "Switch to light mode" : "Switch to dark mode";

    themeToggles.forEach(function (toggle) {
        toggle.textContent = isDark ? "☀" : "☾";
        toggle.setAttribute("aria-label", label);
        toggle.setAttribute("title", label);
    });
}

updateThemeIcons();

themeToggles.forEach(function (toggle) {
    toggle.addEventListener("click", function () {
        root.classList.toggle("dark-mode");
        localStorage.setItem(
            "theme",
            root.classList.contains("dark-mode") ? "dark" : "light"
        );
        updateThemeIcons();
    });
});
</script>

</body>
</html>
