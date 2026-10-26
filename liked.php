<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";
$pageTitle = "Liked Posts | Witchy";
$pageCss = "liked.css";
$user_id = (int) $_SESSION["user_id"];
$topics = ["Spells", "Altars", "Tarot", "Herbs", "Crystals", "Books", "Artwork", "Occult Studies", "Others"];
$sortOptions = [
    "newest_post" => "Newest posts",
    "oldest_post" => "Oldest posts",
    "title_asc" => "Title A–Z"
];
$sort = $_GET["sort"] ?? "newest_post";
$topic = trim($_GET["topic"] ?? "");
if (!isset($sortOptions[$sort])) {
    $sort = "newest_post";
}
if (!in_array($topic, $topics, true)) {
    $topic = "";
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }
    $post_id = (int) ($_POST["post_id"] ?? 0);
    if ($post_id > 0) {
        $deleteStmt = $conn->prepare("
            DELETE FROM post_like
            WHERE user_id = ? AND post_id = ?
        ");
        $deleteStmt->execute([$user_id, $post_id]);
    }
    $params = http_build_query(["sort" => $sort, "topic" => $topic]);
    header("Location: liked.php?" . $params);
    exit;
}
$sql = "
    SELECT
        p.post_id,
        p.title,
        p.description,
        p.topic,
        p.image,
        p.created_at,
        u.username,
        pm.file AS media_file,
        pm.media_type
    FROM post_like pl
    INNER JOIN post p ON p.post_id = pl.post_id
    INNER JOIN users u ON u.user_id = p.user_id
    LEFT JOIN post_media pm ON pm.media_id = (
        SELECT pm2.media_id
        FROM post_media pm2
        WHERE pm2.post_id = p.post_id
        ORDER BY pm2.sort_order ASC, pm2.media_id ASC
        LIMIT 1
    )
    WHERE pl.user_id = ?
";
$params = [$user_id];
if ($topic !== "") {
    $sql .= " AND p.topic = ?";
    $params[] = $topic;
}
if ($sort === "oldest_post") {
    $sql .= " ORDER BY p.created_at ASC";
} elseif ($sort === "title_asc") {
    $sql .= " ORDER BY p.title ASC";
} else {
    $sql .= " ORDER BY p.created_at DESC";
}
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$likedPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$likedCount = count($likedPosts);
require_once __DIR__ . "/includes/user_header.php";
?>
<section class="saved-page liked-page">
    <header class="saved-header">
        <h1>Liked Posts</h1>
        <p><?= $likedCount ?> <?= $likedCount === 1 ? "liked post" : "liked posts" ?><?= $topic !== "" ? " in " . htmlspecialchars($topic, ENT_QUOTES, "UTF-8") : "" ?>.</p>
    </header>
    <nav class="saved-topics" aria-label="Liked post topics">
        <a href="liked.php?<?= htmlspecialchars(http_build_query(["sort" => $sort]), ENT_QUOTES, "UTF-8") ?>" class="<?= $topic === "" ? "active" : "" ?>">All</a>
        <?php foreach ($topics as $topicOption): ?>
            <a href="liked.php?<?= htmlspecialchars(http_build_query(["topic" => $topicOption, "sort" => $sort]), ENT_QUOTES, "UTF-8") ?>" class="<?= $topic === $topicOption ? "active" : "" ?>">
                <?= htmlspecialchars($topicOption, ENT_QUOTES, "UTF-8") ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="saved-sort">
        <label for="liked-sort">Sort by:</label>
        <select id="liked-sort" onchange="window.location.href=this.value">
            <?php foreach ($sortOptions as $value => $label): ?>
                <option value="liked.php?<?= htmlspecialchars(http_build_query(["sort" => $value, "topic" => $topic]), ENT_QUOTES, "UTF-8") ?>" <?= $sort === $value ? "selected" : "" ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, "UTF-8") ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <section class="saved-content">
        <?php if (empty($likedPosts)): ?>
            <div class="saved-empty">
                <h2>No liked posts found</h2>
                <p><?= $topic !== "" ? "You haven't liked any posts in this topic yet." : "Posts you like will appear here." ?></p>
                <a href="explore.php" class="saved-empty-button">Explore posts</a>
            </div>
        <?php else: ?>
            <div class="saved-grid">
                <?php foreach ($likedPosts as $post): ?>
                    <?php
                    $postId = (int) $post["post_id"];
                    $mediaFile = $post["media_file"] ?: $post["image"];
                    $mediaPath = $mediaFile ? "uploads/posts/" . rawurlencode(basename($mediaFile)) : "";
                    $isVideo = $post["media_file"] && $post["media_type"] === "video";
                    ?>
                    <article class="saved-card liked-card">
                        <form action="liked.php?<?= htmlspecialchars(http_build_query(["sort" => $sort, "topic" => $topic]), ENT_QUOTES, "UTF-8") ?>" method="POST" class="saved-card-unsave-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="post_id" value="<?= $postId ?>">
                            <button type="submit" class="saved-card-unsave" aria-label="Unlike post" title="Unlike post">
                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                    <path d="M12 21s-7.5-4.35-10-9.3C-.3 7 2.4 3 6.6 3c2.3 0 4.1 1.3 5.4 3 1.3-1.7 3.1-3 5.4-3C21.6 3 24.3 7 22 11.7 19.5 16.65 12 21Z"/>
                                </svg>
                            </button>
                        </form>
                        <a href="post.php?id=<?= $postId ?>" class="liked-card-link">
                            <div class="saved-card-image">
                                <?php if ($mediaPath !== ""): ?>
                                    <?php if ($isVideo): ?>
                                        <video src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>" muted playsinline preload="metadata"></video>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>" alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>" loading="lazy">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="saved-card-placeholder">
                                        <span>✦</span>
                                        No image
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="saved-card-body">
                                <p class="saved-card-author">@<?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?></p>
                                <h2 class="saved-card-title"><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></h2>
                                <?php if (!empty($post["description"])): ?>
                                    <p class="saved-card-description">
                                        <?= htmlspecialchars(mb_strimwidth($post["description"], 0, 140, "..."), ENT_QUOTES, "UTF-8") ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($post["topic"])): ?>
                                    <p class="saved-card-topic"><?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
