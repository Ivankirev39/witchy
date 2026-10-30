<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Review User | Witchy";
$pageCss = "admin_users.css";

$userId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$user = null;
$posts = [];
$comments = [];
$activeBan = null;
$errorMessage = "";

if (!$userId || $userId < 1) {
    $errorMessage = "Invalid user selected.";
} else {
    try {
        // Load user account
        $stmt = $conn->prepare("
            SELECT
                user_id,
                username,
                email,
                birthdate,
                rank,
                bio,
                profile_image,
                cover_image
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([":user_id" => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errorMessage = "This user could not be found.";
        } else {
            // Load recent posts
            $stmt = $conn->prepare("
                SELECT post_id, title, topic, created_at
                FROM post
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 10
            ");

            $stmt->execute([":user_id" => $userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Load recent comments
            $stmt = $conn->prepare("
                SELECT
                    c.comment_id,
                    c.comment,
                    c.created_at,
                    c.post_id,
                    p.title AS post_title
                FROM comment c
                LEFT JOIN post p ON c.post_id = p.post_id
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");

            $stmt->execute([":user_id" => $userId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check for an active ban
            $stmt = $conn->prepare("
                SELECT ban_id, reason, banned_at, expires_at
                FROM ban
                WHERE user_id = :user_id
                  AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY banned_at DESC
                LIMIT 1
            ");

            $stmt->execute([":user_id" => $userId]);
            $activeBan = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Admin user review error: " . $e->getMessage());
        $errorMessage = "The user profile could not be loaded. Please try again.";
    }
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page admin-user-review">

    <header class="admin-content-heading">
        <h2>User profile review</h2>
        <p>Review account information and recent community activity.</p>
    </header>

    <p class="admin-user-back-link">
        <a href="/witchy/admin/users.php">&larr; Back to users</a>
    </p>

    <?php if ($errorMessage !== ""): ?>

        <div class="admin-content-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php elseif ($user): ?>

        <!-- Profile overview -->
        <section class="admin-content-panel admin-user-profile-card">

            <div class="admin-user-cover">
                <?php if (!empty($user["cover_image"])): ?>
                    <img
                        src="/witchy/uploads/profiles/<?= rawurlencode(basename($user["cover_image"])) ?>"
                        alt="User cover image"
                    >
                <?php else: ?>
                    <div class="admin-user-cover-placeholder"></div>
                <?php endif; ?>
            </div>

            <div class="admin-user-profile-body">

                <div class="admin-user-identity">

                    <div class="admin-user-avatar">
                        <?php if (!empty($user["profile_image"])): ?>
                            <img
                                src="/witchy/uploads/profiles/<?= rawurlencode(basename($user["profile_image"])) ?>"
                                alt="Profile picture of <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                            >
                        <?php else: ?>
                            <span>
                                <?= htmlspecialchars(strtoupper(substr($user["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="admin-user-heading">
                        <h3>
                            <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>
                        </h3>

                        <span class="admin-user-role <?= $user["rank"] === "admin" ? "admin-user-role-admin" : "" ?>">
                            <?= htmlspecialchars(ucfirst($user["rank"]), ENT_QUOTES, "UTF-8") ?>
                        </span>

                        <span class="admin-user-id">
                            User ID: <?= (int) $user["user_id"] ?>
                        </span>
                    </div>

                </div>

                <div class="admin-user-details">

                    <div class="admin-user-detail">
                        <span class="admin-user-detail-label">Email address</span>
                        <span class="admin-user-detail-value">
                            <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="admin-user-detail">
                        <span class="admin-user-detail-label">Birthdate</span>
                        <span class="admin-user-detail-value">
                            <?= htmlspecialchars($user["birthdate"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="admin-user-detail admin-user-bio">
                        <span class="admin-user-detail-label">About this user</span>
                        <div class="admin-user-detail-value">
                            <?= nl2br(htmlspecialchars($user["bio"] ?: "This user has not added a bio.", ENT_QUOTES, "UTF-8")) ?>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Account status -->
        <section class="admin-content-panel admin-user-status-panel">

            <header class="admin-content-panel-header">
                <h3>Account status</h3>
                <p>Current moderation status for this account.</p>
            </header>

            <div class="admin-user-status-content">

                <?php if ($activeBan): ?>

                    <div class="admin-user-status admin-user-status-banned">
                        <span class="admin-user-status-indicator"></span>
                        <strong>Currently banned</strong>
                    </div>

                    <div class="admin-user-ban-details">
                        <p>
                            <strong>Reason:</strong>
                            <?= htmlspecialchars($activeBan["reason"], ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <p>
                            <strong>Banned at:</strong>
                            <?= htmlspecialchars($activeBan["banned_at"], ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <p>
                            <strong>Expires:</strong>
                            <?= htmlspecialchars($activeBan["expires_at"] ?? "No expiry", ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </div>

                <?php else: ?>

                    <div class="admin-user-status admin-user-status-active">
                        <span class="admin-user-status-indicator"></span>
                        <strong>Active account</strong>
                    </div>

                    <p class="admin-user-status-note">
                        No active ban found for this account.
                    </p>

                <?php endif; ?>

            </div>
        </section>

        <!-- Recent posts -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Recent posts</h3>
                <p>Latest posts created by this user.</p>
            </header>

            <?php if (empty($posts)): ?>

                <p class="admin-content-empty">This user has not created any posts.</p>

            <?php else: ?>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Topic</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_post.php?id=<?= (int) $post["post_id"] ?>&from=user&return_user_id=<?= (int) $user["user_id"] ?>"
                                    aria-label="Review post <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <td><?= (int) $post["post_id"] ?></td>
                                    <td><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($post["topic"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

        <!-- Recent comments -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Recent comments</h3>
                <p>Latest comments written by this user.</p>
            </header>

            <?php if (empty($comments)): ?>

                <p class="admin-content-empty">This user has not written any comments.</p>

            <?php else: ?>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Comment</th>
                                <th>Related post</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <?php
                                $commentPreview = $comment["comment"];

                                if (function_exists("mb_strimwidth")) {
                                    $commentPreview = mb_strimwidth($commentPreview, 0, 90, "...");
                                } elseif (strlen($commentPreview) > 90) {
                                    $commentPreview = substr($commentPreview, 0, 87) . "...";
                                }
                                ?>

                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_comment.php?id=<?= (int) $comment["comment_id"] ?>&from=user&return_user_id=<?= (int) $user["user_id"] ?>"
                                    aria-label="Review comment <?= (int) $comment["comment_id"] ?>"
                                >
                                    <td><?= (int) $comment["comment_id"] ?></td>

                                    <td>
                                        <?= htmlspecialchars($commentPreview, ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($comment["post_title"] ?? "Unavailable", ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</section>

<script>
document.querySelectorAll(".admin-clickable-row").forEach(row => {
    row.addEventListener("click", () => {
        window.location.href = row.dataset.href;
    });

    row.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            window.location.href = row.dataset.href;
        }
    });
});
</script>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
