<?php
require_once __DIR__ . "/includes/admin_auth.php";
require_once __DIR__ . "/../config/db.php";

$pageTitle = "Manage Users | Witchy";
$pageCss = "admin_users.css";

// Search
$search = trim($_GET["search"] ?? "");

$users = [];
$totalUsers = 0;

try {
    // Count matching users
    $countSql = "
        SELECT COUNT(*)
        FROM users
    ";

    if ($search !== "") {
        $countSql .= "
            WHERE username LIKE :search_username
               OR email LIKE :search_email
        ";
    }

    $countStmt = $conn->prepare($countSql);

    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $countStmt->execute([
            ":search_username" => $searchTerm,
            ":search_email" => $searchTerm
        ]);
    } else {
        $countStmt->execute();
    }

    $totalUsers = (int) $countStmt->fetchColumn();


    // Fetch matching users
    $sql = "
        SELECT
            user_id,
            username,
            rank
        FROM users
    ";

    if ($search !== "") {
        $sql .= "
            WHERE username LIKE :search_username
               OR email LIKE :search_email
        ";
    }

    $sql .= "
        ORDER BY user_id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($search !== "") {
        $searchTerm = "%" . $search . "%";

        $stmt->execute([
            ":search_username" => $searchTerm,
            ":search_email" => $searchTerm
        ]);
    } else {
        $stmt->execute();
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin users page error: " . $e->getMessage());
    $errorMessage = "Users could not be loaded. Please try again.";
}

require_once __DIR__ . "/includes/admin_header.php";
?>

<section class="admin-content-page">

    <header class="admin-content-heading">
        <h2>Manage users</h2>
        <p>Review Witchy community accounts and their roles.</p>
    </header>

    <!-- User statistics -->
    <div class="admin-stats-grid">
        <div class="admin-stat-card">
            <span class="admin-stat-label">Total users</span>
            <strong class="admin-stat-number">
                <?= $totalUsers ?>
            </strong>
        </div>
    </div>

    <!-- Search -->
    <section class="admin-content-panel">

        <form method="GET" action="users.php" class="admin-search-form">

            <input
                type="text"
                name="search"
                placeholder="Search by username or email..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            >

            <button type="submit" class="admin-button">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="users.php" class="admin-button-secondary">
                    Clear
                </a>
            <?php endif; ?>

        </form>

    </section>

    <?php if (!empty($errorMessage)): ?>

        <div class="admin-content-error" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php else: ?>

        <!-- Users table -->
        <section class="admin-content-panel">

            <header class="admin-content-panel-header">
                <h3>Community members</h3>
                <p>
                    <?= $totalUsers ?>
                    <?= $totalUsers === 1 ? "account" : "accounts" ?> found.
                    Select a row to review an account.
                </p>
            </header>

            <div class="admin-table-wrapper">

                <table class="admin-table">

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
                                <td colspan="3" class="admin-content-empty">
                                    No users found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($users as $user): ?>

                                <tr
                                    class="admin-clickable-row"
                                    tabindex="0"
                                    role="link"
                                    data-href="view_user.php?id=<?= (int) $user["user_id"] ?>"
                                    aria-label="Review user <?= htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8") ?>"
                                >

                                    <td>
                                        <?= (int) $user["user_id"] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $user["username"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            ucfirst($user["rank"]),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

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
