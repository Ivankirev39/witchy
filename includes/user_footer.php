<!-- ========================================
     USER FOOTER
     ======================================== -->

<footer class="user-footer">

    <a
        href="/witchy/feed.php"
        class="user-footer-logo"
        aria-label="Witchy home"
    >
        <img
            src="/witchy/images/vertical_black.svg"
            alt="Witchy"
            class="user-footer-logo-light"
        >

        <img
            src="/witchy/images/vertical_white.svg"
            alt=""
            class="user-footer-logo-dark"
            aria-hidden="true"
        >
    </a>


    <nav
        class="user-footer-nav"
        aria-label="Footer navigation"
        >
        <a href="/witchy/feed.php">
            Home
        </a>

        <a href="/witchy/explore.php">
            Explore
        </a>

        <a href="/witchy/rules.php">
            Rules
        </a>

        <a href="/witchy/saved.php">
            Saved
        </a>

        <a href="/witchy/profile.php">
            Profile
        </a>
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
        themeToggle.setAttribute(
            "aria-label",
            "Switch to light mode"
        );
        themeToggle.setAttribute(
            "title",
            "Switch to light mode"
        );
    } else {
        themeToggle.textContent = "☾";
        themeToggle.setAttribute(
            "aria-label",
            "Switch to dark mode"
        );
        themeToggle.setAttribute(
            "title",
            "Switch to dark mode"
        );
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