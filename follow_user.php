<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid request.");
}
$user_id = (int) $_SESSION["user_id"];
$target_id = (int) ($_POST["user_id"] ?? 0);
if ($target_id <= 0 || $target_id === $user_id) {
    http_response_code(400);
    exit("Invalid user.");
}
$userStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
$userStmt->execute([$target_id]);
$targetExists = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$targetExists) {
    http_response_code(404);
    exit("User not found.");
}
$checkStmt = $conn->prepare("
    SELECT 1
    FROM follow
    WHERE follower_id = ? AND following_id = ?
");
$checkStmt->execute([$user_id, $target_id]);
$isFollowing = (bool) $checkStmt->fetchColumn();
$action = $_POST["action"] ?? ($isFollowing ? "unfollow" : "follow");
if ($action === "follow") {
    $followStmt = $conn->prepare("
        INSERT IGNORE INTO follow (follower_id, following_id)
        VALUES (?, ?)
    ");
    $followStmt->execute([$user_id, $target_id]);
} elseif ($action === "unfollow") {
    $unfollowStmt = $conn->prepare("
        DELETE FROM follow
        WHERE follower_id = ? AND following_id = ?
    ");
    $unfollowStmt->execute([$user_id, $target_id]);
} else {
    http_response_code(400);
    exit("Invalid action.");
}
if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => true,
        "following" => $action === "follow"
    ]);
    exit;
}
$returnTo = $_POST["return_to"] ?? "";
if (preg_match('/^profile\.php\?id=\d+$/', $returnTo)) {
    header("Location: " . $returnTo);
} elseif (preg_match('/^feed\.php#post-\d+$/', $returnTo)) {
    header("Location: " . $returnTo);
} elseif ((int) ($_POST["return_post"] ?? 0) > 0) {
    header("Location: post.php?id=" . (int) $_POST["return_post"]);
} else {
    header("Location: feed.php");
}
exit;
