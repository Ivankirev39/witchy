<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

$pageTitle = "Home | Witchy";
$user_id = (int) $_SESSION["user_id"];

// GET POSTS
$sql = "
    SELECT
        post.*,
        users.username AS author_username,
        users.profile_image AS author_profile_image,
        (
            SELECT COUNT(*)
            FROM post_like
            WHERE post_like.post_id = post.post_id
        ) AS like_count,
        (
            SELECT COUNT(*)
            FROM comment
            WHERE comment.post_id = post.post_id
        ) AS comment_count,
        EXISTS(
            SELECT 1
            FROM post_like
            WHERE post_like.post_id = post.post_id
            AND post_like.user_id = $user_id
        ) AS user_liked,
        EXISTS(
            SELECT 1
            FROM save
            WHERE save.post_id = post.post_id
            AND save.user_id = $user_id
        ) AS user_saved
    FROM post
    JOIN users ON users.user_id = post.user_id
    ORDER BY post.created_at DESC
";

$result = $conn->query($sql);

// PREPARE MEDIA QUERY
$mediaStmt = $conn->prepare("
    SELECT media_id, file, media_type, sort_order
    FROM post_media
    WHERE post_id = ?
    ORDER BY sort_order ASC, media_id ASC
");

require_once __DIR__ . "/includes/user_header.php";
?>

<section class="feed-page">
    <!-- FEED HEADER -->
    <header class="feed-header">
        <div>
            <h1>Home</h1>
            <p>
                Welcome back,
                <?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?>.
            </p>
        </div>
    </header>

    <!-- FEED TABS -->
    <div class="feed-tabs">
        <button class="feed-tab active">For you</button>
        <button class="feed-tab">Following</button>
    </div>

    <!-- FEED -->
    <section class="feed-content">
        <?php while ($post = $result->fetch_assoc()): ?>
            <?php
            $postId = (int) $post["post_id"];

            // GET MEDIA FOR THIS POST
            $mediaStmt->bind_param("i", $postId);
            $mediaStmt->execute();
            $mediaResult = $mediaStmt->get_result();

            $postMedia = [];
            while ($media = $mediaResult->fetch_assoc()) {
                $postMedia[] = $media;
            }

            $mediaCount = count($postMedia);
            ?>

            <article class="post-card" id="post-<?= $postId ?>">

                <!-- POST AUTHOR -->
                <div class="post-author">
                    <?php if (!empty($post["author_profile_image"])): ?>
                        <img
                            class="post-avatar"
                            src="<?= htmlspecialchars($post["author_profile_image"], ENT_QUOTES, "UTF-8") ?>"
                            alt=""
                        >
                    <?php else: ?>
                        <div class="post-avatar">
                            <?= htmlspecialchars(
                                strtoupper(substr($post["author_username"], 0, 1)),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </div>
                    <?php endif; ?>

                    <div>
                        <strong>
                            <?= htmlspecialchars($post["author_username"], ENT_QUOTES, "UTF-8") ?>
                        </strong>
                        <p>
                            <?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </div>
                </div>

                <!-- POST MEDIA -->
                <?php if ($mediaCount > 0): ?>
                    <div class="post-media post-media-count-<?= $mediaCount ?>">
                        <?php foreach ($postMedia as $media): ?>
                            <?php
                            $mediaFile = basename($media["file"]);
                            $mediaPath = "uploads/posts/" . rawurlencode($mediaFile);
                            ?>

                            <div class="post-media-item">
                                <?php if ($media["media_type"] === "image"): ?>
                                    <a
                                        href="post.php?id=<?= $postId ?>"
                                        class="post-media-link"
                                    >
                                        <img
                                            class="post-media-image"
                                            src="<?= $mediaPath ?>"
                                            alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                            loading="lazy"
                                        >
                                    </a>

                                <?php elseif ($media["media_type"] === "video"): ?>
                                    <video
                                        class="post-media-video"
                                        controls
                                        preload="metadata"
                                    >
                                        <source src="<?= $mediaPath ?>">
                                        Your browser does not support video playback.
                                    
                                    <video
                                        class="post-media-video"
                                        controls
                                        muted
                                        playsinline
                                        preload="metadata"
                                    >
                                        <source src="<?= $mediaPath ?>">
                                        Your browser does not support video playback.
                                    </video>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- POST BODY -->
                <div class="post-body">
                    <strong>
                        <a
                            href="post.php?id=<?= $postId ?>"
                            class="post-title-link"
                        >
                            <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                        </a>
                    </strong>

                    <?php if (!empty($post["description"])): ?>
                        <p>
                            <?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($post["topic"])): ?>
                        <span class="post-topic">
                            #<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- POST ACTIONS -->
                <div class="post-actions">

                    <!-- LIKE -->
                    <form action="like_post.php" method="POST">
                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $postId ?>"
                        >
                        <button
                            type="submit"
                            class="post-action-button like-button <?= $post["user_liked"] ? "active" : "" ?>"
                            aria-label="<?= $post["user_liked"] ? "Unlike post" : "Like post" ?>"
                        >
                            <span class="like-heart">
                                <?= $post["user_liked"] ? "♥" : "♡" ?>
                            </span>
                            <?= (int) $post["like_count"] ?>
                        </button>
                    </form>

                    <!-- COMMENTS -->
                    <a
                        href="?comments=<?= $postId ?>#post-<?= $postId ?>"
                        class="post-action-button"
                    >
                        💬 <?= (int) $post["comment_count"] ?>
                    </a>

                    <!-- SAVE -->
                    <form action="save_post.php" method="POST">
                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $postId ?>"
                        >
                        <button
                            type="submit"
                            class="post-action-button save-button <?= $post["user_saved"] ? "active" : "" ?>"
                            aria-label="<?= $post["user_saved"] ? "Remove from saved" : "Save post" ?>"
                            title="<?= $post["user_saved"] ? "Saved" : "Save" ?>"
                        >
                            <svg
                                class="save-icon"
                                viewBox="0 0 24 24"
                                width="20"
                                height="20"
                                aria-hidden="true"
                            >
                                <path
                                    d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z"
                                />
                            </svg>
                        </button>
                    </form>
                </div>

                <!-- EXPANDED COMMENTS -->
                <?php if (
                    isset($_GET["comments"]) &&
                    (int) $_GET["comments"] === $postId
                ): ?>
                    <div class="post-comments">
                        <?php
                        $commentStmt = $conn->prepare("
                            SELECT
                                comment.comment,
                                comment.created_at,
                                users.username
                            FROM comment
                            JOIN users ON comment.user_id = users.user_id
                            WHERE comment.post_id = ?
                            ORDER BY comment.created_at ASC
                        ");

                        $commentStmt->bind_param("i", $postId);
                        $commentStmt->execute();
                        $comments = $commentStmt->get_result();
                        ?>

                        <?php if ($comments->num_rows > 0): ?>
                            <?php while ($comment = $comments->fetch_assoc()): ?>
                                <div class="comment">
                                    <strong>
                                        <?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?>
                                    </strong>
                                    <p>
                                        <?= htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8") ?>
                                    </p>
                                    <small>
                                        <?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?>
                                    </small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>

                        <?php $commentStmt->close(); ?>
                    </div>
                <?php endif; ?>

                <!-- ADD COMMENT -->
                <form
                    action="comment_post.php"
                    method="POST"
                    class="comment-form"
                >
                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= $postId ?>"
                    >
                    <input
                        type="text"
                        name="comment"
                        placeholder="Write a comment..."
                        maxlength="500"
                        required
                    >
                    <button type="submit">Post</button>
                </form>

            </article>
        <?php endwhile; ?>

        <?php $mediaStmt->close(); ?>
    </section>
</section>

<script src="assets/js/feed.js" defer></script>

<?php
require_once __DIR__ . "/includes/user_footer.php";
?>