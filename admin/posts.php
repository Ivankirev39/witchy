<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Manage Posts | Witchy";
$pageCss = "admin_posts.css";

$search = trim($_GET["search"] ?? "");
$posts = [];
$error = "";

try {
    $sql = "
        SELECT
            p.post_id,
            p.title,
            p.topic,
            p.is_hot,
            p.is_sticky,
            p.created_at,
            u.username
        FROM post p
        LEFT JOIN users u ON p.user_id = u.user_id
    ";

    if ($search !== "") {
        $sql .= "
            WHERE p.title LIKE ?
               OR p.topic LIKE ?
               OR u.username LIKE ?
        ";
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($sql);

    if ($search !== "") {
        $term = "%" . $search . "%";
        $stmt->execute([$term, $term, $term]);
    } else {
        $stmt->execute();
    }

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin posts page failed: " . $e->getMessage());
    $error = "Posts could not be loaded. Please try again later.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-posts-page">

    <div class="posts-page-heading">
        <div>
            <span class="posts-eyebrow">ADMINISTRATION</span>
            <h1>Manage Posts</h1>
            <p>
                Browse community posts and review their current moderation status.
            </p>
        </div>

        <div class="posts-heading-decoration" aria-hidden="true">
            ✧
        </div>
    </div>

    <div class="posts-overview">
        <div class="posts-overview-icon" aria-hidden="true">✦</div>

        <div>
            <span class="posts-overview-label">COMMUNITY CONTENT</span>
            <h2>Post directory</h2>
            <p>
                Review published community posts, their authors, topics,
                and moderation labels. This overview does not make changes
                to member content.
            </p>
        </div>
    </div>

    <div class="posts-section-heading">
        <div>
            <h2>Community posts</h2>
            <p>Search and browse the latest posts.</p>
        </div>

        <span class="posts-count">
            <?= count($posts) ?> found
        </span>
    </div>

    <div class="posts-search-panel">
        <form method="GET" action="posts.php" class="posts-search-form">
            <div class="posts-search-field">
                <label for="search">Search community posts</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="Search title, topic, or author..."
                >
            </div>

            <button type="submit" class="posts-search-button">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="posts.php" class="posts-clear-button">
                    Clear search
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($error !== ""): ?>

        <div class="posts-message posts-error" role="alert">
            <span aria-hidden="true">!</span>
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php else: ?>

        <div class="posts-table-panel">

            <div class="posts-table-heading">
                <div>
                    <h3>Published content</h3>
                    <p>
                        <?php if ($search !== ""): ?>
                            Results matching
                            <strong>
                                "<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                            </strong>
                        <?php else: ?>
                            Posts ordered by creation date
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="posts-table-wrap">
                <table class="admin-table posts-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Post</th>
                            <th>Author</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="6" class="posts-empty">
                                    <span aria-hidden="true">✧</span>
                                    <strong>No posts found</strong>
                                    <p>
                                        Try another search term or clear your search.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td class="posts-id">
                                        #<?= (int) $post["post_id"] ?>
                                    </td>

                                    <td class="posts-title">
                                        <?= htmlspecialchars(
                                            $post["title"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td class="posts-author">
                                        <?= htmlspecialchars(
                                            $post["username"] ?? "Unknown",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="posts-topic">
                                            <?= htmlspecialchars(
                                                $post["topic"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="posts-status-list">
                                            <?php if ((int) $post["is_sticky"] === 1): ?>
                                                <span class="posts-status posts-status-pinned">
                                                    Pinned
                                                </span>
                                            <?php endif; ?>

                                            <?php if ((int) $post["is_hot"] === 1): ?>
                                                <span class="posts-status posts-status-hot">
                                                    Hot
                                                </span>
                                            <?php endif; ?>

                                            <?php if (
                                                (int) $post["is_sticky"] !== 1 &&
                                                (int) $post["is_hot"] !== 1
                                            ): ?>
                                                <span class="posts-status posts-status-normal">
                                                    Normal
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="posts-date">
                                        <?= htmlspecialchars(
                                            $post["created_at"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="posts-table-footer">
                Showing <?= count($posts) ?> post(s)
            </div>

        </div>

    <?php endif; ?>

    <div class="posts-bottom-note">
        <span aria-hidden="true">✧</span>
        <p>
            Pinned and hot labels reflect the current post settings.
            Reviewing posts here does not modify their status or content.
        </p>
    </div>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
