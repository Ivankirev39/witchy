<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);

if ($post_id <= 0) {
    header("Location: feed.php");
    exit;
}

// Check that the post belongs to the logged-in user
$stmt = $conn->prepare("
    SELECT user_id
    FROM post
    WHERE post_id = ?
");

$stmt->bind_param("i", $post_id);
$stmt->execute();

$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post || (int) $post["user_id"] !== $user_id) {
    header("Location: feed.php");
    exit;
}

// Delete the post
$stmt = $conn->prepare("
    DELETE FROM post
    WHERE post_id = ?
    AND user_id = ?
");

$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();

$stmt->close();

header("Location: feed.php");
exit;