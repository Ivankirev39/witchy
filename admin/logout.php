<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . "/../config/bootstrap.php";
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "admin/index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!csrf_valid($token)) {
    http_response_code(403);
    exit("Invalid security token. Please go back and try again.");
}

$_SESSION = [];

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

session_destroy();

header("Location: " . BASE_URL . "login.php");
exit;
