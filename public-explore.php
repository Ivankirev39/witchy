<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/models/DBConnector.php";

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$pageTitle = "Explore | Witchy";
$pageCss = "public-explore.css";

$search = trim($_GET["search"] ?? "");
$topic = trim($_GET["topic"] ?? "");

$allowedSorts = [
    "newest" => "p.created_at DESC, p.post_id DESC",
    "oldest" => "p.created_at ASC, p.post_id ASC",
    "title_asc" => "p.title ASC, p.post_id DESC",
    "title_desc" => "p.title DESC, p.post_id DESC"
];

$sort = $_GET["sort"] ?? "newest";

if (!isset($allowedSorts[$sort])) {
    $sort = "newest";
}

$postsPerPage = 9;
$page = max(1, (int) ($_GET["page"] ?? 1));

$conditions = [];
$params = [];

/* SEARCH */
if ($search !== "") {
    $conditions[] = "(
        p.title LIKE ?
        OR p.description LIKE ?
        OR p.topic LIKE ?
        OR u.username LIKE ?
    )";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

/* TOPIC FILTER */
if ($topic !== "") {
    $conditions[] = "LOWER(p.topic) = LOWER(?)";
    $params[] = $topic;
}

$whereSql = "";

if ($conditions) {
    $whereSql = " WHERE " . implode(" AND ", $conditions);
}

/* COUNT MATCHING POSTS */
$countSql = "
    SELECT COUNT(*)
    FROM post p
    INNER JOIN users u ON u.user_id = p.user_id
    $whereSql
";

$countStatement = $pdo->prepare($countSql);
$countStatement->execute($params);

$totalPosts = (int) $countStatement->fetchColumn();

$totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $postsPerPage;

/* FETCH POSTS AND THEIR FIRST MEDIA FILE */
$sql = "
    SELECT
        p.post_id,
        p.title,
        p.description,
        p.topic,
        p.created_at,
        u.username,
        (
            SELECT pm.file
            FROM post_media pm
            WHERE pm.post_id = p.post_id
            ORDER BY pm.sort_order ASC, pm.media_id ASC
            LIMIT 1
        ) AS media_file,
        (
            SELECT pm.media_type
            FROM post_media pm
            WHERE pm.post_id = p.post_id
            ORDER BY pm.sort_order ASC, pm.media_id ASC
            LIMIT 1
        ) AS media_type
    FROM post p
    INNER JOIN users u ON u.user_id = p.user_id
    $whereSql
    ORDER BY {$allowedSorts[$sort]}
    LIMIT {$postsPerPage} OFFSET {$offset}
";

$statement = $pdo->prepare($sql);
$statement->execute($params);

$posts = $statement->fetchAll(PDO::FETCH_ASSOC);

/*
 * Return a readable preview without cutting a word in half.
 * The full description stays in the database and is shown on the post page.
 */
function publicExploreDescriptionPreview(string $description, int $limit = 105): array
{
    $description = trim($description);

    if (function_exists("mb_strlen")) {
        $length = mb_strlen($description, "UTF-8");
    } else {
        $length = strlen($description);
    }

    if ($length <= $limit) {
        return [
            "text" => $description,
            "truncated" => false
        ];
    }

    if (function_exists("mb_substr")) {
        $preview = mb_substr($description, 0, $limit, "UTF-8");
    } else {
        $preview = substr($description, 0, $limit);
    }

    // Prefer ending the preview at a word boundary.
    $lastSpace = strrpos($preview, " ");

    if ($lastSpace !== false && $lastSpace > (int) ($limit * 0.65)) {
        $preview = substr($preview, 0, $lastSpace);
    }

    return [
        "text" => rtrim($preview, " \t\n\r\0\x0B.,;:!?-") . "…",
        "truncated" => true
    ];
}

/* BUILD FILTER AND PAGINATION URLS */
function publicExploreUrl(array $overrides = []): string
{
    $parameters = [
        "search" => trim($_GET["search"] ?? ""),
        "topic" => trim($_GET["topic"] ?? ""),
        "sort" => $_GET["sort"] ?? "newest",
        "page" => $_GET["page"] ?? 1
    ];

    foreach ($overrides as $key => $value) {
        $parameters[$key] = $value;
    }

    if (($parameters["sort"] ?? "newest") === "newest") {
        unset($parameters["sort"]);
    }

    if ((int) ($parameters["page"] ?? 1) <= 1) {
        unset($parameters["page"]);
    }

    $parameters = array_filter(
        $parameters,
        static fn($value) => $value !== "" && $value !== null
    );

    return "public-explore.php?" . http_build_query($parameters);
}

require_once __DIR__ . "/includes/public-header.php";
?>

<main class="explore-page">

    <header class="explore-header">
        <h1>
            <?php if ($topic !== ""): ?>
                Explore: <?= htmlspecialchars($topic, ENT_QUOTES, "UTF-8") ?>
            <?php elseif ($search !== ""): ?>
                Search results
            <?php else: ?>
                Explore Witchy
            <?php endif; ?>
        </h1>

        <p>
            Discover ideas, practices and perspectives
            from the Witchy community.
        </p>

        <?php if ($search !== "" || $topic !== ""): ?>
            <a href="public-explore.php" class="explore-clear-search">
                Clear filters
            </a>
        <?php endif; ?>
    </header>

    <!-- SEARCH -->
    <form class="public-explore-search" action="public-explore.php" method="GET">
        <?php if ($topic !== ""): ?>
            <input
                type="hidden"
                name="topic"
                value="<?= htmlspecialchars($topic, ENT_QUOTES, "UTF-8") ?>"
            >
        <?php endif; ?>

        <label for="public-search">Search Witchy posts</label>

        <div>
            <input
                type="search"
                id="public-search"
                name="search"
                maxlength="150"
                placeholder="Search titles, topics or authors..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            >
            <button type="submit">Search</button>
        </div>
    </form>

    <!-- TOPIC FILTERS -->
    <nav class="explore-topics" aria-label="Browse topics">
        <a
            href="<?= htmlspecialchars(
                publicExploreUrl(["topic" => "", "page" => 1]),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="<?= $topic === "" ? "active" : "" ?>"
        >All</a>

        <?php
        $topics = [
            "Spells",
            "Altars",
            "Tarot",
            "Herbs",
            "Crystals",
            "Books",
            "Artwork",
            "Occult Studies",
            "Others"
        ];
        ?>

        <?php foreach ($topics as $topicOption): ?>
            <a
                href="<?= htmlspecialchars(
                    publicExploreUrl([
                        "topic" => $topicOption,
                        "page" => 1
                    ]),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                class="<?= strcasecmp($topic, $topicOption) === 0 ? "active" : "" ?>"
            >
                <?= htmlspecialchars($topicOption, ENT_QUOTES, "UTF-8") ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- SORT -->
    <div class="explore-sort">
        <label for="explore-sort">Sort by:</label>

        <select
            id="explore-sort"
            onchange="window.location.href=this.value"
        >
            <?php
            $sortLabels = [
                "newest" => "Newest",
                "oldest" => "Oldest",
                "title_asc" => "Title A–Z",
                "title_desc" => "Title Z–A"
            ];
            ?>

            <?php foreach ($sortLabels as $sortValue => $sortLabel): ?>
                <option
                    value="<?= htmlspecialchars(
                        publicExploreUrl([
                            "sort" => $sortValue,
                            "page" => 1
                        ]),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    <?= $sort === $sortValue ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($sortLabel, ENT_QUOTES, "UTF-8") ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- POSTS -->
    <section class="explore-content" aria-label="Explore posts">
        <div class="explore-grid">

            <?php if (empty($posts)): ?>
                <div class="explore-empty">
                    <h2>No posts found</h2>
                    <p>
                        Try another search or topic, or check back later
                        for new posts from the community.
                    </p>
                    <a href="public-explore.php">Browse all posts</a>
                </div>
            <?php else: ?>

                <?php foreach ($posts as $post): ?>
                    <?php
                    $postUrl = "post.php?id=" . (int) $post["post_id"];

                    $mediaFile = trim($post["media_file"] ?? "");
                    $mediaType = strtolower(trim($post["media_type"] ?? ""));

                    $mediaPath = $mediaFile !== ""
                        ? "/witchy/uploads/posts/" . rawurlencode(basename($mediaFile))
                        : "";

                    $isImage = $mediaPath !== ""
                        && in_array($mediaType, ["image", "photo"], true);

                    $isVideo = $mediaPath !== "" && $mediaType === "video";

                    $description = trim($post["description"] ?? "");
                    $preview = publicExploreDescriptionPreview($description);
                    ?>

                    <article class="explore-cards">
                        <!-- IMAGE -->
                        <a
                            class="explore-cards-image"
                            href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>"
                            aria-label="View post: <?= htmlspecialchars($post["title"] ?: "Untitled post", ENT_QUOTES, "UTF-8") ?>"
                        >
                            <?php if ($isImage): ?>
                                <img
                                    src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                    alt="<?= htmlspecialchars($post["title"] ?: "Post image", ENT_QUOTES, "UTF-8") ?>"
                                    loading="lazy"
                                >
                            <?php elseif ($isVideo): ?>
                                <video
                                    preload="metadata"
                                    muted
                                    playsinline
                                    aria-label="<?= htmlspecialchars($post["title"] ?: "Post video", ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <source src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>">
                                </video>
                                <span class="explore-video-label">Video</span>
                            <?php else: ?>
                                <span class="explore-cards-placeholder" aria-hidden="true">
                                    <span class="explore-placeholder-icon">✦</span>
                                    <span>No image</span>
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- CARD CONTENT -->
                        <div class="explore-cards-body">
                            <p class="explore-cards-author">
                                @<?= htmlspecialchars($post["username"] ?? "user", ENT_QUOTES, "UTF-8") ?>
                            </p>

                            <h2 class="explore-cards-title">
                                <a href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>">
                                    <?= htmlspecialchars($post["title"] ?: "Untitled post", ENT_QUOTES, "UTF-8") ?>
                                </a>
                            </h2>

                            <?php if ($description !== ""): ?>
                                <p class="explore-cards-description">
                                    <?= htmlspecialchars($preview["text"], ENT_QUOTES, "UTF-8") ?>
                                    <?php if ($preview["truncated"]): ?>
                                        <a
                                            class="explore-read-more"
                                            href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>"
                                            aria-label="Read the full post: <?= htmlspecialchars($post["title"] ?: "Untitled post", ENT_QUOTES, "UTF-8") ?>"
                                        >Read more</a>
                                    <?php endif; ?>
                                </p>
                            <?php else: ?>
                                <p class="explore-cards-description explore-no-description">
                                    No description provided.
                                </p>
                            <?php endif; ?>

                            <div class="explore-cards-meta">
                                <?php if (!empty($post["topic"])): ?>
                                    <span class="explore-cards-topic">
                                        <?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                                    </span>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>

                                <time
                                    class="explore-cards-date"
                                    datetime="<?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <?= date("M j, Y", strtotime($post["created_at"])) ?>
                                </time>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </section>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <nav class="explore-pagination" aria-label="Explore pages">
            <?php if ($page > 1): ?>
                <a
                    class="explore-pagination-button"
                    href="<?= htmlspecialchars(
                        publicExploreUrl(["page" => $page - 1]),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >← Previous</a>
            <?php endif; ?>

            <div class="explore-pagination-pages">
                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                    <a
                        href="<?= htmlspecialchars(
                            publicExploreUrl(["page" => $pageNumber]),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        class="<?= $pageNumber === $page ? "active" : "" ?>"
                        <?= $pageNumber === $page ? 'aria-current="page"' : "" ?>
                    >
                        <?= $pageNumber ?>
                    </a>
                <?php endfor; ?>
            </div>

            <?php if ($page < $totalPages): ?>
                <a
                    class="explore-pagination-button"
                    href="<?= htmlspecialchars(
                        publicExploreUrl(["page" => $page + 1]),
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >Next →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <p class="public-explore-join">
        Want to like, save or discuss posts?
        <a href="register.php">Join Witchy</a>
        or <a href="login.php">Log in</a>.
    </p>

</main>

<?php require_once __DIR__ . "/includes/public-footer.php"; ?>
