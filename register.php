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

    <title>Register | Witchy</title>

</head>


<body>

    <h1>Join Witchy</h1>

    <p>Create your account.</p>


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
            minlength="3"
            maxlength="50"
            required
        >


        <br><br>


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
            maxlength="100"
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
            minlength="8"
            autocomplete="new-password"
            required
        >


        <br><br>


        <label for="confirm_password">
            Confirm password
        </label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            autocomplete="new-password"
            required
        >


        <br><br>


        <button type="submit">
            Create account
        </button>

    </form>


    <p>
        Already have an account?

        <a href="login.php">
            Log in
        </a>
    </p>

</body>

</html>