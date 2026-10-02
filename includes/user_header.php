<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/csrf.php";
require_once __DIR__ . "/../config/db.php";
$currentPage = basename($_SERVER["PHP_SELF"]);
$headerUser = null;
if (isset($_SESSION["user_id"])) {
    $headerUserStmt = $conn->prepare("SELECT username, profile_image FROM users WHERE user_id = ?");
    $headerUserStmt->bind_param("i", $_SESSION["user_id"]);
    $headerUserStmt->execute();
    $headerUser = $headerUserStmt->get_result()->fetch_assoc();
    $headerUserStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? "Witchy", ENT_QUOTES, "UTF-8") ?></title>
    <link rel="stylesheet" href="/witchy/assets/css/style.css">
    <link rel="stylesheet" href="/witchy/assets/css/user_header.css">
    <link rel="stylesheet" href="/witchy/assets/css/feed.css">
    <link rel="stylesheet" href="/witchy/assets/css/profile.css">
    <link rel="stylesheet" href="/witchy/assets/css/rules.css">
    <link rel="stylesheet" href="/witchy/assets/css/upload.css">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="/witchy/assets/css/<?= htmlspecialchars($pageCss, ENT_QUOTES, "UTF-8") ?>">
    <?php endif; ?>
    <script>
        const savedTheme = localStorage.getItem("theme");
        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }
    </script>
    <script src="/witchy/assets/js/user_header.js" defer></script>
</head>
<body>
<div class="user-layout">
    <header class="mobile-header">
        <a href="/witchy/feed.php" class="mobile-logo" aria-label="Witchy home">
            <img src="/witchy/images/horizontal_black.svg" alt="Witchy" class="user-logo-light">
            <img src="/witchy/images/horizontal_white.svg" alt="Witchy" class="user-logo-dark">
        </a>
        <div class="mobile-header-actions">
            <a href="/witchy/profile.php" class="mobile-profile" aria-label="Profile">
                <?php if (!empty($headerUser["profile_image"])): ?>
                    <img src="/witchy/<?= htmlspecialchars($headerUser["profile_image"], ENT_QUOTES, "UTF-8") ?>" alt="">
                <?php else: ?>
                    <span><?= htmlspecialchars(strtoupper(substr($headerUser["username"] ?? $_SESSION["username"] ?? "U", 0, 1)), ENT_QUOTES, "UTF-8") ?></span>
                <?php endif; ?>
            </a>
            <button type="button" class="mobile-menu-button" id="mobile-menu-button" aria-label="Open navigation" aria-expanded="false" aria-controls="user-sidebar">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>
    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    <aside class="user-sidebar" id="user-sidebar">
        <div class="mobile-menu-top">
            <span>Menu</span>
            <button type="button" class="mobile-menu-close" id="mobile-menu-close" aria-label="Close navigation">×</button>
        </div>
        <div class="user-logo">
            <a href="/witchy/feed.php" aria-label="Witchy home">
                <img src="/witchy/images/horizontal_black.svg" alt="Witchy" class="user-logo-light">
                <img src="/witchy/images/horizontal_white.svg" alt="Witchy" class="user-logo-dark">
            </a>
        </div>
        <nav class="user-nav">
            <a href="/witchy/feed.php" class="<?= $currentPage === "feed.php" ? "active" : "" ?>">Home</a>
            <a href="/witchy/explore.php" class="<?= $currentPage === "explore.php" ? "active" : "" ?>">Explore</a>
            <a href="/witchy/rules.php" class="<?= $currentPage === "rules.php" ? "active" : "" ?>">Rules</a>
            <a href="/witchy/saved.php" class="<?= $currentPage === "saved.php" ? "active" : "" ?>">Saved</a>
            <a href="/witchy/profile.php" class="<?= $currentPage === "profile.php" ? "active" : "" ?>">Profile</a>
        </nav>
        <a href="/witchy/upload.php" class="create-post-button <?= $currentPage === "upload.php" ? "active" : "" ?>">
            <span class="create-post-icon">+</span>
            Create post
        </a>
        <div class="mobile-menu-search">
            <form action="/witchy/explore.php" method="GET">
                <input type="search" name="search" placeholder="Search Witchy..." aria-label="Search Witchy">
            </form>
        </div>
        <div class="user-sidebar-bottom">
            <?php if (isset($_SESSION["username"])): ?>
                <p>Logged in as <strong><?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?></strong></p>
            <?php endif; ?>
            <form method="POST" action="/witchy/logout.php" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                <button type="submit" class="logout-button">Log out</button>
            </form>
        </div>
    </aside>
    <main class="user-main">
        <header class="user-topbar">
            <form class="user-search" action="/witchy/explore.php" method="GET">
                <input type="search" name="search" placeholder="Search posts, users or topics..." aria-label="Search Witchy">
            </form>
            <div class="user-topbar-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode" title="Toggle dark mode">☾</button>
                <button type="button" class="topbar-icon" aria-label="Notifications" title="Notifications">♡</button>
                <button type="button" class="topbar-icon" aria-label="Messages" title="Messages">✉</button>
                <a href="/witchy/profile.php" class="topbar-profile" aria-label="Profile" title="Profile">
                    <?php if (!empty($headerUser["profile_image"])): ?>
                        <img src="/witchy/<?= htmlspecialchars($headerUser["profile_image"], ENT_QUOTES, "UTF-8") ?>" alt="" class="topbar-profile-image">
                    <?php else: ?>
                        <span><?= htmlspecialchars(strtoupper(substr($headerUser["username"] ?? $_SESSION["username"] ?? "U", 0, 1)), ENT_QUOTES, "UTF-8") ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>