<?php

if (session_status() === PHP_SESSION_NONE) {

    $https = !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off";

    session_set_cookie_params([
        "httponly" => true,
        "secure" => $https,
        "samesite" => "Lax"
    ]);

    session_start();
}