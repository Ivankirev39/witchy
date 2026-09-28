<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$post_id = (int) $_POST["post_id"];
$comment = trim($_POST["comment"] ?? "");


/*
    Don't insert an empty comment.
*/

if ($comment === "") {
    header("Location: feed.php");
    exit;
}


$stmt = $conn->prepare("
    INSERT INTO comment (user_id, post_id, comment)
    VALUES (?, ?, ?)
");

$stmt->bind_param(
    "iis",
    $user_id,
    $post_id,
    $comment
);

$stmt->execute();


header("Location: feed.php");
exit;