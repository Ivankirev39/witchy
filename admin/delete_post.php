<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: content.php?tab=posts");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

$postId = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);

if (!$postId || $postId < 1) {
    header("Location: content.php?tab=posts");
    exit;
}

$returnFrom = $_POST["return_from"] ?? "";

$returnUserId = filter_input(
    INPUT_POST,
    "return_user_id",
    FILTER_VALIDATE_INT
);

$returnReportId = filter_input(
    INPUT_POST,
    "return_report_id",
    FILTER_VALIDATE_INT
);

if (
    $returnFrom !== "user" ||
    !$returnUserId ||
    $returnUserId < 1
) {
    $returnFrom = "";
    $returnUserId = null;
} else {
    $returnUserId = (int) $returnUserId;
}

if (!$returnReportId || $returnReportId < 1) {
    $returnReportId = null;
} else {
    $returnReportId = (int) $returnReportId;
}

$queueFile = function (string $folder, ?string $filename) use ($conn): void {

    if ($filename === null || $filename === "") {
        return;
    }

    if (
        basename($filename) !== $filename ||
        !preg_match('/^[a-zA-Z0-9_.-]+$/D', $filename)
    ) {
        throw new RuntimeException("Invalid uploaded filename.");
    }

    $checkStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM file_deletion_queue
        WHERE folder = :folder
          AND filename = :filename
    ");

    $checkStmt->execute([
        ":folder" => $folder,
        ":filename" => $filename
    ]);

    if ((int) $checkStmt->fetchColumn() > 0) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO file_deletion_queue
            (folder, filename, queued_at, attempts, last_error)
        VALUES
            (:folder, :filename, NOW(), 0, NULL)
    ");

    $stmt->execute([
        ":folder" => $folder,
        ":filename" => $filename
    ]);
};

try {

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        SELECT post_id, image
        FROM post
        WHERE post_id = :post_id
        FOR UPDATE
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $conn->rollBack();

        if ($returnReportId !== null) {
            header(
                "Location: view_report.php?id="
                . $returnReportId
                . "&action_success=post_removed"
            );
            exit;
        }

        header("Location: content.php?tab=posts&status=not_found");
        exit;
    }

    $queueFile("posts", $post["image"]);

    $stmt = $conn->prepare("
        SELECT file
        FROM post_media
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $mediaFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($mediaFiles as $filename) {
        $queueFile("posts", $filename);
    }

    $stmt = $conn->prepare("
        SELECT media_file
        FROM comment
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $commentFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($commentFiles as $filename) {
        $queueFile("comments", $filename);
    }

    $stmt = $conn->prepare("
        DELETE FROM notification
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $stmt = $conn->prepare("
        DELETE FROM post_like
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $stmt = $conn->prepare("
        DELETE FROM save
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $stmt = $conn->prepare("
        DELETE FROM comment
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $stmt = $conn->prepare("
        DELETE FROM post_media
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $stmt = $conn->prepare("
        DELETE FROM post
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    $conn->commit();

    if ($returnReportId !== null) {
        header(
            "Location: view_report.php?id="
            . $returnReportId
            . "&action_success=post_removed"
        );
        exit;
    }

    if ($returnFrom === "user" && $returnUserId !== null) {
        header(
            "Location: view_user.php?id="
            . $returnUserId
            . "&post_status=deleted"
        );
        exit;
    }

    header("Location: content.php?tab=posts&status=deleted");
    exit;

} catch (Throwable $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Admin post deletion failed: " . $e->getMessage());

    if ($returnReportId !== null) {
        header(
            "Location: view_report.php?id="
            . $returnReportId
            . "&action_error=server"
        );
        exit;
    }

    if ($returnFrom === "user" && $returnUserId !== null) {
        header(
            "Location: view_user.php?id="
            . $returnUserId
            . "&post_status=error"
        );
        exit;
    }

    header(
        "Location: view_post.php?id="
        . (int) $postId
        . "&status=error"
    );
    exit;
}
