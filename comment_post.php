<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);
$comment = trim($_POST["comment"] ?? "");

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

if ($comment === "") {

    if ($isAjax) {

        header("Content-Type: application/json");

        echo json_encode([
            "success" => false,
            "message" => "Comment cannot be empty."
        ]);

        exit;
    }

    header("Location: feed.php#post-" . $post_id);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO comment (
        user_id,
        post_id,
        comment
    )
    VALUES (?, ?, ?)
");

$stmt->bind_param(
    "iis",
    $user_id,
    $post_id,
    $comment
);

$stmt->execute();

$commentId = $conn->insert_id;

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS comment_count
    FROM comment
    WHERE post_id = ?
");

$countStmt->bind_param("i", $post_id);
$countStmt->execute();

$countRow = $countStmt->get_result()->fetch_assoc();

$commentCount = (int) $countRow["comment_count"];

$userStmt = $conn->prepare("
    SELECT username
    FROM users
    WHERE user_id = ?
");

$userStmt->bind_param("i", $user_id);
$userStmt->execute();

$userRow = $userStmt->get_result()->fetch_assoc();

$username = $userRow["username"];

if ($isAjax) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "comment_id" => $commentId,
        "comment" => $comment,
        "username" => $username,
        "created_at" => date("Y-m-d H:i:s"),
        "comment_count" => $commentCount
    ]);

    exit;
}

header("Location: feed.php#post-" . $post_id);
exit;