<?php
require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/config/db.php";

if (isset($_SESSION["user_id"])) {
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

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login === "" || $password === "") {
        $error = "Incorrect username/email or password.";
    } else {
        $sql = "
            SELECT user_id, username, password, rank
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log($conn->error);
            $error = "Something went wrong. Please try again.";
        } else {
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (
                $user &&
                password_verify($password, $user["password"])
            ) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["rank"] = $user["rank"];

                header("Location: index.php");
                exit;
            }

            $error = "Incorrect username/email or password.";
        }
    }
}

$pageTitle = "Log in | Witchy";
require_once __DIR__ . "/includes/public-header.php";
?>

<main class="auth-page">

    <section class="auth-brand">
        <div class="brand-content">

            <div
                class="brand-symbol"
                aria-hidden="true"
            >
                ✦
            </div>

            <h1>There’s more to discover</h1>

            <p>
                Explore ideas, practices and perspectives
                from across the Witchy community.
            </p>

        </div>

        <p class="brand-footer">
            Share. Discover. Learn. Belong.
        </p>
    </section>


    <section class="auth-main">
        <div class="auth-container">

            <header class="auth-header">
                <h2>Welcome back</h2>
                <p>Log in to continue exploring.</p>
            </header>


            <?php if (
                isset($_GET["registered"]) &&
                $_GET["registered"] === "1"
            ): ?>

                <div
                    class="auth-message auth-success"
                    role="status"
                >
                    Account created. Welcome to Witchy.
                    You can now log in.
                </div>

            <?php endif; ?>


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
                action="login.php"
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
                        maxlength="100"
                        autocomplete="username"
                        placeholder="Enter your email or username"
                        value="<?= htmlspecialchars(
                            $login,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
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
                            maxlength="255"
                            autocomplete="current-password"
                            placeholder="Enter your password"
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

<?php
require_once __DIR__ . "/includes/public-footer.php";
?>