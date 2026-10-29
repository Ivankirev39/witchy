<?php
require_once __DIR__ . "/../../includes/session.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["rank"] ?? "") !== "admin"
) {
    header("Location: /witchy/admin/login.php");
    exit;
}
