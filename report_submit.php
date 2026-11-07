<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/models/DBConnector.php";
require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/models/Report.php";

// Only accept POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

header("Content-Type: application/json");

// Helper for JSON responses
function sendReportResponse(bool $success, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);

    exit;
}

// Validate CSRF token
if (!csrf_valid($_POST["csrf_token"] ?? null)) {
    sendReportResponse(false, "Invalid security token. Please refresh the page.", 403);
}

// Get submitted data
$reportType = $_POST["report_type"] ?? "";
$targetId = filter_var(
    $_POST["target_id"] ?? null,
    FILTER_VALIDATE_INT
);

$reason = trim($_POST["reason"] ?? "");
$details = trim($_POST["details"] ?? "");

// Validate target ID
if ($targetId === false || $targetId === null || $targetId < 1) {
    sendReportResponse(false, "Invalid report target.", 400);
}

// Validate report type and reason
$allowedReasons = [
    "user" => [
        "Harassment or bullying",
        "Fake or impersonation account",
        "Spam",
        "Inappropriate profile",
        "Hate speech",
        "Other"
    ],

    "comment" => [
        "Harassment or bullying",
        "Spam",
        "Hate speech",
        "Inappropriate content",
        "Misinformation",
        "Other"
    ]
];

if (
    !isset($allowedReasons[$reportType]) ||
    !in_array($reason, $allowedReasons[$reportType], true)
) {
    sendReportResponse(false, "Please select a valid report reason.", 400);
}

// Validate additional details
if (strlen($details) > 1000) {
    sendReportResponse(false, "Additional details cannot exceed 1000 characters.", 400);
}

// Get authenticated user
$reporterId = (int) $_SESSION["user_id"];

try {

    // Connect to database
    $pdoConnector = new \DataAccess\DB\DBConnector();
    $pdo = $pdoConnector->connectToDB();

    // Initialize report model
    $reportModel = new Report($pdo);

    // Create appropriate report
    if ($reportType === "user") {

        $reportId = $reportModel->createUserReport(
            $reporterId,
            $targetId,
            $reason,
            $details
        );

    } else {

        $reportId = $reportModel->createCommentReport(
            $reporterId,
            $targetId,
            $reason,
            $details
        );
    }

    // Check for duplicate report
    if ($reportId === null) {
        sendReportResponse(
            false,
            "You have already reported this content."
        );
    }

    sendReportResponse(
        true,
        "Your report has been submitted successfully."
    );

} catch (InvalidArgumentException $e) {

    sendReportResponse(false, $e->getMessage(), 400);

} catch (PDOException $e) {

    error_log("Report submission error: " . $e->getMessage());

    sendReportResponse(
        false,
        "Something went wrong while submitting your report.",
        500
    );
}
