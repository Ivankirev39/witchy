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

    <!-- MAIN STYLES -->
    <link
        rel="stylesheet"
        href="/witchy/assets/css/style.css"
    >

    <!-- PUBLIC HEADER STYLES -->
    <link
        rel="stylesheet"
        href="/witchy/assets/css/header.css"
    >
</head>

<body>

<header class="site-header">

    <!-- LOGO -->
    <a
        href="/witchy/index.php"
        class="site-logo"
        aria-label="Witchy home"
        >
        <img
            src="/witchy/images/horizontal_black.svg"
            alt="Witchy"
            class="logo-light"
        >

        <img
            src="/witchy/images/horizontal_white.svg"
            alt=""
            class="logo-dark"
            aria-hidden="true"
        >
    </a>


    <!-- NAVIGATION -->
    <nav
        class="site-nav"
        aria-label="Main navigation"
    >
        <a href="/witchy/index.php#about">
            About
        </a>

        <a href="/witchy/index.php#topics">
            Explore
        </a>

        <a href="/witchy/index.php#community">
            Community
        </a>
    </nav>


    <!-- HEADER ACTIONS -->
    <div class="site-header-actions">

        <!-- DARK MODE -->
        <button
            type="button"
            class="theme-toggle site-theme-toggle"
            id="theme-toggle"
            aria-label="Switch to dark mode"
            title="Switch theme"
        >
            ☾
        </button>


        <!-- LOG IN -->
        <?php if (
            !$isLoggedIn &&
            $currentPage !== "login.php"
        ): ?>

            <a
                href="/witchy/login.php"
                class="button button-secondary"
            >
                Log in
            </a>

        <?php endif; ?>


        <!-- REGISTER -->
        <?php if (
            !$isLoggedIn &&
            $currentPage !== "register.php"
        ): ?>

            <a
                href="/witchy/register.php"
                class="button button-primary"
            >
                Join Witchy
            </a>

        <?php endif; ?>

    </div>

</header>