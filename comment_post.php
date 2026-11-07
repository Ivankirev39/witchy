<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/KeywordFilter.php";

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

// Detect AJAX requests.
$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

// Validate CSRF token.
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);

    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Invalid security token. Please refresh the page and try again."
        ]);
    } else {
        exit("Invalid security token. Please refresh the page and try again.");
    }

    exit;
}

$user_id = (int) $_SESSION["user_id"];

$post_id = filter_var(
    $_POST["post_id"] ?? null,
    FILTER_VALIDATE_INT
);

$parentCommentId = filter_var(
    $_POST["parent_comment_id"] ?? null,
    FILTER_VALIDATE_INT
);

// Treat an empty parent ID as a normal comment.
if ($parentCommentId === false || $parentCommentId === null) {
    $parentCommentId = 0;
}

$returnUrl = ($_POST["return_to"] ?? "") === "post"
    ? "post.php?id=" . (int) $post_id
    : "feed.php#post-" . (int) $post_id;

$commentText = trim($_POST["comment"] ?? "");

// Validate post ID.
if (!$post_id || $post_id < 1) {
    http_response_code(400);
    exit("Invalid post ID.");
}

// Validate parent comment ID.
if ($parentCommentId < 0) {
    http_response_code(400);
    exit("Invalid parent comment ID.");
}

// Detect uploaded media.
$hasMedia =
    isset($_FILES["comment_media"]) &&
    $_FILES["comment_media"]["error"] !== UPLOAD_ERR_NO_FILE;

// Replies are text-only for now.
if ($parentCommentId > 0 && $hasMedia) {
    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Image attachments are not available for replies."
        ]);
    } else {
        http_response_code(400);
        exit("Image attachments are not available for replies.");
    }

    exit;
}

// Validate comment content.
if ($commentText === "") {
    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Comment cannot be empty."
        ]);
    } else {
        header("Location: " . $returnUrl);
    }

    exit;
}

// Check comment for prohibited words and phrases
$keywordFilter = new KeywordFilter();

if (
    $commentText !== "" &&
    $keywordFilter->containsBlockedContent($commentText)
) 
{
    $errorMessage = "Your comment contains inappropriate language. Please edit it and try again.";

    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => $errorMessage
        ]);

        exit;
    }

    http_response_code(400);
    exit($errorMessage);
}


$mediaFile = null;
$mediaType = null;

// Handle image uploads for normal comments.
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

        $imageInfo = @getimagesize($file["tmp_name"]);

        if (
            !isset($allowedTypes[$mime]) ||
            $imageInfo === false
        ) {
            $error = "Only valid JPG, PNG, WEBP and GIF images are allowed.";
        } else {
            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];

            if (
                $width < 1 ||
                $height < 1 ||
                $width > 6000 ||
                $height > 6000 ||
                $width * $height > 24000000
            ) {
                $error = "Image dimensions are too large. Maximum 6000px per side and 24 megapixels.";
            }
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

            if (!move_uploaded_file(
                $file["tmp_name"],
                $directory . $mediaFile
            )) {
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
        } else {
            http_response_code(400);
            exit($error);
        }

        exit;
    }
}

// Connect to database.
$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();

$commentModel = new Comment($pdo);
$postModel = new Post($pdo);

try {
    if ($parentCommentId > 0) {
        // Create a reply to an existing comment.
        $commentId = $commentModel->createReply(
            $parentCommentId,
            $post_id,
            $user_id,
            $commentText
        );
    } else {
        // Create a normal comment.
        $commentId = $commentModel->createComment(
            $post_id,
            $user_id,
            $commentText,
            $mediaFile,
            $mediaType
        );
    }

} catch (Throwable $e) {
    if ($mediaFile !== null) {
        @unlink(__DIR__ . "/uploads/comments/" . $mediaFile);
    }

    error_log("Comment creation error: " . $e->getMessage());

    if ($isAjax) {
        http_response_code(400);
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => $parentCommentId > 0
                ? "Could not post your reply."
                : "Could not post your comment."
        ]);
    } else {
        header("Location: " . $returnUrl);
    }

    exit;
}

// Send notification.
try {
    $postOwnerId = $postModel->getPostOwnerId($post_id);

    if ($postOwnerId !== null && $postOwnerId !== $user_id) {
        createNotification(
            $pdo,
            $postOwnerId,
            $user_id,
            "comment",
            $post_id
        );
    }
} catch (Throwable $e) {
    error_log("Comment notification failed: " . $e->getMessage());
}

// Retrieve author details.
$userDetails = $commentModel->getUserDetailsByUserId($user_id);

if (!$userDetails) {
    throw new RuntimeException("Could not retrieve comment author.");
}

// Retrieve updated comment count.
$commentCount = $commentModel->getCommentCountByPostId($post_id);

// Return AJAX response.
if ($isAjax) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "comment_id" => $commentId,
        "parent_comment_id" => $parentCommentId > 0
            ? $parentCommentId
            : null,
        "user_id" => $user_id,
        "comment" => $commentText,
        "media_url" => $mediaFile !== null
            ? "uploads/comments/" . rawurlencode($mediaFile)
            : null,
        "username" => $userDetails["username"],
        "profile_image" => $userDetails["profile_image"],
        "created_at" => date("Y-m-d H:i:s"),
        "comment_count" => $commentCount
    ]);

    exit;
}

// Normal form submission.
header("Location: " . $returnUrl);
exit;