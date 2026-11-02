<?php

require_once __DIR__ . "/../../config/bootstrap.php";
require_once __DIR__ . "/../../includes/session.php";
require_once __DIR__ . "/../../config/db.php";

// Require a valid logged-in user.
$userId = filter_var(
    $_SESSION["user_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {
    header("Location: " . BASE_URL . "admin/login.php");
    exit;
}

try {
    // Get the user's current role and ban status
    // directly from the database.
    $stmt = $conn->prepare("
        SELECT
            u.username,
            u.`rank`,

            EXISTS (
                SELECT 1
                FROM ban b
                WHERE b.user_id = u.user_id
                  AND b.revoked_at IS NULL
                  AND b.banned_at <= NOW()
                  AND (
                      b.expires_at IS NULL
                      OR b.expires_at > NOW()
                  )
            ) AS is_banned

        FROM users u
        WHERE u.user_id = ?
        LIMIT 1
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Admin authorization error: " . $e->getMessage());

    http_response_code(503);
    exit("Service temporarily unavailable.");
}

// If the account was deleted or banned,
// immediately invalidate its session.
if (!$user || (int) $user["is_banned"] === 1) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => $params["path"],
            "domain" => $params["domain"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"] ?? "Lax"
        ]);
    }

    session_destroy();

    header("Location: " . BASE_URL . "admin/login.php");
    exit;
}

// Synchronize the session with the database.
$_SESSION["rank"] = $user["rank"];
$_SESSION["username"] = $user["username"];

// Only current administrators may access admin pages.
if ($user["rank"] !== "admin") {
    header("Location: " . BASE_URL . "feed.php");
    exit;
}

// Authorization passed.
// Continue loading the requested admin page.
