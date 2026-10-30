<?php

require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Comment.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$postId = filter_input(INPUT_GET, "post_id", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1]
]);

$offset = filter_input(INPUT_GET, "offset", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 0, "max_range" => 100000]
]);

if ($postId === false || $postId === null ||
    $offset === false || $offset === null) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid post ID or offset."]);
    exit;
}

try {
    $pdoConnector = new \DataAccess\DB\DBConnector();
    $pdo = $pdoConnector->connectToDB();

    $commentModel = new Comment($pdo);

    // Retrieve six to determine whether another page exists.
    $comments = $commentModel->getCommentsByPostId($postId, 6, $offset);

    $hasMore = count($comments) > 5;

    echo json_encode([
        "comments" => array_slice($comments, 0, 5),
        "hasMore" => $hasMore
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log($e->getMessage());

    http_response_code(500);
    echo json_encode([
        "error" => "Could not load comments."
    ]);
}
