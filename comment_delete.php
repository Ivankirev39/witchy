<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/models/Comment.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$comment_id = (int) ($_POST["comment_id"] ?? 0);
$post_id = (int) ($_POST["post_id"] ?? 0);

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

// Use Comment.php for database operations
$commentModel = new Comment($conn);

$deleted = $commentModel->deleteComment(
    $comment_id,
    $user_id
);

// Get the updated comment count
$commentCount = $commentModel->getCommentCountByPostId(
    $post_id
);

if ($isAjax) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => $deleted,
        "comment_count" => $commentCount
    ]);

    exit;
}

// Fallback if JavaScript is unavailable
header("Location: feed.php#post-" . $post_id);
exit;
