<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/csrf.php";

$currentPage = basename($_SERVER["PHP_SELF"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? "Witchy", ENT_QUOTES, "UTF-8") ?></title>
    <link rel="stylesheet" href="assets/css/style.css">

    <script>
        const savedTheme = localStorage.getItem("theme");

        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }
    </script>
</head>

<body>

<div class="user-layout">

    <aside class="user-sidebar">

        <div class="user-logo">
            <a href="feed.php">☾ Witchy</a>
        </div>

        <nav class="user-nav">
            <a
                href="feed.php"
                class="<?= $currentPage === "feed.php" ? "active" : "" ?>"
            >
                Home
            </a>

            <a
                href="explore.php"
                class="<?= $currentPage === "explore.php" ? "active" : "" ?>"
            >
                Explore
            </a>

            <a
                href="upload.php"
                class="<?= $currentPage === "upload.php" ? "active" : "" ?>"
            >
                Create
            </a>

            <a
                href="saved.php"
                class="<?= $currentPage === "saved.php" ? "active" : "" ?>"
            >
                Saved
            </a>

            <a
                href="profile.php"
                class="<?= $currentPage === "profile.php" ? "active" : "" ?>"
            >
                Profile
            </a>
        </nav>

        <div class="user-sidebar-bottom">

            <?php if (isset($_SESSION["username"])): ?>
                <p>
                    Logged in as
                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION["username"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>
                </p>
            <?php endif; ?>

            <form
                method="POST"
                action="logout.php"
                class="logout-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        csrf_token(),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >
                <button
                    type="submit"
                    class="logout-button"
                >
                    Log out
                </button>
            </form>

        </div>

    </aside>

    <main class="user-main">
        <header class="user-topbar">
            <form
                class="user-search"
                action="explore.php"
                method="GET"
            >
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
                >
                    ☾
                </button>

                <button
                    type="button"
                    class="topbar-icon"
                    aria-label="Notifications"
                    title="Notifications"
                >
                    ♡
                </button>

                <button
                    type="button"
                    class="topbar-icon"
                    aria-label="Messages"
                    title="Messages"
                >
                    ✉
                </button>

                <a
                    href="profile.php"
                    class="topbar-profile"
                    aria-label="Profile"
                    title="Profile"
                >
                    <?= strtoupper(
                        substr(
                            $_SESSION["username"] ?? "U",
                            0,
                            1
                        )
                    ) ?>
                </a>
            </div>
        </header>