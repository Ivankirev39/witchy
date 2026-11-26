<?php

require_once __DIR__ . "/session.php";

/**
 * Store a message to display after a redirect.
 */
function flash_set(string $type, string $message): void
{
    $allowedTypes = ["success", "error", "info"];

    if (!in_array($type, $allowedTypes, true)) {
        return;
    }

    $_SESSION["flash_message"] = [
        "type" => $type,
        "message" => $message
    ];
}

/**
 * Retrieve and remove the stored flash message.
 */
function flash_get(): ?array
{
    if (!isset($_SESSION["flash_message"])) {
        return null;
    }

    $flash = $_SESSION["flash_message"];

    // Remove the message so it only appears once.
    unset($_SESSION["flash_message"]);

    return is_array($flash) ? $flash : null;
}
