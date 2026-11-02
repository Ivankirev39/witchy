<?php

require_once __DIR__ . "/../../config/bootstrap.php";
require_once __DIR__ . "/../../includes/session.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["rank"]) ||
    $_SESSION["rank"] !== "admin"
) {
    header("Location: " . BASE_URL . "admin/login.php");
    exit;
}