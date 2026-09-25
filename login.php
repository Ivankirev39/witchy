<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";


// Already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


$error = "";
$login = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF protection
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }


    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";


    if ($login === "" || $password === "") {

        $error = "Incorrect username/email or password.";

    } else {

        // Prepared statement prevents SQL injection
        $sql = "
            SELECT
                user_id,
                username,
                password,
                rank
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ss",
            $login,
            $login
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();


        // Verify hashed password
        if (
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {

            // New session ID after successful login
            session_regenerate_id(true);


            $_SESSION["user_id"] =
                $user["user_id"];

            $_SESSION["username"] =
                $user["username"];

            $_SESSION["rank"] =
                $user["rank"];


            header("Location: index.php");
            exit;
        }


        // Generic error prevents account discovery
        $error =
            "Incorrect username/email or password.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | Witchy</title>

</head>


<body>

    <h1>Welcome back</h1>

    <p>Log in to Witchy.</p>


    <?php if (
        isset($_GET["registered"]) &&
        $_GET["registered"] === "1"
    ): ?>

        <p>
            Account created. You can now log in.
        </p>

    <?php endif; ?>


    <?php if ($error): ?>

        <p>
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                csrf_token(),
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
        >


        <label for="login">
            Email or username
        </label>

        <input
            type="text"
            id="login"
            name="login"
            value="<?= htmlspecialchars(
                $login,
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            autocomplete="username"
            required
        >


        <br><br>


        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
        >


        <br><br>


        <button type="submit">
            Log in
        </button>

    </form>


    <p>
        Don't have an account?

        <a href="register.php">
            Register
        </a>
    </p>

</body>

</html>