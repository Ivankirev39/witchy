<?php
require_once __DIR__ . "/../../config/bootstrap.php";
require_once __DIR__ . "/../../includes/csrf.php";

$pageTitle = $pageTitle ?? "Admin | Witchy";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>admin/css/admin.css">

    <?php if (!empty($pageCss)): ?>
        <link
            rel="stylesheet"
            href="<?= BASE_URL ?>admin/css/<?= htmlspecialchars($pageCss, ENT_QUOTES, "UTF-8") ?>"
        >
    <?php endif; ?>

    <script src="<?= BASE_URL ?>admin/js/admin-mobile-nav.js" defer></script>
</head>

<body>

<header class="admin-header">
    <div class="admin-header-inner">

        <h1 class="admin-brand">
            <a href="<?= BASE_URL ?>admin/index.php">Witchy Admin</a>
        </h1>

        <nav
            class="admin-nav"
            id="admin-navigation"
            aria-label="Admin navigation"
        >
            <a href="<?= BASE_URL ?>admin/index.php">Dashboard</a>
            <a href="<?= BASE_URL ?>admin/users.php">Users</a>
            <a href="<?= BASE_URL ?>admin/content.php">Content</a>
            <a href="<?= BASE_URL ?>admin/reports.php">Reports</a>
            <a href="<?= BASE_URL ?>admin/rules.php">Rules</a>
            <a href="<?= BASE_URL ?>feed.php">Back to Witchy</a>

            <form
                method="POST"
                action="<?= BASE_URL ?>admin/logout.php"
                class="admin-logout-form"
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >

                <button type="submit" class="admin-logout-button">
                    Log out
                </button>
            </form>
        </nav>

        <button
            type="button"
            class="admin-mobile-menu-toggle"
            id="admin-mobile-menu-toggle"
            aria-label="Open admin navigation menu"
            aria-expanded="false"
            aria-controls="admin-navigation"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>
</header>

<main class="admin-main">
