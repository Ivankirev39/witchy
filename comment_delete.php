<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$comment_id = (int) ($_POST["comment_id"] ?? 0);
$post_id = (int) ($_POST["post_id"] ?? 0);
$return_to = $_POST["return_to"] ?? "feed";

if ($comment_id <= 0 || $post_id <= 0) {
    header("Location: feed.php");
    exit;
}

// Delete only if the comment belongs to the logged-in user
$stmt = $conn->prepare("
    DELETE FROM comment
    WHERE comment_id = ?
    AND user_id = ?
");

$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$stmt->close();

// Return to the page where the comment was deleted
if ($return_to === "post") {
    header("Location: post.php?id=" . $post_id);
} else {
    header("Location: feed.php?comments=" . $post_id . "#post-" . $post_id);
}

exit;