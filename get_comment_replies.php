<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid request."]);
    exit;
}

$parentCommentId = filter_var(
    $_POST["parent_comment_id"] ?? null,
    FILTER_VALIDATE_INT
);

$offset = filter_var(
    $_POST["offset"] ?? 0,
    FILTER_VALIDATE_INT
);

if (!$parentCommentId || $parentCommentId < 1 || $offset === false || $offset < 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid request data."]);
    exit;
}

$pdoConnector = new \DataAccess\DB\DBConnector();
$pdo = $pdoConnector->connectToDB();

$commentModel = new Comment($pdo);

$replies = $commentModel->getRepliesByCommentIdPaginated(
    $parentCommentId,
    3,
    $offset
);

$replyCount = $commentModel->getReplyCountByCommentId($parentCommentId);

$formattedReplies = [];

foreach ($replies as $reply) {
    $formattedReplies[] = [
        "comment_id" => (int) $reply["comment_id"],
        "post_id" => (int) $reply["post_id"],
        "user_id" => (int) $reply["user_id"],
        "username" => $reply["username"],
        "profile_image" => $reply["profile_image"],
        "comment" => $reply["comment"],
        "created_at" => $reply["created_at"],
        "like_count" => $commentModel->getCommentLikeCount((int) $reply["comment_id"]),
        "user_liked" => $commentModel->hasUserLikedComment(
            (int) $reply["comment_id"],
            (int) $_SESSION["user_id"]
        ),
        "can_delete" => (int) $reply["user_id"] === (int) $_SESSION["user_id"]
    ];
}

echo json_encode([
    "success" => true,
    "replies" => $formattedReplies,
    "reply_count" => $replyCount,
    "next_offset" => $offset + count($formattedReplies),
    "has_more" => ($offset + count($formattedReplies)) < $replyCount
]);

exit;