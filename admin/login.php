<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../models/LoginRateLimiter.php";

if (
    isset($_SESSION["user_id"]) &&
    ($_SESSION["rank"] ?? "") === "admin"
) {
    header("Location: index.php");
    exit;
}

$error = "";
$login = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $rawLogin = $_POST["login"] ?? "";
    $password = $_POST["password"] ?? "";

    if (!is_string($rawLogin) || !is_string($password)) {
        $error = "Invalid login request.";
    } else {
        $login = trim($rawLogin);

        if (
            $login === "" ||
            $password === "" ||
            strlen($login) > 100 ||
            strlen($password) > 255
        ) {
            $error = "Incorrect username/email or password.";
        } else {
            try {
                $userModel = new User($conn);
                $limiter = new LoginRateLimiter($conn);
                $ip = $_SERVER["REMOTE_ADDR"] ?? "";

                if ($limiter->isLimited($login, $ip)) {
                    $error = "Too many login attempts. Please try again in 15 minutes.";
                } else {
                    $user = $userModel->findByLogin($login);

                    if (
                        !$user ||
                        !password_verify($password, $user["password"])
                    ) {
                        $limiter->recordFailure($login, $ip);
                        $error = "Incorrect username/email or password.";
                    } elseif (($user["rank"] ?? "") !== "admin") {
                        $limiter->recordFailure($login, $ip);
                        $error = "Incorrect username/email or password.";
                    } elseif ($userModel->isBanned((int) $user["user_id"])) {
                        $error = "This account is currently suspended.";
                    } else {
                        $limiter->clearAttempts($login, $ip);

                        session_regenerate_id(true);

                        $_SESSION["user_id"] = (int) $user["user_id"];
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["rank"] = $user["rank"];

                        header("Location: index.php");
                        exit;
                    }
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Witchy</title>
    <link rel="stylesheet" href="/witchy/admin/css/admin.css">
</head>
<body class="admin-login-page">
    <main class="admin-login-container">
        <h1>Witchy Admin</h1>
        <p>Sign in with your administrator account.</p>

        <?php if ($error !== ""): ?>
            <p class="admin-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    csrf_token(),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <label for="login">Email or username</label>
            <input
                type="text"
                id="login"
                name="login"
                maxlength="100"
                autocomplete="username"
                value="<?= htmlspecialchars($login, ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                maxlength="255"
                autocomplete="current-password"
                required
            >

            <button type="submit">Log in</button>
        </form>

        <p><a href="../index.php">Back to Witchy</a></p>
    </main>
</body>
</html>
