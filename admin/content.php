<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

$pageTitle = "Content Management | Witchy";
$pageCss = "admin_content.css";

$activeTab = $_GET["tab"] ?? "posts";

if (!in_array($activeTab, ["posts", "comments"], true)) {
    $activeTab = "posts";
}

$search = trim($_GET["search"] ?? "");

$perPage = 10;
$currentPage = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);

if (!$currentPage || $currentPage < 1) {
    $currentPage = 1;
}

$posts = [];
$comments = [];
$postCount = 0;
$commentCount = 0;
$resultCount = 0;
$totalPages = 1;
$errorMessage = "";

try {
    // Total counts for the tabs.
    $postCount = (int) $conn->query(
        "SELECT COUNT(*) FROM post"
    )->fetchColumn();

    $commentCount = (int) $conn->query(
        "SELECT COUNT(*) FROM comment"
    )->fetchColumn();

    if ($activeTab === "posts") {

        $whereSql = "";

        if ($search !== "") {
            $whereSql = "
                WHERE p.title LIKE :search_title
                OR p.topic LIKE :search_topic
                OR u.username LIKE :search_user
            ";
        }

        // Count matching posts.
        $countSql = "
            SELECT COUNT(*)
            FROM post p
            LEFT JOIN users u ON p.user_id = u.user_id
            $whereSql
        ";

        $countStmt = $conn->prepare($countSql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";

            $countStmt->bindValue(":search_title", $searchTerm);
            $countStmt->bindValue(":search_topic", $searchTerm);
            $countStmt->bindValue(":search_user", $searchTerm);
        }

        $countStmt->execute();
        $resultCount = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($resultCount / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        // Load the current page of posts.
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
            $whereSql
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $conn->prepare($sql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";

            $stmt->bindValue(":search_title", $searchTerm);
            $stmt->bindValue(":search_topic", $searchTerm);
            $stmt->bindValue(":search_user", $searchTerm);
        }

        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        $whereSql = "";

        if ($search !== "") {
            $whereSql = "
                WHERE c.comment LIKE :search_comment
                OR u.username LIKE :search_user
                OR p.title LIKE :search_post
            ";
        }

        // Count matching comments.
        $countSql = "
            SELECT COUNT(*)
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
            LEFT JOIN post p ON c.post_id = p.post_id
            $whereSql
        ";

        $countStmt = $conn->prepare($countSql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";

            $countStmt->bindValue(":search_comment", $searchTerm);
            $countStmt->bindValue(":search_user", $searchTerm);
            $countStmt->bindValue(":search_post", $searchTerm);
        }

        $countStmt->execute();
        $resultCount = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($resultCount / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        // Load the current page of comments.
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
            $whereSql
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $conn->prepare($sql);

        if ($search !== "") {
            $searchTerm = "%" . $search . "%";

            $stmt->bindValue(":search_comment", $searchTerm);
            $stmt->bindValue(":search_user", $searchTerm);
            $stmt->bindValue(":search_post", $searchTerm);
        }

        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    $errorMessage = "Content could not be loaded. Please try again.";
}

// Preserve the selected tab and search when changing pages.
$paginationBase = "content.php?" . http_build_query([
    "tab" => $activeTab,
    "search" => $search
]);

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

                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    aria-label="Review post: <?= htmlspecialchars($post["title"] ?? "Untitled", ENT_QUOTES, "UTF-8") ?>"
                                    data-href="view_post.php?id=<?= (int) $post["post_id"] ?>"
                                >

                                    <td><?= (int) $post["post_id"] ?></td>

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

                <div class="admin-table-wrapper">

                    <table class="admin-table">

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

                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_comment.php?id=<?= (int) $comment["comment_id"] ?>"
                                    aria-label="Review comment <?= (int) $comment["comment_id"] ?>"
                                >

                                    <td><?= (int) $comment["comment_id"] ?></td>

                                    <td>
                                        <?= htmlspecialchars(
                                            mb_strimwidth($comment["comment"] ?? "", 0, 90, "..."),
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
                                            $comment["post_title"] ?? "Unavailable",
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


    <?php if ($errorMessage === "" && $resultCount > 0 && $totalPages > 1): ?>

        <nav class="admin-pagination" aria-label="Content pages">

            <span class="admin-pagination-info">
                Page <?= $currentPage ?> of <?= $totalPages ?>
                · <?= $resultCount ?> results
            </span>

            <div class="admin-pagination-links">

                <?php if ($currentPage > 1): ?>
                    <a href="<?= htmlspecialchars(
                        $paginationBase . "&page=" . ($currentPage - 1),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                ?>

                <?php for ($page = $startPage; $page <= $endPage; $page++): ?>

                    <?php if ($page === $currentPage): ?>
                        <span class="active" aria-current="page">
                            <?= $page ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars(
                            $paginationBase . "&page=" . $page,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>">
                            <?= $page ?>
                        </a>
                    <?php endif; ?>

                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= htmlspecialchars(
                        $paginationBase . "&page=" . ($currentPage + 1),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>

        </nav>

    <?php endif; ?>

</section>


<script>
document.querySelectorAll(".admin-clickable-row").forEach(row => {

    function openPost() {
        window.location.href = row.dataset.href;
    }

    row.addEventListener("click", openPost);

    row.addEventListener("keydown", event => {

        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            openPost();
        }

    });

});
</script>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
