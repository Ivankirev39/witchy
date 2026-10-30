<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

$pageTitle = "Review User | Witchy";
$pageCss = "admin_users.css";

$userId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$user = null;
$posts = [];
$comments = [];
$activeBan = null;
$banHistory = [];
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
        $stmt = $conn->prepare("
            SELECT
                user_id,
                username,
                email,
                birthdate,
                rank,
                bio,
                profile_image,
                cover_image
            FROM users
            WHERE user_id = :user_id
            LIMIT 1
        ");

        $stmt->execute([":user_id" => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

            // Never allow admins to be banned or moderated through this page
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
                        || ($dateErrors !== false &&
                            (
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

                // Check whether the user already has an active ban
                $stmt = $conn->prepare("
                    SELECT ban_id
                    FROM ban
                    WHERE user_id = :user_id
                      AND revoked_at IS NULL
                      AND banned_at <= NOW()
                      AND (
                          expires_at IS NULL
                          OR expires_at > NOW()
                      )
                    LIMIT 1
                ");

                $stmt->execute([":user_id" => $userId]);

                if ($stmt->fetch()) {
                    throw new RuntimeException(
                        "This user already has an active ban."
                    );
                }

                // Create ban and record the administrator
                $stmt = $conn->prepare("
                    INSERT INTO ban (
                        user_id,
                        banned_by,
                        reason,
                        banned_at,
                        expires_at
                    )
                    VALUES (
                        :user_id,
                        :banned_by,
                        :reason,
                        NOW(),
                        :expires_at
                    )
                ");

                $stmt->execute([
                    ":user_id" => $userId,
                    ":banned_by" => $_SESSION["user_id"],
                    ":reason" => $reason,
                    ":expires_at" => $expiresAt
                ]);

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

                // Revoke the ban without deleting its history
                $stmt = $conn->prepare("
                    UPDATE ban
                    SET
                        revoked_at = NOW(),
                        revoked_by = :revoked_by,
                        revocation_reason = :revocation_reason
                    WHERE ban_id = :ban_id
                      AND user_id = :user_id
                      AND revoked_at IS NULL
                      AND banned_at <= NOW()
                      AND (
                          expires_at IS NULL
                          OR expires_at > NOW()
                      )
                ");

                $stmt->execute([
                    ":revoked_by" => $_SESSION["user_id"],
                    ":revocation_reason" => $revocationReason,
                    ":ban_id" => $banId,
                    ":user_id" => $userId
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        "The active ban could not be revoked."
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

        if ($user) {

            // Load recent posts
            $stmt = $conn->prepare("
                SELECT post_id, title, topic, created_at
                FROM post
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 10
            ");

            $stmt->execute([":user_id" => $userId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Load recent comments
            $stmt = $conn->prepare("
                SELECT
                    c.comment_id,
                    c.comment,
                    c.created_at,
                    c.post_id,
                    p.title AS post_title
                FROM comment c
                LEFT JOIN post p ON c.post_id = p.post_id
                WHERE c.user_id = :user_id
                ORDER BY c.created_at DESC
                LIMIT 10
            ");

            $stmt->execute([":user_id" => $userId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check for an active ban
            $stmt = $conn->prepare("
                SELECT
                    b.ban_id,
                    b.reason,
                    b.banned_at,
                    b.expires_at,
                    b.banned_by,
                    admin.username AS banned_by_username
                FROM ban b
                LEFT JOIN users admin
                    ON b.banned_by = admin.user_id
                WHERE b.user_id = :user_id
                  AND b.revoked_at IS NULL
                  AND b.banned_at <= NOW()
                  AND (
                      b.expires_at IS NULL
                      OR b.expires_at > NOW()
                  )
                ORDER BY b.banned_at DESC
                LIMIT 1
            ");

            $stmt->execute([":user_id" => $userId]);
            $activeBan = $stmt->fetch(PDO::FETCH_ASSOC);

            // Load complete moderation history
            $stmt = $conn->prepare("
                SELECT
                    b.ban_id,
                    b.reason,
                    b.banned_at,
                    b.expires_at,
                    b.banned_by,
                    issuing_admin.username AS banned_by_username,
                    b.revoked_at,
                    b.revoked_by,
                    revoking_admin.username AS revoked_by_username,
                    b.revocation_reason
                FROM ban b
                LEFT JOIN users issuing_admin
                    ON b.banned_by = issuing_admin.user_id
                LEFT JOIN users revoking_admin
                    ON b.revoked_by = revoking_admin.user_id
                WHERE b.user_id = :user_id
                ORDER BY b.banned_at DESC
            ");

            $stmt->execute([":user_id" => $userId]);
            $banHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (RuntimeException $e) {
        $errorMessage = $e->getMessage();

    } catch (PDOException $e) {
        error_log("Admin user review error: " . $e->getMessage());
        $errorMessage = "The user profile could not be loaded. Please try again.";
    }
}

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

        <!-- Profile overview -->
        <section class="admin-content-panel admin-user-profile-card">

            <div class="admin-user-cover">
                <?php if (!empty($user["cover_image"])): ?>
                    <img
                        src="/witchy/uploads/profiles/<?= rawurlencode(basename($user["cover_image"])) ?>"
                        alt="User cover image"
                    >
                <?php else: ?>
                    <div class="admin-user-cover-placeholder"></div>
                <?php endif; ?>
            </div>

            <div class="admin-user-profile-body">

                <div class="admin-user-identity">

                    <div class="admin-user-avatar">
                        <?php if (!empty($user["profile_image"])): ?>
                            <img
                                src="/witchy/uploads/profiles/<?= rawurlencode(basename($user["profile_image"])) ?>"
                                alt="Profile picture of <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                            >
                        <?php else: ?>
                            <span>
                                <?= htmlspecialchars(strtoupper(substr($user["username"], 0, 1)), ENT_QUOTES, "UTF-8") ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="admin-user-heading">
                        <h3>
                            <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>
                        </h3>

                        <span class="admin-user-role <?= $user["rank"] === "admin" ? "admin-user-role-admin" : "" ?>">
                            <?= htmlspecialchars(ucfirst($user["rank"]), ENT_QUOTES, "UTF-8") ?>
                        </span>

                        <span class="admin-user-id">
                            User ID: <?= (int) $user["user_id"] ?>
                        </span>
                    </div>

                </div>

                <div class="admin-user-details">

                    <div class="admin-user-detail">
                        <span class="admin-user-detail-label">Email address</span>
                        <span class="admin-user-detail-value">
                            <?= htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="admin-user-detail">
                        <span class="admin-user-detail-label">Birthdate</span>
                        <span class="admin-user-detail-value">
                            <?= htmlspecialchars($user["birthdate"] ?: "Not provided", ENT_QUOTES, "UTF-8") ?>
                        </span>
                    </div>

                    <div class="admin-user-detail admin-user-bio">
                        <span class="admin-user-detail-label">About this user</span>
                        <div class="admin-user-detail-value">
                            <?= nl2br(htmlspecialchars($user["bio"] ?: "This user has not added a bio.", ENT_QUOTES, "UTF-8")) ?>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- Account status and moderation -->
        <section class="admin-content-panel admin-user-status-panel">

            <header class="admin-content-panel-header">
                <h3>Account status</h3>
                <p>Current moderation status for this account.</p>
            </header>

            <div class="admin-user-status-content">

                <?php if ($activeBan): ?>

                    <div class="admin-user-status admin-user-status-banned">
                        <span class="admin-user-status-indicator"></span>
                        <strong>Currently banned</strong>
                    </div>

                    <div class="admin-user-ban-details">

                        <p>
                            <strong>Reason:</strong>
                            <?= htmlspecialchars($activeBan["reason"], ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <p>
                            <strong>Banned at:</strong>
                            <?= htmlspecialchars($activeBan["banned_at"], ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <p>
                            <strong>Expires:</strong>
                            <?= htmlspecialchars($activeBan["expires_at"] ?? "No expiry (permanent)", ENT_QUOTES, "UTF-8") ?>
                        </p>

                        <p>
                            <strong>Issued by:</strong>
                            <?= htmlspecialchars($activeBan["banned_by_username"] ?? "Unknown / account deleted", ENT_QUOTES, "UTF-8") ?>
                        </p>

                    </div>

                    <div class="admin-ban-create">

                        <h4>Revoke this ban</h4>
                        <p>Provide a reason for lifting the ban. The original ban record will be preserved.</p>

                        <form
                            method="POST"
                            action="view_user.php?id=<?= (int) $userId ?>"
                            onsubmit="return confirm('Are you sure you want to revoke this ban?');"
                            class="admin-ban-form"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                            >

                            <input type="hidden" name="action" value="unban">
                            <input type="hidden" name="ban_id" value="<?= (int) $activeBan["ban_id"] ?>">

                            <div class="admin-ban-field">
                                <label for="revocation-reason">Reason for revoking ban</label>
                                <textarea
                                    id="revocation-reason"
                                    name="revocation_reason"
                                    rows="3"
                                    maxlength="1000"
                                    required
                                    placeholder="Explain why this ban is being lifted..."
                                ></textarea>
                            </div>

                            <button type="submit" class="admin-button-secondary">
                                Revoke ban
                            </button>

                        </form>

                    </div>

                <?php else: ?>

                    <div class="admin-user-status admin-user-status-active">
                        <span class="admin-user-status-indicator"></span>
                        <strong>Active account</strong>
                    </div>

                    <p class="admin-user-status-note">
                        No active ban found for this account.
                    </p>

                    <?php if ($user["rank"] !== "admin" && (int) $user["user_id"] !== (int) $_SESSION["user_id"]): ?>

                        <div class="admin-ban-create">

                            <h4>Ban this user</h4>
                            <p>Provide a reason and choose whether the ban should be temporary or permanent.</p>

                            <form
                                method="POST"
                                action="view_user.php?id=<?= (int) $userId ?>"
                                class="admin-ban-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8") ?>"
                                >

                                <input type="hidden" name="action" value="ban">

                                <div class="admin-ban-field">
                                    <label for="ban-reason">Reason for ban</label>
                                    <textarea
                                        id="ban-reason"
                                        name="reason"
                                        rows="4"
                                        maxlength="1000"
                                        required
                                        placeholder="Explain why this account is being banned..."
                                    ></textarea>
                                </div>

                                <div class="admin-ban-field">
                                    <label for="ban-expiry">Ban expiry (optional)</label>
                                    <input
                                        type="datetime-local"
                                        id="ban-expiry"
                                        name="expires_at"
                                    >
                                    <small>Leave empty for a permanent ban.</small>
                                </div>

                                <button
                                    type="submit"
                                    class="admin-ban-button"
                                    onclick="return confirm('Are you sure you want to ban this user?');"
                                >
                                    Ban user
                                </button>

                            </form>

                        </div>

                    <?php else: ?>

                        <p class="admin-user-status-note">
                            Admin accounts cannot be banned through this page.
                        </p>

                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </section>

        <!-- Moderation history -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Moderation history</h3>
                <p>Previous and current bans recorded for this account.</p>
            </header>

            <?php if (empty($banHistory)): ?>

                <p class="admin-content-empty">
                    No previous moderation actions found for this user.
                </p>

            <?php else: ?>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ban date</th>
                                <th>Reason</th>
                                <th>Issued by</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Revoked by</th>
                                <th>Revocation reason</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($banHistory as $ban): ?>

                                <?php
                                if ($ban["revoked_at"] !== null) {
                                    $historyStatus = "Revoked";
                                } elseif (
                                    $ban["expires_at"] !== null
                                    && strtotime($ban["expires_at"]) <= time()
                                ) {
                                    $historyStatus = "Expired";
                                } else {
                                    $historyStatus = "Active";
                                }
                                ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($ban["banned_at"], ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= nl2br(htmlspecialchars($ban["reason"], ENT_QUOTES, "UTF-8")) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ban["banned_by_username"] ?? "Unknown / account deleted", ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ban["expires_at"] ?? "Permanent", ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($historyStatus, ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ban["revoked_by_username"] ?? ($ban["revoked_by"] !== null ? "Unknown / account deleted" : "—"), ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= $ban["revocation_reason"] !== null
                                            ? nl2br(htmlspecialchars($ban["revocation_reason"], ENT_QUOTES, "UTF-8"))
                                            : "—"
                                        ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

        <!-- Recent posts -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Recent posts</h3>
                <p>Latest posts created by this user.</p>
            </header>

            <?php if (empty($posts)): ?>

                <p class="admin-content-empty">This user has not created any posts.</p>

            <?php else: ?>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Topic</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_post.php?id=<?= (int) $post["post_id"] ?>&from=user&return_user_id=<?= (int) $user["user_id"] ?>"
                                    aria-label="Review post <?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <td><?= (int) $post["post_id"] ?></td>
                                    <td><?= htmlspecialchars($post["title"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($post["topic"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($post["created_at"], ENT_QUOTES, "UTF-8") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

        <!-- Recent comments -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Recent comments</h3>
                <p>Latest comments written by this user.</p>
            </header>

            <?php if (empty($comments)): ?>

                <p class="admin-content-empty">This user has not written any comments.</p>

            <?php else: ?>

                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Comment</th>
                                <th>Related post</th>
                                <th>Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($comments as $comment): ?>
                                <?php
                                $commentPreview = $comment["comment"];

                                if (function_exists("mb_strimwidth")) {
                                    $commentPreview = mb_strimwidth($commentPreview, 0, 90, "...");
                                } elseif (strlen($commentPreview) > 90) {
                                    $commentPreview = substr($commentPreview, 0, 87) . "...";
                                }
                                ?>

                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_comment.php?id=<?= (int) $comment["comment_id"] ?>&from=user&return_user_id=<?= (int) $user["user_id"] ?>"
                                    aria-label="Review comment <?= (int) $comment["comment_id"] ?>"
                                >
                                    <td><?= (int) $comment["comment_id"] ?></td>

                                    <td>
                                        <?= htmlspecialchars($commentPreview, ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($comment["post_title"] ?? "Unavailable", ENT_QUOTES, "UTF-8") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($comment["created_at"], ENT_QUOTES, "UTF-8") ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>

    <?php endif; ?>

</section>

<script>
document.querySelectorAll(".admin-clickable-row").forEach(row => {
    row.addEventListener("click", () => {
        window.location.href = row.dataset.href;
    });

    row.addEventListener("keydown", event => {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            window.location.href = row.dataset.href;
        }
    });
});
</script>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
