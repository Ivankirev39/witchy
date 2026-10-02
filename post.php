<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
$pageTitle = "Post | Witchy";
$pageCss = "post.css";
$user_id = (int) $_SESSION["user_id"];
// GET POST ID
$post_id = (int) ($_GET["id"] ?? 0);
if ($post_id <= 0) {
    header("Location: feed.php");
    exit;
}
// GET POST + AUTHOR + COUNTS
$stmt = $conn->prepare("
    SELECT
        post.*,
        users.username,
        users.profile_image,
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
        EXISTS (
            SELECT 1
            FROM post_like
            WHERE post_like.post_id = post.post_id
            AND post_like.user_id = ?
        ) AS user_liked,
        EXISTS (
            SELECT 1
            FROM save
            WHERE save.post_id = post.post_id
            AND save.user_id = ?
        ) AS user_saved,
        EXISTS (
            SELECT 1
            FROM follow
            WHERE follow.follower_id = ?
            AND follow.following_id = post.user_id
        ) AS user_follows
    FROM post
    INNER JOIN users ON users.user_id = post.user_id
    WHERE post.post_id = ?
");
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    http_response_code(404);
    die("Post not found.");
}
// GET POST MEDIA
$mediaStmt = $conn->prepare("
    SELECT media_id, file, media_type, sort_order
    FROM post_media
    WHERE post_id = ?
    ORDER BY sort_order ASC, media_id ASC
");
$mediaStmt->bind_param("i", $post_id);
$mediaStmt->execute();
$mediaResult = $mediaStmt->get_result();
$postMedia = [];
while ($media = $mediaResult->fetch_assoc()) {
    $postMedia[] = $media;
}
$mediaCount = count($postMedia);
// GET COMMENTS + COMMENT AUTHORS
$commentStmt = $conn->prepare("
    SELECT
        comment.comment_id,
        comment.comment,
        comment.created_at,
        users.user_id,
        users.username,
        users.profile_image
    FROM comment
    INNER JOIN users ON users.user_id = comment.user_id
    WHERE comment.post_id = ?
    ORDER BY comment.created_at ASC
");
$commentStmt->bind_param("i", $post_id);
$commentStmt->execute();
$comments = $commentStmt->get_result();
require_once __DIR__ . "/includes/user_header.php";
?>
<section class="post-detail-page">
    <a href="feed.php" class="post-detail-back">← Back</a>
    <div class="post-detail-layout <?= $mediaCount === 0 ? "text-only" : "" ?>">
        <?php if ($mediaCount > 0): ?>
            <div class="post-detail-media">
                <div class="post-detail-media-gallery post-detail-media-count-<?= $mediaCount ?>">
                    <?php foreach ($postMedia as $media): ?>
                        <?php
                        $mediaFile = basename($media["file"]);
                        $mediaPath = "uploads/posts/" . rawurlencode($mediaFile);
                        ?>
                        <div class="post-detail-media-item">
                            <?php if ($media["media_type"] === "image"): ?>
                                <img class="post-detail-image" src="<?= $mediaPath ?>" alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>" loading="lazy">
                            <?php elseif ($media["media_type"] === "video"): ?>
                                <video class="post-detail-video" controls playsinline preload="metadata">
                                    <source src="<?= $mediaPath ?>">
                                    Your browser does not support video playback.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <article class="post-detail-content">
            <header class="post-detail-author-row">
                <div class="post-detail-author">
                    <?php if (!empty($post["profile_image"])): ?>
                        <img class="post-detail-avatar" src="<?= htmlspecialchars($post["profile_image"], ENT_QUOTES, "UTF-8") ?>" alt="">
                    <?php else: ?>
                        <div class="post-detail-avatar post-detail-avatar-fallback"><?= htmlspecialchars(strtoupper(substr($post["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?></div>
                    <?php endif; ?>
                    <div>
                        <strong><?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?></strong>
                        <span class="post-detail-date"><?= htmlspecialchars(date("j M Y", strtotime($post["created_at"])), ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                </div>
                <?php if ((int) $post["user_id"] !== $user_id): ?>
                    <form action="follow_user.php" method="POST">
                        <input type="hidden" name="user_id" value="<?= (int) $post["user_id"] ?>">
                        <input type="hidden" name="return_post" value="<?= $post_id ?>">
                        <button type="submit" class="post-detail-follow <?= $post["user_follows"] ? "active" : "" ?>"><?= $post["user_follows"] ? "Following" : "Follow" ?></button>
                    </form>
                <?php endif; ?>
            </header>
            <div class="post-detail-info">
                <?php if ($mediaCount === 0 && !empty($post["topic"])): ?>
                    <div class="post-detail-topics text-only-topic">
                        <span>#<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                <?php endif; ?>
                <h1><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></h1>
                <?php if (!empty($post["description"])): ?>
                    <p class="post-detail-description"><?= nl2br(htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8")) ?></p>
                <?php endif; ?>
                <?php if ($mediaCount > 0 && !empty($post["topic"])): ?>
                    <div class="post-detail-topics">
                        <span>#<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($post["sources"])): ?>
                    <div class="post-detail-sources">
                        <strong>Sources</strong>
                        <p><?= nl2br(htmlspecialchars($post["sources"], ENT_QUOTES, "UTF-8")) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="post-detail-actions">
                <form action="like_post.php" method="POST">
                    <input type="hidden" name="post_id" value="<?= $post_id ?>">
                    <button type="submit" class="post-detail-action <?= $post["user_liked"] ? "active" : "" ?>">
                        <?= $post["user_liked"] ? "♥" : "♡" ?>
                        <span><?= (int) $post["like_count"] ?></span>
                    </button>
                </form>
                <div class="post-detail-action">
                    ◯
                    <span><?= (int) $post["comment_count"] ?></span>
                </div>
                <form action="save_post.php" method="POST">
                    <input type="hidden" name="post_id" value="<?= $post_id ?>">
                    <button type="submit" class="post-detail-action save-button <?= $post["user_saved"] ? "active" : "" ?>" aria-label="<?= $post["user_saved"] ? "Remove from saved" : "Save post" ?>" title="<?= $post["user_saved"] ? "Saved" : "Save" ?>">
                        <svg class="save-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                            <path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z"/>
                        </svg>
                    </button>
                </form>
            </div>
            <section class="post-detail-comments">
                <h2>Comments</h2>
                <div class="post-detail-comment-list">
                    <?php if ($comments->num_rows > 0): ?>
                        <?php while ($comment = $comments->fetch_assoc()): ?>
                            <article class="post-detail-comment">
                                <?php if (!empty($comment["profile_image"])): ?>
                                    <img class="comment-avatar" src="<?= htmlspecialchars($comment["profile_image"], ENT_QUOTES, "UTF-8") ?>" alt="">
                                <?php else: ?>
                                    <div class="comment-avatar comment-avatar-fallback"><?= htmlspecialchars(strtoupper(substr($comment["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?></div>
                                <?php endif; ?>
                                <div class="comment-content">
                                    <div class="comment-meta">
                                        <strong><?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?></strong>
                                        <span><?= htmlspecialchars(date("j M", strtotime($comment["created_at"])), ENT_QUOTES, "UTF-8") ?></span>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8")) ?></p>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="post-detail-no-comments">No comments yet. Be the first to comment.</p>
                    <?php endif; ?>
                </div>
                <form action="comment_post.php" method="POST" class="post-detail-comment-form">
                    <input type="hidden" name="post_id" value="<?= $post_id ?>">
                    <input type="text" name="comment" placeholder="Add a comment..." maxlength="1000" required>
                    <button type="submit" aria-label="Post comment">➤</button>
                </form>
            </section>
        </article>
    </div>
</section>
<!-- IMAGE LIGHTBOX -->
<div
    id="image-modal"
    class="image-modal"
    aria-hidden="true"
>
    <button
        type="button"
        class="image-modal-close"
        aria-label="Close image"
    >
        &times;
    </button>

    <img
        id="modal-image"
        class="image-modal-content"
        src=""
        alt=""
    >
</div>  

<script src="assets/js/main.js"></script>


<?php
$mediaStmt->close();
$commentStmt->close();
$stmt->close();
require_once __DIR__ . "/includes/user_footer.php";
?>