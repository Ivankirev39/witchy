<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/notifications.php";
require_once __DIR__ . "/models/User.php";
require_once __DIR__ . "/models/Follow.php";

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

$userModel = new User($conn);
$followModel = new Follow($conn);

// Check that the user exists
$targetUser = $userModel->getPublicProfile($target_id);

if (!$targetUser) {
    http_response_code(404);
    exit("User not found.");
}

// Check follow status
$isFollowing = $followModel->isFollowing($user_id, $target_id);
$action = $_POST["action"] ?? ($isFollowing ? "unfollow" : "follow");

if ($action === "follow") {

    $followed = $followModel->followUser($user_id, $target_id);

    if ($followed) {
        createNotification($conn, $target_id, $user_id, "follow");
    }

} elseif ($action === "unfollow") {

    $followModel->unfollowUser($user_id, $target_id);

} else {

    http_response_code(400);
    exit("Invalid action.");
}

    // Check the actual follow status after the action
    $followingNow = $followModel->isFollowing(
        $user_id,
        $target_id
    );

    $expectedFollowing = $action === "follow";

    if ($followingNow !== $expectedFollowing) {
        http_response_code(500);

        if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") {
            header("Content-Type: application/json; charset=UTF-8");

            echo json_encode([
                "success" => false,
                "error" => "Could not update follow status."
            ]);
        } else {
            echo "Could not update follow status.";
        }

        exit;
    }

// AJAX response
if (($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "XMLHttpRequest") {
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => true,
        "following" => $followingNow
    ]);
    exit;
}

// Redirect after following or unfollowing
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
