<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

/* 1. Validate request method */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: content.php?tab=posts");
    exit;
}

/* 2. Validate CSRF token */

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

/* 3. Validate post ID */

$postId = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);

if (!$postId || $postId < 1) {
    header("Location: content.php?tab=posts");
    exit;
}

/* 4. Validate return destination */

$returnFrom = $_POST["return_from"] ?? "";

$returnUserId = filter_input(
    INPUT_POST,
    "return_user_id",
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

/* 5. Queue uploaded files for safe deletion */

$queueFile = function (string $folder, ?string $filename) use ($conn): void {

    if ($filename === null || $filename === "") {
        return;
    }

    // Only allow plain filenames, never paths.
    if (
        basename($filename) !== $filename ||
        !preg_match('/^[a-zA-Z0-9_.-]+$/D', $filename)
    ) {
        throw new RuntimeException("Invalid uploaded filename.");
    }

    // Avoid adding the same file to the queue more than once.
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

/* 6. Delete post safely */

try {

    $conn->beginTransaction();

    // Lock and retrieve the post.
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

        header("Location: content.php?tab=posts&status=not_found");
        exit;
    }

    // Queue the main post image.
    $queueFile("posts", $post["image"]);

    // Retrieve and queue additional post media.
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

    // Retrieve and queue comment attachments.
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

    // Remove notifications connected to this post.
    $stmt = $conn->prepare("
        DELETE FROM notification
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Remove likes.
    $stmt = $conn->prepare("
        DELETE FROM post_like
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Remove saved-post records.
    $stmt = $conn->prepare("
        DELETE FROM save
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Remove comments.
    $stmt = $conn->prepare("
        DELETE FROM comment
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Remove additional media records.
    $stmt = $conn->prepare("
        DELETE FROM post_media
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Finally, remove the post itself.
    $stmt = $conn->prepare("
        DELETE FROM post
        WHERE post_id = :post_id
    ");

    $stmt->execute([
        ":post_id" => $postId
    ]);

    // Commit all database changes.
    $conn->commit();

    /* 7. Return to the correct page */

    if ($returnFrom === "user" && $returnUserId !== null) {
        header(
            "Location: view_user.php?id=" .
            (int) $returnUserId .
            "&post_status=deleted"
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

    if ($returnFrom === "user" && $returnUserId !== null) {
        header(
            "Location: view_user.php?id=" .
            (int) $returnUserId .
            "&post_status=error"
        );
        exit;
    }

    header(
        "Location: view_post.php?id=" .
        (int) $postId .
        "&status=error"
    );
    exit;
}
