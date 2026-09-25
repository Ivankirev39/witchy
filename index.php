<?php

require_once __DIR__ . "/includes/session.php";


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
            $_SESSION["username"],
            ENT_QUOTES,
            "UTF-8"
        ) ?>
    </h1>

    <a href="logout.php">
        Log out
    </a>

</body>

</html>