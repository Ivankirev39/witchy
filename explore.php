
<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/models/Post.php";

$pageTitle = "Explore | Witchy";
$pageCss = "explore.css";

$posts = [];
$csrfToken = csrf_token();

$search = trim($_GET["search"] ?? "");
$topic = trim($_GET["topic"] ?? "");

$allowedTabs = ["all", "hot", "popular"];
$tab = $_GET["tab"] ?? "all";

if (!in_array($tab, $allowedTabs, true)) {
    $tab = "all";
}

$allowedSorts = ["newest", "oldest", "title_asc", "title_desc"];
$sort = $_GET["sort"] ?? "newest";

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "newest";
}

// Sorting only applies to All Posts.
if ($tab !== "all") {
    $sort = "newest";
}

$postsPerPage = 9;
$page = max(1, (int) ($_GET["page"] ?? 1));

// Establish database connection.
$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();

$postModel = new Post($pdo);

// Count posts matching the selected tab and filters.
$totalPosts = $postModel->countExplorePosts(
    $search,
    $topic,
    $tab
);

$totalPages = max(
    1,
    (int) ceil($totalPosts / $postsPerPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $postsPerPage;

// Retrieve posts for the selected tab.
$posts = $postModel->searchExplorePosts(
    $search,
    $topic,
    $sort,
    $postsPerPage,
    $offset,
    $tab,
    (int) $_SESSION["user_id"]
);

// Shared URL parameters.
$baseParams = [];

if ($search !== "") {
    $baseParams["search"] = $search;
}

if ($topic !== "") {
    $baseParams["topic"] = $topic;
}

if ($tab !== "all") {
    $baseParams["tab"] = $tab;
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="explore-page">

    <header class="explore-header">

        <?php if ($search !== ""): ?>

            <h1>Search results</h1>

            <p>
                Showing results for
                <strong>
                    "<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
                </strong>
            </p>

            <?php
            $clearParams = [];

            if ($topic !== "") {
                $clearParams["topic"] = $topic;
            }

            if ($tab !== "all") {
                $clearParams["tab"] = $tab;
            }

            $clearUrl = "explore.php";

            if ($clearParams !== []) {
                $clearUrl .= "?" . http_build_query($clearParams);
            }
            ?>

            <a
                href="<?php echo htmlspecialchars($clearUrl, ENT_QUOTES, "UTF-8"); ?>"
                class="explore-clear-search">
                Clear search
            </a>

        <?php else: ?>

            <h1>Explore</h1>

            <p>
                Discover posts from the Witchy community.
            </p>

        <?php endif; ?>

    </header>


    <!-- Explore tabs -->

    <nav class="explore-tabs" aria-label="Explore categories">

        <?php
        $tabLabels = [
            "all" => "All Posts",
            "hot" => "Trending",
            "popular" => "Popular"
        ];

        foreach ($tabLabels as $tabValue => $tabLabel):

            $tabParams = $baseParams;
            unset($tabParams["tab"]);

            if ($tabValue !== "all") {
                $tabParams["tab"] = $tabValue;
            }

            $tabUrl = "explore.php";

            if ($tabParams !== []) {
                $tabUrl .= "?" . http_build_query($tabParams);
            }
        ?>

            <a
                href="<?php echo htmlspecialchars($tabUrl, ENT_QUOTES, "UTF-8"); ?>"
                class="<?php echo $tab === $tabValue ? "active" : ""; ?>"
                <?php echo $tab === $tabValue ? 'aria-current="page"' : ""; ?>>

                <?php echo htmlspecialchars($tabLabel, ENT_QUOTES, "UTF-8"); ?>

            </a>

        <?php endforeach; ?>

    </nav>


    <!-- Topic filters -->

    <nav class="explore-topics" aria-label="Filter by topic">

        <?php
        $topics = [
            "" => "All",
            "Spells" => "Spells",
            "Altars" => "Altars",
            "Tarot" => "Tarot",
            "Herbs" => "Herbs",
            "Crystals" => "Crystals",
            "Books" => "Books",
            "Artwork" => "Artwork",
            "Occult Studies" => "Occult Studies",
            "Others" => "Others"
        ];

        foreach ($topics as $topicValue => $topicLabel):

            $topicParams = [];

            if ($search !== "") {
                $topicParams["search"] = $search;
            }

            if ($topicValue !== "") {
                $topicParams["topic"] = $topicValue;
            }

            if ($tab !== "all") {
                $topicParams["tab"] = $tab;
            }

            $topicUrl = "explore.php";

            if ($topicParams !== []) {
                $topicUrl .= "?" . http_build_query($topicParams);
            }
        ?>

            <a
                href="<?php echo htmlspecialchars($topicUrl, ENT_QUOTES, "UTF-8"); ?>"
                class="<?php echo $topic === $topicValue ? "active" : ""; ?>"
                <?php echo $topic === $topicValue ? 'aria-current="page"' : ""; ?>>

                <?php echo htmlspecialchars($topicLabel, ENT_QUOTES, "UTF-8"); ?>

            </a>

        <?php endforeach; ?>

    </nav>


    <!-- Sorting: only available for All Posts -->

    <?php if ($tab === "all"): ?>

        <div class="explore-sort">

            <label for="explore-sort">
                Sort by:
            </label>

            <select
                id="explore-sort"
                onchange="window.location.href=this.value">

                <?php
                $sortOptions = [
                    "newest" => "Newest",
                    "oldest" => "Oldest",
                    "title_asc" => "Title A–Z",
                    "title_desc" => "Title Z–A"
                ];

                foreach ($sortOptions as $value => $label):

                    $sortParams = $baseParams;
                    unset($sortParams["tab"]);

                    $sortParams["sort"] = $value;

                    $sortUrl = "explore.php?" . http_build_query($sortParams);
                ?>

                    <option
                        value="<?php echo htmlspecialchars($sortUrl, ENT_QUOTES, "UTF-8"); ?>"
                        <?php echo $sort === $value ? "selected" : ""; ?>>

                        <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>

    <?php endif; ?>


    <!-- Posts -->

    <section class="explore-content">

        <div class="explore-grid">

            <?php if (empty($posts)): ?>

                <div class="explore-empty">

                    <?php if ($tab === "hot"): ?>

                        <h2>No hot posts right now</h2>

                        <p>
                            There hasn't been any engagement on matching posts
                            during the last 48 hours. Check back later!
                        </p>

                    <?php elseif ($tab === "popular"): ?>

                        <h2>No popular posts yet</h2>

                        <p>
                            Posts will appear here as the community likes
                            and comments on them.
                        </p>

                    <?php elseif ($search !== ""): ?>

                        <h2>No results found</h2>

                        <p>
                            We couldn't find any posts matching
                            "<strong>
                                <?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>
                            </strong>".
                        </p>

                    <?php else: ?>

                        <h2>No posts to explore yet</h2>

                        <p>
                            Check back later for new posts from the Witchy community.
                        </p>

                    <?php endif; ?>

                </div>

            <?php else: ?>

                <?php foreach ($posts as $post): ?>

                    <article
                        class="explore-cards"
                        role="link"
                        tabindex="0"
                        onclick="window.location.href='post.php?id=<?php echo (int) $post['post_id']; ?>'"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='post.php?id=<?php echo (int) $post['post_id']; ?>'; }">

                        <div class="explore-cards-image">

                            <a href="post.php?id=<?php echo (int) $post['post_id']; ?>">

                                <?php if (!empty($post["media_file"])): ?>

                                    <?php
                                    $mediaPath = "uploads/posts/" .
                                        rawurlencode(basename($post["media_file"]));
                                    ?>

                                    <?php if ($post["media_type"] === "video"): ?>

                                        <video muted playsinline preload="metadata">
                                            <source src="<?php echo htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8"); ?>">
                                        </video>

                                    <?php else: ?>

                                        <img
                                            src="<?php echo htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8"); ?>"
                                            alt="<?php echo htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8"); ?>"
                                            loading="lazy">

                                    <?php endif; ?>

                                <?php else: ?>

                                    <div class="explore-cards-placeholder">
                                        <span>✦</span>
                                        No media
                                    </div>

                                <?php endif; ?>

                            </a>

                        </div>


                        <div class="explore-cards-body">

                            <!-- Community pin indicator -->

                            <?php if (!empty($post["is_sticky"])): ?>

                                <span
                                    class="explore-pin-label"
                                    title="Community pinned"
                                    aria-label="This post is pinned by the community"
                                    role="img">
                                </span>

                            <?php endif; ?>


                            <p class="explore-cards-author">
                                @<?php echo htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8"); ?>
                            </p>


                            <h2 class="explore-cards-title">

                                <a href="post.php?id=<?php echo (int) $post["post_id"]; ?>">

                                    <?php echo htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8"); ?>

                                </a>

                            </h2>


                            <?php if (!empty($post["description"])): ?>

                                <p class="explore-cards-description">

                                    <?php
                                    echo htmlspecialchars(
                                        mb_strimwidth(
                                            $post["description"],
                                            0,
                                            140,
                                            "..."
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </p>

                            <?php endif; ?>


                            <?php if (!empty($post["topic"]) || !empty($post["created_at"])): ?>

                                <div class="explore-cards-meta">

                                    <?php if (!empty($post["topic"])): ?>

                                        <?php
                                        $postTopicParams = [
                                            "topic" => $post["topic"]
                                        ];

                                        if ($search !== "") {
                                            $postTopicParams["search"] = $search;
                                        }

                                        if ($tab !== "all") {
                                            $postTopicParams["tab"] = $tab;
                                        }

                                        $postTopicUrl = "explore.php?" .
                                            http_build_query($postTopicParams);
                                        ?>

                                        <p class="explore-cards-topic">

                                            <a href="<?php echo htmlspecialchars($postTopicUrl, ENT_QUOTES, "UTF-8"); ?>">

                                                <?php echo htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8"); ?>

                                            </a>

                                        </p>

                                    <?php endif; ?>


                                    <?php if (!empty($post["created_at"])): ?>

                                        <time
                                            class="explore-cards-date"
                                            datetime="<?php echo htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8"); ?>">

                                            <?php echo date("M j, Y", strtotime($post["created_at"])); ?>

                                        </time>

                                    <?php endif; ?>

                                </div>

                            <?php endif; ?>


                            <!-- Engagement counts and like button -->

                            <div class="explore-cards-engagement">

                                <?php $userLiked = !empty($post["user_liked"]); ?>

                                <button
                                    type="button"
                                    class="engagement-item engagement-like-button"
                                    data-post-id="<?php echo (int) $post["post_id"]; ?>"
                                    data-liked="<?php echo $userLiked ? "1" : "0"; ?>"
                                    data-csrf-token="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, "UTF-8"); ?>"
                                    aria-label="<?php echo $userLiked ? "Unlike post" : "Like post"; ?>"
                                    aria-pressed="<?php echo $userLiked ? "true" : "false"; ?>"
                                    title="<?php echo (int) ($post["like_count"] ?? 0); ?> likes">

                                    <svg
                                        class="engagement-icon like-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path d="M20.8 8.7c0 5.2-8.8 11-8.8 11s-8.8-5.8-8.8-11A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/>

                                    </svg>

                                    <span class="engagement-count">
                                        <?php echo (int) ($post["like_count"] ?? 0); ?>
                                    </span>

                                </button>


                                <span
                                    class="engagement-item engagement-comment-item"
                                    title="<?php echo (int) ($post["comment_count"] ?? 0); ?> comments"
                                    aria-label="<?php echo (int) ($post["comment_count"] ?? 0); ?> comments">

                                    <svg
                                        class="engagement-icon comment-icon"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/>

                                    </svg>

                                    <span class="engagement-count">
                                        <?php echo (int) ($post["comment_count"] ?? 0); ?>
                                    </span>

                                </span>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </section>


    <!-- Pagination -->

    <?php if ($totalPages > 1): ?>

        <?php
        $paginationParams = $baseParams;

        if ($tab === "all" && $sort !== "newest") {
            $paginationParams["sort"] = $sort;
        }
        ?>

        <nav class="explore-pagination" aria-label="Explore pages">

            <?php if ($page > 1): ?>

                <?php
                $previousParams = $paginationParams;
                $previousParams["page"] = $page - 1;

                $previousUrl = "explore.php?" .
                    http_build_query($previousParams);
                ?>

                <a
                    href="<?php echo htmlspecialchars($previousUrl, ENT_QUOTES, "UTF-8"); ?>"
                    class="explore-pagination-button">

                    ← Previous

                </a>

            <?php endif; ?>


            <div class="explore-pagination-pages">

                <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>

                    <?php
                    $pageParams = $paginationParams;
                    $pageParams["page"] = $pageNumber;

                    $pageUrl = "explore.php?" .
                        http_build_query($pageParams);
                    ?>

                    <a
                        href="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo $pageNumber === $page ? "active" : ""; ?>"
                        <?php echo $pageNumber === $page ? 'aria-current="page"' : ""; ?>>

                        <?php echo $pageNumber; ?>

                    </a>

                <?php endfor; ?>

            </div>


            <?php if ($page < $totalPages): ?>

                <?php
                $nextParams = $paginationParams;
                $nextParams["page"] = $page + 1;

                $nextUrl = "explore.php?" .
                    http_build_query($nextParams);
                ?>

                <a
                    href="<?php echo htmlspecialchars($nextUrl, ENT_QUOTES, "UTF-8"); ?>"
                    class="explore-pagination-button">

                    Next →

                </a>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const likeButtons = document.querySelectorAll(".engagement-like-button");

    likeButtons.forEach(function (button) {
        button.addEventListener("click", async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (button.disabled) {
                return;
            }

            button.disabled = true;

            const formData = new FormData();
            formData.append("post_id", button.dataset.postId);
            formData.append("csrf_token", button.dataset.csrfToken);

            try {
                const response = await fetch("like_post.php", {
                    method: "POST",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error("The like could not be updated.");
                }

                const liked = data.liked === true || data.liked === 1 || data.liked === "1";

                button.dataset.liked = liked ? "1" : "0";
                button.setAttribute("aria-pressed", liked ? "true" : "false");
                button.setAttribute("aria-label", liked ? "Unlike post" : "Like post");

                const countElement = button.querySelector(".engagement-count");

                if (countElement) {
                    countElement.textContent = data.like_count;
                }

                button.title = data.like_count + " likes";

            } catch (error) {
                alert("Unable to update your like. Please refresh the page and try again.");
            } finally {
                button.disabled = false;
            }
        });

        // Prevent the card's keyboard navigation from handling
        // Enter or Space when the like button has focus.
        button.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.stopPropagation();
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>
