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

$isAjax =
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

// Delete only if the comment belongs to the logged-in user
$stmt = $conn->prepare("
    DELETE FROM comment
    WHERE comment_id = ?
    AND user_id = ?
");

$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();

$deleted = $stmt->affected_rows > 0;
$stmt->close();

// Get updated comment count
$countStmt = $conn->prepare("
    SELECT COUNT(*) AS comment_count
    FROM comment
    WHERE post_id = ?
");

$countStmt->bind_param("i", $post_id);
$countStmt->execute();

$countRow = $countStmt->get_result()->fetch_assoc();
$commentCount = (int) $countRow["comment_count"];

$countStmt->close();

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
?>