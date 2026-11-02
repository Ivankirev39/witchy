<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/models/AdminUser.php";

$adminUser = new AdminUser($conn);

$pageTitle = "Review User | Witchy";
$pageCss = "admin_users.css";

$userId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

// Initialize page data
$user = null;
$posts = [];
$comments = [];
$activeBan = null;
$banHistory = [];

$postCount = 0;
$commentCount = 0;
$followerCount = 0;
$followingCount = 0;

$errorMessage = "";
$successMessage = "";

// Messages after redirects
$status = $_GET["status"] ?? "";

if ($status === "banned") {
    $successMessage = "The user has been successfully banned.";
} elseif ($status === "unbanned") {
    $successMessage = "The ban has been successfully revoked.";
}

// Validate selected user
if (!$userId || $userId < 1) {
    $errorMessage = "Invalid user selected.";
} else {
    try {
        // Load user account
        $user = $adminUser->getUserById((int) $userId);

        if (!$user) {
            $errorMessage = "This user could not be found.";
        }

        // Handle moderation actions
        if ($user && $_SERVER["REQUEST_METHOD"] === "POST") {

            if (!csrf_valid($_POST["csrf_token"] ?? "")) {
                throw new RuntimeException(
                    "Invalid security token. Please refresh the page and try again."
                );
            }

            // Prevent moderation of admin accounts and the current administrator
            if (
                $user["rank"] === "admin"
                || (int) $user["user_id"] === (int) $_SESSION["user_id"]
            ) {
                throw new RuntimeException(
                    "Admin accounts cannot be moderated through this page."
                );
            }

            $action = $_POST["action"] ?? "";

            if ($action === "ban") {

                $reason = trim($_POST["reason"] ?? "");
                $expiryInput = trim($_POST["expires_at"] ?? "");

                if ($reason === "") {
                    throw new RuntimeException(
                        "Please provide a reason for the ban."
                    );
                }

                if (strlen($reason) > 1000) {
                    throw new RuntimeException(
                        "The ban reason cannot exceed 1000 characters."
                    );
                }

                $expiresAt = null;

                // Validate optional expiry date
                if ($expiryInput !== "") {

                    $expiryDate = DateTime::createFromFormat(
                        "!Y-m-d\TH:i",
                        $expiryInput
                    );

                    $dateErrors = DateTime::getLastErrors();

                    if (
                        !$expiryDate
                        || (
                            $dateErrors !== false
                            && (
                                $dateErrors["warning_count"] > 0
                                || $dateErrors["error_count"] > 0
                            )
                        )
                        || $expiryDate->format("Y-m-d\TH:i") !== $expiryInput
                    ) {
                        throw new RuntimeException(
                            "Please enter a valid ban expiry date."
                        );
                    }

                    if ($expiryDate->getTimestamp() <= time()) {
                        throw new RuntimeException(
                            "The expiry date must be in the future."
                        );
                    }

                    $expiresAt = $expiryDate->format("Y-m-d H:i:s");
                }

                // Check for an existing active ban
                if ($adminUser->getActiveBan((int) $userId)) {
                    throw new RuntimeException(
                        "This user already has an active ban."
                    );
                }

                // Create ban
                $created = $adminUser->createBan(
                    (int) $userId,
                    (int) $_SESSION["user_id"],
                    $reason,
                    $expiresAt
                );

                if (!$created) {
                    throw new RuntimeException(
                        "The ban could not be created."
                    );
                }

                header(
                    "Location: view_user.php?id=" . $userId . "&status=banned"
                );
                exit;

            } elseif ($action === "unban") {

                $banId = filter_input(
                    INPUT_POST,
                    "ban_id",
                    FILTER_VALIDATE_INT
                );

                $revocationReason = trim(
                    $_POST["revocation_reason"] ?? ""
                );

                if (!$banId || $banId < 1) {
                    throw new RuntimeException("Invalid ban selected.");
                }

                if ($revocationReason === "") {
                    throw new RuntimeException(
                        "Please provide a reason for revoking this ban."
                    );
                }

                if (strlen($revocationReason) > 1000) {
                    throw new RuntimeException(
                        "The revocation reason cannot exceed 1000 characters."
                    );
                }

                // Revoke ban while preserving its history
                $updatedRows = $adminUser->revokeBan(
                    (int) $banId,
                    (int) $userId,
                    (int) $_SESSION["user_id"],
                    $revocationReason
                );

                if ($updatedRows !== 1) {
                    throw new RuntimeException(
                        "This ban could not be revoked."
                    );
                }

                header(
                    "Location: view_user.php?id=" . $userId . "&status=unbanned"
                );
                exit;

            } else {
                throw new RuntimeException("Invalid moderation action.");
            }
        }

        // Load information required by the views
        if ($user) {

            $activityCounts = $adminUser->getActivityCounts((int) $userId);

            $postCount = $activityCounts["posts"];
            $commentCount = $activityCounts["comments"];
            $followerCount = $activityCounts["followers"];
            $followingCount = $activityCounts["following"];

            $posts = $adminUser->getRecentPosts((int) $userId);
            $comments = $adminUser->getRecentComments((int) $userId);
            $activeBan = $adminUser->getActiveBan((int) $userId);
            $banHistory = $adminUser->getBanHistory((int) $userId);
        }

    } catch (RuntimeException $e) {
        $errorMessage = $e->getMessage();

    } catch (PDOException $e) {
        error_log("Admin user review error: " . $e->getMessage());
        $errorMessage = "The user profile could not be loaded. Please try again.";
    }
}

// Load page header
require_once __DIR__ . "/includes/admin_header.php";

?>

<section class="admin-content-page admin-user-review">

    <header class="admin-content-heading">
        <h2>User profile review</h2>
        <p>Review account information and recent community activity.</p>
    </header>

    <p class="admin-user-back-link">
        <a href="/witchy/admin/users.php">&larr; Back to users</a>
    </p>

    <?php if ($successMessage !== ""): ?>
        <div class="admin-content-success" role="status">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
        <div class="admin-content-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>

        <?php require __DIR__ . "/views/user/profile.php"; ?>
        <?php require __DIR__ . "/views/user/activity.php"; ?>
        <?php require __DIR__ . "/views/user/moderation.php"; ?>
        <?php require __DIR__ . "/views/user/ban_history.php"; ?>
        <?php require __DIR__ . "/views/user/recent_posts.php"; ?>
        <?php require __DIR__ . "/views/user/recent_comments.php"; ?>

    <?php endif; ?>

</section>

<script src="js/view_user.js" defer></script>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>