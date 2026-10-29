<?php
require_once __DIR__ . "/../../includes/csrf.php";
$pageTitle = $pageTitle ?? "Admin | Witchy";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></title>
    <link rel="stylesheet" href="/witchy/admin/css/admin.css">
        <?php if (!empty($pageCss)): ?>
        <link
            rel="stylesheet"
            href="/witchy/admin/css/<?= htmlspecialchars(
                $pageCss,
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >
    <?php endif; ?>
</head>
<body>
<header class="admin-header">
    <div class="admin-header-inner">
        <h1 class="admin-brand">
            <a href="/witchy/admin/index.php">Witchy Admin</a>
        </h1>

        <nav class="admin-nav" aria-label="Admin navigation">
            <a href="/witchy/admin/index.php">Dashboard</a>
            <a href="/witchy/admin/users.php">Users</a>
            <a href="/witchy/admin/content.php">Content</a>
            <a href="/witchy/admin/rules.php">Rules</a>
            <a href="/witchy/feed.php">Back to Witchy</a>
            <form method="POST" action="/witchy/admin/logout.php" class="admin-logout-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                >
                <button type="submit" class="admin-logout-button">Log out</button>
            </form>
        </nav>
    </div>
</header>
<main class="admin-main">
