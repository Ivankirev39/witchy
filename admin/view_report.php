<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/models/AdminReport.php";
require_once __DIR__ . "/models/AdminPost.php";

$pageTitle = "Report Details | Witchy";
$pageCss = "admin_reports.css";

$reportId = filter_var(
    $_GET["id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$reportId || $reportId < 1) {
    http_response_code(400);
    exit("Invalid report ID.");
}

$reportModel = new AdminReport($conn);
$adminPost = new AdminPost($conn);

$errorMessage = "";
$report = false;
$originalPost = null;
$postMedia = [];

try {
    $report = $reportModel->getReportById($reportId);

    if (!$report) {
        http_response_code(404);
    } else {
        $postId = 0;

        if (!empty($report["reported_post_id"])) {
            $postId = (int) $report["reported_post_id"];
        } elseif (!empty($report["comment_post_id"])) {
            $postId = (int) $report["comment_post_id"];
        }

        if ($postId > 0) {
            $originalPost = $adminPost->getPostById($postId);

            if ($originalPost) {
                $postMedia = $adminPost->getPostMedia($postId);
            }
        }
    }
} catch (Throwable $e) {
    error_log("Admin report details error: " . $e->getMessage());
    $errorMessage = "Something went wrong while loading this report.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-report-details">

    <div class="report-details-heading">
        <div>
            <a href="<?= BASE_URL ?>admin/reports.php" class="report-back-link">
                &larr; Back to reports
            </a>

            <h2>Report details</h2>
            <p>Review the information submitted by a Witchy user.</p>
        </div>
    </div>

    <?php if ($errorMessage): ?>

        <p class="reports-error">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </p>

    <?php elseif (!$report): ?>

        <div class="report-details-empty">
            <h3>Report not found</h3>
            <p>This report may no longer exist or the requested ID is invalid.</p>

            <a href="<?= BASE_URL ?>admin/reports.php">
                Return to reports
            </a>
        </div>

    <?php else: ?>

        <?php
        $isUserReport = !empty($report["reported_user_id"]);
        $isCommentReport = !empty($report["comment_id"]);
        $isPostReport = !empty($report["reported_post_id"]);

        $commentRemoved = !empty($report["comment_deleted_at"]);

        $status = $report["status"];

        $statusLabels = [
            "pending" => "Pending",
            "reviewed" => "Reviewed",
            "dismissed" => "Dismissed"
        ];

        $statusLabel = $statusLabels[$status] ?? "Unknown";

        $reportDate = date(
            "d M Y, H:i",
            strtotime($report["created_at"])
        );

        $actionSuccess = $_GET["action_success"] ?? "";
        $actionError = $_GET["action_error"] ?? "";

        $successMessages = [
            "review" => "The report has been marked as reviewed.",
            "dismiss" => "The report has been dismissed.",
            "comment_removed" => "The reported comment has been removed.",
            "post_removed" => "The reported post has been deleted."
        ];

        $errorMessages = [
            "already_processed" => "This report has already been processed or no longer exists.",
            "server" => "Something went wrong while processing this action."
        ];

        $returnTo = rawurlencode(
            "view_report.php?id=" . (int) $report["report_id"]
        );

        $postRemoved = $isPostReport && !$originalPost;

        $commentFilename = basename($report["comment_media_file"] ?? "");
        $commentMediaType = $report["comment_media_type"] ?? "";

        $allowedCommentTypes = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"
        ];
        ?>

        <div class="report-details-layout">

            <div class="report-details-main">

                <?php if (isset($successMessages[$actionSuccess])): ?>
                    <div class="admin-content-success" role="status">
                        <?= htmlspecialchars(
                            $successMessages[$actionSuccess],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($errorMessages[$actionError])): ?>
                    <div class="admin-content-error" role="alert">
                        <?= htmlspecialchars(
                            $errorMessages[$actionError],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </div>
                <?php endif; ?>

                <div class="report-details-panel">

                    <div class="report-details-panel-header">
                        <div>
                            <h3>Report #<?= (int) $report["report_id"] ?></h3>

                            <p>
                                Submitted on
                                <?= htmlspecialchars($reportDate, ENT_QUOTES, "UTF-8") ?>
                            </p>
                        </div>

                        <span class="report-status status-<?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>">
                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="report-information">

                        <div>
                            <span class="report-info-label">Report type</span>

                            <strong>
                                <?= $isUserReport
                                    ? "Profile report"
                                    : ($isCommentReport
                                        ? "Comment report"
                                        : ($isPostReport ? "Post report" : "Unknown")) ?>
                            </strong>
                        </div>

                        <div>
                            <span class="report-info-label">Reason</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $report["reason"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                    </div>

                    <div class="report-details-section">
                        <h4>Additional information</h4>

                        <?php if (!empty($report["details"])): ?>
                            <p class="report-user-details">
                                <?= nl2br(htmlspecialchars(
                                    $report["details"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                )) ?>
                            </p>
                        <?php else: ?>
                            <p class="report-no-details">
                                No additional information was provided.
                            </p>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="report-details-panel">

                    <div class="report-details-panel-header">
                        <h3>Reported content</h3>
                    </div>

                    <?php if ($isUserReport): ?>

                        <div class="reported-target">
                            <span class="report-info-label">Reported profile</span>

                            <?php if (!empty($report["reported_username"])): ?>

                                <strong>
                                    @<?= htmlspecialchars(
                                        $report["reported_username"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>

                                <a href="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $report["reported_user_id"] ?>&return_to=<?= $returnTo ?>">
                                    View user profile
                                </a>

                            <?php else: ?>

                                <p class="report-no-details">
                                    The reported user is no longer available.
                                </p>

                            <?php endif; ?>
                        </div>

                    <?php elseif ($isCommentReport): ?>

                        <div class="reported-target">
                            <span class="report-info-label">Comment author</span>

                            <?php if (!empty($report["comment_author_username"])): ?>

                                <strong>
                                    @<?= htmlspecialchars(
                                        $report["comment_author_username"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>

                                <a href="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $report["comment_author_id"] ?>&return_to=<?= $returnTo ?>">
                                    View user profile
                                </a>

                            <?php else: ?>

                                <p class="report-no-details">
                                    The comment author is no longer available.
                                </p>

                            <?php endif; ?>
                        </div>

                        <div class="report-details-section">
                            <h4>Comment content</h4>

                            <?php if (
                                !empty($report["reported_comment"])
                                || $commentFilename !== ""
                            ): ?>

                                <div class="reported-comment-content">

                                    <div class="reported-comment-header">
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $report["comment_author_username"] ?? "Unknown member",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </strong>

                                            <span>Reported comment</span>
                                        </div>

                                        <?php if (!empty($report["comment_created_at"])): ?>
                                            <time>
                                                <?= htmlspecialchars(
                                                    date(
                                                        "M j, Y · g:i a",
                                                        strtotime($report["comment_created_at"])
                                                    ),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($report["reported_comment"])): ?>
                                        <div class="reported-comment-text">
                                            <?= nl2br(htmlspecialchars(
                                                $report["reported_comment"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (
                                        $commentFilename !== ""
                                        && in_array($commentMediaType, $allowedCommentTypes, true)
                                    ): ?>

                                        <div class="reported-comment-media">
                                            <img
                                                src="<?= htmlspecialchars(
                                                    BASE_URL . "uploads/comments/" . rawurlencode($commentFilename),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                                alt="Reported comment attachment"
                                                loading="lazy"
                                            >
                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <p class="report-no-details">
                                    The reported comment is no longer available.
                                </p>

                            <?php endif; ?>

                            <h4>Original post context</h4>

                            <?php if ($originalPost): ?>

                                <div class="reported-original-post">

                                    <div class="reported-original-post-heading">
                                        <div>
                                            <span>Original context</span>

                                            <h4>
                                                <?= htmlspecialchars(
                                                    $originalPost["title"] ?? "Untitled",
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </h4>
                                        </div>

                                        <a href="<?= BASE_URL ?>admin/view_post.php?id=<?= (int) $originalPost["post_id"] ?>">
                                            View full post
                                        </a>
                                    </div>

                                    <div class="reported-original-post-meta">
                                        <span>
                                            By
                                            <?= htmlspecialchars(
                                                $originalPost["username"] ?? "Unknown member",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                        <span>
                                            <?= htmlspecialchars(
                                                $originalPost["topic"] ?? "Uncategorized",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                        <?php if (!empty($originalPost["created_at"])): ?>
                                            <span>
                                                <?= htmlspecialchars(
                                                    date("M j, Y", strtotime($originalPost["created_at"])),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($originalPost["description"])): ?>
                                        <div class="reported-original-post-description">
                                            <?= nl2br(htmlspecialchars(
                                                $originalPost["description"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            )) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($originalPost["image"])): ?>
                                        <div class="reported-original-post-media">
                                            <img
                                                src="<?= htmlspecialchars(
                                                    BASE_URL . "uploads/posts/" . rawurlencode(basename($originalPost["image"])),
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ) ?>"
                                                alt="Original post image"
                                                loading="lazy"
                                            >
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($postMedia)): ?>
                                        <div class="reported-original-post-gallery">

                                            <?php foreach ($postMedia as $item): ?>

                                                <?php
                                                $filename = basename($item["file"] ?? "");
                                                $mediaType = strtolower($item["media_type"] ?? "");

                                                $mediaPath = BASE_URL
                                                    . "uploads/posts/"
                                                    . rawurlencode($filename);
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

                                </div>

                            <?php else: ?>

                                <p class="report-no-details">
                                    The original post is no longer available.
                                </p>

                            <?php endif; ?>

                        </div>

                    <?php elseif ($isPostReport): ?>

                        <?php if ($originalPost): ?>

                            <div class="reported-target">
                                <span class="report-info-label">Post author</span>

                                <strong>
                                    @<?= htmlspecialchars(
                                        $originalPost["username"] ?? "Unknown member",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </strong>

                                <a href="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $originalPost["user_id"] ?>&return_to=<?= $returnTo ?>">
                                    View post author
                                </a>
                            </div>

                            <div class="reported-original-post">

                                <div class="reported-original-post-heading">
                                    <div>
                                        <span>Reported post</span>

                                        <h4>
                                            <?= htmlspecialchars(
                                                $originalPost["title"] ?? "Untitled",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </h4>
                                    </div>

                                    <a href="<?= BASE_URL ?>admin/view_post.php?id=<?= (int) $originalPost["post_id"] ?>">
                                        View full post
                                    </a>
                                </div>

                                <div class="reported-original-post-meta">
                                    <span>
                                        By
                                        <?= htmlspecialchars(
                                            $originalPost["username"] ?? "Unknown member",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>

                                    <span>
                                        <?= htmlspecialchars(
                                            $originalPost["topic"] ?? "Uncategorized",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>

                                    <?php if (!empty($originalPost["created_at"])): ?>
                                        <span>
                                            <?= htmlspecialchars(
                                                date("M j, Y", strtotime($originalPost["created_at"])),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($originalPost["description"])): ?>
                                    <div class="reported-original-post-description">
                                        <?= nl2br(htmlspecialchars(
                                            $originalPost["description"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($originalPost["image"])): ?>
                                    <div class="reported-original-post-media">
                                        <img
                                            src="<?= htmlspecialchars(
                                                BASE_URL . "uploads/posts/" . rawurlencode(basename($originalPost["image"])),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            alt="Reported post image"
                                            loading="lazy"
                                        >
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($postMedia)): ?>
                                    <div class="reported-original-post-gallery">

                                        <?php foreach ($postMedia as $item): ?>

                                            <?php
                                            $filename = basename($item["file"] ?? "");
                                            $mediaType = strtolower($item["media_type"] ?? "");

                                            $mediaPath = BASE_URL
                                                . "uploads/posts/"
                                                . rawurlencode($filename);
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

                            </div>

                        <?php else: ?>

                            <p class="report-no-details">
                                This post has already been deleted or is no longer available.
                            </p>

                        <?php endif; ?>

                    <?php else: ?>

                        <p class="report-no-details">
                            This report does not have a valid reported target.
                        </p>

                    <?php endif; ?>

                </div>

            </div>

            <aside class="report-details-sidebar">

                <div class="report-details-panel">

                    <h3>Report information</h3>

                    <div class="report-sidebar-item">
                        <span class="report-info-label">Submitted by</span>

                        <?php if (!empty($report["reporter_username"])): ?>

                            <strong>
                                @<?= htmlspecialchars(
                                    $report["reporter_username"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                            <a href="<?= BASE_URL ?>admin/view_user.php?id=<?= (int) $report["reporter_id"] ?>&return_to=<?= $returnTo ?>">
                                View reporter
                            </a>

                        <?php else: ?>

                            <span>Unavailable</span>

                        <?php endif; ?>
                    </div>

                    <div class="report-sidebar-item">
                        <span class="report-info-label">Current status</span>

                        <span class="report-status status-<?= htmlspecialchars($status, ENT_QUOTES, "UTF-8") ?>">
                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="report-sidebar-item">
                        <span class="report-info-label">Reviewed at</span>

                        <strong>
                            <?= !empty($report["reviewed_at"])
                                ? htmlspecialchars(
                                    date("d M Y, H:i", strtotime($report["reviewed_at"])),
                                    ENT_QUOTES,
                                    "UTF-8"
                                )
                                : "Not reviewed yet" ?>
                        </strong>
                    </div>

                    <?php if (!empty($report["reviewer_username"])): ?>

                        <div class="report-sidebar-item">
                            <span class="report-info-label">Reviewed by</span>

                            <strong>
                                @<?= htmlspecialchars(
                                    $report["reviewer_username"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                    <?php endif; ?>

                </div>

                <div class="report-comment-moderation">

                    <h4>Report actions</h4>

                    <?php if ($status === "pending"): ?>

                        <p>
                            Once you have reviewed the reported content,
                            choose how this report should be handled.
                        </p>

                        <form
                            method="POST"
                            action="<?= BASE_URL ?>admin/report_action.php"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?= (int) $report["report_id"] ?>"
                            >

                            <button
                                type="submit"
                                name="action"
                                value="review"
                                class="admin-button"
                            >
                                Mark as reviewed
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="<?= BASE_URL ?>admin/report_action.php"
                            onsubmit="return confirm('Are you sure you want to dismiss this report?');"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?= (int) $report["report_id"] ?>"
                            >

                            <button
                                type="submit"
                                name="action"
                                value="dismiss"
                                class="admin-button-secondary"
                            >
                                Dismiss report
                            </button>
                        </form>

                    <?php else: ?>

                        <p class="report-no-details">
                            This report has already been processed.
                        </p>

                    <?php endif; ?>

                </div>

                <?php if ($isCommentReport): ?>

                    <div class="report-comment-moderation">

                        <h4>Comment moderation</h4>

                        <?php if ($commentRemoved): ?>

                            <p class="report-no-details">
                                This comment has already been removed.
                            </p>

                        <?php else: ?>

                            <p>
                                Remove this comment while preserving the report
                                and its original content.
                            </p>

                            <form
                                method="POST"
                                action="<?= BASE_URL ?>admin/delete_comment.php"
                                onsubmit="return confirm('Are you sure you want to remove this comment?');"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                                >

                                <input
                                    type="hidden"
                                    name="comment_id"
                                    value="<?= (int) $report["comment_id"] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="return_report_id"
                                    value="<?= (int) $report["report_id"] ?>"
                                >

                                <button
                                    type="submit"
                                    class="admin-button-secondary"
                                >
                                    Remove reported comment
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

                <?php if ($isPostReport): ?>

                    <div class="report-comment-moderation">

                        <h4>Post moderation</h4>

                        <?php if ($postRemoved): ?>

                            <p class="report-no-details">
                                This post has already been deleted. The report has been preserved.
                            </p>

                        <?php else: ?>

                            <p>
                                Delete this post and its associated media,
                                comments, likes and saves. The report itself
                                will remain available.
                            </p>

                            <form
                                method="POST"
                                action="<?= BASE_URL ?>admin/delete_post.php"
                                onsubmit="return confirm('Are you sure you want to permanently delete this post? This action cannot be undone.');"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                                >

                                <input
                                    type="hidden"
                                    name="post_id"
                                    value="<?= (int) $report["reported_post_id"] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="return_report_id"
                                    value="<?= (int) $report["report_id"] ?>"
                                >

                                <button
                                    type="submit"
                                    class="admin-button-secondary"
                                >
                                    Delete reported post
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </aside>

        </div>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
