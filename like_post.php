<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/classes/Post.php";

// Only accept POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

// Validate the CSRF token before changing data.
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);

// Reject invalid post IDs.
if ($post_id <= 0) {
    http_response_code(400);
    exit("Invalid post ID.");
}

$postModel = new Post($conn);
$likeData = $postModel->toggleLike($user_id, $post_id);

$liked = $likeData["liked"];
$likeCount = $likeData["like_count"];

if (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) {
    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "liked" => $liked,
        "like_count" => $likeCount
    ]);

    exit;
}

header("Location: post.php?id=" . $post_id);
exit;
