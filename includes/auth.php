<?php

require_once __DIR__ . "/session.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../models/User.php";

// Check whether the request expects JSON
function authExpectsJson(): bool
{
    $requestedWith = $_SERVER["HTTP_X_REQUESTED_WITH"] ?? "";
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";

    return strcasecmp($requestedWith, "XMLHttpRequest") === 0
        || str_contains($accept, "application/json");
}

// Handle unauthorized requests
function denyAccess(
    int $status,
    string $message,
    string $redirect
): void {
    if (authExpectsJson()) {
        http_response_code($status);

        header("Content-Type: application/json; charset=UTF-8");
        header("Cache-Control: no-store");

        echo json_encode([
            "success" => false,
            "error" => $message
        ]);

        exit;
    }

    header("Location: " . $redirect);
    exit;
}

// User must be logged in
if (!isset($_SESSION["user_id"])) {
    denyAccess(
        401,
        "Please log in to continue.",
        "login.php"
    );
}

$userId = (int) $_SESSION["user_id"];

try {
    $userModel = new User($conn);

    // Check whether the account still exists
    $userExists = $userModel->getUserById($userId) !== null;

    // Check whether the account is banned
    $isBanned = $userExists
        && $userModel->isBanned($userId);

} catch (PDOException $e) {
    error_log($e->getMessage());

    if (authExpectsJson()) {
        http_response_code(503);
        header("Content-Type: application/json; charset=UTF-8");

        echo json_encode([
            "success" => false,
            "error" => "Service temporarily unavailable."
        ]);

        exit;
    }

    http_response_code(503);
    exit("Service temporarily unavailable.");
}

// Log out deleted or banned accounts
if (!$userExists || $isBanned) {

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

    if ($isBanned) {
        denyAccess(
            403,
            "This account is currently suspended.",
            "login.php?banned=1"
        );
    }

    denyAccess(
        401,
        "Your session has expired. Please log in again.",
        "login.php"
    );
}
