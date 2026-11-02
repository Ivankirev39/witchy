<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/models/AdminPost.php";

$pageTitle = "Review Post | Witchy";
$pageCss = "admin_content.css";

$adminPost = new AdminPost($conn);

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

$returnUrl = BASE_URL . "admin/content.php?tab=posts";

if ($returnFrom === "user" && $returnUserId && $returnUserId > 0) {
    $returnUserId = (int) $returnUserId;
    $returnUrl = BASE_URL . "admin/view_user.php?id=" . $returnUserId;
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

                $redirectUrl = BASE_URL . "admin/view_post.php?id=" . (int) $postId;

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
        $post = $adminPost->getPostById((int) $postId);

        if (!$post) {
            $errorMessage = "This post could not be found.";
        } else {
            $media = $adminPost->getPostMedia((int) $postId);
            $comments = $adminPost->getPostComments((int) $postId);
            $engagement = $adminPost->getEngagement((int) $postId);
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

                <?php require __DIR__ . "/views/post/details.php"; ?>

                <?php require __DIR__ . "/views/post/engagement.php"; ?>

                <?php require __DIR__ . "/views/post/comments.php"; ?>

            </div>

            <?php require __DIR__ . "/views/post/moderation.php"; ?>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
