<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/models/PostInteraction.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/models/Badge.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

$userId = (int) $_SESSION["user_id"];
$postId = (int) ($_POST["post_id"] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    exit("Invalid post ID.");
}

$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();

$postModel = new Post($pdo);
$postInteraction = new PostInteraction($pdo);

try {
    $likeData = $postInteraction->toggleLike($userId, $postId);
} catch (RuntimeException $e) {
    http_response_code(404);

    if (
        isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
        strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
    ) {
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "message" => "Post not found."
        ]);

        exit;
    }

    exit("Post not found.");
}

$liked = $likeData["liked"];
$likeCount = $likeData["like_count"];


if ($liked) {
    $postOwnerId = $postModel->getPostOwnerId($postId);

    if ($postOwnerId !== null && $postOwnerId !== $userId) {

        // Notify the post owner
        createNotification(
            $pdo,
            $postOwnerId,
            $userId,
            "like",
            $postId
        );

        // Check Community Favorite achievement
        try {
            $badgeModel = new Badge($pdo);

            $badgeModel->checkCommunityFavorite(
                $postOwnerId
            );

        } catch (Throwable $e) {
            error_log(
                "Badge award error: " . $e->getMessage()
            );
        }
    }
}


if (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) {
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => true,
        "liked" => $liked,
        "like_count" => $likeCount
    ]);

    exit;
}

header("Location: post.php?id=" . $postId);
exit;