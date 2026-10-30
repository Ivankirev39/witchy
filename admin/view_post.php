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
$errorMessage = "";
$statusMessage = "";

// Remember where the review page was opened from.
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

if (($_GET["status"] ?? "") === "error") {
    $statusMessage = "The post could not be deleted. Please try again.";
} elseif (($_GET["comment_status"] ?? "") === "deleted") {
    $statusMessage = "The comment was successfully deleted.";
} elseif (($_GET["comment_status"] ?? "") === "error") {
    $statusMessage = "The comment could not be deleted. Please try again.";
}

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
            $mediaStmt = $conn->prepare("
                SELECT media_id, file, media_type, sort_order
                FROM post_media
                WHERE post_id = :post_id
                ORDER BY sort_order ASC, media_id ASC
            ");

            $mediaStmt->execute(["post_id" => $postId]);
            $media = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

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
        }

    } catch (PDOException $e) {
        error_log("Admin post review error: " . $e->getMessage());
        $errorMessage = "The post could not be loaded. Please try again.";
    }
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page">

    <header class="admin-content-heading">
        <h2>Post review</h2>
        <p>Review the post, its author and its discussion.</p>
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

                <!-- Post details -->
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
                        <?= htmlspecialchars($post["created_at"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <?php if ($post["is_sticky"]): ?>
                            Pinned
                        <?php elseif ($post["is_hot"]): ?>
                            Hot
                        <?php else: ?>
                            Normal
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

                                        <button
                                            type="submit"
                                            class="admin-delete-button"
                                        >
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

                    <h3>Moderation</h3>

                    <div class="admin-moderation-author">
                        <span class="admin-moderation-label">Post author</span>

                        <strong>
                            <?= htmlspecialchars($post["username"] ?? "Unknown", ENT_QUOTES, "UTF-8") ?>
                        </strong>

                        <span>
                            User ID: <?= (int) $post["user_id"] ?>
                        </span>
                    </div>

                    <div class="admin-moderation-details">

                        <p>
                            <strong>Post ID:</strong>
                            <?= (int) $post["post_id"] ?>
                        </p>

                        <p>
                            <strong>Comments:</strong>
                            <?= count($comments) ?>
                        </p>

                        <p>
                            <strong>Additional media:</strong>
                            <?= count($media) ?>
                        </p>

                    </div>

                    <div class="admin-moderation-danger">

                        <h4>Delete post</h4>

                        <p>
                            Permanently removes this post, its comments,
                            likes, saves and related notifications.
                            Uploaded files are queued for cleanup.
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
