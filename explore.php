<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Explore | Witchy";
$pageCss = "explore.css";

$posts = [];
$search = trim($_GET["search"] ?? "");
$topic = trim($_GET["topic"] ?? "");
$sort = $_GET["sort"] ?? "newest";

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
";

$conditions = [];

if ($search !== "") {
    $searchTerm = "%" . $conn->real_escape_string($search) . "%";

    $conditions[] = "
        (
            p.title LIKE '$searchTerm'
            OR p.description LIKE '$searchTerm'
            OR p.topic LIKE '$searchTerm'
            OR u.username LIKE '$searchTerm'
        )
    ";
}

if ($topic !== "") {
    $safeTopic = $conn->real_escape_string($topic);

    $conditions[] = "p.topic = '$safeTopic'";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

if ($sort === "oldest") {
    $orderBy = "p.created_at ASC";
} elseif ($sort === "title_asc") {
    $orderBy = "p.title ASC";
} elseif ($sort === "title_desc") {
    $orderBy = "p.title DESC";
} else {
    $orderBy = "p.created_at DESC";
}

$sql .= "
    ORDER BY $orderBy
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="explore-page">

    <header class="explore-header">
        <?php if ($search !== ""): ?>
            <h1>Search results</h1>
            <p>Showing results for
                <strong>
                    "<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
                </strong>
            </p>
            <a href="explore.php" class="explore-clear-search">
                Clear search
            </a>

        <?php else: ?>
            <h1>Explore</h1>
            <p>
                Discover posts from the Witchy community.
            </p>
        <?php endif; ?>
    </header>


    <nav class="explore-topics">
        <a href="explore.php" class="<?php echo $topic === "" ? "active" : ""; ?>">All</a>
        <a href="explore.php?topic=Spells" class="<?php echo $topic === "Spells" ? "active" : ""; ?>">Spells</a>
        <a href="explore.php?topic=Altars" class="<?php echo $topic === "Altars" ? "active" : ""; ?>">Altars</a>
        <a href="explore.php?topic=Tarot" class="<?php echo $topic === "Tarot" ? "active" : ""; ?>">Tarot</a>
        <a href="explore.php?topic=Herbs" class="<?php echo $topic === "Herbs" ? "active" : ""; ?>">Herbs</a>
        <a href="explore.php?topic=Crystals" class="<?php echo $topic === "Crystals" ? "active" : ""; ?>">Crystals</a>
        <a href="explore.php?topic=Books" class="<?php echo $topic === "Books" ? "active" : ""; ?>">Books</a>
        <a href="explore.php?topic=Artwork" class="<?php echo $topic === "Artwork" ? "active" : ""; ?>">Artwork</a>
        <a href="explore.php?topic=Occult%20Studies" class="<?php echo $topic === "Occult Studies" ? "active" : ""; ?>">Occult Studies </a>
        <a href="explore.php?topic=Others" class="<?php echo $topic === "Others" ? "active" : ""; ?>">Others</a>
    </nav>


    <div class="explore-sort">
        <label for="explore-sort">
            Sort by:
        </label>

        <select
            id="explore-sort"
            onchange="window.location.href=this.value">
            <?php
            $baseParams = [];

            if ($search !== "") {
                $baseParams["search"] = $search;
            }

            if ($topic !== "") {
                $baseParams["topic"] = $topic;
            }

            $sortOptions = [
                "newest" => "Newest",
                "oldest" => "Oldest",
                "title_asc" => "Title A–Z",
                "title_desc" => "Title Z–A"
            ];

            foreach ($sortOptions as $value => $label):
                $params = $baseParams;
                $params["sort"] = $value;

                $url = "explore.php?" . http_build_query($params);
            ?>

                <option
                    value="<?php echo htmlspecialchars($url, ENT_QUOTES, "UTF-8"); ?>"
                    <?php echo $sort === $value ? "selected" : ""; ?>
                >
                    <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                </option>

            <?php endforeach; ?>
        </select>
    </div>


    <section class="explore-content">
        <div class="explore-grid">
            <?php if (empty($posts)): ?>
                <div class="explore-empty">
                    <?php if ($search !== ""): ?>
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
                    <article class="explore-cards">
                        <div class="explore-cards-image">
                            <a href="post.php?id=<?php echo (int) $post['post_id']; ?>">
                                <?php if (!empty($post['image'])): ?>
                                    <img
                                        src="uploads/posts/<?php echo rawurlencode(basename($post['image'])); ?>"
                                        alt="<?php echo htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>
                                    <div class="explore-cards-placeholder">
                                        <span>✦</span>
                                        No image
                                    </div>

                                <?php endif; ?>
                            </a>
                        </div>

                        <div class="explore-cards-body">
                            <p class="explore-cards-author">
                                @<?php echo htmlspecialchars($post['username']); ?>
                            </p>

                            <h2 class="explore-cards-title">
                                <a href="post.php?id=<?php echo (int) $post['post_id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>


                            <?php if (!empty($post['description'])): ?>
                                <p class="explore-cards-description">
                                    <?php
                                    echo htmlspecialchars(
                                        mb_strimwidth(
                                            $post['description'],
                                            0,
                                            140,
                                            "..."
                                        )
                                    );
                                    ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($post['topic']) || !empty($post['created_at'])): ?>
                                <div class="explore-cards-meta">
                                    <?php if (!empty($post['topic'])): ?>
                                        <p class="explore-cards-topic">
                                            <a href="explore.php?topic=<?php echo urlencode($post['topic']); ?><?php echo $search !== "" ? "&search=" . urlencode($search) : ""; ?>">
                                                <?php echo htmlspecialchars($post['topic']); ?>
                                            </a>
                                        </p>

                                    <?php endif; ?>


                                    <?php if (!empty($post['created_at'])): ?>

                                        <time
                                            class="explore-cards-date"
                                            datetime="<?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <?php echo date("M j, Y", strtotime($post['created_at'])); ?>
                                        </time>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</section>


<?php
require_once __DIR__ . "/includes/user_footer.php";
?>