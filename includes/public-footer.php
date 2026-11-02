<?php
require_once __DIR__ . "/../config/bootstrap.php";
?>

<footer class="site-footer">

    <a
        href="<?= BASE_URL ?>index.php"
        class="footer-logo"
        aria-label="Witchy home"
    >
        <img
            src="<?= BASE_URL ?>images/vertical_black.svg"
            alt="Witchy"
            class="logo-light"
        >

        <img
            src="<?= BASE_URL ?>images/vertical_white.svg"
            alt=""
            class="logo-dark"
            aria-hidden="true"
        >
    </a>

    <nav class="footer-nav" aria-label="Footer navigation">
        <a href="<?= BASE_URL ?>public_help.php">Help</a>
        <a href="<?= BASE_URL ?>public-contact.php">Contact</a>
        <a href="<?= BASE_URL ?>terms.php">Terms of Service</a>
        <a href="<?= BASE_URL ?>privacy.php">Privacy Policy</a>
    </nav>

    <p class="footer-copy">
        © <?= date("Y") ?> Witchy
    </p>

</footer>

<script src="<?= BASE_URL ?>assets/js/auth.js"></script>

</body>
</html>
