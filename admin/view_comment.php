<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/models/AdminComment.php";

$pageTitle = "Review Comment | Witchy";
$pageCss = "admin_content.css";

$adminComment = new AdminComment($conn);

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

// Status messages.
if (($_GET["status"] ?? "") === "deleted") {
    $statusMessage = "The comment was successfully deleted.";
} elseif (($_GET["status"] ?? "") === "error") {
    $statusMessage = "The comment could not be deleted. Please try again.";
}

// Load comment and related information.
if (!$commentId || $commentId < 1) {
    $errorMessage = "Invalid comment selected.";
} else {
    try {
        $comment = $adminComment->getCommentById((int) $commentId);

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

                <?php require __DIR__ . "/views/comment/details.php"; ?>

                <?php require __DIR__ . "/views/comment/related_post.php"; ?>

            </div>

            <?php require __DIR__ . "/views/comment/moderation.php"; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>