<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: content.php?tab=comments");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

/* 3. Validate IDs and return destination */

$commentId = filter_input(
    INPUT_POST,
    "comment_id",
    FILTER_VALIDATE_INT
);

$returnPostId = filter_input(
    INPUT_POST,
    "return_post_id",
    FILTER_VALIDATE_INT
);

$returnPostId = ($returnPostId && $returnPostId > 0)
    ? (int) $returnPostId
    : null;

// Optional report ID when deletion originates from report details.
$returnReportId = filter_input(
    INPUT_POST,
    "return_report_id",
    FILTER_VALIDATE_INT
);

$returnReportId = ($returnReportId && $returnReportId > 0)
    ? (int) $returnReportId
    : null;

$returnFrom = $_POST["return_from"] ?? "";

$returnUserId = filter_input(
    INPUT_POST,
    "return_user_id",
    FILTER_VALIDATE_INT
);

$returnUserId = (
    $returnFrom === "user" &&
    $returnUserId &&
    $returnUserId > 0
) ? (int) $returnUserId : null;

if ($returnUserId === null) {
    $returnFrom = "";
}

if (!$commentId || $commentId < 1) {
    header("Location: content.php?tab=comments&status=invalid");
    exit;
}

/* 4. Delete comment safely */

$redirectToReport = false;
$redirectToPost = false;
$hasReports = false;

try {

    $conn->beginTransaction();

    /* Retrieve and lock the comment */

    $stmt = $conn->prepare("
        SELECT
            comment_id,
            post_id,
            media_file,
            deleted_at
        FROM comment
        WHERE comment_id = :comment_id
        FOR UPDATE
    ");

    $stmt->execute([
        ":comment_id" => $commentId
    ]);

    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        throw new RuntimeException("Comment not found.");
    }

    /*
     * Only return to a post review if the submitted post ID
     * actually matches the comment's post.
     */

    $redirectToPost = (
        $returnPostId !== null &&
        (int) $comment["post_id"] === $returnPostId
    );

    /* Check whether this comment has any reports */

    $reportStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM reports
        WHERE comment_id = :comment_id
    ");

    $reportStmt->execute([
        ":comment_id" => $commentId
    ]);

    $hasReports = (int) $reportStmt->fetchColumn() > 0;

    /* If the request came from report details, verify that the supplied report actually belongs to this comment */

    if ($returnReportId !== null) {

        $reportCheck = $conn->prepare("
            SELECT COUNT(*)
            FROM reports
            WHERE report_id = :report_id
              AND comment_id = :comment_id
        ");

        $reportCheck->execute([
            ":report_id" => $returnReportId,
            ":comment_id" => $commentId
        ]);

        $redirectToReport = (
            (int) $reportCheck->fetchColumn() === 1
        );

        if (!$redirectToReport) {
            throw new RuntimeException(
                "The requested report does not belong to this comment."
            );
        }
    }

    /*
     * REPORTED COMMENT: SOFT DELETE
     *
     * Preserve the comment record and report history.
     * The deleted_at column makes it disappear from public views.
     */

    if ($hasReports) {

        $deleteStmt = $conn->prepare("
            UPDATE comment
            SET deleted_at = COALESCE(deleted_at, NOW())
            WHERE comment_id = :comment_id
        ");

        $deleteStmt->execute([
            ":comment_id" => $commentId
        ]);

    } else {

        /*
         * UNREPORTED COMMENT: PERMANENT DELETE
         *
         * Keep the existing attachment cleanup queue.
         */

        if (!empty($comment["media_file"])) {

            $filename = basename($comment["media_file"]);

            if (
                $filename !== $comment["media_file"] ||
                !preg_match('/\A[a-zA-Z0-9_.-]+\z/', $filename)
            ) {
                throw new RuntimeException(
                    "Invalid comment attachment filename."
                );
            }

            /* Check whether another comment uses this file */

            $referenceStmt = $conn->prepare("
                SELECT COUNT(*)
                FROM comment
                WHERE media_file = :filename
                  AND comment_id != :comment_id
            ");

            $referenceStmt->execute([
                ":filename" => $filename,
                ":comment_id" => $commentId
            ]);

            $otherReferences = (int) $referenceStmt->fetchColumn();

            if ($otherReferences === 0) {

                $queueCheck = $conn->prepare("
                    SELECT COUNT(*)
                    FROM file_deletion_queue
                    WHERE folder = :folder
                      AND filename = :filename
                ");

                $queueCheck->execute([
                    ":folder" => "comments",
                    ":filename" => $filename
                ]);

                $alreadyQueued = (int) $queueCheck->fetchColumn();

                if ($alreadyQueued === 0) {

                    $queueStmt = $conn->prepare("
                        INSERT INTO file_deletion_queue (
                            folder,
                            filename,
                            queued_at,
                            attempts,
                            last_error
                        )
                        VALUES (
                            :folder,
                            :filename,
                            NOW(),
                            0,
                            NULL
                        )
                    ");

                    $queueStmt->execute([
                        ":folder" => "comments",
                        ":filename" => $filename
                    ]);
                }
            }
        }

        /* Permanently delete the unreported comment */

        $deleteStmt = $conn->prepare("
            DELETE FROM comment
            WHERE comment_id = :comment_id
        ");

        $deleteStmt->execute([
            ":comment_id" => $commentId
        ]);

        if ($deleteStmt->rowCount() !== 1) {
            throw new RuntimeException(
                "Comment could not be deleted."
            );
        }
    }

    /* 5. Commit the deletion and queue entry together */

    $conn->commit();

    /* 6. Return to the correct page */

    // Return to the report details page.
    if ($redirectToReport && $hasReports) {

        header(
            "Location: view_report.php?id=" .
            $returnReportId .
            "&action_success=comment_removed"
        );

        exit;
    }

    // A comment deleted from a post review returns to that post review.
    if ($redirectToPost) {

        $redirectUrl = "view_post.php?id=" .
            (int) $comment["post_id"] .
            "&comment_status=deleted";

        if ($returnFrom === "user" && $returnUserId !== null) {
            $redirectUrl .=
                "&from=user&return_user_id=" . $returnUserId;
        }

        header("Location: " . $redirectUrl);
        exit;
    }

    // A comment deleted directly from a user's profile returns there.
    if ($returnFrom === "user" && $returnUserId !== null) {

        header(
            "Location: view_user.php?id=" .
            (int) $returnUserId .
            "&comment_status=deleted"
        );

        exit;
    }

    // Otherwise return to the Content comments tab.
    header("Location: content.php?tab=comments&status=deleted");
    exit;

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log(
        "Admin comment deletion failed: " . $e->getMessage()
    );

    /* 7. Return to the original page with an error */

    // If the request came from report details, return to that report.
    if ($redirectToReport && $returnReportId !== null) {

        header(
            "Location: view_report.php?id=" .
            $returnReportId .
            "&action_error=server"
        );

        exit;
    }

    if ($returnPostId !== null) {

        $redirectUrl = "view_post.php?id=" .
            $returnPostId .
            "&comment_status=error";

        if ($returnFrom === "user" && $returnUserId !== null) {
            $redirectUrl .=
                "&from=user&return_user_id=" . $returnUserId;
        }

        header("Location: " . $redirectUrl);
        exit;
    }

    if ($returnFrom === "user" && $returnUserId !== null) {

        header(
            "Location: view_user.php?id=" .
            (int) $returnUserId .
            "&comment_status=error"
        );

        exit;
    }

    header("Location: content.php?tab=comments&status=error");
    exit;
}
