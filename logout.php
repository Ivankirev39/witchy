<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";


// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}


// Check CSRF token
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid request.");
}


// Clear session data
$_SESSION = [];


// Delete session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// Destroy session
session_destroy();


header("Location: login.php");
exit;