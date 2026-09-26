<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $_SESSION["register_form_started"] = time();
}

$error = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valid($_POST["csrf_token"] ?? null)) {
        http_response_code(403);
        exit("Invalid request.");
    }

    $website = trim($_POST["website"] ?? "");

    if ($website !== "") {
        http_response_code(400);
        exit("Invalid request.");
    }

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $formStarted =
        $_SESSION["register_form_started"] ?? 0;

    $secondsTaken =
        time() - $formStarted;


    if (
        $formStarted === 0 ||
        $secondsTaken < 3
    ) {

        $error =
            "Please wait a moment before creating your account, then try again.";

        $_SESSION["register_form_started"] =
            time();

    } elseif (
        $username === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {

        $error =
            "Please fill in all fields.";

    } elseif (
        !preg_match(
            '/^[A-Za-z0-9_]{3,50}$/',
            $username
        )
    ) {

        $error =
            "Username must be 3-50 characters and only contain letters, numbers or underscores.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email.";

    } elseif (strlen($email) > 100) {

        $error =
            "Email is too long.";

    } elseif (strlen($password) < 8) {

        $error =
            "Password must be at least 8 characters.";

    } elseif (strlen($password) > 255) {

        $error =
            "Password is too long.";

    } elseif ($password !== $confirmPassword) {

        $error =
            "Passwords do not match.";

    } else {

        $sql = "
            SELECT user_id
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ";

        $stmt =
            $conn->prepare($sql);

        if (!$stmt) {

            error_log($conn->error);

            $error =
                "Something went wrong. Please try again.";

        } else {

            $stmt->bind_param(
                "ss",
                $username,
                $email
            );

            $stmt->execute();

            $result =
                $stmt->get_result();

            if ($result->num_rows > 0) {

                $error =
                    "Username or email is already in use.";

            } else {

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                if ($hashedPassword === false) {

                    $error =
                        "Something went wrong. Please try again.";

                } else {

                    $rank = "user";

                    $sql = "
                        INSERT INTO users
                            (
                                username,
                                email,
                                password,
                                rank
                            )
                        VALUES
                            (?, ?, ?, ?)
                    ";

                    $stmt =
                        $conn->prepare($sql);

                    if (!$stmt) {

                        error_log($conn->error);

                        $error =
                            "Something went wrong. Please try again.";

                    } else {

                        $stmt->bind_param(
                            "ssss",
                            $username,
                            $email,
                            $hashedPassword,
                            $rank
                        );

                        if ($stmt->execute()) {

                            unset(
                                $_SESSION[
                                    "register_form_started"
                                ]
                            );

                            header(
                                "Location: login.php?registered=1"
                            );

                            exit;

                        } else {

                            error_log(
                                $stmt->error
                            );

                            $error =
                                "Something went wrong. Please try again.";
                        }
                    }
                }
            }
        }
    }
}

$pageTitle = "Register | Witchy";

require_once __DIR__
    . "/includes/public-header.php";
?>

<main class="auth-page">

    <section class="auth-brand">
        <div class="brand-content">

            <div
                class="brand-symbol"
                aria-hidden="true"
            >
                ☾
            </div>

            <h1>
                Find your place in Witchy
            </h1>

            <p>
                Discover ideas, save inspiration,
                share what you know, and explore
                with a community built around curiosity.
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


            <?php if ($error !== ""): ?>

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


            <form
                method="POST"
                action="register.php"
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


                <div
                    class="honeypot"
                    aria-hidden="true"
                >

                    <label for="website">
                        Website
                    </label>

                    <input
                        type="text"
                        id="website"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                    >

                </div>


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

                    <div class="password-field">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
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


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm password
                    </label>

                    <div class="password-field">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Enter your password again"
                            minlength="8"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password="confirm_password"
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

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>