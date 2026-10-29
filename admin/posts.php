<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Manage Posts | Witchy";

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

<section>
    <h2>Manage Posts</h2>
    <p class="admin-muted">
        Review community posts and their moderation status.
    </p>

    <form method="GET" action="posts.php" class="admin-form">
        <label for="search">Search posts</label>
        <input
            type="search"
            id="search"
            name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            placeholder="Search title, topic, or author"
        >
        <button type="submit">Search</button>

        <?php if ($search !== ""): ?>
            <a href="posts.php">Clear search</a>
        <?php endif; ?>
    </form>

    <?php if ($error !== ""): ?>
        <p class="admin-error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php else: ?>
        <p class="admin-muted">
            <?= count($posts) ?> post(s) found.
        </p>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Topic</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6">No posts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td><?= (int) $post["post_id"] ?></td>
                                <td>
                                    <?= htmlspecialchars(
                                        $post["title"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $post["username"] ?? "Unknown",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars(
                                        $post["topic"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>
                                <td>
                                    <?php
                                    $status = [];

                                    if ((int) $post["is_sticky"] === 1) {
                                        $status[] = "Pinned";
                                    }

                                    if ((int) $post["is_hot"] === 1) {
                                        $status[] = "Hot";
                                    }

                                    echo htmlspecialchars(
                                        $status ? implode(", ", $status) : "Normal",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>
                                <td>
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
    <?php endif; ?>
</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
