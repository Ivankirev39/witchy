<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Admin Dashboard | Witchy";

$stats = [
    "users" => 0,
    "posts" => 0,
    "comments" => 0,
    "rules" => 0
];

try {
    $stats["users"] = (int) $conn
        ->query("SELECT COUNT(*) FROM users")
        ->fetchColumn();

    $stats["posts"] = (int) $conn
        ->query("SELECT COUNT(*) FROM post")
        ->fetchColumn();

    $stats["comments"] = (int) $conn
        ->query("SELECT COUNT(*) FROM comment")
        ->fetchColumn();

    $stats["rules"] = (int) $conn
        ->query("SELECT COUNT(*) FROM rule")
        ->fetchColumn();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $statsError = "Dashboard statistics could not be loaded.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section>
    <h2>Dashboard</h2>
    <p class="admin-muted">
        Welcome to Witchy's administration area.
    </p>

    <?php if (isset($statsError)): ?>
        <p class="admin-error" role="alert">
            <?= htmlspecialchars($statsError, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <div class="admin-grid">
        <article class="admin-card">
            <h3>Registered users</h3>
            <p><strong><?= $stats["users"] ?></strong></p>
            <a href="users.php">Manage users</a>
        </article>

        <article class="admin-card">
            <h3>Posts</h3>
            <p><strong><?= $stats["posts"] ?></strong></p>
            <a href="posts.php">Moderate posts</a>
        </article>

        <article class="admin-card">
            <h3>Comments</h3>
            <p><strong><?= $stats["comments"] ?></strong></p>
        </article>

        <article class="admin-card">
            <h3>Community rules</h3>
            <p><strong><?= $stats["rules"] ?></strong></p>
            <a href="rules.php">Manage rules</a>
        </article>
    </div>

    <section style="margin-top: 28px;">
        <h2>Administration</h2>
        <div class="admin-grid">
            <article class="admin-card">
                <h3>User management</h3>
                <p>Review registered accounts and moderation status.</p>
                <a href="users.php">Open users</a>
            </article>

            <article class="admin-card">
                <h3>Post moderation</h3>
                <p>Review community posts and moderation needs.</p>
                <a href="posts.php">Open posts</a>
            </article>

            <article class="admin-card">
                <h3>Community rules</h3>
                <p>Maintain the rules shown to the community.</p>
                <a href="rules.php">Open rules</a>
            </article>

            <article class="admin-card">
                <h3>Support</h3>
                <p>Access the administration support section.</p>
                <a href="contact.php">Open support</a>
            </article>

            <article class="admin-card">
                <h3>Site settings</h3>
                <p>Review available site configuration options.</p>
                <a href="site_settings.php">Open settings</a>
            </article>

            <article class="admin-card">
                <h3>Styling</h3>
                <p>Open the site's administration styling section.</p>
                <a href="styling.php">Open styling</a>
            </article>
        </div>
    </section>
</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
