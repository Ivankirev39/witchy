<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);
$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();

$stmt = $pdo->prepare("
    SELECT post_id
    FROM save
    WHERE user_id = ? AND post_id = ?
");
$stmt->execute([$user_id, $post_id]);

if ($stmt->fetchColumn() !== false) {
    $stmt = $pdo->prepare("
        DELETE FROM save
        WHERE user_id = ? AND post_id = ?
    ");
    $stmt->execute([$user_id, $post_id]);

    $saved = false;
} else {
    $stmt = $pdo->prepare("
        INSERT INTO save (user_id, post_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $post_id]);

    $saved = true;
}

if (
    isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
) {
    header("Content-Type: application/json; charset=UTF-8");

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