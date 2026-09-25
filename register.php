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
$username = "";
$email = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // CSRF protection
    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }


    // Get form values
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    // Server-side validation
    if (
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error = "Please fill in all fields.";

    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {

        $error = "Username must be 3-50 characters and only contain letters, numbers or underscores.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email.";

    } elseif (strlen($email) > 100) {

        $error = "Email is too long.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        // Check if username or email already exists
        $sql = "
            SELECT user_id
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows > 0) {

            $error = "Username or email is already in use.";

        } else {

            // Never store the real password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // Rank is controlled by PHP, not the user
            $rank = "user";


            $sql = "
                INSERT INTO users
                    (username, email, password, rank)
                VALUES
                    (?, ?, ?, ?)
            ";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssss",
                $username,
                $email,
                $hashedPassword,
                $rank
            );


            if ($stmt->execute()) {

                header("Location: login.php?registered=1");
                exit;

            } else {

                error_log($stmt->error);

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link rel="stylesheet" href="assets/css/style.css">

    <title>Register | Witchy</title>

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
                    Find your place in Witchy
                </h1>

                <p>
                    Discover ideas, save inspiration,
                    share your practice and connect with
                    a community built for learning and exploration.
                </p>

            </div>


            <div class="brand-footer">
                Share. Discover. Learn. Belong.
            </div>

        </section>


        <section class="auth-main">

            <div class="auth-container">

                <header class="auth-header">

                    <h2>Join Witchy</h2>

                    <p>
                        Create your account and start exploring.
                    </p>

                </header>


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

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars(
                                $username,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            placeholder="Choose a username"
                            minlength="3"
                            maxlength="50"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars(
                                $email,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            placeholder="Enter your email"
                            maxlength="100"
                            autocomplete="email"
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
                            placeholder="At least 8 characters"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Enter your password again"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="auth-button"
                    >
                        Create account
                    </button>

                </form>


                <p class="auth-switch">

                    Already have an account?

                    <a href="login.php">
                        Log in
                    </a>

                </p>

            </div>

        </section>

    </main>

</body>

</html>