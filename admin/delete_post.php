<?php

require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

// Only POST requests are allowed.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: content.php?tab=posts");
    exit;
}

// Validate CSRF token.
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

// Validate post ID.
$postId = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);

if (!$postId || $postId < 1) {
    header("Location: content.php?tab=posts");
    exit;
}

// Queue an uploaded file for safe deletion.
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

    $stmt = $conn->prepare("
        INSERT INTO file_deletion_queue
            (folder, filename, queued_at, attempts, last_error)
        VALUES
            (:folder, :filename, NOW(), 0, NULL)
    ");

    $stmt->execute([
        "folder" => $folder,
        "filename" => $filename
    ]);
};

try {
    $conn->beginTransaction();

    // Lock and retrieve the post.
    $stmt = $conn->prepare("
        SELECT post_id, image
        FROM post
        WHERE post_id = :post_id
        FOR UPDATE
    ");

    $stmt->execute(["post_id" => $postId]);
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

    $stmt->execute(["post_id" => $postId]);
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

    $stmt->execute(["post_id" => $postId]);
    $commentFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($commentFiles as $filename) {
        $queueFile("comments", $filename);
    }

    // Remove notifications connected to this post.
    $stmt = $conn->prepare("
        DELETE FROM notification
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    // Remove likes.
    $stmt = $conn->prepare("
        DELETE FROM post_like
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    // Remove saved-post records.
    $stmt = $conn->prepare("
        DELETE FROM save
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    // Remove comments.
    $stmt = $conn->prepare("
        DELETE FROM comment
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    // Remove additional media records.
    $stmt = $conn->prepare("
        DELETE FROM post_media
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    // Finally, remove the post itself.
    $stmt = $conn->prepare("
        DELETE FROM post
        WHERE post_id = :post_id
    ");
    $stmt->execute(["post_id" => $postId]);

    $conn->commit();

    header("Location: content.php?tab=posts&status=deleted");
    exit;

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Admin post deletion failed: " . $e->getMessage());

    header(
        "Location: view_post.php?id=" . $postId . "&status=error"
    );
    exit;
}