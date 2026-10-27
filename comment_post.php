<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);
$commentText = trim($_POST["comment"] ?? "");

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

$hasMedia =
    isset($_FILES["comment_media"]) &&
    $_FILES["comment_media"]["error"] !== UPLOAD_ERR_NO_FILE;

if ($commentText === "" && !$hasMedia) {
    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Comment cannot be empty."
        ]);

        exit;
    }

    header("Location: feed.php#post-" . $post_id);
    exit;
}

$mediaFile = null;
$mediaType = null;

if ($hasMedia) {
    $file = $_FILES["comment_media"];

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp",
        "image/gif" => "gif"
    ];

    $error = null;

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "Image upload failed.";
    } elseif (
        $file["size"] > 5 * 1024 * 1024 ||
        $file["size"] <= 0
    ) {
        $error = "Image must be between 1 byte and 5 MB.";
    } elseif (!is_uploaded_file($file["tmp_name"])) {
        $error = "Invalid upload.";
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))
            ->file($file["tmp_name"]);

        if (
            !isset($allowedTypes[$mime]) ||
            @getimagesize($file["tmp_name"]) === false
        ) {
            $error = "Only JPG, PNG, WEBP and GIF images are allowed.";
        }
    }

    if ($error === null) {
        $directory = __DIR__ . "/uploads/comments/";

        if (
            !is_dir($directory) &&
            !mkdir($directory, 0755, true)
        ) {
            $error = "Could not create upload directory.";
        } else {
            $mediaType = $mime;
            $mediaFile = bin2hex(random_bytes(16))
                . "." . $allowedTypes[$mime];

            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $directory . $mediaFile
                )
            ) {
                $error = "Could not save image.";
            }
        }
    }

    if ($error !== null) {
        if ($isAjax) {
            header("Content-Type: application/json");

            echo json_encode([
                "success" => false,
                "message" => $error
            ]);

            exit;
        }

        http_response_code(400);
        exit($error);
    }
}

// Database operations are handled by Comment.php
$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();
$commentModel = new Comment($pdo);

try {
    $commentId = $commentModel->createComment(
        $post_id,
        $user_id,
        $commentText,
        $mediaFile,
        $mediaType
    );
} catch (PDOException $e) {
    if ($mediaFile !== null) {
        @unlink(__DIR__ . "/uploads/comments/" . $mediaFile);
    }

    throw $e;
}

if ($commentId) {
    $ownerStmt = $pdo->prepare("SELECT user_id FROM post WHERE post_id = ?");
    $ownerStmt->execute([$post_id]);
    $postOwnerId = $ownerStmt->fetchColumn();
    if ($postOwnerId !== false && (int) $postOwnerId !== $user_id) {
        try {
            createNotification($pdo, (int) $postOwnerId, $user_id, "comment", $post_id);
        } catch (PDOException $e) {
            error_log("Comment notification failed: " . $e->getMessage());
        }
    }
}
$commentCount = $commentModel->getCommentCountByPostId($post_id);
$username = $commentModel->getUsernameByUserId($user_id);

if ($isAjax) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "comment_id" => $commentId,
        "comment" => $commentText,
        "media_url" => $mediaFile !== null
            ? "uploads/comments/" . rawurlencode($mediaFile)
            : null,
        "username" => $username,
        "created_at" => date("Y-m-d H:i:s"),
        "comment_count" => $commentCount
    ]);

    exit;
}

header("Location: feed.php#post-" . $post_id);
exit;
