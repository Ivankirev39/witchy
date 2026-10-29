<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Content Management | Witchy";
$pageCss = "admin_content.css";

$activeTab = $_GET["tab"] ?? "posts";

if (!in_array($activeTab, ["posts", "comments"], true)) {
    $activeTab = "posts";
}

$search = trim($_GET["search"] ?? "");

$posts = [];
$comments = [];
$postCount = 0;
$commentCount = 0;
$errorMessage = "";

try {
    // Load total counts for both tabs.
    $postCount = (int) $conn->query(
        "SELECT COUNT(*) FROM post"
    )->fetchColumn();

    $commentCount = (int) $conn->query(
        "SELECT COUNT(*) FROM comment"
    )->fetchColumn();

    if ($activeTab === "posts") {
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
                WHERE p.title LIKE :search_title
                OR p.topic LIKE :search_topic
                OR u.username LIKE :search_user
            ";
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $conn->prepare($sql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";
        
            $stmt->bindValue(":search_title", $searchTerm);
            $stmt->bindValue(":search_topic", $searchTerm);
            $stmt->bindValue(":search_user", $searchTerm);
        }

        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $sql = "
            SELECT
                c.comment_id,
                c.comment,
                c.created_at,
                c.media_file,
                c.media_type,
                u.username,
                p.post_id,
                p.title AS post_title
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
            LEFT JOIN post p ON c.post_id = p.post_id
        ";

        if ($search !== "") {
            $sql .= "
                WHERE c.comment LIKE :search_comment
                OR u.username LIKE :search_user
                OR p.title LIKE :search_post
            ";
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $conn->prepare($sql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";
        
            $stmt->bindValue(":search_comment", $searchTerm);
            $stmt->bindValue(":search_user", $searchTerm);
            $stmt->bindValue(":search_post", $searchTerm);
        }

        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorMessage = "Content could not be loaded. Please try again.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page">

    <header class="admin-content-heading">
        <h2>Content management</h2>
        <p>
            Review posts and comments shared by the Witchy community.
        </p>
    </header>

    <?php if ($errorMessage !== ""): ?>
        <div class="admin-content-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <nav class="admin-content-tabs" aria-label="Content categories">
        <a
            href="content.php?tab=posts"
            class="<?= $activeTab === "posts" ? "active" : "" ?>"
            <?= $activeTab === "posts" ? 'aria-current="page"' : "" ?>
        >
            Posts (<?= $postCount ?>)
        </a>

        <a
            href="content.php?tab=comments"
            class="<?= $activeTab === "comments" ? "active" : "" ?>"
            <?= $activeTab === "comments" ? 'aria-current="page"' : "" ?>
        >
            Comments (<?= $commentCount ?>)
        </a>
    </nav>

    <form method="GET" action="content.php" class="admin-content-search">

        <input
            type="hidden"
            name="tab"
            value="<?= htmlspecialchars($activeTab, ENT_QUOTES, "UTF-8") ?>"
        >

        <input
            type="search"
            name="search"
            placeholder="<?= $activeTab === "posts"
                ? "Search posts, topics or authors..."
                : "Search comments, authors or posts..." ?>"
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
        >

        <button type="submit">Search</button>

        <?php if ($search !== ""): ?>
            <a href="content.php?tab=<?= htmlspecialchars($activeTab, ENT_QUOTES, "UTF-8") ?>">
                Clear search
            </a>
        <?php endif; ?>

    </form>

    <?php if ($activeTab === "posts"): ?>

        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Community posts</h3>
                <p>
                    Browse posts published by Witchy members.
                </p>
            </header>

            <?php if (empty($posts)): ?>

                <div class="admin-content-empty">
                    No posts found.
                </div>

            <?php else: ?>

                <div class="admin-content-table-wrapper">
                    <table class="admin-content-table">
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
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?= (int) $post["post_id"] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $post["title"] ?? "Untitled",
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
                                            $post["topic"] ?? "Uncategorized",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php if ($post["is_sticky"]): ?>
                                            <span class="admin-content-status pinned">
                                                Pinned
                                            </span>
                                        <?php elseif ($post["is_hot"]): ?>
                                            <span class="admin-content-status hot">
                                                Hot
                                            </span>
                                        <?php else: ?>
                                            <span class="admin-content-status">
                                                Normal
                                            </span>
                                        <?php endif; ?>
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
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

    <?php else: ?>

        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Community comments</h3>
                <p>
                    Browse comments and see which posts they belong to.
                </p>
            </header>

            <?php if (empty($comments)): ?>

                <div class="admin-content-empty">
                    No comments found.
                </div>

            <?php else: ?>

                <div class="admin-content-table-wrapper">
                    <table class="admin-content-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Comment</th>
                                <th>Author</th>
                                <th>Related post</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <tr>
                                    <td>
                                        <?= (int) $comment["comment_id"] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $comment["comment"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $comment["username"] ?? "Unknown",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $comment["post_title"] ?? "Post unavailable",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $comment["created_at"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
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

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
