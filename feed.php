<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/Comment.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/PostFeed.php";
require_once __DIR__ . "/models/Report.php";

$pageTitle = "Home | Witchy";
$user_id = (int) $_SESSION["user_id"];
$returnUrl = $_SERVER["REQUEST_URI"];

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$postFeedModel = new PostFeed($pdo);
$commentModel = new Comment($pdo);

$feedMode = ($_GET["feed"] ?? "for_you") === "following"
    ? "following"
    : "for_you";

$pageSize = 10;

$totalPosts = $postFeedModel->getFeedPostCount(
    $user_id,
    $feedMode === "following"
);

$totalPages = max(1, (int) ceil($totalPosts / $pageSize));

$page = max(1, (int) ($_GET["page"] ?? 1));
$page = min($page, $totalPages);

$posts = $postFeedModel->getFeedPosts(
    $user_id,
    $feedMode === "following",
    $pageSize,
    ($page - 1) * $pageSize
);


$pageCss = "feed.css";

$postIds = array_column($posts, "post_id");
$postMediaByPost = $postFeedModel->getPostMediaForPosts($postIds);
$commentsByPost = $commentModel->getCommentsForPosts(
    $postIds,
    5,
    $user_id
);

require_once __DIR__ . "/includes/user_header.php";
?>
<section class="feed-page" data-user-id="<?= (int) $user_id ?>">
    <!-- FEED HEADER -->
    <header class="feed-header">
        <div>
            <h1>Home</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION["username"], ENT_QUOTES, "UTF-8") ?>.</p>
        </div>
    </header>
    <!-- FEED TABS -->
    <div class="feed-tabs">
        <a
            href="feed.php?feed=for_you"
            class="feed-tab <?= $feedMode === "for_you" ? "active" : "" ?>">
            For you
        </a>
        <a
            href="feed.php?feed=following"
            class="feed-tab <?= $feedMode === "following" ? "active" : "" ?>">
            Following
        </a>
    </div>
    <!-- FEED -->
    <section class="feed-content">
        <?php foreach ($posts as $post): ?>
            <?php
            $postId = (int) $post["post_id"];
            $postMedia = $postMediaByPost[$postId] ?? [];
            $mediaCount = count($postMedia);
            ?>
            <article class="post-card" id="post-<?= $postId ?>">
                <!-- POST AUTHOR -->

                <?php if (!empty($post["is_sticky"])): ?>
                    <div class="admin-pinned-badge">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 3h6l-1 6 3 3v2H7v-2l3-3-1-6Z" />
                            <path d="M12 14v7" />
                        </svg>
                        <span>Admin Pinned</span>
                    </div>
                <?php endif; ?>

                <div class="post-author">
                    <a
                        href="profile.php?id=<?= (int) $post["user_id"] ?>&return=<?= urlencode($returnUrl) ?>"
                        class="post-author-avatar-link">
                        <img
                            src="<?= htmlspecialchars(
                                        !empty($post["author_profile_image"])
                                            ? $post["author_profile_image"]
                                            : "images/default-profile.jpg",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                            alt="<?= htmlspecialchars($post["author_username"], ENT_QUOTES, "UTF-8") ?>'s profile picture"
                            class="post-avatar">
                    </a>
                    <div>
                        <strong>
                            <a
                                href="profile.php?id=<?= (int) $post["user_id"] ?>&return=<?= urlencode($returnUrl) ?>"
                                class="post-author-link">
                                <?= htmlspecialchars($post["author_username"], ENT_QUOTES, "UTF-8") ?>
                            </a>
                        </strong>
                        <p><?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?></p>
                    </div>
                    <?php if ((int) $post["user_id"] !== $user_id): ?>
                        <form action="follow_user.php" method="POST" class="feed-follow-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $post["user_id"] ?>">
                            <button
                                type="submit"
                                class="feed-follow-button <?= $post["user_follows"] ? "active" : "" ?>">
                                <?= $post["user_follows"] ? "Following" : "Follow" ?>
                            </button>
                        </form>
                    <?php endif; ?>
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
                                    <a href="post.php?id=<?= $postId ?>" class="post-media-link">
                                        <img
                                            class="post-media-image"
                                            src="<?= $mediaPath ?>"
                                            alt="<?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                            loading="lazy">
                                    </a>
                                <?php elseif ($media["media_type"] === "video"): ?>
                                    <video
                                        class="post-media-video"
                                        controls
                                        muted
                                        playsinline
                                        preload="metadata">
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
                        <a href="post.php?id=<?= $postId ?>" class="post-title-link">
                            <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>
                        </a>
                    </strong>
                    <?php if (!empty($post["description"])): ?>
                        <p><?= htmlspecialchars($post["description"], ENT_QUOTES, "UTF-8") ?></p>
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
                    <form action="like_post.php" method="POST" class="like-form">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= $postId ?>">

                        <button
                            type="submit"
                            class="post-action-button like-button <?= $post["user_liked"] ? "active" : "" ?>"
                            aria-label="<?= $post["user_liked"] ? "Unlike post" : "Like post" ?>">
                            <span class="like-heart"><?= $post["user_liked"] ? "♥" : "♡" ?></span>
                            <span class="like-count"><?= (int) $post["like_count"] ?></span>
                        </button>
                    </form>
                    <!-- COMMENTS -->
                    <button
                        type="button"
                        class="post-action-button comments-toggle"
                        aria-label="View comments">
                        <svg
                            class="comment-icon"
                            viewBox="0 0 24 24"
                            width="20"
                            height="20"
                            aria-hidden="true">
                            <path d="M20 11.5a8.5 8.5 0 0 1-8.5 8.5 8.4 8.4 0 0 1-4-.99L3 20l.99-4.5A8.4 8.4 0 0 1 3 11.5 8.5 8.5 0 1 1 20 11.5Z" />
                        </svg>
                        <span class="comment-count"><?= (int) $post["comment_count"] ?></span>
                    </button>
                    <!-- SAVE -->
                    <form action="save_post.php" method="POST" class="save-form">
                        <input type="hidden" name="post_id" value="<?= $postId ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <button
                            type="submit"
                            class="post-action-button save-button <?= $post["user_saved"] ? "active" : "" ?>"
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
                    <!-- DELETE POST -->
                    <?php if ((int) $post["user_id"] === $user_id): ?>
                        <form
                            action="delete_post.php"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this post?');">
                            <input type="hidden" name="post_id" value="<?= $postId ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                            <button
                                type="submit"
                                class="post-action-button delete-post-button"
                                aria-label="Delete post">
                                <svg
                                    class="delete-icon"
                                    viewBox="0 0 24 24"
                                    width="18"
                                    height="18"
                                    aria-hidden="true">
                                    <path d="M9 3h6l1 2h4v2H4V5h4l1-2Zm-1 6h2v8H8v-8Zm6 0h2v8h-2v-8ZM6 8h12l-1 11a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 8Z" />
                                </svg>
                                <span>Delete</span>
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ((int) $post["user_id"] !== $user_id): ?>
                        <button
                            type="button"
                            class="post-action-button report-button"
                            data-report-type="post"
                            data-target-id="<?= $postId ?>">
                            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                                <path d="M5 21V4" />
                                <path d="M5 4c4-3 7 3 11 0l3-1v11l-3 1c-4 3-7-3-11 0" />
                            </svg>
                            <span>Report</span>
                        </button>
                    <?php endif; ?>
                </div>
                <!-- COMMENTS AREA -->
                <div class="comments-area" hidden>
                    <div class="post-comments">
                        <?php
                        $postComments = $commentsByPost[$postId] ?? [];
                        ?>

                        <?php foreach ($postComments as $comment): ?>
                            <?php
                            $commentId = (int) $comment["comment_id"];
                            $replyCount = (int) $comment["reply_count"];
                            $likeCount = (int) $comment["like_count"];
                            $userLiked = (bool) $comment["user_liked"];
                            ?>

                            <div
                                class="comment"
                                data-comment-id="<?= $commentId ?>"
                                data-post-id="<?= $postId ?>">

                                <strong>
                                    <a
                                        href="profile.php?id=<?= (int) $comment["user_id"] ?>&return=<?= urlencode($returnUrl) ?>"
                                        class="comment-author-link">
                                        <?= htmlspecialchars($comment["username"], ENT_QUOTES, "UTF-8") ?>
                                    </a>
                                </strong>

                                <div class="feed-comment-content">
                                    <?php if ($comment["comment"] !== ""): ?>
                                        <p><?= htmlspecialchars($comment["comment"], ENT_QUOTES, "UTF-8") ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($comment["media_file"])): ?>
                                        <a
                                            class="comment-media-link"
                                            href="uploads/comments/<?= rawurlencode(basename($comment["media_file"])) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="Open comment image">
                                            <img
                                                class="comment-media"
                                                src="uploads/comments/<?= rawurlencode(basename($comment["media_file"])) ?>"
                                                alt="Comment attachment"
                                                loading="lazy">
                                        </a>
                                    <?php endif; ?>

                                    <?php if ((int) $comment["user_id"] === $user_id): ?>
                                        <form action="comment_delete.php" method="POST" class="delete-comment-form">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                            <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                                            <input type="hidden" name="post_id" value="<?= $postId ?>">
                                            <input type="hidden" name="return_to" value="feed">
                                            <button type="submit" class="delete-comment-button">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="report-button"
                                            data-report-type="comment"
                                            data-target-id="<?= $commentId ?>">
                                            Report
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <small><?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?></small>

                                <div class="comment-actions">
                                    <form action="comment_like.php" method="POST" class="comment-like-form">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                        <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                                        <input type="hidden" name="post_id" value="<?= $postId ?>">
                                        <input type="hidden" name="return_to" value="feed">

                                        <button
                                            type="submit"
                                            class="comment-like-button <?= $userLiked ? "active" : "" ?>"
                                            aria-label="<?= $userLiked ? "Unlike comment" : "Like comment" ?>">
                                            <span class="comment-like-heart"><?= $userLiked ? "♥" : "♡" ?></span>
                                            <span class="comment-like-count"><?= $likeCount ?></span>
                                        </button>
                                    </form>

                                    <button type="button" class="reply-toggle-button">
                                        Reply
                                    </button>
                                </div>

                                <form action="comment_post.php" method="POST" class="comment-reply-form" hidden>
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <input type="hidden" name="parent_comment_id" value="<?= $commentId ?>">
                                    <input type="hidden" name="return_to" value="feed">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                                    <input type="text" name="comment" maxlength="500" placeholder="Write a reply..." required>
                                    <button type="submit">Reply</button>
                                </form>

                                <div
                                    class="comment-replies"
                                    data-loaded="0"
                                    data-offset="0"
                                    hidden>
                                </div>

                                <?php if ($replyCount > 0): ?>
                                    <button
                                        type="button"
                                        class="view-replies-button"
                                        data-reply-count="<?= $replyCount ?>">
                                        View <?= $replyCount ?> <?= $replyCount === 1 ? "reply" : "replies" ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ((int) $post["comment_count"] > count($postComments)): ?>
                        <button
                            type="button"
                            class="load-more-comments-button"
                            data-post-id="<?= $postId ?>"
                            data-offset="<?= count($postComments) ?>">
                            Show more comments
                        </button>
                    <?php endif; ?>

                    <!-- ADD COMMENT -->
                    <form action="comment_post.php" method="POST" enctype="multipart/form-data" class="comment-form">
                        <input type="hidden" name="post_id" value="<?= $postId ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>">
                        <input type="text" name="comment" placeholder="Write a comment..." maxlength="500" autocomplete="off">
                        <label class="comment-upload-button" title="Add image or GIF">
                            📷
                            <input type="file" name="comment_media" accept="image/jpeg,image/png,image/webp,image/gif" class="comment-media-input" hidden>
                        </label>
                        <div class="comment-media-preview" hidden>
                            <img class="comment-preview-image" alt="Selected attachment">
                            <button type="button" class="comment-preview-remove" aria-label="Remove selected image">×</button>
                        </div>
                        <button type="submit">Post</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="feed-pagination" aria-label="Feed pages">

                <?php if ($page > 1): ?>
                    <a
                        href="<?= htmlspecialchars(
                                    "feed.php?" . http_build_query([
                                        "feed" => $feedMode,
                                        "page" => $page - 1
                                    ]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                        class="pagination-arrow"
                        aria-label="Previous page">
                        &laquo;
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                ?>

                <?php if ($startPage > 1): ?>
                    <a href="<?= htmlspecialchars(
                                    "feed.php?" . http_build_query([
                                        "feed" => $feedMode,
                                        "page" => 1
                                    ]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>">
                        1
                    </a>

                    <?php if ($startPage > 2): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a
                        href="<?= htmlspecialchars(
                                    "feed.php?" . http_build_query([
                                        "feed" => $feedMode,
                                        "page" => $i
                                    ]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                        class="<?= $i === $page ? "active" : "" ?>"
                        <?= $i === $page ? 'aria-current="page"' : "" ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>

                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>

                    <a href="<?= htmlspecialchars(
                                    "feed.php?" . http_build_query([
                                        "feed" => $feedMode,
                                        "page" => $totalPages
                                    ]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>">
                        <?= $totalPages ?>
                    </a>

                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <a
                        href="<?= htmlspecialchars(
                                    "feed.php?" . http_build_query([
                                        "feed" => $feedMode,
                                        "page" => $page + 1
                                    ]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                        class="pagination-arrow"
                        aria-label="Next page">
                        &raquo;
                    </a>
                <?php endif; ?>

            </nav>
        <?php endif; ?>


    </section>
</section>
<!-- REPORT MODAL -->
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

        <h2 id="report-modal-title">Report comment</h2>

        <p>Why are you reporting this content?</p>

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

            <label for="report-reason">Reason</label>

            <select name="reason" id="report-reason" required>
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
                <button type="button" class="report-cancel-button">
                    Cancel
                </button>

                <button type="submit" class="report-submit-button">
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
<script src="assets/js/feed.js" defer></script>
<?php
require_once __DIR__ . "/includes/user_footer.php";
?>