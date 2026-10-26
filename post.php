<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Comment.php";

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$commentModel = new Comment($pdo);

$pageTitle = "Post | Witchy";
$pageCss = "post.css";

$isLoggedIn = isset($_SESSION["user_id"]);

$user_id = $isLoggedIn
    ? (int) $_SESSION["user_id"]
    : 0
;

$backUrl = $isLoggedIn
    ? "feed.php"
    : "public-explore.php"
;

if (!empty($_SERVER["HTTP_REFERER"])) {
    $referer = parse_url($_SERVER["HTTP_REFERER"]);

    if (
        isset($referer["host"], $referer["path"]) &&
        $referer["host"] === $_SERVER["HTTP_HOST"] &&
        basename($referer["path"]) !== "post.php"
    ) {
        $backUrl = $referer["path"];

        if (!empty($referer["query"])) {
            $backUrl .= "?" . $referer["query"];
        }
    }
}

// GET POST ID
$post_id = (int) ($_GET["id"] ?? 0);
if ($post_id <= 0) {
    header(
        "Location: " .
        ($isLoggedIn ? "feed.php" : "public-explore.php")
    );
    exit;
}

// GET POST + AUTHOR + COUNTS
$stmt = $pdo->prepare("
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

$stmt->execute([$user_id, $user_id, $user_id, $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("Location: post_not_found.php");
    exit;
}

// GET POST MEDIA
$mediaStmt = $pdo->prepare("
    SELECT media_id, file, media_type, sort_order
    FROM post_media
    WHERE post_id = ?
    ORDER BY sort_order ASC, media_id ASC
");

$mediaStmt->execute([$post_id]);
$postMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
$mediaCount = count($postMedia);

// GET COMMENTS + COMMENT AUTHORS
$comments = $commentModel->getCommentsByPostId($post_id);

if ($isLoggedIn) {
    require_once __DIR__ . "/includes/user_header.php";
} else {
    require_once __DIR__ . "/includes/public-header.php";
}
?>

<section class="post-detail-page">
    <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, "UTF-8") ?>" class="post-detail-back">← Back</a>

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
                    <a
                        href="profile.php?id=<?= (int) $post["user_id"] ?>"
                        class="post-detail-avatar-link">

                        <?php if (!empty($post["profile_image"])): ?>
                            <img
                                class="post-detail-avatar"
                                src="<?= htmlspecialchars($post["profile_image"], ENT_QUOTES, "UTF-8") ?>"
                                alt="">
                        <?php else: ?>
                            <div class="post-detail-avatar post-detail-avatar-fallback">
                                <?= htmlspecialchars(strtoupper(substr($post["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?>
                            </div>
                        <?php endif; ?>

                    </a>

                    <div>
                        <strong>
                            <a
                                href="profile.php?id=<?= (int) $post["user_id"] ?>"
                                class="post-author-link">
                                <?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?>
                            </a>
                        </strong>

                        <span class="post-detail-date"><?= htmlspecialchars(date("j M Y", strtotime($post["created_at"])), ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                </div>

                <?php if (
                    $isLoggedIn &&
                    (int) $post["user_id"] !== $user_id
                ): ?>
                    <form action="follow_user.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
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
                <?php if ($isLoggedIn): ?>
                    <form action="like_post.php" method="POST" class="post-like-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                        >
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">

                        <button
                            type="submit"
                            class="post-detail-action <?= $post["user_liked"] ? "active" : "" ?>"
                        >
                            <?= $post["user_liked"] ? "♥" : "♡" ?>
                            <span><?= (int) $post["like_count"] ?></span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="post-detail-action">
                        ♡ <span>Log in to like</span>
                    </a>
                <?php endif; ?>

                <div class="post-detail-action">
                    ◯
                    <span><?= (int) $post["comment_count"] ?></span>
                </div>

                <?php if ($isLoggedIn): ?>
                    <form action="save_post.php" method="POST">
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">

                        <button type="submit" class="post-detail-action save-button <?= $post["user_saved"] ? "active" : "" ?>" aria-label="<?= $post["user_saved"] ? "Remove from saved" : "Save post" ?>" title="<?= $post["user_saved"] ? "Saved" : "Save" ?>">
                            <svg class="save-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                <path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z" />
                            </svg>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="post-detail-action">
                        Log in to save
                    </a>
                <?php endif; ?>

                <!-- DELETE -->
                <?php if (
                    $isLoggedIn &&
                    (int) $post["user_id"] === $user_id
                ): ?>
                    <form
                        action="delete_post.php"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this post?');">

                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $post_id ?>">

                        <button
                            type="submit"
                            class="post-detail-action delete-post-button"
                            aria-label="Delete post"
                            title="Delete">
                            <svg class="delete-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-1 6h2v8H8v-8Zm6 0h2v8h-2v-8ZM6 8h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z" />
                            </svg>
                            <span>Delete</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <section class="post-detail-comments">
                <h2>Comments</h2>

                <div class="post-detail-comment-list">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <article class="post-detail-comment">
                                <a
                                    href="profile.php?id=<?= (int) $comment["user_id"] ?>"
                                    class="comment-avatar-link">
                                    <img
                                        class="comment-avatar"
                                        src="<?= htmlspecialchars(
                                            !empty($comment["profile_image"])
                                                ? $comment["profile_image"]
                                                : "images/default-profile.jpg",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                        alt="<?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?>'s profile picture">
                                </a>

                                <div class="comment-content">
                                    <div class="comment-meta">
                                        <strong>
                                            <a
                                                href="profile.php?id=<?= (int) $comment["user_id"] ?>"
                                                class="comment-author-link">
                                                <?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?>
                                            </a>
                                        </strong>

                                        <span><?= htmlspecialchars(date("j M", strtotime($comment["created_at"])), ENT_QUOTES, "UTF-8") ?></span>

                                        <?php if (
                                            $isLoggedIn &&
                                            (int) $comment["user_id"] === $user_id
                                        ): ?>
                                            <form action="comment_delete.php" method="POST" class="delete-comment-form">
                                                <input type="hidden" name="comment_id" value="<?= (int) $comment["comment_id"] ?>">
                                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                                <input type="hidden" name="return_to" value="post">

                                                <button type="submit" class="delete-comment-button">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <p><?= nl2br(htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8")) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="post-detail-no-comments">No comments yet. Be the first to comment.</p>
                    <?php endif; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                    <form action="comment_post.php" method="POST" class="post-detail-comment-form">
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                        <input type="text" name="comment" placeholder="Add a comment..." maxlength="1000" required>
                        <button type="submit" aria-label="Post comment">➤</button>
                    </form>
                <?php else: ?>
                    <p>
                        <a href="login.php">Log in</a> to join the conversation.
                    </p>
                <?php endif; ?>
            </section>
        </article>
    </div>
</section>

<!-- IMAGE LIGHTBOX -->
<div
    id="image-modal"
    class="image-modal"
    aria-hidden="true">

    <button
        type="button"
        class="image-modal-close"
        aria-label="Close image">
        &times;
    </button>

    <img
        id="modal-image"
        class="image-modal-content"
        src=""
        alt="">
</div>

<script src="assets/js/main.js"></script>

<?php
if ($isLoggedIn) {
    require_once __DIR__ . "/includes/user_footer.php";
} else {
    require_once __DIR__ . "/includes/public-footer.php";
}
?>