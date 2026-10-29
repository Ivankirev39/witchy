
<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/models/User.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid request.");
}

$password = $_POST["password"] ?? "";
$confirmed = isset($_POST["confirm_delete"]);

if (!is_string($password) || !$confirmed || $password === "") {
    $_SESSION["delete_error"] = "Please confirm the deletion and enter your password.";
    header("Location: settings.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$userModel = new User($conn);

try {
    $files = $userModel->deleteAccount($userId, $password);

    if ($files === null) {
        $_SESSION["delete_error"] = "Incorrect password.";
        header("Location: settings.php");
        exit;
    }

    // Remove files from the relevant upload folders
    $folders = [
        "profiles" => __DIR__ . "/uploads/profiles/",
        "posts" => __DIR__ . "/uploads/posts/",
        "comments" => __DIR__ . "/uploads/comments/"
    ];

    foreach ($files as $type => $fileList) {
        foreach ($fileList as $file) {
            if (!is_string($file) || $file === "") {
                continue;
            }

            // Only remove files inside the expected upload folder
            $filename = basename($file);

            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
                continue;
            }

            $path = $folders[$type] . $filename;

            if (is_file($path) && !is_link($path)) {
                if (!unlink($path)) {
                    error_log("Could not remove uploaded file: " . $path);
                }
            }
        }
    }

    // Log out the deleted account
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

        // Start a new anonymous session for the goodbye message
        session_start();
        session_regenerate_id(true);

        $_SESSION["account_deleted_message"] = true;

        header("Location: account_deleted.php");
        exit;


} catch (Throwable $e) {
    error_log($e->getMessage());

    $_SESSION["delete_error"] = "Something went wrong. Please try again.";
    header("Location: settings.php");
    exit;
}
