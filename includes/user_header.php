<?php
require_once __DIR__ . "/../config/bootstrap.php";
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/csrf.php";
require_once __DIR__ . "/../config/db.php";

$currentPage = basename($_SERVER["PHP_SELF"]);
$isOwnProfile = false;

if ($currentPage === "profile.php") {
    $viewedProfileId = (int) ($_GET["id"] ?? $_SESSION["user_id"] ?? 0);
    $loggedInUserId = (int) ($_SESSION["user_id"] ?? 0);

    $isOwnProfile = ($viewedProfileId === $loggedInUserId);
}

$headerUser = null;
$unreadNotificationCount = 0;

if (isset($_SESSION["user_id"])) {
    $headerUserStmt = $conn->prepare(
        "SELECT username, profile_image FROM users WHERE user_id = ?"
    );
    $headerUserStmt->execute([(int) $_SESSION["user_id"]]);
    $headerUser = $headerUserStmt->fetch(PDO::FETCH_ASSOC);

    $notificationStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM notification
        WHERE user_id = ? AND is_read = 0
    ");
    $notificationStmt->execute([(int) $_SESSION["user_id"]]);
    $unreadNotificationCount = (int) $notificationStmt->fetchColumn();
}

$profileImage = !empty($headerUser["profile_image"])
    ? $headerUser["profile_image"]
    : "images/default-profile.jpg";

$profileImageUrl = BASE_URL . ltrim($profileImage, "/");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle ?? "Witchy", ENT_QUOTES, "UTF-8") ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/user_header.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/feed.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/upload.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/post_not_found.css">

    <?php if (!empty($pageCss)): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>assets/css/<?= htmlspecialchars($pageCss, ENT_QUOTES, "UTF-8") ?>?v=2"
        >
    <?php endif; ?>

    <script>
        const savedTheme = localStorage.getItem("theme");

        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }
    </script>

    <script src="<?= BASE_URL ?>assets/js/user_header.js" defer></script>
</head>

<body>

<div class="user-layout">

    <header class="mobile-header">

        <a href="<?= BASE_URL ?>feed.php" class="mobile-logo" aria-label="Witchy home">
            <img src="<?= BASE_URL ?>images/horizontal_black.svg" alt="Witchy" class="user-logo-light">
            <img src="<?= BASE_URL ?>images/horizontal_white.svg" alt="Witchy" class="user-logo-dark">
        </a>

        <div class="mobile-header-actions">
            <button
             type="button"
             class="theme-toggle mobile-theme-toggle"
             aria-label="Switch to dark mode"
             title="Switch to dark mode">☾
            </button>
            <a
                href="<?= BASE_URL ?>notifications.php"
                class="mobile-notification-link"
                aria-label="Notifications<?= $unreadNotificationCount > 0
                    ? ' (' . $unreadNotificationCount . ' unread)'
                    : '' ?>"
                title="Notifications"
            >
                <svg viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/>
                    <path d="M10 21h4"/>
                </svg>

                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="notification-dot"></span>
                <?php endif; ?>
            </a>

            <a href="<?= BASE_URL ?>profile.php" class="mobile-profile" aria-label="Profile">
                <img
                    src="<?= htmlspecialchars($profileImageUrl, ENT_QUOTES, "UTF-8") ?>"
                    alt="<?= htmlspecialchars($headerUser["username"] ?? "Profile", ENT_QUOTES, "UTF-8") ?>'s profile picture"
                >
            </a>

            <button
                type="button"
                class="mobile-menu-button"
                id="mobile-menu-button"
                aria-label="Open navigation"
                aria-expanded="false"
                aria-controls="user-sidebar"
            >
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
            <a href="<?= BASE_URL ?>feed.php" aria-label="Witchy home">
                <img src="<?= BASE_URL ?>images/horizontal_black.svg" alt="Witchy" class="user-logo-light">
                <img src="<?= BASE_URL ?>images/horizontal_white.svg" alt="Witchy" class="user-logo-dark">
            </a>
        </div>

        <nav class="user-nav">
            <a href="<?= BASE_URL ?>feed.php" class="<?= $currentPage === "feed.php" ? "active" : "" ?>">Home</a>
            <a href="<?= BASE_URL ?>explore.php" class="<?= $currentPage === "explore.php" ? "active" : "" ?>">Explore</a>
            <a href="<?= BASE_URL ?>rules.php" class="<?= $currentPage === "rules.php" ? "active" : "" ?>">Rules</a>
            <a href="<?= BASE_URL ?>saved.php" class="<?= $currentPage === "saved.php" ? "active" : "" ?>">Saved</a>
            <a href="<?= BASE_URL ?>profile.php" class="<?= $isOwnProfile ? "active" : "" ?>">Profile</a>

            <?php if (($_SESSION["rank"] ?? "") === "admin"): ?>
                <a href="<?= BASE_URL ?>admin/index.php">Admin Dashboard</a>
            <?php endif; ?>
        </nav>
        <nav class="mobile-extra-links" aria-label="More pages">
            <a href="<?= BASE_URL ?>notifications.php">Notifications</a>
            <a href="<?= BASE_URL ?>liked.php">Liked posts</a>
            <a href="<?= BASE_URL ?>settings.php">Account settings</a>
            <a href="<?= BASE_URL ?>help.php">Help</a>
            <a href="<?= BASE_URL ?>contact.php">Contact</a>
            <a href="<?= BASE_URL ?>about.php">About</a>
            <a href="<?= BASE_URL ?>community.php">Community</a>
        </nav>

        <a
            href="<?= BASE_URL ?>upload.php"
            class="create-post-button <?= $currentPage === "upload.php" ? "active" : "" ?>"
        >
            <span class="create-post-icon">+</span>
            Create post
        </a>

        <div class="mobile-menu-search">
            <form action="<?= BASE_URL ?>explore.php" method="GET">
                <input
                    type="search"
                    name="search"
                    placeholder="Search Witchy..."
                    aria-label="Search Witchy"
                >
            </form>
        </div>

        <div class="user-sidebar-bottom">

            <?php if (isset($_SESSION["username"])): ?>
                <p>
                    Logged in as
                    <strong><?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?></strong>
                </p>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>logout.php" class="logout-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >
                <button type="submit" class="logout-button">Log out</button>
            </form>

        </div>

    </aside>

    <main class="user-main">

        <header class="user-topbar">

            <form class="user-search" action="<?= BASE_URL ?>explore.php" method="GET">
                <input
                    type="search"
                    name="search"
                    placeholder="Search posts, users or topics..."
                    aria-label="Search Witchy"
                >
            </form>

            <div class="user-topbar-actions">

                <button
                    type="button"
                    class="theme-toggle"
                    id="theme-toggle"
                    aria-label="Toggle dark mode"
                    title="Toggle dark mode"
                >☾</button>

                <a href="<?= BASE_URL ?>help.php" class="topbar-icon" aria-label="Help" title="Help">ⓘ</a>

                <a
                    href="<?= BASE_URL ?>notifications.php"
                    class="topbar-icon topbar-notification-link"
                    aria-label="Notifications"
                    title="Notifications"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z" />
                        <path d="M10 21h4" />
                    </svg>

                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="notification-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>liked.php" class="topbar-icon" aria-label="Liked posts" title="Liked posts">♡</a>

                <a href="<?= BASE_URL ?>profile.php" class="topbar-profile" aria-label="Profile" title="Profile">
                    <img
                        src="<?= htmlspecialchars($profileImageUrl, ENT_QUOTES, "UTF-8") ?>"
                        alt="<?= htmlspecialchars($headerUser["username"] ?? "Profile", ENT_QUOTES, "UTF-8") ?>'s profile picture"
                        class="topbar-profile-image"
                    >
                </a>

            </div>

        </header>

        <script src="/witchy/assets/js/feed-scroll.js"></script>
