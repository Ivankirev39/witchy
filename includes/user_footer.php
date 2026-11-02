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
const themeToggle = document.getElementById("theme-toggle");
const root = document.documentElement;

function updateThemeIcon() {
    if (!themeToggle) {
        return;
    }

    if (root.classList.contains("dark-mode")) {
        themeToggle.textContent = "☀";
        themeToggle.setAttribute("aria-label", "Switch to light mode");
        themeToggle.setAttribute("title", "Switch to light mode");
    } else {
        themeToggle.textContent = "☾";
        themeToggle.setAttribute("aria-label", "Switch to dark mode");
        themeToggle.setAttribute("title", "Switch to dark mode");
    }
}

updateThemeIcon();

if (themeToggle) {
    themeToggle.addEventListener("click", function () {

        root.classList.toggle("dark-mode");

        if (root.classList.contains("dark-mode")) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }

        updateThemeIcon();
    });
}
</script>

</body>
</html>
