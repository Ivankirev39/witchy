<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Manage Users | Witchy";
$pageCss = "admin_users.css";

$search = trim($_GET["search"] ?? "");
$users = [];
$error = "";

try {
    if ($search !== "") {
        $stmt = $conn->prepare("
            SELECT user_id, username, rank
            FROM users
            WHERE username LIKE ? OR email LIKE ?
            ORDER BY user_id DESC
        ");

        $term = "%" . $search . "%";
        $stmt->execute([$term, $term]);
    } else {
        $stmt = $conn->query("
            SELECT user_id, username, rank
            FROM users
            ORDER BY user_id DESC
        ");
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin users page failed: " . $e->getMessage());
    $error = "Users could not be loaded. Please try again later.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-users-page">

    <div class="users-page-heading">
        <div>
            <span class="users-eyebrow">ADMINISTRATION</span>
            <h1>Manage Users</h1>
            <p>
                View registered Witchy members and their current account roles.
            </p>
        </div>

        <div class="users-heading-decoration" aria-hidden="true">
            ♡
        </div>
    </div>

    <div class="users-overview">
        <div class="users-overview-icon" aria-hidden="true">✧</div>

        <div>
            <span class="users-overview-label">MEMBER DIRECTORY</span>
            <h2>Community accounts</h2>
            <p>
                Browse registered accounts and check their assigned roles.
                Email addresses and other private account details are kept out
                of this overview.
            </p>
        </div>
    </div>

    <div class="users-section-heading">
        <div>
            <h2>Registered members</h2>
            <p>Search and browse the Witchy community.</p>
        </div>

        <span class="users-count">
            <?= count($users) ?> found
        </span>
    </div>

    <div class="users-search-panel">
        <form method="GET" action="users.php" class="users-search-form">
            <div class="users-search-field">
                <label for="search">Search members</label>
                <input
                    type="search"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                    placeholder="Search by username or email..."
                >
            </div>

            <button type="submit" class="users-search-button">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="users.php" class="users-clear-button">
                    Clear search
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($error !== ""): ?>

        <div class="users-message users-error" role="alert">
            <span aria-hidden="true">!</span>
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php else: ?>

        <div class="users-table-panel">
            <div class="users-table-heading">
                <div>
                    <h3>Member accounts</h3>
                    <p>
                        <?php if ($search !== ""): ?>
                            Results matching
                            <strong>
                                "<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
                            </strong>
                        <?php else: ?>
                            All registered members
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="users-table-wrap">
                <table class="admin-table users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="3" class="users-empty">
                                    <span aria-hidden="true">✧</span>
                                    <strong>No members found</strong>
                                    <p>
                                        Try another search term or clear your search.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="users-id">
                                        #<?= (int) $user["user_id"] ?>
                                    </td>

                                    <td class="users-username">
                                        <span class="users-avatar" aria-hidden="true">
                                            <?= htmlspecialchars(
                                                strtoupper(substr($user["username"] ?? "?", 0, 1)),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>

                                        <span>
                                            <?= htmlspecialchars(
                                                $user["username"] ?? "",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        $role = $user["rank"] ?? "user";
                                        $roleClass = $role === "admin"
                                            ? "users-role-admin"
                                            : "users-role-member";
                                        ?>

                                        <span class="users-role <?= $roleClass ?>">
                                            <?= htmlspecialchars(
                                                ucfirst($role),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="users-table-footer">
                Showing <?= count($users) ?> member(s)
            </div>
        </div>

    <?php endif; ?>

    <div class="users-bottom-note">
        <span aria-hidden="true">✧</span>
        <p>
            Member information should be handled respectfully. Only account
            details needed for administration are displayed here.
        </p>
    </div>

</section>

<?php require_once __DIR__ . "/includes/admin_footer.php"; ?>
