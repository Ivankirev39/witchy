<?php

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);

$stmt = $conn->prepare("
    SELECT post_id
    FROM save
    WHERE user_id = ? AND post_id = ?
");

$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt = $conn->prepare("
        DELETE FROM save
        WHERE user_id = ? AND post_id = ?
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();

    $saved = false;

} else {

    $stmt = $conn->prepare("
        INSERT INTO save (user_id, post_id)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();

    $saved = true;
}

if (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) {

    header("Content-Type: application/json");

    echo json_encode([
        "success" => true,
        "saved" => $saved
    ]);

    exit;
}

$redirect = $_POST["redirect"] ?? "feed.php";

if ($redirect === "saved.php") {
    header("Location: saved.php");
} else {
    header("Location: feed.php#post-" . $post_id);
}

exit;