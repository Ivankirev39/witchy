    </main>

</div>

<footer class="user-footer">
    <p>&copy; <?= date("Y") ?> Witchy. All rights reserved.</p>
</footer>

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