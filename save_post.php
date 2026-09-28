<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$post_id = (int) $_POST["post_id"];


/*
    First check whether this user
    has already saved this post.
*/

$stmt = $conn->prepare("
    SELECT post_id
    FROM save
    WHERE user_id = ? AND post_id = ?
");

$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    // Already saved → UNSAVE it

    $stmt = $conn->prepare("
        DELETE FROM save
        WHERE user_id = ? AND post_id = ?
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();

} else {

    // Not saved yet → SAVE it

    $stmt = $conn->prepare("
        INSERT INTO save (user_id, post_id)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
}


header("Location: feed.php");
exit;