<?php
$pageTitle = $pageTitle ?? "Witchy";
$currentPage = basename($_SERVER["PHP_SELF"]);
$isLoggedIn = isset($_SESSION["user_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>
        <?= htmlspecialchars(
            $pageTitle,
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </title>
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
</head>
<body>
<header class="site-header">
    <a
        href="index.php"
        class="site-logo"
        aria-label="Witchy home"
    >
        <span
            class="site-logo-symbol"
            aria-hidden="true"
        >
            ☾
        </span>
        <span>Witchy</span>
    </a>

    <nav
        class="site-nav"
        aria-label="Main navigation"
    >
        <a href="index.php#about">
            About
        </a>
        <a href="index.php#topics">
            Explore
        </a>
        <a href="index.php#community">
            Community
        </a>
    </nav>

    <div class="site-header-actions">
        <button
            type="button"
            class="theme-toggle site-theme-toggle"
            id="theme-toggle"
            aria-label="Switch to dark mode"
            title="Switch theme"
        >
            ☾
        </button>

        <?php if (
            !$isLoggedIn &&
            $currentPage !== "login.php"
        ): ?>
            <a
                href="login.php"
                class="button button-secondary"
            >
                Log in
            </a>
        <?php endif; ?>

        <?php if (
            !$isLoggedIn &&
            $currentPage !== "register.php"
        ): ?>
            <a
                href="register.php"
                class="button button-primary"
            >
                Join Witchy
            </a>
        <?php endif; ?>
    </div>
</header>