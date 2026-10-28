<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: feed.php");
    exit;
}

if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

$user_id = (int) $_SESSION["user_id"];
$post_id = (int) ($_POST["post_id"] ?? 0);

if ($post_id <= 0) {
    header("Location: feed.php");
    exit;
}

$connector = new \DataAccess\DB\DBConnector();
$pdo = $connector->connectToDB();

$stmt = $pdo->prepare("
    DELETE FROM post
    WHERE post_id = ?
    AND user_id = ?
");

$stmt->execute([$post_id, $user_id]);

header("Location: feed.php");
exit;