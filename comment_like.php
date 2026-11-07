<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
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

$userId = (int) $_SESSION["user_id"];

$commentId = filter_var(
    $_POST["comment_id"] ?? null,
    FILTER_VALIDATE_INT
);

$postId = filter_var(
    $_POST["post_id"] ?? null,
    FILTER_VALIDATE_INT
);

// Validate IDs.
if (!$commentId || $commentId < 1 || !$postId || $postId < 1) {
    http_response_code(400);

    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Invalid comment or post ID."
        ]);
    } else {
        exit("Invalid comment or post ID.");
    }

    exit;
}

try {
    $connector = new \DataAccess\DB\DBConnector();
    $pdo = $connector->connectToDB();

    $commentModel = new Comment($pdo);

    // Toggle the comment like.
    $result = $commentModel->toggleCommentLike(
        $commentId,
        $userId
    );

    // Return JSON for AJAX requests.
    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => true,
            "liked" => $result["liked"],
            "like_count" => $result["like_count"]
        ]);

        exit;
    }

    // Redirect for normal form submissions.
    header("Location: post.php?id=" . $postId);
    exit;

} catch (RuntimeException $e) {
    error_log("Comment like error: " . $e->getMessage());

    http_response_code(400);

    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Unable to like this comment. The comment may no longer exist."
        ]);
    } else {
        exit("Unable to like this comment. The comment may no longer exist.");
    }

    exit;

} catch (Throwable $e) {
    error_log("Comment like error: " . $e->getMessage());

    http_response_code(500);

    if ($isAjax) {
        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Something went wrong while liking the comment. Please try again."
        ]);
    } else {
        exit("Something went wrong while liking the comment. Please try again.");
    }

    exit;
}