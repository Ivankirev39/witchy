<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/KeywordFilter.php";

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
$returnUrl = ($_POST["return_to"] ?? "") === "post"
    ? "post.php?id=" . $post_id
    : "feed.php#post-" . $post_id;
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

    header("Location: " . $returnUrl);
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

            $maxDimension = 6000;
            $maxPixels = 24000000;

            if (
                $width < 1 ||
                $height < 1 ||
                $width > $maxDimension ||
                $height > $maxDimension ||
                $width * $height > $maxPixels
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
$postModel = new Post($pdo);

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
    $postOwnerId = $postModel->getPostOwnerId($post_id);

    if ($postOwnerId !== null && $postOwnerId !== $user_id) {
        try {
            createNotification(
                $pdo,
                $postOwnerId,
                $user_id,
                "comment",
                $post_id
            );
        } catch (PDOException $e) {
            error_log("Comment notification failed: " . $e->getMessage());
        }
    }
}
$commentCount = $commentModel->getCommentCountByPostId($post_id);
$userDetails = $commentModel->getUserDetailsByUserId($user_id);

if ($isAjax) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "comment_id" => $commentId,
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

header("Location: " . $returnUrl);
exit;
