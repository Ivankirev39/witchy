<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);

$stmt = $conn->prepare("
    SELECT post_id
    FROM post_like
    WHERE user_id = ? AND post_id = ?
");

$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt = $conn->prepare("
        DELETE FROM post_like
        WHERE user_id = ? AND post_id = ?
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();

    $liked = false;

} else {

    $stmt = $conn->prepare("
        INSERT INTO post_like (user_id, post_id)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();

    $liked = true;
}

$countStmt = $conn->prepare("
    SELECT COUNT(*) AS like_count
    FROM post_like
    WHERE post_id = ?
");

$countStmt->bind_param("i", $post_id);
$countStmt->execute();

$row = $countStmt->get_result()->fetch_assoc();

$likeCount = (int) $row["like_count"];

if (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "liked" => $liked,
        "like_count" => $likeCount
    ]);

    exit;
}

header("Location: feed.php#post-" . $post_id);
exit;