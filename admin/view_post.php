
<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

$pageTitle = "Review Post | Witchy";
$pageCss = "admin_content.css";

$postId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$post = null;
$media = [];
$comments = [];
$engagement = [
    "likes" => 0,
    "comments" => 0,
    "saves" => 0,
    "recent_likes" => 0,
    "recent_comments" => 0,
    "popularity_score" => 0
];

$errorMessage = "";
$statusMessage = "";

// Return navigation.
$returnFrom = $_GET["from"] ?? "";
$returnUserId = filter_input(INPUT_GET, "return_user_id", FILTER_VALIDATE_INT);

$returnUrl = "/witchy/admin/content.php?tab=posts";

if ($returnFrom === "user" && $returnUserId && $returnUserId > 0) {
    $returnUserId = (int) $returnUserId;
    $returnUrl = "/witchy/admin/view_user.php?id=" . $returnUserId;
} else {
    $returnFrom = "";
    $returnUserId = null;
}

// Status messages.
if (($_GET["status"] ?? "") === "pinned") {
    $statusMessage = "The post was pinned to the community feed.";
} elseif (($_GET["status"] ?? "") === "unpinned") {
    $statusMessage = "The post was unpinned from the community feed.";
} elseif (($_GET["status"] ?? "") === "pin_error") {
    $statusMessage = "The post pin status could not be changed. Please try again.";
} elseif (($_GET["status"] ?? "") === "error") {
    $statusMessage = "The post could not be deleted. Please try again.";
} elseif (($_GET["comment_status"] ?? "") === "deleted") {
    $statusMessage = "The comment was successfully deleted.";
} elseif (($_GET["comment_status"] ?? "") === "error") {
    $statusMessage = "The comment could not be deleted. Please try again.";
}

// Pin/unpin action.
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["action"] ?? "") === "toggle_sticky"
) {
    if (!csrf_valid($_POST["csrf_token"] ?? "")) {
        $errorMessage = "Your security token was invalid. Please refresh and try again.";
    } elseif (!$postId || $postId < 1) {
        $errorMessage = "Invalid post selected.";
    } else {
        try {
            $checkStmt = $conn->prepare("
                SELECT is_sticky
                FROM post
                WHERE post_id = :post_id
                LIMIT 1
            ");

            $checkStmt->execute(["post_id" => $postId]);
            $currentSticky = $checkStmt->fetchColumn();

            if ($currentSticky === false) {
                $errorMessage = "This post could not be found.";
            } else {
                $newSticky = (int) $currentSticky === 1 ? 0 : 1;

                $updateStmt = $conn->prepare("
                    UPDATE post
                    SET is_sticky = :is_sticky
                    WHERE post_id = :post_id
                ");

                $updateStmt->execute([
                    "is_sticky" => $newSticky,
                    "post_id" => $postId
                ]);

                $redirectUrl = "/witchy/admin/view_post.php?id=" . (int) $postId;

                if ($returnFrom === "user" && $returnUserId) {
                    $redirectUrl .= "&from=user&return_user_id=" . (int) $returnUserId;
                }

                $redirectUrl .= $newSticky === 1
                    ? "&status=pinned"
                    : "&status=unpinned";

                header("Location: " . $redirectUrl);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Admin post pin error: " . $e->getMessage());
            $errorMessage = "The post pin status could not be changed. Please try again.";
        }
    }
}

// Load post and related information.
if (!$postId || $postId < 1) {
    $errorMessage = "Invalid post selected.";
} else {
    try {
        $stmt = $conn->prepare("
            SELECT
                p.post_id,
                p.user_id,
                p.title,
                p.description,
                p.image,
                p.topic,
                p.sources,
                p.is_hot,
                p.is_sticky,
                p.created_at,
                u.username
            FROM post p
            LEFT JOIN users u ON p.user_id = u.user_id
            WHERE p.post_id = :post_id
            LIMIT 1
        ");

        $stmt->execute(["post_id" => $postId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $errorMessage = "This post could not be found.";
        } else {
            // Post media.
            $mediaStmt = $conn->prepare("
                SELECT media_id, file, media_type, sort_order
                FROM post_media
                WHERE post_id = :post_id
                ORDER BY sort_order ASC, media_id ASC
            ");

            $mediaStmt->execute(["post_id" => $postId]);
            $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

            // Comments.
            $commentStmt = $conn->prepare("
                SELECT
                    c.comment_id,
                    c.comment,
                    c.created_at,
                    c.media_file,
                    c.media_type,
                    u.user_id,
                    u.username
                FROM comment c
                LEFT JOIN users u ON c.user_id = u.user_id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC
            ");

            $commentStmt->execute(["post_id" => $postId]);
            $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

            // Engagement statistics.
            $engagementStmt = $conn->prepare("
                SELECT
                    (
                        SELECT COUNT(*)
                        FROM post_like pl
                        WHERE pl.post_id = p.post_id
                    ) AS total_likes,

                    (
                        SELECT COUNT(*)
                        FROM comment c
                        WHERE c.post_id = p.post_id
                    ) AS total_comments,

                    (
                        SELECT COUNT(*)
                        FROM save s
                        WHERE s.post_id = p.post_id
                    ) AS total_saves,

                    (
                        SELECT COUNT(*)
                        FROM post_like pl
                        WHERE pl.post_id = p.post_id
                          AND pl.liked_at >= NOW() - INTERVAL 48 HOUR
                    ) AS recent_likes,

                    (
                        SELECT COUNT(*)
                        FROM comment c
                        WHERE c.post_id = p.post_id
                          AND c.created_at >= NOW() - INTERVAL 48 HOUR
                    ) AS recent_comments

                FROM post p
                WHERE p.post_id = :post_id
            ");

            $engagementStmt->execute(["post_id" => $postId]);
            $engagementData = $engagementStmt->fetch(PDO::FETCH_ASSOC);

            if ($engagementData) {
                $engagement["likes"] = (int) $engagementData["total_likes"];
                $engagement["comments"] = (int) $engagementData["total_comments"];
                $engagement["saves"] = (int) $engagementData["total_saves"];
                $engagement["recent_likes"] = (int) $engagementData["recent_likes"];
                $engagement["recent_comments"] = (int) $engagementData["recent_comments"];

                $engagement["popularity_score"] =
                    $engagement["likes"] + ($engagement["comments"] * 3);
            }
        }

    } catch (PDOException $e) {
        error_log("Admin post review error: " . $e->getMessage());
        $errorMessage = "The post could not be loaded. Please try again.";
    }
}

$isPinned = $post && (int) $post["is_sticky"] === 1;

$isTrending = (
    $engagement["recent_likes"] > 0
    || $engagement["recent_comments"] > 0
);

$createdDate = $post && !empty($post["created_at"])
    ? strtotime($post["created_at"])
    : false;

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page">

    <header class="admin-content-heading">
        <h2>Post review</h2>
        <p>Review the post, its performance, author and discussion.</p>
    </header>

    <p>
        <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, "UTF-8") ?>">
            &larr; Back
        </a>
    </p>

    <?php if ($statusMessage !== ""): ?>
        <div class="admin-content-error" role="status">
            <?= htmlspecialchars($statusMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>

        <div class="admin-content-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php elseif ($post): ?>

        <div class="admin-review-layout">

            <div class="admin-review-main">

                <!-- Post information -->
                <section class="admin-content-panel">

                    <header class="admin-content-panel-header">
                        <h3>
                            <?= htmlspecialchars($post["title"] ?? "Untitled", ENT_QUOTES, "UTF-8") ?>
                        </h3>

                        <p>
                            Post #<?= (int) $post["post_id"] ?>
                            · By
                            <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </header>

                    <p>
                        <strong>Topic:</strong>
                        <?= htmlspecialchars($post["topic"] ?? "Uncategorized", ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Published:</strong>

                        <?php if ($createdDate !== false): ?>
                            <time datetime="<?= date("c", $createdDate) ?>">
                                <?= date("M j, Y · g:i a", $createdDate) ?>
                            </time>
                        <?php else: ?>
                            Unknown
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Author:</strong>

                        <?php if (!empty($post["user_id"])): ?>
                            <a href="/witchy/admin/view_user.php?id=<?= (int) $post["user_id"] ?>">
                                <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                            </a>
                        <?php else: ?>
                            Unknown member
                        <?php endif; ?>
                    </p>

                    <div class="admin-post-description">
                        <?= nl2br(htmlspecialchars($post["description"] ?? "", ENT_QUOTES, "UTF-8")) ?>
                    </div>

                    <?php if (!empty($post["image"])): ?>
                        <div class="admin-post-media">
                            <h4>Main image</h4>

                            <img
                                src="/witchy/uploads/posts/<?= rawurlencode(basename($post["image"])) ?>"
                                alt="Post image"
                                loading="lazy"
                            >
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($media)): ?>
                        <div class="admin-post-media">
                            <h4>Additional media</h4>

                            <?php foreach ($media as $item): ?>
                                <?php
                                $filename = basename($item["file"] ?? "");
                                $mediaType = strtolower($item["media_type"] ?? "");
                                $mediaPath = "/witchy/uploads/posts/" . rawurlencode($filename);
                                ?>

                                <?php if ($filename !== "" && $mediaType === "image"): ?>
                                    <img
                                        src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                        alt="Additional post image"
                                        loading="lazy"
                                    >

                                <?php elseif ($filename !== "" && $mediaType === "video"): ?>
                                    <video controls preload="metadata">
                                        <source src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>">
                                        Your browser does not support this video.
                                    </video>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post["sources"])): ?>
                        <div>
                            <h4>Sources</h4>

                            <p>
                                <?= nl2br(htmlspecialchars($post["sources"], ENT_QUOTES, "UTF-8")) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                </section>


                <!-- Engagement overview -->
                <section class="admin-content-panel">

                    <header class="admin-content-panel-header">
                        <h3>Post engagement</h3>
                        <p>Engagement statistics for this post.</p>
                    </header>

                    <div class="admin-post-engagement-grid">

                        <article class="admin-post-engagement-card">
                            <span>♡</span>
                            <strong><?= number_format($engagement["likes"]) ?></strong>
                            <small>Total likes</small>
                        </article>

                        <article class="admin-post-engagement-card">
                            <span>☽</span>
                            <strong><?= number_format($engagement["comments"]) ?></strong>
                            <small>Total comments</small>
                        </article>

                        <article class="admin-post-engagement-card">
                            <span>♧</span>
                            <strong><?= number_format($engagement["saves"]) ?></strong>
                            <small>Total saves</small>
                        </article>

                        <article class="admin-post-engagement-card">
                            <span>✧</span>
                            <strong><?= number_format($engagement["popularity_score"]) ?></strong>
                            <small>Popularity score</small>
                        </article>

                    </div>

                    <h4>Recent engagement · Last 48 hours</h4>

                    <div class="admin-post-recent-engagement">

                        <div>
                            <strong><?= number_format($engagement["recent_likes"]) ?></strong>
                            <span>Recent likes</span>
                        </div>

                        <div>
                            <strong><?= number_format($engagement["recent_comments"]) ?></strong>
                            <span>Recent comments</span>
                        </div>

                    </div>

                    <p class="admin-post-score-note">
                        Popularity score = total likes + (total comments × 3).
                        Trending eligibility requires at least one like or comment
                        in the last 48 hours.
                    </p>

                </section>


                <!-- Comment moderation -->
                <section class="admin-content-panel">

                    <header class="admin-content-panel-header">
                        <h3>Comments (<?= count($comments) ?>)</h3>
                        <p>Review and moderate comments left on this post.</p>
                    </header>

                    <?php if (empty($comments)): ?>

                        <div class="admin-content-empty">
                            This post has no comments.
                        </div>

                    <?php else: ?>

                        <?php foreach ($comments as $comment): ?>

                            <article class="admin-post-comment">

                                <p>
                                    <strong>
                                        <?= htmlspecialchars($comment["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                                    </strong>

                                    ·

                                    <?= htmlspecialchars($comment["created_at"] ?? "", ENT_QUOTES, "UTF-8") ?>
                                </p>

                                <p>
                                    <?= nl2br(htmlspecialchars($comment["comment"] ?? "", ENT_QUOTES, "UTF-8")) ?>
                                </p>

                                <?php if (!empty($comment["media_file"])): ?>
                                    <p>
                                        <strong>Attached media:</strong>

                                        <?= htmlspecialchars(
                                            basename($comment["media_file"]),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>

                                        (<?= htmlspecialchars(
                                            $comment["media_type"] ?? "unknown",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>)
                                    </p>
                                <?php endif; ?>

                                <div class="admin-comment-actions">

                                    <form
                                        method="POST"
                                        action="/witchy/admin/delete_comment.php"
                                        onsubmit="return confirm('Are you sure you want to permanently delete this comment?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="comment_id"
                                            value="<?= (int) $comment["comment_id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return_post_id"
                                            value="<?= (int) $post["post_id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return_from"
                                            value="<?= htmlspecialchars($returnFrom, ENT_QUOTES, "UTF-8") ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return_user_id"
                                            value="<?= $returnUserId !== null ? (int) $returnUserId : "" ?>"
                                        >

                                        <button type="submit" class="admin-delete-button">
                                            Delete comment
                                        </button>

                                    </form>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </section>

            </div>


            <!-- Moderation sidebar -->
            <aside class="admin-review-sidebar">

                <section class="admin-content-panel admin-moderation-panel">

                    <h3>Moderation status</h3>
                    
                    <div class="admin-moderation-author">
                        <span class="admin-moderation-label">Post author</span>

                        <strong>
                            <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                        </strong>

                        <span>
                            User ID: <?= (int) $post["user_id"] ?>
                        </span>

                        <?php if (!empty($post["user_id"])): ?>
                            <a href="/witchy/admin/view_user.php?id=<?= (int) $post["user_id"] ?>">
                                Review member
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="admin-post-status-list">

                        <div>
                            <span>Community pin</span>

                            <strong>
                                <?= $isPinned ? "Pinned" : "Not pinned" ?>
                            </strong>
                        </div>

                        <div>
                            <span>Trending</span>

                            <strong>
                                <?= $isTrending ? "Trending" : "Not trending" ?>
                            </strong>
                        </div>

                        <div>
                            <span>Recent likes</span>
                            <strong><?= number_format($engagement["recent_likes"]) ?></strong>
                        </div>

                        <div>
                            <span>Recent comments</span>
                            <strong><?= number_format($engagement["recent_comments"]) ?></strong>
                        </div>

                    </div>

                    

                    <div class="admin-moderation-pin">
                        <h4>Community feed</h4>

                        <p>
                            <?= $isPinned
                                ? "This post is currently pinned at the top of the community feed."
                                : "Pin this post to give it priority in the community feed." ?>
                        </p>

                        <form
                            method="POST"
                            action="/witchy/admin/view_post.php?id=<?= (int) $post["post_id"] ?><?= $returnFrom === "user" && $returnUserId ? "&amp;from=user&amp;return_user_id=" . (int) $returnUserId : "" ?>"
                            onsubmit="return confirm('Are you sure you want to <?= $isPinned ? "unpin" : "pin" ?> this post?');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input type="hidden" name="action" value="toggle_sticky">

                            <button type="submit">
                                <?= $isPinned ? "Unpin post" : "Pin to community feed" ?>
                            </button>

                        </form>

                    </div>

                    <div class="admin-moderation-danger">

                        <h4>Delete post</h4>

                        <p>
                            Permanently removes this post and its associated
                            content. Uploaded files are queued for cleanup.
                        </p>

                        <form
                            method="POST"
                            action="/witchy/admin/delete_post.php"
                            onsubmit="return confirm('Are you sure you want to permanently delete this post and its comments? This cannot be undone.');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input
                                type="hidden"
                                name="post_id"
                                value="<?= (int) $post["post_id"] ?>"
                            >

                            <input
                                type="hidden"
                                name="return_from"
                                value="<?= htmlspecialchars($returnFrom, ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input
                                type="hidden"
                                name="return_user_id"
                                value="<?= $returnUserId !== null ? (int) $returnUserId : "" ?>"
                            >

                            <button type="submit" class="admin-delete-button">
                                Delete post
                            </button>

                        </form>

                    </div>

                </section>

            </aside>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
