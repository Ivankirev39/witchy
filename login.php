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
    <link rel="stylesheet" href="assets/css/style.css">

    <title>Login | Witchy</title>

</head>


<body>

    <main class="auth-page">

        <section class="auth-brand">

            <div class="brand-logo">
                ☾ Witchy
            </div>


            <div class="brand-content">

                <div class="brand-symbol">
                    ☾
                </div>

                <h1>
                    A home for modern witches
                </h1>

                <p>
                    Share. Discover. Learn. Belong.
                    Explore practices, ideas and inspiration
                    in a community built around curiosity
                    and connection.
                </p>

            </div>


            <div class="brand-footer">
                Good energy. Great company.
            </div>

        </section>


        <section class="auth-main">

            <div class="auth-container">

                <header class="auth-header">

                    <h2>Welcome back</h2>

                    <p>
                        Log in to continue exploring Witchy.
                    </p>

                </header>


                <?php if (
                    isset($_GET["registered"]) &&
                    $_GET["registered"] === "1"
                ): ?>

                    <div class="auth-message auth-success">
                        Account created. You can now log in.
                    </div>

                <?php endif; ?>


                <?php if ($error): ?>

                    <div class="auth-message auth-error">

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    class="auth-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            csrf_token(),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >


                    <div class="form-group">

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
                            placeholder="Enter your email or username"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="auth-button"
                    >
                        Log in
                    </button>

                </form>


                <p class="auth-switch">

                    New to Witchy?

                    <a href="register.php">
                        Create an account
                    </a>

                </p>

            </div>

        </section>

    </main>

</body>

</html>