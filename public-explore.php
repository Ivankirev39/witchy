
<?php
require_once __DIR__ . "/config/bootstrap.php";
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/models/DBConnector.php";

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$pageTitle = "Explore | Witchy";
$pageCss = "public-explore.css";

$search = trim($_GET["search"] ?? "");
$topic = trim($_GET["topic"] ?? "");

$allowedTabs = ["all", "hot", "popular"];
$tab = $_GET["tab"] ?? "all";

if (!in_array($tab, $allowedTabs, true)) {
    $tab = "all";
}

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

$whereSql = $conditions
    ? " WHERE " . implode(" AND ", $conditions)
    : "";

/* ENGAGEMENT DATA */
$engagementJoins = "";
$engagementCondition = "";
$countJoins = "";
$scoreSql = "0";

if ($tab === "hot") {
    $engagementJoins = "
        LEFT JOIN (
            SELECT
                post_id,
                COUNT(*) AS like_count
            FROM post_like
            WHERE liked_at >= NOW() - INTERVAL 48 HOUR
            GROUP BY post_id
        ) recent_likes
            ON recent_likes.post_id = p.post_id

        LEFT JOIN (
            SELECT
                post_id,
                COUNT(*) AS comment_count
            FROM comment
            WHERE created_at >= NOW() - INTERVAL 48 HOUR
            GROUP BY post_id
        ) recent_comments
            ON recent_comments.post_id = p.post_id
    ";

    $countJoins = $engagementJoins;

    $engagementCondition = "
        AND (
            COALESCE(recent_likes.like_count, 0) > 0
            OR COALESCE(recent_comments.comment_count, 0) > 0
        )
    ";

    $scoreSql = "
        (
            COALESCE(recent_likes.like_count, 0)
            + (COALESCE(recent_comments.comment_count, 0) * 3)
        )
    ";
}

elseif ($tab === "popular") {
    $engagementJoins = "
        LEFT JOIN (
            SELECT
                post_id,
                COUNT(*) AS like_count
            FROM post_like
            GROUP BY post_id
        ) all_likes
            ON all_likes.post_id = p.post_id

        LEFT JOIN (
            SELECT
                post_id,
                COUNT(*) AS comment_count
            FROM comment
            GROUP BY post_id
        ) all_comments
            ON all_comments.post_id = p.post_id
    ";

    $scoreSql = "
        (
            COALESCE(all_likes.like_count, 0)
            + (COALESCE(all_comments.comment_count, 0) * 3)
        )
    ";
}

/* COUNT MATCHING POSTS */
$countSql = "
    SELECT COUNT(*)
    FROM post p
    INNER JOIN users u
        ON u.user_id = p.user_id
    $countJoins
    $whereSql
";

if ($engagementCondition !== "") {
    if ($whereSql === "") {
        $countSql .= " WHERE " . substr(trim($engagementCondition), 4);
    } else {
        $countSql .= $engagementCondition;
    }
}

$countStatement = $pdo->prepare($countSql);
$countStatement->execute($params);

$totalPosts = (int) $countStatement->fetchColumn();

$totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $postsPerPage;

/* DETERMINE ORDER */
if ($tab === "hot" || $tab === "popular") {
    $orderSql = "
        p.is_sticky DESC,
        $scoreSql DESC,
        p.created_at DESC,
        p.post_id DESC
    ";
} else {
    $orderSql = "
        p.is_sticky DESC,
        {$allowedSorts[$sort]}
    ";
}

/* FETCH POSTS */
$sql = "
    SELECT
        p.post_id,
        p.title,
        p.description,
        p.topic,
        p.created_at,
        p.is_sticky,
        u.username,
        $scoreSql AS engagement_score,

        (
            SELECT COUNT(*)
            FROM post_like pl
            WHERE pl.post_id = p.post_id
        ) AS like_count,

        (
            SELECT COUNT(*)
            FROM comment c
            WHERE c.post_id = p.post_id
            AND c.deleted_at IS NULL
        ) AS comment_count,

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

    INNER JOIN users u
        ON u.user_id = p.user_id

    $engagementJoins

    $whereSql
";

if ($engagementCondition !== "") {
    if ($whereSql === "") {
        $sql .= " WHERE " . substr(trim($engagementCondition), 4);
    } else {
        $sql .= $engagementCondition;
    }
}

$sql .= "
    ORDER BY $orderSql
    LIMIT {$postsPerPage} OFFSET {$offset}
";

$statement = $pdo->prepare($sql);
$statement->execute($params);

$posts = $statement->fetchAll(PDO::FETCH_ASSOC);

/* DESCRIPTION PREVIEW */
function publicExploreDescriptionPreview(
    string $description,
    int $limit = 105
): array {
    $description = trim($description);

    $length = function_exists("mb_strlen")
        ? mb_strlen($description, "UTF-8")
        : strlen($description);

    if ($length <= $limit) {
        return [
            "text" => $description,
            "truncated" => false
        ];
    }

    $preview = function_exists("mb_substr")
        ? mb_substr($description, 0, $limit, "UTF-8")
        : substr($description, 0, $limit);

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
        "tab" => $_GET["tab"] ?? "all",
        "sort" => $_GET["sort"] ?? "newest",
        "page" => $_GET["page"] ?? 1
    ];

    foreach ($overrides as $key => $value) {
        $parameters[$key] = $value;
    }

    if (($parameters["tab"] ?? "all") === "all") {
        unset($parameters["tab"]);
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

    return BASE_URL . "public-explore.php?" . http_build_query($parameters);
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
            <a href="<?= BASE_URL ?>public-explore.php" class="explore-clear-search">
                Clear filters
            </a>
        <?php endif; ?>
    </header>

    <nav class="public-explore-tabs" aria-label="Explore post categories">

        <a
            href="<?= htmlspecialchars(
                publicExploreUrl(["tab" => "all", "page" => 1]),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="<?= $tab === "all" ? "active" : "" ?>"
            <?= $tab === "all" ? 'aria-current="page"' : "" ?>
        >
            All Posts
        </a>

        <a
            href="<?= htmlspecialchars(
                publicExploreUrl(["tab" => "hot", "page" => 1]),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="<?= $tab === "hot" ? "active" : "" ?>"
            <?= $tab === "hot" ? 'aria-current="page"' : "" ?>
        >
            Trending
        </a>

        <a
            href="<?= htmlspecialchars(
                publicExploreUrl(["tab" => "popular", "page" => 1]),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="<?= $tab === "popular" ? "active" : "" ?>"
            <?= $tab === "popular" ? 'aria-current="page"' : "" ?>
        >
            Popular
        </a>

    </nav>

    <form class="public-explore-search" action="<?= BASE_URL ?>public-explore.php" method="GET">

        <input
            type="hidden"
            name="tab"
            value="<?= htmlspecialchars($tab, ENT_QUOTES, "UTF-8") ?>"
        >

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

    <nav class="explore-topics" aria-label="Browse topics">

        <a
            href="<?= htmlspecialchars(
                publicExploreUrl(["topic" => "", "page" => 1]),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            class="<?= $topic === "" ? "active" : "" ?>"
        >
            All
        </a>

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

    <?php if ($tab === "all"): ?>

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

    <?php endif; ?>

    <section class="explore-content" aria-label="Explore posts">

        <div class="explore-grid">

            <?php if (empty($posts)): ?>

                <div class="explore-empty">

                    <span class="explore-empty-icon" aria-hidden="true">✧</span>

                    <?php if ($tab === "hot" && $search === "" && $topic === ""): ?>

                        <h2>Nothing is trending just yet</h2>

                        <p>
                            New activity will appear here as the community
                            likes and comments on posts.
                        </p>

                    <?php elseif ($tab === "popular" && $search === "" && $topic === ""): ?>

                        <h2>No popular posts yet</h2>

                        <p>
                            The most-loved posts from the community
                            will find their way here.
                        </p>

                    <?php else: ?>

                        <h2>No posts found</h2>

                        <p>
                            Try another search or topic, or check back later
                            for new posts from the community.
                        </p>

                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>public-explore.php">Browse all posts</a>

                </div>

            <?php else: ?>

                <?php foreach ($posts as $post): ?>

                    <?php
                    $postUrl = BASE_URL . "post.php?id=" . (int) $post["post_id"];

                    $mediaFile = trim($post["media_file"] ?? "");
                    $mediaType = strtolower(trim($post["media_type"] ?? ""));

                    $mediaPath = $mediaFile !== ""
                        ? BASE_URL . "uploads/posts/" . rawurlencode(basename($mediaFile))
                        : "";

                    $isImage = $mediaPath !== ""
                        && in_array($mediaType, ["image", "photo"], true);

                    $isVideo = $mediaPath !== ""
                        && $mediaType === "video";

                    $description = trim($post["description"] ?? "");
                    $preview = publicExploreDescriptionPreview($description);
                    ?>

                    <article class="explore-cards">

                        <a
                            class="explore-cards-image"
                            href="<?= htmlspecialchars($postUrl, ENT_QUOTES, "UTF-8") ?>"
                            aria-label="View post: <?= htmlspecialchars($post["title"] ?: "Untitled post", ENT_QUOTES, "UTF-8") ?>"
                        >

                            <?php if ((int) $post["is_sticky"] === 1): ?>

                                <span class="public-explore-pinned">
                                    ✦ Pinned
                                </span>

                            <?php endif; ?>

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
                                        >
                                            Read more
                                        </a>

                                    <?php endif; ?>

                                </p>

                            <?php else: ?>

                                <p class="explore-cards-description explore-no-description">
                                    No description provided.
                                </p>

                            <?php endif; ?>

                            <div class="explore-cards-topic-row">

                                <?php if (!empty($post["topic"])): ?>

                                    <span class="explore-cards-topic">
                                        <?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="explore-cards-meta">

                                <div class="explore-cards-engagement">

                                    <span
                                        class="explore-engagement-item"
                                        aria-label="<?= (int) $post["like_count"] ?> likes"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/>
                                        </svg>

                                        <?= (int) $post["like_count"] ?>
                                    </span>

                                    <span
                                        class="explore-engagement-item"
                                        aria-label="<?= (int) $post["comment_count"] ?> comments"
                                    >
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            aria-hidden="true"
                                        >
                                            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>
                                        </svg>

                                        <?= (int) $post["comment_count"] ?>
                                    </span>

                                </div>

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
                >
                    ← Previous
                </a>

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
                >
                    Next →
                </a>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

    <p class="public-explore-join">
        Want to like, save or discuss posts?
        <a href="<?= BASE_URL ?>register.php">Join Witchy</a>
        or <a href="<?= BASE_URL ?>login.php">Log in</a>.
    </p>

</main>

<?php require_once __DIR__ . "/includes/public-footer.php"; ?>
