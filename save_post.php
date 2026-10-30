<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid request. Please refresh the page and try again.");
}

$userId = (int) $_SESSION["user_id"];
$postId = (int) ($_POST["post_id"] ?? 0);

if ($postId <= 0) {
    http_response_code(400);

    if ($isAjax) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "message" => "Invalid post ID."
        ]);
        exit;
    }

    header("Location: feed.php");
    exit;
}

$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();
$postModel = new Post($pdo);

try {
    $saved = $postModel->toggleSave($userId, $postId);
} catch (RuntimeException $e) {
    http_response_code(404);

    if ($isAjax) {
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "message" => "Post not found."
        ]);
        exit;
    }

    header("Location: feed.php");
    exit;
}

if ($isAjax) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => true,
        "saved" => $saved
    ]);
    exit;
}

if (($_POST["redirect"] ?? "") === "saved.php") {
    header("Location: saved.php");
} elseif (($_POST["return_to"] ?? "") === "post") {
    header("Location: post.php?id=" . $postId);
} else {
    header("Location: feed.php#post-" . $postId);
}

exit;