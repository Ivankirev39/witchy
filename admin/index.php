
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

$statsError = "";

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

<section class="admin-dashboard">

    <header>
        <h2>Dashboard</h2>
        <p class="admin-muted">
            Welcome to Witchy's administration area.
        </p>
    </header>

    <?php if ($statsError !== ""): ?>
        <p class="admin-error" role="alert">
            <?= htmlspecialchars($statsError, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <section>
        <h2>Community overview</h2>

        <div class="admin-grid">

            <article class="admin-card">
                <h3>Registered users</h3>
                <p><strong><?= $stats["users"] ?></strong></p>
            </article>

            <article class="admin-card">
                <h3>Posts</h3>
                <p><strong><?= $stats["posts"] ?></strong></p>
            </article>

            <article class="admin-card">
                <h3>Comments</h3>
                <p><strong><?= $stats["comments"] ?></strong></p>
            </article>

            <article class="admin-card">
                <h3>Community rules</h3>
                <p><strong><?= $stats["rules"] ?></strong></p>
            </article>

        </div>
    </section>

    <section style="margin-top: 28px;">
        <h2>Manage Witchy</h2>

        <div class="admin-grid">

            <article class="admin-card">
                <h3>User management</h3>
                <p>
                    Review registered accounts and moderation status.
                </p>
                <a href="users.php">Manage users</a>
            </article>

            <article class="admin-card">
                <h3>Content moderation</h3>
                <p>
                    Review community posts and comments.
                </p>
                <a href="content.php">Manage content</a>
            </article>

            <article class="admin-card">
                <h3>Community rules</h3>
                <p>
                    Maintain the rules shown to the community.
                </p>
                <a href="rules.php">Manage rules</a>
            </article>

        </div>
    </section>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>