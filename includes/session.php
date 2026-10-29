<?php

if (session_status() === PHP_SESSION_NONE) {
    // Reject uninitialized session IDs
    ini_set("session.use_strict_mode", "1");

    $https = !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off";

    session_set_cookie_params([
        "httponly" => true,
        "secure" => $https,
        "samesite" => "Lax"
    ]);

    session_start();
}