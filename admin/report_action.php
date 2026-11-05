<?php

require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/models/AdminReport.php";

// Only accept POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

// Validate CSRF token.
if (!csrf_valid($_POST["csrf_token"] ?? "")) {
    http_response_code(403);
    exit("Invalid security token. Please refresh the page and try again.");
}

// Validate report ID.
$reportId = filter_var(
    $_POST["report_id"] ?? null,
    FILTER_VALIDATE_INT
);

if (!$reportId || $reportId < 1) {
    http_response_code(400);
    exit("Invalid report ID.");
}

// Validate requested action.
$action = $_POST["action"] ?? "";

$statusActions = [
    "review" => "reviewed",
    "dismiss" => "dismissed"
];

if (!isset($statusActions[$action])) {
    http_response_code(400);
    exit("Invalid moderation action.");
}

try {

    $reportModel = new AdminReport($conn);

    $updated = $reportModel->updateReportStatus(
        (int) $reportId,
        $statusActions[$action],
        (int) $_SESSION["user_id"]
    );

    if (!$updated) {
        header(
            "Location: " . BASE_URL
            . "admin/view_report.php?id=" . $reportId
            . "&action_error=already_processed"
        );
        exit;
    }

    header(
        "Location: " . BASE_URL
        . "admin/view_report.php?id=" . $reportId
        . "&action_success=" . $action
    );
    exit;

} catch (Throwable $e) {

    error_log("Admin report action error: " . $e->getMessage());

    header(
        "Location: " . BASE_URL
        . "admin/view_report.php?id=" . $reportId
        . "&action_error=server"
    );
    exit;
}