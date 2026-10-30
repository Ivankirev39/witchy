<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Admin Dashboard | Witchy";
$pageCss = "admin_dashboard.css";

$adminUsername = $_SESSION["username"] ?? "Admin";

$stats = [
    "users" => 0,
    "posts" => 0,
    "comments" => 0,
    "rules" => 0,
    "pinned" => 0,
    "hot" => 0
];

$recentActivity = [];
$dashboardError = "";

try {
    // Community statistics.
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

    // Moderation overview.
    $moderationStats = $conn->query("
        SELECT
            COALESCE(SUM(CASE WHEN is_sticky = 1 THEN 1 ELSE 0 END), 0)
                AS pinned_posts,
            COALESCE(SUM(CASE WHEN is_hot = 1 THEN 1 ELSE 0 END), 0)
                AS hot_posts
        FROM post
    ")->fetch(PDO::FETCH_ASSOC);

    $stats["pinned"] = (int) $moderationStats["pinned_posts"];
    $stats["hot"] = (int) $moderationStats["hot_posts"];

    // Latest posts and comments in chronological order.
    $activityQuery = $conn->query("
        SELECT *
        FROM (
            SELECT
                'post' AS activity_type,
                p.post_id AS activity_id,
                p.title AS activity_text,
                u.username,
                p.created_at
            FROM post p
            LEFT JOIN users u ON p.user_id = u.user_id

            UNION ALL

            SELECT
                'comment' AS activity_type,
                c.comment_id AS activity_id,
                c.comment AS activity_text,
                u.username,
                c.created_at
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
        ) AS activity
        ORDER BY created_at DESC
        LIMIT 5
    ");

    $recentActivity = $activityQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $dashboardError = "Some dashboard information could not be loaded.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-dashboard">

    <!-- Welcome section -->
    <header class="dashboard-welcome">
        <div>
            <span class="dashboard-eyebrow">WITCHY ADMINISTRATION</span>

            <h2>
                Welcome back,
                <?= htmlspecialchars($adminUsername, ENT_QUOTES, "UTF-8") ?>!
            </h2>

            <p>
                Here's an overview of what's happening in your Witchy community.
            </p>
        </div>

        <div class="dashboard-welcome-icon" aria-hidden="true">
            <span>✦</span>
        </div>
    </header>

    <?php if ($dashboardError !== ""): ?>
        <div class="dashboard-error" role="alert">
            <?= htmlspecialchars($dashboardError, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>


    <!-- Community statistics -->
    <section class="dashboard-section">

        <div class="dashboard-section-heading">
            <div>
                <h2>Community overview</h2>
                <p>A snapshot of your Witchy community.</p>
            </div>
        </div>

        <div class="dashboard-stats-grid">

            <article class="dashboard-stat-card">
                <div class="dashboard-stat-icon users-icon">♙</div>
                <div>
                    <span class="dashboard-stat-label">Registered users</span>
                    <strong class="dashboard-stat-number">
                        <?= number_format($stats["users"]) ?>
                    </strong>
                </div>
            </article>

            <article class="dashboard-stat-card">
                <div class="dashboard-stat-icon posts-icon">✧</div>
                <div>
                    <span class="dashboard-stat-label">Community posts</span>
                    <strong class="dashboard-stat-number">
                        <?= number_format($stats["posts"]) ?>
                    </strong>
                </div>
            </article>

            <article class="dashboard-stat-card">
                <div class="dashboard-stat-icon comments-icon">☽</div>
                <div>
                    <span class="dashboard-stat-label">Comments</span>
                    <strong class="dashboard-stat-number">
                        <?= number_format($stats["comments"]) ?>
                    </strong>
                </div>
            </article>

            <article class="dashboard-stat-card">
                <div class="dashboard-stat-icon rules-icon">✿</div>
                <div>
                    <span class="dashboard-stat-label">Community rules</span>
                    <strong class="dashboard-stat-number">
                        <?= number_format($stats["rules"]) ?>
                    </strong>
                </div>
            </article>

        </div>
    </section>


    <!-- Recent activity -->
    <section class="dashboard-section">

        <div class="dashboard-section-heading">
            <div>
                <h2>Recent activity</h2>
                <p>See what's been happening in the community.</p>
            </div>

            <a href="content.php" class="dashboard-text-link">
                View all content →
            </a>
        </div>

        <div class="dashboard-panel">

            <?php if (empty($recentActivity)): ?>

                <div class="dashboard-empty">
                    <span>✧</span>
                    <p>No community activity yet.</p>
                </div>

            <?php else: ?>

                <div class="dashboard-activity-list">

                    <?php foreach ($recentActivity as $activity): ?>

                        <?php
                        $isPost = $activity["activity_type"] === "post";

                        $activityDate = !empty($activity["created_at"])
                            ? strtotime($activity["created_at"])
                            : false;
                        ?>

                        <article class="dashboard-activity-item">

                            <div class="activity-icon <?= $isPost
                                ? "activity-post-icon"
                                : "activity-comment-icon" ?>">
                                <?= $isPost ? "✧" : "☽" ?>
                            </div>

                            <div class="activity-details">

                                <div class="activity-description">
                                    <strong>
                                        <?= htmlspecialchars(
                                            $activity["username"] ?? "Unknown member",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>

                                    <?= $isPost
                                        ? " published a new post"
                                        : " added a comment" ?>
                                </div>

                                <p class="activity-content">
                                    <?= htmlspecialchars(
                                        $activity["activity_text"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </p>

                                <?php if ($activityDate !== false): ?>
                                    <time
                                        class="activity-date"
                                        datetime="<?= date("c", $activityDate) ?>"
                                    >
                                        <?= date("M j, Y · g:i a", $activityDate) ?>
                                    </time>
                                <?php endif; ?>

                            </div>

                            <span class="activity-type <?= $isPost
                                ? "post-type"
                                : "comment-type" ?>">
                                <?= $isPost ? "Post" : "Comment" ?>
                            </span>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>


    <!-- Quick actions -->
    <section class="dashboard-section">

        <div class="dashboard-section-heading">
            <div>
                <h2>Quick actions</h2>
                <p>Access your main administration tools.</p>
            </div>
        </div>

        <div class="dashboard-actions-grid">

            <a href="users.php" class="dashboard-action-card">
                <div class="dashboard-action-icon">♙</div>

                <div>
                    <h3>Manage users</h3>
                    <p>Review registered accounts and member information.</p>
                </div>

                <span class="dashboard-action-arrow">→</span>
            </a>

            <a href="content.php" class="dashboard-action-card">
                <div class="dashboard-action-icon">✧</div>

                <div>
                    <h3>Manage content</h3>
                    <p>Review community posts and comments.</p>
                </div>

                <span class="dashboard-action-arrow">→</span>
            </a>

            <a href="rules.php" class="dashboard-action-card">
                <div class="dashboard-action-icon">☽</div>

                <div>
                    <h3>Manage rules</h3>
                    <p>Maintain Witchy's community guidelines.</p>
                </div>

                <span class="dashboard-action-arrow">→</span>
            </a>

        </div>
    </section>


    <!-- Moderation overview -->
    <section class="dashboard-section">

        <div class="dashboard-section-heading">
            <div>
                <h2>Moderation overview</h2>
                <p>A quick look at highlighted community content.</p>
            </div>

            <a href="content.php" class="dashboard-text-link">
                Open content →
            </a>
        </div>

        <div class="dashboard-moderation-grid">

            <article class="dashboard-moderation-card">
                <div class="moderation-icon pinned-icon">⌖</div>

                <div>
                    <strong><?= number_format($stats["pinned"]) ?></strong>
                    <span>Pinned posts</span>
                </div>
            </article>

            <article class="dashboard-moderation-card">
                <div class="moderation-icon hot-icon">♨</div>

                <div>
                    <strong><?= number_format($stats["hot"]) ?></strong>
                    <span>Hot posts</span>
                </div>
            </article>

        </div>

    </section>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
