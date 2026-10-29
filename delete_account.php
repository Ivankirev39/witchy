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

    
    // Attempt to delete uploaded files after the database commit.
    // Failed deletions remain in the queue for a later retry.
    foreach ($files as $type => $fileList) {
        if (!isset($folders[$type])) {
            continue;
        }
        foreach ($fileList as $file) {

            if (!is_string($file) || $file === "") {
                continue;
            }

            $filename = basename($file);

            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $filename)) {
                continue;
            }

            $path = $folders[$type] . $filename;

            // Missing files count as successfully cleaned up.
            // Never follow or remove symbolic links.
            $removed = false;

            if (!file_exists($path) && !is_link($path)) {
                $removed = true;
            } elseif (is_file($path) && !is_link($path)) {
                $removed = @unlink($path);
            }

            try {
                if ($removed) {

                    // File is gone: remove it from the queue.
                    $stmt = $conn->prepare("
                        DELETE FROM file_deletion_queue
                        WHERE folder = ? AND filename = ?
                    ");

                    $stmt->execute([$type, $filename]);

                } else {

                    // File remains: record the failed attempt.
                    $stmt = $conn->prepare("
                        UPDATE file_deletion_queue
                        SET attempts = attempts + 1,
                            last_error = ?
                        WHERE folder = ? AND filename = ?
                    ");

                    $stmt->execute([
                        "Could not remove uploaded file.",
                        $type,
                        $filename
                    ]);

                    error_log(
                        "File deletion queued for retry: " . $path
                    );
                }

            } catch (Throwable $cleanupError) {

                // The account is already deleted.
                // Do not interrupt logout because cleanup failed.
                error_log(
                    "File cleanup database error: " .
                    $cleanupError->getMessage()
                );
            }
        }
    }



    // Clear all authentication and session data
    $_SESSION = [];

    // Invalidate the old session ID and create a fresh one
    if (!session_regenerate_id(true)) {
        error_log("Failed to regenerate session after account deletion.");

        session_destroy();
        header("Location: account_deleted.php");
        exit;
    }

    // Show the account deletion confirmation
    $_SESSION["account_deleted_message"] = true;

    header("Location: account_deleted.php");
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());

    $_SESSION["delete_error"] = "Something went wrong. Please try again.";
    header("Location: settings.php");
    exit;
}
