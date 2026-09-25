<?php

require_once __DIR__ . "/includes/session.php";
require_once __DIR__ . "/includes/csrf.php";


// User must be logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
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

    <title>Witchy</title>

</head>


<body>

    <h1>
        Welcome,
        <?= htmlspecialchars(
            $_SESSION["username"] ?? "User",
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </h1>


    <p>
        You are logged in.
    </p>


    <p>
        User ID:
        <?= htmlspecialchars(
            (string) $_SESSION["user_id"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </p>


    <p>
        Rank:
        <?= htmlspecialchars(
            (string) ($_SESSION["rank"] ?? "user"),
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </p>


    <form
        action="logout.php"
        method="POST"
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

        <button type="submit">
            Log out
        </button>

    </form>

</body>

</html>