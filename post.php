<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Comment.php";
require_once __DIR__ . "/models/Post.php";

function getSafeReturnUrl($url)
{
    if (!is_string($url) || $url === "" || preg_match('/[\r\n\\\\]/', $url)) {
        return null;
    }

    $parts = parse_url($url);

    if (
        $parts === false ||
        isset($parts["scheme"]) ||
        isset($parts["host"]) ||
        isset($parts["user"]) ||
        isset($parts["pass"])
    ) {
        return null;
    }

    $path = $parts["path"] ?? "";

    if (
        $path === "" ||
        $path[0] !== "/" ||
        str_starts_with($path, "//") ||
        in_array(basename($path), ["post.php", "edit_post.php"], true)
    ) {
        return null;
    }

    $returnUrl = $path;

    if (!empty($parts["query"])) {
        $returnUrl .= "?" . $parts["query"];
    }

    if (!empty($parts["fragment"])) {
        $returnUrl .= "#" . $parts["fragment"];
    }

    return $returnUrl;
}

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$commentModel = new Comment($pdo);
$postModel = new Post($pdo);

$pageTitle = "Post | Witchy";
$pageCss = "post.css";

$isLoggedIn = isset($_SESSION["user_id"]);

$user_id = $isLoggedIn
    ? (int) $_SESSION["user_id"]
    : 0;

$backUrl = $isLoggedIn
    ? "feed.php"
    : "public-explore.php";

$returnUrl = getSafeReturnUrl($_GET["return"] ?? "");

if ($returnUrl !== null) {
    $backUrl = $returnUrl;
} elseif (!empty($_SERVER["HTTP_REFERER"])) {
    $referer = parse_url($_SERVER["HTTP_REFERER"]);

    if (
        isset($referer["host"], $referer["path"]) &&
        strtolower($referer["host"]) === strtolower($_SERVER["HTTP_HOST"])
    ) {
        $refererUrl = $referer["path"];

        if (!empty($referer["query"])) {
            $refererUrl .= "?" . $referer["query"];
        }

        if (!empty($referer["fragment"])) {
            $refererUrl .= "#" . $referer["fragment"];
        }

        $safeReferer = getSafeReturnUrl($refererUrl);

        if ($safeReferer !== null) {
            $backUrl = $safeReferer;
        }
    }
}

$post_id = (int) ($_GET["id"] ?? 0);

if ($post_id <= 0) {
    header(
        "Location: " .
            ($isLoggedIn ? "feed.php" : "public-explore.php")
    );
    exit;
}

$post = $postModel->getPostById($post_id, $user_id);

if (!$post) {
    header("Location: post_not_found.php");
    exit;
}

$postMedia = $postModel->getPostMedia($post_id);
$mediaCount = count($postMedia);

$comments = $commentModel->getCommentsByPostId($post_id);

if ($isLoggedIn) {
    require_once __DIR__ . "/includes/user_header.php";
} else {
    require_once __DIR__ . "/includes/public-header.php";
}
?>

<section
    class="post-detail-page"
    data-user-id="<?= (int) $user_id ?>"
    data-replies-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

    <a
        href="<?= htmlspecialchars($backUrl, ENT_QUOTES, "UTF-8") ?>"
        class="post-detail-back">
        ← Back
    </a>

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
                                <img
                                    class="post-detail-image"
                                    src="<?= $mediaPath ?>"
                                    alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                    loading="lazy">

                            <?php elseif ($media["media_type"] === "video"): ?>
                                <video
                                    class="post-detail-video"
                                    controls
                                    playsinline
                                    preload="metadata">

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

            <div class="post-detail-author-row">

                <a
                    href="profile.php?id=<?= (int) $post["user_id"] ?>"
                    class="post-detail-avatar-link">

                    <img
                        class="post-detail-avatar"
                        src="<?= htmlspecialchars(
                            !empty($post["profile_image"])
                                ? $post["profile_image"]
                                : "images/default-profile.jpg",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        alt="<?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?>'s profile picture">

                </a>

                <a
                    href="profile.php?id=<?= (int) $post["user_id"] ?>"
                    class="post-detail-author">

                    <?= htmlspecialchars($post["username"], ENT_QUOTES, "UTF-8") ?>

                </a>

                <?php if (
                    $isLoggedIn &&
                    (int) $post["user_id"] !== $user_id
                ): ?>
                    <form action="follow_user.php" method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <input
                            type="hidden"
                            name="user_id"
                            value="<?= (int) $post["user_id"] ?>">

                        <input
                            type="hidden"
                            name="return_post"
                            value="<?= $post_id ?>">

                        <button
                            type="submit"
                            class="post-detail-follow <?= $post["user_follows"] ? "active" : "" ?>"
                            aria-label="<?= $post["user_follows"] ? "Unfollow user" : "Follow user" ?>"
                            title="<?= $post["user_follows"] ? "Following" : "Follow" ?>">

                            <?php if ($post["user_follows"]): ?>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="m5 12 4 4L19 6" />
                                </svg>
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M15 19a6 6 0 0 0-12 0" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M19 8v6M16 11h6" />
                                </svg>
                            <?php endif; ?>

                        </button>
                    </form>
                <?php endif; ?>

            </div>

            <div class="post-detail-info">

                <?php if ($mediaCount === 0 && !empty($post["topic"])): ?>
                    <div class="post-detail-topics text-only-topic">
                        <span>#<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                <?php endif; ?>

                <div class="post-detail-heading">
                    <h1>
                        <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                    </h1>

                    <?php if (
                        $isLoggedIn &&
                        (int) $post["user_id"] === $user_id
                    ): ?>
                        <details class="post-owner-menu">
                            <summary aria-label="Post options" title="Post options">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <circle cx="5" cy="12" r="1.5" />
                                    <circle cx="12" cy="12" r="1.5" />
                                    <circle cx="19" cy="12" r="1.5" />
                                </svg>
                            </summary>

                            <div class="post-owner-menu-dropdown">
                                <a
                                    href="edit_post.php?id=<?= $post_id ?>&return=<?= rawurlencode($backUrl) ?>"
                                    class="post-owner-menu-item">

                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m16 4 4 4L9 19H5v-4L16 4Z M14 6l4 4" />
                                    </svg>
                                    <span>Edit post</span>
                                </a>

                                <form
                                    action="delete_post.php"
                                    method="POST"
                                    class="delete-post-form">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                                    <input
                                        type="hidden"
                                        name="post_id"
                                        value="<?= $post_id ?>">

                                    <button type="submit" class="post-owner-menu-item delete-post-button">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-1 6h2v8H8v-8Zm6 0h2v8h-2v-8ZM6 8h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z" />
                                        </svg>
                                        <span>Delete post</span>
                                    </button>
                                </form>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post["description"])): ?>
                    <p class="post-detail-description">
                        <?= nl2br(htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8")) ?>
                    </p>
                <?php endif; ?>

                <?php if ($mediaCount > 0 && !empty($post["topic"])): ?>
                    <div class="post-detail-topics">
                        <span>#<?= htmlspecialchars($post["topic"], ENT_QUOTES, "UTF-8") ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($post["sources"])): ?>
                    <div class="post-detail-sources">
                        <strong>Sources</strong>

                        <p>
                            <?= nl2br(htmlspecialchars($post["sources"], ENT_QUOTES, "UTF-8")) ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>

            <div class="post-detail-actions">

                <?php if ($isLoggedIn): ?>
                    <form action="like_post.php" method="POST" class="post-like-form">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $post_id ?>">

                        <button
                            type="submit"
                            class="post-detail-action <?= $post["user_liked"] ? "active" : "" ?>">

                            <?= $post["user_liked"] ? "♥" : "♡" ?>

                            <span><?= (int) $post["like_count"] ?></span>
                        </button>

                    </form>
                <?php else: ?>
                    <a
                        href="register.php"
                        class="post-detail-action"
                        aria-label="Create an account to like this post">

                        ♡

                        <span><?= (int) $post["like_count"] ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <div class="post-detail-action">

                        <svg
                            class="comment-icon"
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            aria-hidden="true">

                            <path d="M20 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-4-.99L3 20l.99-4.5A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 1 1 20 11.5Z" />

                        </svg>

                        <span><?= (int) $post["comment_count"] ?></span>
                    </div>
                <?php else: ?>
                    <a
                        href="register.php"
                        class="post-detail-action"
                        aria-label="Create an account to view and join the comments">

                        <svg
                            class="comment-icon"
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            aria-hidden="true">

                            <path d="M20 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-4-.99L3 20l.99-4.5A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 1 1 20 11.5Z" />

                        </svg>

                        <span><?= (int) $post["comment_count"] ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <form action="save_post.php" method="POST">

                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $post_id ?>">

                        <input
                            type="hidden"
                            name="return_to"
                            value="post">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <button
                            type="submit"
                            class="post-detail-action save-button <?= $post["user_saved"] ? "active" : "" ?>"
                            aria-label="<?= $post["user_saved"] ? "Remove from saved" : "Save post" ?>"
                            title="<?= $post["user_saved"] ? "Saved" : "Save" ?>">

                            <svg
                                class="save-icon"
                                viewBox="0 0 24 24"
                                width="20"
                                height="20"
                                aria-hidden="true">

                                <path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z" />

                            </svg>

                        </button>
                    </form>
                <?php else: ?>
                    <a
                        href="register.php"
                        class="post-detail-action save-button"
                        aria-label="Create an account to save this post"
                        title="Save post">

                        <svg
                            class="save-icon"
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            aria-hidden="true">

                            <path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75v17l-6-3.75-6 3.75v-17Z" />

                        </svg>
                    </a>
                <?php endif; ?>

            </div>

            <section class="post-detail-comments">

                <h2>Comments</h2>

                <div class="post-detail-comment-list">

                    <?php if (!empty($comments)): ?>

                        <?php foreach ($comments as $comment): ?>

                            <?php
                            $commentId = (int) $comment["comment_id"];

                            $replyCount = $commentModel->getReplyCountByCommentId($commentId);

                            $hasLiked = $isLoggedIn
                                ? $commentModel->hasUserLikedComment($commentId, $user_id)
                                : false;

                            $likeCount = $commentModel->getCommentLikeCount($commentId);
                            ?>

                            <article
                                class="post-detail-comment"
                                data-comment-id="<?= $commentId ?>">

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

                                        <span>
                                            <?= htmlspecialchars(date("j M", strtotime($comment["created_at"])), ENT_QUOTES, "UTF-8") ?>
                                        </span>

                                        <?php if (
                                            $isLoggedIn &&
                                            (int) $comment["user_id"] === $user_id
                                        ): ?>
                                            <form
                                                action="comment_delete.php"
                                                method="POST"
                                                class="delete-comment-form">

                                                <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                                <input type="hidden" name="return_to" value="post">

                                                <button type="submit" class="delete-comment-button">
                                                    Delete
                                                </button>
                                            </form>

                                        <?php elseif ($isLoggedIn): ?>
                                            <button
                                                type="button"
                                                class="report-button"
                                                data-report-type="comment"
                                                data-target-id="<?= $commentId ?>">
                                                Report
                                            </button>
                                        <?php endif; ?>

                                    </div>

                                    <?php if ($comment["comment"] !== ""): ?>
                                        <p>
                                            <?= nl2br(htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8")) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($comment["media_file"])): ?>
                                        <?php
                                        $commentImage = "uploads/comments/" .
                                            rawurlencode(basename($comment["media_file"]));
                                        ?>

                                        <a
                                            href="<?= htmlspecialchars($commentImage, ENT_QUOTES, "UTF-8") ?>"
                                            target="_blank"
                                            rel="noopener noreferrer">

                                            <img
                                                class="post-comment-media"
                                                src="<?= htmlspecialchars($commentImage, ENT_QUOTES, "UTF-8") ?>"
                                                alt="Comment attachment"
                                                loading="lazy">

                                        </a>
                                    <?php endif; ?>

                                    <div class="comment-actions">

                                        <?php if ($isLoggedIn): ?>
                                            <form
                                                action="comment_like.php"
                                                method="POST"
                                                class="comment-like-form">

                                                <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                                <input type="hidden" name="return_to" value="post">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                                                <button
                                                    type="submit"
                                                    class="comment-like-button <?= $hasLiked ? "active" : "" ?>"
                                                    aria-label="<?= $hasLiked ? "Unlike comment" : "Like comment" ?>"
                                                    aria-pressed="<?= $hasLiked ? "true" : "false" ?>">

                                                    <?= $hasLiked ? "♥" : "♡" ?>

                                                </button>

                                                <span class="comment-like-count"><?= $likeCount ?></span>

                                            </form>

                                            <button
                                                type="button"
                                                class="reply-toggle-button"
                                                data-comment-id="<?= $commentId ?>">
                                                Reply
                                            </button>

                                        <?php else: ?>
                                            <a
                                                href="register.php"
                                                class="comment-like-button"
                                                aria-label="Create an account to like this comment">
                                                ♡
                                            </a>

                                            <span class="comment-like-count"><?= $likeCount ?></span>
                                        <?php endif; ?>

                                    </div>

                                    <?php if ($isLoggedIn): ?>
                                        <form
                                            action="comment_post.php"
                                            method="POST"
                                            class="comment-reply-form"
                                            data-parent-comment-id="<?= $commentId ?>"
                                            hidden>

                                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                            <input type="hidden" name="parent_comment_id" value="<?= $commentId ?>">
                                            <input type="hidden" name="return_to" value="post">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                                            <textarea
                                                name="comment"
                                                placeholder="Write a reply..."
                                                maxlength="1000"
                                                required></textarea>

                                            <button type="submit">
                                                Reply
                                            </button>
                                        </form>

                                        <?php if ($replyCount > 0): ?>
                                            <button
                                                type="button"
                                                class="view-replies-button post-detail-view-replies"
                                                data-comment-id="<?= $commentId ?>"
                                                data-reply-count="<?= $replyCount ?>"
                                                aria-expanded="false">

                                                View <?= $replyCount ?> <?= $replyCount === 1 ? "reply" : "replies" ?>

                                            </button>
                                        <?php endif; ?>

                                        <div
                                            class="comment-replies"
                                            data-comment-id="<?= $commentId ?>"
                                            data-post-id="<?= $post_id ?>"
                                            data-loaded="0"
                                            data-offset="0"
                                            hidden>
                                        </div>

                                    <?php elseif ($replyCount > 0): ?>
                                        <div class="post-detail-reply-section">

                                            <button
                                                type="button"
                                                class="view-replies-button post-detail-view-replies"
                                                data-comment-id="<?= $commentId ?>"
                                                data-reply-count="<?= $replyCount ?>"
                                                aria-expanded="false">

                                                View <?= $replyCount ?> <?= $replyCount === 1 ? "reply" : "replies" ?>

                                            </button>

                                            <div
                                                class="post-detail-replies"
                                                data-comment-id="<?= $commentId ?>"
                                                data-loaded="0"
                                                data-offset="0"
                                                hidden>
                                            </div>

                                        </div>
                                    <?php endif; ?>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php else: ?>
                        <p class="post-detail-no-comments">
                            No comments yet. Be the first to comment.
                        </p>
                    <?php endif; ?>

                </div>

                <?php if ((int) $post["comment_count"] > count($comments)): ?>
                    <button
                        type="button"
                        class="load-more-comments-button"
                        data-post-id="<?= (int) $post_id ?>"
                        data-offset="<?= count($comments) ?>">
                        Show more comments
                    </button>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <form
                        action="comment_post.php"
                        method="POST"
                        enctype="multipart/form-data"
                        class="post-detail-comment-form">

                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                        <input type="hidden" name="return_to" value="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

                        <input
                            type="text"
                            name="comment"
                            placeholder="Add a comment..."
                            maxlength="1000">

                        <label class="comment-upload-button" title="Add image or GIF">
                            📷

                            <input
                                type="file"
                                name="comment_media"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="comment-media-input"
                                hidden>
                        </label>

                        <button type="submit" aria-label="Post comment">
                            ➤
                        </button>

                        <div class="comment-media-preview" hidden>
                            <img
                                class="comment-preview-image"
                                alt="Selected attachment">

                            <button
                                type="button"
                                class="comment-preview-remove"
                                aria-label="Remove selected image">
                                ×
                            </button>
                        </div>

                    </form>
                <?php else: ?>
                    <p class="post-detail-register-prompt">
                        <a href="register.php" class="post-detail-register-link">Create an account</a> to join the conversation.
                    </p>
                <?php endif; ?>

            </section>

        </article>

    </div>

</section>

<div
    id="report-modal"
    class="report-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="report-modal-title"
    hidden>

    <div class="report-modal-content">

        <button
            type="button"
            class="report-modal-close"
            aria-label="Close report form">
            &times;
        </button>

        <h2 id="report-modal-title">
            Report comment
        </h2>

        <p>
            Why are you reporting this content?
        </p>

        <form id="report-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">

            <input
                type="hidden"
                name="report_type"
                id="report-type"
                value="comment">

            <input
                type="hidden"
                name="target_id"
                id="report-target-id">

            <label for="report-reason">
                Reason
            </label>

            <select
                name="reason"
                id="report-reason"
                required>

                <option value="">Select a reason</option>
                <option value="Harassment or bullying">Harassment or bullying</option>
                <option value="Spam">Spam</option>
                <option value="Hate speech">Hate speech</option>
                <option value="Inappropriate content">Inappropriate content</option>
                <option value="Misinformation">Misinformation</option>
                <option value="Other">Other</option>

            </select>

            <label for="report-details">
                Additional details (optional)
            </label>

            <textarea
                name="details"
                id="report-details"
                maxlength="1000"
                placeholder="Tell us more about the issue..."></textarea>

            <div class="report-modal-actions">

                <button
                    type="button"
                    class="report-cancel-button">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="report-submit-button">
                    Submit report
                </button>

            </div>

            <p
                id="report-message"
                class="report-message"
                role="status"
                aria-live="polite"
                hidden>
            </p>

        </form>

    </div>

</div>

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
<script src="assets/js/post.js"></script>

<?php if (!$isLoggedIn): ?>
    <script src="assets/js/post-replies.js" defer></script>
<?php endif; ?>

<?php
if ($isLoggedIn) {
    require_once __DIR__ . "/includes/user_footer.php";
} else {
    require_once __DIR__ . "/includes/public-footer.php";
}
?>
