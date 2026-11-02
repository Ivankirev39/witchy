<?php
require_once __DIR__ . "/../config/bootstrap.php";

$pageTitle = $pageTitle ?? "Witchy";
$currentPage = basename($_SERVER["PHP_SELF"]);
$isLoggedIn = isset($_SESSION["user_id"]) && !($isLandingPreview ?? false);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></title>

    <!-- MAIN STYLES -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <!-- PUBLIC HEADER STYLES -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/header.css">

    <!-- PAGE-SPECIFIC STYLES -->
    <?php if (!empty($pageCss)): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/<?= htmlspecialchars($pageCss, ENT_QUOTES, "UTF-8") ?>"
        >
    <?php endif; ?>

    <!-- MOBILE NAVIGATION -->
    <script src="<?= BASE_URL ?>assets/js/mobile-nav.js" defer></script>

</head>

<body>

<header class="site-header">

    <!-- LOGO -->
    <a
        href="<?= BASE_URL ?>index.php"
        class="site-logo"
        aria-label="Witchy home"
    >
        <img
            src="<?= BASE_URL ?>images/horizontal_black.svg"
            alt="Witchy"
            class="logo-light"
        >

        <img
            src="<?= BASE_URL ?>images/horizontal_white.svg"
            alt=""
            class="logo-dark"
            aria-hidden="true"
        >
    </a>

    <nav class="site-nav" id="site-navigation" aria-label="Main navigation">
        <a href="<?= BASE_URL ?>index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>

        <a href="<?= BASE_URL ?>about.php"
           class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">About</a>

        <a href="<?= BASE_URL ?>public-explore.php"
           class="<?= $currentPage === 'public-explore.php' ? 'active' : '' ?>">Explore</a>

        <a href="<?= BASE_URL ?>community.php"
           class="<?= $currentPage === 'community.php' ? 'active' : '' ?>">Community</a>
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
        <?php if (!$isLoggedIn && $currentPage !== "login.php"): ?>
            <a
                href="<?= BASE_URL ?>login.php"
                class="button button-secondary"
            >
                Log in
            </a>
        <?php endif; ?>

        <!-- REGISTER -->
        <?php if (!$isLoggedIn && $currentPage !== "register.php"): ?>
            <a
                href="<?= BASE_URL ?>register.php"
                class="button button-primary"
            >
                Join Witchy
            </a>
        <?php endif; ?>

    </div>

    <!-- MOBILE MENU BUTTON -->
    <button
        type="button"
        class="mobile-menu-toggle"
        id="mobile-menu-toggle"
        aria-label="Open navigation menu"
        aria-expanded="false"
        aria-controls="site-navigation"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

</header>
</body>
</html>
