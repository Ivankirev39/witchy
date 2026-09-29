<?php

// Load session, CSRF protection and database connection.
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/csrf.php";
require_once __DIR__ . "/../config/db.php";

// Get the name of the current page for active navigation styling.
$currentPage = basename($_SERVER["PHP_SELF"]);

// Get the logged-in user's information for the header.
$headerUser = null;

if (isset($_SESSION["user_id"])) {

    $headerUserStmt = $conn->prepare("
        SELECT username, profile_image
        FROM users
        WHERE user_id = ?
    ");

    $headerUserStmt->bind_param(
        "i",
        $_SESSION["user_id"]
    );

    $headerUserStmt->execute();

    $headerUser = $headerUserStmt
        ->get_result()
        ->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Page metadata and title. -->
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= htmlspecialchars(
            $pageTitle ?? "Witchy",
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </title>


    <!-- Load shared Witchy stylesheets. -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/upload.css">
    <link rel="stylesheet" href="assets/css/feed.css">
    <link rel="stylesheet" href="assets/css/rules.css">

    <!-- Load an additional page-specific stylesheet when needed. -->
    <?php if (!empty($pageCss)): ?>
        <link
            rel="stylesheet"
            href="assets/css/<?= htmlspecialchars(
                $pageCss,
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >
    <?php endif; ?>


    <!-- Apply the saved theme before the page is displayed. -->
    <script>

        const savedTheme = localStorage.getItem("theme");

        if (savedTheme === "dark") {
            document.documentElement.classList.add("dark-mode");
        }

    </script>

</head>


<body>

<div class="user-layout">


    <!-- Sidebar with logo, navigation and account actions. -->
    <aside class="user-sidebar">


        <!-- Witchy logo. -->
        <div class="user-logo">

            <a
                href="/witchy/feed.php"
                class="user-logo"
                aria-label="Witchy home"
            >

                <img
                    src="/witchy/images/horizontal_black.svg"
                    alt="Witchy"
                    class="user-logo-light"
                >

                <img
                    src="/witchy/images/horizontal_white.svg"
                    alt=""
                    class="user-logo-dark"
                    aria-hidden="true"
                >

            </a>

        </div>


        <!-- Main navigation for logged-in users. -->
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
                href="rules.php"
                class="<?= $currentPage === "rules.php" ? "active" : "" ?>"
            >
                Rules
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


        <!-- Shortcut for creating a new post. -->
        <a
            href="upload.php"
            class="create-post-button <?= $currentPage === "upload.php" ? "active" : "" ?>"
        >
            <span class="create-post-icon">+</span>
            Create post
        </a>


        <!-- Logged-in user information and logout. -->
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


            <!-- Logout uses POST and CSRF protection. -->
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


    <!-- Main logged-in page content. -->
    <main class="user-main">


        <!-- Topbar with search, theme controls and user actions. -->
        <header class="user-topbar">


            <!-- Search sends the query to the Explore page. -->
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


            <!-- Topbar action buttons. -->
            <div class="user-topbar-actions">


                <!-- Dark/light theme toggle. -->
                <button
                    type="button"
                    class="theme-toggle"
                    id="theme-toggle"
                    aria-label="Toggle dark mode"
                    title="Toggle dark mode"
                >
                    ☾
                </button>


                <!-- Notifications button. -->
                <button
                    type="button"
                    class="topbar-icon"
                    aria-label="Notifications"
                    title="Notifications"
                >
                    ♡
                </button>


                <!-- Messages button. -->
                <button
                    type="button"
                    class="topbar-icon"
                    aria-label="Messages"
                    title="Messages"
                >
                    ✉
                </button>


                <!-- Show the profile picture or username initial as fallback. -->
                <a
                    href="profile.php"
                    class="topbar-profile"
                    aria-label="Profile"
                    title="Profile"
                >

                    <?php if (!empty($headerUser["profile_image"])): ?>

                        <img
                            src="/witchy/<?= htmlspecialchars(
                                $headerUser["profile_image"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            alt=""
                            class="topbar-profile-image"
                        >

                    <?php else: ?>

                        <span>
                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $headerUser["username"] ??
                                        $_SESSION["username"] ??
                                        "U",
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    <?php endif; ?>

                </a>

            </div>
        </header>