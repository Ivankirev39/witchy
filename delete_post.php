<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/models/Post.php";
require_once __DIR__ . "/includes/csrf.php";

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
    header("Location: feed.php");
    exit;
}

$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();
$postModel = new Post($pdo);

$deletedFiles = $postModel->deletePost($postId, $userId);

if ($deletedFiles !== null) {
    $directories = [
        "posts" => __DIR__ . "/uploads/posts/",
        "comments" => __DIR__ . "/uploads/comments/"
    ];

    foreach ($directories as $type => $directory) {
        foreach ($deletedFiles[$type] as $file) {
            $path = $directory . basename($file);

            if (is_file($path) && !unlink($path)) {
                error_log("Could not remove uploaded file: " . $path);
            }
        }
    }
}

header("Location: feed.php");
exit;