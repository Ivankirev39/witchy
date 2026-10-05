<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Saved | Witchy";
$pageCss = "saved.css";

$user_id = (int) $_SESSION["user_id"];

$savedPosts = [];

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
    FROM save s
    INNER JOIN post p
        ON p.post_id = s.post_id
    INNER JOIN users u
        ON u.user_id = p.user_id
    WHERE s.user_id = ?
    ORDER BY s.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $savedPosts[] = $row;
}

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="saved-page">

    <header class="saved-header">
        <h1>Saved</h1>
        <p>
            <?php
            $savedCount = count($savedPosts);

            if ($savedCount === 1) {
                echo "1 saved post.";
            } else {
                echo $savedCount . " saved posts.";
            }
            ?>
        </p>
    </header>


    <section class="saved-content">

        <?php if (empty($savedPosts)): ?>
            <div class="saved-empty">
                <h2>No saved posts yet</h2>
                <p>
                    Posts you save will appear here.
                </p>
                <a href="explore.php" class="saved-empty-button">
                    Explore posts
                </a>
            </div>


        <?php else: ?>

            <div class="saved-grid">
                <?php foreach ($savedPosts as $post): ?>

                    <article
                            class="saved-card"
                            role="link"
                            tabindex="0"
                            onclick="window.location.href='post.php?id=<?= (int) $post["post_id"] ?>'"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { window.location.href='post.php?id=<?= (int) $post["post_id"] ?>'; }"
                        >

                        <form action="save_post.php" method="POST" class="saved-card-unsave-form">
                            <input
                                type="hidden"
                                name="post_id"
                                value="<?= (int) $post["post_id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="redirect"
                                value="saved.php"
                            >

                            <button
                                type="submit"
                                class="saved-card-unsave"
                                aria-label="Remove from saved"
                                title="Remove from saved"
                                onclick="event.stopPropagation();"
                            >
                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                    <path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z" />
                                </svg>
                            </button>
                        </form>


                        <div class="saved-card-image">
                            <?php if (!empty($post["image"])): ?>
                                <img
                                    src="uploads/posts/<?= rawurlencode(basename($post["image"])) ?>"
                                    alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                    loading="lazy"
                                >
                            <?php else: ?>

                                <div class="saved-card-placeholder">
                                    <span>✦</span>
                                    No image
                                </div>

                            <?php endif; ?>
                        </div>

                        <div class="saved-card-body">
                            <p class="saved-card-author">
                                @<?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?>
                            </p>
                            <h2 class="saved-card-title">
                                <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                            </h2>

                            <?php if (!empty($post["description"])): ?>

                                <p class="saved-card-description">
                                    <?= htmlspecialchars(
                                        mb_strimwidth(
                                            $post["description"],
                                            0,
                                            140,
                                            "..."
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($post["topic"])): ?>
                                <p class="saved-card-topic">
                                    <a
                                        href="explore.php?topic=<?= urlencode($post["topic"]) ?>"
                                        onclick="event.stopPropagation();"
                                    >
                                        <?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                                    </a>
                                </p>

                            <?php endif; ?>

                        </div>
                    </article>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php
$stmt->close();

require_once __DIR__ . "/includes/user_footer.php";
?>

