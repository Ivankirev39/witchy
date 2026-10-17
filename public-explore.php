<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Explore | Witchy";
$pageCss = "public-explore.css";
$isLoggedIn = isset($_SESSION["user_id"]);

$posts = [];
$search = trim($_GET["search"] ?? "");
$topic = trim($_GET["topic"] ?? "");

$allowedSorts = [
    "newest" => "p.created_at DESC",
    "oldest" => "p.created_at ASC",
    "title_asc" => "p.title ASC",
    "title_desc" => "p.title DESC"
];

$sort = $_GET["sort"] ?? "newest";

if (!isset($allowedSorts[$sort])) {
    $sort = "newest";
}

$postsPerPage = 9;
$page = max(1, (int) ($_GET["page"] ?? 1));

$conditions = [];
$params = [];
$types = "";

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

    $types .= "ssss";
}

if ($topic !== "") {
    $conditions[] = "p.topic = ?";
    $params[] = $topic;
    $types .= "s";
}

$whereSql = "";

if (!empty($conditions)) {
    $whereSql = " WHERE " . implode(" AND ", $conditions);
}

/*
 * Bind search and topic values safely.
 */
$bindParameters = static function (
    $statement,
    string $types,
    array &$params
): void {
    if ($types === "") {
        return;
    }

    $arguments = [$types];

    foreach ($params as $key => &$value) {
        $arguments[] = &$value;
    }

    call_user_func_array(
        [$statement, "bind_param"],
        $arguments
    );
};

/** Count matching posts for pagination. */
$countSql = "
    SELECT COUNT(*) AS total
    FROM post p
    INNER JOIN users u
        ON u.user_id = p.user_id
    $whereSql
";

$countStatement = $conn->prepare($countSql);

if (!$countStatement) {
    error_log($conn->error);
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}

$countParams = $params;
$countTypes = $types;

$bindParameters(
    $countStatement,
    $countTypes,
    $countParams
);

$countStatement->execute();

$countResult = $countStatement->get_result();
$countRow = $countResult->fetch_assoc();

$totalPosts = (int) ($countRow["total"] ?? 0);

$countStatement->close();

$totalPages = max(
    1,
    (int) ceil($totalPosts / $postsPerPage)
);

$page = min($page, $totalPages);
$offset = ($page - 1) * $postsPerPage;

/*
 * Retrieve the matching posts.
 */
$sql = "
    SELECT
        p.post_id,
        p.user_id,
        p.title,
        p.description,
        p.image,
        p.topic,
        p.created_at,
        u.username
    FROM post p
    INNER JOIN users u
        ON u.user_id = p.user_id
    $whereSql
    ORDER BY {$allowedSorts[$sort]}
    LIMIT $postsPerPage OFFSET $offset
";

$statement = $conn->prepare($sql);

if (!$statement) {
    error_log($conn->error);
    http_response_code(500);
    exit("Something went wrong. Please try again later.");
}

$queryParams = $params;
$queryTypes = $types;

$bindParameters(
    $statement,
    $queryTypes,
    $queryParams
);

$statement->execute();

$result = $statement->get_result();

while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}

$statement->close();

require_once __DIR__ . "/includes/public-header.php";

function publicExploreUrl(array $overrides = []): string
{
    $parameters = [
        "search" => trim($_GET["search"] ?? ""),
        "topic" => trim($_GET["topic"] ?? ""),
        "sort" => $_GET["sort"] ?? "newest"
    ];

    foreach ($overrides as $key => $value) {
        $parameters[$key] = $value;
    }

    $parameters = array_filter(
        $parameters,
        static fn($value) => $value !== ""
            && $value !== null
            && !($value === "newest" && isset($parameters["sort"]))
    );

    return "public-explore.php?" . http_build_query($parameters);
}
?>

<main class="explore-page">

    <header class="explore-header">
        <h1>
            <?php if ($topic !== ""): ?>
                Explore: <?= htmlspecialchars(
                    $topic,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
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

    <form
        class="public-explore-search"
        action="public-explore.php"
        method="GET"
    >
        <?php if ($topic !== ""): ?>
            <input
                type="hidden"
                name="topic"
                value="<?= htmlspecialchars(
                    $topic,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
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
                value="<?= htmlspecialchars(
                    $search,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <button type="submit">Search</button>
        </div>
    </form>

    <nav class="explore-topics" aria-label="Browse topics">
        <a href="public-explore.php"
           class="<?= $topic === "" ? "active" : "" ?>">
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
                class="<?= $topic === $topicOption ? "active" : "" ?>"
            >
                <?= htmlspecialchars(
                    $topicOption,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </a>
        <?php endforeach; ?>
    </nav>

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

            foreach ($sortLabels as $sortValue => $sortLabel):
            ?>
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
                    <?= htmlspecialchars(
                        $sortLabel,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <section class="explore-content">
        <div class="explore-grid">

            <?php if (empty($posts)): ?>
                <div class="explore-empty">
                    <h2>No posts found</h2>

                    <p>
                        Try another search or topic, or check back
                        later for new posts from the community.
                    </p>

                    <a href="public-explore.php">
                        Browse all posts
                    </a>
                </div>

            <?php else: ?>

                <?php foreach ($posts as $post): ?>
                    <article class="explore-cards">
                        
                        <div class="explore-cards-image">
                            <a
                                href="post.php?id=<?= (int) $post["post_id"] ?>"
                                aria-label="View post: <?= htmlspecialchars(
                                    $post["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >
                                <?php if (!empty($post["image"])): ?>
                                    <img
                                        src="uploads/posts/<?= rawurlencode(
                                            basename($post["image"])
                                        ) ?>"
                                        alt="<?= htmlspecialchars(
                                            $post["title"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="explore-cards-placeholder">
                                        <span aria-hidden="true">✦</span>
                                        No image
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>


                        <div class="explore-cards-body">
                            <p class="explore-cards-author">
                                @<?= htmlspecialchars(
                                    $post["username"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </p>

                            <h2 class="explore-cards-title">
                                <a
                                    href="post.php?id=<?= (int) $post["post_id"] ?>"
                                >
                                    <?= htmlspecialchars(
                                        $post["title"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </a>
                            </h2>

                            <?php if (!empty($post["description"])): ?>
                                <p class="explore-cards-description">
                                    <?php
                                    $description = $post["description"];

                                    echo htmlspecialchars(
                                        function_exists("mb_strimwidth")
                                            ? mb_strimwidth(
                                                $description,
                                                0,
                                                140,
                                                "..."
                                            )
                                            : substr(
                                                $description,
                                                0,
                                                140
                                            ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="explore-cards-meta">
                                <?php if (!empty($post["topic"])): ?>
                                    <p class="explore-cards-topic">
                                        <?= htmlspecialchars(
                                            $post["topic"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </p>
                                <?php endif; ?>

                                <time
                                    class="explore-cards-date"
                                    datetime="<?= htmlspecialchars(
                                        $post["created_at"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >
                                    <?= date(
                                        "M j, Y",
                                        strtotime($post["created_at"])
                                    ) ?>
                                </time>
                            </div>
                        </div>

                    </article>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav
            class="explore-pagination"
            aria-label="Explore pages"
        >
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
                <?php for (
                    $pageNumber = 1;
                    $pageNumber <= $totalPages;
                    $pageNumber++
                ): ?>
                    <a
                        href="<?= htmlspecialchars(
                            publicExploreUrl(["page" => $pageNumber]),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        class="<?= $pageNumber === $page ? "active" : "" ?>"
                        <?= $pageNumber === $page
                            ? 'aria-current="page"'
                            : "" ?>
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
        <a href="register.php">Join Witchy</a>
        or <a href="login.php">Log in</a>.
    </p>

</main>

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>
