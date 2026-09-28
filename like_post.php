<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$post_id = (int) $_POST["post_id"];

$stmt = $conn->prepare("
    SELECT post_id
    FROM post_like
    WHERE user_id = ? AND post_id = ?
");

$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("
        DELETE FROM post_like
        WHERE user_id = ? AND post_id = ?
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        INSERT INTO post_like (user_id, post_id)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
}

header("Location: feed.php");
exit;
?>