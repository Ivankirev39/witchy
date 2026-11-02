<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/csrf.php";

$isAjax = isset($_SERVER["HTTP_X_REQUESTED_WITH"])
    && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest";

function pinResponse(bool $success, string $status, int $httpCode = 200): void
{
    global $isAjax, $userId;

    if ($isAjax) {
        http_response_code($httpCode);
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => $success,
            "status" => $status
        ]);
        exit;
    }

    header(
        "Location: profile.php?id=" . $userId .
        "&pin_status=" . $status
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? "")) {
    pinResponse(false, "pin_error", 403);
}

$userId = (int) $_SESSION["user_id"];
$postId = (int) ($_POST["post_id"] ?? 0);

if ($postId <= 0) {
    pinResponse(false, "pin_error", 400);
}

$pdo = $conn;

try {
    $pdo->beginTransaction();

    $userStmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE user_id = ?
        FOR UPDATE
    ");
    $userStmt->execute([$userId]);

    if (!$userStmt->fetchColumn()) {
        throw new RuntimeException("User not found.");
    }

    $postStmt = $pdo->prepare("
        SELECT post_id
        FROM post
        WHERE post_id = ?
          AND user_id = ?
    ");
    $postStmt->execute([$postId, $userId]);

    if (!$postStmt->fetchColumn()) {
        throw new RuntimeException("Post not found or not owned by you.");
    }

    $pinStmt = $pdo->prepare("
        SELECT 1
        FROM profile_pinned_post
        WHERE user_id = ?
          AND post_id = ?
    ");
    $pinStmt->execute([$userId, $postId]);

    if ($pinStmt->fetchColumn()) {
        $deleteStmt = $pdo->prepare("
            DELETE FROM profile_pinned_post
            WHERE user_id = ?
              AND post_id = ?
        ");
        $deleteStmt->execute([$userId, $postId]);

        $message = "unpinned";
    } else {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM profile_pinned_post
            WHERE user_id = ?
        ");
        $countStmt->execute([$userId]);

        if ((int) $countStmt->fetchColumn() >= 3) {
            throw new RuntimeException("limit");
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO profile_pinned_post (user_id, post_id)
            VALUES (?, ?)
        ");
        $insertStmt->execute([$userId, $postId]);

        $message = "pinned";
    }

    $pdo->commit();

    pinResponse(true, $message);

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status = $e->getMessage() === "limit"
        ? "pin_limit"
        : "pin_error";

    pinResponse(false, $status, $status === "pin_limit" ? 409 : 400);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Profile pin error: " . $e->getMessage());

    pinResponse(false, "pin_error", 500);
}
