<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";
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
$comment_id = (int) ($_POST["comment_id"] ?? 0);
$post_id = (int) ($_POST["post_id"] ?? 0);

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

// Create PDO connection
$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();
// Use Comment.php for database operations
$commentModel = new Comment($pdo);

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