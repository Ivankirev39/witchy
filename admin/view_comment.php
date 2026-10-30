<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

$pageTitle = "Review Comment | Witchy";
$pageCss = "admin_content.css";

$commentId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$comment = null;
$errorMessage = "";
$statusMessage = "";

// Remember where this page was opened from.
$returnFrom = $_GET["from"] ?? "";
$returnUserId = filter_input(INPUT_GET, "return_user_id", FILTER_VALIDATE_INT);

$returnUrl = "/witchy/admin/content.php?tab=comments";

if ($returnFrom === "user" && $returnUserId && $returnUserId > 0) {
    $returnUserId = (int) $returnUserId;
    $returnUrl = "/witchy/admin/view_user.php?id=" . $returnUserId;
} else {
    $returnFrom = "";
    $returnUserId = null;
}

if (($_GET["status"] ?? "") === "deleted") {
    $statusMessage = "The comment was successfully deleted.";
} elseif (($_GET["status"] ?? "") === "error") {
    $statusMessage = "The comment could not be deleted. Please try again.";
}

if (!$commentId || $commentId < 1) {
    $errorMessage = "Invalid comment selected.";
} else {
    try {
        $stmt = $conn->prepare("
            SELECT
                c.comment_id,
                c.comment,
                c.created_at,
                c.media_file,
                c.media_type,
                c.post_id,
                u.user_id,
                u.username,
                p.title AS post_title,
                p.created_at AS post_created_at
            FROM comment c
            LEFT JOIN users u ON c.user_id = u.user_id
            LEFT JOIN post p ON c.post_id = p.post_id
            WHERE c.comment_id = :comment_id
            LIMIT 1
        ");

        $stmt->execute([
            ":comment_id" => $commentId
        ]);

        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            $errorMessage = "This comment could not be found.";
        }

    } catch (PDOException $e) {
        error_log("Admin comment review error: " . $e->getMessage());
        $errorMessage = "The comment could not be loaded. Please try again.";
    }
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page">

    <header class="admin-content-heading">
        <h2>Comment review</h2>
        <p>Review the comment, its author and the related discussion.</p>
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

    <?php elseif ($comment): ?>

        <div class="admin-review-layout">

            <div class="admin-review-main">

                <!-- Main comment review -->
                <section class="admin-content-panel">

                    <header class="admin-content-panel-header">
                        <h3>Comment #<?= (int) $comment["comment_id"] ?></h3>

                        <p>
                            By
                            <?= htmlspecialchars(
                                $comment["username"] ?? "Unknown",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>
                    </header>

                    <p>
                        <strong>Created:</strong>
                        <?= htmlspecialchars(
                            $comment["created_at"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </p>

                    <div class="admin-post-description">
                        <?= nl2br(htmlspecialchars(
                            $comment["comment"] ?? "",
                            ENT_QUOTES,
                            "UTF-8"
                        )) ?>
                    </div>

                    <!-- Comment attachment -->
                    <?php if (!empty($comment["media_file"])): ?>

                        <?php
                        $filename = basename($comment["media_file"]);
                        $mediaType = strtolower($comment["media_type"] ?? "");
                        $mediaPath = "/witchy/uploads/comments/" . rawurlencode($filename);
                        ?>

                        <div class="admin-post-media">

                            <h4>Attached media</h4>

                            <?php if ($mediaType === "image"): ?>

                                <img
                                    src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>"
                                    alt="Comment attachment"
                                    loading="lazy"
                                >

                            <?php elseif ($mediaType === "video"): ?>

                                <video controls preload="metadata">
                                    <source src="<?= htmlspecialchars($mediaPath, ENT_QUOTES, "UTF-8") ?>">
                                    Your browser does not support this video.
                                </video>

                            <?php else: ?>

                                <p>
                                    Attachment:
                                    <?= htmlspecialchars($filename, ENT_QUOTES, "UTF-8") ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </section>

                <!-- Related post -->
                <section class="admin-content-panel">

                    <header class="admin-content-panel-header">
                        <h3>Related post</h3>
                        <p>The post this comment belongs to.</p>
                    </header>

                    <?php if (!empty($comment["post_id"])): ?>

                        <p>
                            <strong>Post title:</strong>
                            <?= htmlspecialchars(
                                $comment["post_title"] ?? "Untitled",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </p>

                        <p>
                            <strong>Post ID:</strong>
                            <?= (int) $comment["post_id"] ?>
                        </p>

                        <?php
                        $relatedPostUrl = "/witchy/admin/view_post.php?id="
                            . (int) $comment["post_id"];

                        if ($returnFrom === "user" && $returnUserId !== null) {
                            $relatedPostUrl .=
                                "&from=user&return_user_id=" . $returnUserId;
                        }
                        ?>

                        <p>
                            <a href="<?= htmlspecialchars($relatedPostUrl, ENT_QUOTES, "UTF-8") ?>">
                                Review related post &rarr;
                            </a>
                        </p>

                    <?php else: ?>

                        <div class="admin-content-empty">
                            The original post is no longer available.
                        </div>

                    <?php endif; ?>

                </section>

            </div>

            <!-- Moderation sidebar -->
            <aside class="admin-review-sidebar">

                <section class="admin-content-panel admin-moderation-panel">

                    <h3>Moderation</h3>

                    <div class="admin-moderation-author">

                        <span class="admin-moderation-label">
                            Comment author
                        </span>

                        <strong>
                            <?= htmlspecialchars(
                                $comment["username"] ?? "Unknown",
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </strong>

                        <span>
                            User ID: <?= (int) ($comment["user_id"] ?? 0) ?>
                        </span>

                    </div>

                    <div class="admin-moderation-details">

                        <p>
                            <strong>Comment ID:</strong>
                            <?= (int) $comment["comment_id"] ?>
                        </p>

                        <p>
                            <strong>Post ID:</strong>
                            <?= (int) ($comment["post_id"] ?? 0) ?>
                        </p>

                    </div>

                    <div class="admin-moderation-danger">

                        <h4>Delete comment</h4>

                        <p>
                            Permanently removes this comment.
                            Any uploaded attachment will be queued for cleanup.
                        </p>

                        <form
                            method="POST"
                            action="/witchy/admin/delete_comment.php"
                            onsubmit="return confirm('Are you sure you want to permanently delete this comment? This cannot be undone.');"
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

                </section>

            </aside>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
