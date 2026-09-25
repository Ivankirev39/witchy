<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";


// If already logged in, go to the homepage.
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


$error = "";
$login = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =========================
    // CSRF PROTECTION
    // =========================

    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }


    // =========================
    // GET FORM VALUES
    // =========================

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";


    // =========================
    // VALIDATION
    // =========================

    if ($login === "" || $password === "") {

        // Generic message prevents account discovery.
        $error = "Incorrect username/email or password.";

    } else {

        // =========================
        // FIND USER
        // =========================

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


        // Prepared statement prevents SQL injection.
        $stmt = $conn->prepare($sql);


        if (!$stmt) {

            // Real database error goes into the server log.
            error_log($conn->error);

            // Visitor receives only a generic message.
            $error = "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param(
                "ss",
                $login,
                $login
            );


            $stmt->execute();


            $result = $stmt->get_result();

            $user = $result->fetch_assoc();


            // =========================
            // VERIFY PASSWORD
            // =========================

            if (
                $user &&
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                // Prevent session fixation.
                session_regenerate_id(true);


                // Store only the information we need.
                $_SESSION["user_id"] =
                    $user["user_id"];

                $_SESSION["username"] =
                    $user["username"];

                $_SESSION["rank"] =
                    $user["rank"];


                header("Location: index.php");
                exit;
            }


            // Same error whether username/email or
            // password was incorrect.
            $error = "Incorrect username/email or password.";
        }
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

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


    <main class="auth-page">


        <!-- =========================
             BRAND SIDE
             ========================= -->

        <section class="auth-brand">


            <div class="brand-logo">
                ☾ Witchy
            </div>


            <div class="brand-content">


                <div class="brand-symbol">
                    ☾
                </div>


                <h1>
                    Welcome back to Witchy
                </h1>


                <p>
                    Explore ideas, practices and perspectives from across the Witchy community.
                </p>


            </div>


            <div class="brand-footer">
                Share. Discover. Learn. Belong.
            </div>


        </section>



        <!-- =========================
             LOGIN SIDE
             ========================= -->

        <section class="auth-main">


            <!-- DARK / LIGHT MODE -->

            <button
                type="button"
                class="theme-toggle"
                id="theme-toggle"
                aria-label="Switch to dark mode"
                title="Switch theme"
            >
                ☾
            </button>



            <div class="auth-container">


                <header class="auth-header">


                    <h2>
                        Welcome back
                    </h2>


                    <p>
                        Log in to continue exploring Witchy.
                    </p>


                </header>



                <!-- =========================
                     REGISTRATION SUCCESS
                     ========================= -->

                <?php if (
                    isset($_GET["registered"]) &&
                    $_GET["registered"] === "1"
                ): ?>

                    <div
                        class="auth-message auth-success"
                        role="status"
                    >
                        Account created. You can now log in.
                    </div>

                <?php endif; ?>



                <!-- =========================
                     LOGIN ERROR
                     ========================= -->

                <?php if ($error): ?>

                    <div
                        class="auth-message auth-error"
                        role="alert"
                    >

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </div>

                <?php endif; ?>



                <!-- =========================
                     LOGIN FORM
                     ========================= -->

                <form
                    method="POST"
                    class="auth-form"
                >


                    <!-- CSRF TOKEN -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            csrf_token(),
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                    >



                    <!-- EMAIL / USERNAME -->

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

                            maxlength="100"

                            autocomplete="username"

                            required
                        >


                    </div>



                    <!-- PASSWORD -->

                    <div class="form-group">


                        <label for="password">
                            Password
                        </label>


                        <div class="password-field">


                            <input
                                type="password"
                                id="password"
                                name="password"

                                placeholder="Enter your password"

                                maxlength="255"

                                autocomplete="current-password"

                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                data-password="password"
                                aria-label="Show password"
                                title="Show password"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                    ></path>

                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="3"
                                    ></circle>
                                </svg>

                            </button>


                        </div>


                    </div>



                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="auth-button"
                    >
                        Log in
                    </button>


                </form>



                <!-- REGISTER LINK -->

                <p class="auth-switch">

                    New to Witchy?

                    <a href="register.php">
                        Create an account
                    </a>

                </p>


            </div>


        </section>


    </main>


    <!--
        IMPORTANT:
        JavaScript is loaded down here so the HTML above
        already exists when JavaScript searches for it.
    -->

    <script src="assets/js/auth.js"></script>


</body>

</html>